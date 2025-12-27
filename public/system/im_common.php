<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/5/16
 * Time: 22:41
 */

//$config = load_cache('config');
//$sdkappid = $config['tencent_sdkappid'];

//发送广播消息
function push_all_msg($send_info)
{
    $config = load_cache('config');
    $broadMsg['type'] = \app\common\Enum::BROADCAST;
    $sender['id'] = 'admin';
    $sender['user_nickname'] = '系统消息';
    $sender['avatar'] = '';
    $broadMsg['channel'] = 'all'; //通话频道
    $broadMsg['sender'] = $sender;

    $broadMsg['send_info'] = $send_info;
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
    return $ret;
}

function im_check_user_online_state($user_id)
{

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    $ret = $api->check_online_status($user_id);
    return $ret;
}
//IM禁言
function im_shut_up($user_id, $time)
{

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    $ret = $api->shut_up($user_id, $time);
    return $ret;
}

//type 14 语聊系统海外版消息挂断推送
function end_video_call($user_id, $to_user_id, $video_call_info)
{

    $ext['type'] = 14;
    $sender['id'] = $user_id;
    $sender['user_nickname'] = '';
    $sender['avatar'] = '';
    $ext['channel'] = $video_call_info['channel_id']; //通话频道
    $ext['sender'] = $sender;
    $ext['reply_type'] = 1;

    $ser = open_one_im_push($user_id, $to_user_id, $ext);
}

//type 13 语聊系统海外版消息挂断推送
function huang_video_call($user_id, $to_user_id, $video_call_info)
{

    $ext['type'] = 13;
    $sender['id'] = $user_id;
    $sender['user_nickname'] = '';
    $sender['avatar'] = '';
    $ext['channel'] = $video_call_info['channel_id']; //通话频道
    $ext['sender'] = $sender;
    $ext['reply_type'] = 2;

    $ser = open_one_im_push($user_id, $to_user_id, $ext);
}
//type 94 一对一语音通话挂断
function end_sudio_call($user_id, $to_user_id, $video_call_info)
{
    $ext['type'] = \app\common\Enum::CLOSE_VOICE_CALL;
    $sender['id'] = $user_id;
    $sender['user_nickname'] = '';
    $sender['avatar'] = '';
    $ext['channel'] = $video_call_info['channel_id']; //通话频道
    $ext['sender'] = $sender;
    $ext['reply_type'] = 1;

    $ser = open_one_im_push($user_id, $to_user_id, $ext);
}
//type 96 语聊系统海外版通话挂断
function end_video_call_96($user_id, $to_user_id, $video_call_info)
{
    $ext['type'] = \app\common\Enum::CLOSE_VIDEO2_CALL;
    $sender['id'] = $user_id;
    $sender['user_nickname'] = '';
    $sender['avatar'] = '';
    $ext['channel'] = $video_call_info['channel_id']; //通话频道
    $ext['sender'] = $sender;
    $ext['reply_type'] = 1;

    $ser = open_one_im_push($user_id, $to_user_id, $ext);
}
//发送文本消息
function send_c2c_text_msg($user_id, $to_user_id, $msg)
{

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    $api->openim_send_msg($user_id, $to_user_id, $msg);

}

//修改IM用户资料信息
function update_im_user_info($account_id)
{

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

    $api = createTimAPI();

    $user_info = get_user_base_info($account_id, 1);
    #构造高级接口所需参数
    $profile_list = array();
    $profile_nick = array(
        "Tag" => "Tag_Profile_IM_Nick",
        "Value" => $user_info['user_nickname']
    );
    $profile_avatar = array(
        "Tag" => "Tag_Profile_IM_Image",
        "Value" => $user_info['avatar']
    );
    array_push($profile_list, $profile_nick);
    array_push($profile_list, $profile_avatar);

    $ret = $api->profile_portrait_set2((string)$account_id, $profile_list);

    return $ret;
}


function open_one_im_push($account_id, $receiver, $ext)
{
    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

    $msg_content = array();
    //创建array 所需元素
    $msg_content_elem = array(
        'MsgType' => 'TIMCustomElem',       //自定义类型
        'MsgContent' => array(
            'Data' => json_encode($ext),
            'Desc' => '',
        )
    );
    array_push($msg_content, $msg_content_elem);

    $api = createTimAPI();

    $ret = $api->openim_send_msg2($account_id, $receiver, $msg_content);
    //dump($msg_content);exit;
    return $ret;
}

/**
 * 批量发消息(高级接口)
 * @param array $account_list 接收消息的用户id集合
 * @param array $msg_content 消息内容, php构造示例:
 *
 *   $msg_content = array();
 *   //创建array 所需元素
 *   $msg_content_elem = array(
 *       'MsgType' => 'TIMTextElem',       //文本??型
 *       'MsgContent' => array(
 *       'Text' => "hello",                //hello 为文本信息
 *      )
 *   );
 *   //将创建的元素$msg_content_elem, 加入array $msg_content
 *   array_push($msg_content, $msg_content_elem);
 *
 * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
 */
