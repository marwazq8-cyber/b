<?php
/**
 * Created by PhpStorm.
 * User: weipeng  kj
 * Date: 2018/3/4
 * Time: 22:14
 */

function is_two_dimensional_array($array)
{
    // 检查是否是数组
    if (!is_array($array)) {
        return false;
    }

    // 检查数组中的每个元素是否也是数组
    foreach ($array as $element) {
        if (!is_array($element)) {
            return false;
        }
    }

    return true;
}

//检查token是否过期
function check_login_token($uid, $token, $field = array())
{
    if ($uid == 0 || empty($token)) {
        $result['code'] = 0;
        $result['msg'] = lang('Parameter_transfer_error');
        return_json_encode($result);
    }
    //array_push($field,'device_uuid');
    $user_info = check_token($uid, $token, $field);
    //dump($user_info);die();
    if (!$user_info) {
        $result['code'] = 10001;
        $result['msg'] = lang('login_timeout');
        return_json_encode($result);
    }

    //账号是否被禁用
    if ($user_info['user_status'] == 0) {
        $result['code'] = 0;
        $result['msg'] = lang('Due_to_suspected_violation');
        return_json_encode($result);
    }
    //设备是否被封禁
    $device = db('equipment_closures')->where('device_uuid', $user_info['device_uuid'])->find();
    if ($device) {
        $result['code'] = 0;
        $result['msg'] = lang('Due_to_suspected_violation');
        return_json_encode($result);
    }
    // 是否禁封ip地址
    $ip = request()->ip();
    $is_ip = db('close_ip')->where('ip', $ip)->find();
    if ($is_ip) {
        $result['code'] = 0;
        $result['msg'] = "您因涉嫌违规，IP:" . $ip . "账号受限，请联系管理员!";
        return_json_encode($result);
    }
    return $user_info;
}

//参数检查
function check_param($param)
{
    $result['code'] = 0;
    $result['msg'] = lang('Parameter_transfer_error') . $param;
    $data = array('msg' => lang('Parameter_transfer_error'), 'error' => $param);

    return_json_encode($result);
}

//封装json_encode()
function return_json_encode($result)
{
    json($result)->send();
    //echo json_encode($result);
    exit;
}

//封装json_encode()
function return_json_encode_data($data, $code = 1, $msg = '')
{
    $result = ['data' => $data, 'code' => $code, 'msg' => $msg];
    return_json_encode($result);
}

//获取配置信息
function get_config()
{
    $config_res = db('config')->select();
    //var_dump($config_res);exit;
    $config = array_reduce($config_res, function (&$config, $v) {
        $config[$v['code']] = $v;
        return $config;
    });

    return $config;
}

//检测token
function check_token($user_id, $token, $field = [])
{

    $base_field = 'user_nickname,id,age,coin,sex,avatar,user_status,income_level,level,city,province,signature,is_auth,is_player,is_talker,device_uuid,country_code,charm_values_total,consumption_total';
    if (is_array($field) && count($field) > 0) {
        $base_field .= ',' . implode(',', $field);
    }

    $user_info = db('user')
        ->field($base_field)
        ->where(['id' => $user_id, 'token' => $token])
        ->find();

    redis_hSet("user_list", $user_id, json_encode($user_info));
    return $user_info;
}

//完善资料查询邀请人进行奖励
function reg_invite_perfect_info_service($uid, $sex)
{
    $config = load_cache('config');
    $invite_record = db('invite_record')->where('invite_user_id', '=', $uid)->find();
    //查询是否已经有过奖励

    $reward = $sex == 1 ? $config['invite_reg_reward_man'] : $config['invite_reg_reward_female'];

    //如果管理员填写空会报错
    if (empty($reward)) {
        $reward = 0;
    }

    if ($invite_record) {
        $exits = db('invite_profit_record')->where('user_id', '=', $invite_record['user_id'])->where('invite_user_id', '=', $uid)->where('c_id', '=', 6)->find();
        if ($exits) {
            return;
        }
        $record = [
            'user_id' => $invite_record['user_id'],
            'invite_user_id' => $uid,
            'c_id' => 6,
            'income' => 0,
            'invite_code' => $invite_record['invite_code'],
            'create_time' => NOW_TIME,
            'money' => $reward,
            'type' => 0,
        ];

        db('invite_profit_record')->insert($record);
        db('user')->where('id', '=', $invite_record['user_id'])->inc('invitation_coin', $reward)->update();
    }
}

function get_format_money($num)
{
    $pat = '/(\d+\.\d{5})\d*/';
    return preg_replace($pat, "\${1}", $num);
}

//获取鉴权视频链接
function get_sign_video_url($key, $video_url)
{

    $parse_url_arr = parse_url($video_url);
    $url_dir = substr($parse_url_arr['path'], 0, strrpos($parse_url_arr['path'], '/') + 1);
    $t = dechex(time() + 60 * 60 * 24);
    $us = rand_str();
    $sign = md5($key . $url_dir . $t . $us);

    $sign_video_url = $video_url . '?t=' . $t . '&us=' . $us . '&sign=' . $sign;

    return $sign_video_url;
}


