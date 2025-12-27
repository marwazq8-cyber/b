<?php
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

// 应用公共文件
use app\api\model\UserModel;
use app\api\model\VoiceModel;
use app\common\Enum;
use Qiniu\Auth;
use Qiniu\Rtc\AppClient;
use Qiniu\Storage\UploadManager;
use think\Config;
use think\Db;
use think\helper\Time;

// 包含公共方法
include_once ROOT_PATH . 'application/common/redis_utils.php';

function Consumer_classification()
{
    $Consumer_classification = [];

    for ($i = 0; $i <= 40; $i++) {
        switch ($i) {
            case 0:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Other_consumption'),
                    'desc' => '其他',
                );
                break;
            case 1:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Video_consumption'),
                    'desc' => '语音通话消费',
                );
                break;
            case 2:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Private_license_consumption'),
                    'desc' => '视频通话消费',
                );
                break;
            case 3:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Gift_consumption'),
                    'desc' => '赠送礼物',
                );
                break;
            case 4:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('One_to_one_video_consumption'),
                    'desc' => '通话计时消费',
                );
                break;
            case 5:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Private_message_payment'),
                    'desc' => '私信消息付费',
                );
                break;
            case 6:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('购买贵族消费'),
                    'desc' => '购买贵族消费',
                );
                break;
            case 7:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Play_order_consumption'),
                    'desc' => '陪玩订单消费',
                );
                break;
            case 15:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('购买气泡消费'),
                    'desc' => '购买气泡消费',
                );
                break;
            case 22:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Open_treasure_box_consumption'),
                    'desc' => '开宝箱',
                );
                break;
            case 23:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('赠送背包礼物'),
                    'desc' => '背包礼物消费',
                );
                break;
//            case 24:
//                $Consumer_classification[] = array(
//                    'id' => $i,
//                    'title' => lang('Gongge_game'),
//                    'desc' => '宫格游戏抽奖',
//                );
//                break;
            case 25:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('Tree_watering_game'),
                    'desc' => '浇树游戏',
                );
                break;
            case 27:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('购买VIP'),
                    'desc' => '购买VIP',
                );
                break;
            case 28:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('ADMIN_GAMETRIPARTITE_DEFAULT'),
                    'desc' => '三方游戏',
                );
                break;
            case 31:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('装扮'),
                    'desc' => '装扮',
                );
                break;
            case 32:
                $Consumer_classification[] = array(
                    'id' => $i,
                    'title' => lang('用户注册时赠送'),
                    'desc' => '注册奖励',
                );
                break;
            default:
        }
    }
    return $Consumer_classification;
}

//获取input int参数
function get_input_param_int($param_name)
{
    return intval(input('param.' . $param_name));
}

//获取input str参数
function get_input_param_str($param_name)
{
    return trim(input('param.' . $param_name));
}

// 用户是否是vip $time到期时间  1vip 0不是vip
function get_is_vip($time)
{

    $data = intval($time) - NOW_TIME > 0 ? 1 : 0;

    return $data;
}

/*
*  获取勋章名称
*  id 勋章id time到期时间
*/
function medal_one($uid, $id, $time = '')
{
    $data = array(
        'medal_icon' => '',
        'medal_name' => '',
        'medal_time' => '',
    );
    $medal = load_cache("medal");
    if ($time and $id) {
        $type = 0;
        if ($time >= NOW_TIME) {
            foreach ($medal as $k => $v) {
                if ($v['id'] == $id) {
                    $type = 1;
                    $data['medal_icon'] = $v['icon'];
                    $data['medal_name'] = $v['name'];
                    $data['medal_time'] = $time;
                }
            }
        }
        if ($uid && $type == 0) {
            db('user')->where("id=" . $uid)->update(array('medal_id' => 0, 'medal_end_time' => 0));
        }
    }

    return $data;
    exit;

}

/**
 * @dw     邀请扣量
 * @param  $user_id 用户id
 * @return 0不扣量 1扣量
 * */
function get_bucket_invite($user_id, $type = 0)
{
    $user_info = get_user_base_info($user_id, ['create_time', 'invite_buckle_probability', 'invite_buckle_recharge_probability']);
    if (!$user_info) {
        return 0;
    }

    //邀请绑定扣单概率规则
    if ($type == 0) {
        $probability = $user_info['invite_buckle_probability'];

        if ($user_info['invite_buckle_probability'] == 0) {

            $day = (NOW_TIME - $user_info['create_time']) / (60 * 60 * 24);
            $day = intval($day);
            $rule = db('buckle_invite_rule')->where('upper_limit', '<=', $day)->where('lower_limit', '>=', $day)->order('upper_limit desc')->limit(1)->find();

            if (!$rule) {
                return 0;
            }
            $probability = $rule['probability'];
        }
    } else {
        //邀请充值扣单概率规则
        $probability = $user_info['invite_buckle_recharge_probability'];

        if ($user_info['invite_buckle_recharge_probability'] == 0) {

            $day = (NOW_TIME - $user_info['create_time']) / (60 * 60 * 24);
            $day = intval($day);
            $rule = db('buckle_invite_recharge_rule')->where('upper_limit', '<=', $day)->where('lower_limit', '>=', $day)->order('upper_limit desc')->limit(1)->find();

            if (!$rule) {
                return 0;
            }
            $probability = $rule['probability'];
        }
    }

    $arr = array(
        array('id' => 1, 'type' => '扣量', 'v' => $probability),
        array('id' => 0, 'type' => '不扣量', 'v' => 100 - $probability),
    );

    function get_rand($proArr)
    {
        $result = array();
        foreach ($proArr as $key => $val) {
            $arr[$key] = $val['v'];
        }
        // 概率数组的总概率
        $proSum = array_sum($arr);
        asort($arr);
        // 概率数组循环
        foreach ($arr as $k => $v) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $result = $proArr[$k];
                break;
            } else {
                $proSum -= $v;
            }
        }
        return $result;
    }

    return intval(get_rand($arr)['id']);

}

//录入设备信息
function device_info($os = '', $sdk = '', $app = '', $brand = '', $model = '', $uid = 0)
{
    $user_info = db('device_info')->where("uid='$uid'")->find();
    $data = array(
        'os' => $os,
        'sdk_version' => $sdk,
        'app_version' => $app,
        'brand' => $brand,
        'model' => $model,
        'addtime' => time(),
    );
    if ($user_info) {
        $device_info = db('device_info')->where("id=" . $user_info['id'])->update($data);
    } else {
        $data['uid'] = $uid;
        $device_info = db('device_info')->insert($data);
    }

}

// 登录ip记录 type 1 后台登录 2 用户登录
function login_ip_log($uid, $user_login, $name, $type = '1')
{
    $close_ip = array(
        'uid' => $uid,
        'user_login' => $user_login,
        'name' => $name,
        'addtime' => NOW_TIME,
        'type' => $type
    );
    $close_ip['ip'] = $type == 1 ? get_client_ip(0, true) : request()->ip(0, false);
    db('login_log')->insert($close_ip);
}

//测试自定义打招呼信息
function crontab_do_auto_talking_custom_tpl()
{

    $config = load_cache('config');
    if ($config['is_open_auth_see_hi'] != 1) {
        return;
    }
    $lock = $GLOBALS['redis']->get('bogokj:auto_see_hi_lock');
    if ($lock) {
        echo '未到时间：' . ($config['auto_say_hi_interval_time'] - (NOW_TIME - $lock));
        return;
    }
    echo '进入执行任务';

    $GLOBALS['redis']->set('bogokj:auto_see_hi_lock', NOW_TIME, $config['auto_say_hi_interval_time']);

    require_once(DOCUMENT_ROOT . '/system/im_common.php');

    //查询在线的男性用户  -随机获取10个满足条件的用户
    $online_user = db('user')->where('is_online', '=', 1)->where('sex', '=', 1)
        ->where('coin', '<=', $config['auto_say_hi_coin_limit'])
        ->orderRaw('rand()')
        ->limit(10)
        ->select();

    $day_end_time = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
    //开始时间戳
    $day_start = mktime(0, 0, 0, date("m", NOW_TIME), date("d", NOW_TIME), date("Y", NOW_TIME));

    foreach ($online_user as $v) {
        //查询今日对该用户的打招呼次数
        $day_count = db('auto_msg_record')->where('to_user_id', '=', $v['id'])->where('create_time', '>=', $day_start)->count();
        if ($day_count >= $config['auto_say_hi_day_count']) {
            continue;
        }

        //随机查询一个主播
        $emcee = db('user')->where('is_online', '=', 1)->where('is_auth', '=', 1)->orderRaw('rand()')->find();

        //查询今日该主播对该用户的打招呼次数
        $emcee_day_count = db('auto_msg_record')->where('to_user_id', '=', $v['id'])->where('create_time', '>=', $day_start)
            ->where('user_id', '=', $emcee['id'])->count();
        if ($emcee_day_count >= $config['auto_say_hi_day_emcee_count']) {
            continue;
        }

        if ($emcee) {
            $auto_msg_record = db('custom_auto_msg')->where('user_id', '=', $emcee['id'])->where('status', '=', 1)->orderRaw('rand()')->find();
            if ($auto_msg_record) {
                if (!empty($auto_msg_record['msg'])) {
                    $record = [
                        'user_id' => $emcee['id'],
                        'to_user_id' => $online_user['id'],
                        'msg_id' => $auto_msg_record['id'],
                        'create_time' => NOW_TIME,
                    ];
                    db('auto_msg_record')->insert($record);
                    send_c2c_text_msg($emcee['id'], $v['id'], $auto_msg_record['msg']);

                    echo '成功打招呼' . $online_user['id'];
                }
            }
        }
    }
}

//对用户自动打招呼，自动发视频
function crontab_do_auto_talking()
{
    require_once(DOCUMENT_ROOT . '/system/im_common.php');

    //->where('is_open_auto_see_hi', '=', 1)
    $online_user = db('user')->where('coin', '=', 0)->where('is_online', '=', 1)->where('sex', '=', 1)->select();

    $auto_see_hi_msg_list = db('auto_talking_skill')->select();

    if (count($auto_see_hi_msg_list) > 0) {
        foreach ($online_user as $v) {

            $emcee = db('user')->where('is_online', '=', 1)->where('is_auth', '=', 1)->orderRaw('rand()')->find();
            if ($emcee) {
                $msg = $auto_see_hi_msg_list[rand(0, count($auto_see_hi_msg_list) - 1)]['content'];
                if (!empty($msg)) {
                    send_c2c_text_msg($emcee['id'], $v['id'], $msg);
                }
            }
        }
    }
}

//获取最大的ID
function get_max_user_id($mobile, $field = 'mobile')
{
    $exits = db('mb_user')->where(array($field => $mobile))->find();
    if ($exits) {
        return $exits['id'];
    }

    //注册
    $id = db('mb_user')->insertGetId(array($field => $mobile));
    if (db('user')->find($id)) {
        $id = db('mb_user')->insertGetId(array($field => $mobile));
    }

    return $id;
}

//获取登录token
function get_login_token($id)
{
    $token = md5($id . NOW_TIME . '3DW123@#$$$$@@');
    return $token;
}

//填写代理渠道
function reg_full_agent_code($user_id, $agent_code)
{
    $user_info = db('user')->field('link_id')->find($user_id);
    if (empty($user_info['link_id']) && !empty($agent_code)) {
        db('user')->where('id', '=', $user_id)->setField('link_id', $agent_code);
    }
}

//获取是否关注
function get_attention($uid, $to_user_id)
{
    $is_attention = db('user_attention')->where("uid=$uid")->where('attention_uid', '=', $to_user_id)->find();
    return $is_attention ? 1 : 0;
}

//获取是否拉黑
function get_is_black($uid, $to_user_id)
{
    $is_black = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $to_user_id)->find();
    return $is_black ? 1 : 0;
}

