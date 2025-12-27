<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/25 0025
 * Time: 下午 16:58
 */

namespace app\api\controller;

use Qiniu\Auth;
use Qiniu\Rtc\AppClient;
use think\Config;
use think\Db;
use BuguPush;
use VideoCallRedis;
use app\api\model\VideoCallModel;
use app\api\model\UserModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
//视频通话业务类
class VideoCallApi extends Base
{
    /*
     * 是否有聊天权限
     * */
    public function chat_authority()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $to_uid = intval(input('param.to_uid')); // 被呼叫人ID
        $free_type = intval(input('param.free_type')); //1 陪聊 2陪玩

        $user_info = check_login_token($uid, $token, ['income', 'friend_coin']);
        if ($uid == $to_uid) {
            $result['code'] = 0;
            $result['msg'] = lang('You_cant_talk_to_yourself');
            return_json_encode($result);
        }
        //免费
        $data['is_free'] = 0;
        $data['is_text'] = 1;
        $data['is_audio'] = 1;
        $data['is_video'] = 1;
        if ($free_type == 1) {
            //我的身份
            $user_idem = get_user_identity($uid);
            //对方身份
            $touser_idem = get_user_identity($to_uid);
            if ($user_idem == $touser_idem) {
                //都是主播 都是陪玩 都是普通用户
                $data['is_free'] = 0;
                $data['is_text'] = 1;
                $data['is_audio'] = 1;
                $data['is_video'] = 1;
                $result['data'] = $data;
                return_json_encode($result);
            } else if ($user_idem == 2 || $user_idem == 4) {
                //判断谁是主播
                $anchor_id = $uid;
                $user_id = $to_uid;
                $data['is_free'] = 0;
            } else if ($touser_idem == 2 || $touser_idem == 4) {
                $anchor_id = $to_uid;
                $user_id = $uid;
                $data['is_free'] = 1;
            }
            $friendship = friendship_level($user_id, $anchor_id);
            $data['is_text'] = $friendship['is_text'];
            $data['is_audio'] = $friendship['is_audio'];
            $data['is_video'] = $friendship['is_video'];

        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    //语音聊天前调用
    public function get_chat_authority()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $to_uid = intval(input('param.to_uid')); // 被呼叫人ID
        $type = intval(input('param.type', 1)); //1 语音 2视频

        $user_info = check_login_token($uid, $token, ['income', 'friend_coin', 'is_talker']);

        if ($uid == $to_uid) {
            $result['code'] = 0;
            $result['msg'] = lang('You_cant_talk_to_yourself');
            return_json_encode($result);
        }

        $VideoCallModel = new VideoCallModel();

        $UserModel = new UserModel();

        // 对方用户信息
        $to_user_info = get_user_base_info($to_uid, ['custom_video_charging_coin,is_open_do_not_disturb', 'is_voice_online', 'is_talker'], 1);
        //查询是否有自己的通话记录
        $self_video_call_record = $VideoCallModel->video_call_record($uid);

        if ($self_video_call_record) {

            $result['msg'] = lang('There_calls_that_have_not_been_ended_normally');
            return_json_encode($result);

        }

        $black_user = $UserModel->user_black_one($uid, $to_uid);

        if ($black_user) {

            $result['msg'] = lang('It_has_been_hacked_cannot_be_viewed');
            return_json_encode($result);
        }
        $black = $UserModel->user_black_one($to_uid, $uid);

        if ($black) {
            $result['msg'] = lang('Blackout_unable_to_video');
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($to_user_info['user_status'] == 0) {

            $result['msg'] = lang('other_party_suspected_violating_rules_cannot_operate');
            return_json_encode($result);
        }

        //双方用户身份
        $user_identity = get_user_identity($uid);
        $to_user_identity = get_user_identity($to_uid);
        if ($user_identity < 2 && $to_user_identity < 2) {
            $result['msg'] = lang('Ordinary_users_cannot_make_calls');
            return_json_encode($result);
        }
        $is_free = 0;
        $anchor_id = $to_uid;
        if ($user_identity == $to_user_identity) {
            //不是普通用户 身份一样不收费
            $is_free = 1;
        } else if ($to_user_identity == 2) {
            $anchor_id = $to_uid;
        } else if ($user_identity == 2) {
            $anchor_id = $uid;
            //主播主动发起通话不扣费
            $is_free = 1;
        }

        $config = load_cache('config');
        if ($is_free == 0) {
            $user_id = $anchor_id == $uid ? $to_uid : $uid;
            //$info = $anchor_id == $uid?$to_user_info :$user_info;
            //音频模式检查
            if (defined('OPEN_VOICE_CALL') && OPEN_VOICE_CALL == 1 && $type == 1) {
                //音频计时收费价格
                $video_deduction = $config['talker_audio_coin'];

            } else {
                //视频计时收费价格
                $video_deduction = $config['talker_video_coin'];
            }
            //扣费人
            $coin_type = get_user_coin($user_id, $video_deduction);
            if ($coin_type == 0) {
                if ($user_id != $uid) {
                    $result['msg'] = lang('Insufficient_opposite_balance');
                    $result['code'] = 0;
                } else {
                    $result['msg'] = lang('Insufficient_Balance');
                    $result['code'] = 10002;
                }
                return_json_encode($result);
            }

        }

        if ($user_info['is_talker'] != 1) {
            $friendship = friendship_level($uid, $to_uid);
            /*$data['is_text'] = $friendship['is_text'];
            $data['is_audio'] = $friendship['is_audio'];
            $data['is_video'] = $friendship['is_video'];*/
            if ($type == 1) {
                if ($friendship['is_audio'] != 1) {
                    $result['msg'] = lang('Unable_call_due_insufficient_level_close_friend');
                    return_json_encode($result);
                }
            } else {
                if ($friendship['is_video'] != 1) {
                    $result['msg'] = lang('Unable_call_due_insufficient_level_close_friend');
                    return_json_encode($result);
                }
            }
        }

        $voice_id = redis_hGet("user_voice", $uid);
        if ($voice_id) {
            $result['msg'] = lang('Please_exit_voice_room_dial');
            return_json_encode($result);
        }

        $to_uid_voice = redis_hGet("user_voice", $to_uid);
        if ($to_uid_voice) {
            $result['msg'] = lang('other_party_cant_talk_in_voice_room');
            return_json_encode($result);
        }

        $result['code'] = 1;
        return_json_encode($result);
    }

    /**
     * 用户拨打1v1通话
     * id 对方id call_type 1语音通话 2视频通话
     */
    public function video_call_1215()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $to_uid = intval(input('param.to_uid')); // 被呼叫人
        $call_type = intval(input('param.call_type')); //1 语音 2视频
        $free_type = intval(input('param.free_type')); //1 陪聊 2陪玩
        // 是否是匹配通话的
        $is_matching = intval(input('param.is_matching')) ? intval(input('param.is_matching')) : 0;

        $user_info = check_login_token($uid, $token, ['income', 'friend_coin']);

        //是否是自己
        if ($to_uid == $uid) {

            $result['msg'] = lang('You_cant_call_yourself');
            return_json_encode($result);
        }

        $voice_id = redis_hGet("user_voice", $uid);
        if ($voice_id) {
            $result['msg'] = lang('Please_exit_voice_room_dial');
            return_json_encode($result);
        }

        $to_uid_voice = redis_hGet("user_voice", $to_uid);
        if ($to_uid_voice) {
            $result['msg'] = lang('other_party_cant_talk_in_voice_room');
            return_json_encode($result);
        }

        $VideoCallModel = new VideoCallModel();

        $UserModel = new UserModel();

        // 对方用户信息
        $to_user_info = get_user_base_info($to_uid, ['custom_video_charging_coin,is_open_do_not_disturb', 'is_voice_online'], 1);
        /*if($free_type==1){
            //陪聊收费
            $is_free = 0;
            $anchor_id = $to_uid;
        }else */
        if ($free_type == 2) {
            //陪玩不收费
            $is_free = 1;
            $anchor_id = $to_uid;
        } else {
            //双方用户身份
            $user_identity = get_user_identity($uid);
            $to_user_identity = get_user_identity($to_uid);
            if ($user_identity < 2 && $to_user_identity < 2) {
                $result['msg'] = lang('Ordinary_users_cannot_make_calls');
                return_json_encode($result);
            }
            $is_free = 0;
            $anchor_id = $to_uid;
            if ($user_identity == $to_user_identity) {
                //不是普通用户 身份一样不收费
                $is_free = 1;
            } else if ($to_user_identity == 2 || $to_user_identity == 4) {
                $anchor_id = $to_uid;
            } else if ($user_identity == 2 || $user_identity == 4) {
                $anchor_id = $uid;
                //主播主动发起通话不扣费
                $is_free = 1;
            }
        }

        //$anchor_id = $to_user_info['is_auth'] == 1 ? $to_uid : $uid ;

        $result['data'] = ['anchor_id' => $anchor_id];
        //判断对方是否开启勿扰
        /*if ($to_user_info['is_open_do_not_disturb'] == 1) {

            $result['code'] = 10019;
            $result['msg'] = "对方手机不在身边，请稍后再拨!";
            return_json_encode($result);
        }
        */
        /*if ($to_user_info['is_online'] != 1) {

            $result['code'] = 10017;
            $result['msg'] = '对方手机不在身边，请稍后再拨!';
            return_json_encode($result);
        }
        if ($to_user_info['is_voice_online'] != 1) {

            $result['code'] = 10017;
            $result['msg'] = '对方不方便接听电话!';
            return_json_encode($result);
        }*/
        //查询是否有自己的通话记录
        $self_video_call_record = $VideoCallModel->video_call_record($uid);

        if ($self_video_call_record) {
            //删除通话,添加通话记录
            db('video_call_record')->where('id = ' . $self_video_call_record['id'])->delete();
            unset($self_video_call_record['id']);
            db('video_call_record_log')->insert($self_video_call_record);
            $result['msg'] = lang('There_calls_that_have_not_been_ended_normally');
            return_json_encode($result);

        }
        /*if (get_user_auth_status($to_uid) != 1 && get_user_auth_status($uid) != 1) {

            $result['msg'] = "对方未认证，无法发起通话！";
            return_json_encode($result);
        }*/
        $black_user = $UserModel->user_black_one($uid, $to_uid);

        if ($black_user) {

            $result['msg'] = lang('You_have_hacked_cannot_initiate_call');
            return_json_encode($result);
        }
        $black = $UserModel->user_black_one($to_uid, $uid);

        if ($black) {

            $result['msg'] = lang('Has_been_hacked_by_other_party_unable_to_call');
            return_json_encode($result);
        }
        //检查是否已经存在通话记录
        $is_exits_call_record = $VideoCallModel->video_call_record($to_uid);

        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';

        $video_call_redis = new VideoCallRedis();

        $redis_res = $video_call_redis->do_call($uid, $anchor_id);

        if ($is_exits_call_record || $redis_res == 10001) {

            $result['code'] = 10018;
            $result['msg'] = lang('other_party_is_busy');
            return_json_encode($result);
        }


        //$emcee_id = $user_info['is_auth'] == 1 ? $uid : $to_uid;
        //收费
        $config = load_cache('config');
        if ($is_free == 0) {
            $user_id = $anchor_id == $uid ? $to_uid : $uid;
            //$info = $anchor_id == $uid?$to_user_info :$user_info;
            //音频模式检查
            if (defined('OPEN_VOICE_CALL') && OPEN_VOICE_CALL == 1 && $call_type == 1) {
                //音频计时收费价格
                $video_deduction = $config['talker_audio_coin'];

            } else {
                //视频计时收费价格
                $video_deduction = $config['talker_video_coin'];
            }
            //扣费人
            $coin_type = get_user_coin($user_id, $video_deduction);
            if ($coin_type == 0) {
                if ($user_id != $uid) {
                    $result['msg'] = lang('Insufficient_opposite_balance');
                    $result['code'] = 0;
                } else {
                    $result['msg'] = lang('Insufficient_Balance');
                    $result['code'] = 10002;
                }
                return_json_encode($result);
            }

        }
        //账号是否被禁用
        if ($to_user_info['user_status'] == 0) {

            $result['msg'] = lang('other_party_suspected_violating_rules_cannot_operate');
            return_json_encode($result);
        }
        //通话频道ID
        $channel_id = NOW_TIME . $uid . mt_rand(0, 1000);
        //拨打记录
        $call_record['user_id'] = $uid;
        $call_record['call_be_user_id'] = $to_uid;
        $call_record['channel_id'] = $channel_id;
        $call_record['status'] = 0;
        $call_record['create_time'] = NOW_TIME;
        $call_record['type'] = $call_type;
        $call_record['anchor_id'] = $anchor_id;
        $call_record['is_matching'] = $is_matching;
        $call_record['is_free'] = $is_free;

        //拨打记录
        $video_call_status = $VideoCallModel->add_video_call_record($call_record);
        if ($video_call_status) {
            $user_text = get_user_base_info($uid, ['vip_end_time']);
            $user_text['is_vip'] = get_is_vip($user_text['vip_end_time']);

            $message = array(
                'anchor_id' => $anchor_id,
                'channel_id' => $channel_id,
                'to_user_base_info' => $user_text,
                'call_type' => $call_type,
            );
            $body = json_encode($message);

            // 发送通话推送
            require_once DOCUMENT_ROOT . '/system/umeng/BuguPush.php';
            $message = $user_info['user_nickname'] . lang('Calling_you_please_answer');
            $push = new BuguPush($config['umengapp_key'], $config['umeng_message_secret']);
            $push->sendAndroidCustomizedcast('go_app', $to_uid, 'buguniao', lang('Call_message'), $message, '', $body);
            $push->sendIOSCustomizedcast('go_app', $to_uid, 'buguniao', lang('Call_message'), $message, '', $body);
        }

        $result['code'] = 1;
        $result['data']['channel_id'] = $channel_id;
        $result['data']['to_user_base_info'] = get_user_base_info($to_uid, ['vip_end_time']);
        //$result['data']['to_user_base_info']['is_vip'] = get_is_vip($result['data']['to_user_base_info']['vip_end_time']);
        $this->add_friend($uid, $to_uid, 1);
        return_json_encode($result);
    }

