<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-06-13
 * Time: 14:15
 */

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\UserModel;

//use app\api\model\LoginModel;
use app\vue\model\VoiceModel;
use app\vue\model\BzoneModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class DressApi extends Base
{
    private $UserModel;
    //private $LoginModel;
    private $VoiceModel;
    private $BzoneModel;

    protected function _initialize()
    {
        parent::_initialize();

        $this->UserModel = new UserModel();
        //$this->LoginModel = new LoginModel();
        $this->VoiceModel = new VoiceModel();
        $this->BzoneModel = new BzoneModel();
    }

    // 获取购买的装饰中心
    public function get_display()
    {
        $result = array('code' => 1, 'msg' => lang('ADMIN_LIST'));
        $uid = input('param.uid');
        $token = input('param.token');
        $type = intval(input('param.type'));
        $user_info = check_login_token($uid, $token);
        $list = db('dress_up')->where('type', $type)->where('is_pay', 1)->order("orderno asc")->select();
        if ($type == 5) {
            foreach ($list as &$v) {
                $v['icon'] = $v['img_bg'];
            }
        }
        $config = load_cache('config');
        $result['data'] = array(
            'list' => $list,
            'avatar_img' => $user_info['avatar'],
            'currency_name' => $config['currency_name']
        );

        return_json_encode($result);

    }

    // 购买装扮中心商品
    public function buy_display()
    {
        $result = array('code' => 0, 'msg' => lang('Purchase_failed'));
        $uid = input('param.uid');
        $token = input('param.token');
        $id = input('param.id');//装饰ID
        $user_info = check_login_token($uid, $token);

        $dress_up = db('dress_up')->where("id=" . $id . " and is_pay=1")->find();
        if (!$dress_up || $dress_up['days'] <= 0) {
            $result['msg'] = lang('Decoration_error_collect_later');
            return_json_encode($result);
        }
        if ($user_info['coin'] < $dress_up['coin']) {
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }
        //是否购买过
        $info = db('user_dress_up')->where(['uid' => $uid, 'dress_id' => $id])->find();
        $insert_time = $dress_up['days'] * 60 * 60 * 24;
        $addtime = NOW_TIME;
        $endtime = $addtime + $insert_time;

        switch ($dress_up['type']) {
            case 1:
                $content = lang('ADMIN_MEAL');
                break;
            case 2:
                $content = lang('ADMIN_HOME_PAGE');
                break;
            case 3:
                $content = lang('ADMIN_AVATAR_FRAME');
                break;
            case 4:
                $content = lang('ADMIN_CHAT_BUBBLE');
                break;
            case 5:
                $content = lang('ADMIN_CHAT_BG');
                $dress_up['icon'] = $dress_up['img_bg'] ? $dress_up['img_bg'] : $dress_up['icon'];
                break;
            default:
                $content = lang('car');
        }

        $content .= ":" . $dress_up['name'] . " x " . $dress_up['days'] . lang('ADMIN_DAY');

        // 启动事务
        db()->startTrans();
        try {
            db('user')->where('id', '=', $uid)->dec('coin', $dress_up['coin'])->update();
            // 钻石变更记录
            save_coin_log($uid, '-' . $dress_up['coin'], 1, 9, $dress_up['name'] . ": " . $dress_up['type']);
            if ($info) {
                if ($info['endtime'] > $addtime) {
                    // 续费
                    $endtime = $info['endtime'] + $insert_time;
                }
                $update = array(
                    'endtime' => $endtime,
                    'dress_up_name' => $dress_up['name'],
                    'dress_up_icon' => $dress_up['icon'],
                    'dress_up_type' => $dress_up['type'],
                );
                db('user_dress_up')->where(['uid' => $uid, 'dress_id' => $id])->update($update);
                $log_id = $info['id'];
            } else {
                $data = [
                    'uid' => $uid,
                    'dress_id' => $id,
                    'addtime' => $addtime,
                    'endtime' => $endtime,
                    'dress_up_name' => $dress_up['name'],
                    'dress_up_icon' => $dress_up['icon'],
                    'dress_up_type' => $dress_up['type'],
                ];
                $res = db('user_dress_up')->insertGetId($data);
                $log_id = $res;
            }

            // 增加总消费记录
            add_charging_log($uid, 0, 31, $dress_up['coin'], $log_id, 0, $content);
            $result['code'] = 1;
            $result['msg'] = lang('Purchase_succeeded');
            // 提交事务
            db()->commit();
        } catch (\Exception $e) {
            $result['msg'] = lang('Purchase_failed') . $e->getMessage();
            // 回滚事务
            db()->rollback();
        }

        return_json_encode($result);
    }

    //我的装饰 商品
    public function my_display()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = intval(input('param.type'));
        $user_info = check_login_token($uid, $token);

        $list = db('user_dress_up')
            ->where('uid = ' . $uid . ' and dress_up_type =' . $type . ' and endtime > ' . NOW_TIME)
            ->select();
        foreach ($list as &$v) {
            $v['endtime'] = date('Y-m-d', $v['endtime']);
        }

        $result['data'] = array(
            'list' => $list,
            'avatar_img' => $user_info['avatar']
        );
        return_json_encode($result);
    }

    //使用或关闭装饰
    public function save_display_status()
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'));
        $uid = input('param.uid');
        $token = input('param.token');
        check_login_token($uid, $token);
        $id = input('param.id');//装饰ID
        $dress_info = db('user_dress_up')->where('uid = ' . $uid . ' and id = ' . $id . ' and endtime > ' . NOW_TIME)->find();
        if (!$dress_info) {
            $result['msg'] = lang('Decoration_information_error');
            return_json_encode($result);
        }

        if ($dress_info['status'] == 1) {
            $res = db('user_dress_up')->where('uid = ' . $uid . ' and id =' . $id)->update(['status' => 0]);
            $msg = lang('Closed_successfully');
        } else {
            //关闭其他
            db('user_dress_up')->where('uid = ' . $uid . ' and dress_up_type =' . $dress_info['dress_up_type'])->update(['status' => 0]);
            $res = db('user_dress_up')->where('uid = ' . $uid . ' and id =' . $id)->update(['status' => 1]);
            $msg = lang('Open_successfully');
        }
        if ($res) {
            $result['code'] = 1;
            $result['msg'] = $msg;
        }
        return_json_encode($result);
    }

    public function index()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['income_talker_total', 'income_player_total']);
        $list = db('dress_up')
            ->where('type = 3 and is_pay = 1')
            ->order("orderno asc")
            ->limit(8)
            ->select();
        $result['data']['list'] = $list;
        $config = load_cache('config');

        $result['data']['user_avatar'] = $config['user_avatar'];
        return_json_encode($result);
    }

    public function get_avatar_frame()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type');
        $page = input('param.page');
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        if ($user_info['noble_end_time'] > NOW_TIME) {
            //我的贵族
            $noble = db('noble')->field('avatar_frame_id')->find($user_info['nobility_level']);
            $avatar_frame_id = [];
            if ($noble) {
                $avatar_frame_id = explode(',', $noble['avatar_frame_id']);
            }
        } else {
            $avatar_frame_id = [];
        }
        $limit = 9;
        if ($type == 1) {
            $list = db('dress_up')
                ->where('type = 3 and is_pay = 1')
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        } else {
            $map['id'] = ['in', $avatar_frame_id];
            $list = db('dress_up')
                ->where('type = 3 and is_pay = 1')
                ->page($page, $limit)
                ->order("orderno asc")
                ->where($map)
                ->select();
        }

        foreach ($list as &$val) {
            $val['is_receive'] = 0;
            if (in_array($val['id'], $avatar_frame_id)) {
                $val['is_receive'] = 1;
            }
        }
        if ($page == 1) {
            $map['id'] = ['in', $avatar_frame_id];
            $count = db('dress_up')
                ->where('type = 3 and is_pay = 1')
                ->where($map)
                ->count();
            $avatar_frame = '';
            $id = 0;
            $is_receive = 0;
            if ($list) {
                $avatar_frame = $list[0]['icon'];
                $id = $list[0]['id'];
                $is_receive = $list[0]['is_receive'];
            }
            $result['data']['avatar_frame'] = $avatar_frame;
            $result['data']['id'] = $id;
            $result['data']['is_receive'] = $is_receive;
            $result['data']['count'] = $count;
        }
        $config = load_cache('config');

        $result['data']['user_avatar'] = $config['user_avatar'];
        $result['data']['list'] = $list;
        $result['data']['user_info'] = $user_info;
        $result['data']['limit'] = $limit;
        return_json_encode($result);
    }

    public function get_chat_bubble()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type');
        $page = input('param.page');
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        //我的贵族
        $avatar_frame_id = [];
        if ($user_info['noble_end_time'] > NOW_TIME) {
            $noble = db('noble')->field('chat_bubble_id')->find($user_info['nobility_level']);
            if ($noble) {
                $avatar_frame_id = explode(',', $noble['chat_bubble_id']);
            }
        }
        $limit = 6;
        if ($type == 1) {
            $list = db('dress_up')
                ->where('type = 4 and is_pay = 1')
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        } else {
            $map['id'] = ['in', $avatar_frame_id];
            $list = db('dress_up')
                ->where('type = 4 and is_pay = 1')
                ->where($map)
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        }

        foreach ($list as &$val) {
            $val['is_receive'] = 0;
            if (in_array($val['id'], $avatar_frame_id)) {
                $val['is_receive'] = 1;
            }
        }
        if ($page == 1) {
            $map['id'] = ['in', $avatar_frame_id];
            $count = db('dress_up')
                ->where('type = 4 and is_pay = 1')
                ->where($map)
                ->count();
            $avatar_frame = '';
            $id = 0;
            $is_receive = 0;
            if ($list) {
                $avatar_frame = $list[0]['img_bg'];
                $id = $list[0]['id'];
                $is_receive = $list[0]['is_receive'];
            }
            $result['data']['avatar_frame'] = $avatar_frame;
            $result['data']['id'] = $id;
            $result['data']['is_receive'] = $is_receive;
            $result['data']['count'] = $count;
        }

        $result['data']['list'] = $list;
        $result['data']['user_info'] = $user_info;
        $result['data']['limit'] = $limit;

        return_json_encode($result);
    }

    //主页特效
    public function get_home_page()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type');
        $page = input('param.page');
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        //我的贵族
        $avatar_frame_id = [];
        if ($user_info['noble_end_time'] > NOW_TIME) {
            $noble = db('noble')->field('home_page_id')->find($user_info['nobility_level']);
            if ($noble) {
                $avatar_frame_id = explode(',', $noble['home_page_id']);
            }
        }
        $limit = 20;
        if ($type == 1) {
            $list = db('dress_up')
                ->where('type = 2')
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        } else {
            $map['id'] = ['in', $avatar_frame_id];
            $list = db('dress_up')
                ->where('type = 2')
                ->where($map)
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        }

        foreach ($list as &$val) {
            $val['is_receive'] = 0;
            if (in_array($val['id'], $avatar_frame_id)) {
                $val['is_receive'] = 1;
            }
        }
        if ($page == 1) {
            $map['id'] = ['in', $avatar_frame_id];
            $count = db('dress_up')
                ->where('type = 2')
                ->where($map)
                ->count();
            $avatar_frame = '';
            $id = 0;
            $is_receive = 0;
            $bg_svga = '';
            if ($list) {
                $avatar_frame = $list[0]['icon'];
                $id = $list[0]['id'];
                $is_receive = $list[0]['is_receive'];
                $bg_svga = $list[0]['img_bg'];
            }
            $result['data']['avatar_frame'] = $avatar_frame;
            $result['data']['id'] = $id;
            $result['data']['is_receive'] = $is_receive;
            $result['data']['count'] = $count;
            $result['data']['svga'] = $bg_svga;
        }

        $result['data']['list'] = $list;
        $result['data']['user_info'] = $user_info;
        $result['data']['limit'] = $limit;
        return_json_encode($result);
    }

    //聊天背景
    public function get_chat_bg()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type');
        $page = input('param.page');
        //$user_info = check_login_token($uid, $token,['nobility_level']);
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        $avatar_frame_id = [];
        if ($user_info['noble_end_time'] > NOW_TIME) {
            //我的贵族
            $noble = db('noble')->field('chat_bg_id')->find($user_info['nobility_level']);
            if ($noble) {
                $avatar_frame_id = explode(',', $noble['chat_bg_id']);
            }
        }
        $is_page = 0;
        if ($type == 1) {
            $list = db('dress_up')
                ->where('type = 5')
                ->order("orderno asc")
                ->page($page, 12)
                ->select();
            if (!$list) {
                $is_page = 1;
            } else {
                $is_page = 0;
            }
        } else {
            $map['id'] = ['in', $avatar_frame_id];
            $list = db('dress_up')
                ->where('type = 5')
                ->where($map)
                ->order("orderno asc")
                ->page($page, 12)
                ->select();
        }

        foreach ($list as &$val) {
            $val['is_receive'] = 0;
            if (in_array($val['id'], $avatar_frame_id)) {
                $val['is_receive'] = 1;
            }
        }
        $map['id'] = ['in', $avatar_frame_id];
        $count = db('dress_up')
            ->where('type = 5')
            ->where($map)
            ->count();
        $avatar_frame = '';
        $id = 0;
        $is_receive = 0;
        $bg_svga = '';
        $bg_v = '';
        if ($list) {
            $avatar_frame = $list[0]['icon'];
            $id = $list[0]['id'];
            $is_receive = $list[0]['is_receive'];
            $bg_svga = $list[0]['img_bg'];
            $bg_v = $list[0]['v_bg'];
        }
        $result['data']['list'] = $list;
        $result['data']['user_info'] = $user_info;
        $result['data']['avatar_frame'] = $avatar_frame;
        $result['data']['id'] = $id;
        $result['data']['is_receive'] = $is_receive;
        $result['data']['count'] = $count;
        $result['data']['img_bg'] = $bg_svga;
        $result['data']['v_bg'] = $bg_v;
        $result['data']['is_page'] = $is_page;
        return_json_encode($result);
    }

    //进场动画
    public function get_car()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type');
        $page = input('param.page');
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        //我的贵族
        $avatar_frame_id = [];
        if ($user_info['noble_end_time'] > NOW_TIME) {
            $noble = db('noble')->field('car_id')->find($user_info['nobility_level']);
            if ($noble) {
                $avatar_frame_id = explode(',', $noble['car_id']);
            }
        }
        $limit = 20;
        if ($type == 1) {
            $list = db('dress_up')
                ->where('type = 7 and is_pay = 1')
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        } else {
            $map['id'] = ['in', $avatar_frame_id];
            $list = db('dress_up')
                ->where('type = 7 and is_pay = 1')
                ->where($map)
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        }

        foreach ($list as &$val) {
            $val['is_receive'] = 0;
            if (in_array($val['id'], $avatar_frame_id)) {
                $val['is_receive'] = 1;
            }
        }
        if ($page == 1) {
            $map['id'] = ['in', $avatar_frame_id];
            $count = db('dress_up')
                ->where('type = 7')
                ->where($map)
                ->count();
            $avatar_frame = '';
            $id = 0;
            $is_receive = 0;
            $bg_svga = '';
            if ($list) {
                $avatar_frame = $list[0]['icon'];
                $id = $list[0]['id'];
                $is_receive = $list[0]['is_receive'];
                $bg_svga = $list[0]['img_bg'];
            }
            $result['data']['avatar_frame'] = $avatar_frame;
            $result['data']['id'] = $id;
            $result['data']['is_receive'] = $is_receive;
            $result['data']['count'] = $count;
            $result['data']['svga'] = $bg_svga;
        }

        $result['data']['list'] = $list;
        $result['data']['user_info'] = $user_info;
        $result['data']['limit'] = $limit;
        return_json_encode($result);
    }

    //荣誉勋章
    public function get_medal()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type');
        $page = input('param.page');

        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        $medal_id = '';
        $medal_id_array = '';
        if ($user_info['noble_end_time'] > NOW_TIME) {
            //我的贵族
            $noble = db('noble')->field('medal_id')->find($user_info['nobility_level']);
            if ($noble) {
                $medal_id_array = explode(',', $noble['medal_id']);
                $medal_id = $noble['medal_id'];
            }

        }

        //return_json_encode($avatar_frame_id);
        $limit = 20;
        if ($type == 1) {
            $list = db('dress_up')
                ->where('type = 1')
                ->order("orderno asc")
                ->page($page, $limit)
                ->select();
        } else {
            if ($medal_id) {
                $list = db('dress_up')
                    ->where("type = 1 and id in ($medal_id)")
                    ->order("orderno asc")
                    ->select();
            } else {
                $list = [];
            }
        }

        foreach ($list as &$val) {
            $val['is_receive'] = 0;
            //in_array($val['id'],$avatar_frame_id);
            if ($medal_id_array) {
                if (in_array($val['id'], $medal_id_array)) {
                    $val['is_receive'] = 1;
                }
            }
        }
        if ($page == 1) {
            //$map['id'] = ['in',$avatar_frame_id];
            if ($medal_id) {
                $count = db('dress_up')
                    ->where("type = 1 and id in ($medal_id)")
                    ->count();
            } else {
                $count = 0;
            }

            $id = 0;
            $is_receive = 0;
            if ($list) {
                $id = $list[0]['id'];
                $is_receive = $list[0]['is_receive'];
            }
            $result['data']['id'] = $id;
            $result['data']['is_receive'] = $is_receive;
            $result['data']['count'] = $count;
        }

        $result['data']['list'] = $list;
        $result['data']['user_info'] = $user_info;
        $result['data']['limit'] = $limit;
        return_json_encode($result);
    }

    //我的装饰
    /*public function get_my_dress(){
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        //$id = input('param.id');//装饰ID
        $user_info = check_login_token($uid, $token,['nobility_level','noble_end_time']);
        if($user_info['nobility_level'] && $user_info['noble_end_time']>NOW_TIME){
            $list = db('user_dress_up')
                ->alias('u')
                ->join('dress_up d','d.id=u.dress_id')
                ->field('u.*,d.name,d.icon,d.type')
                ->where('u.uid = '.$uid.' and u.endtime > '.NOW_TIME)
                ->select();
            foreach($list as &$v){
                //if($v['status']==1){
                $status = 'status'.$v['id'];
                //$v['status'] = [$status=>'true'];
                //$v['item'] = $status;
                //}
                $v['item'] = $status;

            }
        }else{
            $list = [];
        }
        //勋章
        $medal = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d','d.id=u.dress_id')
            ->field('u.*,d.name,d.icon,d.type')
            ->where('d.type = 1 and u.uid = '.$uid.' and u.endtime > '.NOW_TIME)
            ->find();
        $is_medal = 0;
        if($medal){
            $is_medal = 1;
        }
        $result['is_medal'] = $is_medal;
        //主页特效
        $home_page = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d','d.id=u.dress_id')
            ->field('u.*,d.name,d.icon,d.type')
            ->where('d.type = 2 and u.uid = '.$uid.' and u.endtime > '.NOW_TIME)
            ->find();
        $is_home_page = 0;
        if($home_page){
            $is_home_page = 1;
        }
        $result['is_home_page'] = $is_home_page;
        //头像框
        $avatar_frame = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d','d.id=u.dress_id')
            ->field('u.*,d.name,d.icon,d.type')
            ->where('d.type = 3 and u.uid = '.$uid.' and u.endtime > '.NOW_TIME)
            ->find();
        $is_avatar_frame = 0;
        if($avatar_frame){
            $is_avatar_frame = 1;
        }
        $result['is_avatar_frame'] = $is_avatar_frame;
        //聊天气泡
        $chat_bubble = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d','d.id=u.dress_id')
            ->field('u.*,d.name,d.icon,d.type')
            ->where('d.type = 4 and u.uid = '.$uid.' and u.endtime > '.NOW_TIME)
            ->find();
        $is_chat_bubble = 0;
        if($chat_bubble){
            $is_chat_bubble = 1;
        }
        $result['is_chat_bubble'] = $is_chat_bubble;
        //聊天背景
        $chat_bg = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d','d.id=u.dress_id')
            ->field('u.*,d.name,d.icon,d.type')
            ->where('d.type = 5 and u.uid = '.$uid.' and u.endtime > '.NOW_TIME)
            ->find();
        $is_chat_bg = 0;
        if($chat_bg){
            $is_chat_bg = 1;
        }
        $result['is_chat_bg'] = $is_chat_bg;
        $result['data'] = $list;
        return_json_encode($result);
    }*/
    public function get_my_dress()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        //$id = input('param.id');//装饰ID
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        $is_medal = 0;
        $is_home_page = 0;
        $is_avatar_frame = 0;
        $is_chat_bubble = 0;
        $is_chat_bg = 0;
        $is_car = 0;
        //    if($user_info['nobility_level'] && $user_info['noble_end_time']>NOW_TIME){
        $list = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d', 'd.id=u.dress_id')
            ->field('u.*,d.name,d.icon,d.img_bg,d.type')
            ->where('u.uid = ' . $uid . ' and u.endtime > ' . NOW_TIME)
            ->select();

        foreach ($list as &$v) {
            $status = 'status' . $v['id'];
            $v['item'] = $status;

            switch ($v['type']) {
                case 1:
                    $is_medal = 1;
                    break;
                case 2:
                    $is_home_page = 1;
                    break;
                case 3:
                    $is_avatar_frame = 1;
                    break;
                case 4:
                    $v['icon'] = $v['img_bg'];
                    $is_chat_bubble = 1;
                    break;
                case 5:
                    $is_chat_bg = 1;
                    break;
                case 7:
                    $is_car = 1;
                    break;

            }
        }
