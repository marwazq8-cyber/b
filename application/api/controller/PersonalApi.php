<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 下午 15:11
 */

namespace app\api\controller;

use think\Db;
use \app\api\controller\Base;
use app\api\model\UserModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
//个人中心主播信息
class PersonalApi extends Base
{
    // vip聊天限制
    public function vip_private_letter()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $touid = intval(input('param.touid'));
        check_login_token($uid, $token);
        $key = "vip_private_letter_" . $uid;
        $private_chat_sum = intval(get_user_vip_authority($uid, 'is_private_chat'));
        if ($private_chat_sum <= 0) {
            $vip = db('vip')->field("id,title")->where('is_private_chat > 0 and status=1')->order("sort desc,id asc")->find();
            $title = $vip ? ":" . $vip['title'] : "";
            $result['code'] = 2;
            $result['msg'] = lang('no_chat_purchase_vip') . $title; // 购买vip
            return_json_encode($result);
        }
        $user_list = redis_islock_nx($key);
        if ($user_list) {
            $user_list = json_decode($user_list, true);
            if (count($user_list) >= $private_chat_sum) {
                $result['code'] = 0;
                $result['msg'] = lang('private_chat_limit'); // 私信数量达到上线
                return_json_encode($result);
            }
            if (!in_array($touid, $user_list)) {
                $user_list[] = $touid;
            }
        } else {
            $user_list[] = $touid;
        }
        // 当天开始时间时间戳
        $startTime = strtotime(date("Y-m-d", NOW_TIME));
        // 当天结束之间时间戳
        $endTime = $startTime + 60 * 60 * 24;
        save_set($key, json_decode($user_list), $endTime);

        return_json_encode($result);
    }

    // 非好友私信页面接口
    public function private_letter()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $touid = intval(input('param.touid'));
        $type = intval(input('param.type', 2));//消息类型 1陪玩消息 2普通收费消息

        $user_info = check_login_token($uid, $token, ['signature', 'age', 'is_talker']);

        $touser_info = get_user_base_info($touid, ['label', 'is_player', 'is_talker']);

        $UserModel = new UserModel();
        // 是否关注对方
        $is_focus = $UserModel->is_focus_user($uid, $touid);

        // 是否被对方拉黑
        $black = $UserModel->user_black($uid, $touid);
        // 是否被对方拉黑 1拉黑了 0否
        $data['is_shielding'] = $black ? 1 : 0;

        // 对方是否关注你
        $is_fans = $UserModel->is_focus_user($touid, $uid);
        // 是否是好友
        $data['is_friend'] = $is_fans && $is_focus ? 1 : 0;
        $data['id'] = $touid;
        $data['user_nickname'] = $touser_info['user_nickname'];
        $data['avatar'] = $touser_info['avatar'];
        $data['age'] = $touser_info['age'];
        $data['sex'] = $touser_info['sex'];
        $data['level'] = $touser_info['level'];
        $data['income_level'] = $touser_info['income_level'];
        $data['city'] = $touser_info['city'];
        $data['is_player'] = $touser_info['is_player'];
        if ($touser_info['label']) {
            $data['label'] = explode(',', $touser_info['label']);
        } else {
            $data['label'] = [];
        }

        $data['is_attention'] = 0;
        if ($is_focus) {
            $data['is_attention'] = 1;
        }
        //是否需要扣费
