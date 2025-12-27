<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/5/21
 * Time: 10:20
 */

namespace app\api\controller;

use app\common\Enum;
use BuguPush;
use UserOnlineStateRedis;
use VideoCallRedis;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
header("Content-Type:text/html; charset=utf-8");

class ImCallbackApi extends Base
{

    public function callback()
    {

        require_once DOCUMENT_ROOT . '/system/umeng/BuguPush.php';
        $json = file_get_contents("php://input");
        $post = json_decode($json, true);

        bogokjLogPrint("IM",$json);
        if ($post['CallbackCommand'] == 'C2C.CallbackBeforeSendMsg') {

            $userInfo = get_user_base_info($post['From_Account'],['device_uuid','user_status']);

            if ($userInfo) {
                $errorReturn = ['ActionStatus' => 'OK', 'ErrorCode' => 1, 'ErrorInfo' => ''];

                //账号是否被禁用
                if ($userInfo['user_status'] == 0) {
                    echo json_encode($errorReturn);
                    exit;
                }

                //设备是否被封禁
                $device = db('equipment_closures')->where('device_uuid', $userInfo['device_uuid'])->find();
                if ($device) {
                    echo json_encode($errorReturn);
                    exit;
                }
            }

        } else if ($post['CallbackCommand'] == 'C2C.CallbackAfterSendMsg') {
            if ($post['MsgBody'][0]['MsgType'] == 'TIMTextElem') {

                //普通私信消息
                $config = load_cache('config');
                $push = new BuguPush($config['umengapp_key'], $config['umeng_message_secret']);
                $custom = [
                    'action' => 1,
                    'user_id' => $post['From_Account']
                ];
                $Text = $post['MsgBody'][0]['MsgContent']['Text'];
                // 增加聊天记录
                $this->add_chat_record($post['From_Account'], $post['To_Account'], $Text, 1);
                task_reward(3, $post['From_Account']);

//                if(!is_online($post['To_Account'],$config['heartbeat_interval'])){
//
//                    $push -> sendAndroidCustomizedcast('go_app',$post['To_Account'],'buguniao','私信消息','你有新的消息',$post['MsgBody'][0]['MsgContent']['Text'],json_encode($custom));
//                }
                $push->sendAndroidCustomizedcast('go_app', $post['To_Account'], 'buguniao', '私信消息', '你有新的消息', $Text, json_encode($custom));
                $push->sendIOSCustomizedcast('go_app', $post['To_Account'], 'buguniao', '私信消息', '你有新的消息', $Text, json_encode($custom));

                //自定义回复消息功能测试
                if (defined('OPEN_CUSTOM_AUTO_REPLY') && OPEN_CUSTOM_AUTO_REPLY == 1) {
                    require_once(DOCUMENT_ROOT . '/system/im_common.php');

                    //->where('is_online', '=', 1) 自动回复不需要在线
                    $emcee = db('user')->where('is_open_auto_see_hi', '=', 1)->where('is_auth', '=', 1)->where('id', '=', $post['To_Account'])->find();
                    if ($emcee) {
                        $auto_msg_record = db('custom_auto_msg')->where('user_id', '=', $emcee['id'])->find();
                        if ($auto_msg_record) {
                            $auto_msg_array = [];
                            foreach ($auto_msg_record as $k2 => $v2) {
                                if (strripos($k2, 'ply_msg') > 0 && !empty($v2)) {
                                    $auto_msg_array[] = $v2;
                                }
                            }

                            $msg = $auto_msg_array[rand(0, count($auto_msg_array) - 1)];
                            if (!empty($msg)) {
                                send_c2c_text_msg($post['To_Account'], $post['From_Account'], $msg);
                            }
                        }
                    }
                }

            } else if ($post['MsgBody'][0]['MsgType'] == 'TIMCustomElem') {

                $data = $post['MsgBody'][0]['MsgContent']['Data'];
                $data = json_decode($data, true);

                $config = load_cache('config');

                $push = new BuguPush($config['umengapp_key'], $config['umeng_message_secret']);

                if ($data['type'] == 23) {//赠送礼物
                    $custom = [
                        'action' => 1,
                        'user_id' => $post['From_Account']
                    ];
                    $push->sendAndroidCustomizedcast('go_app', $post['To_Account'], 'buguniao', '礼物消息', '收到礼物打赏', $data['to_msg'], json_encode($custom));
                    $push->sendIOSCustomizedcast('go_app', $post['To_Account'], 'buguniao', '礼物消息', '收到礼物打赏', $data['to_msg'], json_encode($custom));

                } elseif ($data['type'] == 12) {
                    $custom = [
                        'action' => 12,
                        'user_id' => $post['From_Account'],
                        'custom_data' => json_encode($data),
                    ];
                    $push->sendAndroidCustomizedcast('go_custom', $post['To_Account'], 'buguniao', lang('Call_message'), '新的通话消息，点击查看', '用户：' . $data['sender']['user_nickname'], json_encode($custom));
                    $push->sendIOSCustomizedcast('go_custom', $post['To_Account'], 'buguniao', lang('Call_message'), '新的通话消息，点击查看', '用户：' . $data['sender']['user_nickname'], json_encode($custom));

                }
            } else if ($post['MsgBody'][0]['MsgType'] == 'TIMSoundElem') {
                $Text = $post['MsgBody'][0]['MsgContent']['Url'];
                task_reward(3, $post['From_Account']);
                // 增加聊天记录 --语音
                $this->add_chat_record($post['From_Account'], $post['To_Account'], '', 2, $Text);
            } else if ($post['MsgBody'][0]['MsgType'] == 'TIMImageElem') {
                $Text = $post['MsgBody'][0]['MsgContent']['ImageInfoArray'][0]['URL'];
                task_reward(3, $post['From_Account']);
                // 增加聊天记录 -- 图片
                $this->add_chat_record($post['From_Account'], $post['To_Account'], '', 3, $Text);
            }
        } else if ($post['CallbackCommand'] == 'State.StateChange') {

            $user_id = $post['Info']['To_Account'];
            $action = $post['Info']['Action'];

            require_once DOCUMENT_ROOT . '/system/redis/UserOnlineStateRedis.php';

            $user_online_redis = new UserOnlineStateRedis();
            $user_online_redis->change_state($user_id, $action);
            $user_info = db('user')->where("id='$user_id'")->find();

            if ($action == 'Logout' || $action == 'Disconnect' || $action == 'TimeOut') {
                $video_record = db('video_call_record')->whereOr(['user_id' => $user_id])->whereOr(['call_be_user_id' => $user_id])->whereOr(['anchor_id' => $user_id])->select();

                if ($video_record) {
                    unset($video_record[0]['id']);
                    db('video_call_record_log')->insert($video_record[0]);

                    require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
                    $video_call_redis = new VideoCallRedis();
                    $video_call_redis->del_call($video_record[0]['anchor_id']);
                    $video_call_redis->del_call($video_record[0]['user_id']);
                    $video_call_redis->del_call($video_record[0]['call_be_user_id']);

                    require_once DOCUMENT_ROOT . '/system/im_common.php';

                    if ($video_record[0]['user_id'] == $user_id) {
                        //$uid = $user_id;
                        $touid = $video_record[0]['call_be_user_id'];
                    } else {
                        $touid = $video_record[0]['user_id'];
                        //$uid = $video_record[0]['call_be_user_id'];
                    }
                    if ($video_record[0]['type'] == 1) {
                        end_sudio_call($user_id, $touid, $video_record[0]);
                    } else {
                        end_video_call_96($user_id, $touid, $video_record[0]);
                    }
                }
                //删除通话记录
                db('video_call_record')->whereOr(['user_id' => $user_id])->whereOr(['call_be_user_id' => $user_id])->whereOr(['anchor_id' => $user_id])->delete();

                //查询没有下线的记录
                $online_record = db('online_record')->where('user_id', '=', $user_id)->where('offline_time', '=', 0)->find();
                if ($online_record) {
                    //更新下线时间
                    $online_time = NOW_TIME - $online_record['up_online_time'];
                    $update_online_data = ['offline_time' => NOW_TIME, 'time' => $online_time];
                    db('online_record')->where('id=' . $online_record['id'])->update($update_online_data);
                    //更新状态
                    db('user')->where(['id' => $user_id])->update(['is_online' => 0]);
                }

                $voice_id = redis_hGet("user_voice", $user_id);

                if ($voice_id) {
                    $voice = db('voice')->where("user_id=" . $voice_id)->find();
                    //房间人数减1
                    db('voice')->where("id=" . $voice_id)->setDec("online_number", 1);
                    //解除禁言房间缓存
                    redis_hDelOne('ban_voice_' . $voice_id, $user_id);
                    //删除直播间用户缓存
                    voice_del_userlist($voice_id, $user_id);
                    //删除用户在直播间缓存
                    redis_hDelOne("user_voice", $user_id);
                    // 更新房间在线人数
                    $online_number = voice_userlist_sum($voice_id);
                    db('voice')->where('user_id=' .$voice_id)->update(array('online_number' => intval($online_number)));
                    //退出语音房间
                    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

                    $api = createTimAPI();
                    //发送下麦消息
                    $config = load_cache('config');
                    $broadMsg['sender']['user_id'] = $user_info['id'];
                    $broadMsg['sender']['user_nickname'] = $user_info['user_nickname'];
                    $broadMsg['sender']['avatar'] = $user_info['avatar'];
                    $broadMsg['sender']['user_level'] = $user_info['level'];
                    $broadMsg['user']['user_id'] = $user_info['id'];
                    $broadMsg['user']['user_nickname'] = $user_info['user_nickname'];
                    $broadMsg['user']['avatar'] = $user_info['avatar'];
                    $broadMsg['user']['user_level'] = $user_info['level'];

                    $wheat_logs = db('voice_even_wheat_log')->where("user_id=" . $user_id . " and (status=1 or status = 0)")->find();
                    if ($wheat_logs) {
                        //正在连麦的下麦
                        $name = array('status' => 3, 'endtime' => NOW_TIME);
                        db('voice_even_wheat_log')->where("id=" . $wheat_logs['id'])->update($name);

                        $broadMsg['type'] = Enum::LOWER_WHEAT;
                        $broadMsg['wheat_id'] = $wheat_logs['voice_id'];
                        #构造rest API请求包
                        $msg_content = array();
                        //创建$msg_content 所需元素
                        $msg_content_elem = array(
                            'MsgType' => 'TIMCustomElem',       //定义类型为普通文本型
                            'MsgContent' => array(
                                'Data' => json_encode($broadMsg)    //转为JSON字符串
                            )
                        );

                        //将创建的元素$msg_content_elem, 加入array $msg_content
                        array_push($msg_content, $msg_content_elem);
                        $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);
                    }
                    //退出直播间消息
                    $broadMsg['type'] = Enum::EXIT_ROOM;
                    #构造rest API请求包
                    $msg_content = array();
                    //创建$msg_content 所需元素
                    $msg_content_elem = array(
                        'MsgType' => 'TIMCustomElem',       //定义类型为普通文本型
                        'MsgContent' => array(
                            'Data' => json_encode($broadMsg)    //转为JSON字符串
                        )
                    );
                    array_push($msg_content, $msg_content_elem);
                    $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);
                }
            } else {
                //增加上下线记录
                db('online_record')->insert(['user_id' => $user_id, 'up_online_time' => NOW_TIME]);
                //db('user')->where(['id'=>$user_id])->update(['is_online'=>1]);
            }
        }

        echo json_encode(['ActionStatus' => 'OK', 'ErrorCode' => 0, 'ErrorInfo' => '']);

    }

    /**
     * 增加聊天记录
     * @param $uid
     * @param $receive_uid
     * @param $information
     * @param $type 1文本2语音3图片
     * @param $url
     */
    public function add_chat_record($uid, $receive_uid, $information, $type, $url = '')
    {

        $chat_record = array(
            'uid' => $uid,
            'receive_uid' => $receive_uid,
            'information' => $information,
            'create_time' => NOW_TIME,
            'url' => $url,
            'type' => $type
        );
        db('chat_record')->insert($chat_record);
    }

}