//            }else{
//                $list = [];
//            }
        $result['is_medal'] = $is_medal;
        $result['is_home_page'] = $is_home_page;
        $result['is_avatar_frame'] = $is_avatar_frame;
        $result['is_chat_bubble'] = $is_chat_bubble;
        $result['is_chat_bg'] = $is_chat_bg;
        $result['is_car'] = $is_car;
        $result['data'] = $list;
        return_json_encode($result);
    }

    //使用
    public function request_dress_status()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);

        $id = input('param.id');//装饰ID
        $dress_info = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d', 'd.id=u.dress_id')
            ->field('u.*,d.name,d.icon,d.type')
            ->where('u.uid = ' . $uid . ' and u.id = ' . $id . ' and u.endtime > ' . NOW_TIME)
            ->find();

        if (!$dress_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Decoration_information_error');
            return_json_encode($result);
        }

        if ($dress_info['status'] == 1) {
            $res = db('user_dress_up')
                ->where('uid = ' . $uid . ' and id =' . $id)
                ->update(['status' => 0]);
            $msg = lang('Closed_successfully');
        } else {
            $res = db('user_dress_up')
                ->where('uid = ' . $uid . ' and id =' . $id)
                ->update(['status' => 1]);
            if ($res) {
                //关闭其他

                $list = db('user_dress_up')
                    ->alias('u')
                    ->join('dress_up d', 'd.id=u.dress_id')
                    ->field('u.*,d.name,d.icon,d.type')
                    ->where('u.uid = ' . $uid . ' and u.id != ' . $id)
                    ->where('d.type = ' . $dress_info['type'])
                    ->select();
                foreach ($list as $val) {
                    db('user_dress_up')
                        ->where('uid = ' . $uid . ' and id =' . $val['id'])
                        ->update(['status' => 0]);
                }
            }
            $msg = lang('Open_successfully');
        }
        if ($res) {
            $result['msg'] = $msg;
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('operation_failed');
        }
        //$result['data'] = $list;
        return_json_encode($result);
    }

    //获取装饰
    public function request_dress_up()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $id = input('param.id');//装饰ID
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);
        if ($user_info['noble_end_time'] < NOW_TIME) {
            $result['code'] = 0;
            $result['msg'] = lang('decoration_is_not_available_yet');
            return_json_encode($result);
        }
        //我的贵族
        $noble = db('noble')
            ->field('home_page_id,avatar_frame_id,medal_id,chat_bg_id,chat_bubble_id,car_id')
            ->find($user_info['nobility_level']);
        if (!$noble) {
            $result['code'] = 0;
            $result['msg'] = lang('decoration_is_not_available_yet');
            return_json_encode($result);
        }
        $dress_id = $noble['home_page_id'] . ',' . $noble['avatar_frame_id'] . ',' . $noble['medal_id'] . ',' . $noble['chat_bg_id'] . ',' . $noble['chat_bubble_id'] . ',' . $noble['car_id'];
        $dress = explode(',', $dress_id);
        if (!in_array($id, $dress)) {
            $result['code'] = 0;
            $result['msg'] = lang('decoration_is_not_available_yet');
            return_json_encode($result);
        }

        $dress_up = db('dress_up')->find($id);
        if (!$dress_up) {
            $result['code'] = 0;
            $result['msg'] = lang('Decoration_error_collect_later');
            return_json_encode($result);
        }
        //是否领取过
        $info = db('user_dress_up')
            ->where(['uid' => $uid, 'dress_id' => $id])
            ->find();
        if ($info) {
            if ($info['endtime'] == $user_info['noble_end_time']) {
                $res = true;
            } else {
                $res = db('user_dress_up')->where(['uid' => $uid, 'dress_id' => $id])->update(['endtime' => $user_info['noble_end_time']]);
            }
        } else {
            $data = [
                'uid' => $uid,
                'dress_id' => $id,
                'addtime' => NOW_TIME,
                'endtime' => $user_info['noble_end_time'],
            ];
            $res = db('user_dress_up')->insertGetId($data);
        }
        if ($res) {
            $result['msg'] = lang('Received_successfully');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Collection_failed');
        }

        return_json_encode($result);

    }
}
