<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/1
 * Time: 15:20
 */

namespace app\api\controller;

use app\api\model\GiftModel;
use app\api\model\SmsModel;
use Overtrue\EasySms\Exceptions\Exception;
use QCloud\COSSTS\Sts;
use Qiniu\Auth;
use think\Config;
use think\Db;
use Closure;
use Overtrue\EasySms\EasySms;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class AppApi extends Base
{
    //获取七牛上传token
    public function get_qiniu_upload_token()
    {
        header('Access-Control-Allow-Origin:*');
        $result = array('code' => 1, 'msg' => '');

        // $this->checkLoginToken(['image_label']);

        $qiniu_config = get_qiniu_config();

        require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $qiniu_config['accessKey'];
        $secretKey = $qiniu_config['secretKey'];
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $result['bucket'] = $qiniu_config['bucket'];
        $result['domain'] = $qiniu_config['domain'];
        $result['token'] = $auth->uploadToken($result['bucket']);

        return_json_encode($result);
    }

    public function get_qcloud_update_token()
    {
        header('Access-Control-Allow-Origin:*');
        $result = array('code' => 1, 'msg' => '');

        $ten_info = db('upload_set')->where('type = 1')->find();

        if ($ten_info) {
            require_once DOCUMENT_ROOT . '/system/qcloud_upload_sdk/qcloud-cos-sts-php-sdk/src/Sts.php';

            $SecretId = $ten_info['secret_id'];
            $SecretKey = $ten_info['secret_key'];
            $region = $ten_info['region'];
            $bucket_url = $ten_info['url'];
            $bucket = $ten_info['bucket'];
            $allowPrefix = '*';
            $sts = new Sts();
            $config = array(
                'url'             => 'https://sts.tencentcloudapi.com/',
                'domain'          => 'sts.tencentcloudapi.com', // 域名，非必须，默认为 sts.tencentcloudapi.com
                'proxy'           => '',
                'secretId'        => $SecretId, // 固定密钥
                'secretKey'       => $SecretKey, // 固定密钥
                'bucket'          => $bucket, // 换成你的 bucket
                'region'          => $region, // 换成 bucket 所在园区
                'durationSeconds' => 1800, // 密钥有效期
                'allowPrefix'     => $allowPrefix, // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
                // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
                'allowActions'    => array(
                    // 简单上传
                    'name/cos:PutObject',
                    'name/cos:PostObject',
                    // 分片上传
                    'name/cos:InitiateMultipartUpload',
                    'name/cos:ListMultipartUploads',
                    'name/cos:ListParts',
                    'name/cos:UploadPart',
                    'name/cos:CompleteMultipartUpload'
                )
            );
            $tempKeys = $sts->getTempKeys($config);
            //dump($tempKeys);die();
            if (isset($tempKeys['credentials'])) {
                $result['sessionToken'] = $tempKeys['credentials']['sessionToken'];
                $result['tmpSecretId'] = $tempKeys['credentials']['tmpSecretId'];
                $result['tmpSecretKey'] = $tempKeys['credentials']['tmpSecretKey'];
                $result['bucket'] = $bucket;
                $result['domain'] = $bucket_url;
                $result['region'] = $region;
                $result['expiredTime'] = $tempKeys['expiredTime'];
                $result['startTime'] = $tempKeys['startTime'];
                $result['requestId'] = $tempKeys['requestId'];
                $result['allowPrefix'] = $allowPrefix;
                //echo date('Y-m-d H:i:s',$tempKeys['expiredTime']);die();
            }
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Tencent_cloud_upload_is_not_configured');
        }

        return_json_encode($result);
    }

    public function get_upload_type()
    {
        $result = array('code' => 1, 'msg' => '');
        $storage = Db::name('option')->where("option_name = 'storage'")->value('option_value');
        if ($storage) {
            $json = json_decode($storage, true);
            if ($json['type'] = 'Tencent') {
                $upload_type = 2;
            } else {
                $upload_type = 1;
            }
        } else {
            $upload_type = 1;
        }
        $result['upload_type'] = $upload_type;
        return_json_encode($result);
    }

    //配置文件
    public function config()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $config = load_cache('config');
        //上传方式
        $storage = Db::name('option')->where("option_name = 'storage'")->value('option_value');
        if ($storage) {
            $json = json_decode($storage, true);
            if ($json['type'] == 'Tencent') {
                $upload_type = 2;
            } else {
                $upload_type = 1;
            }
        } else {
            $upload_type = 1;
        }
        $data['upload_type'] = $upload_type;
        //苹果支付开关
        $data['ios_pay_switch'] = $config['ios_pay_switch'];

        // 心跳
        $data['heartbeat'] = $config['heartbeat_interval'];
        $data['group_id'] = $config['acquire_group_id'];
        $data['sdkappid'] = $config['tencent_sdkappid'];
        $data['accountType'] = $config['accountType_one'];
        $data['private_photos'] = $config['private_photos'];
        $data['app_qgorq_key'] = $config['app_qgorq_key'];
        $data['video_deduction'] = $config['video_deduction'];
        $data['tab_live_heart_time'] = $config['tab_live_heart_time'];
        $data['system_message'] = $config['system_message'];
        $data['currency_name'] = $config['currency_name'];

        //系统赠送金币名称
        $data['system_currency_name'] = $config['system_currency_name'];
        // 榜单显示0 全部 1当月 2当周 3当日
        $data['voice_rank_type'] = $config['voice_rank_type'];
        //上传动态是否需要认证 1是
        $data['upload_bzone_auth'] = $config['upload_bzone_auth'];
        //上传视频是否需要认证 1是
        $data['upload_video_auth'] = $config['upload_video_auth'];
        // 是否收集手机型号
        $data['collection_model_status'] = $config['collection_model_status'];
        // 是否开启语音直播
        $data['is_open_voice'] = 1;
        $data['request_heartbeat_time'] = $config['voice_heartbeat_time'];
        // 开启语音直播是否需要认证
        $data['voice_live_is_auth'] = $config['voice_live_is_auth'];
        // 房间魅力值统计方式
        $data['voice_charm_type'] = $config['voice_charm_type'];
        /*---------0608新增-----------*/
        $data['is_open_chat_pay'] = $config['is_open_chat_pay'];
        $data['private_chat_money'] = $config['private_chat_money'];
        $data['video_call_msg_alert'] = $config['video_call_msg_alert'];
        /*--------------------*/

        /*----------1004新增----------------*/

        $data['open_login_qq'] = $config['open_login_qq'];
        $data['open_login_wx'] = $config['open_login_wx'];
        $data['open_login_facebook'] = $config['open_login_facebook'];
        $data['open_app_time'] = $config['open_app_time'];//开屏时长
        $data['system_log'] = $config['system_log'];//网站log
        $data['open_pay_pal'] = 0;
        $data['pay_pal_client_id'] = '';
        if (defined('OPEN_PAY_PAL') && OPEN_PAY_PAL == 1) {
            $data['open_pay_pal'] = 1;
            $data['pay_pal_client_id'] = $config['pay_pal_client_id'];
        }

        $data['open_sandbox'] = 0;
        if (defined('OPEN_SANDBOX') && OPEN_SANDBOX == 1) {
            $data['open_sandbox'] = 1;
        }

        $data['open_auto_see_hi_plugs'] = 0;

        /*  非好友私信聊天收费  */
        $data['no_friends_chat_charge_coin'] = $config['talker_chat_charge_coin'];
        /* 加速匹配消费金额 */
        $data['accelerate_matching'] = $config['accelerate_matching'];
        /* 多人房间房主分成设置(%) */
        $data['more_room_partition'] = $config['more_room_partition'];

        /*----------1017新增----------------*/
        $data['share_title'] = $config['share_title'];
        $data['share_content'] = $config['share_content'];

        /*----------新人福利----------------*/
        $virtual_coin = db("config")->where(" code='system_coin_registered'")->field("val,virtual_coin")->find();

        $data['system_coin_registered'] = $config['system_coin_registered'];
        // 新人福利单位
        if ($virtual_coin) {
            if ($virtual_coin['virtual_coin'] == 1) {
                $data['system_coin_registered_unit'] = $config['currency_name'];
            } elseif ($virtual_coin['virtual_coin'] == 2) {
                $data['system_coin_registered_unit'] = $config['virtual_currency_earnings_name'];
            } else {
                $data['system_coin_registered_unit'] = '';
            }
        }
        $data['virtual_currency_earnings_name'] = $config['virtual_currency_earnings_name'];


        $data['share_content'] = $config['share_content'];

        /*---------------------------------*/
        $data['is_show_ios_version_number'] = $config['is_show_ios_version_number'];
        //短视频上传时长限制（秒）
        $data['upload_short_video_time_limit'] = $config['upload_short_video_time_limit'];

        $data['upload_certification'] = $config['upload_certification'];
        //踢人时间
        if ($config['kicking_time'] > 0) {
            $data['kicking_time'] = $config['kicking_time'] / 60 > 1 ? round($config['kicking_time'] / 60) . "小时" : $config['kicking_time'] . "分钟";
        } else {
            $data['kicking_time'] = '';
        }
        //是否强制绑定手机号
        $data['is_binding_mobile'] = $config['is_binding_mobile'];

        //鉴黄设置
        $data['is_open_check_huang'] = 0;
        if (isset($config['is_open_check_huang'])) {
            $data['is_open_check_huang'] = $config['is_open_check_huang'];
        }

        $data['check_huang_rate'] = 10;
        if (isset($config['check_huang_rate']) && $config['check_huang_rate'] > 0) {
            $data['check_huang_rate'] = $config['check_huang_rate'];
        }

        //认证类型
        if (isset($config['auth_type'])) {
            $data['auth_type'] = $config['auth_type'];
        }

        //布谷科技美颜SDK密钥
        if (isset($config['bogokj_beauty_sdk_key'])) {
            $data['bogokj_beauty_sdk_key'] = $config['bogokj_beauty_sdk_key'];
        }

        $data['open_select_contact'] = 0;

        //实名认证协议
        $portal = db("portal_category_post")->alias('a')
            ->where(" a.status=1 and b.post_type=1 and b.post_status=1 and c.name='实名认证'")
            ->join("portal_category c", "c.id=a.category_id")
            ->join("portal_post b", "b.id=a.post_id")
            ->field("b.id,b.post_title")
            ->find();

        if (!empty($portal['post_title'])) {
            $data['real_name_authentication'] = $portal['post_title'];
        } else {
            $data['real_name_authentication'] = '';
        }

        //联系方式
        $contact = db("portal_category_post")->alias('a')
            ->where(" a.status=1 and b.post_type=1 and b.post_status=1 and c.name='联系方式'")
            ->join("portal_category c", "c.id=a.category_id")
            ->join("portal_post b", "b.id=a.post_id")
            ->field("b.id")
            ->find();
        //客户端h5链接
        $data['app_h5'] = array(
            'newbie_guide'           => SITE_URL . '/api/novice_guide_api/index', //新手引导
            'binding_account'        => SITE_URL . '/api/userinfo_api/binding_account', //绑定 用户uid   token
            'my_detail'              => SITE_URL . '/api/detail_api/defaults', //我的明细     用户uid 不填是所有的 1聊币2积分
            'private_clause_url'     => SITE_URL . '/api/novice_guide_api/content/id/7.html', //隐私条款
            'user_clause_url'        => SITE_URL . '/api/novice_guide_api/content/id/33.html', //用户协议
            'user_withdrawal'        => SITE_URL . '/api/withdrawal_api/index',
            //我的守护
            'my_guardian'            => SITE_URL . '/api/guardian_api/guardian',
            //守护主播列表(传值hostid 主播id)
            'guardian_list'          => SITE_URL . '/api/guardian_api/index',
            //实名认证协议
            'real_name_url'          => SITE_URL . '/api/novice_guide_api/content/id/' . empty($portal['id']) ? '' : $portal['id'],
            'medal_url'              => SITE_URL . "/api/medal_api/index",    //购买勋章
            //'invited_share_url' =>  SITE_URL . "/api/invited_share_api/index",    //分享邀请收益  uid
            'share_withdrawal_url'   => SITE_URL . "/api/invited_share_api/withdrawal",    //邀请分享收益 提现  uid token
            'voice_revenue_url'      => SITE_URL . "/api/userinfo_api/voice_revenue/",    //房间收益导出 传参 房间id
            //     'exchange_url' => SITE_URL . "/api/exchange_api/integral",    //收益兑换钻石 uid token
            'exchange_url'           => SITE_URL . VUE_URL . "/#/exchange_list",    //收益兑换钻石 uid token
            'luck_url'               => SITE_URL . "/api/luck_api/index",    //靓号 uid token
            // 关于我们
            'about_me'               => SITE_URL . '/portal/article/index/id/6.html',
            // 系统消息 uid token
            'system_message'         => SITE_URL . VUE_URL . '/#/system_message',
            //邀请好友 用户uid token
            'invite_friends'         => SITE_URL . VUE_URL . '/#/invite',
            'invited_share_url'      => SITE_URL . VUE_URL . '/#/invite',
            //装饰中心 用户uid token     dress_up
            'dress_up'               => SITE_URL . VUE_URL . '/#/disguise',
            //我的装饰中心 用户uid token   my_dress
            'my_dress'               => SITE_URL . VUE_URL . '/#/disguise_bag',
            //贵族中心 用户uid token
            'noble_url'              => SITE_URL . VUE_URL . '/#/noble',
            //粉丝 用户uid token
            'fans_url'               => SITE_URL . VUE_URL . '/#/fans',
            //关注 用户uid token
            'attention_url'          => SITE_URL . VUE_URL . '/#/attention',
            //帮助
            'help_url'               => SITE_URL . VUE_URL . '/#/help',
            //我的等级 用户uid token
            'anchor_level'           => SITE_URL . VUE_URL . '/#/wealth_level',
            'my_level'               => SITE_URL . VUE_URL . '/#/wealth_level',//uid token
            //订单消息 用户uid token
            'system_order'           => SITE_URL . VUE_URL . '/#/system_order',
            //活动消息 用户uid token
            'system_activity'        => SITE_URL . VUE_URL . '/#/system_activity',
            //意见反馈
            'user_feedback'          => SITE_URL . VUE_URL . '/#/feedback',
            //了解密友
            'friend_info'            => SITE_URL . VUE_URL . '/#/friend_info',
            //工会
            'my_guild'               => SITE_URL . VUE_URL . '/#/my_guild',
            //充值协议
            'recharge_agreement_url' => SITE_URL . '/api/novice_guide_api/content/id/13.html',
            'receive_gift_log_url'   => SITE_URL . VUE_URL . '/#/receive_coin_gift_log',
            // vip等级
            'vip_level'              => SITE_URL . VUE_URL . '/#/noble',
            // 公会申请说明
            'guild_url'              => SITE_URL . VUE_URL . '/#/GuildDescription',
            // 排行榜
            'ranking_url'            => SITE_URL . '/api/novice_guide_api/content/id/65.html',
            // 绑定提现银行卡 uid token
            'bank_url'               => SITE_URL . VUE_URL . '/#/add_bank',
            // 房间内消费排行 -- uid token room_id
            'ranking_room_user_url'         => SITE_URL . '/bogovue/#/ranking_room',
            // 幸运礼物记录 -- uid token
            'lucky_gift_log'         => SITE_URL . '/bogovue/#/lucky_gift_log',
        );

        //隐私条款
        $privacy_policy = db('portal_post')->find(7);
        $data['privacy_policy_url'] = '';
        if ($privacy_policy) {
            $data['privacy_policy_url'] = html_entity_decode($privacy_policy['post_content']);
        }

        //注销协议
        $privacy_policy = db('portal_post')->find(56);
        $data['logout_policy_url'] = '';
        if ($privacy_policy) {
            $data['logout_policy_url'] = html_entity_decode($privacy_policy['post_content']);
        }
        // 获取财富(消费)等级列表
        $level_list = load_cache('level');
        $data['level_list'] = $level_list;
        // 获取明星(收益费)等级列表
        $level_list = load_cache('income_level');
        $data['income_level_list'] = $level_list;
        // 获取兴趣 标签
        $visualize_list = load_cache('visualize');
        $data['visualize_table'] = $visualize_list;
        // 筛选区间价格
        $data['screening_maximum_price'] = $config['screening_maximum_price'];
        $data['screening_minimum_price'] = $config['screening_minimum_price'];

        // 查询开屏广告
        $splash = db('slide_item')
            ->where('slide_id = 3')
            ->order('id desc')
            ->find();
        $data['splash_url'] = '';
        $data['splash_img_url'] = '';
        $data['splash_content'] = '';

        if ($splash) {
            $data['splash_url'] = $splash['url'];
            $data['splash_img_url'] = $splash['image'];
            $data['splash_content'] = $splash['content'];
        }

        //是否开启了自定义金额
        $data['open_custom_video_charge_coin'] = 0;
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            $data['open_custom_video_charge_coin'] = 1;
        }

        //是否开启邀请模块
        $data['open_invite'] = 0;
        if (defined('OPEN_INVITE') && OPEN_INVITE == 1) {
            $data['open_invite'] = 1;
        }

        /*--------0816新增----------*/
        $data['open_video_chat'] = 0;
        if (defined('OPEN_VIDEO_CHAT') && OPEN_VIDEO_CHAT == 1) {
            $data['open_video_chat'] = 1;
        }
        //脏字库
        $data['dirty_word'] = '';
        if ($config['dirty_word']) {
            $data['dirty_word'] = $config['dirty_word'];
        }

        //在线客服(外链接地址)
        $data['custom_service_qq'] = $config['custom_service_qq'];

        //版本控制
        $android_dow = db('version_log')->where('type = 2 and is_release = 1')->order('create_time desc')->find();
        if ($android_dow) {
            $data['android_download_url'] = $android_dow['url'];
            $data['android_app_update_des'] = $android_dow['content'];
            $data['android_version'] = $android_dow['version_number'];
            $data['android_is_force_upgrade'] = $android_dow['is_update'];
        } else {
            $data['android_download_url'] = $config['android_download_url'];
            $data['android_app_update_des'] = $config['android_app_update_des'];
            $data['android_version'] = $config['android_version'];
            $data['android_is_force_upgrade'] = $config['android_version'];
        }

        $ios_dow = db('version_log')->where('type = 1 and is_release = 1')->order('create_time desc')->find();
        if ($ios_dow) {
            //下载地址
            $data['ios_download_url'] = $ios_dow['url'];
            //说明
            $data['ios_app_update_des'] = $ios_dow['content'];
            //版本号
            $data['ios_version'] = $ios_dow['version_number'];
            $data['ios_is_force_upgrade'] = $ios_dow['is_update'];
        } else {
            //下载地址
            $data['ios_download_url'] = $config['ios_download_url'];
            //说明
            $data['ios_app_update_des'] = $config['ios_app_update_des'];
            //版本号
            $data['ios_version'] = $config['ios_version'];
            $data['ios_is_force_upgrade'] = $config['ios_version'];
        }

        $data['is_force_upgrade'] = $config['is_force_upgrade'];

        //是否开启ios上架审核
        $data['is_ios_base'] = $config['is_grounding'];

        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        //创建群组
        $api = createTimAPI();

        //$ret = $api->group_create_group2('ChatRoom',$config['acquire_group_id'],$config['tencent_identifier'],$info_set,$mem_list);
        //创建在线广播大群
        $ret = $api->full_group_create($config['acquire_group_id'], '');
        //   var_dump($ret);exit;
        $GiftModel = new GiftModel();
        $data['gift_moral'] = $GiftModel->moral();
        //聊天气泡
        $chat_bubble = db('dress_up')->field('id,icon,ios_icon')->where('type = 4')->select();
        $data['chat_bubble'] = $chat_bubble;
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 获取 直播间音效
    public function get_sound()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $sound = load_cache('sound');

        $result['data'] = $sound;

        return_json_encode($result);
    }

    //获取游戏列表
    public function game_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $list = load_cache('game_list');
        foreach ($list as &$v) {
            if(strpos($v['url'], 'http') === false){
                $v['url'] = SITE_URL . $v['url'];
            }
            if ($v['type'] == 2) {
                $v['url'] = SITE_URL . '/api/bubble_api/index';
            }
        }
        $tripartite_game = db('tripartite_game')->field("*")->where("status = 1")->order("sort desc")->select();
        $return = $list;
        foreach ($tripartite_game as &$v) {
            $isLandscape = $v['is_landscape'] == 1 ? 'true' : 'false';
            $url = $v['domain_name'] . "?isLandscape=" . $isLandscape;

            if ($v['merchant']) {
                $url .= "&merchant=" . $v['merchant'];
            }
            if ($v['game_name']) {
                $url .= "&gameName=" . $v['game_name'];
            }

            $return[] = array(
                'id'          => $v['id'],
                'name'        => $v['title'],
                'img'         => $v['icon'],
                'type'        => $v['type'],
                'bg_img'      => $v['bg_img'],
                'full_screen' => $v['full_screen'],
                'url'         => $url
            );
        }

        $result['data'] = $return;

        return_json_encode($result);
    }

    //获取游戏列表
    public function room_game_list_api()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $list = load_cache('game_list');
        foreach ($list as &$v) {
            if(strpos($v['url'], 'http') === false){
                $v['url'] = SITE_URL . $v['url'];
            }
            if ($v['type'] == 2) {
                $v['url'] = SITE_URL . '/api/bubble_api/index';
            }
        }
        $tripartite_game = db('tripartite_game')->field("*")->where("status = 1")->order("sort desc")->select();
        $return = [];
        foreach ($tripartite_game as &$v) {
            $isLandscape = $v['is_landscape'] == 1 ? 'true' : 'false';
            $url = $v['domain_name'] . "?isLandscape=" . $isLandscape;

            if ($v['merchant']) {
                $url .= "&merchant=" . $v['merchant'];
            }
            if ($v['game_name']) {
                $url .= "&gameName=" . $v['game_name'];
            }

            $return[] = array(
                'id'          => $v['id'],
                'name'        => $v['title'],
                'img'         => $v['icon'],
                'type'        => $v['type'],
                'bg_img'      => $v['bg_img'],
                'full_screen' => $v['full_screen'],
                'url'         => $url
            );
        }
        $result['data'] = array(
            'tripartite_game' => $return,
            'game_list'       => $list
        );

        return_json_encode($result);
    }

    /**
     * 获取游戏列表
     */
    public function room_game_list()
    {
        // 允许所有来源访问
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers: *');#允许的header名称
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');#允许的请求方法
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $type = input('param.type');
        $tripartite_game = db('tripartite_game')->field("*")->where("status = 1")->order("sort desc")->select();
        $return = [];
        foreach ($tripartite_game as &$v) {
            if ($type && $type == 'h5') {
                $isLandscape = 'true';
                $url = $v['domain_name'];
                if ($v['directorys']) {
                    $url .= $v['directorys'] . "/";
                }
                $url .= "?isLandscape=" . $isLandscape;
            } else {
                $isLandscape = $v['is_landscape'] == 1 ? 'true' : 'false';
                $url = $v['domain_name'] . "?isLandscape=" . $isLandscape;
            }

            if ($v['merchant']) {
                $url .= "&merchant=" . $v['merchant'];
            }
            if ($v['game_name']) {
                $url .= "&gameName=" . $v['game_name'];
            }
            if ($type && $type == 'h5') {
                $url .= "&groupName=111";
            }
            $return[] = array(
                'id'          => $v['id'],
                'name'        => $v['title'],
                'img'         => $v['icon'],
                'type'        => $v['type'],
                'bg_img'      => $v['bg_img'],
                'full_screen' => $v['full_screen'],
                'url'         => $url
            );
        }
        $result['data'] = $return;
        return_json_encode($result);
    }

    //心跳间隔时间 Redis 存储
    public function interval()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = input('param.uid');
        $token = input('param.token');

        //update_heartbeat($uid);
        check_login_token($uid, $token);

        $data = array(
            'monitor_time' => NOW_TIME
        );
        //移动端监控请求
        $results = db('monitor')->where('user_id=' . $uid)->find();
        if (!$results) {
            $data['user_id'] = $uid;
            db('monitor')->where('user_id=' . $uid)->insert($data);
        } else {
            db('monitor')->where('user_id=' . $uid)->update($data);
        }
        return_json_encode($result);
    }

    public function get_version()
    {
        $result = array('code' => 1, 'msg' => '');
        $config = load_cache('config');
        //版本控制
        $android_dow = db('version_log')->where('type = 2 and is_release = 1')->order('create_time desc')->find();
        if ($android_dow) {
            $data['android_download_url'] = $android_dow['url'];
            $data['android_app_update_des'] = $android_dow['content'];
            $data['android_version'] = $android_dow['version_number'];
            $data['android_is_force_upgrade'] = $android_dow['is_update'];
        } else {
            $data['android_download_url'] = $config['android_download_url'];
            $data['android_app_update_des'] = $config['android_app_update_des'];
            $data['android_version'] = $config['android_version'];
            $data['android_is_force_upgrade'] = $config['android_version'];
        }

        $ios_dow = db('version_log')->where('type = 1 and is_release = 1')->order('create_time desc')->find();
        if ($ios_dow) {
            //下载地址
            $data['ios_download_url'] = $ios_dow['url'];
            //说明
            $data['ios_app_update_des'] = $ios_dow['content'];
            //版本号
            $data['ios_version'] = $ios_dow['version_number'];
            $data['ios_is_force_upgrade'] = $ios_dow['is_update'];
        } else {
            //下载地址
            $data['ios_download_url'] = $config['ios_download_url'];
            //说明
            $data['ios_app_update_des'] = $config['ios_app_update_des'];
            //版本号
            $data['ios_version'] = $config['ios_version'];
            $data['ios_is_force_upgrade'] = $config['ios_version'];
        }

        $data['is_force_upgrade'] = $config['is_force_upgrade'];
        $result['data'] = $data;
        return_json_encode($result);
    }

    /**
     * 获取国家分类
     * */
    public function getCountryClassify()
    {
        $countryList = getCountryList();

        return_json_encode_data($countryList);
    }

}