//根据条件获取主播列表 x
function user_info_complete($list)
{

    $config = load_cache('config');
    foreach ($list as &$v) {

        $level = get_level($v['id']);
        $v['level'] = $level;

        //分钟扣费金额
        $v['charging_coin'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {

            if (isset($v['custom_video_charging_coin']) && $level >= $config['custom_video_money_level'] && $v['custom_video_charging_coin'] > 0) {
                $v['charging_coin'] = $v['custom_video_charging_coin'];
            }
        }

        if (isset($v['custom_video_charging_coin'])) {
            unset($v['custom_video_charging_coin']);
        }

        //认证信息
        $auth_info = db('auth_form_record')->field('height')->where('user_id', '=', $v['id'])->find();

        if ($auth_info) {
            $v['height'] = $auth_info['height'] . 'CM';
        }

        $v['vip_price'] = $v['charging_coin'] / 2;
    }

    return $list;
}

//注册邀请业务处理
function reg_invite_service($uid, $invite_code)
{
    $invite_data['user_id'] = 0;
    $invite_data['invite_code'] = '';

    if (!empty($invite_code)) {

        $invite_code = db('invite_code')->where('invite_code', '=', $invite_code)->find();

        if ($invite_code && $invite_code['user_id'] != $uid) {
            $invite_data['user_id'] = $invite_code['user_id'];
            $invite_data['invite_code'] = $invite_code['invite_code'];
            $invite_data['invite_user_id'] = $uid;
            $invite_data['create_time'] = NOW_TIME;
            //添加邀请奖励
            //task_reward(4,$invite_code['user_id']);
        } else {
            return 0;
        }
    }

    //添加邀请记录
    return db('invite_record')->insert($invite_data);

}


//随机获取一个空闲主播
function get_rand_emcee($uid, $max_count = 5, $count = 0)
{
    if ($max_count == $count) {
        return 0;
    }
    $monitor = db('user')
        ->where('is_open_do_not_disturb', 'neq', 1)
        ->where('is_auth', '=', 1)
        ->where('is_online', '=', 1)
        ->where('id', '<>', $uid)
        ->limit(1)
        ->orderRaw('rand()')
        ->find();
    if (!$monitor) {
        return 0;
    }
    $is_call = db('video_call_record')->where('anchor_id', '=', $monitor['id'])->find();
    if ($is_call) {
        $count++;
        get_rand_emcee($uid, $max_count, $count);
    } else {
        return $monitor['id'];
    }
}


/**
 * @dw主播收益提成比例
 * @param type 消费类型
 * @param coin 消费的金额
 * @param uid  主播id
 * @param type=1 一对一购买视频分成比例  host_bay_video_proportion
 * @param type =2 购买私照分成比例  host_bay_phone_proportion
 * @param type =3 聊天室赠送礼物分成比例  host_bay_gift_proportion
 * @param type=4 语聊系统海外版通话 host_one_video_proportion
 * @param type=5私信消息分成比例  host_direct_messages
 * @param type=8系统赠送币送礼物，所有收礼物的人通用  friend_gift_proportion
 * @param type=9充值币赠送的礼物，所有收礼物的人通用，包含背包礼物  heart_gift_proportion
 * @return 返回的是主播的收益
 */
function host_income_commission($type, $coin = 0, $uid = 0)
{

    switch ($type) {
        case 1:
            $filed = 'host_bay_video_proportion';
            break;
        case 2:
            $filed = 'host_bay_phone_proportion';
            break;
        case 3:
            $filed = 'host_bay_gift_proportion';
            break;
        case 4:
            $filed = 'host_one_video_proportion';
            break;
        case 5:
            $filed = 'host_direct_messages';
            break;
        case 6:
            $filed = 'host_guardian_proportion';
            break;
        case 7:
            $filed = 'host_turntable_ratio';
            break;
        case 8:
            $filed = 'friend_gift_proportion';
            break;
        case 9:
            $filed = 'heart_gift_proportion';
            break;
        default:
            $filed = '';
    }

    if (empty($filed)) {
        return 0;
    }
    $ratio = 0;

    $config = load_cache('config');
    if ($config && isset($config[$filed]) && $config[$filed] != 0) {
        $ratio = $config[$filed];
    }

    $invite_coin = $ratio ? floor($coin * $ratio * 100) / 100 : 0;

    return $invite_coin;
}

/*
* 获取语音直播间收益
* $user_id 用户id   $voice_uid 房间用户id
*/
function get_voice_earnings($user_id, $voice_uid)
{
    $config = load_cache('config');
    $voice_info = db('voice')->where('user_id = ' . $voice_uid)->find();
    //本房间的上麦用户是否清零过
    $reset = db('voice_gift_reset')->where("user_id=" . $user_id . " and voice_user_id=" . $voice_uid)->order("addtime desc")->find();

    $where = "to_user_id=" . $user_id . " and voice_user_id=" . $voice_uid;
    //是否统计系统赠送的虚拟币
    $where .= $config['system_virtual_currency'] != 1 ? " and gift_type !=3" : "";

    if ($config['voice_quantity_cycle'] == 1) {   //本周时间
        $sdefaultDate = date("Y-m-d");
        $w = date('w', strtotime(date("Y-m-d")));

        $start = strtotime(date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - 1 : 6) . ' days')));
        $start = $reset && $reset['addtime'] > $start ? $reset['addtime'] : $start;
        $where .= " and create_time >=" . $start;
    } elseif ($config['voice_quantity_cycle'] == 2) {    //本月时间
        $start = strtotime(date('Y-m-01', time()));
        $start = $reset && $reset['addtime'] > $start ? $reset['addtime'] : $start;
        $where .= " and create_time >=" . $start;
    } elseif ($config['voice_quantity_cycle'] == '0') {   //当日
        $start = strtotime(date('Y-m-d', time()));
        $start = $reset && $reset['addtime'] > $start ? $reset['addtime'] : $start;
        $where .= " and create_time >=" . $start;
    } else {
        if ($reset && $reset['addtime']) {
            $where .= " and create_time >=" . $reset['addtime'];
        }
    }
    if ($voice_info['charm_status'] == 0) {
        $gift_earnings = 0;
    } else {
        if ($config['voice_charm_type'] == 1) {
            $field = 'profit';
        } else {
            $field = 'gift_coin';
        }
        $gift_earnings = db('user_gift_log')->where($where)->sum($field);
    }
    //dump($gift_earnings);die();profit
    return $gift_earnings;
}

/*
*公会收益记录
*uid 主播id  table_id记录表id type 1礼物表2通话3私聊   coin主播总收益(包括公会收益) host_coin(主播实际收益)
*/
function add_guild_log($uid, $table_id, $type, $coin, $host_coin, $log_id)
{
    //获取是否有公会提成
    $guild = db('guild')->alias('g')->field("g.commission,g.type,g.id")->join('guild_join u', 'u.guild_id=g.id')->where("u.user_id=" . $uid . " and u.status=1")->find();

    $insert_id = 0;
    if ($guild) {
        $guild_coin = round($guild['commission'] * $coin, 2);//公会提成
        $data = array(
            'user_id' => $uid,
            'table_log' => $table_id,
            'type' => $type,
            'guild_id' => $guild['id'],
            'host_earnings' => $host_coin,
            'guild_earnings' => $guild_coin,
            'guild_commission' => $guild['commission'],
            'guild_type' => $guild['type'],
            'addtime' => NOW_TIME,
            'consume_log' => $log_id,
        );
        $insert_id = db('guild_log')->insertGetId($data);
        if ($insert_id) {
            db('guild')->where(['id' => $guild['id']])->inc('earnings', $guild_coin)->inc('total_earnings', $guild_coin)->update();
        }
    }

    return $insert_id;
}

//公会提成
function sel_guild_log($uid, $invite_coin)
{
    $data = array(
        'invite_coin' => 0,
        'guild_id' => 0,
        'guild_uid' => 0,
    );
    //获取是否有公会提成
    $guild = db('guild')->alias('g')->field("g.commission,g.user_id,g.id")->join('guild_join u', 'u.guild_id=g.id')->where("u.user_id=" . $uid . " and u.status=1")->find();
    if ($guild) {
        $user = Db::name("user")->where("id=" . intval($guild['user_id']))->find();
        if ($user) {
            if ($guild['type'] = 2) {
                $data['invite_coin'] = round($guild['commission'] * $invite_coin, 2);//公会提成
            }
            $data['guild_id'] = $guild['id'];
            $data['guild_uid'] = $guild['user_id'];
        }
    }
    return $data;
}

function sctonum($num, $double = 0)
{
    if (false !== stripos($num, "e")) {
        $a = explode("e", strtolower($num));
        return bcmul($a[0], bcpow(10, $a[1], $double), $double);

    } else {
        return $num;
    }
}

/**
 *   添加消费总记录
 *   user_id 消费用户id  to_user_id收益人id type消费类型
 *   coin 消费金额  table_id对应的消费记录表id profit 用户收益 content消费说明 $coin_type消费金额类型1钻石2系统赠送的金币
 *  $type_id : $voice_id语音房间id $video_id
 * @param        $user_id
 * @param        $to_user_id
 * @param        $type
 * @param        $coin
 * @param        $table_id
 * @param        $profit
 * @param string $content
 * @param string $coin_type
 * @param array  $type_id 1 = 语音房间id 2=视频id 3=动态id 4= 私信
 * @return int|string
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function add_charging_log($user_id, $to_user_id, $type, $coin, $table_id, $profit, $content = '', $coin_type = "", $type_id = array())
{
    $data = [
        'user_id' => $user_id,
        'to_user_id' => $to_user_id,
        'coin' => $type == 23 ? 0 : $coin,
        'table_id' => $table_id,
        'type' => $type,
        'create_time' => NOW_TIME,
        'profit' => $profit,
        'status' => $type == 7 ? 0 : 1
    ];
    if ($coin_type) {
        $data['coin_type'] = intval($coin_type);
    }
    $is_lucky = 0;
    $guild_lucky_coin = 0;
    if ($type_id) {
        // 分类id(classification): 1 = 语音房间id 2=视频id 3=动态id 4= 私信
        $data['classification_id'] = $type_id['id'];
        $data['classification'] = $type_id['type'];
        $is_lucky = $type_id['is_lucky'] == 2 ? 1 : 0;
        if ($is_lucky == 1) {
            $guild_lucky_coin = $type_id['guild_lucky_coin'];
        }
    }
    $config = load_cache('config');
    // 开关 开启语音厅 开启陪玩约单 开启私聊礼物 开启私聊消息 开启视频通话 开启公语音通话 开启短视频礼物
    $switch = array(
        'guild_voice_open' => intval($config['guild_voice_open']),
        'guild_player_open' => intval($config['guild_player_open']),
        'guild_chat_gift_open' => intval($config['guild_chat_gift_open']),
        'guild_chat_news_open' => intval($config['guild_chat_news_open']),
        'guild_video_call_open' => intval($config['guild_video_call_open']),
        'guild_audio_call_open' => intval($config['guild_audio_call_open']),
        'guild_video_gift_open' => intval($config['guild_video_gift_open'])
    );

    $guild_type_open = 0; // 是否开启了对应类型的公会收益
    $agent_open = 1; // 是否有邀请收益记录
    $vip_level_open = 1;// 是否vip加速

    switch ($type) {
        case 1:
            $data['content'] = lang('voice_call');
            $guild_type_open = $switch['guild_audio_call_open'] ? 1 : 0;
            break;
        case 2:
            $data['content'] = lang('Video_call');
            $guild_type_open = $switch['guild_video_call_open'] ? 1 : 0;
            break;
        case 3:
            $data['content'] = lang('ADMIN_SEND') . ' ' . $content;
            if ($type_id) {
                if ($type_id['type'] == 1) {
                    $guild_type_open = $switch['guild_voice_open'] ? 1 : 0;
                    // 分类id(classification): 1 = 语音房间id 2=视频id 3=动态id 4= 私信
                } elseif ($type_id['type'] == 2) {
                    $guild_type_open = $switch['guild_video_gift_open'] ? 1 : 0;
                } elseif ($type_id['type'] == 4) {
                    $guild_type_open = $switch['guild_chat_gift_open'] ? 1 : 0;
                }
            }
            break;
        case 4:
            $data['content'] = lang('Call_consumption');
            break;
        case 5:
            $data['content'] = lang('Private_letter_payment');
            $guild_type_open = $switch['guild_chat_news_open'] ? 1 : 0;
            break;
        case 6:
            $data['content'] = lang('ADMIN_NOBLE');
            break;
        case 7:
            $data['content'] = lang('Play_with_others_place_order');
            $guild_type_open = $switch['guild_player_open'] ? 1 : 0;
            break;
        /*case 8:
           $data['content'] = '购买勋章消费'; break;
        case 9:
           $data['content'] = '砸蛋消费';     break;
        case 11:
           $data['content'] = '购买靓号消费'; break;
        case 12:
           $data['content'] = '加速匹配消费'; break;
        case 13:
           $data['content'] = '购买座驾消费'; break;
        case 14:
           $data['content'] = '购买头饰消费'; break;*/
        case 15:
            $data['content'] = '购买气泡消费';
            break;
        case 16:
            $data['content'] = '购买扭蛋券消费';
            break;
        case 22:
            // 开宝箱
            $data['content'] = $content;
            break;
        case 23:
            //背包礼物消费
            $data['content'] = $content;
            if ($type_id) {
                if ($type_id['type'] == 1) {
                    $guild_type_open = $switch['guild_voice_open'] ? 1 : 0;
                    // 分类id(classification): 1 = 语音房间id 2=视频id 3=动态id 4= 私信
                } elseif ($type_id['type'] == 2) {
                    $guild_type_open = $switch['guild_video_gift_open'] ? 1 : 0;
                } elseif ($type_id['type'] == 4) {
                    $guild_type_open = $switch['guild_chat_gift_open'] ? 1 : 0;
                }
            }
            break;
        case 24:
            // 宫格游戏抽奖
            $data['content'] = $content;
            break;
        case 25:
            // 浇树游戏
            $data['content'] = $content;
            break;
        case 27:
            // 购买VIP
            $data['content'] = 'VIP';
            break;
        case 28:
            // 三方游戏
            $guild_type_open = 0;
            $agent_open = 0;
            $vip_level_open = 0;
            $data['content'] = $content;
            break;
        case 31:
            // 装扮
            $guild_type_open = 0;
            $agent_open = 0;
            $vip_level_open = 0;
            $data['content'] = $content;
            break;
        case 0:
        default:
            $data['content'] = $content;
            break;
    }
    $guild_info = array();
    if ($guild_type_open) {
        //是否开启了公会收益类型 --- 加入工会
        $guild_info = db('guild')
            ->alias('g')
            ->join('guild_join j', 'g.id=j.guild_id', 'left')
            ->where('(j.user_id = ' . $to_user_id . ' and j.status = 1) or g.user_id=' . $to_user_id)
            ->field('g.*')
            ->find();
        if ($guild_info) {
            //工会收益数
            $income_total = $profit;
            if ($is_lucky == 1) {
                $income = $guild_lucky_coin;
            } else {
                $income = floor($guild_info['commission'] * $coin);
                if ($guild_info['type'] == 2) {
                    // 主播收益扣除公会收益
                    if ($profit - $income > 0) {
                        $income_total = $profit - $income;
                    } else {
                        $income = 0;
                    }
                }
            }

            $data['guild_uid'] = $guild_info['user_id'];
            $data['guild_earnings'] = $income;
            $data['guild_commission'] = $guild_info['commission'];
            $data['guild_type'] = $guild_info['type'];
            $data['profit'] = $income_total;
        }
    }
    if ($agent_open && $is_lucky != 1) {
        // 渠道消费记录
        $to_user_info = get_user_base_info($user_id, ['link_id']);
        if ($to_user_info && $to_user_info['link_id']) {
            $agent = db('agent')->where("id=" . intval($to_user_info['link_id']))->find();
            if ($agent) {
                $data['agent_id'] = $to_user_info['link_id'];
                if ($agent['agent_level'] == 1) {
                    $data['agent_company'] = $to_user_info['link_id'];
                } elseif ($agent['agent_level'] == 2) {
                    $data['agent_company'] = $agent['agent_company'];
                    $data['agent_staff'] = $to_user_info['link_id'];
                } else {
                    $data['agent_company'] = $agent['agent_company'];
                    $data['agent_staff'] = $agent['agent_staff'];
                }
            }
        }
    }
    if ($vip_level_open) {
        // vip 等级加速
        $level_acceleration = floatval(get_user_vip_authority($user_id, 'level_acceleration'));
        if ($level_acceleration > 0) {
            $data['vip_level_coin'] = intval($coin * $level_acceleration);
        }
        if ($to_user_id) {
            // vip 等级加速
            $level_acceleration = floatval(get_user_vip_authority($to_user_id, 'level_acceleration'));
            if ($level_acceleration > 0) {
                $data['vip_level_profit'] = intval($profit * $level_acceleration);
            }
        }
    }

    //增加消费记录
    $insert_id = db('user_consume_log')->insertGetId($data);
    if ($insert_id) {
        db('user')->where('id', '=', $user_id)->setInc('consumption_total', $coin);
        db('user')->where('id', '=', $to_user_id)->setInc('charm_values_total', $coin);
        //邀请收益分成
        //invite_back_now($profit, $to_user_id, $insert_id);
        if ($type == 3) {
            request_invite_record($to_user_id, 3, $profit, $insert_id);
        }
        // 公会收益
        if ($guild_type_open && $guild_info) {
            if ($data['guild_earnings'] && $guild_info['user_id'] && $data['status'] == 1) {
                // 用户收益
                $UserModel = new UserModel();
                $to_user_info = db('user')->where("id=" . $to_user_id)->find();
                $UserModel->add_user_earnings($guild_info['user_id'], $income, $to_user_info, 14, 1);
                //公会收益记录
                add_guild_income($to_user_id, 1, $table_id, $coin, $income_total, $insert_id, $income, $guild_info['id'], $guild_info['commission']);
            }
        }
    }

    return $insert_id;
}