function open_all_im_push($account_list, $ext)
{

    require_once(DOCUMENT_ROOT . '/system/tim/TimRestApi.php');

    $msg_content = array();
    //创建array 所需元素
    $msg_content_elem = array(
        'MsgType' => 'TIMCustomElem',       //自定义类型
        'MsgContent' => array(
            'Data' => json_encode($ext),
            'Desc' => '',
        )
    );
    array_push($msg_content, $msg_content_elem);

    $api = createTimAPI();
    $ret = $api->openim_batch_sendmsg2($account_list, $msg_content);
    return $ret;

}

//设置腾讯云sig，获取API对象
function set_qcloud_user_sig($id = 'admin')
{
    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    return $api;
}

//设置群组
function qcloud_group_create_group($group_type = 'AVChatRoom', $user_id, $user_id1, $video_id)
{
    $api = set_qcloud_user_sig();

    $ret = $api->group_create_group($group_type, $user_id, (string)$user_id1, $video_id);
    return $ret;
}

//删除群组
function qcloud_group_destroy_group($group_id)
{
    $api = set_qcloud_user_sig();
    $ret = $api->group_destroy_group((string)$group_id);
    return $ret;
}

//用户入群
function qcloud_group_add_group_member($group_id, $member_id, $silence = 1)
{
    $api = set_qcloud_user_sig();
    $ret = $api->group_add_group_member($group_id, (string)$member_id, $silence);
    return $ret;
}

//用户退群
function qcloud_group_delete_group_member($group_id, $member_id, $silence = 1)
{
    $api = set_qcloud_user_sig();
    $ret = $api->group_delete_group_member($group_id, (string)$member_id, $silence);
    return $ret;
}

/*
"Data": "message",
"Desc": "notification",
"Ext": "url",
"Sound": "dingdong.aiff"

Data    String  自定义消息数据。 不作为APNS的payload中字段下发，故从payload中无法获取Data字段。
Desc    String  自定义消息描述信息；当接收方为iPhone后台在线时，做iOS离线Push时文本展示。
Ext     String  扩展字段；当接收方为iOS系统且应用处在后台时，此字段作为APNS请求包Payloads中的ext键值下发，Ext的协议格式由业务方确定，APNS只做透传。
Sound   String  自定义APNS推送铃音。

TIMTextElem 文本消息。
TIMLocationElem 地理位置消息。
TIMFaceElem 表情消息。
TIMCustomElem   自定义消息，当接收方为IOS系统且应用处在后台时，此消息类型可携带除文本以外的字段到APNS。注意，一条组合消息中只能包含一个TIMCustomElem自定义消息元素。
TIMSoundElem    语音消息。（服务端集成Rest API不支持发送该类消息）
TIMImageElem    图像消息。（服务端集成Rest API不支持发送该类消息）
TIMFileElem 文件消息。（服务端集成Rest API不支持发送该类消息）

//广播：直播结束
$ext = array();
$ext['type'] = 3; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束
$ext['room_id'] = $room_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
$ext['show_num'] = 0;//观看人数
$ext['fonts_color'] = '';//字体颜色
$ext['desc'] = '主播退出';//弹幕消息;
$ext['desc2'] = '主播退出';//弹幕消息;

#构造高级接口所需参数
$msg_content = array();
//创建array 所需元素
$msg_content_elem = array(
'MsgType' => 'TIMCustomElem',       //自定义类型
'MsgContent' => array(
        'Data' => json_encode($ext),
                'Desc' => '',
)
);
 */
//发送群组通知扩展消息
function qcloud_group_send_group_msg2_ext($account_id, $group_id, $text_content)
{
    $api = set_qcloud_user_sig();
    #构造高级接口所需参数
    $msg_content = array();
    //创建array 所需元素
    $msg_content_elem = array(
        'MsgType' => 'TIMCustomElem',       //文本类型TIMTextElem
        'MsgContent' => array(
            'Data' => json_encode($text_content),                //hello 为文本信息
        )
    );
    array_push($msg_content, $msg_content_elem);
    // dump($msg_content);die();
    $ret = $api->group_send_group_msg2((string)$account_id, $group_id, $msg_content);
    return $ret;
}

/**
 * 获取推流地址
 * 如果不传key和过期时间，将返回不含防盗链的url
 * @param domain 您的推流域名
 *        streamId 您用来区别不同推流地址的唯一流ID
 *        key 安全密钥
 *        time 过期时间 sample 2016-11-12 12:00:00
 * @return String url
 */

function getPushUrl($domain, $streamId, $key = null, $time = null)
{
    $streamId = get_pk_stream($streamId);
    if ($key && $time) {
        $txTime = strtoupper(base_convert(strtotime($time), 10, 16));
        $txSecret = md5($key . $streamId . $txTime);
        $extStr = "?" . http_build_query(array(
                "txSecret" => $txSecret,
                "txTime" => $txTime
            ));
    }
    return "rtmp://" . $domain . "/live/" . $streamId . (isset($extStr) ? $extStr : "");
    //return "rtmp://".$domain."/live/".$streamId;
}