//        if ($type == 1) {
//            $data['is_free'] = 0;
//        } else {
//            //我的身份
//            $user_idem = get_user_identity($uid);
//            //对方身份
//            $touser_idem = get_user_identity($touid);
//            if ($user_idem == $touser_idem) {
//                //都是主播 都是陪玩 都是普通用户 不需要扣费
//                $data['is_free'] = 0;
//            } else if ($user_idem == 2 || $user_idem == 4) {
//                //自己是主播不需要扣费
//                $data['is_free'] = 0;
//            } else if ($touser_idem == 2 || $touser_idem == 4) {
//                //对方是主播需要扣费
//                $data['is_free'] = 1;
//            } else {
//                $data['is_free'] = 0;
//            }
//        }
//
//        if ($data['is_free'] == 1) {
//            //是否扣过费
//            $freelog = db('user_log')->where(['uid' => $uid, 'operator' => $touid, 'buy_type' => 1])->find();
//            if ($freelog) {
//                $data['is_free'] = 0;
//            }
//        }
        // 免费私信
        $data['is_free'] = 0;
        //是否聊过天
        //文字
        $text = db('user_greet_log')
            ->where('uid = ' . $uid . ' and touid = ' . $touid)
            ->whereOr('uid = ' . $touid . ' and touid = ' . $uid)
            ->find();
        //语音 视频
        $audio = db('video_call_record_log')
            ->where('user_id = ' . $uid . ' and call_be_user_id = ' . $touid)
            ->whereOr('user_id = ' . $touid . ' and call_be_user_id = ' . $uid)
            ->find();
        if ($text || $audio) {
            $data['is_msg'] = 1;
        } else {
            $data['is_msg'] = 0;
        }
        //是否有未读消息
        $no_msg = db('user_greet_log')
            ->where('touid = ' . $uid . ' and status = 0 and uid = ' . $touid)
            ->find();
        if ($no_msg) {
            db('user_greet_log')->where(['id' => $no_msg['id']])->update(['status' => 1]);
        }

        //头像框
        $uid_dress = get_user_dress_up($uid, 3);
        $touid_dress = get_user_dress_up($touid, 3);
        $data['user_avatar_frame'] = '';
        $data['touser_avatar_frame'] = '';
        if ($uid_dress) {
            $data['user_avatar_frame'] = $uid_dress['icon'];
        }
        if ($touid_dress) {
            $data['touser_avatar_frame'] = $touid_dress['icon'];
        }
        $config = load_cache('config');