/*
 * 删除消费记录
 * */
function del_user_consume_log($uid, $type, $table_id)
{
    $where = "user_id = $uid and type = $type and table_id = $table_id";
    $res = db('user_consume_log')->where($where)->delete();
    return $res;
}

/*
* 添加用户变动金额记录表
*  uid用户id coin 金额 type 1消费的金额2收益金额 genre 1增加 2减少
*  ip ip地址  control 控制器  operator 操作账号id content 备注
 * buy_type 消费类型 money 人名币金额 coin_type 1心币 友币
*/
function upd_user_coin_log($uid, $coin, $money, $buy_type, $type, $genre, $ip, $operator, $control = '')
{
    $data = [
        'uid' => $uid,
        //'touid'   => $touid,
        'coin' => $coin,
        'type' => $type,
        'genre' => $genre,
        'ip' => $ip,
        'operator' => $operator,
        'buy_type' => $buy_type,
        'money' => $money,
        'addtime' => NOW_TIME,
    ];
    switch ($buy_type) {
        case '1':
            $data['center'] = '聊天';
            $data['control'] = 'videoCallApi/video_call_time_charging';
            break;
        case '2':
            $data['center'] = '语音';
            $data['control'] = 'videoCallApi/video_call_time_charging';
            break;
        case '3':
            $data['center'] = '视频';
            $data['control'] = 'videoCallApi/video_call_time_charging';
            break;
        case '4':
            $data['center'] = '打赏礼物';
            $data['control'] = 'giftApi/gift_giving';
            break;
        case '5':
            $data['center'] = '打赏背包礼物';
            $data['control'] = 'giftApi/send_bag_gift';
            break;
        case '6':
            $data['center'] = lang('Play_with_others_place_order');
            $data['control'] = 'PlayerOrderApi/request_add_order';
            break;
        case '7':
            $data['center'] = '签到奖励';
            $data['control'] = 'sign_api/request_sign_in';
            break;
        case '8':
            $data['center'] = '任务奖励';
            $data['control'] = 'common/task_reward';
            break;
        case '9':
            $data['center'] = '取消陪玩订单';
            $data['control'] = 'userOrderApi/request_cancel_order';
            break;
        case '10':
            $data['center'] = '拒接陪玩订单';
            $data['control'] = 'PlayerOrderApi/reuqest_order_status';
            break;
        case '11':
            $data['center'] = '陪玩订单退款';
            $data['control'] = 'PlayerOrderApi/reuqest_order_status';
            break;
        case '12':
            $data['center'] = '购买贵族';
            $data['control'] = 'NpbleVueApi/buy_noble';
            break;
        case '13':
            $data['center'] = '续费贵族礼包';
            $data['control'] = 'NpbleVueApi/buy_noble';
            break;
        case '14':
            // 公会收益
            $data['center'] = lang('Guild_income');
            $data['control'] = '';
            break;
        case '15':
            // 购买vip
            $data['center'] = '购买VIP';
            $data['control'] = '';
            break;
        case '16':
            // 幸运礼物中奖
            $data['center'] = '幸运礼物中奖';
            $data['control'] = '';
            break;
        case '32':
            // 新人注册福利
            $data['center'] = '注册福利';
            $data['control'] = '';
            break;
        default:
            $data['center'] = lang('other');
            $data['control'] = $control;
            break;
    }
    //增加消费记录
    $insert_id = db('user_log')->insertGetId($data);

    return $insert_id;
}

/*
*   系统赠送(新人福利)
*   $uid 用户id $coin 奖励金额 $type 类型1新人福利 $genre 1增加2减少
*/
function update_new_welfare($uid, $coin, $type, $genre)
{
    $data = [
        'uid' => $uid,
        'coin' => $coin,
        'type' => $type,
        'genre' => $genre,
        'create_time' => NOW_TIME,
    ];
    //增加消费记录
    $insert_id = db('user_new_welfare')->insertGetId($data);

    return $insert_id;
}

//获取用户基本信息 1是不缓存 0是缓存
function get_user_base_info($user_id, $field = array(), $cache = 1)
{
    $user_info = load_cache('user_info', ['user_id' => $user_id, 'field' => $field, 'cache' => $cache]);

    if (!$user_info) {

        $user_info = ['user_nickname' => lang('user_does_not_exist'), 'id' => -1, 'avatar' => '', 'sex' => 0, 'age' => 1, 'level' => 1];
    }

    return $user_info;
}

/**
 * @dw 获取用户认证信息
 * @return int 成功返回认证状态,未提交返回-1
 * */
function get_user_auth_status($user_id)
{
    $config = load_cache('config');
    //获取认证状态
    if (isset($config['auth_type']) && $config['auth_type'] == 1) {
        //是否提交认证
        $auth_record = db('user_auth_video')->where('user_id', '=', $user_id)->find();
    } else {
        //是否提交认证
        $auth_record = db('auth_form_record')->where('user_id', '=', $user_id)->find();
    }
    return $auth_record ? $auth_record['status'] : -1;

}

// 获取消费=====等级图标
function get_user_level($user_id, $field = '')
{
    $user_info = get_user_base_info($user_id, ['consumption_total']);
    //获取消费记录
    $total = $user_info['consumption_total'];

    $levelRule = load_cache('level', ['type' => 1]);
    $lastLevel = false;
    foreach ($levelRule as $value) {
        if ($value['level_up'] <= $total) {
            $lastLevel = $value;
        } else {
            break;
        }
    }
    if ($field) {
        return $lastLevel ? $lastLevel[$field] : '';
    } else {
        return $lastLevel;
    }
}

// 获取消费=====等级图标
function get_user_level_by_consumption($consumption_total, $field = '')
{
    $levelRule = load_cache('level', ['type' => 1]);
    $lastLevel = null;
    foreach ($levelRule as $value) {
        if ($value['level_up'] <= $consumption_total) {
            $lastLevel = $value;
        } else {
            break;
        }
    }
    if ($field) {
        return $lastLevel ? $lastLevel[$field] : '';
    } else {
        return $lastLevel;
    }
}


// 获取收益=====等级图标chat_icon
function get_user_income_level($user_id, $field = '')
{
    $user_info = get_user_base_info($user_id, ['charm_values_total']);
    $total = $user_info['charm_values_total'];

    $levelRule = load_cache('level', ['type' => 2]);
    $lastLevel = false;
    foreach ($levelRule as $value) {
        if ($value['level_up'] <= $total) {
            $lastLevel = $value;
        } else {
            break;
        }
    }
    if ($field) {
        return $lastLevel ? $lastLevel[$field] : '';
    } else {
        return $lastLevel;
    }
}

// 获取明星(收益)等级
function get_income_level($user_id, $type = '1')
{
    $key = "user_income_level_" . $user_id;
    $income_level = redis_get($key);

    if (!$income_level) {
        $user_info = get_user_base_info($user_id);
        //获取明星等级 获取消费记录
        $where = "to_user_id=" . $user_id . " and (type =3 or type=23)";   //9砸蛋中奖的礼物进入背包
        $total1 = db('user_consume_log')->where($where)->sum("coin+vip_level_profit");
        $total = intval($total1); //获取充值金币和消费金币总数

        $income_level = array(
            'level_name' => 1,
            'msum' => $total,
            'level_icon' => '',
            'approach_icon' => '',
            'level_colors' => '',
            'level_up' => 0,
        );
        $level = db('level')->alias("l")
            ->where("l.level_up <=" . $total . " and l.type=2")
            ->join("level_type t", "l.level_type_id =t.id", "left")
            ->field("l.*,t.level_type_name,t.icon as ticon")
            ->order("l.level_up desc")->find();

        if (!$level) {
            // 如果没有查询到--有可能是顶级
            $level = db('level')->alias("l")
                ->where("l.level_up >=" . $total . " and l.type=2")
                ->join("level_type t", "l.level_type_id =t.id", "left")
                ->field("l.*,t.level_type_name,t.icon as ticon")
                ->order("l.level_up desc")->find();
        }
        if ($level) {
            $income_level['level_name'] = $level['level_name'];
            $income_level['level_up'] = $level['level_up']; // 区间值
            $income_level['level_icon'] = $level['chat_icon']; // 等级图标
            $income_level['approach_icon'] = $level['ticon']; // 进场图标
            $income_level['level_colors'] = $level['colors']; // 颜色值
            if ($level['level_name'] != $user_info['income_level']) {
                db("user")->where("id=" . $user_id)->update(array('income_level' => $level['level_name']));
            }
        }
        redis_set($key, json_encode($income_level), 60);
    } else {
        $income_level = json_decode($income_level, true);
    }

    if ($type == 1) {
        return $income_level['level_name'];
    } else {
        return $income_level;
    }
}

/**
 * 获取消费(财富)等级
 * user_id 用户id
 * type 1返回等级id 2返回数组
 * */
