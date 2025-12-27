<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 上午 11:02
 */

namespace app\admin\controller;

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class MessageController extends AdminBaseController {
	/**
	 *   系统消息个人
	 */
	public function index() {
		$Message = Db::name("user_message")->select();
		//print_($Message);exit;
        $type = [
            '1'=> lang('Identity_authentication_passed'), //身份认证通过
            '2'=> lang('Identity_authentication_failed'), // 身份认证失败
            '3'=> lang('private_license_is_approved'), // 私照审核通过
            '4'=> lang('Private_license_review_failed'), //私照审核失败
            '5'=> lang('Short_video_review_passed'), //短视频审核通过
            '6'=> lang('Short_video_review_failed'), //短视频审核失败
            '7'=> lang('Chatting_partner_certification_passed'), //陪聊师认证通过
            '8'=> lang('Chatter_authentication_failed'), //陪聊师认证失败
            '9'=> lang('accompanier_has_passed_certification'), //陪玩师认证通过
            '10'=> lang('Accompanier_authentication_failed'), //陪玩师认证失败
            '11'=> lang('Official_certification_passed'), //官方认证通过
            '12'=> lang('Official_authentication_failed'), //官方认证失败
            '13'=> lang('Like_message'), //点赞消息
            '14'=> lang('Reward_message'), //打赏消息
            '15'=> lang('Follow_news'), //关注消息
            '16'=> lang('Close_friend_news'), //密友消息
            '17'=> lang('Recharge_message'), // 充值后台消息--充值到账消息
        ];
		$this->assign('type', $type);
		$this->assign('gift', $Message);
		return $this->fetch();
	}

	/**
	 * 添加消息个人
	 */
	public function add() {
		$id = input('param.id');
		if ($id) {
			$name = Db::name("user_message")->where("id=$id")->find();
		}else{
            $name = ['type'=>1];
        }
        $this->assign('message', $name);
		//分类
        $type = [
            [
                'id'=>1,
                'name'=> lang('Identity_authentication_passed'), // 身份认证通过
            ],
            [
                'id'=>2,
                'name'=> lang('Identity_authentication_failed'), //身份认证失败
            ],
            [
                'id'=>3,
                'name'=> lang('private_license_is_approved'), //私照审核通过
            ],
            [
                'id'=>4,
                'name'=> lang('Private_license_review_failed'), // 私照审核失败
            ],
            [
                'id'=>5,
                'name'=> lang('Short_video_review_passed'), //短视频审核通过
            ],
            [
                'id'=>6,
                'name'=> lang('Short_video_review_failed'), //短视频审核失败
            ],
            [
                'id'=>13,
                'name'=> lang('Like_message'), //点赞消息
            ],[
                'id'=>14,
                'name'=> lang('Reward_message'), //打赏消息
            ],[
                'id'=>15,
                'name'=> lang('Follow_news'), //关注消息
            ],[
                'id'=>16,
                'name'=> lang('Close_friend_news'), //密友消息
            ],[
                'id'=>17,
                'name'=> lang('Recharge_message'), // 充值到账消息
            ],
        ];
        $this->assign('type_list',$type);
		return $this->fetch();
	}

	//保存消息个人
	public function addPost() {
		$param = $this->request->param();
		//  print_r($param);exit;
		$id = $param['id'];
		$data = $param['post'];
		$data['addtime'] = time();
		if ($id) {
			$result = Db::name("user_message")->where("id=$id")->update($data);
		} else {
			$result = Db::name("user_message")->insert($data);
		}
		if ($result) {
			$this->success(lang('EDIT_SUCCESS'), url('message/index'));
		} else {
			$this->error(lang('EDIT_FAILED'));
		}
	}

	/**
	 *   系统消息所有人
	 */
	public function all() {
		$Message = Db::name("user_message_all")->select();
		//  print_($Message);exit;
		$this->assign('gift', $Message);
		return $this->fetch();
	}

	/**
	 * 添加消息所有人
	 */
	public function add_all() {
		$id = input('param.id');
		if ($id) {
			$name = Db::name("user_message_all")->where("id=$id")->find();
			$this->assign('message', $name);
		}

		return $this->fetch();
	}

	//保存消息所有人
	public function addPost_all() {
		$param = $this->request->param();
		//  print_r($param);exit;
		$id = $param['id'];
		$data = $param['post'];
		$data['addtime'] = time();
        if ($data['url']) {
            // 判断url是否是链接
            if(substr($data['url'], 0, 4) !="http") {
                $this->error("系统消息连接地址错误");
            }
        }
		if ($id) {
			$result = Db::name("user_message_all")->where("id=$id")->update($data);
		} else {
			$result = Db::name("user_message_all")->insert($data);
		}
		if ($result) {
			$this->success(lang('EDIT_SUCCESS'), url('message/all'));
		} else {
			$this->error(lang('EDIT_FAILED'));
		}
	}

	//消息推送记录
	public function charge() {

		$where = [];
		if (isset($_REQUEST['type']) && $_REQUEST['type'] != '' && $_REQUEST['type'] != '-1') {
			$where['a.type'] = $_REQUEST['type'];
		} else {
			$_REQUEST['type'] = '-1';
		}

		if (isset($_REQUEST['status']) && $_REQUEST['status'] != '' && $_REQUEST['status'] != '-1') {
			$where['a.status'] = $_REQUEST['status'];
		} else {
			$_REQUEST['status'] = '-1';
		}

		if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
			$where['a.uid'] = $_REQUEST['uid'];
		}
		if (isset($_REQUEST['touid']) && $_REQUEST['touid'] != '') {
			$where['a.touid'] = $_REQUEST['touid'];
		}

		$user = Db::name("user_message_log")
			->alias("a")
			->where($where)
			->order('a.addtime desc')
			->paginate(20, false, ['query' => request()->param()]);

		$lists = $user->toArray();

		foreach ($lists['data'] as &$v) {
            $uid = $v['touid'];
            if($uid >0){
                //个人和管理员推送时获取被推送人的名称
                $users = Db::name("user")->where("id=$uid")->find();
                $v['toname'] = $users['user_nickname'];
            }else{
                if($uid =='-1'){
                    $v['toname'] = lang('Authenticated_user');
                    $v['touid']=0;
                }elseif($uid =='-2'){
                    $v['toname'] = lang('Unauthenticated_user');
                    $v['touid']=0;
                }else{
                    $v['toname'] = lang('All_users');
                }
            }

			if ($_REQUEST['type'] == 1) {
				$mid = $v['messageid'];
				$users = Db::name("user_message")->where("id=$mid")->find();
				$v['messagetype'] = $users['title'] . $v['messagetype'];
			}
		}
		//print_r($lists['data']);exit;
		$this->assign('user', $lists['data']);
		$this->assign('request', $_REQUEST);
		$this->assign('page', $user->render());
		return $this->fetch();
	}

	//推送数据
	public function push_all() {
		$id = input("param.id");
        $type = input("param.type");
        $name = input("param.name");
        if($type==2){
            $userid=$name ? $name :0;
        }elseif($type==3){
            $userid='-1';
        }elseif($type==4){
            $userid='-2';
        }else{
            $userid=0;
        }
		$res = push_msg($id,$userid, 2);

		if ($res) {
			echo 1;
			exit;
		}

		echo 0;
		exit;
	}

	public function del_all(){
        $param = $this->request->param();
        //  print_r($param);exit;
        $id = $param['id'];

        $result = Db::name("user_message_all")->where("id=$id")->delete();
        return $result?1:0;
    }

}
