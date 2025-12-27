<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/6/18
 * Time: 22:45
 */

namespace app\admin\controller;
require_once DOCUMENT_ROOT . '/system/im_common.php';

use app\common\Enum;
use cmf\controller\AdminBaseController;

class LiveController extends AdminBaseController
{
    //直播中列表
    public function index(){

        $list = db('live')
            ->where(array("live_in" => 1))
            ->order("lid DESC")
            ->paginate(20);

        $data = $list->toArray();
        $page = $list->render();

        foreach ($data['data'] as &$v){
            $base_field = 'id,avatar,user_nickname,sex';

            $user_info = db('user') -> field($base_field) -> find($v['user_id']);
//            $v['emcee_info'] = $emcee_info;

//            $user_id = $v['anchor_id'] == $v['user_id'] ? $v['call_be_user_id'] : $v['user_id'];
//            $user_info = db('user') -> field($base_field) -> find($user_id);
            $v['user_info'] = $user_info;

        }
//        dump($data);

        $this->assign('list',$data['data']);
        $this->assign('page', $page);
        return $this -> fetch();
    }
    //监控
    public function monitor(){

        $list = db('live')
            ->where(array("live_in" => 1))
            ->order("lid DESC")
            ->paginate(20);

        $data = $list->toArray();
        $page = $list->render();

        foreach ($data['data'] as &$v){
            $base_field = 'id,avatar,user_nickname,sex';

            $user_info = db('user') -> field($base_field) -> find($v['user_id']);
//            $v['emcee_info'] = $emcee_info;

//            $user_id = $v['anchor_id'] == $v['user_id'] ? $v['call_be_user_id'] : $v['user_id'];
//            $user_info = db('user') -> field($base_field) -> find($user_id);
            $v['user_info'] = $user_info;

        }
//        dump($data);

        $this->assign('list',$data['data']);
        $this->assign('page', $page);
        return $this -> fetch();
    }
    //直播记录
    public function livelog(){

        $list = db('live')
            ->where(array("live_in" => 0))
            ->order("lid DESC")
            ->paginate(20);

        $data = $list->toArray();
        $page = $list->render();

        foreach ($data['data'] as &$v){
            $base_field = 'id,avatar,user_nickname,sex';

            $user_info = db('user') -> field($base_field) -> find($v['user_id']);
            $v['user_info'] = $user_info;

        }
        $this->assign('list',$data['data']);
        $this->assign('page', $page);
        return $this -> fetch();
    }

    //查看直播
    public function liveInfo(){

        $id = input('param.id');
        $live = db('live')
            ->find($id);

        $config = load_cache('config');

        $this->assign('channel_id',$live['lid']);
//        $this->assign('channel_id',$live['user_id']);
        $this->assign('app_qgorq_key',$config['app_qgorq_key']);

        return $this -> fetch();
    }

    //关闭视频通话
    public function close(){
        $id = input('param.id');
        $video = db('video_call_record')
            ->find($id);

        $ext = array();
        $ext['type'] = Enum::CLOSE_VIDEO_CALL;//type 25 关闭视频通话
        $ext['msg_content'] = lang('Video_content_violates_laws_regulations');

        $config = load_cache('config');
        require_once DOCUMENT_ROOT . '/system/im_common.php';
        $ser = open_one_im_push($config['tencent_identifier'],$video['anchor_id'],$ext);

        if($ser['ActionStatus'] =='OK'){
            $this->success(lang('Operation_successful'));

        }else{
            $this->error(lang('operation_failed'));
        }
        exit;
    }
    //关闭直播间
    public function close_live(){
        $id = input('param.id');
        $video = db('live')->find($id);

        $data['live_in'] = 0;//live_in:是否直播中 1-直播中 0-已停止;2:正在创建直播;
        $data['end_time'] = NOW_TIME;//'结束时间';

        $re = db('live')->where(array('lid' => $id))->update($data);
        if($re){
            $ext = array();
            $ext['type'] = Enum::CLOSE_LIVE; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束

            $ext['msg'] = lang('End_of_live_broadcast');

            $ext['info']['room_id'] = $video['lid'];//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
            $ext['info']['vote_number'] = $video['vote_number'];//收益数量
            // $ext['info']['gift_id'] = $gid;
            $ext['info']['watch_number'] = get_live_people_count($video['lid']);//观看人数
            // $ext['info']['fonts_color'] = '#333333';//字体颜色
            // $ext['info']['desc'] = $gift['name'];//弹幕消息;

            //发送直播间关闭群消息
             require_once DOCUMENT_ROOT . '/system/im_common.php';
          
            $re = qcloud_group_send_group_msg2_ext(1, $video['group_id'], $ext);
            //销毁群组
            qcloud_group_destroy_group($video['group_id']);
        

             $this->success(lang('Operation_successful'));

        }else{
            $this->error(lang('operation_failed'));
        }
      


    }