function get_level($user_id, $type = '1')
{
    $key = "user_grade_level_" . $user_id;
    $grade_level = redis_get($key);

    if (!$grade_level) {
        $user_info = get_user_base_info($user_id);
        $where = "user_id=" . $user_id . " and (type =3 or type=23)";   //9砸蛋中奖的礼物进入背包23背包 24 25 游戏 32第三方游戏 33欢乐游戏
        //获取消费记录
        $total = intval(db('user_consume_log')->where($where)->sum("coin+vip_level_coin"));
        $grade_level = array(
            'level_name' => 1,
            'msum' => $total,
            'level_icon' => '',
            'approach_icon' => '',
            'level_colors' => '',
            'level_up' => 0,
        );
        $level = db('level')->alias("l")
            ->where("l.level_up <=" . $total . " and l.type=1")
            ->join("level_type t", "l.level_type_id =t.id", "left")
            ->field("l.*,t.level_type_name,t.icon as ticon")
            ->order("l.level_up desc")->find();
        if (!$level) {
            // 如果没有查询到--有可能是顶级
            $level = db('level')->alias("l")
                ->where("l.level_up >=" . $total . " and l.type=1")
                ->join("level_type t", "l.level_type_id =t.id", "left")
                ->field("l.*,t.level_type_name,t.icon as ticon")
                ->order("l.level_up desc")->find();
        }

        if ($level) {
            $grade_level['level_name'] = $level['level_name'];
            $grade_level['level_up'] = $level['level_up']; // 区间值
            $grade_level['level_icon'] = $level['chat_icon']; // 等级图标
            $grade_level['approach_icon'] = $level['ticon']; // 进场图标
            $grade_level['level_colors'] = $level['colors']; // 颜色值
            if ($level['level_name'] != $user_info['income_level']) {
                db("user")->where("id=" . $user_id)->update(array('level' => $level['level_name']));
            }
        }
        redis_set($key, json_encode($grade_level), 60);
    } else {
        $grade_level = json_decode($grade_level, true);
    }
    if ($type == 1) {
        return $grade_level['level_name'];
    } else {
        return $grade_level;
    }
}

//获取消费(财富)用户等级
function get_grade_level($user_id)
{
    $user_info = get_user_base_info($user_id, ['consumption_total']);
    //获取消费记录
    $total = $user_info['consumption_total'];

    $levelRule = load_cache('level', ['type' => 1]);
    $lastLevel = false;
    foreach ($levelRule as $value) {
        if ($value['level_up'] <= $total) {
            $lastLevel = $value;
        } else {
            break;
        }
    }
    if ($lastLevel) {
        $level_type = db('level_type')->where("id=" . $lastLevel['level_type_id'])->find();
        $lastLevel['ticon'] = $level_type ? $level_type['icon'] : '';
        $lastLevel['level_type_name'] = $level_type ? $level_type['level_type_name'] : '';
    }

    $lastLevel2 = false;
    foreach ($levelRule as $value2) {
        if ($value2['level_up'] > $total) {
            $lastLevel2 = $value2;
            break;
        }
    }

    $data['level_name'] = $lastLevel ? $lastLevel['level_name'] : '';
    //获取充值金币和消费金币总数
    $data['msum'] = $total; // 获取提成比例
    $data['level_icon'] = $lastLevel ? $lastLevel['chat_icon'] : ''; // 等级图标
    $data['approach_icon'] = $lastLevel ? $lastLevel['ticon'] : ''; // 进场聊天图标
    $data['level_colors'] = $lastLevel ? $lastLevel['colors'] : ''; // 颜色值

    if ($lastLevel2) {
        // 获取下一个级别
        $data['down_name'] = $lastLevel2['level_name'] ? $lastLevel2['level_name'] : 0;
        $data['spread'] = $lastLevel2['level_up'] - $total > 0 ? $lastLevel2['level_up'] - $total : 0;
        // 进度 单位%                                   // 获取下一个级别
        $data['progress'] = $total > 0 && $lastLevel2['level_up'] > 0 ? round(100 * ($total / $lastLevel2['level_up'])) : 0; // 进度 单位%
        $data['down_name_val'] = $lastLevel2['level_up'] ? $lastLevel2['level_up'] : 0;   //下一个等级值
        $data['top_name_val'] = $lastLevel['level_up'];    //本等级区间值

    } else {
        $data['down_name'] = '99999';
        $data['progress'] = '0%';
        $data['spread'] = 0;
    }
    return $data;
}

//获取明星(收益)用户等级
function get_grade_income_level($user_id)
{
    $user_info = get_user_base_info($user_id, ['charm_values_total']);
    //获取总收益等级数据
    $total = intval($user_info['charm_values_total']);

    $levelRule = load_cache('level', ['type' => 2]);
    $lastLevel = false;
    foreach ($levelRule as $value) {
        if ($value['level_up'] <= $total) {
            $lastLevel = $value;
        } else {
            break;
        }
    }

    if ($lastLevel) {
        $level_type = db('level_type')->where("id=" . $lastLevel['level_type_id'])->find();
        $lastLevel['ticon'] = $level_type ? $level_type['icon'] : '';
        $lastLevel['level_type_name'] = $level_type ? $level_type['level_type_name'] : '';
    }

    $lastLevel2 = false;
    foreach ($levelRule as $value2) {
        if ($value2['level_up'] > $total) {
            $lastLevel2 = $value2;
            break;
        }
    }

    $data['level_name'] = $lastLevel ? $lastLevel['level_name'] : '';
    //获取充值金币和消费金币总数
    $data['msum'] = $total; // 获取提成比例
    $data['level_icon'] = $lastLevel ? $lastLevel['chat_icon'] : ''; // 等级图标
    $data['approach_icon'] = $lastLevel ? $lastLevel['ticon'] : ''; // 进场聊天图标
    $data['level_colors'] = $lastLevel ? $lastLevel['colors'] : ''; // 颜色值

    if ($lastLevel2) {
        // 获取下一个级别
        $data['down_name'] = $lastLevel2['level_name'] ? $lastLevel2['level_name'] : 0;
        $data['spread'] = $lastLevel2['level_up'] - $total > 0 ? $lastLevel2['level_up'] - $total : 0;
        // 进度 单位%                                   // 获取下一个级别
        $data['progress'] = $total > 0 && $lastLevel2['level_up'] > 0 ? round(100 * ($total / $lastLevel2['level_up'])) : 0; // 进度 单位%
        $data['down_name_val'] = $lastLevel2['level_up'] ? $lastLevel2['level_up'] : 0;   //下一个等级值
        $data['top_name_val'] = $lastLevel['level_up'];    //本等级区间值

    } else {
        $data['down_name'] = '99999';
        $data['progress'] = '0%';
        $data['spread'] = 0;
    }
    return $data;
}

/**
 * 根据消费值获取等级
 * */
function getWealthLevelRuleInfoByTotalValue($total, $type = 1)
{
    $levelRule = load_cache('level', ['type' => $type]);
    $lastLevel = null;

    foreach ($levelRule as $value) {
        if ($value['level_up'] <= $total) {
            $lastLevel = $value;
        } else {
            break;
        }
    }

    return $lastLevel;
}

//用户是否在线
function is_online($user_id)
{
    include_once DOCUMENT_ROOT . '/system/redis/UserOnlineStateRedis.php';

    $user_redis = new UserOnlineStateRedis();
    $res = $user_redis->is_online($user_id);
    return $res ? 1 : 0;
}

//生成邀请码
function create_invite_code_0910($user_id)
{
    //获取邀请码
    $invite_code = db('invite_code')->where('user_id', '=', $user_id)->find();

    if (!$invite_code) {
        //生成邀请码;
        db('invite_code')->insert(['user_id' => $user_id, 'invite_code' => $user_id]);
        $invite_code = $user_id;
    } else {
        $invite_code = $invite_code['invite_code'];
    }

    return $invite_code;
}

//生成邀请码  作废
function create_invite_code()
{

    $code = rand_str(6);
    $res = db('invite_code')->where('invite_code', '=', $code)->find();
    if ($res) {
        create_invite_code();
    } else {
        return $code;
    }
}


/**
 * @dw 男性用户充值返现业务
 * @param $total_money 充值金额
 * @param $uid         用户ID
 * @param $log_id      充值记录ID
 *                     *@author 魏鹏
 */
function invite_back_now_recharge($total_money, $uid, $order_id)
{
    $user_info = get_user_base_info($uid);
    //增加收益分成
    $invite_record = db('invite_record')->where('invite_user_id', '=', $uid)->find();

    // && $user_info['sex'] == 1 语音直播不限制男性
    if ($invite_record && $invite_record['user_id'] != 0) {

        //获取充值扣单概率
        if (get_bucket_invite($invite_record['user_id'], 1) == 0) {
            //分成比例
            $config = load_cache('config');
            $invite_income = round($total_money * $config['invite_income_ratio'], 2);

            if ($invite_income > 0) {
                //增加邀请人收益
                $record = [
                    'user_id' => $invite_record['user_id'],
                    'invite_user_id' => $uid,
                    'c_id' => 0,
                    'income' => 0,
                    'invite_code' => $invite_record['invite_code'],
                    'create_time' => NOW_TIME,
                    'total_coin' => $total_money,
                    'money' => $invite_income,
                    'type' => 2,
                    'order_id' => $order_id,
                ];

                db('invite_profit_record')->insert($record);
                db('user')->where('id', '=', $invite_record['user_id'])->inc('invitation_coin', $invite_income)->update();
            }
        } else {
            //扣量记录
            $invite_deduction_record = [
                'user_id' => $invite_record['user_id'],
                'invite_user_id' => $uid,
                'order_id' => $order_id,
                'create_time' => NOW_TIME,
                'money' => $total_money,
            ];
            db('invite_recharge_deduction_record')->insert($invite_deduction_record);
        }
    }
}

//七牛批量删除
function oss_del_file($data)
{
    require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
    // 需要填写你的 Access Key 和 Secret Key
    $accessKey = Config::get('qiniu.accessKey');
    $secretKey = Config::get('qiniu.secretKey');
    // 要上传的空间
    $bucket = Config::get('qiniu.bucket');
    // 构建鉴权对象
    $auth = new Auth($accessKey, $secretKey);

    $config = new \Qiniu\Config();

    $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);

    $result = $bucketManager->delete($bucket, $data);

    //var_dump($result);exit;
    if (!$result) {
        return true;
    } else {
        return false;
    }

}

//七牛上传 $file文件
function oss_upload($file)
{
    // 要上传图片的本地路径
    $filePath = $file->getRealPath();
    $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION); //后缀
    // 上传到七牛后保存的文件名
    $key = substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;
    require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
    // 需要填写你的 Access Key 和 Secret Key
    $accessKey = Config::get('qiniu.accessKey');
    $secretKey = Config::get('qiniu.secretKey');
    // 构建鉴权对象
    $auth = new Auth($accessKey, $secretKey);
    // 要上传的空间
    $bucket = Config::get('qiniu.bucket');
    $domain = Config::get('qiniu.DOMAIN');
    $token = $auth->uploadToken($bucket);

    // 初始化 UploadManager 对象并进行文件的上传
    $uploadMgr = new UploadManager();

    // 调用 UploadManager 的 putFile 方法进行文件的上传
    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
    if ($err !== null) {

        return false;
    } else {
        $url = $domain . '/' . $ret['key'];
        //返回图片的完整URL
        return $url;
    }
}

//定时清理语音房间用户离线数据
function crontab_do_end_voice()
{
    //清除所有过期的心跳
    $config = load_cache('config');
    //时间
    $time = NOW_TIME - $config['heartbeat_interval'] - 60;//偏移量5秒
    $user = db('monitor')->where('monitor_time', '<', $time)->select();
    foreach ($user as $v) {

        //查询语音房间
        $uid = $v['user_id'];
        $wheat_logs = db('voice_even_wheat_log')->where("user_id=$uid and status=1")->find();
        if ($wheat_logs) {
            //正在连麦的下麦
            $name = array('status' => 3, 'endtime' => NOW_TIME);
            db('voice_even_wheat_log')->where("id=" . $wheat_logs['id'])->update($name);
            redis_hDelOne('ban_voice_' . $wheat_logs['voice_id'], $uid);   //解除禁言房间缓存
            voice_del_userlist($wheat_logs['voice_id'], $uid);//删除直播间用户缓存
            redis_hDelOne("user_voice", $uid);    //删除用户在直播间缓存

            $online_number = voice_userlist_sum($wheat_logs['voice_id']);
            db('voice')->where('user_id=' . $wheat_logs['voice_id'])->update(array('online_number' => intval($online_number)));
        } else {
            $voice_id = redis_hGet("user_voice", $uid);
            if ($voice_id) {
                redis_hDelOne("user_voice", $uid);        //删除用户在直播间缓存
                redis_hDelOne('ban_voice_' . $voice_id, $uid);   //解除禁言房间缓存
                voice_del_userlist($voice_id, $uid);//删除直播间用户缓存
                $online_number = voice_userlist_sum($voice_id);
                db('voice')->where('user_id=' . $voice_id)->update(array('online_number' => intval($online_number)));
            }
        }
    }

}

//定时清理离线用户
function crontab_do_end_live()
{
    $config = load_cache('config');

    //时间
    $time = NOW_TIME - $config['heartbeat_interval'] - 10; //偏移量5秒
    $time_out_user = db('monitor')->where('monitor_time', '<', $time)->select();

    $out_id_array = [];
    foreach ($time_out_user as $v) {

        $out_id_array[] = $v['user_id'];
        //删除心跳
        $key = 'online:' . $v['user_id'];
        $GLOBALS['redis']->del('del', $key);
    }

    if (count($out_id_array) > 0) {

        $ids = implode(',', $out_id_array);
        //删除所有超时心跳
        db('monitor')->where('user_id', 'in', $ids)->delete();
    }

    db('video_live_list')->where('last_heart_time', '<', $time)->delete();

}