//echo getPushUrl("pushtest.com", "123456", "69e0daf7234b01f257a7adb9f807ae9f", "2016-09-11 20:08:07");

/**
 * 获取播放地址
 * @param domain 您的播放域名
 *        streamId 您用来区别不同推流地址的唯一流ID
 * @return string[] url
 */

function getPlayUrl($domain, $streamId)
{
    $streamId = get_pk_stream($streamId);
    return array(
        'rtmp' => "rtmp://" . $domain . "/live/" . $streamId,
        'flv' => "http://" . $domain . "/live/" . $streamId . ".flv",
        'hls' => "http://" . $domain . "/live/" . $streamId . ".m3u8"
    );
}

//echo getPlayUrl("playtest.com","123456");


//http://fcgi.video.qcloud.com/common_access?appid=1252500000&interface=Mix_StreamV2&t=t&sign=sign
//混流
function get_pk_stream($lid1 = '', $lid2 = '')
{
    return md5($lid1 . $lid2);
}

function get_pk_url($lid1, $lid2)
{
    $time = time();
    $config = load_cache('config');
    $appid = $config['qcloud_appid'];
    //echo $appid;die();
    $key = $config['qcloud_api_key'];
    $domainPull = $config['qcloud_pull_url'];
    $t = $time + 60;
    $signStr = $key . $t;
    $sign = md5($signStr);
    $url = 'http://fcgi.video.qcloud.com/common_access?appid=' . $appid . '&interface=Mix_StreamV2&sign=' . $sign . '&t=' . $t;
    //echo md5($key. '1554808525');die();
    //http://fcgi.video.qcloud.com/common_access?appid=1251470018&interface=Mix_StreamV2&sign=0a108774815c458e672555cb311e2b09&t=1554808525
    //http://fcgi.video.qcloud.com/common_access?appid=1251470018&interface=Mix_StreamV2&sign=428eac1c87c2d4a9344f43c0547af1a4&t=1554808723

    //http://fcgi.video.qcloud.com/common_access?appid=1251470018&interface=Mix_StreamV2&sign
    //=4fd9dd76eee856bd2aabad65693a9c77&t=1554808907
    //echo $url;
    $data = array();
    $data['timestamp'] = $time;
    $data['eventId'] = $time;
    $data['interface']['interfaceName'] = 'Mix_StreamV2';
    $data['interface']['para']['interface'] = 'mix_streamv2.start_mix_stream_advanced';
    $data['interface']['para']['app_id'] = $appid;
    $data['interface']['para']['domain'] = '';
    $data['interface']['para']['path'] = '';
    $data['interface']['para']['mix_stream_template_id'] = 390;
    $data['interface']['para']['mix_stream_session_id'] = 'stream_session' . $time . get_pk_stream($lid1, $lid2);//md5
    //($lid1 .
    // $lid2);
    $output_stream_id = 'out_' . $time . '_' . get_pk_stream($lid1, $lid2);//md5($lid1 . $lid2);
    $data['interface']['para']['output_stream_id'] = $output_stream_id;


    /*
        output_stream_type不填默认为0;
        当输出流为输入流list中的一条时，填写0；
        当期望生成的混流结果成为一条新流时，该值填为1;
        该值为1时，output_stream_id不能出现在input_stram_list中，且直播后台中，不能存在相同id的流。
     * */
    $data['interface']['para']['output_stream_type'] = 1;

    $data['interface']['para']['input_stream_list'][0]['input_stream_id'] = 'canvas1';
    $data['interface']['para']['input_stream_list'][1]['input_stream_id'] = get_pk_stream($lid1);
    $data['interface']['para']['input_stream_list'][2]['input_stream_id'] = get_pk_stream($lid2);

    $data['interface']['para']['input_stream_list'][0]['layout_params']['image_layer'] = 1;
    $data['interface']['para']['input_stream_list'][0]['layout_params']['input_type'] = 3;
    $data['interface']['para']['input_stream_list'][0]['layout_params']['image_width'] = 400;
    $data['interface']['para']['input_stream_list'][0]['layout_params']['image_height'] = 640;
    $data['interface']['para']['input_stream_list'][0]['layout_params']['color'] = "0x000000";

    $data['interface']['para']['input_stream_list'][1]['layout_params']['image_layer'] = 2;
    $data['interface']['para']['input_stream_list'][2]['layout_params']['image_layer'] = 3;
    //$rejson = to_post($url, json_encode($data));

    $rejson = post(json_encode($data), $url);
    //echo $output_stream_id . '~~~~~~' .$rejson;die();
    $re = json_decode($rejson, true);
    if ($re['code'] == 0) {
        return getPlayUrl($domainPull, $output_stream_id);
    } else {
        $info = array('code' => 0, 'msg' => $re['message'], 'data' => array());
        return_json_encode($info);
    }
}
