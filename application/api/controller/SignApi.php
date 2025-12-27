<?php

namespace app\api\controller;

use think\Db;
use think\helper\Time;

class SignApi extends Base
{
    //获取签到信息
    public function get_sign_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['signature', 'age', 'is_voice_online', 'occupation']);

        // 获取当前vip额外增加签到福利
        $sign_in_coin = intval(get_user_vip_authority($uid, 'sign_in_coin'));

        $sign_list = db('sign_in')->field("id,name,sum(you_coin + " . $sign_in_coin . ") as you_coin,audio_num,video_num,box_id,status")->group("id")->order('sort')->select();
        if (!$sign_list) {
            $result['code'] = 0;
            return_json_encode($result);
        }
        $data['sign_list'] = $sign_list;
        $time_day = date('Y-m-d', NOW_TIME);
        $times = date('Y-m-d', strtotime($time_day . '-1 day'));
        //最后一次签到时间
        $sign_log = db('sign_in_log')
            ->where(['uid' => $uid])
            ->order('addtime desc')
            ->find();
        //今天是否已经签到
        $data['is_sign'] = 0;
        //签到天数
        $data['date_count'] = 0;
        //签到ID
        $data['sign_id'] = 0;
        if ($sign_log) {
            if ($sign_log['sign_in_date'] == $time_day) {
                $data['is_sign'] = 1;
                $data['date_count'] = $sign_log['keep_days'];
                $data['sign_id'] = $sign_log['sign_id'];
            } else if ($sign_log['sign_in_date'] == $times) {
                //昨天是否签到
                //签到天数
                $data['date_count'] = $sign_log['keep_days'];
                $data['sign_id'] = $sign_log['sign_id'];
            }
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    //签到
    public function request_sign_in()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['last_login_ip']);

        // 获取当前vip额外增加签到福利
        $sign_in_coin = intval(get_user_vip_authority($uid, 'sign_in_coin'));

        $sign_list = db('sign_in')->field("id,name,sum(you_coin + " . $sign_in_coin . ") as you_coin,audio_num,video_num,box_id,status")->group("name")->order('sort')->select();

        if (!$sign_list) {
            $result['code'] = 0;
            $result['msg'] = lang('Abnormal_check_in_function');
            return_json_encode($result);
        }
        //签到记录
        $data['sign_list'] = $sign_list;
        $time_day = date('Y-m-d', NOW_TIME);
        $times = date('Y-m-d', strtotime($time_day . '-1 day'));
        //最后一次签到时间
        $sign_log = db('sign_in_log')
            ->where(['uid' => $uid])
            ->order('addtime desc')
            ->find();
        $date_count = 0;
        if ($sign_log) {
            if ($sign_log['sign_in_date'] == $time_day) {
                $result['code'] = 0;
                $result['msg'] = lang('You_signed_it_today');
                return_json_encode($result);
            } else if ($sign_log['sign_in_date'] == $times) {
                //昨天是否签到
                //签到天数
                $date_count = $sign_log['keep_days'];
                //$sign_id = $sign_log['sign_id'];
            }
        }
        //查询签到奖励信息
        if (isset($sign_list[$date_count])) {
            $sgin_info = $sign_list[$date_count];
        } else {
            $date_count = 0;
            $sgin_info = $sign_list[$date_count];
        }
        //签到奖励 宝箱 随机奖品
        if ($sgin_info['box_id'] == 1) {
            $box = rand(1, 3);
            if ($box == 1) {
                //友币
                $number = $sgin_info['you_coin'];
                $type = 1;
                db('user')->where(['id' => $uid])->inc('coin', $number)->update();
                upd_user_coin_log($uid, $number, $number, 8, 2, 1, $user_info['last_login_ip'], 1);
                // 钻石变更记录
                save_coin_log($uid,$number,1,6);
            } else if ($box == 2) {
                $number = $sgin_info['audio_num'];
                $type = 2;
                db('user')
                    ->where(['id' => $uid])
                    ->inc('free_speak_ticket', $number)
                    ->update();
            } else {
                $number = $sgin_info['video_num'];
                $type = 3;
                db('user')
                    ->where(['id' => $uid])
                    ->inc('free_video_ticket', $number)
                    ->update();
            }
        } else {
            $number = $sgin_info['you_coin'];
            $type = 1;
            db('user')
                ->where(['id' => $uid])
                ->inc('coin', $number)
                ->update();
            // 钻石变更记录
            save_coin_log($uid,$number,1,6);
            upd_user_coin_log($uid, $number, $number, 8, 2, 1, $user_info['last_login_ip'], 1);
        }
        $data = [
            'uid' => $uid,
            'number' => $number,
            'type' => $type,
            'sign_id' => $sgin_info['id'],
            'keep_days' => $date_count + 1,
            'sign_in_date' => $time_day,
            'addtime' => NOW_TIME,
        ];
        $sign_in_log_id = db('sign_in_log')->insertGetId($data);
        if ($sign_in_log_id) {
            $result['data']['type'] = $type;
            $result['data']['number'] = $number;
            $result['msg'] = lang('Check_in_succeeded');
        }
        //$result['data'] = $sgin_info;
        return_json_encode($result);

    }

    //任务
    public function get_task_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['signature', 'age', 'is_voice_online', 'occupation']);
        $list = db('task')->where('status = 1')->order('orderno')->select();
        foreach ($list as &$v) {
            switch ($v['id']) {
                case 1:
                    $v['name'] = lang('Bind_mobile_phone_number');
                    break;
                case 2:
                    $v['name'] = lang('Publish_news_feed');
                    break;
                case 3:
                    $v['name'] = lang('Complete_private_message_chat');
                    break;
                case 4:
                    $v['name'] = lang('Invitation_to_download_register');
                    break;
                case 5:
                    $v['name'] = lang('Share_it_on_moments');
                    break;
                case 6:
                    $v['name'] = lang('Complete_voice_call');
                    break;
                case 7:
                    $v['name'] = lang('Complete_video_call');
                    break;
                case 8:
                    $v['name'] = lang('Paid_gift_giving');
                    break;
                default:
                    $v['name'] = lang('Complete_play_order');
            }
            $task_log = db('task_log')->where(['uid' => $uid, 'type' => $v['id']])->find();
            if ($task_log) {
                $v['is_out'] = 1;
            } else {
                $v['is_out'] = 0;
            }
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //任务 统一处理
    public function task_reward()
    {
        $type = intval(input('param.type'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        $uid = $user_info['id'];
        $task_log = db('task_log')
            ->where(['uid' => $uid, 'type' => $type])
            ->find();
        $task_info = db('task')->where('status = 1')->find($type);
        //任务没完成过
        if (!$task_log && $task_info) {
            //添加记录 添加奖励
            $coin = $task_info['coin'];
            $name = $task_info['name'];
            $data = [
                'uid' => $uid,
                'coin' => $coin,
                'name' => $name,
                'type' => $type,
                'addtime' => NOW_TIME,
            ];
            $log_res = db('task_log')->insertGetId($data);
            if ($log_res) {
                db('user')
                    ->where(['id' => $uid])
                    ->inc('friend_coin', $coin)
                    ->update();
                // 钻石变更记录
                save_coin_log($uid,$coin,2,7);
                //upd_user_coin_log($uid,$coin,2,1,$user_info['last_login_ip'],$uid,18);
                upd_user_coin_log($uid, $coin, $coin, 7, 2, 1, $user_info['last_login_ip'], 1);
            }
        }
    }

}