//定时清理超时电话
function crontab_do_end_call()
{

    $config = load_cache('config');
    //查询
    $list = db('video_call_record')->where(['status' => 0])->select();
    foreach ($list as $v) {
        $time = NOW_TIME - $v['create_time'];
        if ($time > $config['video_call_time_out']) {
            //删除超时电话记录
            db('video_call_record')->delete($v['id']);
        }
    }

    //查询
    $list = db('video_call_record')->where(['status' => 1])->select();
    foreach ($list as $v) {
        $time = NOW_TIME - $v['create_time'];
        if ($time > 60 * 60 * 5) {
            //删除超时电话记录
            $v['status'] = 4;
            $v['end_time'] = NOW_TIME;

            db('video_call_record_log')->insert($v);
            db('video_call_record')->delete($v['id']);
        }
    }

}

//支付通用回调方法 $trade_no 第三方订单号
function pay_call_service($notice_sn, $pay_type = '', $ios_info = '', $trade_no = '')
{
    //订单信息
    $order_info = db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->find();

    if ($order_info) {
        //充值VIP
        if ($order_info['type'] == 7777777) {
            $vip_rule = db('vip_rule')->find($order_info['refillid']);
            $vip_time = $vip_rule['day_count'] * 60 * 60 * 24 * 30;
            $user_info = get_user_base_info($order_info['uid'], ['vip_end_time']);

            if ($user_info['vip_end_time'] > NOW_TIME) {
                $shop_time = $user_info['vip_end_time'] + $vip_time;
                db('user')->where('id', '=', $user_info['id'])->setInc('vip_end_time', $vip_time);
            } else {
                $vip_time = NOW_TIME + $vip_time;
                $shop_time = $vip_time;
                db('user')->where('id', '=', $user_info['id'])->setField('vip_end_time', $vip_time);
            }
            //修改订单状态
            db('user_charge_log')
                ->where('order_id', '=', $notice_sn)
                ->where('status', '=', 0)
                ->update(['trade_no' => $trade_no, 'status' => 1]);
            // 获取vip商城特权
            add_vip_shop($order_info['uid'], $shop_time);

            $config = load_cache('config');

            //发送群频道通知
            $broadMsg['type'] = \app\common\Enum::PAY_CALL_SERVICE;
            $sender['id'] = $user_info['id'];
            $sender['user_nickname'] = $user_info['user_nickname'];
            $sender['avatar'] = $user_info['avatar'];
            $broadMsg['channel'] = 'all'; //通话频道
            $broadMsg['sender'] = $sender;
            $msg_str = lang('vulgar_tycoon') . $user_info['user_nickname'] . lang('Opened_distinguished_VIP');
            $broadMsg['vip_info']['send_msg'] = $msg_str;
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

            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $api = createTimAPI();

            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $config['acquire_group_id'], $msg_content);
            //增加代理用户分成数据
            agent_order_recharge($order_info['money'], $order_info['uid'], $notice_sn, $order_info['pay_type_id'], 1);

            return true;
        } else {
            //充值钻石
            if ($order_info['refillid'] == -1) {
                if ($order_info['status'] == 0) {
                    //增加用户钻石
                    $coin = $order_info['coin'];
                    db('user')->where('id', '=', $order_info['uid'])->setInc('coin', $coin);
                    db('user_charge_log')
                        ->where('order_id', '=', $notice_sn)
                        ->where('status', '=', 0)
                        ->update(['trade_no' => $trade_no, 'status' => 1]);
                    //邀请奖励分成
                    //invite_back_now_recharge($order_info['money'], $order_info['uid'], $order_info['id']);
                    request_invite_record($order_info['uid'], 2, $coin, $order_info['id'], $notice_sn);
                    //增加代理用户分成数据
                    agent_order_recharge($order_info['money'], $order_info['uid'], $notice_sn, $order_info['pay_type_id'], 0);
                    // 钻石变更记录
                    save_coin_log($order_info['uid'], $coin, 1, 1, $notice_sn);
                    //增加回调信息
                    notify_log($notice_sn, $order_info['uid'], '充值回调成功,success:增加钻石成功');

                    return true;
                }
            } else {
                $rule = db('user_charge_rule')->find($order_info['refillid']);
                if ($rule && $order_info['status'] == 0) {
                    $coin = $rule['coin'] + $rule['give'];
                    if ($pay_type == 'appley_pay') {
                        if (empty($ios_info)) {
                            notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
                            exit;
                        }
                        /*if (isset($ios_info['receipt']['bundle_id'])) {
                            if ($config['ios_package_name'] != $ios_info['receipt']['bundle_id']) {
                                notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
                                exit;
                            }
                        } else {
                            notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
                            exit;
                        }
                        if (isset($ios_info['receipt']['in_app'][0]['product_id']) && isset($ios_info['receipt']['in_app'][0]['transaction_id'])) {
                            //查询充值规则
                            if ($rule['name'] != $ios_info['receipt']['in_app'][0]['product_id']) {
                                notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
                                exit;
                            }
                            $pay_order_id = $ios_info['receipt']['in_app'][0]['transaction_id'];
                            $have_pay = db('user_charge_log')
                                ->where(['pay_order_id' => $pay_order_id])
                                ->find();
                            if ($have_pay) {
                                notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
                                exit;
                            } else {
                                db('user_charge_log')
                                    ->where('order_id', '=', $notice_sn)
                                    ->where('status', '=', 0)->
                                    update(['pay_order_id' => $pay_order_id]);
                            }

                        } else {
                            notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
                            exit;
                        }*/

                        $coin = $rule['apple_pay_coin'] + $rule['give'];
                    }
                    //增加用户钻石
                    db('user')->where('id', '=', $order_info['uid'])->setInc('coin', $coin);
                    db('user_charge_log')
                        ->where('order_id', '=', $notice_sn)
                        ->where('status', '=', 0)
                        ->update(['trade_no' => $trade_no, 'status' => 1]);
                    //邀请奖励分成
                    //invite_back_now_recharge($order_info['money'], $order_info['uid'], $order_info['id']);
                    request_invite_record($order_info['uid'], 2, $rule['coin'], $order_info['id'], $notice_sn);
                    //增加代理用户分成数据
                    agent_order_recharge($order_info['money'], $order_info['uid'], $notice_sn, $order_info['pay_type_id'], 0);
                    // 钻石变更记录
                    save_coin_log($order_info['uid'], $coin, 1, 1, $notice_sn);
                    //增加回调信息
                    notify_log($notice_sn, $order_info['uid'], '充值回调成功,success:增加钻石成功');

                    return true;
                } else {
                    //充值规则不存在
                    db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->setField('status', 2);
                    notify_log($notice_sn, $order_info['uid'], '充值回调成功,error:充值规则不存在');
                }
            }

        }


    } else {
        //订单信息不存在

        notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
    }

    return false;
}

function pay_call_service_no($notice_sn, $trade_no = '')
{
    //订单信息
    $order_info = db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->find();

    if ($order_info) {

        db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->update(['trade_no' => $trade_no]);
    } else {
        //订单信息不存在

        notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
    }

}

function add_vip_shop($uid, $shop_time)
{
    // 获取vip商城
    $shop_where = "status=1 and is_vip=1";
    // 获取vip特权商城列表
    $list = db("shop")->field("id,name,type")->where($shop_where)->order("sort desc")->select();

    foreach ($list as $v) {
        // 查询商品是否续费的信息
        $shop_user_where = "uid=" . $uid . " and shop_id=" . $v['id'] . " and type=" . $v['type'] . " and status=1 and endtime >" . NOW_TIME;

        $shop_log_status = db("shop_user")->where($shop_user_where)->order("endtime desc")->find();

        if ($shop_log_status) {
            // 修改购买商品
            db("shop_user")->where("id=" . $shop_log_status['id'])->update(array('endtime' => $shop_time));
        } else {

            $shop_data = array(
                'uid' => $uid,
                'shop_id' => $v['id'],
                'shop_price_id' => 0,
                'coin' => 0,
                'month' => 0,
                'type' => $v['type'],
                'addtime' => NOW_TIME,
                'endtime' => $shop_time,
                'is_renewal' => 0,
                'status' => 1,
            );
            db('shop_user')->insertGetId($shop_data);
        }
    }
}

function notify_log($order_id, $user_id, $content)
{
    db('pay_notify_log')->insert(['order_id' => $order_id, 'user_id' => $user_id, 'content' => $content, 'create_time' => NOW_TIME]);
}

function post($curlPost, $url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);

    $return_str = curl_exec($curl);

    curl_close($curl);
    return $return_str;
}

//xml解析
function xml_to_array($xml)
{
    $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";

    if (preg_match_all($reg, $xml, $matches)) {

        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $subxml = $matches[2][$i];
            $key = $matches[1][$i];
            if (preg_match($reg, $subxml)) {
                $arr[$key] = xml_to_array($subxml);
            } else {
                $arr[$key] = $subxml;
            }
        }
    }
    return $arr;
}

/**
 * 根据经纬度和半径计算出范围
 * @param string $lat    纬度
 * @param String $lng    经度
 * @param float  $radius 半径
 * @return Array 范围数组
 *                       (24901 * 1609) 值等于40065709。2*PI*6378137 = 40075008 基本相同 就是地球某一个经度下，地球的周长，看上面式子，应该是每一度有多长
 *                       1609是英里转米的系数
 */
function calcScope($lat, $lng, $radius)
{
    $degree = (24901 * 1609) / 360.0;
    $dpmLat = 1 / $degree;

    $radiusLat = $dpmLat * $radius;
    $minLat = $lat - $radiusLat;    // 最小纬度
    $maxLat = $lat + $radiusLat;    // 最大纬度

    $mpdLng = $degree * cos($lat * (pi() / 180));
    $dpmLng = 1 / $mpdLng;
    $radiusLng = $dpmLng * $radius;
    $minLng = $lng - $radiusLng;   // 最小经度
    $maxLng = $lng + $radiusLng;   // 最大经度

    /** 返回范围数组 */
    $scope = array(
        'minLat' => $minLat,
        'maxLat' => $maxLat,
        'minLng' => $minLng,
        'maxLng' => $maxLng
    );
    return $scope;
}

