<?php

namespace app\voice\controller;

use app\api\controller\Base;
use app\common\Enum;
use app\vue\model\LevelModel;
use im\BogoIM;
use think\Config;
use think\Db;
use think\helper\Time;
use think\Model;
use UserOnlineStateRedis;
use app\api\model\VoiceModel;
use app\api\model\UserModel;
use app\vue\model\ShopModel;

/**
 * 语音房相关的api接口
 * */
class VoiceApi extends Base
{
    private $VoiceModel;
    private $UserModel;
    private $ShopModel;

    protected function _initialize()
    {
        parent::_initialize();

        $this->VoiceModel = new VoiceModel();
        $this->UserModel = new UserModel();
        $this->ShopModel = new ShopModel();
    }

    // 保存开始直播信息
    public function start_voice()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);
        // 房间主题ID
        $voice_theme_id = trim(input('param.voice_theme_id'));
        // 房间标题
        $voice_title = trim(input('param.voice_title'));
        // 房间公告
        $announcement = trim(input('param.announcement'));
        // 房间背景
        $voice_bg = trim(input('param.voice_bg'));
        // 房间类型 1单人直播 2多人直播
        $voice_type = intval(input('param.voice_type')) == 1 ? 1 : 2;
        // 房间封面图
        $voice_img = trim(input('param.voice_img'));
        // 房间类型 1密码 0
        $voice_status = intval(input('voice_status'));
        // 密码
        $voice_psd = intval(input('voice_psd'));

        if (!$voice_title) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_fill_in_room_name');
            return_json_encode($result);
        }

        if (!$voice_img) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_upload_room_picture');
            return_json_encode($result);
        }

        if (!$voice_theme_id) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_select_room_theme');
            return_json_encode($result);
        }
        $config = load_cache('config');
        $title_count = mb_strlen($voice_title);
        if ($config['voice_title_count'] > 0 && $title_count > $config['voice_title_count']) {
            $result['code'] = 0;
            $result['msg'] = lang('Maximum_number_words_in_room_title') . $config['voice_title_count'];
            return_json_encode($result);
        }
        // 封面图审核
        $img = $this->VoiceModel->upd_user_voice_img($uid, $voice_img);
        // 获取直播间信息
        $voice = $this->VoiceModel->sel_voice_one($uid);
        $voice_theme = $this->VoiceModel->get_voice_label_default('id = ' . $voice_theme_id);

        if (!$voice_theme) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_select_correct_room_theme');
            return_json_encode($result);
        }
        if (!$voice_bg) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_select_room_background');
            return_json_encode($result);
        }
        $voice_bg_info = $this->VoiceModel->get_voice_bg_one($voice_bg);
        if (!$voice_bg_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_select_room_background');
            return_json_encode($result);
        }
        if ($voice_status == 1) {
            $data['voice_status'] = $voice_status;
            if (!$voice_psd) {
                $result['code'] = 0;
                $result['msg'] = lang('Please_set_room_password');
                return_json_encode($result);
            }
            $data['voice_psd'] = $voice_psd;
        }

        // 根据IP获取国家代码
        $ipCountryCode = get_country_code();

        $data = array(
            //'voice_label' => $label_name,
            'user_id'       => $uid,
            'title'         => $voice_title,
            'type'          => $voice_type,
            'live_in'       => 1,
            'online_number' => 0,
            'avatar'        => $img,
            'voice_type'    => $voice_theme_id,
            'announcement'  => $announcement,
            'voice_bg'      => $voice_bg,
            'country_code'  => $ipCountryCode,
            'create_time'   => NOW_TIME,
        );

        // 是否加入工会
        $guild_info = db('guild_join')
            ->alias('j')
            ->join('guild g', 'g.id=j.guild_id')
            ->where('j.user_id = ' . $uid . ' and j.status = 1')
            ->field('g.*')
            ->find();
        if ($guild_info) {
            $data['guild_uid'] = $guild_info['user_id'];
        }

        // 创建直播
        $this->VoiceModel->add_voice($uid);
        // sel_voice_one
        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        // 创建群组
        $api = createTimAPI();

        $ret = $api->group_create_group('AVChatRoom', (string)$uid, (string)$uid);

        if ($ret['ActionStatus'] == 'OK') {
            // 获取群组id
            $data['group_id'] = $ret['GroupId'];
            $data['status'] = 1;
            //销毁之前的群组
            if ($voice['group_id']) {
                require_once DOCUMENT_ROOT . '/system/im_common.php';
                qcloud_group_destroy_group($voice['group_id']);
            }
        } else {
            // 创建群组失败
            $result['code'] = 0;
            $result['msg'] = 'Error Code:' . $ret['ErrorCode'] . ' ' . $ret['ActionStatus'] . ' Error Info:' . $ret['ErrorInfo'];
            return_json_encode($result);
        }
        // 修改正在开始的直播
        $voice_status = $this->VoiceModel->upd_user_voice($uid, $data);

        if (!$voice_status) {
            // 开播失败
            $result['msg'] = lang('Failed_to_start_broadcasting');
            $result['code'] = 0;
        }
        return_json_encode($result);
    }

    // 用户掉线后或闪退，重新进入获取直播间信息
    public function get_window_voice()
    {
        $result = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 获取本用户信息
        check_login_token($uid, $token);
        // 房主id
        $voice_id = redis_hGet("user_voice", $uid);
        $voice = '';
        if ($voice_id) {
            // 查询语音房间
            $voice = $this->VoiceModel->sel_voice_one($voice_id);
        }
        $data = array(
            'voice_psd' => $voice ? $voice['voice_psd'] : '',
            'voice_id'  => $voice ? $voice['id'] : 0,
            'voice_uid' => $voice ? $voice['user_id'] : 0,
            'voice_img' => $voice ? $voice['voice_avatar'] : 0,
            'live_in'   => $voice ? $voice['live_in'] : 0,
            'title'     => $voice ? $voice['title'] : '',
        );

        $result['code'] = 1;
        $result['data'] = $data;
        return_json_encode($result);
    }

    /**
     * 进入语音房间获取房间内详细数据接口，用于客户端进入房间后获取房间信息展示基本数据使用
     *
     * */
    public function index()
    {

        $result = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 是否第一次进入1是0否
        //     $is_first_room = intval(input('param.is_first_room')) ? 1 : 0;
        // 获取本用户信息
        $user_info = check_login_token($uid, $token, ['income_level', 'age', 'is_stealth', 'consumption_total']);
        // 查询语音房间
        $voice = $this->VoiceModel->sel_voice_one($voice_id);

        $config = load_cache('config');

        if (!$voice || $voice['live_in'] != 1) {
            if ($uid == $voice_id) {
                $result['code'] = 20003;
            }
            $result['msg'] = lang('Room_closed');
            return_json_encode($result);
        }
        $isset_room_user = redis_hGet('voice_list_' . $voice['user_id'], $uid);
        if (!$isset_room_user) {

            $updateRoomData = [];
            if ($voice_id == $uid) {
                // 如果是房主进入房间，更新一下房间的国家代码，方便测试
                $countryCode = get_country_code();
                $updateRoomData['country_code'] = $countryCode;
            }

            // 更新房间信息 房间 人数、总统计加 1
            db('voice')->where("user_id=" . $voice['user_id'])
                ->inc('online_number', 1)
                ->inc('online_count', 1)
                ->update($updateRoomData);

            // 进房间加入缓存
            $value = array(
                'user_id'       => $uid,
                'user_nickname' => $user_info['user_nickname'],
                'sex'           => $user_info['sex'],
                'avatar'        => $user_info['avatar'],
                'level'         => $user_info['level'],
                'age'           => $user_info['age'],
            );

            set_voice_userlist($voice['user_id'], $uid, json_encode($value));

            // 查询语音房间
            $voice = $this->VoiceModel->sel_voice_one($voice_id);
        }

        //查询用户是否已被踢出房间
        $kick_out = redis_hGet('kick_out_voice_' . $voice['user_id'], $uid);

        if ($kick_out) {
            $user_kick_out = json_decode($kick_out, true);
            // 计算踢出的时间
            $time = $user_kick_out['addtime'] + $user_kick_out['time'];

            if ($time > NOW_TIME) {
                $result['msg'] = lang('user_has_been_kicked_out_room');
                return_json_encode($result);
            } else {
                redis_hDelOne('kick_out_voice_' . $voice['user_id'], $uid);
            }
        }

        // 上麦转换
        $voice['wheat_type'] = json_decode($voice['wheat_type']);
        // 房间背景图片
        $voice['voice_bg_image'] = $this->VoiceModel->get_voice_bg_one($voice['voice_bg']);

        // 查询用户已禁言
        $kick_out = redis_hGet('banned_speak_voice_' . $voice['user_id'], $uid);
        // 是否禁言1禁言0否
        $result['user']['is_kick_out'] = $kick_out ? 1 : 0;
        // 查询用户是否禁止发音
        $ban_voice = redis_hGet('ban_voice_' . $voice['user_id'], $uid);
        // 是否禁止发音 1禁0否
        $result['user']['is_ban_voice'] = $ban_voice ? 1 : 0;
        // 是否是管理员
        //$result['user']['is_admin'] = $this->user_is_admin($voice['user_id'], $uid);
        $result['user']['is_admin'] = $this->VoiceModel->is_voice_admin($voice['user_id'], $uid);
        // 是否是主持人
        $result['user']['is_host'] = $this->VoiceModel->is_voice_host($voice['user_id'], $uid);
        // 获取用户是否关注
        $focus = $this->UserModel->is_focus_user($uid, $voice['user_id']);
        // 是否关注 0为关注 1已关注
        $voice['is_focus'] = $focus ? 1 : 0;
        // 获取用户等级
        $result['user']['id'] = $user_info['id'];
        $result['user']['avatar'] = $user_info['avatar'];
        $result['user']['user_nickname'] = $user_info['user_nickname'];
        $result['user']['level'] = $user_info['level'];
        // 获取明星等级
        $result['user']['income_level'] = $user_info['income_level'];
        // 获取用户开启使用商品信息
        $shop = $this->ShopModel->get_user_shop($uid);

        // 座驾图片
        $result['user']['car_url'] = $shop['car_url'];
        // 座驾名称
        $result['user']['car_name'] = $shop['car_name'];
        // 座驾svga格式
        $result['user']['car_svga_url'] = $shop['car_svga_url'];

        // 座驾图片
        $result['user']['entry_vehicles_url'] = $shop['entry_vehicles_url'];
        // 座驾名称
        $result['user']['entry_vehicles_name'] = $shop['entry_vehicles_name'];
        // 座驾svga格式
        $result['user']['entry_vehicles_svga_url'] = $shop['entry_vehicles_svga_url'];


        // 头饰图片
        $result['user']['headwear_url'] = $shop['headwear_url'];
        $result['user']['headwear_svga'] = $shop['headwear_svga'];
        // 头饰名称
        $result['user']['headwear_name'] = $shop['headwear_name'];
        // 聊天气泡图片
        $result['user']['chat_bubble_url'] = $shop['chat_bubble_url'];
        $result['user']['chat_bubble_ios_url'] = $shop['chat_bubble_ios_url'];
        // 聊天气泡名称
        $result['user']['chat_bubble_name'] = $shop['chat_bubble_name'];
        $noble = get_noble_level($uid);
        $result['user']['noble_img'] = $noble['noble_img'];
        $result['user']['user_name_colors'] = $noble['colors'];
        $result['user']['entry_effects'] = $noble['entry_effects'];
        // 是否有vip隐身权限
        $is_stealth = intval(get_user_vip_authority($uid, 'is_stealth'));
        // 隐身进场
        $result['user']['is_stealth'] = 0;
        if ($is_stealth) {
            if ($user_info['is_stealth'] == 1) {
                $result['user']['is_stealth'] = 1;
            }
        }
        // vip3级会员专属勋章、专属头饰、进场特效、麦位声浪、专属气泡、房间名片

        // vip3级会员专属勋章
        $identity_url = get_user_vip_authority($uid, 'identity_app');
        if ($identity_url) {
            $result['user']['vip_identity'] = $identity_url;
        }

        // vip麦位声浪
        $mike_url = get_user_vip_authority($uid, 'sound_wave_app');

        if ($mike_url) {
            $result['user']['mike_url'] = $mike_url;
        }

        // vip 房间名片
        $room_card_url = get_user_vip_authority($uid, 'room_card_app');
        if ($room_card_url) {
            $result['user']['room_card_url'] = $room_card_url;
        }

        $levelInfo = getWealthLevelRuleInfoByTotalValue($user_info['consumption_total']);

        if ($levelInfo != null) {
            $result['user']['level_img'] = $levelInfo['chat_icon'];
        } else {
            $result['user']['level_img'] = '';
        }

        $to_voice_id = redis_hGet("user_voice", $uid);
        if ($to_voice_id && $to_voice_id != $voice['user_id']) {
            // 用户在另一个房间，需要退出房间重新加入当前房间
            // 查询语音房间
            $sel_voice = $this->VoiceModel->sel_voice_user_one($to_voice_id, 1);

            // 查询麦位
            $wheat_logs = $this->VoiceModel->get_voice_even_wheat_log_one($to_voice_id, $uid, '-1');
            if ($wheat_logs) {
                // 解除禁言房间缓存
                redis_hDelOne('ban_voice_' . $to_voice_id, $uid);

                if ($sel_voice['room_type'] == 2 && ($wheat_logs['location'] == 2 || $wheat_logs['location'] == 1)) {
                    //嘉宾下麦，清除派单详情
                    $dispatch = db('voice_dispatch')
                        ->where('voice_id = ' . $voice_id . ' and user_id = ' . $wheat_logs['user_id'])
                        ->find();
                    if ($dispatch) {
                        db('voice_dispatch')->where('voice_id = ' . $to_voice_id)->delete();
                        $this->send_dispatch_msg($sel_voice, 2);
                    }
                }
                $this->im_wheat_position_upd($to_voice_id);
            }
            //下麦
            $this->VoiceModel->del_voice_even_wheat_log('voice_id = ' . $to_voice_id . ' and user_id=' . $uid);

            // 删除用户在直播间缓存
            redis_hDelOne("user_voice", $uid);
            // 删除直播间用户缓存
            voice_del_userlist($to_voice_id, $uid);
            // 更新房间在线人数
            $online_number = voice_userlist_sum($to_voice_id);
            $this->VoiceModel->upd_user_voice($to_voice_id, array('online_number' => intval($online_number)));
        }


        // 用户在房间标记
        redis_hSet("user_voice", $uid, $voice['user_id']);

        //获取上麦人数
        $even_wheat = $this->VoiceModel->get_voice_even_wheat_log_list($voice['user_id']);

        // 处理麦位上信息
        foreach ($even_wheat as &$v) {

            $v['is_room'] = 1;
            if ($v['user_id'] == $voice['user_id']) {
                $voice_id = redis_hGet("user_voice", $voice['user_id']);
                if ($voice_id != $voice['user_id']) {
                    $v['is_room'] = 0;
                }
            }

            // 查询用户已禁言
            $kick_out = redis_hGet('banned_speak_voice_' . $voice['user_id'], $v['user_id']);
            // 是否禁言1禁言0否
            $v['is_kick_out'] = $kick_out ? 1 : 0;
            // 获取语音直播间收益
            if ($config['voice_charm_type'] == 1) {
                //$v['gift_earnings'] =number_format(get_voice_earnings($v['user_id'], $voice['user_id']),2);
                $v['gift_earnings'] = get_voice_earnings($v['user_id'], $voice['user_id']) . '.00';
            } else {
                $v['gift_earnings'] = get_voice_earnings($v['user_id'], $voice['user_id']);
            }

            // 是否是管理员
            //$v['is_admin'] = $this->user_is_admin($voice['user_id'], $v['user_id']);
            $v['is_admin'] = $this->VoiceModel->is_voice_admin($voice['user_id'], $v['user_id']);
            $v['is_host'] = $this->VoiceModel->is_voice_host($voice['user_id'], $v['user_id']);
            // 查询用户是否禁止发音
            $ban_voice = redis_hGet('ban_voice_' . $voice['user_id'], $v['user_id']);
            // 是否禁止发音 1禁0否
            $v['is_ban_voice'] = $ban_voice ? 1 : 0;
            $shop = $this->ShopModel->get_user_shop($v['user_id']);
            // 头饰图片
            $v['headwear_url'] = $shop['headwear_url'];
            // 麦位声浪 --vip特权
            $v['mike_url'] = get_user_vip_authority($v['user_id'], 'sound_wave_app');
            // 昵称颜色
            $noble = get_noble_level($v['user_id']);
            $v['user_name_colors'] = $noble['colors'];
        }

        // 分享语音直播
        $result['share_voice'] = SITE_URL . "/api/download_api/voice_duwnload/invite_code/" . $uid . "/id/" . $voice['user_id'];

        $voice_user_info = get_user_base_info($voice['user_id'], ['luck']);

        if ($voice_user_info['luck'] != 0) {
            $voice['luck'] = $voice_user_info['luck'];
        } else {
            $voice['luck'] = $voice['user_id'];
        }

        $voice_collect = db('voice_collect')->where('status = 1 and user_id = ' . $uid . ' and voice_id = ' . $voice['user_id'])->find();
        if ($voice_collect) {
            $voice['is_collect'] = 1;
        } else {
            $voice['is_collect'] = 0;
        }

        redis_hSet("voice_list_information", $voice['user_id'], json_encode($voice));

        if (config('app.open_bogo_im')) {
            $bogoIM = new BogoIM();
            // 创建频道
            $createChannelRes = $bogoIM->createChannel($voice['id'], $uid);
            if ($createChannelRes['status'] != 200) {
                return_json_encode(array('code' => 0, 'msg' => $createChannelRes['msg']));
            }
            // 加入订阅
            $addChannelSubRes = $bogoIM->addChannelSubscribers($voice['id'], $uid);
            if ($addChannelSubRes['status'] != 200) {
                return_json_encode(array('code' => 0, 'msg' => $addChannelSubRes['msg']));
            }
        }

        $result['even_wheat'] = $even_wheat;
        $result['voice'] = $voice;
        $result['code'] = 1;
        return_json_encode($result);
    }

    // 获取房间背景图片
    public function get_voice_bg()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));

        $user_info = check_login_token($uid, $token);
        // 获取房间背景图片列表
        $voice_bg = $this->VoiceModel->get_voice_bg_list();
        // 获取房间信息
        $voice = $this->VoiceModel->sel_voice_user_one($voice_id);

        $result['voice_bg_list'] = $voice_bg;
        if ($voice) {
            $result['voice_bg'] = $voice['voice_bg'];
        } else {
            $result['voice_bg'] = '';
        }
        return_json_encode($result);
    }

    // 设置上麦权限
    public function wheat_apply()
    {

        $result = array('code' => 0, 'msg' => lang('Set_successfully'));

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 上麦的位置id
        $wheat_id = intval(input('param.wheat_id'));
        // 上麦权限 0直接上麦 1申请上麦 2锁定上麦
        //$type = intval(input('param.type')) > 0 ? intval(input('param.type')) : 1;
        $type = intval(input('param.type'));
        // 获取用户信息
        $user_info = check_login_token($uid, $token);
        // 查询语音房间
        $voice = $this->VoiceModel->sel_voice_one($voice_id);

        if (!$voice || $voice['live_in'] != 1) {
            $result['msg'] = lang('Voice_room_closed');
            return_json_encode($result);
        }
        // 查询语音房间管理员
        $administrator = $this->VoiceModel->is_voice_admin($voice_id, $uid);
        $host = $this->VoiceModel->is_voice_host($voice_id, $uid);

        if ($administrator == 0 && $host == 0 && $voice['user_id'] != $uid) {

            $result['msg'] = lang('Operation_without_permission');
            return_json_encode($result);
        }
        // 上麦转换
        $wheat_type = json_decode($voice['wheat_type'], true);

        foreach ($wheat_type as &$v) {
            if ($v['wheat_id'] == $wheat_id) {
                $v['type'] = $type;
            }
        }
        $name = array('wheat_type' => json_encode($wheat_type));
        // 修改上麦
        $upd_voice = $this->VoiceModel->upd_user_voice($voice_id, $name);

        if ($upd_voice) {

            $result['code'] = '1';
        } else {

            $result['msg'] = lang('Failed_set_microphone_type_in_voice_room');
        }
        return_json_encode($result);
    }

    // 申请上麦
    public function add_wheat_user()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 上麦位置id
        $wheat_id = intval(input('param.wheat_id'));
        // 用户信息
        $user_info = check_login_token($uid, $token);
        // 查询语音房间
        $voice = $this->VoiceModel->sel_voice_one($voice_id);

        if (!$voice || $voice['live_in'] != 1) {
            $result['msg'] = lang('Voice_room_closed');
            return_json_encode($result);
        }

        // 处理连点
        $User_lock_key = "User_lock_" . $uid;
        redis_locksleep_nx($User_lock_key, 1);


        //主持麦判断
        if ($wheat_id == 1) {
            //是否在该麦位上
            $wheat_logs_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $uid, -1, $wheat_id);
            if ($wheat_logs_type) {
                if ($wheat_logs_type['status'] == 1) {
                    $result['msg'] = lang('Already_in_wheat_position');
                } else {
                    $result['msg'] = lang('Applied_for_wheat');
                }
                redis_unlock_nx($User_lock_key);
                return_json_encode($result);
            }
            // 获取麦上位置是否存在用户
            $wheat_logs_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, '', 1, $wheat_id);
            if ($wheat_logs_type) {
                $result['msg'] = lang('User_already_exists_on_site');
                redis_unlock_nx($User_lock_key);
                return_json_encode($result);
            }
            //主持麦位直接上麦，检测是否是房主或主持人
            $status = 1;
            $is_host = $this->VoiceModel->is_voice_host($voice['user_id'], $uid);
            if ($voice['user_id'] != $uid && $is_host != 1) {
                $result['msg'] = lang('Not_host_not_host_Mike');
                redis_unlock_nx($User_lock_key);
                return_json_encode($result);
            }
        } else {
            if ($voice['room_type'] == 2) {
                if ($wheat_id == 2) {
                    //嘉宾直接排麦
                    $status = 0;
                } else {
                    //是否在该麦位上
                    $wheat_logs_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $uid, -1, $wheat_id);
                    if ($wheat_logs_type) {
                        if ($wheat_logs_type['status'] == 1) {
                            $result['msg'] = lang('Already_in_wheat_position');
                        } else {
                            $result['msg'] = lang('Applied_for_wheat');
                        }
                        redis_unlock_nx($User_lock_key);
                        return_json_encode($result);
                    }
                    // 获取麦上位置是否存在用户
                    $wheat_logs_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, '', 1, $wheat_id);
                    if ($wheat_logs_type) {
                        $result['msg'] = lang('User_already_exists_on_site');
                        redis_unlock_nx($User_lock_key);
                        return_json_encode($result);
                    }
                    //陪玩麦直接上麦
                    $status = 1;
                    if ($user_info['is_player'] != 1) {
                        $result['msg'] = lang('not_playmate_on_wheat');
                        redis_unlock_nx($User_lock_key);
                        return_json_encode($result);
                    }
                }
            } else {
                // 获取麦上位置是否存在用户
                $wheat_logs_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, '', 1, $wheat_id);
                if ($wheat_logs_type) {
                    $result['msg'] = lang('User_already_exists_on_site');
                    redis_unlock_nx($User_lock_key);
                    return_json_encode($result);
                }

                // 获取用户是否在麦上 切换麦位逻辑 单独处理
                $user_wheat_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $uid, 1);
                if ($user_wheat_type) {
                    //原麦位下麦，重新上新麦位
                    //    $name = array('status' => 3, 'endtime' => NOW_TIME);
                    $this->VoiceModel->del_voice_even_wheat_log('id = ' . $user_wheat_type['id']);
                    //   $this->VoiceModel->upd_voice_even_wheat_log_status('id = ' . $user_wheat_type['id'], $name);
                    $data_log = array(
                        'voice_id'      => $voice_id,
                        'user_id'       => $uid,
                        'status'        => 1,
                        'user_nickname' => $user_info['user_nickname'],
                        'avatar'        => $user_info['avatar'],
                        'location'      => $wheat_id,
                        'audio_status'  => $user_wheat_type['audio_status'],
                        'gift_earnings' => $user_wheat_type['gift_earnings'],
                        'addtime'       => NOW_TIME,
                    );
                    // 加入连麦记录
                    $this->VoiceModel->add_voice_even_wheat_log($data_log);
                    $this->send_switch_wheat_msg($user_info, $voice, $user_wheat_type['gift_earnings'], $wheat_id, $user_wheat_type['audio_status']);
                    $result['code'] = '1';
                    $result['data']['gift_earnings'] = $user_wheat_type['gift_earnings'];
                    $this->im_wheat_position_upd($voice_id);
                    redis_unlock_nx($User_lock_key);
                    return_json_encode($result);
                }
                //上麦位状态 0申请 1直接上麦 3下麦
                $status = 0;
                // 查询语音房间管理员
                $administrator = $this->VoiceModel->is_voice_admin($voice_id, $uid);
                // 查询语音房间主持
                $host = $this->VoiceModel->is_voice_host($voice_id, $uid);
                if ($administrator == 1 || $host == 1 || $voice['user_id'] == $uid) {
                    $status = 1;
                } else {
                    $wheat_type = json_decode($voice['wheat_type'], true);
                    $type = 1;
                    // 是否有上麦申请权限
                    foreach ($wheat_type as &$v) {
                        if ($v['wheat_id'] == $wheat_id) {
                            $type = $v['type'];
                        }
                    }
                    //直接上麦
                    if ($type == 0) {
                        $status = 1;
                        $result['msg'] = lang('Successful_wheat_feeding');
                    }
                }
            }
        }

        //用户是否在其他麦位
        $user_wheat = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $uid, -1);
        if ($user_wheat) {
            //原麦位下麦
            //   $name = array('status' => 3, 'endtime' => NOW_TIME);
            $this->VoiceModel->del_voice_even_wheat_log('id = ' . $user_wheat['id']);
            //  $this->VoiceModel->upd_voice_even_wheat_log_status('id = ' . $user_wheat['id'], $name);
        }

        // 获取收益
        $gift_earnings = get_voice_earnings($uid, $voice['user_id']);
        // 上麦人收益
        $result['data']['gift_earnings'] = $gift_earnings;
        $data_log = array(
            'voice_id'      => $voice_id,
            'user_id'       => $uid,
            'status'        => $status,
            'user_nickname' => $user_info['user_nickname'],
            'avatar'        => $user_info['avatar'],
            'location'      => $wheat_id,
            'gift_earnings' => $gift_earnings,
            'addtime'       => NOW_TIME,
        );
        // 加入连麦记录
        $this->VoiceModel->add_voice_even_wheat_log($data_log);
        $this->im_wheat_position_upd($voice_id);
        $result['code'] = '1';
        redis_unlock_nx($User_lock_key);
        return_json_encode($result);
    }

    // 用户上下麦 同意上麦
    public function upd_wheat_user()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token);
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 上下麦人id
        $to_user_id = intval(input('param.to_user_id'));
        // 上下麦位置id
        $wheat_id = intval(input('param.wheat_id'));
        // 连麦类型 1上麦成功 2拒绝上麦 3结束上麦(下麦)
        $status = intval(input('param.status')) ? intval(input('param.status')) : 3;
        // 查询语音房间
        $voice = $this->VoiceModel->sel_voice_one($voice_id);
        if (!$voice) {
            $result['msg'] = lang('Parameter_transfer_error');
            return_json_encode($result);
        }
        // 处理连点
        $User_lock_key = "User_lock_" . $uid;
        redis_locksleep_nx($User_lock_key, 1);

        // 获取用户是否在麦上
        $wheat_logs = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $to_user_id, '-1');
        // 获取房间用户收益
        $gift_earnings = get_voice_earnings($to_user_id, $voice['user_id']);
        // 上麦人收益
        $result['data']['gift_earnings'] = $gift_earnings;


        if ($wheat_logs) {
            // 用户操作自己的上麦状态
            if ($uid == $to_user_id) {
                // 用户自己是否下麦
                if ($status != 3) {
                    $result['msg'] = lang('Unauthorized_operation');
                    redis_unlock_nx($User_lock_key);
                    return_json_encode($result);
                }
                // 用户下麦
                $name = array('status' => 3, 'endtime' => NOW_TIME);
            } else {
                // 房间主播用户操作用户上线状态
                //是否有管理员权限
                if ($voice['room_type'] == 1 || ($voice['room_type'] == 2 && $wheat_id != 2)) {
                    //是否有管理员权限
                    $this->get_is_admin($voice_id, $to_user_id, $uid);
                }

                if ($status == 1) {
                    // 获取麦上位置是否存在用户
                    $wheat_logs_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, '', 1, $wheat_id);
                    if ($voice['room_type'] == 2 && $wheat_id == 2) {
                        // 获取麦上位置是否存在用户
                        if ($wheat_logs_type) {
                            //嘉宾位下麦，排麦嘉宾上麦
                            //$this->VoiceModel -> upd_voice_even_wheat_log($wheat_logs_type['id'],array('status' => 3, 'addtime' => NOW_TIME));
                            $result['code'] = 2003;
                            $result['msg'] = lang('User_already_exists_on_site');
                            redis_unlock_nx($User_lock_key);
                            return_json_encode($result);
                        }
                    } else {
                        if ($wheat_logs_type) {
                            $result['msg'] = lang('User_already_exists_on_site');
                            redis_unlock_nx($User_lock_key);
                            return_json_encode($result);
                        }
                    }
                }
                // 要修改麦位上的字段
                $name = $status == 1 ? array('status' => $status, 'addtime' => NOW_TIME, 'location' => $wheat_id) : array('status' => $status, 'endtime' => NOW_TIME);
            }
            // 操作修改
            $upd_voice = $this->VoiceModel->upd_voice_even_wheat_log($wheat_logs['id'], $name);
            if ($name['status'] == 3) {
                $this->VoiceModel->del_voice_even_wheat_log('id = ' . $wheat_logs['id']);
            }
            $this->im_wheat_position_upd($voice_id);
            if (!$upd_voice) {
                $result['msg'] = lang('operation_failed');
                redis_unlock_nx($User_lock_key);
                return_json_encode($result);
            }

            if (($status == 3 || $status == 2) && $voice['room_type'] == 2 && ($wheat_id == 2 || $wheat_id == 1)) {
                //嘉宾下麦，清除派单详情
                $dispatch = db('voice_dispatch')
                    ->where('voice_id = ' . $voice_id . ' and user_id = ' . $wheat_logs['user_id'])
                    ->find();
                if ($dispatch) {
                    db('voice_dispatch')->where('voice_id = ' . $voice_id)->delete();
                    $this->send_dispatch_msg($voice, 2);
                    /*unset($dispatch['id']);
                    db('voice_dispatch_log')->insert($dispatch);*/
                }
            }
        } else {

            $result['msg'] = lang('user_canceled_microphone');
            redis_unlock_nx($User_lock_key);
            return_json_encode($result);
        }

        $result['code'] = '1';
        $result['msg'] = lang('Wheat_planting_succeeded');
        redis_unlock_nx($User_lock_key);
        return_json_encode($result);
    }

    /*
    *  管理员操作封装
    *  $id 房主id   $voice_uid 房间本人id
    *  $to_user_id  要操作的对方用户  $uid  操作人
    *  $type 1禁言查询
    **/
    private function get_is_admin($voice_uid, $to_user_id, $uid, $type = '')
    {

        $result = array('code' => 0, 'msg' => '');
        // 查询语音房间管理员
        $administrator = $this->VoiceModel->get_voice_administrator_list($voice_uid);

        $administrator_str = [];
        $host_str = [];
        foreach ($administrator as $k => $v) {
            if ($v['type'] == 1) {
                $administrator_str[] = $v['user_id'];
            } else if ($v['type'] == 2) {
                $host_str[] = $v['user_id'];
            }
        }
        // 房间本人
        $administrator_str[] = $voice_uid;
        // 禁言和房主
        if ($type == 1 && $voice_uid == $uid) {

            return true;
        } else {

            if (in_array($to_user_id, $administrator_str) && $voice_uid != $uid) {
                $result['msg'] = lang('Operation_without_permission_administrators');
                return_json_encode($result);
            } else if (in_array($to_user_id, $host_str) && $voice_uid != $uid) {
                $result['msg'] = lang('Operation_without_permission_host');
                return_json_encode($result);
            }
            // 查询用户是否是管理员和房间本人
            if (!in_array($uid, $administrator_str) && !in_array($uid, $host_str)) {

                $result['msg'] = lang('Operation_without_permission');
                return_json_encode($result);
            }
        }
        return true;
    }

    /**
     *   是否是管理员
     *   $voice_id 房主id   $to_user_id 判断用户是否是管理员
     */
    private function user_is_admin($voice_id, $to_user_id)
    {

        // 查询语音房间管理员
        $administrator = $this->VoiceModel->get_voice_administrator_list($voice_id);

        $administrator_str = [];

        foreach ($administrator as $k => $v) {

            $administrator_str[] = $v['user_id'];
        }
        // 房间本人
        $administrator_str[] = $voice_id;
        //dump($administrator_str);die();
        $user_is_admin = 0;

        if (in_array($to_user_id, $administrator_str)) {
            // 查询用户是否是管理员和房间本人
            $user_is_admin = 1;
        }

        return $user_is_admin;
    }

    // 显示麦位管理
    public function management_voice()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 麦位类型 -1 是正在麦位和申请的麦位 0申请1正在2拒绝3结束
        $type = input('param.type') ? input('param.type') : '-1';
        $voice = $this->VoiceModel->sel_voice_one($voice_id);
        if (!$voice) {
            $result['list'] = [];
            $result['wheat_list'] = [];
            return_json_encode($result);
        }
        //获取上麦人数
        $where = "location != 1 and voice_id = " . $voice_id;
        if ($voice['room_type'] == 2) {
            //location != 2 and
            $where .= ' and location != 2';
        }

        if ($type == -1) {
            $where .= ' and (status=1 or status =0)';
        } else {
            $where .= " and status=" . $type;
        }
        $wheat_list = db('voice_even_wheat_log')->where("status = 1 and location = 1 and voice_id = " . $voice_id)->select();
        if ($wheat_list) {
            foreach ($wheat_list as &$val) {
                // 查询用户是否禁止发音
                $ban_voice = redis_hGet('ban_voice_' . $voice_id, $val['user_id']);
                // 是否禁止发音 1禁0否
                $val['is_ban_voice'] = $ban_voice ? 1 : 0;
                // 获取用户开启使用商品信息
                $shop = $this->ShopModel->get_user_shop($val['user_id']);
                // 头饰图片
                $val['headwear_url'] = $shop['headwear_url'];
                // 头饰名称
                $val['headwear_name'] = $shop['headwear_name'];
                $user = get_user_base_info($val['user_id'], ['age', 'sex']);
                $noble = get_noble_level($val['user_id']);
                $val['user_name_colors'] = $noble['colors'];
                $val['sex'] = $user['sex'];
                $val['age'] = $user['age'];
            }
        }
        //获取上麦人数
        $even_wheat = db('voice_even_wheat_log')->where($where)->select();

        foreach ($even_wheat as &$v) {
            // 查询用户是否禁止发音
            $ban_voice = redis_hGet('ban_voice_' . $voice_id, $v['user_id']);
            // 是否禁止发音 1禁0否
            $v['is_ban_voice'] = $ban_voice ? 1 : 0;
            // 获取用户开启使用商品信息
            $shop = $this->ShopModel->get_user_shop($v['user_id']);
            // 头饰图片
            $v['headwear_url'] = $shop['headwear_url'];
            // 头饰名称
            $v['headwear_name'] = $shop['headwear_name'];
            $user = get_user_base_info($v['user_id'], ['age', 'sex']);
            $noble = get_noble_level($v['user_id']);
            $v['user_name_colors'] = $noble['colors'];
            $v['sex'] = $user['sex'];
            $v['age'] = $user['age'];
        }

        $result['list'] = $even_wheat;
        $result['wheat_list'] = $wheat_list;
        return_json_encode($result);
    }

    // 抱人上麦
    public function voice_up()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 对方id
        $to_user_id = trim(input('param.to_user_id'));
        // 上麦位置id
        $wheat_id = trim(input('param.wheat_id'));
        // 查询语音房间管理员
        $administrator = $this->VoiceModel->is_voice_admin($voice_id, $uid);
        $host = $this->VoiceModel->is_voice_host($voice_id, $uid);

        if ($administrator == 0 && $voice_id != $uid && $host == 0) {

            $result['code'] = 0;
            $result['msg'] = lang('Operation_without_permission');
            return_json_encode($result);
        }
        // 获取用户信息
        $user = get_user_base_info($to_user_id);
        // 获取直播间信息
        $voice = $this->VoiceModel->sel_voice_user_one($voice_id, 1);
        //麦位上是否有用户
        $wheat_info = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, '', '-1', $wheat_id);
        if ($wheat_info) {
            if ($wheat_info['status'] == 0) {
                // 正在连麦的下麦
                //    $name = array('status' => 3, 'endtime' => NOW_TIME);
                // 操作修改
                //  $this->VoiceModel->upd_voice_even_wheat_log($wheat_info['id'], $name);
                $this->VoiceModel->del_voice_even_wheat_log("id=" . $wheat_info['id']);
            } else {
                $result['code'] = 0;
                $result['msg'] = lang('User_already_exists_on_the_site');
                return_json_encode($result);
            }
        }
        // 取消正在麦位或申请的
        $wheat_logs = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $to_user_id, $status = '-1');

        if ($wheat_logs) {
            //    $name = array('status' => 3, 'endtime' => NOW_TIME);
            // 操作修改
            //   $this->VoiceModel->upd_voice_even_wheat_log($wheat_logs['id'], $name);
            $this->VoiceModel->del_voice_even_wheat_log("id=" . $wheat_logs['id']);
        }
        // 获取收益
        $gift_earnings = get_voice_earnings($to_user_id, $voice['user_id']);
        // 上麦人收益
        $result['data']['gift_earnings'] = $gift_earnings;

        $data_log = array(
            'voice_id'      => $voice_id,
            'user_id'       => $to_user_id,
            'status'        => 1,
            'user_nickname' => $user['user_nickname'],
            'avatar'        => $user['avatar'],
            'location'      => $wheat_id,
            'gift_earnings' => $gift_earnings,
            'addtime'       => NOW_TIME,
        );
        // 加入连麦记录
        $this->VoiceModel->add_voice_even_wheat_log($data_log);
        $this->im_wheat_position_upd($voice_id);
        return_json_encode($result);
    }

    // 查看用户信息
    public function sel_voice_user()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 查看对方用户id
        $to_user_id = intval(input('param.to_user_id'));
        // 获取用户信息
        $sel_user = get_user_base_info($to_user_id, ['address', 'vip_end_time', 'birthday', 'luck', 'custom_video_charging_coin', 'is_voice_online', 'constellation', 'country_code']);
        // 获取关注列表id
        $focus = $this->UserModel->is_focus_user($uid, $to_user_id);
        // 是否关注 0为关注 1已关注
        $sel_user['focus'] = $focus ? 1 : 0;
        // 粉丝数
        $sel_user['attention_fans'] = db('user_attention')->where("attention_uid=" . $to_user_id)->count();
        // 查询
        $black_record = $this->UserModel->user_black_one($uid, $to_user_id);
        // 查询是否拉黑用户
        $sel_user['is_black'] = $black_record ? 1 : 0;
        // 查询用户已禁言
        $kick_out = redis_hGet('banned_speak_voice_' . $voice_id, $to_user_id);
        // 是否禁言1禁言0否
        $sel_user['is_kick_out'] = $kick_out ? 1 : 0;
        // 对方是否是管理员
        $sel_user['is_admin'] = $this->VoiceModel->is_voice_admin($voice_id, $to_user_id);
        $sel_user['is_host'] = $this->VoiceModel->is_voice_host($voice_id, $to_user_id);
        // 自己是否是管理员
        $sel_user['is_admin_own'] = $this->VoiceModel->is_voice_admin($voice_id, $uid);
        $sel_user['is_host_own'] = $this->VoiceModel->is_voice_host($voice_id, $uid);

        // 星座
        //$sel_user['constellation'] =$user_auth_info && $user_auth_info['constellation'] ? $user_auth_info['constellation']: '';
        //陪聊等级
        $talker_level = get_talker_level($to_user_id);
        $sel_user['talker_level_name'] = '';
        $sel_user['talker_level_img'] = '';
        //陪玩等级
        $player_level = get_player_level($to_user_id);
        $sel_user['player_level_name'] = $player_level['player_level_name'];
        $sel_user['player_level_img'] = $player_level['player_level_img'];

        $noble = get_noble_level($to_user_id);
        $sel_user['noble_img'] = $noble['noble_img'];
        $sel_user['user_name_colors'] = $noble['colors'];

        // 获取配置
        $config = load_cache('config');
        // 主页轮播图
        //$sel_user['user_img'] = $this->UserModel->user_img($sel_user['id'], 1);

        $level = get_level($sel_user['id']);
        // 等级
        $sel_user['level'] = $level;
        // 分钟扣费金额
        $sel_user['charging_coin'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {

            if (isset($sel_user['custom_video_charging_coin']) && $level >= $config['custom_video_money_level'] && $sel_user['custom_video_charging_coin'] > 0) {
                $sel_user['charging_coin'] = $sel_user['custom_video_charging_coin'];
            }
        }

        if (isset($sel_user['custom_video_charging_coin'])) {
            unset($sel_user['custom_video_charging_coin']);
        }

        $shop = $this->ShopModel->get_user_shop($to_user_id);
        // 头饰图片
        $sel_user['headwear_url'] = $shop['headwear_url'];
        // 房间名片 --vip特权
        $room_card_url = get_user_vip_authority($to_user_id, 'room_card_app');
        $sel_user['room_card_url'] = $room_card_url;
        // vip专属勋章 --vip特权
        $identity_url = get_user_vip_authority($to_user_id, 'identity_app');
        $sel_user['vip_medal_url'] = $identity_url;
        // vip专属昵称 --vip特权
        $exclusive_nickname = get_user_vip_authority($to_user_id, 'exclusive_nickname');
        $sel_user['vip_exclusive_nickname'] = $exclusive_nickname;

        $country = get_country_one(intval($sel_user['country_code']));
        $sel_user['country_flag_img_url'] = '';
        if ($country) {
            $sel_user['country_flag_img_url'] = $country['img'];
        }

        $result['list'] = $sel_user;
        return_json_encode($result);
    }

    // 踢出语音房间
    public function kick_out_voice()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 要踢出的用户id
        $to_user_id = intval(input('param.to_user_id'));
        // 本用户信息
        $user_info = check_login_token($uid, $token);
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);
        // vip权限-- 房间防踢
        $is_kick = intval(get_user_vip_authority($to_user_id, 'is_kick'));
        if ($is_kick == 1) {
            $result['msg'] = lang('Enable_kick_prevention_permission');
            return_json_encode($result);
        }
        // 是否有管理员权限
        $this->get_is_admin($voice_id, $to_user_id, $uid);
        // 踢出时间限制小时
        $config = load_cache('config');
        // 时间
        $time = $config['kicking_time'] > 0 ? $config['kicking_time'] * 60 : 0;
        //用户踢出房间
        $value = array(
            'play_man' => $uid,       // 踢出人id
            'uid'      => $to_user_id,    // 踢出用户id
            'addtime'  => NOW_TIME,   // 踢出当时时间
            'time'     => $time,          // 踢出时间限制小时
        );
        // 踢出房间缓存
        redis_hSet('kick_out_voice_' . $voice_id, $to_user_id, json_encode($value));
        // 查询麦位
        $even_wheat = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $to_user_id, $status = '-1');

        if ($even_wheat) {

//            $name = array('status' => 3, 'endtime' => NOW_TIME);
//            // 修改麦位
//            $upd_voice = $this->VoiceModel->upd_voice_even_wheat_log($even_wheat['id'], $name);
            // 删除麦位
            $this->VoiceModel->del_voice_even_wheat_log("id=" . $even_wheat['id']);

            $this->im_wheat_position_upd($voice_id);
        }
        // 删除房间缓存
        voice_del_userlist($voice_id, $to_user_id);
        // 删除用户在直播间缓存
        redis_hDelOne("user_voice", $to_user_id);
        // 删除直播间用户缓存
        voice_del_userlist($voice_id, $to_user_id);

        // 更新房间在线人数
        $online_number = voice_userlist_sum($voice_id);
        $this->VoiceModel->upd_user_voice($voice_id, array('online_number' => intval($online_number)));
        // 房间人数减1
        // $this->VoiceModel -> upd_cumulative($voice_id,'online_number',1,2);
        return_json_encode($result);
    }

    // 语音房间禁言用户
    public function banned_speak()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 要禁言的用户id
        $to_user_id = intval(input('param.to_user_id'));
        // 本用户信息
        $user_info = check_login_token($uid, $token);
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);
        // 是否有管理员权限
        $this->get_is_admin($voice_id, $to_user_id, $uid, 1);
        // 禁言时间限制小时
        $time = 24;
        // 用户禁言房间
        $value = array(
            'play_man' => $uid,       // 禁言人id
            'uid'      => $to_user_id,    // 禁言用户id
            'addtime'  => NOW_TIME,   // 禁言当时时间
            'time'     => $time,          // 禁言时间限制小时
        );
        // 禁言房间缓存
        redis_hSet('banned_speak_voice_' . $voice_id, $to_user_id, json_encode($value));

        return_json_encode($result);
    }

    // 解除禁言的用户
    public function remove_banned_speak()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 解除禁言的用户id
        $to_user_id = intval(input('param.to_user_id'));
        // 本用户信息
        $user_info = check_login_token($uid, $token);
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);
        //是否有管理员权限
        $this->get_is_admin($voice_id, $to_user_id, $uid, 1);
        // 解除禁言房间缓存
        redis_hDelOne('banned_speak_voice_' . $voice_id, $to_user_id);

        return_json_encode($result);
    }

    // 查看禁言语音列表
    public function get_banned_list()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));

        $banned_speak_voice = redis_hGet('banned_speak_voice_' . $voice_id);
        $lists = array();
        $field = "user_nickname,id,avatar";
        foreach ($banned_speak_voice as $key => $val) {
            $lists_one = json_decode($val, true);

            // 判断是否是用户自己禁麦的
            if ($lists_one['play_man'] != $lists_one['uid']) {
                $where = "id=" . $lists_one['uid'];
                $sel_lists_one = $this->UserModel->get_user($where, $field);
                array_push($lists, $sel_lists_one);
            }
        }
        $result['data'] = $lists;
        return_json_encode($result);
    }

    // 用户退出房间
    public function live_exit()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 本用户信息

        $user_info = check_login_token($uid, $token);

        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_user_one($voice_id, 1);
        // 查询麦位
        $wheat_logs = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $uid, $status = '-1');

        if ($wheat_logs) {
            // 正在连麦的下麦
            //$name = array('status' => 3, 'endtime' => NOW_TIME);
            // 修改麦位
            // 删除麦位
            $this->VoiceModel->del_voice_even_wheat_log("id=" . $wheat_logs['id']);
            //    $this->VoiceModel->upd_voice_even_wheat_log($wheat_logs['id'], $name);
            $this->im_wheat_position_upd($voice_id);
            // 解除禁言房间缓存
            redis_hDelOne('ban_voice_' . $voice_id, $uid);

            if ($sel_voice['room_type'] == 2 && ($wheat_logs['location'] == 2 || $wheat_logs['location'] == 1)) {
                //嘉宾下麦，清除派单详情
                $dispatch = db('voice_dispatch')
                    ->where('voice_id = ' . $voice_id . ' and user_id = ' . $wheat_logs['user_id'])
                    ->find();
                if ($dispatch) {
                    db('voice_dispatch')->where('voice_id = ' . $voice_id)->delete();
                    $this->send_dispatch_msg($sel_voice, 2);
                    /*unset($dispatch['id']);
                    db('voice_dispatch_log')->insert($dispatch);*/
                }
            }
        }
        // 正在申请上麦的下麦
        $upd_voice_status = "voice_id=" . $voice_id . " and status=0 and user_id = " . $uid;
        // $this->VoiceModel->upd_voice_even_wheat_log_status($upd_voice_status, $name);
        // 删除麦位
        $this->VoiceModel->del_voice_even_wheat_log($upd_voice_status);
        // 删除用户在直播间缓存
        redis_hDelOne("user_voice", $uid);
        // 删除直播间用户缓存
        voice_del_userlist($voice_id, $uid);
        // 更新房间在线人数
        $online_number = voice_userlist_sum($voice_id);
        $this->VoiceModel->upd_user_voice($voice_id, array('online_number' => intval($online_number)));
        // 房间人数减1
        //  $this->VoiceModel->upd_cumulative($voice_id, 'online_number', 1, 2);
        //}

        if (config('app.open_bogo_im')) {
            $bogoIM = new BogoIM();
            $bogoIM->removeChannelSubscribers($sel_voice['id'], $uid);
        }

        return_json_encode($result);
    }

    // 禁止用户语音  禁止发音
    public function user_ban_voice()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 要禁止语音的用户id
        $to_user_id = intval(input('param.to_user_id'));
        // 本用户信息
        $user_info = check_login_token($uid, $token);
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);

        if ($to_user_id != $uid) {
            // 是否有管理员权限
            $this->get_is_admin($voice_id, $to_user_id, $uid);
        }

        // 用户禁言房间
        $value = array(
            'ban_voice' => $uid,       // 禁止语音人id
            'uid'       => $to_user_id,    // 禁止语音用户id
            'addtime'   => NOW_TIME,   // 禁止语音当时时间
        );
        // 禁止语音缓存
        redis_hSet('ban_voice_' . $voice_id, $to_user_id, json_encode($value));

        return_json_encode($result);
    }

    // 解除禁止用户语音
    public function remove_user_ban_voice()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 解除禁止语音的用户id
        $to_user_id = intval(input('param.to_user_id'));
        // 本用户信息
        $user_info = check_login_token($uid, $token);
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);
        // 是否禁止用户语音

        $ban_voice = redis_hGet('ban_voice_' . $voice_id, $to_user_id);

        $value = json_decode($ban_voice, true);

        if ($value) {
            // 解除禁止用户语音
            $result['type'] = 0;
            $is_admin = $this->VoiceModel->is_voice_admin($voice_id, $uid);

            if ($uid != $to_user_id) {
                //是否有管理员权限
                $this->get_is_admin($voice_id, $to_user_id, $uid);
                if ($value['ban_voice'] == $value['uid']) {
                    $result['code'] = 0;
                    $result['msg'] = lang('User_has_been_banned');
                    return_json_encode($result);
                }
            } else {
                if ($value['ban_voice'] != $uid && $sel_voice['user_id'] != $uid) {

                    if (!$is_admin || ($is_admin && $value['ban_voice'] == $sel_voice['user_id'])) {

                        $result['code'] = 0;
                        $result['msg'] = lang('User_has_been_banned');
                        return_json_encode($result);
                    }
                }
            }
            // 解除禁言房间缓存
            redis_hDelOne('ban_voice_' . $voice_id, $to_user_id);
        } else {
            // 用户禁言房间
            $value = array(
                'ban_voice' => $uid,       // 禁止语音人id
                'uid'       => $to_user_id,    // 禁止语音用户id
                'addtime'   => NOW_TIME,   // 禁止语音当时时间
            );
            // 禁止语音缓存
            redis_hSet('ban_voice_' . $voice_id, $to_user_id, json_encode($value));
            // 禁止用户语音
            $result['type'] = 1;
        }

        return_json_encode($result);
    }

    // 查看禁止语音列表
    public function get_ban_speech()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));

        $ban_voice = redis_hGet('ban_voice_' . $voice_id);
        $lists = array();
        $field = "user_nickname,id,avatar";
        foreach ($ban_voice as $key => $val) {
            $lists_one = json_decode($val, true);

            // 判断是否是用户自己禁麦的
            if ($lists_one['ban_voice'] != $lists_one['uid']) {
                $where = "id=" . $lists_one['uid'];
                $sel_lists_one = $this->UserModel->get_user($where, $field);
                array_push($lists, $sel_lists_one);
            }
        }
        $result['data'] = $lists;
        return_json_encode($result);
    }

    // 管理员重置用户收益展示
    public function voice_reset()
    {

        $result = array('code' => 0, 'msg' => lang('Reset_failed'));

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 要清零收益的用户id 多个用逗号隔开
        $to_user_id = trim(input('param.to_user_id'));
        // 数组
        $to_users = explode(",", $to_user_id);
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);

        foreach ($to_users as $v) {
            // 重置记录字段名
            $data = array(
                'user_id'       => $v,
                'voice_user_id' => $sel_voice['user_id'],
                'operation_uid' => $uid,
                'addtime'       => NOW_TIME
            );

            if ($uid != $v) {
                // 查询语音房间管理员
                $administrator = $this->VoiceModel->is_voice_admin($voice_id, $uid);
                $host = $this->VoiceModel->is_voice_host($voice_id, $uid);

                // 查询用户是否是管理员和房间本人
                if ($administrator == 0 && $host == 0 && $uid != $voice_id) {

                    $result['msg'] = lang('Operation_without_permission');
                    return_json_encode($result);
                }
            }
            // 重置房间麦上用户收益
            $gift_reset = $this->VoiceModel->add_voice_gift_reset($data);

            if (!$gift_reset) {
                return_json_encode($result);
            }
        }

        $result['code'] = 1;
        $result['msg'] = lang('Reset_successful');
        return_json_encode($result);
    }

    // 房间管理员
    public function voice_administrator()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 查询语音房间管理员
        $list = $this->VoiceModel->get_voice_administrator_user($voice_id, $uid);

        $result['list'] = $list;

        return_json_encode($result);
    }

    // 删除管理员
    public function del_voice_administrator()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 要删除的用户id
        $to_user_id = intval(input('param.to_user_id'));
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);

        if (!$sel_voice) {

            $result['msg'] = lang('Room_does_not_exist');
            return_json_encode($result);
        }
        if ($voice_id != $uid) {
            $result['msg'] = lang('Operation_without_permission');
            return_json_encode($result);
        }
        // 删除管理员
        $upd_voice = $this->VoiceModel->del_voice_administrator($voice_id, $to_user_id, $uid);

        if (!$upd_voice) {

            $result['msg'] = lang('Failed_cancel_administrator');
            return_json_encode($result);
        }
        $result['code'] = 1;
        return_json_encode($result);
    }

    // 添加房间管理员
    public function add_voice_administrator()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 连麦人id
        $to_user_id = intval(input('param.to_user_id'));

        if ($to_user_id == $uid) {

            $result['msg'] = lang('Cannot_operate_your_own_account');
            return_json_encode($result);
        }

        if ($uid != $voice_id) {

            $result['msg'] = lang('room_that_not_user_cannot_operated');
            return_json_encode($result);
        }
        $voice_administrator = db('voice_administrator')->where('type = 1 and user_id = ' . $to_user_id . ' and voice_id = ' . $voice_id)->find();
        //$voice_administrator = $this->VoiceModel -> get_voice_administrator_one($voice_id,$to_user_id);

        if ($voice_administrator) {

            $result['msg'] = lang('User_already_administrator');
            return_json_encode($result);
        }

        $name = array(
            'user_id'   => $to_user_id,
            'voice_id'  => $voice_id,
            'voice_uid' => $uid,
            'type'      => 1,
            'addtime'   => NOW_TIME,
        );
        // 加入管理员
        $administrator = $this->VoiceModel->add_voice_administrator($name);

        if (!$administrator) {

            $result['msg'] = lang('Failed_to_add_administrator');
            return_json_encode($result);
        }

        $result['code'] = 1;
        return_json_encode($result);
    }

    // 获取要添加的管理员
    public function sel_voice_administrator()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 查询语音房间管理员
        $administrator = $this->VoiceModel->get_voice_administrator_user($voice_id, $uid);

        $administrator_str = [];
        foreach ($administrator as $k => $v) {
            $administrator_str[] = $v['user_id'];
        }
        // 获取房间人数列表
        $list = voice_userlist_arsort($voice_id);
        $user_list = [];

        foreach ($list as $k => $v) {
            $users = json_decode($v, true);
            // 筛选出不是管理员的用户
            if (!in_array($users['user_id'], $administrator_str) && $users['user_id'] != $uid) {
                $user_list[] = $users;
            }
        }

        $result['list'] = $user_list;
        return_json_encode($result);
    }

    // 显示收礼物人
    public function gift_earnings_user()
    {

        $result = array('code' => 1, 'msg' => '');
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 查询语音房间
        $voice = $this->VoiceModel->sel_voice_one($voice_id);
        /*if($voice['room_type']==2){*/
        // 获取在上麦人数
        $even_wheat = $this->VoiceModel->sel_voice_even_wheat_user_list_dispatch($voice_id);
        /*}else{
            // 获取在上麦人数
            $even_wheat =$this->VoiceModel ->sel_voice_even_wheat_user_list($voice_id);
        }*/
        $result['list'] = $even_wheat;

        return_json_encode($result);
    }

    // 删除判断本地音乐是否存在在服务器上
    public function judge_music()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 音乐的md5值多个用逗号隔开
        $url_md5 = trim(input('param.url_md5'));
        $where = "uid=" . $uid;
        if ($url_md5) {
            $url = explode(',', $url_md5);
            foreach ($url as $v) {
                $where .= " and url_md5 !='" . $v . "'";
            }
        }
        // 删除本地已操作删除的音乐
        $status = $this->VoiceModel->del_judge_music($where);

        return_json_encode($result);
    }


    /*-----------------------------------    以下是旧接口(待废弃)   --------------------------------------------*/


    //判断房间是否是密码房间
    public function is_voice_psd()
    {
        $result = array('code' => 0, 'msg' => '', 'type' => 0);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $id = intval(input('param.id'));        //房间id 不传是自己的房间
        $touid = intval(input('param.touid'));        //用户id
        $user_info = check_login_token($uid, $token);

        $voice_where = "status=1";
        if ($id) {
            $voice_where .= " and id=" . $id;
        } elseif ($touid) {
            $voice_where .= " and user_id=" . $touid;
        } else {
            $voice_where .= " and user_id=" . $uid;
        }

        //查询语音房间
        $voice = db('voice')->where($voice_where)->find();

        if (!$voice) {
            $result['msg'] = lang('Room_closed');
            return_json_encode($result);
        }
        //    $admin=$this->user_is_admin($voice['id'],$uid);              //是否是管理员
        if ($voice['voice_status'] == 1 && $uid != $voice['user_id']) {
            $result['type'] = 1;
        }
        $result['code'] = 1;
        $result['id'] = $voice['id'];
        $result['voice_psd'] = $voice['voice_psd'];
        return_json_encode($result);
    }

    //获取在线观众人数
    public function get_voice_userlist()
    {
        $result = array('code' => 1, 'msg' => '');

        $id = intval(input('param.id'));        //房间id
        $voice = db('voice')->where("id=" . $id)->find();
        //dump($voice);die();
        //获取房间人数
        $result['sum'] = voice_userlist_sum($voice['user_id']);
        //获取房间人数列表
        $list = voice_userlist_arsort($voice['user_id']);
        $user_list = [];
        foreach ($list as &$v) {
            $value = json_decode($v, true);
            $user = get_user_base_info($value['user_id'], ['age']);
            $value['rank_sum'] = intval(get_user_vip_authority($value['user_id'], "is_rank"));
            if ($user['id'] != -1) {
                $value['age'] = $user['age'];
                $user_list[] = $value;
            }
        }
        if (count($user_list)) {
            $user_list = arraySequence($user_list, 'rank_sum');
        }
        $result['userlist'] = $user_list;
        return_json_encode($result);
    }

    //保存房间详情信息
    public function save_voice()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $id = intval(input('param.id'));                  //房间id
        $avatar = trim(input('param.voice_img'));                  //房间缩略图
        $title = trim(input('param.voice_title'));                  //房间标题
        $voice_theme_id = intval(input('param.voice_theme_id'));//房间主题ID
        $announcement = trim(input('param.announcement'));//公告
        $voice_bg = intval(input('param.voice_bg'));                  //房间背景图id
        $voice_status = input('param.voice_status', 0); //房间类型
        $voice_psd = trim(input('param.voice_psd')); //房间类型
        $sel_voice = db('voice')->where("id=" . $id)->find();
        if ($sel_voice && $uid != $sel_voice['user_id']) {
            // 查询语音房间管理员
            $administrator = $this->VoiceModel->is_voice_admin($sel_voice['user_id'], $uid);
            $host = $this->VoiceModel->is_voice_host($sel_voice['user_id'], $uid);
            //dump($administrator);die();
            // 查询用户是否是管理员和房间本人
            if ($administrator == 0 && $host == 0 && $uid != $sel_voice['user_id']) {
                $result['msg'] = lang('Operation_without_permission');
                return_json_encode($result);
            }
        }

        $config = load_cache('config');
        $name = [];
        if ($avatar) {
            // 封面图审核
            $img = $this->VoiceModel->upd_user_voice_img($uid, $avatar);
            if ($config['user_avatar'] != $img) {
                $name['avatar'] = $avatar;
            }
        }
        if ($title) {
            $name['title'] = $title;
        }
        if ($voice_bg) {
            $name['voice_bg'] = $voice_bg;
        }
        if ($announcement) {
            $name['announcement'] = $announcement;
        }

        if ($voice_status == 1) {
            $name['voice_status'] = $voice_status;
            $name['voice_psd_show'] = $voice_psd;
            $name['voice_psd'] = $voice_psd;
        }

        if ($voice_status == '-1') {
            $name['voice_status'] = 0;
            $name['voice_psd_show'] = '';
            $name['voice_psd'] = '';
        }
        if ($voice_theme_id) {
            $name['voice_type'] = $voice_theme_id;
        }

        if (count($name) > 0) {
            $upd_voice = db('voice')->where("id=" . $id)->update($name);
            if (!$upd_voice) {
                if ($avatar && !isset($name['avatar'])) {
                    // 只修改了封面图片不能返回错误 000
                } else {
                    $result['code'] = 0;
                    $result['msg'] = lang('EDIT_FAILED');
                    return_json_encode($result);
                }
            }
        }
        $voice = db('voice')->where("id=" . $id)->find();
        $voice_type_id = db('voice_bg')->where("id=" . $voice['voice_bg'] . " and status=1")->find();
        $broadMsg['voice_id'] = $id;
        $broadMsg['voice_uid'] = $uid;
        $broadMsg['voice_title'] = $voice['title'];
        $broadMsg['voice_bg'] = $voice_type_id ? $voice_type_id['image'] : '';
        $broadMsg['voice_avatar'] = $voice['avatar'];
        $broadMsg['announcement'] = $voice['announcement'];
        $broadMsg['voice_type'] = $voice['voice_type'];
        $broadMsg['type'] = Enum::SAVE_ROOM;

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

        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        $api = createTimAPI();

        $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);

        return_json_encode($result);
    }


    //语音房间七日排行榜 ---真爱榜
    public function consumption_ranking_list()
    {
        //查询语音房间
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $id = trim(input('param.id'));                            //房间id
        $page = trim(input('param.page')); //

        $user_info = check_login_token($uid, $token);
        //$startime = strtotime('-7 days');      //七天前的开始时间
        $startime = 0;

        $where = "l.type=4 and l.voice_user_id=" . $id . " and l.create_time >=" . $startime . " and l.gift_type !=3";
        $list = [];
        if ($page == 1) {
            $list = db('user')->alias('a')
                ->join('user_gift_log l', 'l.user_id=a.id')
                ->join('voice_even_wheat_log v', 'l.voice_log_id=v.id', 'left')
                ->field("a.user_nickname,a.avatar,a.sex,a.age,a.level,sum(l.gift_coin) as total_diamonds,a.id")
                ->where($where)
                ->group("a.id")
                ->order("total_diamonds desc")
                ->page($page)
                //->limit(0, 100)
                ->select();
            //    var_dump(db('user')->getLastSql());exit;
        }
        $user_info['ranking'] = 0;
        $user_info['total_diamonds'] = 0;
        foreach ($list as $k => $v) {
            /*if ($v['id'] == $uid) {
                $user_info['ranking'] = $k + 1;
                $user_info['total_diamonds'] = $v['total_diamonds'];
            }*/
            /*$medal = medal_one($v['id'], $v['medal_id'], $v['medal_end_time']);
            $list[$k]['medal_icon'] = $medal['medal_icon'];
            $list[$k]['medal_name'] = $medal['medal_name'];
            $list[$k]['medal_time'] = $medal['medal_time'];*/
            //陪聊等级
            $talker_level = get_talker_level($v['id']);
            $list[$k]['talker_level_name'] = $talker_level['talker_level_name'];
            $list[$k]['talker_level_img'] = $talker_level['talker_level_img'];
            //陪玩等级
            $player_level = get_player_level($v['id']);
            $list[$k]['player_level_name'] = $player_level['player_level_name'];
            $list[$k]['player_level_img'] = $player_level['player_level_img'];
        }

        $prefix = Config::get('database.prefix');
        //$where = "l.type=3 and v.voice_id=" . $id . " and l.create_time >=" . $startime;
        // 获取排名
        $ranking_wheret = 'type = 4 and voice_user_id = ' . $id . ' and create_time >= ' . $startime . '  and gift_type !=3 GROUP BY(user_id)';
        // 查询用户排名
        $ranking_where = "user_id = " . $uid;

        // 获取用户在排行榜名次
        $sql_zi = "SELECT *,@rownum := @rownum + 1 AS rownum FROM (SELECT @rownum := 0) r,(SELECT user_id,sum(gift_coin) coin_sum  FROM " . $prefix . "user_gift_log AS t WHERE " . $ranking_wheret . " ORDER BY coin_sum DESC) as tt";
        // 查询用户排名
        $sql = "SELECT b.* FROM (" . $sql_zi . ") AS b WHERE " . $ranking_where;
//var_dump($sql);exit;
        $user_ranking_log = Db::query($sql);
        $coin_sum = $user_ranking_log ? $user_ranking_log[0]['coin_sum'] : 0;
        //上一名
        $top_sql = "SELECT b.* FROM (" . $sql_zi . ") AS b WHERE coin_sum > " . $coin_sum . ' ORDER BY coin_sum ASC';
        $user_ranking_top = Db::query($top_sql);
        $top_sum = 0;
        if ($user_ranking_top) {
            if ($coin_sum > 0) {
                $top_sum = $user_ranking_top[0]['coin_sum'] - $coin_sum;
            } else {
                $top_sum = $user_ranking_top[0]['coin_sum'];
            }
        }

        $user_info = array(
            'ranking'        => $user_ranking_log ? $user_ranking_log[0]['rownum'] : 0,
            'total_diamonds' => $top_sum,
            'avatar'         => $user_info['avatar'],
            'user_nickname'  => $user_info['user_nickname'],
        );

        $result['list'] = $list;
        $result['user'] = $user_info;

        return_json_encode($result);
    }

    /**
     * 断网联网之后处理
     * */
    public function reconnect()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $voice_id = intval(input('param.voice_id'));
        //$psd = isset($this->param_info['voice_psd'])?$this->param_info['voice_psd']:0;

        $user_info = check_login_token($uid, $token, ['age']);

        $user_id = $uid;
        if ($voice_id) {
            $voice = db('voice')->where("user_id=" . $voice_id)->find();

            //退出语音房间
            $wheat_logs = db('voice_even_wheat_log')->where("user_id=" . $user_id . " and status=1")->find();
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
                    'MsgType'    => 'TIMCustomElem',       //定义类型为普通文本型
                    'MsgContent' => array(
                        'Data' => json_encode($broadMsg)    //转为JSON字符串
                    )
                );

                //将创建的元素$msg_content_elem, 加入array $msg_content
                array_push($msg_content, $msg_content_elem);
                $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);
            }

            $voice_redis = redis_hGet("user_voice", $user_id);
            if (!$voice_redis) {
                $value = array(
                    'user_id'        => $uid,
                    'user_nickname'  => $user_info['user_nickname'],
                    'sex'            => $user_info['sex'],
                    'avatar'         => $user_info['avatar'],
                    'level'          => $user_info['level'],
                    'medal_id'       => $user_info['medal_id'],
                    'medal_end_time' => $user_info['medal_end_time'],
                    'age'            => $user_info['age'],
                );

                if ($user_info['user_type'] == 2) {
                    //房间人数加1
                    db('voice')->where("id=" . $voice['id'])->setInc("online_number", 1);
                    //进房间加入缓存
                    set_voice_userlist($voice['user_id'], $uid, json_encode($value));
                    redis_hSet("user_voice", $uid, $voice['user_id']);
                }
            }
        }
        return_json_encode($result);
    }

    /**
     * 是否被禁麦
     * */
    public function is_ban_voice()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $voice_id = input('voice_id');

        $user_info = check_login_token($uid, $token);

        $ban_voice = redis_hGet('ban_voice_' . $voice_id, $uid);
        if ($ban_voice) {
            $result['data']['is_ban_voice'] = 1;
            $result['msg'] = lang('You_have_been_banned');
        } else {
            $result['data']['is_ban_voice'] = 0;
        }
        return_json_encode($result);
    }

    // 管理员重置关闭统计魅力值
    public function voice_reset_charm()
    {

        $result = array('code' => 0, 'msg' => lang('Reset_failed'));

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token);
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);
        $config = load_cache('config');
        if ($sel_voice) {
            //['charm_status'];
            if ($sel_voice['charm_status'] == 1) {
                $data['charm_status'] = 0;
                $msg = lang('Closed_successfully');
            } else {
                $data['charm_status'] = 1;
                $msg = lang('Open_successfully');
            }
            // 查询语音房间管理员
            $administrator = $this->VoiceModel->is_voice_admin($voice_id, $uid);
            $host = $this->VoiceModel->is_voice_host($sel_voice['user_id'], $uid);
            //dump($administrator);die();
            // 查询用户是否是管理员和房间本人
            if ($administrator == 0 && $host == 0 && $uid != $voice_id) {
                $result['msg'] = lang('Operation_without_permission');
                return_json_encode($result);
            }
            $upd = $this->VoiceModel->upd_user_voice($sel_voice['user_id'], $data);
            if ($upd && $data['charm_status'] == 0) {
                //清除麦位魅力值
                $even_list = $this->VoiceModel->get_voice_even_wheat_list('voice_id = ' . $voice_id);
                if ($even_list) {
                    foreach ($even_list as $v) {
                        // 重置记录字段名
                        $up_data = array(
                            'user_id'       => $v['user_id'],
                            'voice_user_id' => $sel_voice['user_id'],
                            'operation_uid' => $uid,
                            'addtime'       => NOW_TIME
                        );
                        // 重置房间麦上用户收益
                        $gift_reset = $this->VoiceModel->add_voice_gift_reset($up_data);

                        if (!$gift_reset) {
                            return_json_encode($result);
                        }
                    }
                }
            }
            $result['code'] = 1;
            $result['msg'] = $msg;

            $broadMsg['voice_id'] = $voice_id;
            $broadMsg['voice_uid'] = $uid;
            $broadMsg['charm_status'] = $data['charm_status'];
            //$broadMsg['voice_bg'] = $voice_type_id ? $voice_type_id['image'] : '';
            //$broadMsg['voice_avatar'] = $sel_voice['avatar'];
            //$broadMsg['announcement'] = $sel_voice['announcement'];
            //$broadMsg['voice_type'] = $sel_voice['voice_type'];
            $broadMsg['type'] = Enum::CHARM_VALUE;

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

            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $api = createTimAPI();

            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $sel_voice['group_id'], $msg_content);
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Room_does_not_exist');
        }

        return_json_encode($result);
    }

    //魅力值统计状态
    public function get_charm_status()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token);
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 查询语音房间
        $sel_voice = $this->VoiceModel->sel_voice_one($voice_id);
        $result['data']['charm_status'] = $sel_voice['charm_status'];
        return_json_encode($result);
    }

    private function send_switch_wheat_msg($user_info, $voice, $gift_earnings, $wheat_id, $audio_status)
    {
        //to_user_id  total_ticket   prop_id   gift_num  gift_name svga  icon
        $config = load_cache('config');
        //$levelModel = new LevelModel();
        $noble = get_noble_level($user_info['id']);
        $sender['noble_img'] = $noble['noble_img'];
        $sender['user_name_colors'] = $noble['colors'];
        $sender['entry_effects'] = $noble['entry_effects'];

        $broadMsg['type'] = Enum::AGREE_WHEAT; //同意上麦
        $sender['user_nickname'] = $user_info['user_nickname'];
        $sender['avatar'] = $user_info['avatar'];
        $sender['user_id'] = $user_info['id'];
        $sender['guardian'] = 0;
        $sender['level'] = $user_info['level'];
        $sender['sex'] = $user_info['sex'];
        $sender['has_car'] = 0;
        //头像框
        $uid_dress = get_user_dress_up($user_info['id'], 3);
        $sender['avatar_frame'] = '';
        if ($uid_dress) {
            $sender['avatar_frame'] = $uid_dress['icon'];
            $sender['headdress'] = $uid_dress['icon'];
            $sender['headwear_url'] = $uid_dress['icon'];
        }

        //勋章
        $dress = get_user_dress_up($user_info['id'], 1);
        $sender['user_medal'] = '';
        if ($dress) {
            $sender['user_medal'] = $dress['icon'];
        }

        //$sender['send_msg'] = $user_info['user_nickname']."赠送" . $to_user_info['user_nickname'] . '一个'.$data['name'];
        $broadMsg['voice_id'] = $voice['id']; //房间id
        //财富值、魅力值、明星值
        //$sender['wealth_info'] = $levelModel->wealth($user_info['id']);
        //$sender['charm_info'] = $levelModel->charm($user_info['id']);
        //$sender['star_info'] = $levelModel->star($user_info['id']);

        $broadMsg['user'] = $sender;
        $broadMsg['gift_earnings'] = $gift_earnings;
        $broadMsg['wheat_id'] = $wheat_id;
        $broadMsg['wheat_type'] = 1;
        $broadMsg['audio_status'] = $audio_status;

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

        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        $api = createTimAPI();

        $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);

        return $ret;

    }

    public function send_dispatch_msg($voice, $status)
    {
        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        $api = createTimAPI();
        //发送派单消息
        $config = load_cache('config');

        $broadMsg['duration']['status'] = $status;

        $broadMsg['type'] = Enum::DISPATCH_MESSAGE;
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
        $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);
        //dump($ret);
        return $ret;
    }

    // 用户上下麦变动需要发送im到前端更房间麦位信息
    private function im_wheat_position_upd($voice_id)
    {
        // 查询语音房间
        $voice = $this->VoiceModel->sel_voice_one($voice_id);
        //获取上麦人数
        $even_wheat = $this->VoiceModel->get_voice_even_wheat_log_list($voice['user_id']);
        // 处理麦位上信息
        foreach ($even_wheat as &$v) {
            // 查询用户已禁言
            $kick_out = redis_hGet('banned_speak_voice_' . $voice['user_id'], $v['user_id']);
            // 是否禁言1禁言0否
            $v['is_kick_out'] = $kick_out ? 1 : 0;
            // 获取语音直播间收益
            $v['gift_earnings'] = get_voice_earnings($v['user_id'], $voice['user_id']);
            // 是否是管理员
            $v['is_admin'] = $this->user_is_admin($voice['user_id'], $v['user_id']);
            // 查询用户是否禁止发音
            $ban_voice = redis_hGet('ban_voice_' . $voice['user_id'], $v['user_id']);
            // 是否禁止发音 1禁0否
            $v['is_ban_voice'] = $ban_voice ? 1 : 0;
            $shop = $this->ShopModel->get_user_shop($v['user_id']);
            // 头饰图片
            $v['headwear_url'] = $shop['headwear_url'];
            $v['headwear_svga'] = $shop['headwear_svga'];
            // 麦位声浪 --vip特权 mike_url
            $v['mike_url'] = get_user_vip_authority($v['user_id'], 'sound_wave_app');
            // 昵称颜色
            $noble = get_noble_level($v['user_id']);
            $v['user_name_colors'] = $noble['colors'];
        }
        $broadMsg['type'] = Enum::WHEAT_CHANGE;

        $broadMsg['room_id'] = $voice['room_id']; // 房间id
        $broadMsg['even_wheat'] = $even_wheat;
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

        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        $api = createTimAPI();
        $config = load_cache('config');
        $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);

        return $ret;
    }
}
