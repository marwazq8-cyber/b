<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-06-02
 * Time: 14:30
 */

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\UserModel;

//use app\api\model\LoginModel;
use app\vue\model\VoiceModel;
use app\vue\model\BzoneModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class LoginApi extends Base
{
    private $UserModel;


    // 前端手机号登录
    public function login_vue()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = intval(input('param.mobile'));
        $code = intval(input('param.code'));
        if (!is_numeric($mobile)) {
            $result['msg'] = lang('Incorrect_mobile_phone_number');
            return_json_encode($result);
        }

        if ($code == 0) {
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }
        $verification_code = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        if (!$verification_code) {
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }
        $user_info = db('user')->where("mobile='$mobile'")->find();
        if (!$user_info) {
            $result['msg'] = lang('Login_account_does_not_exist');
            return_json_encode($result);
        }

        $result['code'] = 1;
        $result['msg'] = lang('LOGIN_SUCCESS');

        $result['data'] = array(
            'uid' => $user_info['id'],
            'token' => $user_info['token']
        );
        return_json_encode($result);
    }

}