function load_cache($key, $param = array(), $is_real = true)
{

    $file = ROOT_PATH . "public/system/cache/" . $key . ".auto_cache.php";
    require_once $file;
    $class = $key . "_auto_cache";

    $obj = new $class;
    $result = $obj->load($param, $is_real);
    return $result;
}

function load_cache_rm($key, $param = array())
{
    $file = ROOT_PATH . "public/system/cache/" . $key . ".auto_cache.php";
    require_once $file;
    $class = $key . "_auto_cache";

    $obj = new $class;
    $result = $obj->rm($param);
    return $result;
}

//PHP把秒转换成小时数和分钟 ：时间转换
function secs_to_str($secs)
{
    $r = '';
    if ($secs >= 3600) {
        $hours = floor($secs / 3600);
        $secs = $secs % 3600;
        $r = $hours . ' 时';
        if ($hours <> 1) {
            $r .= '';
        }
        if ($secs > 0) {
            $r .= ', ';
        }
    }
    if ($secs >= 60) {
        $minutes = floor($secs / 60);
        $secs = $secs % 60;
        $r .= $minutes . ' 分';
        if ($minutes <> 1) {
            $r .= '';
        }
        if ($secs > 0) {
            $r .= '';
        }
    }
    $r .= $secs;
    if ($secs <> 1) {
        $r .= '秒';
    }
    return $r;
}

function get_oss_file_path($path)
{
    $file_name = parse_url($path)['path'];
    $file_name = substr($file_name, 1, strlen($file_name));
    return $file_name;
}

function bugu_request_file($path)
{

    require_once $path;
}

