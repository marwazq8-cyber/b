<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/6/18
 * Time: 22:45
 */

namespace app\admin\controller;

use app\common\Enum;
use cmf\controller\AdminBaseController;
use VideoCallRedis;

class VideoCallListController extends AdminBaseController {

	public function index() {

		$list = db('video_call_record')
			->order("create_time DESC")
			->paginate(20);

		$data = $list->toArray();
		$page = $list->render();

		foreach ($data['data'] as &$v) {
			$base_field = 'id,avatar,user_nickname,sex,level,coin';

			$emcee_info = db('user')->field($base_field)->find($v['anchor_id']);
			$v['emcee_info'] = $emcee_info;

			$user_id = $v['anchor_id'] == $v['user_id'] ? $v['call_be_user_id'] : $v['user_id'];
			$user_info = db('user')->field($base_field)->find($user_id);
			$v['user_info'] = $user_info;

		}

		$this->assign('list', $data['data']);
		$this->assign('page', $page);
		return $this->fetch();
	}

	//查看视频
	public function select_video() {

		$id = input('param.id');
		$video = db('video_call_record')
			->find($id);

		$config = load_cache('config');

		$this->assign('channel_id', $video['channel_id']);
		$this->assign('app_qgorq_key', $config['app_qgorq_key']);

		return $this->fetch();
	}

	//关闭视频通话
	public function close() {
		$id = input('param.id');
		$video = db('video_call_record')->find($id);

		$ext = array();
        if($video['type']==1){
            $ext['type'] = Enum::CLOSE_VOICE_CALL; //type 94 关闭语音通话
        }else{
            $ext['type'] = Enum::CLOSE_VIDEO2_CALL; //type 96 关闭视频通话
        }
		$ext['msg_content'] = lang('Video_content_violates_laws_regulations');

		$config = load_cache('config');
		require_once DOCUMENT_ROOT . '/system/im_common.php';
		$ser = open_one_im_push($config['tencent_identifier'], $video['anchor_id'], $ext);

		if ($ser['ActionStatus'] == 'OK') {
			$this->success(lang('Operation_successful'));
            //删除拨打视频通话缓存记录
            require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
            $video_call_redis = new VideoCallRedis();

            $video['end_time'] = NOW_TIME;
            $video['status'] = 3;
                // 通话时长
            $video['call_time'] = $video['end_time'] - $video['create_time'];
                // 删除通话记录，添加日志记录
                //$VideoCallModel -> del_video_call_record($v['id']);
            db('video_call_record')->where('id ='.$id)->delete();
                //$VideoCallModel -> add_video_call_record_log($v);
            unset($video['id']);
            db('video_call_record_log')->insert($video);
            $video_call_redis->del_call($video['anchor_id']);


		} else {
			$this->error(lang('operation_failed'));
		}
		exit;
	}
	//通话记录
	public function record() {
        $p = $this->request->param('page');

        if ($this->request->param('uid') || $this->request->param('host') || $this->request->param('start_time') || $this->request->param('end_time') || $this->request->param('status') || empty($p)) {

            $data['uid'] = $this->request->param('uid');
            $data['host'] = $this->request->param('host');
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : date('Y-m-d',time());
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') :date('Y-m-d',time());
            $data['status'] = $this->request->param('status') ? $this->request->param('status') :'-1';

            session("admin_record", null);
            session("admin_record",$data);

        }

        $uid = session("admin_record.uid");
        $host = session("admin_record.host");
        $status = session("admin_record.status");
        $starttime = strtotime(session("admin_record.start_time") . " 00:00:00");
        $endtime = strtotime(session("admin_record.endtime") . " 23:59:59");
        $where = "create_time >=".$starttime." and end_time <= ".$endtime;

        if ($uid) {
            $where .= " and (user_id=".$uid." or call_be_user_id=".$uid.") and anchor_id !=".$uid;
        }
        if ($host) {
            $where .= " and anchor_id =".$host;
        }
        if ($status !='-1') {
            $where .= " and status=".$status;
        }

        $list = db('video_call_record_log')
            ->where($where)
			->order("create_time DESC")
			->paginate(20, false, ['query' => request()->param()]);

		$data = $list->toArray();
		$page = $list->render();

		foreach ($data['data'] as &$v) {
			$base_field = 'id,avatar,user_nickname,sex,level,coin';

			$emcee_info = db('user')->field($base_field)->find($v['anchor_id']);
			$v['emcee_info'] = $emcee_info;

			$user_id = $v['anchor_id'] == $v['user_id'] ? $v['call_be_user_id'] : $v['user_id'];
			$user_info = db('user')->field($base_field)->find($user_id);
			$v['user_info'] = $user_info;

		}

		$this->assign('list', $data['data']);
        $this->assign('requery',session("admin_record"));
		$this->assign('page', $page);
		return $this->fetch();
	}
    //通话礼物详情
    public function select_call() {
        $id = input('param.id');
        $list = db('video_call_record_log')->alias("v")
            ->join("user_gift_log g","g.channel_id=v.channel_id")
            ->join("user u","u.id=g.user_id")
            ->join("user s","s.id=g.to_user_id")
            ->field("g.*,u.user_nickname as uname,s.user_nickname as toname")
            ->where("v.id=".$id)
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();
        $page = $list->render();

        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        return $this->fetch();
    }
    //通话详情统计
    public function select_details(){
        $id = input('param.id');
        //礼物
        $coin= db('user_gift_log')->alias("v")
                    ->field("sum(v.gift_coin * v.gift_count) as total_coin,sum(v.profit) as profit")
                    ->join("video_call_record_log g","g.channel_id=v.channel_id")
                    ->where("g.id=".$id)
                    ->find();
        //通话计时
        $money= db('video_charging_record')->alias("v")
            ->field("sum(v.coin) as coin,sum(v.profit) as profit")
            ->join("video_call_record_log g","g.channel_id=v.channel_id")
            ->where("g.id=".$id)
            ->find();

        $data['total_coin']=$coin['total_coin'];
        $data['total_profit']=$coin['profit'];
        $data['coin']=$money['coin'];
        $data['profit']=$money['profit'];

        $data['sum_coin']=$money['coin']+$coin['total_coin'];
        $data['sum_profit']=$money['profit']+$coin['profit'];
        $data['id']=$id;

        $this->assign('list', $data);
        return $this->fetch();
    }
    //通话消费详情
    public function select_del(){
        $id = input('param.id');
        $list = db('video_call_record_log')->alias("v")
            ->join("video_charging_record g","g.channel_id=v.channel_id")
            ->join("user u","u.id=g.user_id")
            ->join("user s","s.id=g.to_user_id")
            ->field("g.*,u.user_nickname as uname,s.user_nickname as toname")
            ->where("v.id=".$id)
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();
        $page = $list->render();

        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        return $this->fetch();
    }
    //删除一对一通话
    public function del(){
        $id = input('param.id');
        db('video_call_record') -> delete($id);
        echo 1;
        exit;
    }
}