//获取经纬度
function returnSquarePoint($lng, $lat, $distance = 0.5)
{
    define('EARTH_RADIUS', '6371'); //地球半径，平均半径为6371km
    $dlng = 2 * asin(sin($distance / (2 * EARTH_RADIUS)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);

    $dlat = $distance / EARTH_RADIUS;
    $dlat = rad2deg($dlat);

    return array(
        'left-top' => array('lat' => $lat + $dlat, 'lng' => $lng - $dlng),
        'right-top' => array('lat' => $lat + $dlat, 'lng' => $lng + $dlng),
        'left-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng - $dlng),
        'right-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng + $dlng),
    );
}

/**
 * 计算两点地理坐标之间的距离
 * @param Decimal $longitude1 起点经度
 * @param Decimal $latitude1  起点纬度
 * @param Decimal $longitude2 终点经度
 * @param Decimal $latitude2  终点纬度
 * @param Int     $unit       单位 1:米 2:公里
 * @param Int     $decimal    精度 保留小数位数
 * @return Decimal
 */
function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
{

    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;

    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;

    if ($unit == 2) {
        $distance = $distance / 1000;
    }

    return round($distance, $decimal);

}

//获取验证码数字
function get_verification_code($area_code, $account, $length = 6)
{
    if (empty($account)) {
        return false;
    }

    $config = load_cache('config');

    $maxCount = $config['system_sms_sum'];
    $time = Time::today();

    $day_send_count = db('verification_code')
        ->where('phone_area_code', $area_code)
        ->where("account", $account)
        ->where('send_time', '>', $time[0])
        ->count();

    $result = true;
    if ($day_send_count >= $maxCount) {
        return false;
    }

    if ($result) {
        switch ($length) {
            case 4:
                $result = rand(1000, 9999);
                break;
            case 6:
                $result = rand(100000, 999999);
                break;
            case 8:
                $result = rand(10000000, 99999999);
                break;
            default:
                $result = rand(100000, 999999);
        }
    }

    return $result;
}


/*
 *  代理渠道用户充值记录表
 * */

function agent_order_recharge($total_money, $uid, $order_id, $pay_type_id, $type)
{
    $user_info = get_user_base_info($uid);
    if ($user_info['link_id']) {
        $agent = db('agent')->where("id='" . $user_info['link_id'] . "'")->find();
        if ($agent) {
            $data = array(
                'agent_id' => $agent['id'],
                'order_id' => $order_id,
                'channel_link' => $agent['channel'],
                'uid' => $uid,
                'money' => $total_money,
                'agent_commission' => $agent['commission'],
                'type' => $type,
                'pay_type_id' => $pay_type_id,
                'addtime' => time(),
                'date_time' => date('Y-m-d H:i:s', time())
            );
            $commission = 0;
            if ($agent['agent_level'] == 1) {
                $commission = $type == 1 ? $agent['vip_commission'] : $agent['commission'];
            } else {
                $agent_company = db('agent')->where("id='" . intval($agent['agent_company']) . "'")->find();
                if ($agent_company) {
                    // 获取公司提成
                    $commission = $type == 1 ? $agent_company['vip_commission'] : $agent_company['commission'];
                }
            }
            if ($commission > 0) {
                $agent_money = floor($commission * $total_money * 100) / 100;
                $data['agent_money'] = $agent_money;
                db('agent_order_log')->insert($data);
                if ($agent_money > 0) {
                    // 实际收益数
                    db('agent')->where("id='" . intval($agent['agent_company']) . "'")->inc("income", $agent_money)->inc("income_total", $agent_money)->update();
                }
            }
        }
    }
}

/**
 * 更新手机或邮箱验证码发送日志
 * @param string $account    手机或邮箱
 * @param string $code       验证码
 * @param int    $expireTime 过期时间
 * @param array  $result     返回值
 * @return boolean
 */
function verification_code_log($areaCode, $account, $code, $result, $expireTime = 0)
{
    $currentTime = NOW_TIME;
    $expireTime = $expireTime > $currentTime ? $expireTime : $currentTime + 30 * 60;
    $verificationCodeQuery = db('verification_code');
    $findVerificationCode = $verificationCodeQuery->where('account', $account)->where('phone_area_code', $areaCode)->order("id desc")->find();

    $count = 1;
    $todayStartTime = strtotime(date("Y-m-d")); //当天0点
    if ($findVerificationCode && $findVerificationCode['send_time'] > $todayStartTime) {
        //获取当天的条数
        $count = $findVerificationCode['count'] + 1;
    }

    $result = $verificationCodeQuery
        ->insert([
            'phone_area_code' => $areaCode,
            'account' => $account,
            'send_time' => $currentTime,
            'code' => $code,
            'count' => $count,
            'expire_time' => $expireTime,
            'status' => $result['code'] == 1 ? 1 : 2,
            'msg' => $result['msg'],
            'smUuid' => $result['smUuid'] ? $result['smUuid'] : '',
        ]);
    return $result;
}

function birthday($birthday)
{
    $age = strtotime($birthday);
    if ($age === false) {
        return false;
    }
    list($y1, $m1, $d1) = explode("-", date("Y-m-d", $age));
    $now = strtotime("now");
    list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
    $age = $y2 - $y1;
    if ((int)($m2 . $d2) < (int)($m1 . $d1))
        $age -= 1;
    return $age;
}

/*
 * 判断用户身份
 * 0普通用户
 * 1身份认证
 * 2主播 陪聊
 * 3陪玩
 * 4全认证
 * */
function get_user_identity($uid)
{
    //判断用户身份
    $user_info = Db::name('user')->field('is_auth,is_player,is_talker')->find($uid);
    //dump($user_info);

    $identity = 0;//普通用户
    if ($user_info) {
        if ($user_info['is_auth'] == 1 && $user_info['is_player'] == 1 && $user_info['is_talker'] == 1) {
            $identity = 4;//全认证用户
        } else if ($user_info['is_auth'] == 1 && $user_info['is_player'] == 1) {
            $identity = 3;//陪玩
        } else if ($user_info['is_auth'] == 1 && $user_info['is_talker'] == 1) {
            $identity = 2;//陪聊
        } else if ($user_info['is_auth'] == 1) {
            $identity = 1;//身份认证
        } else {
            $identity = 0;//普通用户
        }
    }

    return $identity;
}

/*
 * 扣费判断用户心币(充值) 友币(赠送)
 * $coin 金额
 * 1 心币 2友币 0余额不足
 * */
function get_user_coin($uid, $coin)
{
    $user_info = Db::name('user')->field('coin,friend_coin')->find($uid);
    if ($user_info['friend_coin'] >= $coin) {
        $type = 2;
    } else if ($user_info['coin'] >= $coin) {
        $type = 1;
    } else {
        $type = 0;
    }
    return $type;
}

/*
 * 陪聊师等级
 * */
function get_talker_level($uid)
{
    $user_info = Db::name('user')->field('is_auth,is_talker,income_talker_total')->find($uid);
    $data = [
        'talker_level_name' => '',
        'talker_level_img' => '',
        'level_up' => 0
    ];
    if ($user_info['is_auth'] == 1 && $user_info['is_talker'] == 1) {
        //计算陪聊师等级
        $level = Db::name('talker_level')
            ->where('level_up <= ' . $user_info['income_talker_total'])
            ->order('level_up desc')
            ->find();
        if ($level) {
            $data['talker_level_name'] = $level['name'];
            $data['talker_level_img'] = $level['chat_icon'];
            $data['level_up'] = $level['level_up'];
        }
    }
    return $data;
}

/*
 * 陪玩师等级
 * */
function get_player_level($uid)
{
    $user_info = Db::name('user')->field('is_auth,is_player,income_player_total')->find($uid);
    $data = [
        'player_level_name' => '',
        'player_level_img' => '',
        'level_up' => 0,
        'player_max_coin' => '',
        'player_min_coin' => ''
    ];
    if ($user_info['is_auth'] == 1 && $user_info['is_player'] == 1) {
        //计算陪聊师等级
        $level = Db::name('player_level')
            ->where('level_up <= ' . $user_info['income_player_total'])
            ->order('level_up desc')
            ->find();
        if ($level) {
            $data['player_level_name'] = $level['name'];
            $data['player_level_img'] = $level['chat_icon'];
            $data['level_up'] = $level['level_up'];
            $data['player_max_coin'] = $level['player_max_coin'];
            $data['player_min_coin'] = $level['player_min_coin'];
        }
    }
    return $data;
}

/*
 * 用户等级
 * */
function get_noble_level($uid)
{
    // vip 专属昵称颜色值
    //  $colors = get_user_vip_authority($uid, 'colors');

    $user_info = Db::name('user')->field('nobility_level,noble_end_time')->find($uid);
    $data = [
        //'player_level_name'=>'',
        'noble_name' => '',
        'noble_img' => '',
        'colors' => '',
        'entry_effects' => '',
    ];
    if ($user_info['nobility_level'] && $user_info['noble_end_time'] > NOW_TIME) {
        //贵族信息
        $noble = Db::name('noble')
            ->find($user_info['nobility_level']);
        if ($noble) {
            //$data['player_level_name'] = $level['name'];
            $data['noble_name'] = $noble['name'];
            $data['noble_img'] = $noble['noble_img'];
            $data['colors'] = $noble['colors'];
            $data['entry_effects'] = $noble['entry_effects'];
        }
    }
    return $data;
}

/*
 * 成为密友
 * */
function add_friendship($uid, $touid, $coin, $gift = '')
{
    //判断用户身份
    $toidentity = get_user_identity($touid);
    //陪聊主播
    if ($toidentity == 2 || $toidentity == 4) {
        //是否是密友
        $user_friendship = Db::name('user_friendship')->where(['uid' => $uid, 'touid' => $touid])->find();
        if ($user_friendship) {
            Db::name('user_friendship')
                ->where(['id' => $user_friendship['id']])
                ->setInc('coin', $coin);
        } else {
            $data = [
                'uid' => $uid,
                'touid' => $touid,
                'coin' => $coin,
                'addtime' => NOW_TIME,
            ];
            Db::name('user_friendship')->insertGetId($data);
        }
        //
        if (!empty($gift)) {
            $content = lang('Your_close_friend') . $gift['user_nickname'] . lang('Gave_it_to_you') . $gift['name'] . 'x' . $gift['count'];
            push_sys_msg_user(20, $touid, 1, $content);
        }
    }
}

/*
 * 密友等级*/
function friendship_level($uid, $touid)
{
    $user_friendship = Db::name('user_friendship')->where(['uid' => $uid, 'touid' => $touid])->find();

    if ($user_friendship) {
        $coin = $user_friendship['coin'];
        //等级
        $level = Db::name('friendship_level')->where('level_up <= ' . $coin)->order('level_up desc')->find();
        if (!$level) {
            $level = Db::name('friendship_level')->order('level_up desc')->find();
        }
    } else {
        $level = Db::name('friendship_level')->order('level_up asc')->find();
    }
    return $level;
}

/*
 * 是否有权限观看
 * */
function friendship_level_is_look($uid, $touid, $level_id)
{
    $set_level = Db::name('friendship_level')->order('level_up asc')->find($level_id);
    $data['level'] = 0;
    $data['is_look'] = 0;
    if ($set_level) {
        $data['level'] = $set_level['name'];
        $level = friendship_level($uid, $touid);
        if ($level) {
            if ($level['level_up'] >= $set_level['level_up']) {
                $data['is_look'] = 1;
            } else {
                $data['is_look'] = 0;
            }
        }
    } else {
        $data['is_look'] = 1;
    }
    if ($uid == $touid) {
        $data['is_look'] = 1;
    }

    return $data;
}

//时间转换，秒转换成 ?天?小时?分?秒
function secondChanage($second = 0)
{
    $newtime = '';
    $d = floor($second / (3600 * 24));
    $h = floor(($second % (3600 * 24)) / 3600);
    $m = floor((($second % (3600 * 24)) % 3600) / 60);
    $s = $second - ($d * 24 * 3600) - ($h * 3600) - ($m * 60);

    empty($d) ?
        $newtime = (
        empty($h) ? (
        empty($m) ? $s . lang('SECONDS') : (
        empty($s) ? $m . lang('ADMIN_MINUTE') : $m . lang('ADMIN_MINUTE') . $s . lang('SECONDS')
        )
        ) : (
        empty($m) && empty($s) ? $h . lang('ADMIN_HOUR') : (
        empty($m) ? $h . lang('ADMIN_HOUR') . $s . lang('SECONDS') : (
        empty($s) ? $h . lang('ADMIN_HOUR') . $m . lang('ADMIN_MINUTE') : $h . lang('ADMIN_HOUR') . $m . lang('ADMIN_MINUTE') . $s . lang('SECONDS')
        )
        )
        )
        ) : $newtime = (
    empty($h) && empty($m) && empty($s) ? $d . lang('ADMIN_DAY') : (
    empty($h) && empty($m) ? $d . lang('ADMIN_DAY') . $s . lang('SECONDS') : (
    empty($h) && empty($s) ? $d . lang('ADMIN_DAY') . $m . lang('ADMIN_MINUTE') : (
    empty($m) && empty($s) ? $d . lang('ADMIN_DAY') . $h . lang('ADMIN_HOUR') : (
    empty($h) ? $d . lang('ADMIN_DAY') . $m . lang('ADMIN_MINUTE') . $s . lang('SECONDS') : (
    empty($m) ? $d . lang('ADMIN_DAY') . $h . lang('ADMIN_HOUR') . $s . lang('SECONDS') : (
    empty($s) ? $d . lang('ADMIN_DAY') . $h . lang('ADMIN_HOUR') . $m . lang('ADMIN_MINUTE') : $d . lang('ADMIN_DAY') . $h . lang('ADMIN_HOUR') . $m . lang('ADMIN_MINUTE') . $s . lang('SECONDS')
    )
    )
    )
    )
    )
    )
    );

    return $newtime;
}

/*
 * 添加邀请收益
 * type 1分成 2充值,3收益
 * coin 金额
 * c_id 消费记录ID
 * order_id 充值订单号
 * */
function request_invite_record($uid, $type, $coin, $c_id, $order_id = '')
{
    //邀请信息
    $invite_info = db('invite_record')->where(['invite_user_id' => $uid])->find();
    if (!$invite_info) {
        return 0;
    }
    $config = load_cache('config');
    if ($type == 2) {
        $ratio = $config['invite_income_ratio'];
    } else {
        $ratio = $config['invite_income_ratio_female'];
    }
    $income = round($coin * $ratio, 2);
    $money = 0;
    $data = [
        'user_id' => $invite_info['user_id'],
        'invite_user_id' => $invite_info['invite_user_id'],
        'c_id' => $c_id,
        'income' => $income,
        'create_time' => NOW_TIME,
        'invite_code' => $invite_info['invite_code'],
        'total_coin' => $coin,
        'money' => $money,
        'type' => $type,
        'order_id' => $order_id,
    ];
    return db('invite_profit_record')->insertGetId($data);
}

/*
 * 任务统一处理
 * */

function task_reward($type, $uid)
{
    //$type = intval(input('param.type'));
    //$uid = intval(input('param.uid'));
    //$token = trim(input(('param.token')));
    //$user_info = check_login_token($uid, $token,['last_login_ip']);
    $user_info = get_user_base_info($uid, ['last_login_ip']);
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
            // 记录
            save_coin_log($uid, $coin, 1, 7, $name);
            //upd_user_coin_log($uid,$coin,2,1,$user_info['last_login_ip'],$uid,18);
            upd_user_coin_log($uid, $coin, $coin, 8, 2, 1, $user_info['last_login_ip'], 1);
        }
    }
}

/*
 * 订单消息
 * type:1待接单2已接单-待服务 3服务中-进行中 4完成服务-待确认 5确认-待评价，6结束-评价完成 7拒绝 8取消 9退款 10同意 11不同意
 * order_type 1接单中心 2我的订单
 * */
