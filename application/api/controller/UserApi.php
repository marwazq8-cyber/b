<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/22
 * Time: 23:33
 */

namespace app\api\controller;

use app\vue\model\ShopModel;
use think\Db;
use think\helper\Time;
use app\api\model\UserModel;
use app\api\model\LoginModel;
use app\api\model\VoiceModel;
use app\api\model\BzoneModel;
use think\Model;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class UserApi extends Base
{
    private $UserModel;
    private $LoginModel;
    private $VoiceModel;
    private $BzoneModel;

    protected function _initialize()
    {
        parent::_initialize();

        header('Access-Control-Allow-Origin:*');
        $this->UserModel = new UserModel();
        $this->LoginModel = new LoginModel();
        $this->VoiceModel = new VoiceModel();
        $this->BzoneModel = new BzoneModel();
    }

    // 判断vip特权
    public function get_is_vip_privilege()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        check_login_token($uid, $token, ['is_stealth', 'is_ban_attention']);
        $is_stealth = intval(get_user_vip_authority($uid, 'is_stealth'));
        $status = 0;
        if ($is_stealth) {
            // 是否有vip隐身权限
            $status = 1;
        }
        $is_ban_attention = intval(get_user_vip_authority($uid, 'is_ban_attention'));
        if ($is_ban_attention) {
            // 是否有vip禁止跟随
            $status = 1;
        }
        $message = "";
        if ($status != 1) {
            $user_vip = redis_hGet('vip_level_user', $uid);
            // 获取最低开通vip开关功能权限
            $vip = db('vip')->field("id,title")->where('(is_stealth = 1 or is_ban_attention=1) and status=1')->order("sort desc,id asc")->find();
            $title = $vip ? ":" . $vip['title'] : "";
            if ($user_vip) {
                // 暂无权限，需要升级vip
                $message = lang('Upgrade_VIP_level') . $title;
            } else {
                // 暂无权限，需要 购买vip
                $message = lang('Purchase_VIP_level') . $title;
            }
        }
        $result['data'] = array(
            'status'  => 1,
            'message' => $message
        );
        return_json_encode($result);
    }

    // 获取vip开关特权
    public function get_vip_switching_privilege()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['is_stealth', 'is_ban_attention']);
        $is_stealth = intval(get_user_vip_authority($uid, 'is_stealth'));
        $list = array();
        if ($is_stealth) {
            // 是否有vip隐身权限
            $list[] = array(
                'id'     => 1,
                'title'  => lang('Turn_stealth'),
                'status' => $user_info['is_stealth']
            );
        }
        $is_ban_attention = intval(get_user_vip_authority($uid, 'is_ban_attention'));

        if ($is_ban_attention) {
            // 是否有vip禁止跟随
            $list[] = array(
                'id'     => 2,
                'title'  => lang('ban_attention'),
                'status' => $user_info['is_ban_attention']
            );
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    // 开启隐身或禁止关注开启
    public function save_vip_privilege_switch()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $status = intval(input('param.status')) == 1 ? 1 : 0;
        $type = intval(input('param.type')); // 1隐身2禁止跟随
        $user_info = check_login_token($uid, $token, ['is_stealth', 'is_ban_attention']);
        if ($type == 1) {
            $is_stealth = intval(get_user_vip_authority($uid, 'is_stealth'));

            if ($is_stealth) {
                // 是否有vip隐身权限
                db('user')->where('id = ' . $uid)->update(['is_stealth' => $status]);
            } else {
                if ($user_info['is_stealth'] == 1) {
                    // 清除隐身
                    db('user')->where('id = ' . $uid)->update(['is_stealth' => 0]);
                }
                $result['code'] = 0;
                $result['msg'] = lang('No_permission');
                return_json_encode($result);
            }
        } else {
            $is_ban_attention = intval(get_user_vip_authority($uid, 'is_ban_attention'));
            if ($is_ban_attention) {
                // 是否有vip禁止跟随
                db('user')->where('id = ' . $uid)->update(['is_ban_attention' => $status]);
            } else {
                if ($user_info['is_stealth'] == 1) {
                    // 清除禁止跟随
                    db('user')->where('id = ' . $uid)->update(['is_ban_attention' => 0]);
                }
                $result['code'] = 0;
                $result['msg'] = lang('No_permission');
                return_json_encode($result);
            }
        }
        return_json_encode($result);
    }

    //是否实名认证
    public function is_auth_form()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token);
        //检查是否认证
        $auth_where = [
            'user_id' => $user_info['id'],
            'status'  => 1,
        ];
        $auth_info = db('auth_form_record')->where($auth_where)->find();
        if ($auth_info) {
            $result['data']['is_auth'] = 1;
        } else {
            $result['data']['is_auth'] = 0;
        }
        return_json_encode($result);
    }

    //密友信息
    public function get_friendship()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $touid = intval(input('param.touid'));

        $user_info = check_login_token($uid, $token, ['is_talker', 'is_player']);
        $to_user_info = get_user_base_info($touid, ['is_talker', 'is_player'], 1);
        $data = [];

        if ($user_info['is_player'] == 1 && $to_user_info['is_talker'] == 0) {
            //自己陪玩 对方不是主播
            $data['is_text'] = 1;
            $data['is_audio'] = 1;
            $data['is_video'] = 1;
        } else if ($user_info['is_talker'] == 0 && $to_user_info['is_player'] == 1) {
            //对方陪玩 自己不是主播
            $data['is_text'] = 1;
            $data['is_audio'] = 1;
            $data['is_video'] = 1;
        } else if ($user_info['is_talker'] == 1 && $to_user_info['is_talker'] == 1) {
            //对方和自己都是主播
            $data['is_text'] = 1;
            $data['is_audio'] = 1;
            $data['is_video'] = 1;
        } else if ($user_info['is_talker'] == 1) {
            //自己是主播
            $data['is_text'] = 1;
            $data['is_audio'] = 1;
            $data['is_video'] = 1;
        } else {
            //对方是主播
            $user_friendship = Db::name('user_friendship')->where(['uid' => $uid, 'touid' => $touid])->find();

            if ($user_friendship) {
                $coin = $user_friendship['coin'];
                //等级
                $level = Db::name('friendship_level')->where('level_up > ' . $coin)->order('level_up asc')->find();
                if (!$level) {
                    $level = Db::name('friendship_level')->order('level_up desc')->find();
                }
            } else {
                $level = Db::name('friendship_level')->order('level_up asc')->find();
            }

            $data['is_text'] = $level['is_text'];
            $data['is_audio'] = $level['is_audio'];
            $data['is_video'] = $level['is_video'];
        }
        $friendlevel_text = Db::name('friendship_level')
            ->where(['is_text' => 1])
            ->order('level_up asc')
            ->find();
        $friendlevel_audio = Db::name('friendship_level')
            ->where(['is_audio' => 1])
            ->order('level_up asc')
            ->find();
        $friendlevel_video = Db::name('friendship_level')
            ->where(['is_video' => 1])
            ->order('level_up asc')
            ->find();
        $data['friendship_level_text'] = $friendlevel_text['name'];
        $data['friendship_level_audio'] = $friendlevel_audio['name'];
        $data['friendship_level_video'] = $friendlevel_video['name'];
        //"id":1,
        //"name":"1",
        //"level_up":300,
        //"addtime":1590481438,
        //"is_text":1,
        //"is_audio":1,
        //"is_video":0,
        //"is_info":0,
        //"sort":1

        $result['data'] = $data;
        return_json_encode($result);
    }

    // 开启和关闭打电话在线显示
    public function upd_voice_online()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token, ['is_voice_online']);
        // 粉丝
        $is_voice_online = $user_info['is_voice_online'] == 1 ? 0 : 1;

        $data = array('is_voice_online' => $is_voice_online);

        $status = $this->UserModel->upd_user($uid, $token, $data);

        if (!$status) {

            $result['code'] = 0;

            $result['msg'] = lang('operation_failed');

            return_json_encode($result);
        }

        $result['data']['is_voice_online'] = $is_voice_online;

        return_json_encode($result);
    }


    // 用户查看其他个人中心 constellation
    public function get_user_page_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $to_user_id = intval(input('param.to_user_id'));

        $uid = intval(input('param.uid'));

        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token);

        $to_user_filed = ['custom_video_charging_coin', 'luck', 'is_voice_online', 'vip_end_time'];

        $user = get_user_base_info($to_user_id, $to_user_filed);

        $level = get_level($to_user_id);

        $data = array(
            'id'              => $to_user_id,
            'sex'             => $user['sex'],
            'user_nickname'   => $user['user_nickname'],
            'avatar'          => $user['avatar'],
            'user_status'     => $user['is_auth'],
            'is_voice_online' => $user['is_voice_online'],
            'income_level'    => $user['income_level'],
            'level'           => $level,
            'signature'       => $user['signature'],
            'luck'            => $user['luck'],
            'constellation'   => $user['constellation'],
            'audio_file'      => $user['audio_file'],
            'audio_time'      => $user['audio_time'],
            'city'            => $user['city'],
            'province'        => $user['province'],
            //    'visualize_name' => $this->UserModel ->visualize_name($user['visualize_name']),
            'label'           => $user['label'],
            'age'             => $user['age'],
            'is_auth'         => $user['is_auth'],
        );
        // 获取是否关注
        $data['attention'] = $to_user_id != $uid ? get_attention($uid, $to_user_id) : 1;
        // 是否拉黑
        $data['is_black'] = get_is_black($uid, $to_user_id);

        $config = load_cache('config');
        //是否在线0不在1在
        //$data['online'] = is_online($to_user_id, $config['heartbeat_interval']);
        $data['is_online'] = $user['is_online'];
        // 粉丝
        $data['attention_fans'] = $this->UserModel->fans_count($to_user_id);
        // 关注
        $data['attention_all'] = $this->UserModel->focus_count($to_user_id);
        //通话时长
        $call_time = $this->UserModel->call_time($to_user_id);
        //通话总时长转换小时
        //    $data['call'] = $call_time ? secs_to_str(abs($call_time)) :'0';
        $data['call'] = $call_time ? floor(abs($call_time) / 60) : '0';
        // 是否是vip
        $data['is_vip'] = get_is_vip($user['vip_end_time']);
        // 获取好评总数
        $evaluation = $this->UserModel->video_call_record_log_count($to_user_id, 1);
        //好评百分比
        $data['evaluation'] = $evaluation;
        //主页轮播图
        $data['img'] = $this->UserModel->user_img($to_user_id, 1);
        // 增加访客记录
        if ($to_user_id != $uid) {

            $this->UserModel->add_visitors($uid, $to_user_id);
        }
        // 访客
        $data['visitors_count'] = $this->UserModel->visitors_count($to_user_id);
        //通话价格
        $data['video_deduction'] = $config['video_deduction'];

        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            //判断用户等级是否符合规定
            if ($user['custom_video_charging_coin'] != 0) {

                $data['video_deduction'] = $user['custom_video_charging_coin'];
            }
        }
        //本用户是否在语音房间
        $voice_id = redis_hGet("user_voice", $to_user_id);
        $data['voice_id'] = $voice_id ? $voice_id : 0;
        //头像框
        $uid_dress = get_user_dress_up($uid, 3);
        $data['user_avatar_frame'] = '';
        if ($uid_dress) {
            $data['user_avatar_frame'] = $uid_dress['icon'];
        }
        //勋章
        $uid_medal = get_user_dress_up($uid, 1);
        $data['user_medal'] = '';
        if ($uid_medal) {
            $data['user_medal'] = $uid_medal['icon'];
        }

        $result['data'] = $data;

        return_json_encode($result);
    }

    //显示修改的用户信息
    public function edit_user_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('param.uid');
        $token = input('param.token');

        $user_info = check_login_token($uid, $token, ['birthday', 'constellation', 'province', 'city', 'signature', 'audio_file', 'audio_time', 'label', 'occupation', 'country_code']);

        $user_info['img'] = $this->UserModel->user_img($uid);
        $user_info['user_country_code'] = $user_info['country_code'];
        $country = get_country_one(intval($user_info['user_country_code']));
        $user_info['user_country_name'] = '';
        if ($country) {
            $user_info['user_country_name'] = $country['name'];
        }

        $result['data'] = $user_info;

        return_json_encode($result);
    }

    //提交修改用户信息
    public function update_user_info()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $id = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $sex = intval(input("param.sex"));//性别
        $user_nickname = trim(input('param.user_nickname'));//昵称
        $signature = trim(input('param.signature'));//签名
        $constellation = trim(input('param.constellation'));//星座
        $city = trim(input('param.city'));//城市
        //$province = trim(input('param.province'));
        $birthday = trim(input('param.birthday'));//出生日期

        $audio_file = trim(input('param.audio_file'));//语音
        $audio_time = trim(input('param.audio_time'));//语音时长
        $data = array();
        //$custom_video_charging_coin = trim(input('param.custom_video_charging_coin'));
        // 获取个性标签 多个用逗号隔开
        $visualize_name = trim(input('param.label'));//标签
        //上传头像
        $avatar = trim(input('param.avatar'));//头像
        $occupation = trim(input('param.occupation'));//职业

        $country_code = trim(input('param.country_code'));// 国家代号

        $user_info = check_login_token($id, $token, ['country_code']);
        // 获取配置
        //$config = load_cache('config');
        // 修改昵称
        if (!empty($user_nickname)) {

//           $all_name = $this->UserModel ->sel_user_nickname($id,$user_nickname);
//
//            if ($all_name) {
//
//                $result['msg'] = "用户名重复，请重新输入用户名";
//                return_json_encode($result);
//            }

            $config = load_cache('config');
            $dirty_word = explode(',', $config['dirty_word']);
            foreach ($dirty_word as $val) {
                if (stripos($user_nickname, $val) !== false) {
                    $result['code'] = 0;
                    $result['msg'] = lang('User_name_forbidden_words');
                    return_json_encode($result);
                }
            }

            $data['user_nickname'] = $user_nickname;
        }
        if ($sex) {
            $data['sex'] = $sex;
        }

        //修改签名
        if (!empty($signature)) {

            $data['signature'] = $signature;
        }
        // 头像
        if (!empty($avatar)) {

            $data['avatar'] = $avatar;
        }
        if (!empty($constellation)) {
            $data['constellation'] = $constellation;
        }
        if ($city) {
            $data['city'] = $city;
        }
        /*if ($province) {
            // 获取省
            $data['province'] = $province;
        }*/
        if ($birthday) {
            // 获取年龄
            $data['age'] = birthday($birthday);
            $data['birthday'] = $birthday;
        }
        if ($audio_file) {
            // 获取声音介绍
            $data['audio_file'] = $audio_file;
        }
        if ($audio_time) {
            $data['audio_time'] = $audio_time;
        }
        if ($visualize_name) {
            $data['label'] = $visualize_name;
        }

        if ($occupation) {
            $data['occupation'] = $occupation;
        }

        // 轮播图
        $new_image = array();

        for ($i = 0; $i < 6; $i++) {

            $img = trim(input('param.img' . $i));

            if ($img) {

                $new_image[$i] = $img;
            }
        }

        if ($new_image) {
            // 获取数据中轮播图数量
            $all_img = $this->UserModel->user_img_count($id);

            if ((count($new_image) + $all_img) > 6) {

                $result['msg'] = lang('Please_delete_picture_add_it');

                return_json_encode($result);
            }

            foreach ($new_image as $v) {

                $upload_all['img'][]['img'] = $v;
            }
        }
        //国家代号
        if (!empty($country_code) && $user_info['country_code'] != $country_code) {
            $data['country_code'] = $country_code;
        }
        if ($data) {
            //更新修改信息
            $this->UserModel->upd_user($id, $token, $data);
        }
        if ($new_image && isset($upload_all)) {
            // 更新的轮播
            foreach ($upload_all['img'] as &$v) {
                $v['uid'] = $id;
                $v['addtime'] = NOW_TIME;
                $data['img'][]['img'] = $v['img'];
            }
            //添加轮播图
            $all_img = $this->UserModel->add_user_img($upload_all['img']);

            if (!$all_img) {

                $result['msg'] = lang('Failed_to_save_user_information');

                return_json_encode($result);
            }
        }

        require_once DOCUMENT_ROOT . '/system/im_common.php';

        update_im_user_info($id);

        $result['code'] = 1;

        $result['msg'] = lang('Modified_successfully');

        $result['data'] = $data;

        return_json_encode($result);
    }

    // 删除形象图片
    public function del_image()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $img_id = intval(input('param.id'));

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $ser = $this->UserModel->user_img_one("uid=$uid and id=$img_id");

        $file_name = parse_url($ser['img'])['path'];
        $file_name = substr($file_name, 1, strlen($file_name));

        $set = oss_del_file($file_name);

        // 删除轮播图
        $ser_status = $this->UserModel->del_user_img("uid=$uid and id=$img_id");

        if ($ser_status) {
            $result['code'] = 1;
        }

        return_json_encode($result);
    }

    //获取其他用户主页信息
    public function get_other_user_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('param.uid');
        $token = input('param.token');

        $user_info = check_login_token($uid, $token);
        $touid = input('param.touid') ? input('param.touid') : $uid;
        $list = get_user_base_info($touid, ['birthday', 'city', 'signature', 'audio_file', 'audio_time', 'label', 'occupation', 'is_player', 'is_talker', 'country_code','nobility_level','signature']);
        $talker_level = get_talker_level($touid);
        $player_level = get_player_level($touid);
        // 用户等级
        $list['consumption_level_icon'] = get_user_level($touid, 'chat_icon');
        $list['income_level_icon'] = get_user_income_level($touid, 'chat_icon');
        
        $country = get_country_one(intval($list['country_code']));
        $list['country_flag_img_url'] = '';
        if ($country) {
            $list['country_flag_img_url'] = $country['img'];
        }

        $list['talker_level_name'] = $talker_level['talker_level_name'];
        $list['talker_level_img'] = $talker_level['talker_level_img'];
        $list['player_level_name'] = $player_level['player_level_name'];
        $list['player_level_img'] = $player_level['player_level_img'];

        $list['is_attention'] = get_attention($uid, $touid);
        $list['is_black'] = get_is_black($uid, $touid);
        $list['uid'] = $list['id'];

        $list['img_list'] = $this->UserModel->user_img($touid);
        $dress = get_user_dress_up($touid, 2);
        $list['dress_svga'] = '';
        if ($dress) {
            $list['dress_svga'] = $dress['img_bg'];
        }
        $noble = get_noble_level($touid);
        $list['noble_img'] = $noble['noble_img'];
        $list['user_name_colors'] = $noble['colors'];
        //勋章
        $uid_medal = get_user_dress_up($touid, 1);
        $list['user_medal'] = '';
        if ($uid_medal) {
            $list['user_medal'] = $uid_medal['icon'];
        }
        //是否官方认证
        $platform = db('platform_auth')
            ->where(['user_id' => $touid, 'status' => 1])
            ->order('id desc')
            ->find();
        $list['is_platform_auth'] = 0;
        $list['platform_name'] = '';
        if ($platform) {
            $list['is_platform_auth'] = 1;
            $list['platform_name'] = $platform['type_name'];
        }
        $voice_id = redis_hGet("user_voice", $touid);
        $list['is_voice'] = 0;
        $list['voice_id'] = 0;
        if ($voice_id) {
            $list['is_voice'] = 1;
            $list['voice_id'] = $voice_id;
        }
        $ShopModel = new ShopModel();
        $shop = $ShopModel->get_user_shop($touid);

        // 头饰图片
        $list['headwear_url'] = $shop['headwear_url'];
        // 增加访客记录
        if ($touid != $uid) {
            $this->UserModel->add_visitors($uid, $touid);
        }
        // vip专属昵称 -- 图片链接
        $list['vip_exclusive_nickname'] = get_user_vip_authority($touid, 'exclusive_nickname');
        // vip专属勋章 -- 图片链接
        $list['identity_app'] = get_user_vip_authority($touid, 'identity_app');

        // 访客
        $list['visitors_count'] = $this->UserModel->visitors_count($touid);
 
        $list['fans_count'] = $this->UserModel->fans_count($uid);
        $list['focus_count'] = $this->UserModel->focus_count($uid);
        $list['luck'] = $list['luck'] ? $list['luck'] : '';
        
        $list['medals'] = $user_medal = Db::table('bogo_user_medals')
       ->alias('um')
       ->join('bogo_dress_up m', 'um.medal_id = m.id')
       ->where('user_id', $uid)
       ->select();
       
        $user_cars = Db::table('bogo_user_dress_up')
        ->where('uid', $user_info['id'])
        ->where('dress_up_type' , 11)
        ->select();
        $list['$user_cars'] = $user_cars;

        $level = get_grade_level($touid);


        $result['data'] = $list;
        return_json_encode($result);
    }

    //资料
    public function get_other_user_information()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token);
        $touid = input('param.touid');
        //$friendship_level = friendship_level($uid,$touid);
        $to_user_info = get_user_base_info($touid, ['birthday', 'city', 'luck', 'signature', 'audio_file', 'audio_time', 'label', 'occupation', 'is_player', 'is_talker', 'friendship_level_info', 'friendship_level_video', 'friendship_level_dynamic', 'constellation']);
        //$result['data']['friendship_level'] = $to_user_info['friendship_level_info'];
        //$result['data']['is_look_info'] = 0;
        $level_info = friendship_level_is_look($uid, $touid, $to_user_info['friendship_level_info']);
        $result['data']['friendship_level'] = $level_info['level'];
        $result['data']['is_look_info'] = $level_info['is_look'];
        if ($uid != $touid) {
            if ($level_info['is_look'] == 1) {
                $result['data']['is_look_info'] = 1;
                $result['data']['uid'] = $to_user_info['id'];
                $result['data']['luck'] = $to_user_info['luck'];
                $result['data']['occupation'] = $to_user_info['occupation'];
                $result['data']['signature'] = $to_user_info['signature'];
                $result['data']['constellation'] = $to_user_info['constellation'];
                $result['data']['label'] = [];
                if ($to_user_info['label']) {
                    $result['data']['label'] = explode(',', $to_user_info['label']);
                }
            }
        } else {
            //$result['data']['is_look_info'] = 1;
            $result['data']['is_look_info'] = 1;
            $result['data']['uid'] = $to_user_info['id'];
            $result['data']['luck'] = $to_user_info['luck'];
            $result['data']['occupation'] = $to_user_info['occupation'];
            $result['data']['signature'] = $to_user_info['signature'];
            $result['data']['constellation'] = $to_user_info['constellation'];

            $result['data']['label'] = [];
            if ($to_user_info['label']) {
                $result['data']['label'] = explode(',', $to_user_info['label']);
            }
        }


        //$result['data'] = $friendship_level;
        return_json_encode($result);

    }

    //小视频
    public function get_other_user_video()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token);
        $touid = input('param.touid');
        $p = input('param.page');
        //$friendship_level = friendship_level($uid,$touid);
        $to_user_info = get_user_base_info($touid, ['birthday', 'city', 'signature', 'audio_file', 'audio_time', 'label', 'occupation', 'is_player', 'is_talker', 'friendship_level_info', 'friendship_level_video', 'friendship_level_dynamic']);
        /*$result['data']['friendship_level'] = $to_user_info['friendship_level_video'];
        $result['data']['is_look_info'] = 0;
        if($uid == $touid || $friendship_level['id']>=$to_user_info['friendship_level_video']){
            $result['data']['is_look_info'] = 1;
        }*/
        $level_info = friendship_level_is_look($uid, $touid, $to_user_info['friendship_level_video']);
        $result['data']['friendship_level'] = $level_info['level'];
        $result['data']['is_look_info'] = $level_info['is_look'];

        $video_list = db('user_video')->where('uid', '=', $touid)->order('addtime desc')->page($p)->select();
        foreach ($video_list as &$v) {
            $v['title'] = emoji_decode($v['title']);
        }
        $result['data']['video_list'] = $video_list;
        return_json_encode($result);

    }

    //动态
    public function get_other_user_dynamic()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token);
        $touid = input('param.touid');
        $page = input('param.page');
        //$friendship_level = friendship_level($uid,$touid);
        $to_user_info = get_user_base_info($touid, ['friendship_level_info', 'friendship_level_video', 'friendship_level_dynamic']);
        /*$result['data']['friendship_level'] = $to_user_info['friendship_level_dynamic'];
        $result['data']['is_look_info'] = 0;
        if($uid == $touid || $friendship_level['id']>=$to_user_info['friendship_level_dynamic']){
            $result['data']['is_look_info'] = 1;
        }*/
        $level_info = friendship_level_is_look($uid, $touid, $to_user_info['friendship_level_dynamic']);
        $result['data']['friendship_level'] = $level_info['level'];
        $result['data']['is_look_info'] = $level_info['is_look'];
        $where = ['b.uid' => $touid];
        $bzone_list = $this->BzoneModel->get_list($uid, $where, $page);

        $result['data']['list'] = $bzone_list;
        return_json_encode($result);
    }

    /*
     * 设置*/
    //密友等级
    public function get_friend_level()
    {
        $result = array('code' => 1, 'msg' => '');
        $list = Db::name('friendship_level')
            ->field('id,name')
            ->order('sort')
            ->select();
        $result['data']['info_list'] = $list;
        $result['data']['video_list'] = $list;
        $result['data']['dynamic_list'] = $list;
        return_json_encode($result);
    }

    //我设置的密友等级
    public function get_my_friend_level()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['friendship_level_info', 'friendship_level_video', 'friendship_level_dynamic']);
        //我的等级
        $identity = get_user_identity($uid);
        if ($identity == 2 || $identity == 4) {
            $level_info = $user_info['friendship_level_info'] ? $user_info['friendship_level_info'] : 1;
            $list['friendship_level_info'] = $level_info;
            $level_video = $user_info['friendship_level_video'] ? $user_info['friendship_level_video'] : 1;
            $list['friendship_level_video'] = $level_video;
            $level_dynamic = $user_info['friendship_level_dynamic'] ? $user_info['friendship_level_dynamic'] : 1;
            $list['friendship_level_dynamic'] = $level_dynamic;
            $list['is_talker'] = 1;
        } else {
            $list['is_talker'] = 0;
        }

        $result['data'] = $list;
        return_json_encode($result);
    }

    //密友权限设置
    public function set_friend_authority()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['friendship_level_info', 'friendship_level_video', 'friendship_level_dynamic']);
        //查看个人资料 等级ID
        $friendship_level_info = input('param.friendship_level_info');
        //查看小视频
        $friendship_level_video = input('param.friendship_level_video');
        //查看动态
        $friendship_level_dynamic = input('param.friendship_level_dynamic');

        $data = [
            'friendship_level_info'    => $friendship_level_info,
            'friendship_level_video'   => $friendship_level_video,
            'friendship_level_dynamic' => $friendship_level_dynamic,
        ];
        $res = $this->UserModel->upd_user($uid, $token, $data);
        return_json_encode($result);
    }

    //聊天背景、气泡
    public function get_chat_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $os = input('param.os');
        $user_info = check_login_token($uid, $token, ['income', 'friend_coin']);
        $chat_bubble = get_user_dress_up($uid, 4);
        $chat_bg = get_user_dress_up($uid, 5);
        $data = ['chat_bubble' => '', 'chat_bubble_ios' => '', 'chat_bg' => ''];
        if ($chat_bubble) {
            $data['chat_bubble'] = $chat_bubble['icon'];
            $data['chat_bubble_ios'] = $chat_bubble['ios_icon'];
        }
        if ($chat_bg) {
            $data['chat_bg'] = $chat_bg['id'];
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 个人中心 自己
    public function personal_center()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));

        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token, [
            'luck',
            'signature',
            'age',
            'is_voice_online',
            'occupation',
            'last_remove_message_time',
            'create_time',
            'is_named_user',
            'nobility_level',
            'noble_end_time',
            'custom_audio_call_coin',
            'custom_video_call_coin',
            'coin_system',
            'income',
            'consumption_total',
            'friend_coin'
        ]);
        // 粉丝
        $user_info['fans_count'] = $this->UserModel->fans_count($uid);
        // 关注
        $user_info['focus_count'] = $this->UserModel->focus_count($uid);

        $user_info['luck'] = $user_info['luck'] ? $user_info['luck'] : '';