    //PK时间列表
    public function pkTime(){

        $where = ["status" => 1];
        $pk_time = db('live_pk_time')
            ->where($where)
            ->order("sort asc")
            ->paginate(10, false, ['query' => request()->param()]);
        // 获取分页显示
        $page = $pk_time->render();
        $this->assign("page", $page);
        $this->assign("list", $pk_time);
        return $this->fetch();
    }
    //PK时长增加
    public function pkTimeAdd() {
        return $this->fetch();
    }
    //保存增加时长
    public function pkTimeAddPost(){
        if ($this->request->isPost()) {
            $data = $_POST;
            $time = time();
            $data['status'] = 1;
            $data['create_time'] = $time;
            $data['modify_time'] = $time;
            $result = db('live_pk_time')->insertGetId($data);
            if ($result !== false) {
                $this->success(lang('EDIT_SUCCESS'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }

        }
    }
    //PK时长编辑
    public function pkTimeModify(){
        $id = input('param.id');
        $time = db('live_pk_time')
            ->find($id);
        $this->assign('pkTime',$time);
        return $this->fetch();
    }
    //保存编辑
    public function pkTimeModifyPost(){
        if ($this->request->isPost()) {
            $data = $_POST;
            $data['modify_time'] = time();
            $result = db('live_pk_time')->update($data);
            if ($result !== false) {
                $this->success(lang('EDIT_SUCCESS'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }

        }
    }
    //删除
    public function pkTimeDelete(){
        $id = input('param.id');
        $data['id'] = $id;
        $data['status'] = 0;
        $data['modify_time'] = time();
        $result = db('live_pk_time')->update($data);
        if ($result !== false) {
            $this->success(lang('DELETE_SUCCESS'));
        } else {
            $this->error(lang('DELETE_FAILED'));
        }
    }
    //添加直播
    public function add(){

        return $this -> fetch();
    }
    //增加直播
    public function addpost(){
        $data['user_id'] = input('param.user_id');
        $data['title'] = input('param.title');
        $data['live_image'] = input('param.img');
        $data['video_vid'] = input('param.video_vid');
        $user_info = db('user') -> field("sex") -> find($data['user_id']);

        if(!$user_info){
            $this->error(lang('Anchor_does_not_exist'));
        }

         $live = db('live')->where("user_id =".$data['user_id']." and (live_in =1 or live_in =3)") -> find();
          
         if($live){
            $this->error(lang('anchor_is_broadcasting_live'));
         }
        if($data['title'] ==''){
            $this->error(lang('Please_enter_live_broadcast_title'));
        }
     /*   if(!$data['live_image']){
            $this->error(lang("请上传直播封面地址"));
        }*/
        if(!$data['video_vid']){
            $this->error(lang('Please_enter_live_broadcast_address'));
        }
        $data['sex']=$user_info['sex'];
        $data['create_time']=time();
        $data['begin_time']=time();
        $data['is_false_video']=1;
        $data['play_mp4']=$data['video_vid'];
        $lid = db('live')->insertGetId($data);

        if($lid){
                    //创建群组
            $ret = qcloud_group_create_group('AVChatRoom', $data['user_id'] . '-' . $lid, (string)$data['user_id'], (string)$lid);
          if($ret['ActionStatus'] =='OK'){
            $name=array('group_id'=>$ret['GroupId']);
              $result = db('live')->where("lid=".$lid)->update($name);
          }
   //       var_dump($ret );exit;
            //加入Socket群组
      //      $client_id = redis_get($data['user_id']);
    //        joinGroup($client_id, $lid);
             $this->success(lang('ADD_SUCCESS'));
         }else{
            $this->error(lang('ADD_FAILED'));
         }
    }
}