function player_order_msg($order_id, $type)
{
    //接单
    //订单消息
    $order_info = db('skills_order')
        ->alias('o')
        ->join('play_game g', 'g.id=o.game_id')
        ->where('o.id = ' . $order_id)
        ->field('o.*,g.name as game_name')
        ->find();
    if (!$order_info) {
        return 0;
    }
    $time = date('Y-m-d H:i', $order_info['ordertime']);

    switch ($type) {
        case 1:
            $order_type = 1;
            $user_id = $order_info['touid'];
            $title = lang('New_order');
            $msg = $time . ' ' . $order_info['game_name'] . lang('order');
            break;
        case 2:
            $order_type = 2;
            $user_id = $order_info['uid'];
            $title = lang('Play_with_received_order');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_received');
            break;
        case 5:
            $order_type = 1;
            $user_id = $order_info['touid'];
            $title = lang('Order_completed');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_completed');
            break;
        case 7:
            $order_type = 2;
            $user_id = $order_info['uid'];
            $title = lang('Play_with_others_refuse_accept_orders');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_rejected');
            break;
        case 8:
            $order_type = 1;
            $user_id = $order_info['touid'];
            $title = lang('Order_cancelled');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_cancelled');
            break;
        case 9:
            $order_type = 1;
            $user_id = $order_info['touid'];
            $title = lang('Order_request_refundcelled');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_request_refundcelled');
            break;
        case 10:
            $order_type = 2;
            $user_id = $order_info['uid'];
            $title = lang('Order_refund_succeeded');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_refund_succeeded');
            break;
        case 11:
            $order_type = 2;
            $user_id = $order_info['uid'];
            $title = lang('Order_rejection_refund');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_accompaniment_refused_refund');
            break;
        case 19:
            $order_type = 2;
            $user_id = $order_info['uid'];
            $title = lang('Order_refund_succeeded');
            $msg = $time . ' ' . $order_info['game_name'] . lang('Order_platform_refund_succeeded');
            break;
        default:
            $order_type = 1;
            $user_id = 0;
            $title = '';
            $msg = '';
            break;
    }

    $msg = [
        'uid' => $user_id,
        'type' => 1,
        'order_id' => $order_id,
        'order_type' => $order_type,
        'addtime' => NOW_TIME,
        'title' => $title,
        'content' => $msg,
    ];
    return db('user_msg')->insertGetId($msg);
}

/*
 * 装饰
 * uid 用户ID
 * type 1勋章,2主页特效,3头像框,4聊天气泡,5聊天背景
 * */
function get_user_dress_up($uid, $type)
{
    //贵族等级
    $data = db('user_dress_up')
        ->alias('u')
        ->join('dress_up d', 'd.id=u.dress_id')
        ->where(['u.uid' => $uid, 'type' => $type, 'status' => 1])
        ->where('endtime > ' . NOW_TIME)
        ->field('d.*')
        ->find();
    return $data;
}

/*
 * 系统消息推送
 * 动态点赞、打赏，关注*/
function push_sys_msg_user($msg_id, $user_id, $type, $content = '', $url = '')
{
    $msg = db("user_message")->find($msg_id);
    //url bogo://message?type=1&id=1
    if ($msg) {
        $data = [
            'uid' => 0,
            'touid' => $user_id,
            'type' => $type,
            'messageid' => $msg['id'],
            'messagetype' => $content ? $content : $msg['centent'],
            'jump_url' => $url,
            'status' => 1,
            'addtime' => NOW_TIME,
        ];
        return db('user_message_log')->insert($data);
    }
    return false;

}

/*
 * 一对一订单消息*/
function send_order_msg($uid, $touid, $broadMsg)
{
    //$config = load_cache('config');

    $broadMsg['type'] = \app\common\Enum::ONE_ORDERS;
    #构造高级接口所需参数
    $msg_content = array();
    //创建array 所需元素
    $msg_content_elem = array(
        'MsgType' => 'TIMCustomElem',       //文本类型
        'MsgContent' => array(
            //'Text' => $text_content,                //hello 为文本信息
            'ext' => lang('Order_message'),                       //hello 为文本信息
            'Data' => json_encode($broadMsg),                //
        )
    );

    //将创建的元素$msg_content_elem, 加入array $msg_content
    array_push($msg_content, $msg_content_elem);
    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

    $api = createTimAPI();
    $ret = $api->openim_send_msg2($uid, $touid, $msg_content);
    return $ret;
}

/*
 * 添加工会提成收益
 * uid 用户ID
 * type 1礼物 2陪玩 3私信 4语音通话 5视频通话
 * table_id 记录ID 礼物 通话 私信 陪玩 ID
 * coin 主播收益金额
 * consume_log 收益记录表ID
 * */
function add_guild_income($uid, $type, $table_id, $user_consumption, $coin, $consume_log, $income, $guild_id, $commission)
{

    $data = [
        'user_id' => $uid,
        'table_log' => $table_id,
        'type' => $type,
        'guild_id' => $guild_id,
        'user_consumption' => $user_consumption,
        'host_earnings' => $coin,
        'guild_earnings' => $income,
        'guild_commission' => $commission,
        'guild_type' => 2,
        'addtime' => NOW_TIME,
        'consume_log' => $consume_log,
    ];
    $res = db('guild_log')->insert($data);
    if ($res) {
        // 暂无使用
        db('guild')->where('id = ' . $guild_id)->inc('total_earnings', $income)->update();
        db('guild')->where('id = ' . $guild_id)->inc('earnings', $income)->update();
    }
}

/*
 * 添加观看动态小视频记录
 * */
function add_look_bv_log($uid, $type)
{
    $res = db('look_bzone_video_log')->where('uid = ' . $uid . ' and type = ' . $type)->find();
    if ($res) {
        db('look_bzone_video_log')
            ->where('uid = ' . $uid . ' and type = ' . $type)
            ->update(['addtime' => NOW_TIME]);
    } else {
        $data = [
            'uid' => $uid,
            'type' => $type,
            'addtime' => NOW_TIME,
        ];
        db('look_bzone_video_log')->insert($data);
    }
    //bogo_look_bzone_video_log
}

/*
 * 加密*/
function encryption()
{
    require_once(DOCUMENT_ROOT . '/system/encryption/OpenSSLAES.php');
    $key = '0987654321927654';
    $cipher = 'AES-128-CBC';
    $iv = '0987654321927654';
    return $encrypt = new OpenSSLAES($key, $cipher, $iv); // $key为生成的密钥，$cipher为加密方式
}

/*后台*/

/*
 * 推送所有人可以收到的系统消息
 * */
function push_msg($msg_id, $user_id, $type)
{

    $msg = db("user_message_all")->find($msg_id);
    if ($msg) {
        $data = [
            'uid' => 0,
            'touid' => $user_id,
            'messageid' => $msg['id'],
            'title' => $msg['title'],
            'messagetype' => $msg['centent'],
            'jump_url' => $msg['url'],
            'type' => $type,
            'status' => 1,
            'addtime' => NOW_TIME,
        ];
        return db('user_message_log')->insert($data);
    }
    return false;
}

/*
 * 推送单用户系统消息
 * */
function push_msg_user($msg_id, $user_id, $type, $content = '', $url = '')
{
    $msg = db("user_message")->where('type = ' . $msg_id)->find();

    if ($msg) {
        $data = [
            'uid' => 0,
            'touid' => $user_id,
            'type' => $type,
            'messageid' => $msg['id'],
            'messagetype' => $content ? $content : $msg['centent'],
            'status' => 1,
            'addtime' => NOW_TIME,
            'jump_url' => $url,
        ];
        return db('user_message_log')->insert($data);
    }
    return false;

}

//计算充值赠送贵族
function calculate_noble($uid)
{
    $user_info = get_user_base_info($uid, ['nobility_level', 'noble_end_time']);
    $noble_log = Db::name('noble')->order('coin desc')->find();
    if ($user_info['noble_end_time'] > NOW_TIME && $user_info['nobility_level'] == $noble_log['id']) {
        return 0;
    } else {
        //本月
        $first = date('Y-m-01 0:0:0', time());
        $last = date('Y-m-d 23:59:59', strtotime("$first +1 month -1 day"));
        //上个月
        $top_first = date('Y-m-01 0:0:0', (strtotime($first) - 86400));
        $top_last = date('Y-m-d 23:59:59', strtotime("$top_first +1 month -1 day"));
        $top_first_time = strtotime($top_first);
        $top_last_time = strtotime($top_last);
        //本月充值数
        $first_time = strtotime($first);
        $last_time = strtotime($last);
        $total_coin = db('user_charge_log')->where('type != 7777777 and status = 1 and addtime >= ' . $first_time . ' and addtime <= ' . $last_time . ' and uid = ' . $uid)->sum('coin');
        if ($total_coin > 0) {
            $noble = Db::name('noble')->where('coin <= ' . $total_coin)->order('coin desc')->find();
            //上个月最高等级
            $top_noble = db('user_noble_log')
                ->alias('l')
                ->join('noble n', 'n.id=l.noble_id')
                ->where('l.buy_type = 0 and l.addtime > ' . $top_first_time . ' and l.addtime < ' . $top_last_time)
                ->field('n.*')
                ->order('n.coin desc')
                ->find();
            $is_noble = 0;
            if ($noble) {
                if ($top_noble) {
                    //上月等级大于现在等级 查看是否可以保级
                    if ($top_noble['coin'] > $noble['coin']) {
                        if ($total_coin >= $top_noble['renew_coin']) {
                            //保级成功
                            $data = [
                                'nobility_level' => $top_noble['id'],
                                'noble_end_time' => $last_time,
                            ];
                            $res = db('user')->where('id = ' . $uid)->update($data);
                            if ($res) {
                                $data = [
                                    'uid' => $uid,
                                    'noble_id' => $top_noble['id'],
                                    'buy_type' => 2,
                                    'addtime' => NOW_TIME,
                                    'endtime' => $last_time,
                                ];
                                db('user_noble_log')->insertGetId($data);
                            }
                        }
                    } else {
                        $is_noble = 1;
                    }
                } else {
                    $is_noble = 1;
                }
                if ($is_noble == 1) {
                    //renew_coin
                    //dump($last);
                    $data = [
                        'nobility_level' => $noble['id'],
                        'noble_end_time' => $last_time,
                    ];
                    $res = db('user')->where('id = ' . $uid)->update($data);
                    if ($res) {
                        $data = [
                            'uid' => $uid,
                            'noble_id' => $noble['id'],
                            'buy_type' => 0,
                            'addtime' => NOW_TIME,
                            'endtime' => $last_time,
                        ];
                        db('user_noble_log')->insertGetId($data);
                    }
                }
            }

        }
        return 1;
    }
}

function get_geo_hash($lat, $lng)
{
    require_once DOCUMENT_ROOT . '/system/geohash/geohash.class.php';
    $Geohash = new \Geohash();
    $hash = $Geohash->encode($lat, $lng);
    //$geo_hash = substr($hash, 0, 6);
    return $hash;
}

/**
 * 二维数组按照指定字段进行排序
 * @params array $array 需要排序的数组
 * @params string $field 排序的字段
 * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
 */
function arraySequence($array, $field, $sort = 'SORT_DESC')
{
    $arrSort = array();
    foreach ($array as $uniqid => $row) {
        foreach ($row as $key => $value) {
            $arrSort[$key][$uniqid] = $value;
        }
    }
    array_multisort($arrSort[$field], constant($sort), $array);
    return $array;
}

/**
 * 获取当前用户vip等级权限  -- 检测是否有权限
 * $uid 用户id
 * $field 权限名称
 * vip3级会员专属勋章、专属头饰、   进场特效、    麦位声浪、        专属气泡、     房间名片
 * identity_url、headwear_id、approach_id、sound_wave_url、bubble_id、room_card_url
 *  专属昵称、      每日签到福利、 排名靠前、查看访客记录、  任意私聊、       商城优惠、
 *  is_nickname、sign_in_coin、is_rank、is_visitors、is_private_chat、shop_coin
 *   等级加速、          粉丝数上限、      关注数上限、          开启隐身、   禁止跟随、       房间防踢
 *  level_acceleration、maximum_fans、maximum_attention、is_stealth、is_ban_attention、is_kick
 * 昵称颜色
 * colors
 *
 *
 * $user_info = Db::name('user')->field('nobility_level,noble_end_time')->find($uid);
 * $data = [
 * //'player_level_name'=>'',
 * 'noble_name'    => '',
 * 'noble_img'     => '',
 * 'colors'        => '',
 * 'entry_effects' => '',
 * ];
 * if ($user_info['nobility_level'] && $user_info['noble_end_time'] > NOW_TIME) {
 * //贵族信息
 * $noble = Db::name('noble')
 * ->find($user_info['nobility_level']);
 * if ($noble) {
 * //$data['player_level_name'] = $level['name'];
 * $data['noble_name'] = $noble['name'];
 * $data['noble_img'] = $noble['noble_img'];
 * $data['colors'] = $noble['colors'];
 * $data['entry_effects'] = $noble['entry_effects'];
 * }
 * }
 * return $data;
 *
 */
function get_user_vip_authority($uid, $field)
{
    $authority = '';
    return $authority;
}

/**
 * 通过ip获取国家  https://ipinfo.io/
 * https://ipinfo.io/103.153.130.166?token=187f909d0af15b
 *
 * */
function get_country_code()
{
    return '';
//    $config = load_cache('config');
//    if ($config['IP_acquisition_country'] == 1) {
//        $country_code = get_country_ipinfo($config['ipinfo_token']);
//    } else {
//        $country_code = get_country_ip2region();
//    }
//    return $country_code;
}

/**
 * 通过ip获取国家  https://ipinfo.io/
 * https://ipinfo.io/103.153.130.166?token=187f909d0af15b
 *
 * */