    //取消视频电话
    public function cancel_video_call()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);
        $VideoCallModel = new VideoCallModel();
        $user_where = "user_id=" . $uid . " and status=0";
        $call_record = $VideoCallModel->video_call_record_one($user_where);
        if (!$call_record) {
            $result['msg'] = lang('Call_record_does_not_exist');
            return_json_encode($result);
        }
        if ($call_record) {
            $call_record['status'] = 3;
            $call_record['end_time'] = NOW_TIME;
            //删除通话记录
            $VideoCallModel->del_video_call_record($call_record['id']);
            //同步到日志
            unset($call_record['id']);
            $VideoCallModel->add_video_call_record_log($call_record);
        }

        $data['channel_id'] = $call_record['channel_id'];
        $result['code'] = 1;
        $result['data'] = $data;

        //删除拨打视频通话缓存记录
        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
        $video_call_redis = new VideoCallRedis();
        $video_call_redis->del_call($call_record['user_id']);
        $video_call_redis->del_call($call_record['call_be_user_id']);

        return_json_encode($result);
    }

    /**
     *    回复1v1通话
     *    id 接收人  channel 通道字符串 type 1接通 2拒绝
     */
    public function reply_video_call_0907()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = input('param.uid');

        $token = input('param.token');

        $to_uid = intval(input('param.to_uid')); //接收人

        $channel = input('param.channel'); //获取通道字符串

        $type = intval(input('param.type'));

        $user_info = check_login_token($uid, $token);

        $VideoCallModel = new VideoCallModel();
        $where = 'user_id = ' . $uid . ' or call_be_user_id =' . $uid;
        $call_record = $VideoCallModel->video_call_record_one($where);
        //查询是否存在通话记录
        //$call_record = $VideoCallModel -> sel_video_call_record_one($channel);

        if (!$call_record) {

            $result['msg'] = lang('Call_record_does_not_exist');

            return_json_encode($result);
        }

        if ($type == 1) {

            $change_data['status'] = 1;
            //修改通话状态
            $VideoCallModel->upd_video_call_record($call_record['id'], $change_data);
            //任务
            if ($call_record['type'] == 1) {
                task_reward(6, $call_record['user_id']);
            } else {
                task_reward(7, $call_record['user_id']);
            }
            $this->add_friend($uid, $to_uid, 2);
        } else if ($type == 2) {

            $call_record['status'] = 2;

            $call_record['end_time'] = NOW_TIME;
            //拒绝接听电话删除通话记录
            $VideoCallModel->del_video_call_record($call_record['id']);
            unset($call_record['id']);
            $VideoCallModel->add_video_call_record_log($call_record);

            //删除拨打视频通话缓存记录
            require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';

            $video_call_redis = new VideoCallRedis();

            $video_call_redis->del_call($call_record['user_id']);

            $video_call_redis->del_call($call_record['call_be_user_id']);
        }

        $result['code'] = 1;
        $result['data']['to_uid'] = $to_uid;
        $result['data']['channel'] = $channel;
        $result['data']['type'] = $type;

        return_json_encode($result);
    }

    // 结束1v1通话
    public function end_video_call()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $VideoCallModel = new VideoCallModel();

        $UserModel = new UserModel();

        // 查询通话记录
        $video_call_record = $VideoCallModel->video_call_record($uid);

        if (!$video_call_record) {
            $result['msg'] = lang('operation_failed');
            return_json_encode($result);
        }

        // 查询正在通话的记录
        $user_where = "(user_id=" . $uid . " or call_be_user_id=" . $uid . ") and (status=1 or status=0) ";

        $call_record = $VideoCallModel->video_call_record_one($user_where);

        if (!$call_record) {
            $result['msg'] = lang('operation_failed');
            return_json_encode($result);
        }

        //删除拨打视频通话缓存记录
        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
        $video_call_redis = new VideoCallRedis();

        foreach ($video_call_record as $k => $v) {

            $v['end_time'] = NOW_TIME;
            $v['status'] = 3;
            // 通话时长
            $v['call_time'] = $v['end_time'] - $v['create_time'];
            // 删除通话记录，添加日志记录
            $VideoCallModel->del_video_call_record($v['id']);
            unset($v['id']);
            $VideoCallModel->add_video_call_record_log($v);

            $video_call_redis->del_call($v['anchor_id']);
        }
        // 对方id
        $to_uid = $call_record['user_id'] == $uid ? $call_record['call_be_user_id'] : $call_record['user_id'];
        $result['code'] = 1;
        $result['data']['to_uid'] = $to_uid;
        return_json_encode($result);
    }

    //是否需要扣费
    public function is_need_charging()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = get_user_base_info($uid);

        $result['is_need_charging'] = 0;
        $result['is_free'] = 0;
        //双方用户身份
        $user_identity = get_user_identity($uid);
        $to_user_identity = get_user_identity($to_user_id);

        if ($user_identity == 2) {
            //自己是主播不扣费
            $result['is_free'] = 0;
            $result['is_need_charging'] = 0;
        } else if ($to_user_identity == 2) {
            //对方是主播扣费
            $result['is_free'] = 1;
            $result['is_need_charging'] = 1;
        }

        $log = db('video_call_record_log')
            ->where('status = 3 and user_id = ' . $uid . ' and call_be_user_id = ' . $to_user_id)
            ->whereOr('status = 3 and call_be_user_id = ' . $uid . ' and user_id = ' . $to_user_id)
            ->find();
        if ($log) {
            $result['is_need_charging'] = 0;
        }

        $config = load_cache('config');
        $VideoCallModel = new VideoCallModel();

        $user_where = "(user_id=" . $uid . " or call_be_user_id=" . $uid . ")";
        $call_record = $VideoCallModel->video_call_record_one($user_where);
        if ($call_record) {
            //扣费金额
            if ($call_record['type'] == 1) {
                //音频
                $charging_coin = $config['talker_audio_coin'];
                //$type = 2;
            } else {
                //视频
                $charging_coin = $config['talker_video_coin'];
                //$type = 3;
            }
        } else {
            //默认语音
            $charging_coin = $config['talker_audio_coin'];
        }

        $result['charging_coin'] = $charging_coin;
        return_json_encode($result);
    }

    //视频通话计时扣费
    public function video_call_time_charging()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user = check_login_token($uid, $token, ['friend_coin', 'last_login_ip', 'is_talker', 'is_player', 'free_speak_ticket', 'free_video_ticket']);

        $VideoCallModel = new VideoCallModel();

        $UserModel = new UserModel();
        // 查询正在通话的记录
        $user_where = "(user_id=" . $uid . " or call_be_user_id=" . $uid . ") and status=1";

        $call_record = $VideoCallModel->video_call_record_one($user_where);
        //$result['data'] = $call_record;
        //return_json_encode($result);
        if (!$call_record) {
            $result['code'] = 10011;
            $result['msg'] = lang('Call_record_does_not_exist');
            return_json_encode($result);
        }

        //对方用户ID
        $to_user_id = $uid == $call_record['user_id'] ? $call_record['call_be_user_id'] : $call_record['user_id'];
        $to_user_info = get_user_base_info($to_user_id, ['is_talker', 'is_player', 'last_login_ip', 'custom_video_call_coin', 'custom_audio_call_coin']);

        //不扣费
        /*if($user['is_talker']==1 && $to_user_info['is_talker']==1){
            //双方是陪聊
            $result['code'] = 10022;
            $result['msg'] = '主播不扣费！';
            return_json_encode($result);
        }else if($user['is_talker']==1){
            //自己是陪聊
            $result['code'] = 10022;
            $result['msg'] = '主播不扣费！';
            return_json_encode($result);
        }else if($to_user_info['is_talker']==1){
            //对方是陪玩
            $result['code'] = 10022;
            $result['msg'] = '陪玩不扣费！';
            return_json_encode($result);
        }*/

        //消费人ID
        //$to_uid = $call_record['user_id'] == $call_record['anchor_id']?$call_record['call_be_user_id']:$call_record['user_id'];
        // 获取通话对方id
        //$to_uid = $uid == $call_record['user_id'] ? $call_record['call_be_user_id'] : $call_record['user_id'];
        // 扣费方
        //$to_user_info = get_user_base_info($to_uid, ['friend_coin','free_speak_ticket','free_video_ticket','last_login_ip'], 1);

        //后台统一价格
        $config = load_cache('config');
        $charging_coin = 0;

        //收费通话
        if ($call_record['type'] == 1) {
            //音频
            if ($to_user_info['custom_audio_call_coin'] > 0) {
                $charging_coin = $to_user_info['custom_audio_call_coin'];
            } else {
                $charging_coin = $config['talker_audio_coin'];
            }
            $type = 2;
        } else {
            //视频
            if ($to_user_info['custom_video_call_coin'] > 0) {
                $charging_coin = $to_user_info['custom_video_call_coin'];
            } else {
                $charging_coin = $config['talker_video_coin'];
            }
            $type = 3;
        }

        $result['data']['charging_coin'] = $charging_coin;
        $result['data']['channel'] = $call_record['channel_id'];
        //查询上次扣费时间到当前是否满足一分钟
        $last_where = [
            'user_id' => $uid,
            'to_user_id' => $to_user_id,
            'channel_id' => $call_record['channel_id'],
        ];
        // 查询最后一条扣费记录
        $last_charge_record = $VideoCallModel->sel_video_charging_record_one($last_where);

        if ($last_charge_record && NOW_TIME - $last_charge_record['create_time'] < 55) {
            return_json_encode($result);
        }
        $to_uid_coin = get_user_coin($uid, $charging_coin);
        if ($to_uid_coin == 0) {
            //用户是收益者扣对方的
            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }
        // 启动事务
        /* Db::startTrans();
         try {*/
        //扣除免费通话数量
        if ($call_record['type'] == 1) {
            //音频
            if ($user['free_speak_ticket'] > 0) {
                //扣除
                $charging_coin_res = db('user')
                    ->where(['id' => $user['id']])
                    ->dec('free_speak_ticket', 1)
                    ->update();
            } else {
                $charging_coin_res = $UserModel->deduct_user_coin_new($user, $charging_coin, $to_uid_coin, 2, $to_user_id);
                save_coin_log($user['id'], $charging_coin, 1, 5);
            }
        } else {
            //视频
            if ($user['free_video_ticket'] > 0) {
                //扣除
                $charging_coin_res = db('user')
                    ->where(['id' => $user['id']])
                    ->dec('free_video_ticket', 1)
                    ->update();
            } else {
                $charging_coin_res = $UserModel->deduct_user_coin_new($user, $charging_coin, $to_uid_coin, 3, $to_user_id);
                save_coin_log($user['id'], $charging_coin, 1, 3);
            }
        }
        // 扣除用户余额 1:1对1通话计时
        //$charging_coin_res =$UserModel -> deduct_user_coin($user,$charging_coin,1);

        if (!$charging_coin_res) {
            // 提交事务
            //Db::rollback();
            $result['msg'] = lang('Insufficient_Balance');
            $result['code'] = 10002;
            return_json_encode($result);
        }
        //增加总消费记录
        if ($charging_coin_res) {

            // 获取主播提成收益
            if ($to_uid_coin == 1) {
                $proportion = $config['heart_talker_chat_proportion'];
            } else {
                $proportion = $config['friend_talker_chat_proportion'];
            }

            $income_total = round($proportion * $charging_coin, 2);
            // 增加主播收益 1:1对1通话计时
            $UserModel->add_user_earnings($to_user_id, $income_total, $user, $type);
            // 获取用户余额
            $deduction = $UserModel->user_coin($uid);
            // 返回用户余额
            $result['data']['coin'] = $deduction['coin'];
            // 通话剩余时间
            $result['data']['remaining_time'] = $deduction['coin'] > 0 && $charging_coin > 0 ? floor($deduction['coin'] / $charging_coin) : 0;
            //增加通话扣费记录
            $data = [
                'user_id' => $uid,
                'to_user_id' => $to_user_id,
                'coin' => $charging_coin,
                'profit' => $income_total,
                'create_time' => NOW_TIME,
                'channel_id' => $call_record['channel_id'],
            ];

            $VideoCallModel->add_video_charging_record($data);
            //增加总扣费记录
            $consume_id = add_charging_log($uid, $to_user_id, 4, $charging_coin, $call_record['channel_id'], $income_total);

            //邀请收益记录
            request_invite_record($to_user_id, 3, $income_total, $consume_id);
        }

        // 提交事务
        /*    Db::commit();
        } catch (\Exception $e) {
            //$result['msg'] = $e -> getMessage();
            $result['msg'] = "余额不足！";
            $result['code'] = 10002;
            // 回滚事务
            Db::rollback();
        }*/

        return_json_encode($result);
    }

    // 加速匹配消费
    public function accelerate_matching()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user = check_login_token($uid, $token, ['custom_video_charging_coin', 'last_login_ip']);

        $config = load_cache('config');

        if ($user['coin'] < $config['accelerate_matching']) {
            $result['msg'] = lang('Insufficient_Balance');
            $result['code'] = 10002;
            return_json_encode($result);
        }

        $UserModel = new UserModel();
        // 扣除加速匹配的金额 4:加速匹配
        $accelerate_matching = $UserModel->deduct_user_coin($user, $config['accelerate_matching'], 4);

        if ($accelerate_matching) {
            // 获取用户余额
            $deduction = $UserModel->user_coin($uid);
            //增加总扣费记录
            add_charging_log($uid, 0, 12, $config['accelerate_matching'], 0, 0);

            $result['code'] = 1;
            $result['data']['coin'] = $deduction['coin'];

        } else {

            $result['msg'] = lang('Insufficient_Balance');
            $result['code'] = 10002;
        }
        return_json_encode($result);
    }

    //获取通话消息信息
    public function get_video_call_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $id = intval(input('param.id'));

        $user_info = check_token($uid, $token);

        $video_call = db('video_call_record')->where('user_id', '=', $id)->find();
        if (!$video_call) {
            $result['code'] = 0;
            $result['msg'] = lang('Call_ended');
            return_json_encode($result);
        }

        $call_user_info = get_user_base_info($id);
        $ext = array();
        $ext['type'] = 12;//type 12 语聊系统海外版消息请求推送
        $sender['id'] = $id;
        $sender['user_nickname'] = $call_user_info['user_nickname'];
        $sender['avatar'] = $call_user_info['avatar'];
        $ext['channel'] = $video_call['channel_id'];//通话频道
        $ext['is_use_free'] = $video_call['is_free'];
        $ext['sender'] = $sender;

        $result['ext'] = $ext;
        return_json_encode($result);
    }

    //获取拨打的电话记录
    public function get_video_call_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = input('param.token');

        $user_info = check_login_token($uid, $token);

        $result['list'] = db('video_call_record')
            ->alias('v')
            ->where('v.call_be_user_id', '=', $user_info['id'])
            ->field('u.user_nickname,u.avatar,v.user_id,v.create_time')
            ->join('user u', 'v.user_id=u.id')
            ->select();

        foreach ($result['list'] as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        return_json_encode($result);
    }

    //预约用户列表
    public function subscribe_user()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = check_login_token($uid, $token);
        if ($user_info['sex'] != 1) {
            $result['code'] = 0;
            $result['msg'] = lang('Female_users_cannot_make_appointment');
        }

        if ($user_info['is_auth'] == 1) {
            $result['code'] = 0;
            $result['msg'] = lang('anchor_cannot_reserve_users_moment');

            return_json_encode($result);
        }
        $video_call_subscribe = db('video_call_subscribe')
            ->where('user_id', '=', $uid)
            ->where('to_user_id', '=', $to_user_id)
            ->where('status', 'neq', 2)
            ->select();

        if ($video_call_subscribe) {
            $result['code'] = 0;
            $result['msg'] = lang('Already_made_an_appointment');
            return_json_encode($result);
        }

        //判断余额是否足够一分钟
        $user_coin = db('user')->where('id', '=', $uid)->value('coin');

        $config = load_cache('config');

        $to_user_info = get_user_base_info($to_user_id, ['custom_video_charging_coin'], 1);
        $to_user_level = get_level($to_user_id);

        $to_user_info['charging_coin'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            if (isset($to_user_info['custom_video_charging_coin']) && $to_user_level >= $config['custom_video_money_level'] && $to_user_info['custom_video_charging_coin'] > 0) {
                $to_user_info['charging_coin'] = $to_user_info['custom_video_charging_coin'];
            }
        }

        if ($user_coin < $to_user_info['charging_coin']) {
            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        //扣费
        $charging_coin_res = db('user')->where('id', '=', $uid)->setDec('coin', $to_user_info['charging_coin']);
        if (!$charging_coin_res) {
            $result['code'] = 0;
            $result['msg'] = lang('Fee_deduction_failed');
            return_json_encode($result);
        }

        //增加预约记录
        $video_call_subscribe_data = [
            'user_id' => $uid,
            'to_user_id' => $to_user_id,
            'create_time' => NOW_TIME,
            'coin' => $to_user_info['charging_coin'],
            'status' => 0,
        ];

        $insert_res = db('video_call_subscribe')->insert($video_call_subscribe_data);
        if (!$insert_res) {
            $result['code'] = 0;
            $result['msg'] = lang('Appointment_failed');
            return_json_encode($result);
        } else {
            $result['msg'] = lang('Appointment_succeeded');
        }

        return_json_encode($result);

    }

    //回拨打视频通话
    public function back_video_call()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        //被呼叫人
        $id = intval(input('param.id'));

        $user_info = check_login_token($uid, $token, ['income', 'coin_system', 'custom_video_charging_coin', 'is_auth', 'coin_system']);

        //是否是自己
        if ($id == $uid) {
            $result['code'] = 0;
            $result['msg'] = lang('You_cant_call_yourself');
            return_json_encode($result);
        }

        if ($user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = lang('Unable_to_call_back_without_authentication');
            return_json_encode($result);
        }

        //对方信息
        $to_user_info = get_user_base_info($id, ['custom_video_charging_coin', 'is_open_do_not_disturb', 'is_auth', 'is_voice_online'], 1);

        //对方是否是主播
        if ($user_info['is_auth'] == 1 && $to_user_info['is_auth'] == 1) {
            $result['code'] = 0;
            $result['msg'] = lang('Cannot_initiate_video_between_anchors');
            return_json_encode($result);
        }

        $result['data']['to_user_base_info'] = $to_user_info;

        //账号是否被禁用
        if ($to_user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('other_party_suspected_violating_rules_cannot_operate');
            return_json_encode($result);
        }

        $config = load_cache('config');

        //是否被对方拉黑
        $black = db('user_black')->where('user_id', '=', $id)->where('black_user_id', '=', $uid)->find();
        if ($black) {
            $result['code'] = 0;
            $result['msg'] = lang('Blackout_unable_to_video');
            return_json_encode($result);
        }

        //判断对方是否开启勿扰
        /*   if ($to_user_info['is_open_do_not_disturb'] == 1) {
               $result['code'] = 10019;
               $result['msg'] = "对方开启了勿扰模式！";
               return_json_encode($result);
           }*/
        if ($to_user_info['is_voice_online'] != 1) {

            $result['code'] = 10019;
            $result['msg'] = lang('other_party_has_turned_not_disturb_mode');
            return_json_encode($result);
        }
        if ($to_user_info['is_online'] != 1) {
            $result['code'] = 10017;
            $result['msg'] = lang('other_party_is_not_online');
            return_json_encode($result);
        }

        //检查是否已经存在通话记录
        $is_exits_call_record = db("video_call_record")->whereOr('call_be_user_id', '=', $id)->whereOr('user_id', '=', $id)->whereOr('anchor_id', '=', $id)->select();
        if ($is_exits_call_record) {
            $result['code'] = 10018;
            $result['msg'] = lang('other_party_is_busy');
            return_json_encode($result);
        }

        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
        $video_call_redis = new VideoCallRedis();
        $redis_res = $video_call_redis->do_call($uid, $id);
        if ($redis_res == 10001) {
            $result['code'] = 10018;
            $result['msg'] = lang('other_party_is_busy');
            return_json_encode($result);
        }

        //通话频道ID
        $channel_id = NOW_TIME . $uid . mt_rand(0, 1000);

        //拨打记录
        $call_record['user_id'] = $uid;
        $call_record['call_be_user_id'] = $id;
        $call_record['channel_id'] = $channel_id;
        $call_record['status'] = 0;
        $call_record['create_time'] = NOW_TIME;
        $call_record['anchor_id'] = $uid;

        //拨打记录
        db("video_call_record")->insert($call_record);
        //处理预约
