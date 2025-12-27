<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/23 0023
 * Time: 下午 14:53
 */

namespace app\api\controller;

use think\Db;
use cmf\controller\ApiController;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class SystemMessageApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        header('Access-Control-Allow-Origin:*');
    }

    //获取系统消息列表
    public function get_system_message()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);

        $last_time = $user_info['last_remove_message_time'] ? $user_info['last_remove_message_time'] :$user_info['create_time'] ;

        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }
        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and messageid !=0  and addtime > $last_time";;
        $message_log = db("user_message_log")->where($where)->order('addtime desc')->select();
        $list = array();
        foreach ($message_log as $k => $v) {

            $list[$k]['id'] = $v['id'];
            $id = $v['messageid'];
            if ($v['type'] == 1) {
                //后台管理员审核操作
                $message_list = db("user_message")->where("id=$id")->find();
                $list[$k]['title'] = $message_list['title'] . ':' . $v['messagetype'];
                $list[$k]['url'] = '';
            } else {
                //后台系统消息
                //$message_list = Db::name("user_message_all")->where("id=$id")->find();
                //$list[$k]['title'] = $message_list['title'];
                //$list[$k]['url'] = $message_list['url'];
                $list[$k]['url'] = $v['jump_url'];
            }

            //内容
            $list[$k]['centent'] = $message_list['centent'];
            //时间
            $list[$k]['addtime'] = date('Y-m-d', $v['addtime']);

        }

        $result['list'] = $list;
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

        $last_time =$user_info['create_time'];
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

        $last_time = $user_info['last_remove_message_time'] ? $user_info['last_remove_message_time'] :$user_info['create_time'] ;

        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }

        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and addtime > $last_time";
        $count =db("user_message_log")->where($where)->count();

        //未读消息数量
        $result['sum'] = $count;

        //未处理预约消息数量
        $result['un_handle_subscribe_num'] = db('video_call_subscribe')->where('to_user_id=' . $uid . ' and status=0')->count();
        // 获取最新的一条消息记录
        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and addtime > ".$user_info['create_time'];
        $message = db("user_message_log")->where($where)->order("addtime desc")->find();
        // 内容

        $last_time_user = $user_info['last_remove_message_time'] ? $user_info['last_remove_message_time'] :$user_info['create_time'] ;
        $where_user = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and messageid !=0  and addtime > $last_time_user";;
        $result['system_msg_count'] = db("user_message_log")
            ->where($where_user)
            ->where(['read_status'=>0])
            ->count();
        $order_msg = db('user_msg')
            ->where('type =1 and uid = '.$uid)
            ->order('addtime desc')
            ->find();
        $result['order_msg_count'] = db('user_msg')->where('type =1 and uid = '.$uid)->where(['read_status'=>0])->count();

        $result['centent'] =$message ? $message['messagetype'] : '';
        $result['sys_time'] =$message ? $message['addtime'] : '';
        $result['order_centent'] =$order_msg ? $order_msg['title'] : '';
        $result['order_time'] =$order_msg ? $order_msg['addtime'] : '';

        $activity = db('user_msg')
            ->where('type =2 ')
            ->order('addtime desc')
            ->find();
        //想认识你的人
        $know_user_count = db('user_greet_log')
            ->alias('l')
            ->join('user u','u.id = l.uid')
            ->where('l.touid = '.$uid.' and l.status = 0 and l.uid != '.$uid)
            ->count();
        //派单消息
        $dispatch_msg = db('voice_dispatch_msg')
            ->alias('m')
            ->join('voice_dispatch_log d','d.id=m.dispatch_id')
            ->join('play_game g','g.id=d.game_id')
            ->where('m.user_id = '.$uid)
            ->field('d.remark,g.name')
            ->order('m.id desc')
            ->find();
        //派单消息数量
        $result['dispatch_count'] = db('voice_dispatch_msg')->where('read_status = 0 and user_id = '.$uid)->count();
        if($dispatch_msg){
            $result['dispatch_centent'] = $dispatch_msg['name'].'：'.$dispatch_msg['remark'];
        }else{
            $result['dispatch_centent'] = '';
        }
        $result['activity_count'] =0;
        $result['activity_centent'] =$activity ? $activity['title'] : '';
        $result['activity_time'] =$activity ? $activity['addtime'] : '';
        $result['know_user_count'] = $know_user_count;

        return_json_encode($result);
    }

    //派单消息
    public function get_dispatch_msg(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        $user_info = check_login_token($uid, $token, ['last_remove_message_time,create_time']);
        //设置为已读
        db('voice_dispatch_msg')->where('read_status = 0 and user_id = '.$uid)->update(['read_status'=>1]);
        //消息列表
        $dispatch_msg_list = db('voice_dispatch_msg')
            ->alias('m')
            ->join('voice_dispatch_log d','d.id=m.dispatch_id')
            ->join('play_game g','g.id=d.game_id')
            ->where('m.user_id = '.$uid)
            ->field('m.id,d.voice_id,d.game_id,d.sex,d.min_price,d.max_price,d.dispatch_id,d.remark,m.user_id,m.create_time,g.name,g.img')
            ->order('m.id desc')
            ->page($page)
            ->select();
        if($dispatch_msg_list){
            $dispatch_arr = array_column($dispatch_msg_list,'dispatch_id');
            if($dispatch_arr){
                //$dispatch_str = implode(',',$dispatch_arr);
                $voice_dispatch = db('voice_dispatch')->where('dispatch_id','in',$dispatch_arr)->select();
            }else{
                $voice_dispatch = '';
            }
            foreach ($dispatch_msg_list as &$v){
                $v['status'] = 2; //已完成
                $v['format_time'] = date('m-d H:i',$v['create_time']);
                if($voice_dispatch){
                    foreach ($voice_dispatch as $k1=>$v1){
                        if($v1['dispatch_id']==$v['dispatch_id']){
                            $v['status'] = 1; //正在派单
                            unset($voice_dispatch[$k1]);
                        }
                    }
                }
            }
        }
        $result['list'] = $dispatch_msg_list;
        return_json_encode($result);
    }

}