//生成随机字符串
function rand_str_number($len = 8)
{
    $chars = [
        "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    ];
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

//生成随机字符串
function rand_str($len = 8)
{
    $chars = [
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    ];
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

function time_trans($the_time)
{
    $now_time = time();
    $show_time = $the_time;

    $dur = $now_time - $show_time;

    if ($dur < 60) {
        return $dur . lang('Seconds_ago');
    } else if ($dur < 3600) {
        return floor($dur / 60) . lang('Minutes_ago');
    } else if ($dur < 86400) {
        return floor($dur / 3600) . lang('Hours_ago');
    } else if ($dur < 259200) {
        //3天内
        return floor($dur / 86400) . lang('Days_ago');
    } else {
        return date('Y-m-d', $the_time);
    }
}

//对emoji表情转义
function emoji_encode($str)
{
    $strEncode = '';

    $length = mb_strlen($str, 'utf-8');

    for ($i = 0; $i < $length; $i++) {
        $_tmpStr = mb_substr($str, $i, 1, 'utf-8');
        if (strlen($_tmpStr) >= 4) {
            $strEncode .= '[[EMOJI:' . rawurlencode($_tmpStr) . ']]';
        } else {
            $strEncode .= $_tmpStr;
        }
    }

    return $strEncode;
}

//对emoji表情转反义
function emoji_decode($str)
{
    $strDecode = preg_replace_callback('|\[\[EMOJI:(.*?)\]\]|', function ($matches) {
        return rawurldecode($matches[1]);
    }, $str);

    return $strDecode;
}


//用户进入语音直播间缓存
function set_voice_userlist($voiceid, $uid, $value)
{
    $uservoicelist = redis_hGet('voice_list_' . $voiceid, $uid);
    if (!$uservoicelist) {
        redis_hSet('voice_list_' . $voiceid, $uid, $value);
    }
    return true;
}


//获取语音房间所有的用户列表键值
function voice_userlist_arsort($voiceid)
{
    $uservoicelist = redis_hVals('voice_list_' . $voiceid);
    return $uservoicelist;
}

//删除语音直播间用户缓存
function voice_del_userlist($voiceid, $uid)
{
    redis_hDelOne('voice_list_' . $voiceid, $uid);
    return true;
}

//获取房间总人数
function voice_userlist_sum($voiceid)
{
    $sum = redis_hLen('voice_list_' . $voiceid);
    return $sum ? $sum : 0;
}


/*
 * Redis  //写入缓存
 *
 * */

function redis_hSet($key, $hashKey, $value)
{
    return $GLOBALS['redis']->hSet($key, $hashKey, $value);
}

//获取
function redis_hGet($key, $hashKey = '')
{
    if ($hashKey) {
        return $GLOBALS['redis']->hGet($key, $hashKey);
    } else {
        return $GLOBALS['redis']->hvals($key);
    }

}

//获取数量
function redis_hLen($key)
{
    return $GLOBALS['redis']->hLen($key);
}

// 获取所有的键名
function redis_hkeys($key)
{
    return $GLOBALS['redis']->hKeys($key);
}

/**
 * redis 哈希表(hash)类型 HKEYS
 * 返回哈希表 $key 中，所有的域和值。
 * @param $key
 */
function redis_hGetAll($key)
{
    return $GLOBALS['redis']->hGetAll($key);
}

/*
*获取某个hash表所有字段值。
**/
function redis_hVals($key)
{

    return $GLOBALS['redis']->hVals($key);
}

/**
 * 删除哈希表key中的一个指定域，不存在的域将被忽略。
 * @param $key
 * @param $field
 * @return BOOL TRUE in case of success, FALSE in case of failure
 */
function redis_hDelOne($key, $field)
{

    return $GLOBALS['redis']->hdel($key, $field);
}

function redis_locksleep_nx($key, $value)
{
    do {  //使用循环 判断锁
        $isLock = redis_lock_nx($key, $value);
        if ($isLock == false) {
            usleep(5000);
        } else {
            continue;
        }
    } while ($isLock == false);
}


// 加锁
function redis_lock_nx($key, $value, $time = 10)
{
    return $GLOBALS['redis']->set_lock($key, $value, $time);
}

// 存入缓存
function save_set($key, $value, $exprie = 0)
{
    return $GLOBALS['redis']->set($key, $value, $exprie);
}

// 解锁
function redis_unlock_nx($key)
{
    return $GLOBALS['redis']->del($key);
}

// 判断锁 redis_lock_nx
function redis_islock_nx($key)
{
    return $GLOBALS['redis']->get($key);
}

// 队列 --右进插入列表尾部
function redis_RPush($key, $val)
{
    return $GLOBALS['redis']->RPush($key, $val);
}

// 队列 --左出 读取列表顶部
function redis_lPop($key)
{
    return $GLOBALS['redis']->lPop($key);
}

// 自增
function add_incr($key, $val = 1)
{
    return $GLOBALS['redis']->incr($key, $val);
}

//获取七牛存储配置
function get_qiniu_config()
{

    $qiniu_config = db('plugin')->where('name', '=', 'Qiniu')->find();
    if ($qiniu_config) {
        $qiniu_config = json_decode($qiniu_config['config'], true);
        $qiniu_config['domain'] = $qiniu_config['protocol'] . '://' . $qiniu_config['domain'];
    } else {
        $qiniu_config = [
            'accessKey' => '',
            'secretKey' => '',
            'bucket' => '',
            'domain' => '',
        ];
    }
    return $qiniu_config;
}

function get_domain()
{
    /* 协议 */
    $protocol = get_http();

    /* 域名或IP地址 */
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        /* 端口 */
        if (isset($_SERVER['SERVER_PORT'])) {
            $port = ':' . $_SERVER['SERVER_PORT'];

            if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                $port = '';
            }
        } else {
            $port = '';
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'] . $port;
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $host = $_SERVER['SERVER_ADDR'] . $port;
        }
    }

    return $protocol . $host;
}

function get_http()
{
    return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
}


/**
 * $url 第三方请求地址
 * $post_data 传值参数
 * 装post请求第三方数据
 * @param $url
 * @param $post_data
 * @return mixed
 */
function tripartite_post($url, $post_data, $headers = [])
{
    //发送post请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    // 判断是否是https，用来解决https证书不被信任的问题
    if (stripos($url, "https://") !== false) {
        // 禁用后cURL将终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 	使用的SSL版本(2 或 3)
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    $data = json_decode($result, true);
    return $data;
}

//根据时间获取星座
function get_constellation($birthday)
{

    $month = date('m', $birthday);
    $day = date('d', $birthday);
    $constellation = '';
    // 检查参数有效性
    if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
        return $constellation;
    }

    if (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) {
        $constellation = lang('aquarius'); // 水瓶
    } else if (($month == 2 && $day >= 19) || ($month == 3 && $day <= 20)) {
        $constellation = lang('Pisces'); // 双鱼
    } else if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) {
        $constellation = lang('Aries'); // 白羊
    } else if (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) {
        $constellation = lang('Taurus'); //金牛
    } else if (($month == 5 && $day >= 21) || ($month == 6 && $day <= 21)) {
        $constellation = lang('Gemini'); // 双子
    } else if (($month == 6 && $day >= 22) || ($month == 7 && $day <= 22)) {
        $constellation = lang('Cancer'); //巨蟹
    } else if (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) {
        $constellation = lang('leo'); //狮子
    } else if (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) {
        $constellation = lang('Virgo'); //处女
    } else if (($month == 9 && $day >= 23) || ($month == 10 && $day <= 23)) {
        $constellation = lang('libra'); //天秤
    } else if (($month == 10 && $day >= 24) || ($month == 11 && $day <= 22)) {
        $constellation = lang('scorpio'); //天蝎
    } else if (($month == 11 && $day >= 23) || ($month == 12 && $day <= 21)) {
        $constellation = lang('sagittarius'); //射手
    } else if (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) {
        $constellation = lang('Capricornus'); //摩羯
    }

    return $constellation;
}


/**
 * php显示指定长度的字符串，超出长度以省略号(...)填补尾部显示
 * @ str 字符串
 * @ len 指定长度
 * @param     $str
 * @param int $len
 * @return string
 */
function cutSubstr($str, $len = 3)
{
    if (strlen($str) > $len) {
        $str = mb_substr($str, 0, $len) . '...';
    }
    return $str;
}
