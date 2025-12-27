<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-21
 * Time: 09:52
 */

namespace app\api\controller;

use think\Db;
use think\helper\Time;
use app\api\model\UserModel;
use app\api\model\LoginModel;
use app\api\model\VoiceModel;
use think\Request;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class AuthApi extends Base
{
    private $UserModel;
    private $LoginModel;
    private $VoiceModel;

    protected function _initialize()
    {
        parent::_initialize();

        $this->UserModel = new UserModel();
        $this->LoginModel = new LoginModel();
        $this->VoiceModel = new VoiceModel();
    }

    /*
     * 用户身份*/
    public function get_user_identity()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        check_login_token($uid, $token);
        $user_identity = get_user_identity($uid);
        $result['data'] = [
            'user_identity' => $user_identity
        ];
        return_json_encode($result);
    }

    /*
     * 认证状态*/
    public function get_auth_status()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');

        check_login_token($uid, $token);

        $auth_form = $this->UserModel->get_auth_form_record($uid);
        $skills = $this->UserModel->get_user_auth_anchor($uid);
        $player = db('auth_player')
            ->where(['uid' => $uid, 'status' => 1])
            ->order('id desc')
            ->find();
        if (!$player) {
            $player = db('auth_player')
                ->where(['uid' => $uid])
                ->order('id desc')
                ->find();
        }

        $platform = db('platform_auth')->where(['user_id' => $uid])->order('id desc')->find();
        $data['auth_status'] = 4;
        $data['auth_skills'] = 4;
        $data['auth_player'] = 4;
        $data['auth_platform'] = 4;
        if ($auth_form) {
            $data['auth_status'] = $auth_form['status'];
        }
        if ($skills) {
            $data['auth_skills'] = $skills['status'];
        }
        if ($player) {
            $data['auth_player'] = $player['status'];
        }
        if ($platform) {
            $data['auth_platform'] = $platform['status'];
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 提交认证信息 身份认证 --实名认证 20220804添加接口 替换了request_submit_auth_info接口 --增加了手机号验证码
    public function request_submit_authentication()
    {
        $result = array('code' => 0, 'msg' => '提交失败，请稍后重试！');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');

        $nickname = $this->get_param_info('nickname');// 用户姓名
        $id_number = $this->get_param_info('id_number');// 用户身份证号码
        $phone = $this->get_param_info('phone');// 联系方式
        $code = $this->get_param_info('code');// 短信验证码
        $auth_id_card_img_url1 = $this->get_param_info('auth_id_card_img_url1');// 身份证正面照
        $auth_id_card_img_url2 = $this->get_param_info('auth_id_card_img_url2');// 身份证反面照片
        check_login_token($uid, $token);
        // 获取用户是否认证
        $is_auth = $this->UserModel->get_auth_form_record($uid);
        if ($is_auth) {
            if ($is_auth['status'] != 2) {
                $result['msg'] = '已经提交过认证信息，请勿重复提交!';
                return_json_encode($result);
            } else if ($is_auth['status'] == 2) {
                $this->UserModel->del_auth_form_record($uid);
            }

        }
        if (empty($phone)) {
            $result['msg'] = '请输入联系方式';
            return_json_encode($result);
        }
        // 验证码
        if (empty($code)) {
            $result['msg'] = '验证码错误！';
            return_json_encode($result);
        }
        // 获取验证码
        $ver = $this->LoginModel->get_verification_code($code, $phone);

        if (!$ver) {
            $result['msg'] = "验证码错误，请重新获取！";
            return_json_encode($result);
        }
        // 获取手机号的数量
        $phone_number = db('auth_form_record')->where("phone='" . $phone . "' and status !=2")->count();
        if (intval($phone_number) >= 10) {
            $result['msg'] = "认证失败,当前手机号最多认证10个用户！";
            return_json_encode($result);
        }

        if (empty($nickname)) {

            $result['msg'] = '请输入真实姓名';
            return_json_encode($result);
        }

        if (empty($id_number)) {

            $result['msg'] = '请输入身份证号码';
            return_json_encode($result);
        }
        if (empty($auth_id_card_img_url1)) {

            $result['msg'] = '请上传身份证正面照';
            return_json_encode($result);
        }
        if (empty($auth_id_card_img_url2)) {

            $result['msg'] = '请上传身份证背面照';
            return_json_encode($result);
        }
        $insert_data = [
            'user_nickname' => $nickname,
            'user_id' => $uid,
            'status' => 0,
            'phone' => $phone,
            'id_number' => $id_number,
            'create_time' => NOW_TIME,
            'auth_id_card_img_url1' => $auth_id_card_img_url1,
            'auth_id_card_img_url2' => $auth_id_card_img_url2,
        ];
        // 添加认证
        $res = $this->UserModel->add__auth_form_record($insert_data);
        if ($res) {
            $result['code'] = 1;
            $result['msg'] = '认证成功等待审核';
        }

        return_json_encode($result);
    }

    // 提交认证信息 身份认证
    public function request_submit_auth_info()
    {
        $result = array('code' => 0, 'msg' => lang('Submit_failed_retry'));

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');

        $nickname = $this->get_param_info('nickname');// 用户姓名
        $id_number = $this->get_param_info('id_number');// 用户身份证号码
        $phone = $this->get_param_info('phone');// 联系方式
        $auth_id_card_img_url1 = $this->get_param_info('auth_id_card_img_url1');// 身份证正面照
        $auth_id_card_img_url2 = $this->get_param_info('auth_id_card_img_url2');// 身份证反面照片

        $user_info = check_login_token($uid, $token, ['mobile']);
//        if (empty($user_info['mobile'])) {
//            $result['msg'] = '请绑定手机号后,再实名认证!';
//            return_json_encode($result);
//        }
        // 获取用户是否认证
        $is_auth = $this->UserModel->get_auth_form_record($uid);
        if ($is_auth) {
            if ($is_auth['status'] != 2) {
                $result['msg'] = lang('Certification_information_has_been_submitted');
                return_json_encode($result);
            } else if ($is_auth['status'] == 2) {
                $this->UserModel->del_auth_form_record($uid);
            }

        }

        if (empty($nickname)) {

            $result['msg'] = lang('Please_enter_your_real_name');
            return_json_encode($result);
        }

        if (empty($id_number)) {

            $result['msg'] = lang('Please_enter_your_ID_number');
            return_json_encode($result);
        }
        if (empty($auth_id_card_img_url1)) {

            $result['msg'] = lang('Please_upload_front_photo_your_ID_card');
            return_json_encode($result);
        }
        if (empty($auth_id_card_img_url2)) {

            $result['msg'] = lang('upload_photo_on_back_your_ID_card');
            return_json_encode($result);
        }
        $insert_data = [
            'user_nickname' => $nickname,
            'user_id' => $uid,
            'status' => 0,
            'phone' => $phone,
            'id_number' => $id_number,
            'create_time' => NOW_TIME,
            'auth_id_card_img_url1' => $auth_id_card_img_url1,
            'auth_id_card_img_url2' => $auth_id_card_img_url2,
        ];
        // 添加认证
        $res = $this->UserModel->add__auth_form_record($insert_data);

        if ($res) {

            $result['code'] = 1;
            $result['msg'] = lang('Authentication_successful_waiting_audit');
        }

        return_json_encode($result);
    }

    /*
     * 获取认证信息
     * 身份认证*/
    public function get_auth_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');

        $user_info = check_login_token($uid, $token);
        $auth_list = $this->UserModel->get_auth_form_record($uid);
        if ($auth_list) {
            $data = [
                'user_nickname' => mb_substr($auth_list['user_nickname'], 0, 1) . '****',
                'phone' => mb_substr($auth_list['phone'], 0, 3) . '****' . mb_substr($auth_list['id_number'], -2),
                'auth_id_card_img_url1' => $auth_list['auth_id_card_img_url1'],
                'auth_id_card_img_url2' => $auth_list['auth_id_card_img_url2'],
                'id_number' => mb_substr($auth_list['id_number'], 0, 4) . '****' . mb_substr($auth_list['id_number'], -2),
                'status' => $auth_list['status'],
            ];
        } else {
            $data = [
                'user_nickname' => '',
                'phone' => '',
                'auth_id_card_img_url1' => '',
                'auth_id_card_img_url2' => '',
                'id_number' => '',
                'status' => 4,
            ];
        }

        $result['data'] = $data;
        return_json_encode($result);
    }

    /*
     * 主播认证
     * */
    public function get_auth_anchor()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');

        $user_info = check_login_token($uid, $token);
        $auth_info = $this->UserModel->get_user_auth_anchor($uid);
        $data = [
            'img_list' => [],
            'status' => 4,
        ];
        if ($auth_info) {
            $data = $auth_info;
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    public function add_auth_anchor()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        $img = $this->get_param_info('img');
        $type = $this->get_param_info('type_id');

        //判读那当前用户身份
        $auth_player = db('auth_player')->where('uid = ' . $uid . ' and status != 2')->select();
        if ($auth_player) {
            $result['code'] = 0;
            $result['msg'] = lang('accompanying_player_cannot_authenticate_anchor');
            return_json_encode($result);
        }
        if (!$img) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_upload_certification_photos');
            return_json_encode($result);
        }
        $user_info = check_login_token($uid, $token, ['is_auth']);

        if ($user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_pass_identity_authentication_first');
            return_json_encode($result);
        }
        $auth_info = $this->UserModel->get_user_auth_anchor($uid);
        if ($auth_info) {
            if ($auth_info['status'] == 0) {
                $result['code'] = 0;
                $result['msg'] = lang('Submitted_certification_information_under_review');
                return_json_encode($result);
            } else {
                $this->UserModel->del_auth_anchor($uid);
                //取消认证状态
                Db::name('user')->where(['id' => $uid])->update(['is_talker' => 0]);
            }
        }
        $data = [
            //'img'=>$img,
            'uid' => $uid,
            'status' => 0,
            'type' => $type,
            'create_time' => NOW_TIME,
        ];
        $res = $this->UserModel->add_user_auth_anchor($data, $img);
        if ($res) {
            $result['msg'] = lang('Authentication_submitted_successfully');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Failed_submit_authentication');
        }

        return_json_encode($result);
    }

    //获取陪玩认证信息
    public function get_auth_player()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        $game_id = $this->get_param_info('game_id');//游戏ID
        //$game_id = intval(input(('param.game_id')));//游戏ID
        /*$img = trim(input(('param.img')));//逗号分隔
        $game_nubmer = trim(input(('param.game_nubmer')));//游戏账号
        $avatar = trim(input(('param.avatar')));//头像*/

        if (!$game_id) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_select_game');
            return_json_encode($result);
        }

        $user_info = check_login_token($uid, $token);

        $auth_info = $this->UserModel->get_user_auth_player($uid, $game_id);
        if ($auth_info) {
            $skill = db('skills_info')
                ->where(['uid' => $uid, 'game_id' => $game_id])
                ->find();
            if ($skill) {
                $auth_info['skill_id'] = $skill['id'];
            } else {
                $auth_info['skill_id'] = 0;
            }
            $data = $auth_info;
        } else {
            $data = [
                'game_id' => $game_id,
                'game_nubmer' => '',
                'uid' => $uid,
                'avatar' => '',
                'img_list' => [],
                'status' => 4,
                'create_time' => NOW_TIME,
                'skill_id' => 0
            ];
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    //陪玩师认证
    public function add_auth_player()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        $game_id = $this->get_param_info('game_id');//游戏ID
        $img = $this->get_param_info('img');//逗号分隔
        $game_nubmer = $this->get_param_info('game_number');//游戏账号

        //判读那当前用户身份
        $auth_talker = db('auth_talker')->where('uid = ' . $uid . ' and status != 2')->select();
        if ($auth_talker) {
            $result['code'] = 0;
            $result['msg'] = lang('anchor_cannot_certified_to_play_with');
            return_json_encode($result);
        }

        if (!$img) {
            $result['code'] = 0;
            $result['msg'] = lang('upload_authentication_picture');
            return_json_encode($result);
        }
        if (!$game_id) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_select_good_game');
            return_json_encode($result);
        }
        if (!$game_nubmer) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_fill_in_game_account');
            return_json_encode($result);
        }

        $user_info = check_login_token($uid, $token);
        if ($user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_pass_identity_authentication_first');
            return_json_encode($result);
        }

        //$user_info['is_player'];
        $auth_info = $this->UserModel->get_user_auth_player($uid, $game_id);
        if ($auth_info) {
            if ($auth_info['status'] == 0) {
                $result['code'] = 0;
                $result['msg'] = lang('Certification_information_under_review');
                return_json_encode($result);
            } else {
                //修改
                $data = [
                    'game_id' => $game_id,
                    'game_number' => $game_nubmer,
                    'uid' => $uid,
                    'status' => 0,
                    'create_time' => NOW_TIME,
                ];
                $res = db('auth_player')
                    ->where(['uid' => $uid, 'game_id' => $game_id])
                    ->update($data);
                //删除认证图片
                $del_img = db('auth_player_img')->where(['pid' => $auth_info['id']])->delete();
                if ($del_img) {
                    //认证图片
                    $img_arr = explode(',', $img);
                    foreach ($img_arr as $k => $v) {
                        $img_data = [
                            'uid' => $data['uid'],
                            'img' => $v,
                            'pid' => $auth_info['id'],
                            'addtime' => time(),
                        ];
                        db('auth_player_img')->insert($img_data);
                    }
                }

            } /*if($auth_info['status'] == 2){
                    $this->UserModel ->del_user_auth_player($uid,$game_id);
                }*/
            $result['msg'] = lang('Authentication_submitted_successfully');
        } else {
            $data = [
                'game_id' => $game_id,
                'game_number' => $game_nubmer,
                'uid' => $uid,
                'status' => 0,
                'create_time' => NOW_TIME,
            ];
            $res = $this->UserModel->add_user_auth_player($data, $img);
            if ($res) {
                $result['msg'] = lang('Authentication_submitted_successfully');
            } else {
                $result['code'] = 0;
                $result['msg'] = lang('Failed_submit_authentication');
            }
        }

        return_json_encode($result);
    }

    //获取官方认证信息
    public function get_auth_platform()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');

        $user_info = check_login_token($uid, $token);
        $auth_info = $this->UserModel->get_user_auth_platform($uid);
        $data = [
            'img_list' => [],
            'status' => 4,
        ];
        if ($auth_info) {
            $data = $auth_info;
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    //提交官方认证信息
    public function add_auth_platform()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        $img = $this->get_param_info('img');
        $type = $this->get_param_info('type_name');

        /*if(mb_strlen($type)>10){
            $result['code'] = 0;
            $result['msg'] = '认证类型不能超过10字！';
            return_json_encode($result);
        }*/
        //判读那当前用户身份
        if (!$img) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_upload_certification_photos');
            return_json_encode($result);
        }
        $user_info = check_login_token($uid, $token, ['is_auth']);

        /*if($user_info['is_auth'] != 1){
            $result['code'] = 0;
            $result['msg'] = '请先通过身份认证！';
            return_json_encode($result);
        }*/
        $auth_info = $this->UserModel->get_user_auth_platform($uid);
        if ($auth_info) {
            if ($auth_info['status'] == 0) {
                $result['code'] = 0;
                $result['msg'] = lang('Submitted_certification_information_under_review');
                return_json_encode($result);
            } else {
                $this->UserModel->del_auth_platform($uid);
                //取消认证状态
                //Db::name('user')->where(['id'=>$uid])->update(['is_talker'=>0]);
            }
        }
        $data = [
            //'img'=>$img,
            'user_id' => $uid,
            'status' => 0,
            'type_name' => $type,
            'create_time' => NOW_TIME,
        ];
        $res = $this->UserModel->add_user_auth_platform($data, $img);
        if ($res) {
            $result['msg'] = lang('Authentication_submitted_successfully');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Failed_submit_authentication');
        }

        return_json_encode($result);
    }

    //主播认证标签
    public function get_talker_label()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        check_login_token($uid, $token);
        $list = Db::name('auth_talker_label')->order('orderno')->select();
        $result['data'] = $list;
        return_json_encode($result);
    }

    //官方认证类型
    public function get_platform_type()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        check_login_token($uid, $token);
        $list = Db::name('platform_auth_type')->order('orderno')->select();
        $result['data'] = $list;
        return_json_encode($result);
    }

    //游戏详情
    public function get_game_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $game_id = $this->get_param_info('game_id');
        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        check_login_token($uid, $token);
        $data = Db::name('play_game')->find($game_id);
        $result['data'] = $data;
        return_json_encode($result);
    }

    //已认证的游戏
    public function get_auth_game()
    {
        $result = array('code' => 1, 'msg' => '');
        //$game_id = intval(input('param.game_id'));
        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        check_login_token($uid, $token);
        $list = Db::name('auth_player')
            ->alias('a')
            ->join('play_game p', 'p.id=a.game_id')
            ->where(['a.uid' => $uid, 'a.status' => 1])
            //->whereOr(['a.uid'=>$uid,'a.status'=>0])
            ->field('p.*')
            ->order('a.create_time desc')
            ->select();

        $game_array = [];
        foreach ($list as &$val) {
            //$game_array();
            array_push($game_array, $val['id']);
            $skill = db('skills_info')
                ->where(['uid' => $uid, 'game_id' => $val['id']])
                ->find();
            if ($skill) {
                $val['skill_id'] = $skill['id'];
            } else {
                $val['skill_id'] = 0;
            }

        }

        $auth_list = [
            ['id' => 0,
                'type_name' => lang('ADMIN_AUTH_YES'),
                'game_list' => $list
            ]
        ];

        $type_list = Db::name('play_game_type')
            ->order('orderno')
            ->field('id,type_name')
            ->select();
        if ($type_list) {
            $map['id'] = ['not in', $game_array];
            foreach ($type_list as &$val) {
                $val['game_list'] = Db::name('play_game')
                    ->where('type_id = ' . $val['id'])
                    ->where($map)
                    ->order('orderno')
                    ->field('id,name,img')
                    ->select();
                foreach ($val['game_list'] as &$v) {
                    $v['skill_id'] = '';
                }
            }
        }


        //dump($game_array);
        //die();
        $result['data']['auth_list'] = $auth_list;
        $result['data']['game_list'] = $type_list;
        return_json_encode($result);
    }

    //取消陪玩认证
    public function cancel_auth_player()
    {
        $result = array('code' => 1, 'msg' => '');

        $game_id = $this->get_param_info('game_id');
        $uid = $this->get_param_info('uid');
        $token = $this->get_param_info('token');
        check_login_token($uid, $token);
        //查看认证状态

        $info = $this->UserModel->get_user_auth_player($uid, $game_id);
        if (!$info) {
            $result['code'] = 0;
            $result['msg'] = lang('Accompanying_game_information_error');
            return_json_encode($result);
        }

        //修改状态
        $res = db('auth_player')
            ->where(['uid' => $uid, 'game_id' => $game_id])
            ->update(['status' => 4]);
        if ($res) {
            //查看是否有其他游戏认证
            $auth = db('auth_player')
                ->where(['uid' => $uid, 'status' => 1])
                ->find();
            if (!$auth) {
                //陪玩师认证状态
                Db::name('user')->where(['id' => $uid])->update(['is_player' => 0]);
            }
            //是否有陪玩信息
            $skills_info = Db::name('skills_info')
                ->where(['uid' => $uid, 'game_id' => $game_id])
                ->find();
            if ($skills_info) {
                //删除接单信息
                Db::name('skills_info')
                    ->where(['uid' => $uid, 'game_id' => $game_id])
                    ->delete();
                //->update(['status'=>4]);
            }
            $result['msg'] = lang('Cancellation_succeeded');
        }
        return_json_encode($result);
    }
}
