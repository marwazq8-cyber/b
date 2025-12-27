<?php

namespace app\vue\controller;

use think\Db;
use app\vue\model\UserModel;

class  MessageApi extends Base
{
	protected function _initialize()
    {
        parent::_initialize();

        $this->UserModel = new UserModel();
    }

    //系统消息
    public function index(){

    	$result = array('code' => 1, 'msg' => '');

    	$uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $page = intval(input('param.page')) ? intval(input('param.page')) : 1;
        
        $user_info = check_login_token($uid, $token,['last_remove_message_time','is_auth','create_time']);
        // 创建的时间
        $last_time =$user_info['create_time'];

        $is_auth = $user_info['is_auth'] == '1' ? '-1' : '-2';

        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and addtime > $last_time";

        $message_log =$this->UserModel ->get_user_message_log($where,$page);

        $this->UserModel ->update_user("id=$uid",array('last_remove_message_time' => NOW_TIME));
      
        $data = array();
        foreach ($message_log as $k => $v) {

            $data[$k]['id'] = $v['id'];
            $id = $v['messageid'];
            if ($v['type'] == 1) {
                //后台管理员审核操作
                $message_list = $this->UserModel -> get_user_message("id=$id");

                $data[$k]['title'] = $message_list['centent'];
                $data[$k]['url'] = '';
            } elseif ($v['type'] == 2) {
                //后台系统消息
                $message_list = $this->UserModel -> get_user_message_all("id=$id");

                $data[$k]['title'] = $message_list['title'];
                $data[$k]['url'] = $message_list['url'];
            } else {
                $message_list = $this->UserModel -> get_user("id=".$v['uid']);
                $data[$k]['title'] = $message_list['user_nickname'] . lang('Message_for');
                $data[$k]['url'] = '';
            }
            // 内容
            $data[$k]['centent'] = $v['messagetype'];     
            // 时间   
            $data[$k]['addtime'] = date('Y-m-d H:i',$v['addtime']);           

        }

        $result['data'] =$data;

        return_json_encode($result);
    }


}
?>