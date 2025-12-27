<?php

namespace app\api\controller;

use app\api\controller\Base;
use app\api\model\VideoCallModel;
use app\api\model\UserModel;
use app\api\model\VoiceModel;
use app\api\model\GiftModel;
use app\common\Enum;
use Psr\Log\LogLevel;
use Redis;
use think\Cache;

class GiftApi extends Base
{
    protected $VideoCallModel;
    protected $UserModel;
    protected $VoiceModel;
    protected $GiftModel;


    protected function _initialize()
    {
        parent::_initialize();

        $this->VideoCallModel = new VideoCallModel();
        $this->UserModel = new UserModel();
        $this->VoiceModel = new VoiceModel();
        $this->GiftModel = new GiftModel();
    }

    // 获取列表分类
    public function get_gift_type()
    {
        $result = array('code' => 1, 'msg' => '');
        $gift_list = db('gift_type')->field("id,title")->where('status = 1')->order("sort desc")->select();
        $result['list'] = $gift_list;
        return_json_encode($result);
    }

    // 获取礼物列表 --20230508
    public function get_gift_type_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('param.id'));
        $gift_list = load_cache('gift_type', ['id' => $id]);
        $result['list'] = $gift_list;

        return_json_encode($result);
    }

    // 获取私信礼物列表
    public function private_letter_gift_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $gift_list = load_cache('private_letter_gift');
        $result['list'] = $gift_list;

        return_json_encode($result);
    }

    // 获取礼物列表
    public function get_gift_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $gift_list = load_cache('gift');
        //    $gift_list = db('gift')->where('status = 1')->order("orderno asc")->select();
        $result['list'] = $gift_list;

        return_json_encode($result);
    }

    // 获取背包礼物列表
    public function get_bag_list()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $GiftModel = new GiftModel();

        $bagList = $GiftModel->sel_user_bag($uid);

        $result['count'] = $GiftModel->user_bag_count($uid);
        //$result['coin_type'] = 1;
        $result['list'] = $bagList;

        return_json_encode($result);
    }

    // 获取礼物寓意列表
    public function moral()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 获取用户本人信息
        $user_info = check_login_token($uid, $token);
        //获取守护主播的列表
        $list = $this->GiftModel->moral();

        $result['data'] = $list;
        $result['coin'] = $user_info['coin'];

        return_json_encode($result);
    }

    // 赠送背包礼物
    public function send_bag_gift()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 收礼用户id 多个用逗号隔开
        $to_user_id = trim(input('param.to_user_id'));
        // 数组
        $to_users = explode(",", $to_user_id);
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 通话id
        $call_id = intval(input('param.call_id'));
        // 礼物id
        $gid = intval(input('param.gid'));
        // 礼物数量
        $count = intval(input('param.count'));
        // 获取本用户信息
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        // 获取配置
        $config = load_cache('config');

        if ($count == 0) {

            $result['msg'] = lang('Gift_quantity_must_greater_than_0');
            return_json_encode($result);
        }

        $gift = load_cache('gift_id', ['id' => $gid]);
        $coin_type = $gift['coin_type'];

        if (!$gift) {

            $result['msg'] = lang('Gift_information_does_not_exist');
            return_json_encode($result);
        }
        // 获取单个的背包礼物
        $bag = $this->GiftModel->get_user_bag_one($uid, $gid);
        $room_id = 0;

        if ($call_id) {
            // 获取通话信息
            $call = $this->VideoCallModel->sel_video_call_record_one('', $call_id);

            if (!$call) {

                $result['msg'] = lang('Direct_call_does_not_exist');
                return_json_encode($result);
            }

        } else if ($voice_id) {
            // 获取直播间信息
            $voice_wheat = $this->VoiceModel->sel_voice_one($voice_id);
            if (!$voice_wheat) {
                $result['msg'] = lang('live_room_does_not_exist');
                return_json_encode($result);
            }
            $room_id = $voice_wheat['id'];
        }

        if (count($to_users) <= 0) {
            $result['msg'] = lang('User_has_exited');
            return_json_encode($result);
        }

        if ($bag['giftnum'] < $count * count($to_users)) {
            $result['msg'] = lang('Insufficient_gifts');
            return_json_encode($result);
        }
        // 主播收益数
        $charging_coin = $count * $gift['coin'];
        // 启动事务
        db()->startTrans();
        try {
            // 扣除的礼物数量
            $giftnum = $count * count($to_users);
            // 扣除背包礼物
            $charging_coin_res = $this->GiftModel->del_user_bag_sum($uid, $gid, $giftnum);

            if ($charging_coin_res) {
                // 返回给前端剩余的背包礼物数
                $result['giftnum'] = $bag['giftnum'] - $giftnum;
                //增加送礼物记录
                $gift_log = [
                    'user_id'     => $uid,
                    'gift_id'     => $gift['id'],
                    'gift_name'   => $gift['name'],
                    'gift_count'  => $count,
                    'gift_coin'   => $charging_coin,
                    'gift_type'   => 2,
                    'create_time' => NOW_TIME,
                ];
                $to_user = [];

                $income_total = 0;
                $proportion_type = 1; // 普通礼物
                foreach ($to_users as $v) {


                    $to_user_info = get_user_base_info($v, ['luck', 'guild_id']);
                    $income_type = 8;
                    // 用户收益
                    if ($coin_type == 1) {
                        $income_type = $voice_id ? 3 : 9;
                    }


                    // 用户收益
                    $income_totals = host_income_commission($income_type, $charging_coin, $v);

                    /*if($coin_type==1){
                        $income_totals = round($config['heart_gift_proportion'] * $charging_coin);
                    }else{
                        $income_totals = round($config['friend_gift_proportion'] * $charging_coin);
                    }*/
                    // 获取通话信息
                    if ($call_id) {
                        // 记录类型 1通话 2直播间
                        $gift_log['type'] = 5;

                    } else if ($voice_id) {

                        // 语音直播间
                        $wheat_id = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $v, 1);
                        if (!$wheat_id) {
                            $result['msg'] = lang('Current_user_not_in_Mai_Xu');
                            return_json_encode($result);
                        }
                        if (!$wheat_id && $v == $voice_wheat['user_id']) {
                            $wheat_id = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $v, 3);
                        }

                        $gift_log['voice_profit'] = 0;
                        // 直播间id标识符
                        $gift_log['room_id'] = $voice_wheat['id'];

                        // 房间类型
                        $gift_log['room_type'] = $voice_wheat['type'];
                        // 直播间收益人
                        $gift_log['voice_user_id'] = $voice_wheat['user_id'];
                        // 语音房间提成
                        $gift['room_divide_info'] = 0;
                        if ($gift['room_divide_info'] > 0 && $income_totals > 0 && $income_totals > $gift['room_divide_info']) {
                            // 直播间收益数
                            $gift_log['voice_profit'] = $gift['room_divide_info'];
                            // 直播间收益人
                            $gift_log['voice_user_id'] = $voice_wheat['user_id'];

                            // 增加直播间房主收益 5:赠送背包礼物
                            $this->UserModel->add_user_earnings($gift_log['voice_user_id'], $gift_log['voice_profit'], $user_info, 5);
                            // 增加房间收益
                            $this->VoiceModel->add_voice_vote_number($voice_id, $gift_log['voice_profit']);

                            // 收到礼物的用户实际得到的收益
                            $income_totals = $income_totals - $gift_log['voice_profit'];
                        }
                        // 增加房间总流水
                        $this->VoiceModel->add_voice_coin_number($voice_id, $charging_coin);
                        if ($wheat_id) {
                            $gift_log['voice_log_id'] = $wheat_id['id'];
                        }
                        // 记录类型 1通话 2直播间
                        $gift_log['type'] = 4;
                    }
                    //  公会提成
                    $gift_log['guild_id'] = $to_user_info['guild_id']; // 公会id
                    if ($gift_log['guild_id']) {
                        $gift_log['guild_status'] = 1;
                    }
                    // 收到礼物的用户实际得到的收益
                    $income_total = $income_totals;
                    // 增加收到礼物的用户实际得到的收益 3:赠送背包礼物
                    if ($income_total > 0) {
                        $this->UserModel->add_user_earnings($v, $income_total, $user_info, 5);
                        $notes = $uid . ';' . $gift['name'] . "(" . $gift['id'] . ") x" . $count;
                        // 收益变更记录
                        save_income_log($v, $income_total, 1, 1, $notes);
                    }

                    $gift_log['profit'] = $income_total;

                    $gift_log['to_user_id'] = $v;
                    // 添加送礼物记录
                    $table_id = $this->GiftModel->add_user_gift_log($gift_log);
                    $even_wheat_income = 0;
                    if ($voice_id) {
                        // 增加连麦表收益记录
                        if ($voice_wheat['charm_status'] == 1) {
                            if ($config['voice_charm_type'] == 1) {
                                $even_wheat_income = $income_total;
                            } else {
                                $even_wheat_income = $charging_coin;
                            }
                            $this->VoiceModel->add_voice_even_wheat_log_coin($wheat_id['id'], $even_wheat_income);
                        }
                        // 房间礼物消息
                        $this->push_send_gift_msg($user_info, $to_user_info, $count, $gift, $voice_wheat, $even_wheat_income);
                        //增加热度
                        $this->VoiceModel->upd_cumulative($voice_id, 'heat', $income_totals, 1);
                    }
                    //增加总消费记录
                    $content = lang('Gift_Backpack') . $gift['name'] . "(" . $gift['id'] . ") x" . $count;

                    $type_id = array(
                        'id'               => $room_id,
                        'type'             => 1,
                        'is_lucky'         => $proportion_type,
                        'guild_lucky_coin' => 0
                    );
                    add_charging_log($uid, $v, 23, $charging_coin, $table_id, $income_totals, $content, $type_id);
                    $to_user_info['total_ticket'] = $even_wheat_income;
                    $to_user[] = $to_user_info;
                    //全频道广播
                    if (isset($gift['is_all_notify']) && $gift['is_all_notify'] == 1) {

                        $this->push_all_gift_msg($user_info, $to_user_info, $count, $gift['name'], $gift['img']);
                    }
                    $result['code'] = 1;
                }
                //$result['data'] = $this->deal_send($uid, $to_user, $count, $voice_id, $user_info, $gift, $income_total);
                $result['data'] = $this->deal_send_voice($uid, $to_user, $count, $gift, $income_total);
            } else {

                $result['msg'] = lang('Insufficient_gifts');
                $result['code'] = 10002;
            }

            // 提交事务
            db()->commit();
        } catch (\Exception $e) {

            $result['msg'] = $e->getMessage();
            $result['code'] = 10002;
            // 回滚事务
            db()->rollback();
        }
        $GiftModel = new GiftModel();
        $result['count'] = $GiftModel->user_bag_count($uid);
        return_json_encode($result);

    }

    public function deal_send($user_id, $to_user, $num, $gift, $income)
    {
        $total_coin = $gift['coin'] * $num;
        $root['from_msg'] = lang('give') . $num . lang('individual') . $gift['name'];
        $root['from_score'] = lang('Your_experience_value') . "+" . $total_coin;
        $root['from_level'] = get_level($user_id);
        $root['to_ticket'] = intval($total_coin);
        $root['to_diamonds'] = $gift['coin']; //可获得的：钻石数；只有红包时，才有
        $root['to_user_id'] = $to_user;
        $root['prop_icon'] = $gift['img'];
        $root['prop_svga'] = $gift['svga'];
        $root['status'] = 1;
        $root['prop_id'] = $gift['id'];
        $root['to_msg'] = lang('received') . $num . lang('individual') . $gift['name'] . lang('Gain_income') . $income;
        $root['total_ticket'] = $income; //用户获得的印票数

        return $root;
    }

    public function deal_send_voice($user_id, $to_user, $num, $gift, $income)
    {
        $total_coin = $gift['coin'] * $num;
        $root['from_msg'] = lang('give') . $num . lang('individual') . $gift['name'];
        $root['from_score'] = lang('Your_experience_value') . "+" . $total_coin;
        $root['from_level'] = get_level($user_id);
        $root['to_ticket'] = intval($total_coin);
        $root['to_diamonds'] = $gift['coin']; //可获得的：钻石数；只有红包时，才有
        //$root['to_user_id'] = $to_user;
        $root['to_user_id'] = $to_user[0]['id'];
        $root['to_user'] = $to_user;
        $root['prop_icon'] = $gift['img'];
        $root['prop_svga'] = $gift['svga'];
        $root['status'] = 1;
        $root['prop_id'] = $gift['id'];
        $root['to_msg'] = lang('received') . $num . lang('individual') . $gift['name'] . lang('Gain_income') . $income;
        $root['total_ticket'] = $income; //用户获得的印票数

        return $root;
    }


    // 语音聊天室内送分类礼物
    public function gift_type_giving()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        // 对方id多个用逗号隔开
        $to_user_id = trim(input('param.to_user_id'));
        // 数组
        $to_users = explode(",", $to_user_id);
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 通话id
        $call_id = intval(input('param.call_id'));
        // 礼物id
        $gid = intval(input('param.gid'));
        // flag 前端标识-不做处理
        $result['flag'] = trim(input('param.flag'));
        // 礼物数量
        $count = intval(input('param.count'));
        // 获取配置
        $config = load_cache('config');
        // 本用户信息
        $user_info = check_login_token($uid, $token, ['last_login_ip', 'friend_coin']);

        $room_id = 0;
        if ($count <= 0) {
            $result['msg'] = lang('Gift_quantity_must_greater_than_0');
            return_json_encode($result);
        }

        $gift = load_cache('gift_id', ['id' => $gid]);
        if (!$gift) {
            $result['msg'] = lang('Gift_information_does_not_exist');
            return_json_encode($result);
        }
        if ($gift['gift_type_id'] == 1) {

            //贵族等级
            $noble = get_noble_level($uid);
            if (!$noble['noble_name']) {
                // vip等级不足
                $result['msg'] = lang('Upgrade_VIP_level');
                return_json_encode($result);
            }
//            $vip_id  = get_user_vip_authority($uid,"id");
//            if(intval($vip_id) < intval($gift['vip_id'])){
//                // vip等级不足
//                $result['msg'] = lang('Upgrade_VIP_level');
//                return_json_encode($result);
//            }


        }
        if ($call_id) {
            //获取通话信息
            $call = $this->VideoCallModel->sel_video_call_record_one('', $call_id);
            if (!$call) {
                $result['msg'] = lang('Direct_call_does_not_exist');
                return_json_encode($result);
            }
        } else if ($voice_id) {
            // 获取直播间信息
            $voice_wheat = $this->VoiceModel->sel_voice_one($voice_id);
            if (!$voice_wheat) {
                $result['msg'] = lang('live_room_does_not_exist');
                return_json_encode($result);
            }
            $room_id = $voice_wheat['id'];
        }

        if (count($to_users) <= 0) {
            $result['msg'] = lang('User_has_exited');
            return_json_encode($result);
        }

        $coin_type = $gift['coin_type'];
        // 获取所有人的消费值
        $charging_coin = $count * $gift['coin'] * count($to_users);
        // 获取给1个人的消费值
        $charging_coin_user = $count * $gift['coin'];
        // 1钻石 系统赠送的虚拟币
        $user_coin = $coin_type == 1 ? $user_info['coin'] : $user_info['friend_coin'];

        if ($charging_coin > $user_coin) {
            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        $coin = $charging_coin;

        // 扣除用户金额 2:赠送礼物
        $charging_coin_res = $this->UserModel->deduct_user_coin_new($user_info, $coin, $coin_type, 4);
        if (!$charging_coin_res) {
            $result['msg'] = lang('Insufficient_Balance');
            $result['code'] = 10002;
            return_json_encode($result);
        }

        // 钻石变更记录
        $notes = $to_user_id . ';' . $gift['name'] . "(" . $gift['id'] . ") x" . $count;
        save_coin_log($uid, '-' . $coin, $coin_type, 4, $notes);
        $result['coin'] = $user_info['coin'];
        $result['friend_coin'] = $user_info['friend_coin'];
        if ($coin_type == 1) {
            // 获取用户剩余余额
            $result['coin'] = $user_info['coin'] - $coin;
        } else {
            // 获取用户剩余余额
            $result['friend_coin'] = $user_info['friend_coin'] - $coin;
        }
        //增加送礼物记录
        $gift_log = [
            'user_id'     => $uid,
            'gift_id'     => $gift['id'],
            'gift_name'   => $gift['name'],
            'gift_count'  => $count,
            'gift_coin'   => $charging_coin_user,
            'gift_type'   => $coin_type == 1 ? 1 : 3,
            'create_time' => NOW_TIME,
            'date_y_m_d' => date('Y-m-d'),
            'date_y_m' => date('Y-m'),
            'date_y_w' => date('Y')."-".date('W'),
            'date_y_m_d_h' =>date('Y-m-d')."-".date('H'),
        ];

        // 用户收益
        $income_total = 0;
        $to_user = [];
        $proportion_type = 1; // 1 = 普通礼物 2 = 是幸运礼物

        $lucky_reward_coin = 0;
        $incomeLogs = [];
        $giftLogs = [];
        $result['lucky_reward'] = '';
        $host_income_totals = 0;
        $gift_lucky_log = '';
        if ($voice_id && $gift['is_luck'] == 1) {
            // 幸运礼物
            $lucky_reward = $this->lucky_reward($user_info, $gid, count($to_users), $count, $voice_id);
            if ($lucky_reward['is_lucky_gift'] == 1) {
                $proportion_type = 2;
                $lucky_reward_coin = $lucky_reward['user_money'];
                $host_income_totals = $lucky_reward['lucky_host'];
                $gift_lucky_log = $lucky_reward['gift_lucky_log'];
            }
            $result['lucky_reward'] = $lucky_reward;
        }

        $lucky_log_array = [];
        foreach ($to_users as $v) {
            $to_user_info = get_user_base_info($v, ['luck', 'guild_id']);

            $gift_log_one = $gift_log;
            $gift_log_one['to_user_id'] = $v;
            $income_type = 8;
            // 用户收益
            if ($coin_type == 1) {
                $income_type = $voice_id ? 3 : 9;
            }
            $income_totals = $host_income_totals; // 默认是幸运奖励
            if ($proportion_type == 1) {
                //公会id
                $gift_log_one['guild_id'] = $to_user_info['guild_id']; // 公会id
                if ($config['is_Joining_guild_earns_benefits'] != 1) {
                    $income_totals = host_income_commission($income_type, $charging_coin_user, $v);
                }
                if ($gift_log_one['guild_id']) {
                    $gift_log_one['guild_status'] = 1;
                    if ($config['is_Joining_guild_earns_benefits'] == 1) {
                        $income_totals = host_income_commission($income_type, $charging_coin_user, $v);
                    }
                }
            }
            $even_wheat_income = 0;
            // 获取通话信息
            if ($call_id) {
                // 记录类型 1动态,2短视频,3聊天,4语音,5视频通话
                $gift_log_one['type'] = 5;
            } else if ($voice_id) {
                // 语音直播间
                $wheat_id = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $v, 1);
                if (!$wheat_id && $v == $voice_wheat['user_id']) {
                    $wheat_id = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $v, 3);
                }
                $gift_log_one['voice_profit'] = 0;
                // 直播间id标识符
                $gift_log_one['room_id'] = $voice_wheat['id'];
                // 房间类型
                $gift_log_one['room_type'] = $voice_wheat['type'];
                // 直播间收益人
                $gift_log_one['voice_user_id'] = $voice_wheat['user_id'];
                // 语音房间提成
                $gift['room_divide_info'] = 0;
                // 记录类型 1通话 2直播间
                $gift_log_one['type'] = 4;


                if ($voice_wheat['charm_status'] == 1) {
                    $even_wheat_income = $config['voice_charm_type'] == 1 ? $income_totals : $charging_coin_user;
                    $even = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $v, 1);
                    if ($even) {
                        $this->VoiceModel->add_voice_even_wheat_log_coin($wheat_id['id'], $even_wheat_income);
                    }
                }

                if ($gift['room_divide_info'] > 0 && $income_totals > 0 && $income_totals > $gift['room_divide_info']) {
                    // 直播间收益数
                    $gift_log_one['voice_profit'] = $gift['room_divide_info'];
                    // 增加直播间房主收益 4:赠送礼物
                    $this->UserModel->add_user_earnings($gift_log_one['voice_user_id'], $gift_log_one['voice_profit'], $user_info, 4);
                    // 增加房间收益
                    $this->VoiceModel->add_voice_vote_number($voice_id, $gift_log_one['voice_profit']);
                    // 收到礼物的用户实际得到的收益
                    $income_totals = $income_totals - $gift_log_one['voice_profit'];
                }

                // 增加房间总流水
                //$this->VoiceModel->add_voice_coin_number($voice_id, $charging_coin_user);
                //增加热度
                //$this->VoiceModel->upd_cumulative($voice_id, 'heat', $income_totals, 1);

                if ($wheat_id) {
                    $gift_log_one['voice_log_id'] = $wheat_id['id'];
                }

                db('voice')->where("user_id=" . $voice_id)->setInc('coin_number', $charging_coin_user, 10);
                db('voice')->where("user_id=" . $voice_id)->setInc('heat', $income_totals, 10);
            }

            // 收到礼物的用户实际得到的收益
            $income_total = $income_totals;
            // 增加收到礼物的用户实际得到的收益 2:赠送礼物
            if ($income_total > 0) {
                $this->UserModel->add_user_earnings($v, $income_total, $user_info, 4);
                $notes = $uid . ';' . $gift['name'] . "(" . $gift['id'] . ") x" . $count;
                // 收益变更记录
                //save_income_log($v, $income_total, 1, 4, $notes);
                $incomeLogs[] = [
                    'uid'         => $v,
                    'income'      => $income_total,
                    'income_type' => $coin_type == 2 ? 2 : 1,
                    'type'        => 4,
                    'create_time' => NOW_TIME,
                    'notes'       => $notes
                ];
            }

            $gift_log_one['profit'] = $income_total;
            // 添加送礼物记录
            //$table_id = $this->GiftModel->add_user_gift_log($gift_log);
            $giftLogs[] = $gift_log_one;

            // 增加总消费记录
            $content = $gift['name'] . "(" . $gift['id'] . ") x" . $count;
            // 1消费的钻石类型 2 消费的系统赠送类型
            $coin_type = $coin_type == 1 ? 1 : 2;
            $type_id = array(
                'id'               => $room_id,
                'type'             => 1,
                'is_lucky'         => $proportion_type,
                'guild_lucky_coin' => $proportion_type == 2 && $result['lucky_reward'] && isset($result['lucky_reward']['guild_lucky_coin']) ? intval($result['lucky_reward']['guild_lucky_coin']) : 0
            );

            add_charging_log($uid, $v, 3, $charging_coin_user, 0, $income_totals, $content, $coin_type, $type_id);

            $result['code'] = 1;
            $to_user_info['total_ticket'] = $even_wheat_income;
            $to_user[] = $to_user_info;
            //全频道广播
            if (isset($gift['is_all_notify']) && $gift['is_all_notify'] == 1) {
                $this->push_all_gift_msg($user_info, $to_user_info, $count, $gift['name'], $gift['img']);
            }

            if ($gift_lucky_log) {
                $gift_lucky_log_one = $gift_lucky_log;
                $gift_lucky_log_one['host_name'] = $to_user_info['user_nickname'];
                $gift_lucky_log_one['guild_id'] = $to_user_info['guild_id'];
                $gift_lucky_log_one['host_id'] = $to_user_info['id'];
                $lucky_log_array[] = $gift_lucky_log_one;
            }

            if ($voice_id && $proportion_type == 1) {
                // 房间内礼物 IM 消息
                $this->push_send_gift_msg($user_info, $to_user_info, $count, $gift, $voice_wheat, $even_wheat_income);
            }
        }

        if ($incomeLogs) {
            db('user_income_log')->insertAll($incomeLogs);
        }

        if ($giftLogs) {
            db('user_gift_log')->insertAll($giftLogs);
        }

        if (count($to_user)) {
            $result['data'] = $this->deal_send_voice($uid, $to_user, $count, $gift, $income_total);
        }

        if ($proportion_type == 2) {
            if (count($lucky_log_array)) {
                // 添加幸运礼物记录
                db('gift_lucky_log')->insertAll($lucky_log_array);
            }

            // 发送幸运消息
            if ($voice_id) {
                // 房间内礼物消息
                $this->push_send_gift_msg($user_info, $to_user, $count * count($to_user), $gift, $voice_wheat, 0, $result['lucky_reward']);

                $lucky_reward = $result['lucky_reward'];
                if ($lucky_reward['user_multiple'] >= intval($config['luck_gift_top_float_all_times_limit_set'])) {
                    // 幸运礼物达到多少倍发全局飘屏
                    lucky_send_im($user_info, $lucky_reward['user_money'], $lucky_reward['user_multiple'], $voice_id);
                }

                if ($lucky_reward['user_multiple'] >= intval($config['luck_gift_screen_all_times_limit_set'])) {
                    lucky_send_im($user_info, $lucky_reward['user_money'], $lucky_reward['user_multiple'], $voice_id, 1);
                }
            }
        }

        $result['coin'] = $result['coin'] + $lucky_reward_coin;
        $result['data']['is_lucky_gift'] = $proportion_type;
        return_json_encode($result);
    }

    // 送礼物 动态、短视频
    public function send_gift_giving()
    {
        $result = array('code' => 0, 'msg' => lang('Reward_failed'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 对方id多个用逗号隔开
        $to_user_id = trim(input('param.to_user_id'));
        //类型 1 动态、2 短视频、3 私信
        $type = intval(input('param.type'));
        // 动态、短视频、语音通话、视频通话ID
        $other_id = intval(input('param.other_id'));
        // 礼物id
        $gid = intval(input('param.gid'));
        // 礼物数量
        $count = intval(input('param.count'));

        // 获取配置
        $config = load_cache('config');
        // 本用户信息
        $user_info = check_login_token($uid, $token, ['friend_coin', 'last_login_ip']);
//        $user_identity = get_user_identity($to_user_id);
//
//        if ($user_identity < 2) {
//            $result['msg'] = lang('Gifts_cannot_be_received_without_authentication');
//            return_json_encode($result);
//        }

        if ($count <= 0) {
            $result['msg'] = lang('Gift_quantity_must_greater_than_0');
            return_json_encode($result);
        }

        $gift = load_cache('gift_id', ['id' => $gid]);

        if (!$gift) {
            $result['msg'] = lang('Gift_information_does_not_exist');
            return_json_encode($result);
        }

        // 礼物总价值
        $charging_coin = $count * $gift['coin'];
        $coin_type = $gift['coin_type'];
        if ($coin_type == 1) {
            //心币
            $user_coin = $user_info['coin'];
        } else {
            //友币
            $user_coin = $user_info['friend_coin'];
        }
        if ($charging_coin > $user_coin) {
            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }
        $to_user_info = get_user_base_info($to_user_id, ['guild_id']);

        // 启动事务
        db()->startTrans();
        try {
            $coin = $charging_coin;
            // 扣除用户金额 2:赠送礼物
            $charging_coin_res = $this->UserModel->deduct_user_coin_new($user_info, $coin, $coin_type, 4);

            if ($charging_coin_res) {
                // 钻石变更记录
                $notes = $to_user_id . ';' . $gift['name'] . "(" . $gift['id'] . ") x" . $count;
                save_coin_log($uid, '-' . $coin, $coin_type, 4, $notes);
                // 获取用户剩余余额
                if ($coin_type == 1) {
                    $profit = round($config['heart_gift_proportion'] * $coin, 2);
                } else {
                    $profit = round($config['friend_gift_proportion'] * $coin, 2);
                }
                //增加送礼物记录
                $gift_log = [
                    'user_id'     => $uid,
                    'to_user_id'  => $to_user_id,
                    'gift_id'     => $gift['id'],
                    'gift_name'   => $gift['name'],
                    'gift_count'  => $count,
                    'gift_coin'   => $charging_coin,
                    'gift_total'  => $charging_coin,
                    'profit'      => $profit,
                    'create_time' => NOW_TIME,
                    'other_id'    => $other_id,
                    'type'        => $type,
                    'gift_type'   => $coin_type == 1 ? 1 : 3,
                ];
                //公会id
                $gift_log['guild_id'] = $to_user_info['guild_id']; // 公会id
                if ($gift_log['guild_id']) {
                    $gift_log['guild_status'] = 1;
                }
                $table_id = $this->GiftModel->add_user_gift_log($gift_log);
                // 用户收益
                $this->UserModel->add_user_earnings($to_user_id, $profit, $user_info, 4);
                // 增加总消费记录
                $content = $gift['name'] . "(" . $gift['id'] . ") x" . $count;
                $is_type = 0;
                if ($type == 2) {
                    $is_type = 2;
                } elseif ($type == 3) {
                    $is_type = 4;
                }
                $type_id = array(
                    'id'       => $other_id,
                    'type'     => $is_type,
                    'is_lucky' => 0,
                );

                //增加总消费记录
                add_charging_log($uid, $to_user_id, 3, $charging_coin, $table_id, $profit, $content, '', $type_id);

                //密友
                $friend_gift = [];
                $friend_gift['name'] = $gift['name'];
                $friend_gift['count'] = $count;
                $friend_gift['user_nickname'] = $user_info['user_nickname'];
                add_friendship($uid, $to_user_id, $charging_coin, $friend_gift);

                //增加热度
                if ($type == 1) {
                    db('bzone')->where('id = ' . $other_id)->inc('heart', $coin)->update();
                    $content = $user_info['user_nickname'] . lang('rewarded_you_for_your_dynamic') . $gift['name'] . 'x' . $count;
                    $url = 'bogo://message?type=2&id=' . $other_id;
                    push_sys_msg_user(18, $to_user_id, 1, $content, $url);
                } else if ($type == 2) {
                    db('user_video')->where('id = ' . $other_id)->inc('heart', $coin)->update();
                }
                $result['code'] = 1;
                $result['msg'] = lang('Reward_succeeded');
                $result['data'] = $this->deal_send($uid, $to_user_id, $count, $gift, $profit);
                $user_info = get_user_base_info($uid, ['friend_coin', 'last_login_ip']);
                $result['data']['coin'] = $user_info['coin'];
                $result['data']['friend_coin'] = $user_info['friend_coin'];
                task_reward(8, $uid);
                // 提交事务
                db()->commit();
            } else {
                $result['msg'] = lang('Insufficient_Balance');
                $result['code'] = 10002;
                db()->rollback();
            }

        } catch (\Exception $e) {

            $result['msg'] = $e->getMessage();
            // 回滚事务
            db()->rollback();
        }

        return_json_encode($result);
    }

    // 背包礼物 动态、短视频
    public function send_gift_bag_giving()
    {
        $result = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 对方id
        $to_user_id = trim(input('param.to_user_id'));
        // 类型 1 动态、2 短视频 3 私信
        $type = intval(input('param.type'));
        // 动态、短视频、语音通话、视频通话ID
        $other_id = intval(input('param.other_id'));
        // 礼物id
        $gid = intval(input('param.gid'));
        // 礼物数量
        $count = intval(input('param.count'));


        // 获取配置
        $config = load_cache('config');
        // 本用户信息
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
//        $user_identity = get_user_identity($to_user_id);
//
//        if ($user_identity < 2) {
//            $result['msg'] = lang('Gifts_cannot_be_received_without_authentication');
//            return_json_encode($result);
//        }

        if ($count <= 0) {
            $result['msg'] = lang('Gift_quantity_must_greater_than_0');
            return_json_encode($result);
        }

        $gift = load_cache('gift_id', ['id' => $gid]);

        if (!$gift) {
            $result['msg'] = lang('Gift_information_does_not_exist');
            return_json_encode($result);
        }

        // 获取单个的背包礼物
        $bag = $this->GiftModel->get_user_bag_one($uid, $gid);

        if ($bag['giftnum'] < $count) {
            $result['msg'] = lang('Insufficient_gifts');
            return_json_encode($result);
        }
        // 主播收益数
        $charging_coin = $count * $gift['coin'];
        $to_user_info = get_user_base_info($to_user_id, ['guild_id']);
        // 启动事务
        db()->startTrans();
        try {
            $coin = $charging_coin;
            // 扣除背包礼物数量
            $charging_coin_res = $this->GiftModel->del_user_bag_sum($uid, $gid, $count);

            if ($charging_coin_res) {
                // 获取用户剩余余额
                $profit = round($config['heart_gift_proportion'] * $coin, 2);

                //增加送礼物记录
                $gift_log = [
                    'user_id'     => $uid,
                    'to_user_id'  => $to_user_id,
                    'gift_id'     => $gift['id'],
                    'gift_name'   => $gift['name'],
                    'gift_count'  => $count,
                    'gift_coin'   => $charging_coin,
                    'gift_total'  => $charging_coin,
                    'profit'      => $profit,
                    'create_time' => NOW_TIME,
                    'other_id'    => $other_id,
                    'type'        => $type,
                    'gift_type'   => 2,
                ];
                //公会id
                $gift_log['guild_id'] = $to_user_info['guild_id']; // 公会id
                if ($gift_log['guild_id']) {
                    $gift_log['guild_status'] = 1;
                }
                $table_id = $this->GiftModel->add_user_gift_log($gift_log);
                // 用户收益
                $this->UserModel->add_user_earnings($to_user_id, $profit, $user_info, 4);

                $content = $gift['name'] . "(" . $gift['id'] . ") x" . $count;

                $is_type = 0;
                if ($type == 2) {
                    $is_type = 2;
                } elseif ($type == 3) {
                    $is_type = 4;
                }

                $type_id = array(
                    'id'       => $other_id,
                    'type'     => $is_type,
                    'is_lucky' => 0,
                );
                //增加总消费记录
                add_charging_log($uid, $to_user_id, 3, $charging_coin, $table_id, $profit, $content, '', $type_id);

                //密友
                add_friendship($uid, $to_user_id, $charging_coin);
                $result['code'] = 1;
                $result['data'] = $this->deal_send($uid, $to_user_id, $count, $gift, $profit);
                if ($type == 1) {
                    $content = $user_info['user_nickname'] . lang('rewarded_you_for_your_dynamic') . $gift['name'] . 'x' . $count;
                    $url = 'bogo://message?type=2&id=' . $other_id;
                    push_sys_msg_user(18, $to_user_id, 1, $content, $url);
                }
                task_reward(8, $uid);
                // 提交事务
                db()->commit();
            } else {
                $result['msg'] = lang('Insufficient_Balance');
                $result['code'] = 10002;
                db()->rollback();
            }

        } catch (\Exception $e) {

            $result['msg'] = $e->getMessage();
            // 回滚事务
            db()->rollback();
        }

        return_json_encode($result);
    }

    //礼物消息
    private function push_send_gift_msg($user_info, $to_user_info, $num, $gift, $voice, $income, $lucky_reward = array())
    {
        $config = load_cache('config');

        $broadMsg['type'] = Enum::REGULAR_GIFT; //赠送礼物
        $sender['user_nickname'] = $user_info['user_nickname'];
        $sender['avatar'] = $user_info['avatar'];
        $sender['user_id'] = $user_info['id'];
        $sender['guardian'] = 0;
        $sender['level'] = $user_info['level'];
        $sender['sex'] = $user_info['sex'];
        $sender['has_car'] = 0;
        $broadMsg['voice_id'] = $voice['id']; //房间id
        $broadMsg['sender'] = $sender;

        $user['user_nickname'] = '';
        $user['avatar'] = '';

        $broadMsg['to_user_list'] = [$to_user_info];
        // 如果是二维数组就是多个接收用户，多个的话不用赋值这个参数
        if (!is_two_dimensional_array($to_user_info)) {
            $user['user_nickname'] = $to_user_info ? $to_user_info['user_nickname'] : '';
            $user['avatar'] = $to_user_info ? $to_user_info['avatar'] : '';
        } else {
            $broadMsg['to_user_list'] = $to_user_info;
        }

        $user['focus'] = 0;
        $broadMsg['to_user'] = $user;
        $broadMsg['is_animated'] = 0; //宝石特效
        $broadMsg['animated_url'] = ''; //
        $broadMsg['isTaked'] = ''; //
        $broadMsg['num'] = ''; //
        $broadMsg['is_noble'] = ''; //
        // 是否统计系统赠送的虚拟币
        if ($config['system_virtual_currency'] != 1) {
            $income = $gift['coin_type'] == 1 ? $income : 0;
        }
        $broadMsg['icon'] = $gift['img'];
        $broadMsg['gift_name'] = $gift['name'];
        $broadMsg['svga'] = $gift['svga'];
        $broadMsg['audio_file'] = $gift['audio_file'];
        $broadMsg['gift_num'] = $num;
        $broadMsg['total_ticket'] = $income;
        $broadMsg['prop_id'] = $gift['id'];
        $broadMsg['to_user_id'] = !is_two_dimensional_array($to_user_info) ? $to_user_info['id'] : '';
        $broadMsg['text'] = '送出' . $num . lang('individual') . $gift['name'];
        $broadMsg['is_plus'] = 0;
        $broadMsg['user_nickname'] = $user_info['user_nickname'];
        $broadMsg['to_user_nickname'] = !is_two_dimensional_array($to_user_info) ? $to_user_info['user_nickname'] : '';
        $broadMsg['gift_type'] = $gift['type'];
        $broadMsg['is_luck'] = $gift['is_luck'];

        // 幸运礼物奖励信息
        $broadMsg['lucky_reward'] = array(
            'is_winning'    => $lucky_reward ? $lucky_reward['is_winning'] : 0,
            'user_multiple' => $lucky_reward ? $lucky_reward['user_multiple'] : 0,
            'user_money'    => $lucky_reward ? $lucky_reward['user_money'] : 0,
        );

        #构造rest API请求包
        $msg_content = array();
        //创建$msg_content 所需元素
        $msg_content_elem = array(
            'MsgType'    => 'TIMCustomElem',       //定义类型为普通文本型
            'MsgContent' => array(
                'Data' => json_encode($broadMsg)    //转为JSON字符串
            )
        );

        //将创建的元素$msg_content_elem, 加入array $msg_content
        array_push($msg_content, $msg_content_elem);

        if (config('app.gift_im_send_type') == 1) {
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $api = createTimAPI();

            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);
            bogokjLogPrint("push_send_gift_msg", $ret);
            return $ret;
        } else {
            // 获取 Redis 实例
            $redis = Cache::store('redis')->handler();
            $redis->setOption(Redis::OPT_PREFIX, config('cache.prefix'));
            // 将消息插入到队列的左侧（先进先出）
            $redis->lPush('gift', json_encode(['group_id' => $voice['group_id'], 'msg_content' => $msg_content]));

            return true;
        }

    }


    //发送全局礼物消息
    private function push_all_gift_msg($send_user_info, $to_user_info, $count, $gift_name, $gift_icon)
    {
        $config = load_cache('config');
        $broadMsg['type'] = Enum::BROADCAST;
        $sender['id'] = $send_user_info['id'];
        $sender['user_nickname'] = $send_user_info['user_nickname'];
        $sender['avatar'] = $send_user_info['avatar'];
        $sender['level'] = get_level($send_user_info['id']);

        $broadMsg['channel'] = 'all'; //通话频道
        $broadMsg['sender'] = $sender;
        $broadMsg['send_gift_info']['send_user_nickname'] = $send_user_info['user_nickname'];
        $broadMsg['send_gift_info']['send_user_id'] = $send_user_info['id'];
        $broadMsg['send_gift_info']['send_user_avatar'] = $send_user_info['avatar'];
        $broadMsg['send_gift_info']['send_to_user_id'] = $to_user_info['id'];
        $broadMsg['send_gift_info']['send_to_user_nickname'] = $to_user_info['user_nickname'];
        $broadMsg['send_gift_info']['send_to_user_avatar'] = $to_user_info['avatar'];
        $broadMsg['send_gift_info']['gift_icon'] = $gift_icon;

        $msg_str = $send_user_info['user_nickname'] . " " . lang('give') . ' ' . $count . ' ' . lang('individual') . ' ' . $gift_name . ' ' . lang('to') . ' ' . $to_user_info['user_nickname'];
        $broadMsg['send_gift_info']['send_msg'] = $msg_str;
        #构造rest API请求包
        $msg_content = array();
        //创建$msg_content 所需元素
        $msg_content_elem = array(
            'MsgType'    => 'TIMCustomElem',       //定义类型为普通文本型
            'MsgContent' => array(
                'Data' => json_encode($broadMsg)    //转为JSON字符串
            )
        );

        //将创建的元素$msg_content_elem, 加入array $msg_content
        array_push($msg_content, $msg_content_elem);

        if (config('app.gift_im_send_type') == 1) {
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

            $api = createTimAPI();
            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $config['acquire_group_id'], $msg_content);
            return $ret;
        } else {
            // 获取 Redis 实例
            $redis = Cache::store('redis')->handler();
            $redis->setOption(Redis::OPT_PREFIX, config('cache.prefix'));
            // 将消息插入到队列的左侧（先进先出）
            $redis->lPush('gift', json_encode(['group_id' => $config['acquire_group_id'], 'msg_content' => $msg_content]));

            return true;
        }
    }

    /**
     * 幸运礼物 处理
     * */
    public function lucky_reward($user_info, $gift_id, $to_uses_sum, $num, $voice_id)
    {
        $is_lucky_reward = 1;
        $user_id = $user_info['id'];
        // 限制用户
//        if ($user_id == 167630) {
//            $is_lucky_reward = 1;
//        }
        $lucky_reward = array(
            'is_lucky_gift'  => 0,
            'is_winning'     => 0,
            'user_multiple'  => 0,
            'user_money'     => 0,
            'MsgResult'      => '',
            'gift_lucky_log' => array()
        );
        if ($is_lucky_reward == 1) {
            $lucky_reward = load_cache("lucky_reward", array('user_id' => $user_id, 'gift_id' => $gift_id, 'to_uses_sum' => $to_uses_sum, 'num' => $num, 'user_name' => $user_info['user_nickname'], 'voice_id' => $voice_id));
            $lucky_reward['num'] = $num;
            //    createRotatingLogger('lucky_reward')->log(LogLevel::INFO, " lucky_reward=====" . json_encode($lucky_reward));
        }
        return $lucky_reward;
    }

}