//        $charge_coin = $config['talker_chat_charge_coin'];
//        $data['charge_coin'] = $charge_coin;
//        // 是否付费 1付费0免费
//        $data['is_pay'] = intval($charge_coin) > 0 ? 1 : 0;
        $data['is_pay'] = 0;
        //聊天背景
        $chat_bg = get_user_dress_up($uid, 5);
        $data['chat_bg'] = '';
        if ($chat_bg) {
            $data['chat_bg'] = $chat_bg['img_bg'];
        }
        //聊天气泡
        $chat_bubble = get_user_dress_up($uid, 4);
        $tochat_bubble = get_user_dress_up($touid, 4);
        $data['user_chat_bubble'] = $config['to_user_chat_bubble'];
        $data['user_chat_bubble_ios'] = $config['to_user_chat_bubble'];
        $data['touser_chat_bubble'] = $config['user_chat_bubble'];
        $data['touser_chat_bubble_ios'] = $config['user_chat_bubble'];
        if ($chat_bubble) {
            $data['user_chat_bubble'] = $chat_bubble['icon'];
            $data['user_chat_bubble_ios'] = $chat_bubble['ios_icon'];
        }
        if ($tochat_bubble) {
            $data['touser_chat_bubble'] = $tochat_bubble['icon'];
            $data['touser_chat_bubble_ios'] = $tochat_bubble['ios_icon'];
        }

        //密友等级
        if ($user_info['is_talker'] == 1) {
            $friendship = friendship_level($touid, $uid);
        } else {
            $friendship = friendship_level($uid, $touid);
        }

        $data['friendship_level'] = $friendship['name'];

        $result['data'] = $data;

        return_json_encode($result);
    }

    // 非好友聊天扣费接口
    public function chat_no_friend_coin()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $touid = intval(input('param.touid')); // 被呼叫人
        $msg = trim(input('param.msg'));
        $user_info = check_login_token($uid, $token, ['income', 'friend_coin', 'last_login_ip']);
        //我的身份
        $user_idem = get_user_identity($uid);
        //对方身份
        $to_user_idem = get_user_identity($touid);

        //是否聊过天
        $greet = db('user_greet_log')
            ->where('uid = ' . $uid . ' and touid = ' . $touid)
            ->whereOr('uid = ' . $touid . ' and touid = ' . $uid)
            ->find();
        if ($greet) {
            if ($greet['status'] == 0) {
                db('user_greet_log')->where('id = ' . $greet['id'])->update(['status' => 1]);
            }
            $table_id = $greet['id'];
        } else {
            //写入记录
            $data = [
                'uid'     => $uid,
                'touid'   => $touid,
                'msg'     => $msg,
                'status'  => 0,
                'addtime' => NOW_TIME,
            ];
            $table_id = db('user_greet_log')->insertGetId($data);
        }
        //好友申请(第一次发消息)
        $user_friend = db('user_friend')->where(['touid' => $uid, 'uid' => $touid])->find();
        if ($user_friend) {
            if ($user_friend['status'] == 0) {
                db('user_friend')->where(['touid' => $uid, 'uid' => $touid])->update(['status' => 1]);
                $data_friend = [
                    'uid'     => $uid,
                    'touid'   => $touid,
                    'status'  => 1,
                    'addtime' => NOW_TIME,
                ];
                db('user_friend')->insert($data_friend);
            }
        } else {
            $data_friend = [
                'uid'     => $uid,
                'touid'   => $touid,
                'status'  => 0,
                'addtime' => NOW_TIME,
            ];
            db('user_friend')->insert($data_friend);
        }
        //扣费
        $config = load_cache('config');
        $charge_coin = $config['talker_chat_charge_coin'];
        if ($charge_coin <= 0) {
            return_json_encode($result);
        }
        if ($user_idem < 2 && $to_user_idem < 2) {
            //普通用户之间不能聊天
            $result['code'] = 0;
            $result['msg'] = lang('Ordinary_users_cannot_chat');
            return_json_encode($result);
        } else if ($user_idem == 2) {
            //自己是主播不扣费
            return_json_encode($result);
        } else if ($to_user_idem == 3) {
            //对方是陪玩不扣费
            return_json_encode($result);
        } else if ($user_idem == 3 && $to_user_idem < 2) {
            //我是陪玩 对方普通用户 不扣费
            return_json_encode($result);
        }

        $user_coin = get_user_coin($uid, $charge_coin);
        if ($user_coin == 0) {
            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }
        $UserModel = new UserModel();

        $charging_coin_res = $UserModel->deduct_user_coin_new($user_info, $charge_coin, $user_coin, 1, $touid);

        if ($charging_coin_res) {
            // 钻石变更记录
            save_coin_log($user_info['id'], '-' . $charge_coin, 1, 2, $touid);
            // 获取主播提成收益
            if ($user_coin == 1) {
                $proportion = $config['heart_talker_chat_proportion'];
            } else {
                $proportion = $config['friend_talker_chat_proportion'];
            }

            $income_total = round($proportion * $charge_coin, 2);
            // 增加主播收益 1:1对1通话计时
            $UserModel->add_user_earnings($touid, $income_total, $user_info, 1);


            //消费记录
            $consume_id = add_charging_log($user_info['id'], $touid, 5, $charge_coin, $table_id, $income_total);

            task_reward(3, $uid);
            //邀请收益记录
            request_invite_record($touid, 3, $income_total, $consume_id);
        } else {
            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
        }
        return_json_encode($result);
    }

    // 私信
    public function private_chat()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $to_user_id = input('param.to_user_id');

        $user_info = check_login_token($uid, $token);

        $to_user_info = get_user_base_info($to_user_id);

        $config = load_cache('config');
        $result['is_pay'] = 0;
        //返回是否需要按条扣费

        if ($user_info['sex'] == 1 && $to_user_info['sex'] == 2 && $config['is_open_chat_pay'] == 1) {
            $result['is_pay'] = 1;
            $result['pay_coin'] = $config['private_chat_money'];
        }

        //是否被对方拉黑
        $black = db('user_black')->where('user_id', '=', $to_user_id)->where('black_user_id', '=', $uid)->find();
        if ($black) {
            $result['code'] = 0;
            $result['msg'] = lang('Blackout_unable_to_video');
            return_json_encode($result);
        }

        //女性是否认证
        $result['sex'] = $user_info['sex'];
        $result['is_auth'] = get_user_auth_status($uid);

        $result['user_info'] = get_user_base_info($to_user_id);

        return_json_encode($result);
    }

    //获取评价列表
    public function get_evaluate_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $to_user_id = intval(input('param.to_user_id'));
        $page = intval(input('param.page'));

        //获取评价列表
        $result['evaluate_list'] = db('user_evaluate_record')->alias('e')
            ->join('user u', 'e.user_id=u.id')
            ->field('u.user_nickname,u.avatar,e.label_name')
            ->where('e.to_user_id', '=', $to_user_id)
            ->order('e.create_time desc')
            ->page($page)
            ->select();

        foreach ($result['evaluate_list'] as &$v) {

            $v['label_list'] = [];
            if (!empty($v['label_name'])) {
                $label_array = explode('-', $v['label_name']);
                foreach ($label_array as $k => $v2) {
                    if (empty($v2)) {
                        unset($label_array[$k]);
                    }
                }
                $v['label_list'] = $label_array;
            }
        }

        return_json_encode($result);

    }

    //获取用户主页信息
    public function get_user_page_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $id = intval(input('param.id'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        $user_info = check_token($uid, $token);

        $user = db('user')->where("id=$id")->find();

        $level_name = get_level($id);
        $data = array(
            'id'            => $id,
            'sex'           => $user['sex'],
            'user_nickname' => $user['user_nickname'],
            'avatar'        => $user['avatar'],
            'address'       => $user['address'],
            'user_status'   => get_user_auth_status($id),
            'teacher'       => '',
            'level'         => $level_name,
            'max_level'     => $level_name,
            'luck'          => $user['luck']
        );
        $data['attention'] = 1;
        if ($id != $uid) {
            $is_att = db('user_attention')->where('uid', '=', $uid)->where('attention_uid', '=', $id)->find();         //获取是否关注
            if (!$is_att) {
                $data['attention'] = 0;
            }
        }

        //是否拉黑
        $data['is_black'] = 0;
        $black_record = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $id)->find();
        if ($black_record) {
            $data['is_black'] = 1;
        }

        $config = load_cache('config');
        //$heartbeat_interval = db("config")->where("code='heartbeat_interval'")->find();

        $gift_list = db('user_gift_log')
            ->alias('l')
            ->join('gift g', 'l.gift_id=g.id')
            ->field('g.*')
            ->where('l.to_user_id', '=', $id)
            ->group('gift_id')
            ->select();


        //获取主播视频
        $video_list = db('user_video')->where("uid=$id")->where('type', '=', 1)->field("status,img,video_url,title,coin,viewed,uid,id,follow_num")->limit(0, 10)->select();
        //获取主播私照
        $private_photo_list = db('user_pictures')->where("uid=$id and status=1")->field("img,id")->limit(0, 15)->select();

        //处理图片模糊状态
        foreach ($private_photo_list as &$v) {
            //获取查询私照是否支付观看过
            $buy_record = db("user_photo_buy")->where("p_id=" . $v['id'] . " and user_id=$uid")->find();
            if (!$buy_record) {
                $v['img'] = $v['img'] . "?imageMogr2/auto-orient/blur/40x50";    //私照加密
                $v['watch'] = 1;
            } else {
                $v['watch'] = 0;
            }
        }

        if ($id == $uid) {
            $gift_count = db('user_gift_log')->where('user_id', '=', $id)->sum('gift_count');
            $data['gift_count'] = $gift_count;                   //统计收到的礼物
        } else {
            $gift_count = db('user_gift_log')->where('to_user_id', '=', $id)->sum('gift_count');
            $data['gift_count'] = $gift_count;                   //统计收到的礼物
        }
        $data['gift'] = $gift_list;                          //统计收到的礼物
        $data['video_count'] = count($video_list);                //统计主播视频
        $data['video'] = $video_list;                     //主播视频10条
        $data['pictures_count'] = count($private_photo_list);           //统计主播私照
        $data['pictures'] = $private_photo_list;                     //统计主播私照

        $data['online'] = $user['is_online'];          //是否在线0不在1在
        $data['is_online'] = $data['online'];          //是否在线0不在1在

        $attention_fans_count = db('user_attention')->where("attention_uid=$id")->count();
        $attention_count = db('user_attention')->where("uid=$id")->count();
        //通话时长
        $call_time = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->whereOr('call_be_user_id', '=', $id)
            ->sum('call_time');

        if ($call_time) {
            $call_time = secs_to_str(abs($call_time));
        } else {
            $call_time = '0';
        }

        //好评比
        $evaluation = db('video_call_record_log')->where('is_fabulous', '=', 1)->where('anchor_id', '=', $id)->count();   //获取评价总数

        //主页轮播图
        $user_image = db('user_img')->where("uid=$id")->where("status=1")->field("id,img")->order("addtime desc")->limit(6)->select();

        //点赞总数
        $fabulous_count = db('user_fabulous_record')->where('to_user_id', '=', $id)->count();

        //获取通话付费价格
        $user_level = get_level($id);

        $data['video_deduction'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            //判断用户等级是否符合规定
            if ($user_level >= $config['custom_video_money_level'] && $user['custom_video_charging_coin'] != 0) {
                $data['video_deduction'] = $user['custom_video_charging_coin'];
            }
        }

        $data['attention_fans'] = $attention_fans_count;    //获取关注人数
        $data['attention_all'] = $attention_count;      //获取粉丝人数
        $data['call'] = $call_time;                          //通话总时长
        $data['evaluation'] = $evaluation;             //好评百分比
        $data['teacher'] = [];             //获取收徒榜
        $data['img'] = $user_image;                      //主播轮播图
        $data['give_like'] = $fabulous_count;       //获取点赞数

        $result['data'] = $data;
        return_json_encode($result);
    }

    //获取用户基础信息
    public function get_user_base_info()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $to_user_id = input('param.to_user_id');

        $user_info = check_login_token($uid, $token);

        $result['user_info'] = get_user_base_info($to_user_id, ['id', 'user_nickname', 'sex', 'avatar']);

        return_json_encode($result);
    }

    //关注和取消
    public function click_attention()
    {
        $result = array('code' => 1, 'msg' => lang('Focus_on_success'));

        $uid = input('param.uid');
        $token = input('param.token');
        $id = input('param.id');

        $user_info = check_login_token($uid, $token);

        $attention = db('user_attention')->where("uid=$uid and attention_uid=$id")->find();
        if ($attention) {
            // 取消关注
            $result['msg'] = lang('Unsubscribe_successfully');
            $atte = db('user_attention')->where("uid=$uid and attention_uid=$id")->delete();
            if (!$atte) {
                $result['code'] = 0;
                $result['msg'] = lang('Failed_to_cancel_following');
            }

            $result['follow'] = 0;
        } else {
            // 禁止关注 -- 当前vip 是否有禁止关注(不让对方关注自己)
//            $is_ban_attention = intval(get_user_vip_authority($id, 'is_ban_attention'));
//            if ($is_ban_attention == 1) {
//                $to_user_info = get_user_base_info($id, ['is_ban_attention']);
//                if ($to_user_info['is_ban_attention'] == 1) {
//                    $result['code'] = 0;
//                    $result['msg'] = lang('Enable_disable_follow');
//                    return_json_encode($result);
//                }
//            }
            // 关注 -- 当前vip 是否有关注上限和粉丝上限
//            $maximum_attention = intval(get_user_vip_authority($uid, 'maximum_attention'));
//            $maximum_fans = intval(get_user_vip_authority($id, 'maximum_fans'));
//            $attention_sum = db('user_attention')->where("uid=$uid")->count();
//            $attention_fans = db('user_attention')->where("attention_uid=$id")->count();
//            if ($attention_sum >= $maximum_attention) {
//                $result['code'] = 0;
//                $result['msg'] = lang('Upper_limit_attention');
//                return_json_encode($result);
//            }
//            if ($attention_fans >= $maximum_fans) {
//                $result['code'] = 0;
//                $result['msg'] = lang('Maximum_number_fans');
//                return_json_encode($result);
//            }

            $data = array(
                'uid'           => $uid,
                'attention_uid' => $id,
                'addtime'       => NOW_TIME
            );
            $atte = db('user_attention')->insert($data);
            if (!$atte) {
                $result['code'] = 0;
                $result['msg'] = lang('Failed_to_follow');
            }
            $msg = db("user_message")->where('type = 15')->find();
            $content = $user_info['user_nickname'] . $msg['centent'];
            $url = 'bogo://message?type=1&id=' . $uid;
            push_sys_msg_user(19, $id, 1, $content, $url);


            $result['follow'] = 1;
        }
        return_json_encode($result);

    }

    //拉黑用户
    public function black_user()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $to_user_id = input('param.to_user_id');

        $user_info = check_login_token($uid, $token);

        $record = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $to_user_id)->find();
        if ($record) {
            db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $to_user_id)->delete();

        } else {
            $data = [
                'user_id'       => $uid,
                'black_user_id' => $to_user_id,
                'create_time'   => NOW_TIME
            ];
            db('user_black')->insert($data);
        }

        return_json_encode($result);
    }
}