function get_country_ipinfo($ipinfo_token)
{
    $ip = get_client_ip();
    $url = "https://ipinfo.io/" . $ip . "?token=" . $ipinfo_token;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $return_str = curl_exec($curl);
    curl_close($curl);

    $country_code = '';
    try {
        // 解码JSON响应
        $details = json_decode($return_str, true);
        $file = file_get_contents(DOCUMENT_ROOT . "/countries.json");
        $countries = json_decode($file, true);
        foreach ($countries as $v) {
            if ($details['country'] == $v['alpha_2_code']) {
                $country_code = $v['num_code'];
            }
        }
    } catch (\Exception $e) {
        return 0;
    }

    return $country_code;
}

/**
 * 通过ip获取当前国家数字编码号
 */
function get_country_ip2region()
{
    $config = load_cache('config');
    // 获取 IP 地址
    require_once(DOCUMENT_ROOT . "/system/ip2region/Ip2Region.php");
    $ip = get_client_ip();
    $country_code = '';
    try {
        $sc = new ip2Region;
        $ipInfo = $sc->memorySearch($ip);
        $region = explode('|', $ipInfo['region']);
        $country_list = json_decode(file_get_contents(DOCUMENT_ROOT . "/country_number.json"), true);
        $country = $region[0]; // array(5) { [0]=>string(6) "美国" [1]=>string(1)"0"[2]=>  string(9) "华盛顿"  [3]=>  string(1) "0"[4]=> string(6) "谷歌"}
        $country_code = $country_list[$country];
    } catch (\Exception $e) {
        return 0;
    }

    return $country_code;
}

/**
 *  使用ip info 根据ip获取国家信息
 * */
function getCountryInfoByIP($ip)
{
    $token = config('sdk_config.ip_info')['token'];
    $ipInfo = file_get_contents("http://ipinfo.io/{$ip}?token=$token");

    return $ipInfo;
}


/**
 * 将一个数组根据指定的key生成新的数组
 * @return array 新的数组
 * */
function getNewArrayKFromV($originalArray, $key)
{
    // 新数组
    $newArray = array();

    // 遍历原始数组
    foreach ($originalArray as $item) {
        if (isset($item[$key])) {
            // 将key和value添加到新数组中
            $newArray[$item[$key]] = $item;
        }
    }

    return $newArray;
}

/**
 * 注销房间
 * */
function close_delete_voice($uid)
{
    // 关闭房间
    $VoiceModel = new VoiceModel();
    $sel_voice = $VoiceModel->sel_voice_user_one($uid);
    //   bogokjLogPrint('logout','sel_voice == '.json_encode($sel_voice));
    if ($sel_voice) {
        // 房主关闭直播间
        // 获取麦位人数列表
        $wheat_logs = $VoiceModel->get_voice_even_wheat_log_list($uid, '-1');
        //    bogokjLogPrint('logout','wheat_logs =='.json_encode($wheat_logs));
        foreach ($wheat_logs as $v) {
            // 解除禁言房间缓存
            redis_hDelOne('ban_voice_' . $uid, $v['user_id']);
            // 删除用户在直播间缓存
            redis_hDelOne("user_voice", $v['user_id']);
            // 删除直播间用户缓存
            voice_del_userlist($uid, $v['user_id']);
        }
        $name = array('status' => 3, 'endtime' => NOW_TIME);
        // 正在连麦的和申请上麦的下麦
        $upd_voice_status = "voice_id=" . $uid . " and ( status=0 or status=1)";

        $ceshi = $VoiceModel->upd_voice_even_wheat_log_status($upd_voice_status, $name);

        require_once DOCUMENT_ROOT . '/system/im_common.php';
        // 销毁群组
        qcloud_group_destroy_group($sel_voice['group_id']);
        //     bogokjLogPrint('logout','add_voice_log =='.json_encode($sel_voice));

        // 关闭房间加入房间记录
        $VoiceModel->add_voice_room_log($sel_voice);
    }

}

/**
 *   处理钻石变更记录
 * uid 用户id coin +-100 钻石数量
 * type 1充值 2聊天 3视频通话 4送礼物 5语音通话 6签到 7任务 8VIP 9装扮 10贵族 11退出公会 12(无)
 * 13(无) 14(幸运礼物) 15兑换 16浇树 17宝箱 18三方游戏 19打招呼(无) 20退出家族 21邀请领取奖励 22新人注册福利 100后台手动操作
 * notes 备注
 * balance 余额 处理之前余额（游戏使用）
 * **/
function save_coin_log($uid, $coin, $coin_type, $type, $notes = '', $balance = 0)
{
    switch ($coin_type) {
        case 2:
            $coin_type_val = 2;
            break;
        case 3:
            $coin_type_val = 3;
            break;
        default:
            $coin_type_val = 1;
    }
    $data = [
        'uid' => $uid,
        'coin' => $coin,
        'coin_type' => $coin_type_val,
        'type' => $type,
        'balance' => $balance,
        'create_time' => NOW_TIME,
        'notes' => $notes
    ];
    //增加变更记录
    return db('user_coin_log')->insertGetId($data);
}

/**
 *   处理钻石变更记录--批量处理
 * **/
function save_coin_all_log($data)
{
    //增加变更记录
    return db('user_coin_log')->insertAll($data);
}

/**
 *   处理收益变更记录
 * uid 用户id coin +-100 钻石数量
 * type 1收背包礼物 2聊天回复对象 3视频通话对方 4收礼物 5语音通话对方 6提现 7任务奖励 13(无) 14(无) 15兑换 100后台手动操作
 * notes 备注
 * **/
function save_income_log($uid, $income, $coin_type, $type, $notes = '')
{
    $data = [
        'uid' => $uid,
        'income' => $income,
        'income_type' => $coin_type == 2 ? 2 : 1,
        'type' => $type,
        'create_time' => NOW_TIME,
        'notes' => $notes
    ];
    //增加变更记录
    return db('user_income_log')->insertGetId($data);
}

/**
 * 通过国家编号获取国家信息
 * */
function get_country_one($country_code)
{
    if ($country_code <= 0) {
        return array(
            'id' => '',
            'name' => '',
            'alpha_2_code' => '',
            'num_code' => '',
            'en_short_name' => '',
            'img' => ''
        );
    }
    $country_code_key = 'country_code';
    $country = redis_hGet($country_code_key, $country_code);
    if (!$country || $country == null) {
        $file = file_get_contents(DOCUMENT_ROOT . "/countries.json");
        $countries = json_decode($file, true);
        $country = array(
            'id' => '',
            'name' => '',
            'alpha_2_code' => '',
            'num_code' => '',
            'en_short_name' => '',
            'img' => ''
        );
        foreach ($countries as $v) {
            $country_val = array(
                'id' => '',
                'name' => $v['en_short_name'],
                'alpha_2_code' => $v['alpha_2_code'],
                'num_code' => $v['num_code'],
                'en_short_name' => $v['en_short_name'],
                'img' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . "/static/countries_flags/" . $v['alpha_2_code'] . ".png"
            );

            redis_hSet($country_code_key, $v['num_code'], json_encode($country_val));
            if ($country_code == $v['num_code']) {
                $country = $country_val;
            }
        }
    } else {
        $country = is_array($country) ? $country : json_decode($country, true);
    }
    return $country;
}

/**
 * 幸运礼物--头奖奖励--随机几位用户
 **/
function lucky_jackpot_rand($user_list, $number, $text)
{
    $randNum = mt_rand(0, $number - 1);
    if (strpos($text, ',' . $user_list[$randNum] . ",") !== false) {
        // 如果存在递归
        $text = lucky_jackpot_rand($user_list, $number, $text);
    } else {
        $text = $text ? $text . $user_list[$randNum] . "," : "," . $user_list[$randNum] . ",";
    }
    return $text;
}

/**
 * 用户奖励
 **/
function add_lucky_jackpot_winners($winners_user, $lucky_gift, $lucky_jackpot_pot_amount, $jackpot_time)
{
    $coin = 0;
    if (count($winners_user)) {

        // 更新奖池
        $coin = count($winners_user) * $lucky_gift['gift_coin'] * $lucky_gift['lucky_jackpot_bonus_multiple'];
        $notes = "更改奖池前:" . $lucky_jackpot_pot_amount['coin'] . "; 变更奖池数量(头奖): -" . $coin;
        $lucky_jackpot_pot_amount['coin'] = $lucky_jackpot_pot_amount['coin'] - $coin > 0 ? $lucky_jackpot_pot_amount['coin'] - $coin : $lucky_jackpot_pot_amount['coin'];
        redis_hSet('lucky_reward_gift', $lucky_gift['gift_id'], json_encode($lucky_jackpot_pot_amount));
        unset($lucky_jackpot_pot_amount['lucky_multiple_array']);
        // 更新奖池
        $res = db('gift_lucky')->where('id=' . $lucky_gift['id'])->update($lucky_jackpot_pot_amount);
        $notes .= ";更改后奖池余额:" . $lucky_jackpot_pot_amount['coin'];
        if ($res) {
            // 增加用户收益记录
            $user_coin = $lucky_gift['gift_coin'] * $lucky_gift['lucky_jackpot_bonus_multiple'];
            foreach ($winners_user as $wv) {
                $gift_lucky_log = db('gift_lucky_log')->where("jackpot_time='" . $jackpot_time . "' and status < 2 and uid=" . $wv)->find();
                $voice_user_id = $gift_lucky_log ? $gift_lucky_log['voice_user_id'] : 0;
                $to_user_info = get_user_base_info($wv);
                // 更新用户收益
                db('user')->where('id', $wv)->setInc('coin', intval($user_coin));
                // 钻石变更记录
                save_coin_log($wv, intval($user_coin), 1, 14);
                $ip = get_client_ip();
                // 操作记录
                upd_user_coin_log($wv, intval($user_coin), 0, 16, 1, 1, $ip, $wv);
                //未中奖记录和中奖记录
                $insert = array(
                    'uid' => $wv,
                    'host_id' => 0,
                    'gift_id' => $lucky_gift['gift_id'],
                    'coin' => 0,
                    'num' => 0,
                    'ticket' => 0,
                    'prize_pool_coin' => 0,
                    'platform_coin' => 0,
                    'winning' => $user_coin,
                    'ratio' => $lucky_gift['lucky_jackpot_bonus_multiple'],
                    'status' => 2,
                    'addtime' => NOW_TIME,
                    'jackpot_time' => $jackpot_time,
                    'user_name' => $to_user_info['user_nickname'],
                    'host_name' => '',
                    'gift_name' => $lucky_gift['name'],
                    'voice_user_id' => $voice_user_id,
                    'notes' => $notes
                );
                db('gift_lucky_log')->insert($insert);
                lucky_send_im($to_user_info, $user_coin, $lucky_gift['lucky_jackpot_bonus_multiple'], $voice_user_id);
            }
        }
    }
    return $coin;
}

/**
 * 幸运礼物大奖 IM 通知
 * @param object $user_info     用户信息
 * @param int    $user_coin     钻石
 * @param int    $multiple      倍数
 * @param int    $voice_user_id 语音用户id
 * @param int    $notice_type   = 0 顶部、1 公屏
 * */
function lucky_send_im($user_info, $user_coin, $multiple, $voice_user_id, $notice_type = 0)
{
    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    $config = load_cache('config');
    // 全站飘屏
    $ext = array();
    $ext['type'] = Enum::LUCKY_FIRST_PRIZE; // 83
    //  was lucky enough to get 500 钻石
    //$text = lang("congratulations_lucky_get", ['username' => " <font color='#89560C' style='font-size:22px;font-weight: bold'>" . emoji_decode($user_info['user_nickname']) . "</font>", 'name' => " <font color='#ffff00' style='font-size:22px;font-weight: bold'>" . $user_coin . "</font>", 'n' => $config['currency_name']]);
    $text = $config['currency_name'] . " back：<font color='#FFD700' style='font-size:22px;font-weight: bold'>" . emoji_decode($user_info['user_nickname']) . "</font>"
        . " Win <font color='#FFD700' style='font-size:22px;font-weight: bold'>$multiple</font> times coins back and get <font color='#FFD700' style='font-size:22px;font-weight: bold'>$user_coin</font> " . $config['currency_name'];
    $data = array(
        'user_id' => $user_info['id'], //发送人昵称
        'user_nickname' => emoji_decode($user_info['user_nickname']),
        'avatar' => $user_info['avatar'],
        'multiple' => $multiple,
        'text' => $text,
        'voice_user_id' => $voice_user_id,
        'notice_type' => $notice_type,
    );
    $ext['data'] = $data;

    #构造高级接口所需参数
    $lucky_msg_content = array();
    //创建array 所需元素
    $lucky_msg_content_elem = array(
        'MsgType' => 'TIMCustomElem',       //自定义类型
        'MsgContent' => array(
            'Data' => json_encode($ext)
        )
    );
    //将创建的元素$msg_content_elem, 加入array $msg_content
    array_push($lucky_msg_content, $lucky_msg_content_elem);
    $re = $api->group_send_group_msg2($config['tencent_identifier'], $config['acquire_group_id'], $lucky_msg_content);
    bogokjLogPrint("lucky_jackpot", "lucky_send_im =" . json_encode($re) . ";ext=" . json_encode($ext));
}

// 充值代理后台生成邀请码
function initcode($id)
{
    // 是否自动生成推广码
    $link = $id . rand_str_number(4);
    return $link;
}