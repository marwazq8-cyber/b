<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-06-16
 * Time: 11:24
 */

namespace app\vue\controller;

use think\Db;
use cmf\controller\ApiController;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author:
// +----------------------------------------------------------------------
class SystemMessageApi extends Base
{

    //获取系统消息列表
    public function get_system_message()
    {

        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);

        $last_time = $user_info['last_remove_message_time'] ? $user_info['last_remove_message_time'] : $user_info['create_time'];

        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }
        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and messageid !=0  and addtime > $last_time";;
        $message_log = db("user_message_log")
            ->where($where)
            ->order('addtime desc')
            ->page($page)
            ->select();
        $list = array();
        foreach ($message_log as $k => $v) {

            $list[$k]['id'] = $v['id'];
            $list[$k]['is_see'] = 1;
            $id = $v['messageid'];
            if ($v['messageid'] == 13 || $v['messageid'] == 14 || $v['messageid'] == 15 || $v['messageid'] == 16) {
                // 配玩认证的前端不显示查看按钮
                $list[$k]['is_see'] = 0;
            }
            if ($v['type'] == 1) {
                //后台管理员审核操作
                $message_list = db("user_message")->where("id=$id")->find();
                $list[$k]['title'] = $message_list['title'];// . ':' . $v['messagetype'];
                $list[$k]['url'] = $v['jump_url'];
                $list[$k]['type'] = 1;
            } else {
                //后台系统消息
                $message_list = Db::name("user_message_all")->where("id=$id")->find();
                if ($message_list) {
                    $list[$k]['title'] = $message_list['title'];
                    $list[$k]['url'] = $message_list['url'];
                } else {
                    $list[$k]['url'] = '';
                    $list[$k]['is_see'] = 0;
                }
                $list[$k]['type'] = 2;

            }
            if ($v['read_status'] == 0) {
                db('user_message_log')->where('id = ' . $v['id'])->update(['read_status' => 1]);
            }
            //内容
            $list[$k]['centent'] = $v['messagetype'];
            //时间
            $list[$k]['addtime'] = date('m-d H:i:s', $v['addtime']);

        }
        db("user_message_log")->where($where)->update(['read_status' => 1]);

        $result['data'] = $list;
        return_json_encode($result);

    }

    //系统消息详情
    public function get_system_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('param.id'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);
        $where = ['id' => $id];
        $message_log = db("user_message_log")
            ->where($where)
            ->order('addtime desc')
            ->find();
        if ($message_log) {
            //$list[$k]['id'] = $message_log['id'];
            $id = $message_log['messageid'];
            if ($message_log['type'] == 1) {
                //后台管理员审核操作
                $message_list = db("user_message")->where("id=$id")->find();
                $message_log['title'] = $message_list['title'];// . ':' . $v['messagetype'];
                $message_log['url'] = '';
            } else {
                //后台系统消息
                $message_list = Db::name("user_message_all")->where("id=$id")->find();
                $message_log['title'] = $message_list['title'];
                $message_log['url'] = $message_list['url'];
            }

            //内容
            $message_log['centent'] = $message_log['messagetype'];
            //时间
            $message_log['addtime'] = date('m-d H:i:s', $message_log['addtime']);
        }

        $result['data'] = $message_log;
        return_json_encode($result);
    }

    //订单消息
    public function get_system_order()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);
        $list = db('user_msg')->where('type =1 and uid = ' . $uid)->order('id desc')->page($page)->select();
        //全部消息已读
        db('user_msg')->where('read_status = 0 and type = 1 ')->update(['read_status' => 1]);
        foreach ($list as &$v) {
            $v['addtime'] = date('m-d H:i:s', $v['addtime']);
            /*if($v['read_status']==0 && $v['type']==1){
                db('user_msg')->where('id = '.$v['id'])->update(['read_status'=>1]);
            }*/
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //活动消息
    function get_system_activity()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);
        $list = db('user_msg')->where('type =2 ')->order('id desc')->page($page)->select();
        foreach ($list as &$v) {
            $v['addtime'] = date('m-d H:i:s', $v['addtime']);
            $time = $v['endtime'] - NOW_TIME;
            if ($time > 0) {
                $v['endtime'] = secondChanage($time);
            } else {
                $v['endtime'] = lang('Activity_ended');
            }

        }
        $result['data'] = $list;
        return_json_encode($result);
    }


    /**
     *    h5 页面我的消息
     */
    public function index()
    {
        $uid = intval(input("param.uid"));

        if ($uid == 0) {
            echo lang('Page_access_error');
            exit;
        }
        $user_info = Db::name("user")->where("id=$uid")->field("last_remove_message_time,is_auth,create_time")->find();

        $last_time = $user_info['create_time'];
        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }

        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and addtime > $last_time";
        $message_log = Db::name("user_message_log")->where($where)->order('addtime desc')->select();

        db('user')->where("id=$uid")->update(array('last_remove_message_time' => NOW_TIME));

        $data = array();
        foreach ($message_log as $k => $v) {

            $data[$k]['id'] = $v['id'];
            $id = $v['messageid'];
            if ($v['type'] == 1) {
                //后台管理员审核操作
                $message_list = Db::name("user_message")->where("id=$id")->find();

                $data[$k]['title'] = $message_list['centent'];
                $data[$k]['url'] = '';
            } elseif ($v['type'] == 2) {
                //后台系统消息
                $message_list = Db::name("user_message_all")->where("id=$id")->find();

                $data[$k]['title'] = $message_list['title'];
                $data[$k]['url'] = $message_list['url'];
            } else {
                $message_list = Db::name("user")->where("id=" . $v['uid'])->find();
                $data[$k]['title'] = $message_list['user_nickname'] . lang('Message_for');
                $data[$k]['url'] = '';
            }

            $data[$k]['centent'] = $v['messagetype'];         //内容
            $data[$k]['addtime'] = $v['addtime'];            //时间

        }

        $this->assign("message", $data);
        return $this->fetch();
    }

    /*
     * 获取未读信息的数量
     * */
    public function unread_messages()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);

        $last_time = $user_info['last_remove_message_time'] ? $user_info['last_remove_message_time'] : $user_info['create_time'];

        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }

        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and addtime > $last_time";
        $count = db("user_message_log")->where($where)->count();

        //未读消息数量
        $result['sum'] = $count;

        //未处理预约消息数量
        $result['un_handle_subscribe_num'] = db('video_call_subscribe')->where('to_user_id=' . $uid . ' and status=0')->count();
        // 获取最新的一条消息记录
        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and addtime > " . $user_info['create_time'];
        $message = db("user_message_log")->where($where)->order("addtime desc")->find();
        // 内容
        $result['centent'] = $message ? $message['messagetype'] : '';
        $result['sys_time'] = $message ? $message['addtime'] : '0';

        return_json_encode($result);
    }

    /*
     * 活动消息详情
     * */
    public function get_system_activity_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('param.id'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);
        $list = db('user_msg')->where('id = ' . $id)->find();
        if (!$list) {
            $result['code'] = 0;
            $result['msg'] = lang('Activity_ended');
        } else {
            if ($list['url']) {
                $list['is_url'] = 1;
            } else {
                $list['is_url'] = 0;
            }
            $result['data'] = $list;
        }
        return_json_encode($result);
    }

}