//        db('video_call_subscribe')
//            ->where('user_id', '=', $id)
//            ->where('to_user_id', '=', $uid)
//            ->update(['status' => 2]);
        $result['data']['channel_id'] = $channel_id;
        $result['code'] = 1;

        return_json_encode($result);
    }


    //一键约爱
    public function one_key_video_call_1901017()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = input('param.token');

        $user_info = check_token($uid, $token, ['income', 'coin_system', 'custom_video_charging_coin', 'is_use_free_time']);

        //随机查找在线主播进行视频通话
        $emcee_id = get_rand_emcee($uid);

        if (!$emcee_id) {
            $result['code'] = 0;
            $result['msg'] = lang('anchors_are_not_online_yet');
            return_json_encode($result);
        }

        $result['emcee_id'] = $emcee_id;
        return_json_encode($result);

    }

    //取消视频电话
    public function hang_up_video_call_0907()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $call_record = db('video_call_record')->where(['user_id' => $user_info['id']])->find();
        if ($call_record) {
            $call_record['status'] = 3;
            $call_record['end_time'] = NOW_TIME;
            //删除通话记录
            db('video_call_record')->where(['user_id' => $user_info['id']])->delete();
            //同步到日志
            db('video_call_record_log')->insert($call_record);

            $data['channel_id'] = $call_record['channel_id'];
            $result['code'] = 1;
            $result['data'] = $data;

            //删除拨打视频通话缓存记录
            require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
            $video_call_redis = new VideoCallRedis();
            $video_call_redis->del_call($call_record['user_id']);
            $video_call_redis->del_call($call_record['call_be_user_id']);
        }

        return_json_encode($result);
    }

    //获取结束收益信息
    public function get_video_call_end_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = check_login_token($uid, $token);

        //查询通话记录
        $video_call_record = db('video_call_record_log')->where(['channel_id' => $channel_id])->find();

        if (!$video_call_record) {
            $result['msg'] = lang('Call_record_does_not_exist');
            $result['code'] = 10002;
            return_json_encode($result);
        }

        if ($uid == $video_call_record['anchor_id']) {

            $video_sum_field = 'profit';
            $gift_sum_field = 'profit';
            $where_video_total = 'channel_id=' . $video_call_record['channel_id'] . ' and to_user_id=' . $user_info['id'];
            $where_gift_total = 'channel_id=' . $video_call_record['channel_id'] . ' and to_user_id=' . $user_info['id'];
            //是否点过赞
            $result['is_follow'] = 1;
        } else {
            $video_sum_field = 'coin';
            $gift_sum_field = 'gift_coin';
            $where_video_total = 'channel_id=' . $video_call_record['channel_id'] . ' and user_id=' . $user_info['id'];
            $where_gift_total = 'channel_id=' . $video_call_record['channel_id'] . ' and user_id=' . $user_info['id'];

            $attention = db('user_attention')->where("uid=$uid and attention_uid=" . $video_call_record['anchor_id'])->find();
            $result['is_follow'] = 0;
            if ($attention) {
                $result['is_follow'] = 1;
            }
        }

        //视频总收入
        $video_total = db('video_charging_record')->where($where_video_total)->sum($video_sum_field);
        //礼物总收入
        $gift_total = db('user_gift_log')->where($where_gift_total)->sum($gift_sum_field);

        $result['video_count'] = $video_total;
        $result['gift_count'] = $gift_total;
        $result['total_count'] = $video_total + $gift_total;

        return_json_encode($result);

    }

    //定时查看是否通话超时
    public function check_time_out()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = check_login_token($uid, $token);

        //查询视频记录
        $config = load_cache('config');
        $video_record = db('video_call_record')->where('channel_id', '=', $channel_id)->find();
        $time = NOW_TIME - $video_record['create_time'];
        if ($time > $config['video_call_time_out']) {
            //超时
            $result['status'] = 4;
        } else {
            $result['status'] = 0;
        }

        return_json_encode($result);
    }

    public function is_need_charging_qiniu()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $to_user_id = intval(input('param.to_user_id'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = get_user_base_info($uid);

        $result['is_need_charging'] = 0;
        if ($user_info['is_auth'] != 1) {
            $result['is_need_charging'] = 1;
        }

        $config = load_cache('config');
        $result['video_deduction'] = $config['video_deduction'];

        $emcee_id = $user_info['is_auth'] == 1 ? $uid : $to_user_id;
        $emcee_info = get_user_base_info($emcee_id, ['custom_video_charging_coin']);
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            $emcee_level = get_level($emcee_id);
            //判断用户等级是否符合规定
            if ($emcee_level >= $config['custom_video_money_level'] && $emcee_info['custom_video_charging_coin'] != 0) {
                $result['video_deduction'] = $emcee_info['custom_video_charging_coin'];
            }
        }
        $config = load_cache('config');
        $result['resolving_power'] = $config['phone_resolving_power'];


        /*----------------生成七牛RoomToken---------------------------start*/
        require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Config::get('qiniu.accessKey');
        $secretKey = Config::get('qiniu.secretKey');

        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        $app_client = new AppClient($auth);
        $result['room_token'] = trim($app_client->appToken("dtoanlepg", $channel_id, (string)$uid, (NOW_TIME + 3600), 'user'));
        /*----------------生成七牛RoomToken---------------------------end*/

        return_json_encode($result);
    }


    //通话满意度点赞
    public function video_fabulous()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = check_login_token($uid, $token);

        $record = db('video_call_record_log')->where('channel_id', '=', $channel_id)->find();
        if (!$record) {

            return_json_encode($result);
        }

        db('video_call_record_log')->where('channel_id', '=', $channel_id)->setField('is_fabulous', 1);

        return_json_encode($result);
    }

    //获取实时的通话金额、礼物金额、消费金额
    public function get_video_call_time_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $channel_id = trim(input('param.channel_id'));

        if (empty($uid) || empty($channel_id)) {
            $result['code'] = 0;
            $result['msg'] = lang('Parameter_transfer_error');
            return_json_encode($result);
        }

        //查询通话记录
        $video_call_record = db('video_call_record')
            ->where(['channel_id' => $channel_id])
            ->find();

        //判断扣费人,查询用户余额
        //$user = get_user_base_info($video_call_record['user_id']);

        $to_user_id = $video_call_record['user_id'] == $uid ? $video_call_record['call_be_user_id'] : $video_call_record['user_id'];

        $coin = db('user')->where(['id' => $to_user_id])->sum('coin');

        //查询消费数量
        $total_coin = db('video_charging_record')->where('channel_id', '=', $video_call_record['channel_id'])->sum('coin');

        $gift_total = db('user_gift_log')
            ->where('channel_id', '=', $video_call_record['channel_id'])
            ->sum('gift_coin');

        $result['total'] = $total_coin + $gift_total;
        $result['video_call_total_coin'] = $total_coin;
        $result['gift_total_coin'] = $gift_total;
        $result['user_coin'] = $coin;

        return_json_encode($result);
    }

    public function add_friend($uid, $touid, $type)
    {
        if ($type == 1) {
            //拨打
            //是否存在好友
            $user_friend = db('user_friend')->where(['touid' => $touid, 'uid' => $uid])->find();
            if (!$user_friend) {
                $data_friend = [
                    'uid' => $uid,
                    'touid' => $touid,
                    'status' => 0,
                    'addtime' => NOW_TIME,
                ];
                db('user_friend')->insert($data_friend);
            }
        } else {
            //接通
            //是否存在好友
            $user_friend = db('user_friend')->where(['touid' => $uid, 'uid' => $touid])->find();
            if ($user_friend['status'] == 0) {
                db('user_friend')->where(['touid' => $uid, 'uid' => $touid])->update(['status' => 1]);
                $data_friend = [
                    'uid' => $uid,
                    'touid' => $touid,
                    'status' => 1,
                    'addtime' => NOW_TIME,
                ];
                db('user_friend')->insert($data_friend);
            }
        }
    }

}