//        $talker_level = get_talker_level($uid);
//        $player_level = get_player_level($uid);
//        $user_info['talker_level_name'] = $talker_level['talker_level_name'];
//        $user_info['talker_level_img'] = $talker_level['talker_level_img'];
//        $user_info['player_level_name'] = $player_level['player_level_name'];
//        $user_info['player_level_img'] = $player_level['player_level_img'];
        $noble = get_noble_level($user_info['id']);
        $user_info['noble_name'] = $noble['noble_name'];
        $user_info['noble_img'] = $noble['noble_img'];
        $user_info['user_name_colors'] = $noble['colors'];
        //头像框
        $uid_dress = get_user_dress_up($uid, 3);
        $user_info['user_avatar_frame'] = '';
        if ($uid_dress) {
            $user_info['user_avatar_frame'] = $uid_dress['icon'];
        }
        if ($user_info['noble_end_time'] > NOW_TIME) {
            $user_info['noble_end_time'] = date('Y-m-d', $user_info['noble_end_time']);
            $user_info['is_noble'] = 1;
        } else {
            $user_info['is_noble'] = 0;
            $user_info['noble_end_time'] = 0;
        }

        // 财富等级
        $levelInfo = getWealthLevelRuleInfoByTotalValue($user_info['consumption_total']);

        if ($levelInfo != null) {
            $user_info['level_img'] = $levelInfo['chat_icon'];
        } else {
            $user_info['level_img'] = '';
        }
        $touid = input('param.touid') ? input('param.touid') : $uid;
        $talker_level = get_talker_level($touid);
        $player_level = get_player_level($touid);
        $user_info['consumption_level_icon'] = get_user_level($touid, 'chat_icon');
        $user_info['income_level_icon'] = get_user_income_level($touid, 'chat_icon');
        $user_info['talker_level_name'] = $talker_level['talker_level_name'];
        $user_info['talker_level_img'] = $talker_level['talker_level_img'];
        $user_info['player_level_name'] = $player_level['player_level_name'];
        $user_info['player_level_img'] = $player_level['player_level_img'];
        $user_info['img_list'] = $this->UserModel->user_img($touid);
        $country = get_country_one(intval($user_info['country_code']));
        $user_info['country_flag_img_url'] = '';
        
        if ($country) {
            $user_info['country_flag_img_url'] = $country['img'];
        }
        $user_info['medals'] = $user_medal = Db::table('bogo_user_medals')
       ->alias('um')
       ->join('bogo_dress_up m', 'um.medal_id = m.id')
       ->where('user_id', $uid)
       ->select();
        
        $user_info['visitors_count'] = $this->UserModel->visitors_count($uid);

        $result['data'] = $user_info;

        return_json_encode($result);
    }

    // 判断vip是否到期
    public function get_is_vip()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => []);

        $uid = intval(input('param.uid'));

        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token, ['vip_end_time']);

        $result['data']['is_vip'] = get_is_vip($user_info['vip_end_time']);

        return_json_encode($result);
    }

    // 判断是否认证
    public function is_auth()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token);

        $result['is_auth'] = $user_info['is_auth'];

        if ($user_info['is_auth'] != 1) {

            $auth_list = $this->UserModel->get_auth_form_record($uid);

            $result['is_auth'] = $auth_list ? $auth_list['status'] : '-1';
        }

        return_json_encode($result);
    }

    // 获取粉丝列表
    public function get_fans_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));

        $page = intval(input('param.page'));

        $key_word = trim(input('param.key_word')) ? trim(input('param.key_word')) : '';
        $where = "a.attention_uid=" . $uid;
        $where .= $key_word ? " and (u.id like '%" . $key_word . "%' or u.user_nickname like '%" . $key_word . "%' or u.luck like '%" . $key_word . "%')" : '';

        // 获取粉丝列表
        $attention = $this->UserModel->focus_fans_list($where, $page, "a.uid=u.id");
        $ShopModel = new ShopModel();
        foreach ($attention as &$v) {
            // 是否关注对方
            $focus = $this->UserModel->is_focus_user($uid, $v['id']);
            $v['focus'] = $focus ? 1 : 0;
            // 本用户是否在语音房间
            $voice_id = redis_hGet("user_voice", $v['id']);
            $v['voice_id'] = $voice_id ? $voice_id : 0;
            $noble = get_noble_level($v['id']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
            $shop = $ShopModel->get_user_shop($v['id']);
            // 头饰图片
            $v['headwear_url'] = $shop['headwear_url'];
        }

        $result['data'] = $attention;

        return_json_encode($result);
    }

    //获取关注用户列表
    public function get_follow_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));

        $page = intval(input('param.page'));

        $key_word = trim(input('param.key_word')) ? trim(input('param.key_word')) : '';
        $where = "a.uid=" . $uid;
        $where .= $key_word ? " and (u.id like '%" . $key_word . "%' or u.user_nickname like '%" . $key_word . "%' or u.luck like '%" . $key_word . "%')" : '';
        // 获取关注列表
        $attention = $this->UserModel->focus_fans_list($where, $page, "a.attention_uid=u.id");
        $ShopModel = new ShopModel();
        foreach ($attention as &$v) {
            // 是否关注对方
            $focus = $this->UserModel->is_focus_user($v['id'], $uid);
            $v['focus'] = $focus ? 1 : 0;
            // 本用户是否在语音房间
            $voice_id = redis_hGet("user_voice", $v['id']);
            $v['voice_id'] = $voice_id ? $voice_id : 0;
            $noble = get_noble_level($v['id']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
            $shop = $ShopModel->get_user_shop($v['id']);
            // 头饰图片
            $v['headwear_url'] = $shop['headwear_url'];
        }

        $result['data'] = $attention;

        return_json_encode($result);
    }

    /*
    * 更新用户城市经纬度
    */
    public function refresh_city()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $province = trim(input('param.province'));
        $city = trim(input('param.city'));
        $longitude = trim(input('param.longitude'));
        $latitude = trim(input('param.latitude'));
        // 经纬度
        $data['longitude'] = $longitude;
        $data['latitude'] = $latitude;
        //city 城市 province
        $address = input('param.address', '');
        if ($address) {
            $data['address'] = $address;
        } else {
            $data['address'] = $province . $city;
        }
        if ($province) {
            $data['province'] = $province;
        }
        if ($city) {
            $data['city'] = $city;
        }

        $status = db('user')->where('id = ' . $uid)->update($data);
        //$status = $this->UserModel ->upd_user($uid,$token,$data);
        if (!$status) {
            $result['code'] = 0;
            $result['msg'] = lang('seek_failed');
        }
        return_json_encode($result);
    }

    // 用户是否绑定手机号码和是否认证
    public function is_binding_mobile()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['mobile', 'is_auth']);

        $config = load_cache('config');

        $data['status'] = $config['is_binding_mobile'];
        // 1 已绑定 0未绑定手机号
        $data['is_binding_mobile'] = $user_info['mobile'] ? 1 : 0;
        // 手机号
        $data['mobile'] = $user_info['mobile'];
        // 是否认证
        $data['is_auth'] = $user_info['is_auth'] == 1 ? 1 : '-1';

        // 用户认证信息
        $auth_list = $this->UserModel->get_auth_form_record($uid);

        if ($data['is_auth'] != 1) {

            $data['is_auth'] = $auth_list ? $auth_list['status'] : '-1';
        }

        // 获取身份证号码
        $data['id_number'] = $auth_list ? $auth_list['id_number'] : '';
        // 获取真实姓名
        $data['user_name'] = $auth_list ? $auth_list['user_nickname'] : '';

        $result['data'] = $data;

        return_json_encode($result);
    }

    // 绑定手机号和更改手机号
    public function binging_mobile()
    {
        $result = array('code' => 0, 'msg' => lang('Operation_successful'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $mobile = trim(input('param.mobile'));
        $code = trim(input('param.code'));

        $user_info = check_login_token($uid, $token, ['is_reg_perfect', 'mobile', 'address']);

        if (!is_numeric($mobile)) {
            $result['msg'] = lang('Incorrect_mobile_phone_number');
            return_json_encode($result);
        }
        //查询手机号是否注册过
        $is_mobile = $this->UserModel->get_moble_user($mobile);
        if ($is_mobile) {
            $result['msg'] = lang('Mobile_phone_has_been_registered');
            return_json_encode($result);
        }
        if ($code == 0) {
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }
        // 获取验证码
        $ver = $this->LoginModel->get_verification_code($code, $mobile);

        if (!$ver) {
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }
        // 修改手机号
        $data = $this->UserModel->upd_user($uid, $token, array("mobile" => $mobile));

        if (!$data) {
            $result['msg'] = lang('operation_failed');
            return_json_encode($result);
        }

        $result['data'] = array(
            'id'             => $user_info['id'],
            'token'          => $token,
            'sex'            => $user_info['sex'],
            'user_nickname'  => $user_info['user_nickname'],
            'avatar'         => $user_info['avatar'],
            'address'        => $user_info['address'],
            'is_reg_perfect' => $user_info['is_reg_perfect'],
        );

        $signature = load_cache('usersign', ['id' => $user_info['id']]);
        $result['data']['user_sign'] = $signature['usersign'];
        task_reward(1, $user_info['id']);
        $result['code'] = 1;
        return_json_encode($result);
    }

    //获取好友列表互相关注的
    public function get_friends_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page'));
        $key_word = trim(input('param.key_word')) ? trim(input('param.key_word')) : '';
        $where = "a.uid=" . $uid;
        $where .= $key_word ? " and (u.id like '%" . $key_word . "%' or u.user_nickname like '%" . $key_word . "%' or u.luck like '%" . $key_word . "%')" : '';
        // 获取粉丝列表
        $attention = $this->UserModel->focus_fans_list($where, $page, "a.attention_uid=u.id");

        $list = [];
        foreach ($attention as &$v) {
            $focus = $this->UserModel->is_focus_user($v['id'], $uid);
            //本用户是否在语音房间
            $voice_id = redis_hGet("user_voice", $v['id']);

            $v['voice_id'] = $voice_id ? $voice_id : 0;
            if ($focus) {
                // 互相关注的
                $list[] = $v;
            }
        }

        $result['data'] = $list;
        return_json_encode($result);
    }

    //举报用户
    public function do_report_user()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $type = trim(input('param.type'));
        //$content = trim(input('param.content'));

        $user_info = check_login_token($uid, $token);
        $type_info = db('user_report_type')->find($type);
        if (!$type_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Report_type_does_not_exist');
            return_json_encode($result);
        }
        $content = $type_info['title'];

        //添加记录
        $report_record = [
            'uid'        => $uid,
            'reportid'   => $to_user_id,
            'reporttype' => $type,
            'content'    => $content,
            'addtime'    => NOW_TIME,
        ];

        $log_id = db('user_report')->insertGetId($report_record);

        /*$img = request()->file(); //获取举报图
        if (count($img) > 3) {
            $result['code'] = 0;
            $result['msg'] = '图片数量最多3张';
            return_json_encode($result);
        }

        $data = [];
        foreach ($img as $k => $v) {
            $uploads = oss_upload($v); //单图片上传
            if ($uploads) {
                $data[$k]['report'] = $log_id;
                $data[$k]['addtime'] = NOW_TIME;
                $data[$k]['img'] = $uploads;
            }
        }
        //举报截图
        db('user_report_img')->insertAll($data);*/

        return_json_encode($result);

    }


    /*
    * 免打扰设置
    */
    // public function request_set_do_not_disturb()
    // {

    //     $result = array('code' => 1, 'msg' => '');
    //     $uid = intval(input('param.uid'));
    //     $token = trim(input('param.token'));
    //     $type = intval(input('param.type'));

    //     $user_info = check_login_token($uid, $token);

    //     if ($type == 1) {
    //         db('user')->where('id', '=', $uid)->setField('is_open_do_not_disturb', 1);
    //     } else {
    //         db('user')->where('id', '=', $uid)->setField('is_open_do_not_disturb', 0);
    //     }
    //     return_json_encode($result);

    // }


    //返回该用户的礼物柜
    // public function request_get_gift_cabinet()
    // {
    //     $result = array('code' => 1, 'msg' => '');
    //     $to_user_id = intval(input('param.to_user_id'));

    //     $gift = db('gift')->select();
    //     foreach ($gift as &$g) {
    //         $g['gift_count'] = db('user_gift_log')
    //             ->where('to_user_id', '=', $to_user_id)
    //             ->where('gift_id', '=', $g['id'])
    //             ->sum('gift_count');
    //     }
    //     $result['gift_list'] = $gift;

    //     return_json_encode($result);
    // }

    /*
    * 自动打招呼设置
    */
    // public function request_set_auto_see_hi()
    // {

    //     $result = array('code' => 1, 'msg' => '');
    //     $uid = intval(input('param.uid'));
    //     $token = trim(input('param.token'));
    //     $type = intval(input('param.type'));

    //     $user_info = check_login_token($uid, $token);

    //     if ($type == 1) {
    //         db('user')->where('id', '=', $uid)->setField('is_open_auto_see_hi', 1);
    //     } else {
    //         db('user')->where('id', '=', $uid)->setField('is_open_auto_see_hi', 0);
    //     }
    //     return_json_encode($result);

    // }


    //获取自动打招呼模板消息
    public function request_get_auto_smg_tpl_info()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token);

        $info = db('custom_auto_msg')->where('user_id', '=', $uid)->find();
        //->file('auto_msg1,auto_msg2,auto_msg3,auto_msg4,auto_msg5,reply_msg1,reply_msg2,reply_msg3,reply_msg4,reply_msg5,province,city')

        if (!$info) {
            $tpl_info = [
                'auto_msg1'  => '',
                'auto_msg2'  => '',
                'auto_msg3'  => '',
                'auto_msg4'  => '',
                'auto_msg5'  => '',
                'reply_msg1' => '',
                'reply_msg2' => '',
                'reply_msg3' => '',
                'reply_msg4' => '',
                'reply_msg5' => '',
                'province'   => '',
                'city'       => '',
            ];
        } else {
            $tpl_info = $info;
        }

        $result['data'] = $tpl_info;
        return_json_encode($result);
    }

    //保存自动打招呼模板
    public function request_save_auto_smg_tpl()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token);

        $auto_msg1 = trim(input(('param.auto_msg1')));
        $auto_msg2 = trim(input(('param.auto_msg2')));
        $auto_msg3 = trim(input(('param.auto_msg3')));
        $auto_msg4 = trim(input(('param.auto_msg4')));
        $auto_msg5 = trim(input(('param.auto_msg5')));


        $auto_reply_msg1 = trim(input(('param.reply_msg1')));
        $auto_reply_msg2 = trim(input(('param.reply_msg2')));
        $auto_reply_msg3 = trim(input(('param.reply_msg3')));
        $auto_reply_msg4 = trim(input(('param.reply_msg4')));
        $auto_reply_msg5 = trim(input(('param.reply_msg5')));

        $province = trim(input(('param.province')));
        $city = trim(input(('param.city')));


        $update_data = [
            'auto_msg1'  => $auto_msg1,
            'auto_msg2'  => $auto_msg2,
            'auto_msg3'  => $auto_msg3,
            'auto_msg4'  => $auto_msg4,
            'auto_msg5'  => $auto_msg5,
            'reply_msg1' => $auto_reply_msg1,
            'reply_msg2' => $auto_reply_msg2,
            'reply_msg3' => $auto_reply_msg3,
            'reply_msg4' => $auto_reply_msg4,
            'reply_msg5' => $auto_reply_msg5,
            'province'   => $province,
            'city'       => $city,
        ];

        $exits = db('custom_auto_msg')->where('user_id', '=', $uid)->find();
        if (!$exits) {
            $update_data['user_id'] = $uid;
            $update_data['create_time'] = NOW_TIME;
            db('custom_auto_msg')->insert($update_data);
        } else {
            db('custom_auto_msg')->where('user_id=' . $uid)->update($update_data);
        }

        return_json_encode($result);
    }

    /**
     * 会话列表根据id返回用户信息
     * */
    public function get_conversation_user_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['is_talker']);

        $ids = input("param.ids");

        $id_array = explode(',', $ids);
        if (count($id_array) > 0) {
            $list = db('user')
                ->whereIn('id', $id_array)
                ->field('id,avatar,user_nickname,sex,age,is_player,is_talker,is_online,city,nobility_level,noble_end_time')
                ->select();
            foreach ($list as &$v) {
                $dress_up = get_user_dress_up($v['id'], 3);
                $v['avatar_frame'] = '';
                if ($dress_up) {
                    $v['avatar_frame'] = $dress_up['icon'];
                }
                //陪聊等级
                $talker_level = get_talker_level($v['id']);
                $v['talker_level_name'] = $talker_level['talker_level_name'];
                $v['talker_level_img'] = $talker_level['talker_level_img'];
                //陪玩等级
                $player_level = get_player_level($v['id']);
                $v['player_level_name'] = $player_level['player_level_name'];
                $v['player_level_img'] = $player_level['player_level_img'];
                //密友等级
                if ($user_info['is_talker'] == 1) {
                    $friendship = friendship_level($v['id'], $uid);
                } else {
                    $friendship = friendship_level($uid, $v['id']);
                }

                $v['friendship_level'] = $friendship['name'];
                if ($v['noble_end_time'] < NOW_TIME) {
                    $v['nobility_level'] = 0;
                }

            }
            $result['list'] = $list;
        }

        return_json_encode($result);
    }

    /*
     * 好友列表
     * */
    public function get_my_firend()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $p = input('param.page');
        $user_info = check_login_token($uid, $token, ['is_talker']);
        $list = db('user_friend')
            ->alias('f')
            ->join('user u', 'u.id=f.touid')
            ->where(['uid' => $uid])
            ->field('u.id,u.avatar,u.user_nickname,u.sex,u.age,u.is_player,u.is_talker,u.is_online,u.city,u.nobility_level,u.noble_end_time')
            ->order('u.is_online desc')
            ->group('f.touid')
            ->page($p)->select();
        if ($list) {
            foreach ($list as &$v) {
                $dress_up = get_user_dress_up($v['id'], 3);
                $v['avatar_frame'] = '';
                if ($dress_up) {
                    $v['avatar_frame'] = $dress_up['icon'];
                }
                //陪聊等级
                $talker_level = get_talker_level($v['id']);
                $v['talker_level_name'] = $talker_level['talker_level_name'];
                $v['talker_level_img'] = $talker_level['talker_level_img'];
                //陪玩等级
                $player_level = get_player_level($v['id']);
                $v['player_level_name'] = $player_level['player_level_name'];
                $v['player_level_img'] = $player_level['player_level_img'];
                //密友等级
                if ($user_info['is_talker'] == 1) {
                    $friendship = friendship_level($v['id'], $uid);
                } else {
                    $friendship = friendship_level($uid, $v['id']);
                }

                $v['friendship_level'] = $friendship['name'];
                if ($v['noble_end_time'] < NOW_TIME) {
                    $v['nobility_level'] = 0;
                }

            }

        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //分享任务回调
    public function share_it()
    {
        $result = array('code' => 1, 'msg' => '');

        $type = intval(input('param.type'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token);
        if ($type == 1) {
            //分享到朋友圈
            task_reward(5, $uid);
        }
        return_json_encode($result);
    }

    //设置青少年模式
    public function set_teens_model()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['is_named_user']);
        if ($user_info['is_named_user'] == 1) {
            db('user')->where(['id' => $uid])->update(['is_named_user' => 0]);
            $result['data']['is_named_user'] = 0;
            $result['msg'] = lang('Closed_successfully');
        } else {
            db('user')->where(['id' => $uid])->update(['is_named_user' => 1]);
            $result['data']['is_named_user'] = 1;
            $result['msg'] = lang('Open_successfully');
        }
        return_json_encode($result);
    }

    //加入工会
    public function join_guild()
    {
        $result = array('code' => 1, 'msg' => lang('Submitted_successfully'));
        $guild_id = input('param.guild_id');//工会ID
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['is_named_user']);
        //工会信息
        $guild_info = db('guild')->where('status = 1 and id = ' . $guild_id)->find();
        if (!$guild_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Union_information_error');
            return_json_encode($result);
        }
        $guild_join = db('guild_join')
            ->where('user_id = ' . $uid)
            ->find();
        if ($guild_join) {
            if ($guild_join['status'] == 0) {
                $result['code'] = 0;
                $result['msg'] = lang('Applied_join_labor_union_pending_approval');
                return_json_encode($result);
            } else if ($guild_join['status'] == 1) {
                $result['code'] = 0;
                $result['msg'] = lang('You_have_joined_labor_union');
                return_json_encode($result);
            }
            $data = [
                'guild_id'    => $guild_id,
                'status'      => 0,
                'create_time' => NOW_TIME,
            ];
            $res = db('guild_join')->where('user_id = ' . $uid)->update($data);

        } else {
            $data = [
                'user_id'     => $uid,
                'guild_id'    => $guild_id,
                'status'      => 0,
                'create_time' => NOW_TIME,
            ];
            $res = db('guild_join')->insert($data);
        }

        if (!$res) {
            $result['code'] = 0;
            $result['msg'] = lang('Submit_failed_retry');
        }
        return_json_encode($result);
    }

    //是否有工会
    public function request_is_guild()
    {
        $result = array('code' => 1, 'msg' => lang('nonunion'));
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['is_named_user']);

        $guild_join = db('guild_join')
            ->where('user_id = ' . $uid)
            ->find();
        $result['data']['status'] = 4;//未提交
        if ($guild_join) {
            if ($guild_join['status'] == 0) {
                $result['msg'] = lang('CHECK_LOADING');
            } else if ($guild_join['status'] == 1) {
                $result['msg'] = lang('PASSED');
            }
            $result['data']['status'] = $guild_join['status'];//未提交
        }
        return_json_encode($result);
    }

    /*
     * 客户端日志*/
    public function add_client_log()
    {
        $result = array('code' => 1, 'msg' => lang('ADD_SUCCESS'));
        $uid = input('param.uid');
        $token = input('param.token');
        $url = input('param.url');
        $type = input('param.type', 1);
        $user_info = check_login_token($uid, $token, ['is_named_user']);
        $data = [
            'uid'     => $uid,
            'url'     => $url,
            'type'    => $type,
            'addtime' => NOW_TIME,
        ];
        db('client_log')->insert($data);
        return_json_encode($result);
    }

    /*
     * 发布动态、短视频权限验证
     * */
    public function detection_permission()
    {
        $result = array('code' => 1, 'msg' => lang('Detection_succeeded'));
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type', 1);//1动态 2短视频
        $user_info = check_login_token($uid, $token, ['is_auth', 'is_talker', 'is_player']);
        $config = load_cache('config');
        if ($type == 1) {
            $auth = $config['upload_bzone_auth'];
            if ($auth == 1 && $user_info['is_auth'] != 1) {
                $result['code'] = 0;
                $result['msg'] = lang('Real_name_authentication_release_trends');
                //return_json_encode($result);
            } else if ($auth == 2 && $user_info['is_player'] != 1) {
                $result['code'] = 0;
                $result['msg'] = lang('Release_trends_after_accompanying_certification');
            } else if ($auth == 3 && $user_info['is_talker'] != 1) {
                $result['code'] = 0;
                $result['msg'] = lang('Release_dynamics_after_anchor_authentication');
            }
        } else {
            $auth = $config['upload_video_auth'];
            if ($auth == 1 && $user_info['is_auth'] != 1) {
                $result['code'] = 0;
                $result['msg'] = lang('Release_small_video_after_real_authentication');
                //return_json_encode($result);
            } else if ($auth == 2 && $user_info['is_player'] != 1) {
                $result['code'] = 0;
                $result['msg'] = lang('Please_post_small_video_after_certification');
            } else if ($auth == 3 && $user_info['is_talker'] != 1) {
                $result['code'] = 0;
                $result['msg'] = lang('Please_post_video_after_anchor_authentication');
            }
        }
        return_json_encode($result);
    }

    /**
     * 设置主播的通话、语音自定义收费价格
     * */
    public function set_charge_price()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $custom_video_call_coin = input('param.custom_video_call_coin');//视频通话价格
        $custom_audio_call_coin = input('param.custom_audio_call_coin');//语音通话价格
        $user_info = check_login_token($uid, $token, ['custom_audio_call_coin', 'custom_video_call_coin']);
        /*$config = load_cache('config');
        if($private_chat_coin<$config['chat_coin_min']){
            $result['code'] = 0;
            $result['msg'] = '价格不能低于'.$config['chat_coin_min'];
            return_json_encode($result);
        }
        if($private_chat_coin>$config['chat_coin_max']){
            $result['code'] = 0;
            $result['msg'] = '价格不能高于'.$config['chat_coin_max'];
            return_json_encode($result);
        }*/

        if ($custom_video_call_coin) {
            $data['custom_video_call_coin'] = $custom_video_call_coin;
        }
        if ($custom_audio_call_coin) {
            $data['custom_audio_call_coin'] = $custom_audio_call_coin;
        }
        $res = $this->UserModel->upd_user($uid, $token, $data);
        if (!$res) {
            $result['code'] = 0;
            $result['msg'] = lang('Setting_failed');
        } else {
            $result['msg'] = lang('Set_successfully');
            $result['data'] = $data;
        }
        return_json_encode($result);
    }
}
