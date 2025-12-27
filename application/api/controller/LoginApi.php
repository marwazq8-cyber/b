<?php

namespace app\api\controller;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\api\model\SmsModel;
use app\api\model\VoiceModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use im\BogoIM;
use Overtrue\EasySms\PhoneNumber;
use think\Cache;
use think\captcha\Captcha;
use think\Db;
use think\Model;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
// 登录相关

class LoginApi extends Base
{

    private $otpConfig;

    protected function _initialize()
    {
        parent::_initialize();

        $this->otpConfig = config('app.OTP');
    }

    /**
     * facebook登录
     */
    public function facebook_login()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $facebook_token = input('param.facebook_token');
        $invite_code = trim(input('param.invite_code'));
        $agent_code = input('param.agent', '', 'trim');
        $uuid = input('param.uuid', '', 'trim');
        $os = input('param.os', '', 'trim');
        $config = load_cache('config');
        $app_id = $config['facebook_app_id'];
        $app_secret = $config['facebook_app_secret'];
        $fb = new \Facebook\Facebook([
            'app_id'                => $app_id,
            'app_secret'            => $app_secret,
            'default_graph_version' => 'v2.10',
            //'default_access_token' => '{access-token}', // optional
        ]);
        try {
            // Get the \Facebook\GraphNode\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            $response = $fb->get('/me', $facebook_token);
            $me = $response->getGraphUser();
//            echo 'Logged in as ' . $me->getName();
            $value = array(
                'facebook_id' => $me['id'],
                'email'       => isset($me['email']) ? $me['email'] : '',
                'name'        => isset($me['name']) ? $me['name'] : '',
                'picture'     => isset($payload['picture']) ? $payload['picture'] : '',
            );
            $result_val = $this->login_ok(3, $value, 0, $uuid, $invite_code, $agent_code, $os);
            $result['code'] = $result_val['code'];
            $result['msg'] = $result_val['msg'];
            $result['data'] = $result_val['data'];

        } catch (\Facebook\Exception\FacebookResponseException $e) {
            // When Graph returns an error
            $result['msg'] = $e->getMessage();
        } catch (\Facebook\Exception\FacebookSDKException $e) {
            // When validation fails or other local issues
            $result['msg'] = $e->getMessage();
        }
        return_json_encode($result);
    }

    /**
     * 谷歌登录
     */
    public function google_login()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $google_token = input('param.google_token');
        $invite_code = trim(input('param.invite_code'));
        $agent_code = input('param.agent', '', 'trim');
        $uuid = input('param.uuid', '', 'trim');
        $os = input('param.os', '', 'trim');
        $config = load_cache('config');

        $CLIENT_ID = '';
        if (preg_match('/(iPhone|iPad|iPod)/', $_SERVER['HTTP_USER_AGENT'])) {
            /*    echo "这是iOS手机";*/
            $CLIENT_ID = $config['google_ios_client_id'];
        } elseif (preg_match('/Android/', $_SERVER['HTTP_USER_AGENT'])) {
            /*echo "这是安卓手机";*/
            $CLIENT_ID = $config['google_client_id'];
        }

        $client = new \Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend

        $payload = $client->verifyIdToken($google_token);

        if ($payload) {
            // If request specified a G Suite domain:
            //$domain = $payload['hd'];
            $value = array(
                'google_sub' => $payload['sub'],
                'email'      => $payload['email'] ?? '',
                'name'       => $payload['name'] ?? '',
                'picture'    => $payload['picture'] ?? '',
            );

            $result_val = $this->login_ok(2, $value, 0, $uuid, $invite_code, $agent_code, $os);
            $result['code'] = $result_val['code'];
            $result['msg'] = $result_val['msg'];
            $result['data'] = $result_val['data'];
        } else {
            // Invalid ID token
            $result['msg'] = lang('Parameter_error');
        }
        return_json_encode($result);
    }

    /**
     * 登录成功或注册成功封装
     * type 1游邮箱 2 谷歌 3 facebook
     * login_argument 登录验证的参数
     * ver_id 后台验证码id
     */
    public function login_ok($type, $login_array, $ver_id, $uuid, $invite_code, $agent_code, $os, $address = '')
    {
        $result = array('code' => 0, 'msg' => lang('Parameter_error'));

        // 不是测试账号检测IP是否是可用地区
        $is_test_account = false;

        if ($type == 2 && in_array($login_array['email'], config('app.test_google_account'))) {
            $is_test_account = true;
        }

        $result = $this->checkLimitAreaUser($is_test_account, $result);

        $config = load_cache('config');

        $name = '';
        $avatar = '';
        $login_type = 0;
        if ($type == 1) {
            $login_argument = $login_array['email'];
            $login_type = 4;
            $user_info = db('user')->where('user_email', $login_argument)->where('user_type', 2)->find();
        } elseif ($type == 2) {
            $login_argument = $login_array['google_sub'];
            $login_type = 5;
            $name = $login_array['name'];
            $avatar = $login_array['picture'];
            $user_info = db('user')->where('google_sub', $login_argument)->where('user_type', 2)->find();
        } else {
            $login_argument = $login_array['facebook_id'];
            $login_type = 6;
            $user_info = db('user')->where('facebook_id', $login_argument)->where('user_type', 2)->find();
        }
        $email = $login_array['email'];
        $country_code = get_country_code();

        if (!$user_info) {
            if ($login_array['email'] && ($type == 2 || $type == 3)) {
                $user_info = db('user')->where('user_email', $login_array['email'])->where('user_type', 2)->order("user_type asc")->find();
                if ($user_info) {
                    db('user')->where('id', $user_info['id'])->update(['google_sub' => $login_array['google_sub']]);
                }
            }
        }
        if ($ver_id) {
            //设置验证码过期
            db('verification_code')->where('id = ' . $ver_id)->update(['expire_time' => NOW_TIME]);
        }
        //登录
        if ($user_info) {
            if ($user_info['user_type'] == 3) {
                // 用户已注销 == 禁止登录
                $result['code'] = 0;
                $result['msg'] = lang('Account_has_been_closed');
                return_json_encode($result);
            }
            //生成token
            $token = get_login_token($user_info['id']);

            $data = array(
                'is_online'       => 1,
                'token'           => $token,
                'last_login_time' => NOW_TIME,
                'last_login_ip'   => request()->ip(),
                'device_uuid'     => $uuid,
                'country_code'    => $country_code,
                'user_email'      => $email,
                'login_type'      => $login_type
            );
            $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

            if (!$update_res) {
                $result['code'] = 0;
                $result['msg'] = lang('Login_failed');
                return_json_encode($result);
            }


            //拉黑时间到取消拉黑状态
            $shielding_time = 0;
            if ($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME) {
                $data['shielding_time'] = 0;
                $data['user_status'] = 1;
                db('user')->where('id =' . $user_info['id'] . " and user_type=2")->update($data);
                $shielding_time = 1;
            }

            if ($user_info['user_status'] != 0 || $shielding_time == 1) {
                $result['code'] = 1;
                $result['msg'] = lang('Login_successful');

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
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                update_im_user_info($user_info['id']);

                $result['data']['user_sign'] = $signature['usersign'];
            } else {
                if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                    $result['msg'] = lang('Users_not_allowed_log_in');
                } else {
                    $result['msg'] = lang('Account_has_been_hacked');
                }
                return_json_encode($result);
            }


        } else {

            //是否开启设备注册限制
            if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {
                //查询是否该设备已注册
                $is_device_reg = db('user')->where('device_uuid', '=', $uuid)->count();
                if ($is_device_reg >= OPEN_DEVICE_REG_SUM) {
                    $result['code'] = 0;
                    $result['msg'] = lang('device_has_reached_registration_limit');
                    return_json_encode($result);
                }
            }

            //检查IP是否超过注册量
            $client_ip = get_client_ip(1, true);
            $ip_log = db('ip_reg_log')->where(['ip' => $client_ip])->find();
            if ($ip_log && $ip_log['count'] >= IP_REG_MAX_COUNT) {
                $result['code'] = 0;
                $result['msg'] = lang('IP_registration_exceeds_limit');
                return_json_encode($result);
            }

            //生成token
            $token = get_login_token($login_argument);

            $data = array(
                'user_type'              => 2,
                'user_nickname'          => lang('New_registered_user') . '-' . rand(88888, 99999),
                'create_time'            => NOW_TIME,
                'last_login_time'        => NOW_TIME,
                'sex'                    => 1,
                'avatar'                 => SITE_URL . '/image/headicon.png',
                'token'                  => $token,
                'address'                => $address ? $address : lang('Mars'),
                'device_uuid'            => $uuid,
                'registered_device_uuid' => $uuid,
                'is_online'              => 1,
                'os'                     => $os,
                'country_code'           => $country_code,
                'user_email'             => $email
            );
            if ($type == 1) {
                $data['user_email'] = $login_argument;
                $user_account_type = 6;
            } elseif ($type == 2) {
                $user_account_type = 5;
                $data['google_sub'] = $login_argument;
            } else {
                $user_account_type = 4;
                $data['facebook_id'] = $login_argument;
            }
            $reg_result = db('user')->insertGetId($data);

            if ($reg_result) {
                $result['data'] = array(
                    'id'             => $reg_result,
                    'token'          => $token,
                    'is_reg_perfect' => 0
                );

                $signature = load_cache('usersign', ['id' => $reg_result]);

                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                //添加邀请码
                create_invite_code_0910($reg_result);

                //注册邀请奖励业务
                reg_invite_service($reg_result, $invite_code);

                update_im_user_info($reg_result);

                $user_info = db('user')->where("id='$reg_result'")->find();
                $result['data']['user_sign'] = $signature['usersign'];

                $result['code'] = 1;
                $result['msg'] = lang('login_was_successful');
            } else {
                $result['code'] = 0;
                $result['msg'] = lang('Please_re_register');
            }
        }

        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);
        login_ip_log($user_info['id'], $login_argument, $user_info['user_nickname'], 2);
        return $result;
    }

    //三方登录
    public function auth_login()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $plat_id = trim(input('param.plat_id'));
        $invite_code = trim(input('param.invite_code'));
        $agent_code = trim(input('param.agent'));
        $uuid = trim(input('param.uuid'));
        //登录方式: 1手机号 2qq 3微信 4facebook 5google 6苹果账号 7Line
        $login_type = intval(input('param.login_way')) ? intval(input('param.login_way')) : 1;
        $country_code = get_country_code();
        //是否开启设备注册限制
        if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {

            //查询是否该设备已注册
            if (empty($uuid)) {
                $result['code'] = 0;
                $result['msg'] = lang('Missing_device_ID');
                return_json_encode($result);
            }
        }
        if ($uuid) {
            // 查询设备号是否被禁用
            $equipment_closures = db('equipment_closures')->where("device_uuid='$uuid'")->find();
            if ($equipment_closures) {
                $result['code'] = 0;
                $result['msg'] = lang('此设备号已被禁止使用');
                return_json_encode($result);
            }
        }
        $config = load_cache('config');
        if ($config['open_login_qq'] != 1 && $login_type == 2) {
            $result['code'] = 0;
            $result['msg'] = lang('Login_mode_is_turned_off');
            return_json_encode($result);
        }
        if ($config['open_login_wx'] != 1 && $login_type == 3) {
            $result['code'] = 0;
            $result['msg'] = lang('Login_mode_is_turned_off');
            return_json_encode($result);
        }

        if ($config['open_login_facebook'] != 1 && $login_type == 4) {
            $result['code'] = 0;
            $result['msg'] = lang('Login_mode_is_turned_off');
            return_json_encode($result);
        }
        //第三方ID会有为空的情况处理一下
        if (empty($plat_id)) {
            $result['code'] = 0;
            $result['msg'] = lang('plat_id_ID_is_empty');
            return_json_encode($result);
        }
        $user_info = db('user')->where('plat_id', $plat_id)->where('user_type', 2)->order("user_type asc")->find();

        //登录
        if ($user_info) {
            if ($user_info['user_type'] == 3) {
                // 用户已注销 == 禁止登录
                $result['code'] = 0;
                $result['msg'] = lang('账户已注销');
                return_json_encode($result);
            }
            //生成token
            $token = get_login_token($user_info['id']);
            //更新的信息
            $data = array('token' => $token, 'country_code' => $country_code, 'last_login_time' => NOW_TIME, 'last_login_ip' => request()->ip(0, false), 'login_way' => $login_type);
            $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

            if (!$update_res) {
                $result['code'] = 0;
                $result['msg'] = lang('Login_failed');
                return_json_encode($result);
            }

            //拉黑时间到取消拉黑状态
            $shielding_time = 0;
            if ($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME) {
                $data['shielding_time'] = 0;
                $data['user_status'] = 1;
                db('user')->where('id =' . $user_info['id'] . " and user_type=2")->update($data);
                $shielding_time = 1;
            }

            if ($user_info['user_status'] != 0 || $shielding_time == 1) {
                $result['code'] = 1;
                $result['msg'] = lang('LOGIN_SUCCESS');

                $result['data'] = array(
                    'id'             => $user_info['id'],
                    'token'          => $token,
                    'sex'            => $user_info['sex'],
                    'user_nickname'  => $user_info['user_nickname'],
                    'avatar'         => $user_info['avatar'],
                    'address'        => $user_info['address'],
                    'is_reg_perfect' => $user_info['is_reg_perfect'],
                    'mobile'         => $user_info['mobile'],
                );

                $signature = load_cache('usersign', ['id' => $user_info['id']]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($user_info['id']);

                $result['data']['user_sign'] = $signature['usersign'];


            } else {
                if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                    $result['msg'] = lang('User_forbidden_login');
                } else {
                    $result['msg'] = lang('Account_has_been_hacked');
                }
            }

        } else {

            //是否开启设备注册限制
            if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {
                //查询是否该设备已注册
                $is_device_reg = db('user')->where('device_uuid', '=', $uuid)->find();
                if ($is_device_reg) {
                    $result['code'] = 0;
                    $result['msg'] = lang('device_has_reached_registration_limit');
                    return_json_encode($result);
                }
            }

            //注册
            //$id = get_max_user_id($plat_id, 'plat_id');
            $token = get_login_token($plat_id);
            $brand = trim(input('param.brand'));
            $model = trim(input('param.model'));
            $intranet_ip = trim(input('param.intranet_ip'));
            $public_ip = request()->ip();
            $data = array(
                'user_type'       => 2,
                'user_nickname'   => lang('New_registered_user') . rand(88888, 99999),
                'create_time'     => NOW_TIME,
                'last_login_time' => NOW_TIME,
                'sex'             => 0,
                'avatar'          => SITE_URL . '/image/headicon.png',
                'token'           => $token,
                'address'         => lang('the_outer_space'),
                'plat_id'         => $plat_id,
                'login_type'      => 1,
                'device_uuid'     => $uuid,
                'login_way'       => $login_type,
                'mobile'          => '',
                'intranet_ip'     => $intranet_ip,
                'brand'           => $brand,
                'model'           => $model,
                'public_ip'       => $public_ip,
                'register_device' => $uuid,
                'country_code'    => $country_code
            );

            $reg_result = db('user')->insertGetId($data);

            if ($reg_result) {
                $result['data'] = array(
                    'id'             => $reg_result,
                    'token'          => $token,
                    'is_reg_perfect' => 0
                );

                $signature = load_cache('usersign', ['id' => $reg_result]);

                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                //添加渠道代理
                //reg_full_agent_code($reg_result, $agent_code);

                //添加邀请码
                create_invite_code_0910($reg_result);

                //注册邀请奖励业务
                reg_invite_service($reg_result, $invite_code);

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($reg_result);

                $user_info = db('user')->where("id='$reg_result'")->find();
                $result['data']['user_sign'] = $signature['usersign'];

                $result['code'] = 1;
                $result['msg'] = lang('login_was_successful');

            } else {
                $result['code'] = 0;
                $result['msg'] = lang('login_has_failed');
            }
        }


        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);
        login_ip_log($user_info['id'], $user_info['mobile'], $user_info['user_nickname'], 2);
        return_json_encode($result);
    }

    // 验证码登陆注册
    public function do_login()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $area_code = input('param.area_code', 0, 'trim'); // 国际区号
        $code = intval(input('param.code'));
        $address = trim(input('param.address'));
        $invite_code = trim(input('param.invite_code'));
        $agent_code = trim(input('param.agent'));
        $uuid = trim(input('param.uuid'));

        $area_mobile = new PhoneNumber($mobile, $area_code);

        // 不是测试账号检测IP是否是可用地区
        $is_test_account = false;

        if (in_array($area_code . $mobile, config('app.test_mobile_account'))) {
            $is_test_account = true;
        }

        $config = load_cache('config');

        if (strpos($config['mobile_shelf_use'], (string)$area_mobile) !== false) {
            $is_test_account = true;
        }

        $result = $this->checkLimitAreaUser($is_test_account, $result);

        $country_code = get_country_code();

        //是否开启设备注册限制
        if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {

            //查询是否该设备已注册
            if (empty($uuid)) {
                $result['code'] = 0;
                $result['msg'] = lang('Missing_device_ID');
                return_json_encode($result);
            }
        }
        if ($uuid) {
            // 查询设备号是否被禁用
            $equipment_closures = db('equipment_closures')->where("device_uuid='$uuid'")->find();
            if ($equipment_closures) {
                $result['code'] = 0;
                $result['msg'] = lang('此设备号已被禁止使用');
                return_json_encode($result);
            }
        }
        if (!is_numeric($mobile) || strlen($mobile) <= 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Incorrect_mobile_phone_number');
            return_json_encode($result);
        }

        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }


        if ($this->otpConfig['is_open'] && !$is_test_account) {
            // 判断如果开启了 OTP ，参数必须携带 opt_message_id 参数 string 类型，否则返回错误
            $opt_message_id = input('param.otp_message_id');
            if (!$opt_message_id) {
                $result['code'] = 0;
                $result['msg'] = 'otp_message_id is must!';
                return_json_encode($result);
            }

            $authString = base64_encode($this->otpConfig['app_key'] . ':' . $this->otpConfig['app_secret']);
            $headers = array(
                "Authorization: Basic " . $authString,
                "Content-Type:application/json"
            );
            $to_area_mobile = $area_mobile;
            if (substr($to_area_mobile, 0, 1) !== '+') {
                $to_area_mobile = '+' . $to_area_mobile;
            }
            $sms_data = array(
                'message_id'  => $opt_message_id,
                'verify_code' => (string)$code,
            );
            $url = "https://otp.api.engagelab.cc/v1/verifications";
            $re = tripartite_post($url, json_encode($sms_data), $headers);
            // 打印请求响应
            bogokjLogPrint('OTP', [
                'verify_res' => $re,
            ]);

            if (isset($re['code']) || !isset($re['verified']) || !$re['verified']) {
                // 验证失败
                $result['code'] = 0;
                $result['msg'] = $re['message'];
                return_json_encode($result);
            }

            $ver = true;
        } else {

            $ver = db('verification_code')
                ->where('phone_area_code', $area_code)
                ->where('code', $code)
                ->where('account', $mobile)
                ->where('expire_time', '>', NOW_TIME)
                ->find();
        }

        if (!$ver) {

            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);

        } else {

            // 根据区号+手机号码查询用户是否已经注册

            $user_info = db('user')->where('mobile_area_code', $area_code)
                ->where('mobile', $mobile)
                ->where('user_type', 2)
                ->find();

            //登录
            if ($user_info) {
                if ($user_info['user_type'] == 3) {
                    // 用户已注销 == 禁止登录
                    $result['code'] = 0;
                    $result['msg'] = lang('账户已注销');
                    return_json_encode($result);
                }
                //生成token
                $token = get_login_token($user_info['id']);
                //更新的信息
                $data = array('token' => $token, 'country_code' => $country_code, 'last_login_time' => NOW_TIME, 'last_login_ip' => request()->ip(0, false),);
                $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

                if (!$update_res) {
                    $result['code'] = 0;
                    $result['msg'] = lang('Login_failed');
                    return_json_encode($result);
                }
                //拉黑时间到取消拉黑状态
                $shielding_time = 0;
                if ($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME) {
                    $data['shielding_time'] = 0;
                    $data['user_status'] = 1;
                    db('user')->where('id =' . $user_info['id'] . " and user_type=2")->update($data);
                    $shielding_time = 1;
                }

                if ($user_info['user_status'] != 0 || $shielding_time == 1) {
                    $result['code'] = 1;
                    $result['msg'] = lang('LOGIN_SUCCESS');

                    $result['data'] = array(
                        'id'             => $user_info['id'],
                        'token'          => $token,
                        'sex'            => $user_info['sex'],
                        'user_nickname'  => $user_info['user_nickname'],
                        'avatar'         => $user_info['avatar'],
                        'address'        => $user_info['address'],
                        'is_reg_perfect' => $user_info['is_reg_perfect'],
                        'mobile'         => $user_info['mobile'],
                    );

                    $signature = load_cache('usersign', ['id' => $user_info['id']]);
                    if ($signature['status'] != 1) {
                        $result['code'] = 0;
                        $result['msg'] = $signature['error'];
                        return_json_encode($result);
                    }

                    update_im_user_info($user_info['id']);

                    $result['data']['user_sign'] = $signature['usersign'];

                    if (config('app.open_bogo_im')) {
                        $bogoIM = new BogoIM();
                        $result['data']['im_token'] = $bogoIM->loginIM($result['data']['id'], $result['data']['token']);
                    }

                } else {
                    if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                        $result['msg'] = lang('User_forbidden_login');
                    } else {
                        $result['msg'] = lang('Account_has_been_hacked');
                    }
                }

            } else {

                if (IS_TEST) {
                    // 测试模式下仅允许固定测试账号注册新账户
                    $result['code'] = 0;
                    $result['msg'] = 'Please use the test account provided by the business to log in and experience it!';
                    return_json_encode($result);
                }

                //是否开启设备注册限制
                if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {
                    //查询是否该设备已注册
                    $is_device_reg = db('user')->where('device_uuid', '=', $uuid)->find();
                    if ($is_device_reg) {
                        $result['code'] = 0;
                        $result['msg'] = lang('device_has_reached_registration_limit');
                        return_json_encode($result);
                    }
                }

                //检查IP是否超过注册量
                $client_ip = request()->ip();
                $ip_log = db('ip_reg_log')->where(['ip' => $client_ip])->find();
                if ($ip_log && $ip_log['count'] >= IP_REG_MAX_COUNT) {
                    $result['code'] = 0;
                    $result['msg'] = lang('IP_registration_exceeded_limit');
                    return_json_encode($result);
                }

                //生成ID
                //$id = get_max_user_id($mobile);
                //生成token
                $token = get_login_token($mobile);
                $brand = input('param.brand', '');
                $model = input('param.model', '');
                $intranet_ip = input('param.intranet_ip', '');

                $data = array(
                    'user_type'        => 2,
                    'user_nickname'    => lang('New_registered_user') . rand(88888, 99999),
                    'create_time'      => NOW_TIME,
                    'last_login_time'  => NOW_TIME,
                    'sex'              => 0,
                    'avatar'           => SITE_URL . '/image/headicon.png',
                    'mobile'           => $mobile,
                    'mobile_area_code' => $area_code,
                    'token'            => $token,
                    'address'          => $address ?: lang('the_outer_space'),
                    'device_uuid'      => $uuid,
                    'intranet_ip'      => $intranet_ip,
                    'brand'            => $brand,
                    'model'            => $model,
                    'public_ip'        => $client_ip,
                    'register_device'  => $uuid,
                    'country_code'     => $country_code,
                );

                $reg_result = db('user')->insertGetId($data);

                if ($reg_result) {
                    $result['data'] = array(
                        'id'             => $reg_result,
                        'token'          => $token,
                        'is_reg_perfect' => 0,
                        'mobile'         => $mobile,
                    );

                    $signature = load_cache('usersign', ['id' => $reg_result]);
                    if ($signature['status'] != 1) {
                        $result['code'] = 0;
                        $result['msg'] = $signature['error'];
                        return_json_encode($result);
                    }
                    $result['data']['user_sign'] = $signature['usersign'];

                    //增加注册IP记录
                    if ($ip_log) {
                        db('ip_reg_log')->where(['ip' => $client_ip])->setInc('count', 1);
                    } else {
                        db('ip_reg_log')->insert(['ip' => $client_ip, 'count' => 1]);
                    }

                    //添加渠道代理
                    reg_full_agent_code($reg_result, $agent_code);
                    //添加邀请码
                    create_invite_code_0910($reg_result);
                    reg_invite_service($reg_result, $invite_code);

                    require_once DOCUMENT_ROOT . '/system/im_common.php';
                    update_im_user_info($reg_result);
                    $user_info = db('user')->where("id='$reg_result'")->find();
                    $result['code'] = 1;
                    $result['msg'] = lang('login_was_successful');
                    task_reward(1, $reg_result);
                } else {
                    $result['code'] = 0;
                    $result['msg'] = lang('login_has_failed');
                }
            }
        }

        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);
        login_ip_log($user_info['id'], $user_info['mobile'], $user_info['user_nickname'], 2);
        return_json_encode($result);
    }

    // firebase 登陆注册
    public function do_firebase_login()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $area_code = input('param.area_code', '', 'trim'); // 国际区号
        $code = intval(input('param.code'));

        $address = trim(input('param.address'));

        $invite_code = trim(input('param.invite_code'));
        $agent_code = trim(input('param.agent'));

        $uuid = trim(input('param.uuid'));

        $fireBaseToken = input('param.idToken', '', 'trim');
        $loginType = input('param.login_type', '', 'trim');
        $country_code = get_country_code();
        //是否开启设备注册限制
        if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {
            //查询是否该设备已注册
            if (empty($uuid)) {
                $result['code'] = 0;
                $result['msg'] = lang('Missing_device_ID');
                return_json_encode($result);
            }
        }
        if ($uuid) {
            // 查询设备号是否被禁用
            $equipment_closures = db('equipment_closures')->where("device_uuid='$uuid'")->find();
            if ($equipment_closures) {
                $result['code'] = 0;
                $result['msg'] = lang('此设备号已被禁止使用');
                return_json_encode($result);
            }
        }

        // 请求接口验证IDToken
        $client = new Client([
            'timeout' => 3.0,
        ]);

        $requestUpInfoQuery = [
            'firebase_token' => $fireBaseToken,
        ];

        try {
            $webManUrl = config('base_config.webman_url');
            if (empty($webManUrl)) {
                return_json_encode_data('', 0, 'webman_url is empty!');
            }
            $response = $client->request('GET', $webManUrl . '/login/firebaseLogin', [
                'query' => $requestUpInfoQuery
            ]);
        } catch (GuzzleException $e) {
            $result['code'] = -1;
            $result['msg'] = $e->getMessage();
            return_json_encode($result);
        }

        if ($response->getStatusCode() != 200) {
            $result['code'] = $response->getStatusCode();
            $result['msg'] = $response->getReasonPhrase();
            return_json_encode($result);
        }

        $requestContent = $response->getBody()->getContents();

        if (empty($requestContent)) {
            $result['code'] = -1;
            $result['msg'] = 'Auth error';
            return_json_encode($result);
        }

        if (is_null(json_decode($requestContent))) {
            echo $requestContent;
            exit;
        }

        $authResult = json_decode($requestContent, true);

        if ($authResult['code'] != 1) {
            $result['code'] = $authResult['code'];
            $result['msg'] = $authResult['msg'];
            return_json_encode($result);
        }

        $fireBasePhoneNumber = $authResult['data']['phone_number'];

        // 用户的手机号码+区号拼接格式，例如：+86157xxxxxxxxx
        $userMobileAreaCodePhoneNumber = '';

        if ($loginType == 'phone') {
            // 验证用户输入的手机号码和firebase的手机号码是否一样
            if ($fireBasePhoneNumber != ('+' . $area_code . $mobile)) {
                $result['code'] = 0;
                $result['msg'] = lang('firebase_code_login_error_3');
                return_json_encode($result);
            }

            $userMobileAreaCodePhoneNumber = $fireBasePhoneNumber;

        } else if ($loginType == 'google') {
            // 用户如果使用google登录获取之前是否使用过手机号码
            if (!empty($fireBasePhoneNumber)) {
                $userMobileAreaCodePhoneNumber = $fireBasePhoneNumber;
            }
        }

        $openid = $authResult['data']['uid'];

        $config = load_cache('config');

        $user_info = db('user')->where('firebase_uid', $openid)
            ->where('user_type', 2)
            ->find();
        //登录
        if ($user_info) {
            //生成token
            $token = get_login_token($user_info['id']);
            //更新的信息
            $data = array('token' => $token, 'country_code' => $country_code, 'last_login_time' => NOW_TIME, 'last_login_ip' => request()->ip(0, false),);
            $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

            if (!$update_res) {
                $result['code'] = 0;
                $result['msg'] = lang('Login_failed');
                return_json_encode($result);
            }

            //拉黑时间到取消拉黑状态
            $shielding_time = 0;
            if ($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME) {
                $data['shielding_time'] = 0;
                $data['user_status'] = 1;
                $data['country_code'] = $country_code;
                db('user')->where('id =' . $user_info['id'] . " and user_type=2")->update($data);
                $shielding_time = 1;
            }

            if ($user_info['user_status'] != 0 || $shielding_time == 1) {
                $result['code'] = 1;
                $result['msg'] = lang('LOGIN_SUCCESS');

                $result['data'] = array(
                    'id'             => $user_info['id'],
                    'token'          => $token,
                    'sex'            => $user_info['sex'],
                    'user_nickname'  => $user_info['user_nickname'],
                    'avatar'         => $user_info['avatar'],
                    'address'        => $user_info['address'],
                    'is_reg_perfect' => $user_info['is_reg_perfect'],
                    'mobile'         => $user_info['mobile'],
                );

                $signature = load_cache('usersign', ['id' => $user_info['id']]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                require_once DOCUMENT_ROOT . '/system/im_common.php';

                update_im_user_info($user_info['id']);

                $result['data']['user_sign'] = $signature['usersign'];
            } else {
                if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                    $result['msg'] = lang('User_forbidden_login');
                } else {
                    $result['msg'] = lang('Account_has_been_hacked');
                }
            }

        } else {

            //是否开启设备注册限制
            if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {
                //查询是否该设备已注册
                $is_device_reg = db('user')->where('device_uuid', '=', $uuid)->find();
                if ($is_device_reg) {
                    $result['code'] = 0;
                    $result['msg'] = lang('device_has_reached_registration_limit');
                    return_json_encode($result);
                }
            }

            //检查IP是否超过注册量
            $client_ip = request()->ip();
            $ip_log = db('ip_reg_log')->where(['ip' => $client_ip])->find();
            if ($ip_log && $ip_log['count'] >= IP_REG_MAX_COUNT) {
                $result['code'] = 0;
                $result['msg'] = lang('IP_registration_exceeded_limit');
                return_json_encode($result);
            }

            //生成ID
            //$id = get_max_user_id($mobile);
            //生成token
            $token = get_login_token($userMobileAreaCodePhoneNumber);
            $brand = input('param.brand', '');
            $model = input('param.model', '');
            $intranet_ip = input('param.intranet_ip', '');

            $data = array(
                'user_type'        => 2,
                'user_nickname'    => lang('New_registered_user') . rand(88888, 99999),
                'create_time'      => NOW_TIME,
                'last_login_time'  => NOW_TIME,
                'sex'              => 0,
                'avatar'           => SITE_URL . '/image/headicon.png',
                'mobile'           => $userMobileAreaCodePhoneNumber,
                'mobile_area_code' => $area_code,
                'token'            => $token,
                'address'          => $address ?: lang('the_outer_space'),
                'device_uuid'      => $uuid,
                'intranet_ip'      => $intranet_ip,
                'brand'            => $brand,
                'model'            => $model,
                'public_ip'        => $client_ip,
                'register_device'  => $uuid,
                'firebase_uid'     => $openid,
                'country_code'     => $country_code,
            );

            $reg_result = db('user')->insertGetId($data);

            if ($reg_result) {
                $result['data'] = array(
                    'id'             => $reg_result,
                    'token'          => $token,
                    'is_reg_perfect' => 0,
                    'mobile'         => $userMobileAreaCodePhoneNumber,
                );

                $signature = load_cache('usersign', ['id' => $reg_result]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }
                $result['data']['user_sign'] = $signature['usersign'];

                //增加注册IP记录
                if ($ip_log) {
                    db('ip_reg_log')->where(['ip' => $client_ip])->setInc('count', 1);
                } else {
                    db('ip_reg_log')->insert(['ip' => $client_ip, 'count' => 1]);
                }

                //添加渠道代理
                reg_full_agent_code($reg_result, $agent_code);
                //添加邀请码
                create_invite_code_0910($reg_result);
                reg_invite_service($reg_result, $invite_code);

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($reg_result);
                $user_info = db('user')->where("id='$reg_result'")->find();
                $result['code'] = 1;
                $result['msg'] = lang('login_was_successful');
                task_reward(1, $reg_result);
            } else {
                $result['code'] = 0;
                $result['msg'] = lang('login_has_failed');
            }
        }

        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);
        login_ip_log($user_info['id'], $user_info['mobile'], $user_info['user_nickname'], 2);
        return_json_encode($result);
    }

    //密码注册
    public function do_registered()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $code = intval(input('param.code'));
        $password = trim(input('param.password'));
        $invite_code = trim(input('param.invite_code'));
        $agent_code = trim(input('param.agent'));
        $uuid = trim(input('param.uuid'));
        $country_code = get_country_code();
        //是否开启设备注册限制
        if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {

            //查询是否该设备已注册
            if (empty($uuid)) {
                $result['code'] = 0;
                $result['msg'] = lang('Missing_device_ID');
                return_json_encode($result);
            }
        }
        if ($uuid) {
            // 查询设备号是否被禁用
            $equipment_closures = db('equipment_closures')->where("device_uuid='$uuid'")->find();
            if ($equipment_closures) {
                $result['code'] = 0;
                $result['msg'] = lang('此设备号已被禁止使用');
                return_json_encode($result);
            }
        }
        if (!is_numeric($mobile) || strlen($mobile) <= 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Incorrect_mobile_phone_number');
            return_json_encode($result);
        }

        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }

        $config = load_cache('config');

        /*
         * 苹果上架审核
         * */
        if ($mobile == '13246579813' && $code == 111111 && $config['is_grounding'] == 1) {
            $ver = 1;
        } else {
            $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        }
        if (!$ver) {

            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);

        } else {

            $user_info = db('user')->where('mobile', $mobile)
                ->where('user_type', 2)
                ->find();
            //登录
            if ($user_info) {
                $result['code'] = 0;
                $result['msg'] = lang('Mobile_number_registered');
                return_json_encode($result);

            } else {
                //检查IP是否超过注册量
                $client_ip = request()->ip();
                $ip_log = db('ip_reg_log')->where(['ip' => $client_ip])->find();
                if ($ip_log && $ip_log['count'] >= IP_REG_MAX_COUNT) {
                    $result['code'] = 0;
                    $result['msg'] = lang('IP_registration_exceeded_limit');
                    return_json_encode($result);
                }

                //生成ID
                $id = get_max_user_id($mobile);
                //生成token
                $token = get_login_token($id);
                $brand = trim(input('param.brand'));
                $model = trim(input('param.model'));
                $intranet_ip = trim(input('param.intranet_ip'));
                $public_ip = request()->ip();
                $data = array(
                    'id'              => $id,
                    'user_type'       => 2,
                    'user_nickname'   => lang('New_registered_user') . rand(88888, 99999),
                    'create_time'     => NOW_TIME,
                    'last_login_time' => NOW_TIME,
                    'sex'             => 1,
                    'avatar'          => SITE_URL . '/image/headicon.png',
                    'mobile'          => $mobile,
                    'token'           => $token,
                    'address'         => lang('the_outer_space'),
                    'user_pass'       => md5($password),
                    'device_uuid'     => $uuid,
                    'intranet_ip'     => $intranet_ip,
                    'brand'           => $brand,
                    'model'           => $model,
                    'public_ip'       => $public_ip,
                    'register_device' => $uuid,
                    'country_code'    => $country_code
                );

                $reg_result = db('user')->insert($data);

                if ($reg_result) {
                    $result['data'] = array(
                        'id'             => $id,
                        'token'          => $token,
                        'is_reg_perfect' => 0
                    );

                    $signature = load_cache('usersign', ['id' => $id]);
                    if ($signature['status'] != 1) {
                        $result['code'] = 0;
                        $result['msg'] = $signature['error'];
                        return_json_encode($result);
                    }
                    $result['data']['user_sign'] = $signature['usersign'];

                    //增加注册IP记录
                    if ($ip_log) {
                        db('ip_reg_log')->where(['ip' => $client_ip])->setInc('count', 1);
                    } else {
                        db('ip_reg_log')->insert(['ip' => $client_ip, 'count' => 1]);
                    }

                    //添加渠道代理
                    reg_full_agent_code($id, $agent_code);
                    //添加邀请码
                    create_invite_code_0910($id);
                    reg_invite_service($id, $invite_code);

                    require_once DOCUMENT_ROOT . '/system/im_common.php';
                    update_im_user_info($id);
                    $user_info = db('user')->where("id='$id'")->find();
                    $result['code'] = 1;
                    $result['msg'] = lang('login_was_successful');
                } else {
                    $result['code'] = 0;
                    $result['msg'] = lang('login_has_failed');
                }
            }
        }

        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);
        login_ip_log($user_info['id'], $user_info['mobile'], $user_info['user_nickname'], 2);
        return_json_encode($result);
    }

    //密码修改
    public function upd_pass()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $code = intval(input('param.code'));
        $password = trim(input('param.password'));

        if (!is_numeric($mobile) || strlen($mobile) <= 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Incorrect_mobile_phone_number');
            return_json_encode($result);
        }

        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }

        $config = load_cache('config');

        /*
         * 苹果上架审核
         * */
        if ($mobile == '13246579813' && $code == 111111 && $config['is_grounding'] == 1) {
            $ver = 1;
        } else {
            $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        }
        if (!$ver) {

            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);

        } else {

            $user_info = db('user')->where("mobile='$mobile'")->find();
            if ($user_info) {
                $data = array(
                    'user_pass' => md5($password),
                );
                $update_res = db('user')->where("id=" . $user_info['id'])->update($data);
                if ($update_res) {
                    $result['code'] = 1;
                    $result['msg'] = lang('Modified_successfully');
                    return_json_encode($result);
                } else {
                    $result['code'] = 0;
                    $result['msg'] = lang('Modification_failed');
                    return_json_encode($result);
                }
            } else {
                $result['code'] = 0;
                $result['msg'] = lang('Wrong_mobile_number');
                return_json_encode($result);
            }

        }


        return_json_encode($result);
    }

    //密码登陆
    public function do_mobile_pass()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $password = trim(input('param.password'));

        if (!is_numeric($mobile) || strlen($mobile) <= 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Incorrect_mobile_phone_number');
            return_json_encode($result);
        }
        $country_code = get_country_code();
        $user_info = db('user')->where('mobile', $mobile)
            ->where('user_type', 2)
            ->find();
        //登录
        if ($user_info) {
            //生成token
            if ($user_info['user_pass'] != md5($password)) {
                $result['code'] = 0;
                $result['msg'] = lang('Password error');
                return_json_encode($result);
                exit;
            }
            $token = get_login_token($user_info['id']);
            //更新的信息
            $data = array('token' => $token, 'country_code' => $country_code, 'last_login_time' => NOW_TIME, 'last_login_ip' => request()->ip(0, false),);
            $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

            if (!$update_res) {
                $result['code'] = 0;
                $result['msg'] = lang('Login_failed');
            }

            //拉黑时间到取消拉黑状态
            $shielding_time = 0;
            if ($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME) {
                $data['shielding_time'] = 0;
                $data['user_status'] = 1;

                db('user')->where('id =' . $user_info['id'] . " and user_type=2")->update($data);
                $shielding_time = 1;
            }

            if ($user_info['user_status'] != 0 || $shielding_time == 1) {
                $result['code'] = 1;
                $result['msg'] = lang('LOGIN_SUCCESS');

                $result['data'] = array(
                    'id'             => $user_info['id'],
                    'token'          => $token,
                    'sex'            => $user_info['sex'],
                    'user_nickname'  => $user_info['user_nickname'],
                    'avatar'         => $user_info['avatar'],
                    'address'        => $user_info['address'],
                    'is_reg_perfect' => $user_info['is_reg_perfect'],
                    'mobile'         => $user_info['mobile'],
                );

                $signature = load_cache('usersign', ['id' => $user_info['id']]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($user_info['id']);

                $result['data']['user_sign'] = $signature['usersign'];
            } else {
                if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                    $result['msg'] = lang('User_forbidden_login');
                } else {
                    $result['msg'] = lang('Account_has_been_hacked');
                }
            }

        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Mobile_number_does_not_exist');
            return_json_encode($result);
            exit;
        }


        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);
        login_ip_log($user_info['id'], $user_info['mobile'], $user_info['user_nickname'], 2);
        return_json_encode($result);
    }

    //完善注册信息 使用中
    public function perfect_reg_info()
    {

        $result = array('code' => 0, 'msg' => '');
        //    $one = request()->file('avatar');   //获取头像
        $sex = intval(request()->post('sex'));
        $user_nickname = trim(request()->post('user_nickname'));
        $uid = request()->post('uid');
        $address = trim(request()->post('address'));
        $avatar = trim(request()->post('avatar'));
        $token = trim(request()->post('token'));
        $birthday = trim(request()->post('birthday'));  //生日
        $agent_code = trim(input('agent_code'));  // 邀请码

        $country_code = intval(input('country_code'));  // 国家 country_code 标识

        $user_info = check_login_token($uid, $token, ['link_id', 'last_login_ip']);

        if ($sex == 0) {
            $result['msg'] = lang('Please_select_gender');
            return_json_encode($result);
        }
        if (empty($user_nickname)) {
            $result['msg'] = lang('User_name_cannot_be_empty');
            return_json_encode($result);
        }
        if (!$avatar) {
            $result['msg'] = lang('Please_upload_your_Avatar');
            return_json_encode($result);
        }
        /* $upload_one = SITE_URL . '/image/headicon.png';
         if ($one) {
             $upload_one = oss_upload($one);      //单图片上传
         } else {
             $result['msg'] = "请上传头像";
             return_json_encode($result);
         }*/
//            $exits_name = db('user')->where("user_nickname='$user_nickname'")->find();
//            if ($exits_name) {
//                $result['code'] = 0;
//                $result['msg'] = "用户名重复，请重新输入用户名";
//                return_json_encode($result);
//            }

        $config = load_cache('config');

        //注册赠送积分或钻石
//        $give_coin = $config['system_coin_registered'];
        $age_time = NOW_TIME - strtotime($birthday);
        $age = ceil($age_time / 86400 / 365);
        $data = array(
            'user_nickname'  => $user_nickname,
            'sex'            => $sex == 1 ? $sex : 2,
            'avatar'         => $avatar,
            'address'        => $address ? $address : lang('the_outer_space'),
            'is_reg_perfect' => 1,
            'birthday'       => $birthday,
            'age'            => $age,
        );
        //计算星座
        $data['constellation'] = get_constellation(strtotime($birthday));
        if ($country_code) {
            $country = get_country_one($country_code);

            // 查询国家
            if ($country) {
                $data['country_code'] = $country_code;
            }
        }

        $voice_id = 0;
        if (!empty($agent_code)) {
            $invite_record = db('invite_record')->where("invite_user_id='$uid'")->find();
            if ($invite_record) {
                // 已经绑定了邀请关系了不需要绑定渠道代理
                $result['msg'] = lang('已绑定过邀请码');
                return_json_encode($result);
            } else {
                if ($user_info['link_id']) {
                    $result['msg'] = lang('已绑定过邀请渠道码');
                    return_json_encode($result);
                }
                $agent = db('agent')->where("channel='" . $agent_code . "'")->find();
                if (!$agent) {
                    $result['msg'] = lang('邀请码错误');
                    return_json_encode($result);
                }
                // 是否自动跳转到房间
                if ($agent['voice_id']) {
                    $agent_voice = db('agent_voice')->where("voice_id='" . $agent['voice_id'] . "' and agent_id=" . $agent['agent_company'])->find();
                    if ($agent_voice['status'] == 1) {
                        // 判断房间是否还存在
                        $voice = db('voice')->where("id=" . $agent['voice_id'] . " and (live_in = 1 || live_in = 3)")->find();
                        if ($voice) {
                            $voice_id = $agent['voice_id'];
                        }
                    }
                }
                // 是否自动关注主播
                if ($agent['host_id']) {
                    $agent_voice = db('agent_host')->where("host_id='" . $agent['host_id'] . "' and agent_id=" . $agent['agent_company'])->find();
                    if ($agent_voice['status'] == 1) {
                        // 自动关注
                        $attention = db('user_attention')->where("uid=$uid and attention_uid=" . $agent['host_id'])->find();
                        if (!$attention) {
                            $attention_data = array(
                                'uid'           => $uid,
                                'attention_uid' => $agent['host_id'],
                                'addtime'       => NOW_TIME
                            );
                            db('user_attention')->insert($attention_data);
                            $msg = db("user_message")->where('type = 15')->find();
                            $content = $user_info['user_nickname'] . $msg['centent'];
                            $url = 'bogo://message?type=1&id=' . $uid;
                            push_sys_msg_user(19, $agent['host_id'], 1, $content, $url);
                        }
                    }
                }

                db('user')->where('id', '=', $uid)->setField('link_id', $agent['id']);
                $agent_data = array(
                    'uid'      => $uid,
                    'agent_id' => $agent['id'],
                    'code'     => $agent_code,
                    'status'   => 1,
                    'addtime'  => time()
                );
                db('agent_register')->insert($agent_data);
            }
        }
        $update_res = db('user')->where("id=$uid")->update($data);
        // 如果存在voice_id --直接跳转到直播间
        $data['voice_id'] = $voice_id;
        //更新IM信息
        require_once DOCUMENT_ROOT . '/system/im_common.php';
        update_im_user_info($uid);

        if ($update_res) {
            //注册赠送积分或钻石
            $give_coin = intval($config['system_coin_registered']);
            if ($give_coin) {
                $status = db('user_new_welfare')->where("uid=" . $uid . " and type=1 and genre=1")->find();
                if (!$status) {
                    $charging_coin = db('user')->where('id=' . $uid)->setInc('friend_coin', $give_coin);
                    if ($charging_coin) {
                        update_new_welfare($uid, $give_coin, 1, 1);
                        // 钻石变更记录
                        save_coin_log($uid, $give_coin, 2, 22);
                        upd_user_coin_log($uid, $give_coin, 0, 32, 2, 1, $user_info['last_login_ip'], 1);

                        // 账号变更记录 1:1对1通话计时 2:赠送礼物 3:赠送背包礼物 4:加速匹配 5：非好友聊天 13 座驾 14 头饰 15聊天气泡 16 新人福利
                        //upd_user_coin_log($user_info['id'], $give_coin, 2, 1, $user_info['last_login_ip'], $user_info['id'], 16);
                    }
                }
            }
            //男用户完善信息后奖励
            if ($sex == 1) {
                reg_invite_perfect_info_service($uid, 1);
            }
            $result['data'] = $data;
            $result['code'] = 1;
            $result['msg'] = "";
        }
        return_json_encode($result);
    }

    //分享注册
    public function share_reg()
    {

        $invite_code = input('param.invite_code');

        $this->assign('invite_code', $invite_code);
        return $this->fetch();
    }

    //分享注册账号
    public function share_reg_insert()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $code = intval(input('param.code'));
        $invite_code = trim(input('param.invite_code'));

        $config = load_cache('config');
        $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        if (!$ver) {
            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
        } else {

            $sdk_app_id = $config['tencent_sdkappid'];
            $user_info = db('user')->where("mobile='$mobile'")->find();

            if ($user_info) {
                $result['code'] = 0;
                $result['msg'] = lang('Mobile_number_registered');
                return_json_encode($result);
            }
            //  注册
            $id = db('mb_user')->insertGetId(array('mobile' => $mobile));
            $token = md5($mobile . $id . NOW_TIME);
            $brand = trim(input('param.brand'));
            $model = trim(input('param.model'));
            $intranet_ip = trim(input('param.intranet_ip'));
            $public_ip = request()->ip();
            $data = array(
                'id'              => $id,
                'user_type'       => 2,
                'user_nickname'   => lang('New_registered_user') . rand(88888, 99999),
                'create_time'     => NOW_TIME,
                'last_login_time' => NOW_TIME,
                'sex'             => 1,
                'avatar'          => SITE_URL . '/image/headicon.png',
                'mobile'          => $mobile,
                'token'           => $token,
                'address'         => lang('the_outer_space'),
                'intranet_ip'     => $intranet_ip,
                'brand'           => $brand,
                'model'           => $model,
                'public_ip'       => $public_ip
            );

            $reg_result = db('user')->insert($data);
            if ($reg_result) {

                $result['data'] = array(
                    'id'             => $id,
                    'token'          => $token,
                    'sdkappid'       => $sdk_app_id,
                    'is_reg_perfect' => 0
                );

                $signature = load_cache('usersign', ['id' => $id]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }
                $result['data']['user_sign'] = $signature['usersign'];

                //添加邀请码
                create_invite_code_0910($id);
                reg_invite_service($id, $invite_code);

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($id);

                $result['code'] = 1;
                $result['msg'] = lang('login_was_successful');
            } else {
                $result['code'] = 0;
                $result['msg'] = lang('login_has_failed');
            }
        }

        return_json_encode($result);
    }

    //分享注册成功
    public function share_reg_success()
    {
        $config = load_cache('config');

        $this->assign('ios_download_url', $config['ios_download_url']);
        $this->assign('android_download_url', $config['android_download_url']);
        return $this->fetch();
    }

    //发送验证码 使用中
    public function code()
    {
        header('Access-Control-Allow-Origin:*');

        $result = array('code' => 1, 'msg' => lang('Verification_code_sent_successfully'), 'data' => null);

        $mobile = input('param.mobile');
        $area_code = input('param.area_code', '', 'trim'); // 国际区号
        $sendType = input('param.send_type', 0, 'intval');

        // 注销发送验证码
        if ($sendType == 1) {

            $uid = input('param.uid', '', 'trim');
            $token = input('param.token', '', 'trim');

            if (empty($uid) || empty($token)) {
                return_json_encode_data('', 1, lang('登录参数错误'));
            }

            $userInfo = check_login_token($uid, $token, ['mobile', 'mobile_area_code']);

            // 注销发送验证码
            if (!$userInfo) {
                return_json_encode_data('', 1, lang('登录信息错误'));
            }

            // 判断是否绑定手机号
            if (empty($userInfo['mobile'])) {
                return_json_encode_data('', 1, lang('未绑定手机号码无法发送验证码'));
            }

            $mobile = $userInfo['mobile'];
            $area_code = $userInfo['mobile_area_code'];
        } else {

            if (empty($area_code)) {
                $result['msg'] = lang('缺少区号');
                return_json_encode($result);
            }
            if (empty($mobile)) {
                check_param($mobile);
            }
        }

        $is_area_mobile = 1;

        $area_mobile = new PhoneNumber($mobile, $area_code);

        $verification = db('verification_code')
            ->where('account', $mobile)
            ->where('phone_area_code', $area_code)
            ->order('send_time desc')
            ->find();
        if ($verification) {
            if ((NOW_TIME - $verification['send_time']) < 60) {
                $result['msg'] = lang('Send_in_60_seconds');
                return_json_encode($result);
            }
        }

        $code = get_verification_code($area_code, $mobile);

        if (!$code) {
            $result['code'] = 0;
            $result['msg'] = lang('Too_many_verification_codes_sent');
            return_json_encode($result);
        }

        $config = load_cache('config');

        $is_test = 0;
        if (strpos($config['mobile_shelf_use'], (string)$area_mobile) !== false) {
            $is_test = 1;
        } else {
            $user_info = db('user')->where('mobile_area_code ', $area_code)->where('mobile', $mobile)->find();
            if ($user_info) {
                if ($user_info['is_test'] == 1) {
                    $is_test = 1;
                }
            }
        }

        $result['data']['otp_message_id'] = '';

        //是否开启短信 $config['system_sms_open'] == 1 && $is_test != 1
        if ($config['system_sms_open'] == 1 && $is_test != 1) {
            $is_area_mobile_status = 0;
            if ($is_area_mobile) {

                // OTP 登录功能，发送OTP验证码
                if ($this->otpConfig['is_open']) {

                    $authString = base64_encode($this->otpConfig['app_key'] . ':' . $this->otpConfig['app_secret']);
                    $headers = array(
                        "Authorization: Basic " . $authString,
                        "Content-Type:application/json"
                    );
                    $to_area_mobile = $area_mobile;
                    if (substr($to_area_mobile, 0, 1) !== '+') {
                        $to_area_mobile = '+' . $to_area_mobile;
                    }
                    $sms_data = array(
                        'to'       => (string)$to_area_mobile,
                        'template' => [
                            'id'       => "1",
                            'language' => 'en'
                        ]
                    );
                    $url = "https://otp.api.engagelab.cc/v1/messages";
                    $re = tripartite_post($url, json_encode($sms_data), $headers);

                    // 打印请求响应
                    bogokjLogPrint('OTP', ['url' => $url, 'param' => $sms_data, 're' => $re]);

                    if (isset($re['code'])) {
                        $result['code'] = 0;
                        $result['msg'] = $re['message'];
                    } else {
                        $result['code'] = 1;
                        $result['msg'] = 'OK';
                        $result['data']['otp_message_id'] = $re['message_id'];
                    }

                    $is_area_mobile_status = 1;
                } else {

                    $where = "status=1 and type=1";
                    $list = db('cloud_sms_config')->where($where)->find();
                    if ($list['val'] == 'vonage') {
                        $url = "https://rest.nexmo.com/sms/json";
                        $text = urldecode(lang('Your_verification_code_is') . $code);
                        $send_array_text = "from=Vonage APIs&text=" . $text . "&to=" . $area_mobile . "&api_key=" . $list['app_key'] . "&api_secret=" . $list['app_secret'];
                        $re = tripartite_post($url, $send_array_text);

                        if ($re['messages'] && $re['messages'][0]['status'] == 0) {
                            $result['code'] = 1;
                            $result['msg'] = lang('Verification_code_sent_successfully');
                        } else {
                            $result['code'] = 0;
                            $result['msg'] = json_encode($re['messages']);
                            return_json_encode($result);
                        }
                        $is_area_mobile_status = 1;
                    } else if ($list['val'] == 'aliyun') {
                        $is_area_mobile_status = 1;
                        AlibabaCloud::accessKeyClient($list['app_key'], $list['app_secret'])
                            ->regionId('ap-southeast-1')
                            ->asGlobalClient();
                        try {
                            AlibabaCloud::rpcRequest()
                                ->product('Dysmsapi')
                                ->host('dysmsapi.ap-southeast-1.aliyuncs.com')
                                ->version('2018-05-01')
                                ->action('SendMessageToGlobe')
                                ->method('POST')
                                ->options([
                                    'query' => [
                                        'RegionId' => "ap-southeast-1",
                                        "To"       => $area_mobile,
                                        "Message"  => lang('Your_verification_code_is') . $code,
                                    ],
                                ])
                                ->request();
                            $result['code'] = 1;
                            $result['msg'] = lang('Verification_code_sent_successfully');
                        } catch (ClientException $e) {
                            $result['code'] = 0;
                            $result['msg'] = json_encode($e->getErrorMessage());
                        } catch (ServerException $e) {
                            $result['code'] = 0;
                            $result['msg'] = json_encode($e->getErrorMessage());
                        }
                    } else if ($list['val'] == 'engage') {

                        $authString = base64_encode($list['app_key'] . ':' . $list['app_secret']);
                        $headers = array(
                            "Authorization: Basic " . $authString,
                            "Content-Type:application/json"
                        );
                        $to_area_mobile = $area_mobile;
                        if (substr($to_area_mobile, 0, 1) !== '+') {
                            $to_area_mobile = '+' . $to_area_mobile;
                        }
                        $sms_data = array(
                            'to'   => [$to_area_mobile],
                            "body" => array(
                                "template_id" => $list['template'],
                                "vars"        => array(
                                    "code" => $code
                                )
                            )
                        );
                        $url = "https://sms.api.engagelab.cc/v1/send";
                        $re = tripartite_post($url, json_encode($sms_data), $headers);
                        if (isset($re['code'])) {
                            $result['code'] = 0;
                            $result['msg'] = $re['message'];
                            $result['message_id'] = '';
                        } else {
                            $result['code'] = 1;
                            $result['msg'] = 'OK';
                        }
                        $is_area_mobile_status = 1;
                    }
                }

            }

            if ($is_area_mobile_status != 1 && !$this->otpConfig['is_open']) {
                $sms = new SmsModel();
                $res = $sms->send_code($is_area_mobile ? $area_mobile : $mobile, $code, $is_area_mobile);
                if ($res['code'] == 1) {
                    $result['code'] = 1;
                    $result['msg'] = lang('Verification_code_sent_successfully');
                } else {
                    $result['code'] = 0;
                    $result['msg'] = $res['msg'];
                }
            }
            $result['smUuid'] = '';
        } else {
            $code = $config['mobile_default_code'];
            $result['msg'] = lang('Verification_code_sent_successfully');
            $result['code'] = 1;
            $result['smUuid'] = '';
        }

        verification_code_log($area_code, $mobile, $code, $result, 5 * 60);

        return_json_encode($result);
    }

    //获取图形验证码
    public function get_img_code()
    {

        $mobile = get_input_param_str('mobile');

        $config = [
            // 验证码字体大小
            'fontSize' => 30,
            // 验证码位数
            'length'   => 3,
            // 关闭验证码杂点
            'useNoise' => false,
        ];

        $captcha = new Captcha($config);
        return $captcha->entry($mobile);
    }

    public function share_reg_new()
    {
        $invite_code = input('param.invite_code');

        $invite_code_info = db('invite_code')->where('invite_code', '=', $invite_code)->find();
        $user_info = get_user_base_info($invite_code_info['user_id']);

        $this->assign('user_info', $user_info);
        $this->assign('invite_code', $invite_code);
        return $this->fetch();
    }


    //完善注册信息
    public function perfect_reg_info_190708()
    {

        $result = array('code' => 0, 'msg' => '');
        $avatar = trim(request()->post('avatar'));   //获取头像
        $sex = intval(request()->post('sex'));
        $user_nickname = trim(request()->post('user_nickname'));
        $uid = request()->post('uid');
        $address = trim(request()->post('address'));
        $token = trim(request()->post('token'));

        $user_info = check_login_token($uid, $token, ['last_login_ip']);

        if ($sex == 0) {
            $result['msg'] = lang('Please_select_gender');
            return_json_encode($result);
        }
        if (empty($user_nickname)) {
            $result['msg'] = lang('User_name_cannot_be_empty');
            return_json_encode($result);
        }

        if (empty($avatar)) {
            $result['msg'] = lang('Please_upload_your_Avatar');
            return_json_encode($result);
        }

//            $exits_name = db('user')->where("user_nickname='$user_nickname'")->find();
//            if ($exits_name) {
//                $result['code'] = 0;
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


        $data = array(
            'user_nickname'  => $user_nickname,
            'sex'            => $sex == 1 ? $sex : 2,
            'avatar'         => $avatar,
            'address'        => $address ? $address : lang('the_outer_space'),
            'is_reg_perfect' => 1,
//            'coin' => 0,
//            'income' => 0,
//            'income_total' => 0,
        );
        //bogo_friendship_level 用户中心密友等级限制
        $friendship = db('friendship_level')->order('level_up asc')->find();
        $data['friendship_level_info'] = $friendship['id'];
        $data['friendship_level_video'] = $friendship['id'];
        $data['friendship_level_dynamic'] = $friendship['id'];
        $update_res = db('user')->where("id=$uid")->update($data);

        //更新IM信息
        require_once DOCUMENT_ROOT . '/system/im_common.php';
        update_im_user_info($uid);

        if ($update_res) {
            //注册赠送积分或钻石
            $give_coin = intval($config['system_coin_registered']);
            if ($give_coin) {
                $status = db('user_new_welfare')->where("uid=" . $uid . " and type=1 and genre=1")->find();
                if (!$status) {
                    $charging_coin = db('user')->where('id=' . $uid)->setInc('friend_coin', $give_coin);
                    if ($charging_coin) {
                        // 钻石变更记录
                        save_coin_log($uid, $give_coin, 2, 22);
                        upd_user_coin_log($uid, $give_coin, 0, 22, 2, 1, $user_info['last_login_ip'], 1);

                        update_new_welfare($uid, $give_coin, 1, 1);
                        // 账号变更记录 1:1对1通话计时 2:赠送礼物 3:赠送背包礼物 4:加速匹配 5：非好友聊天 13 座驾 14 头饰 15聊天气泡 16 新人福利
                        //upd_user_coin_log($user_info['id'], $give_coin, 2, 1, $user_info['last_login_ip'], $user_info['id'], 16);
                    }
                }
            }
            //男用户完善信息后奖励
            if ($sex == 1) {
                reg_invite_perfect_info_service($uid, 1);
            }
            $result['data'] = $data;
            $result['code'] = 1;
            $result['msg'] = "";
        }
        return_json_encode($result);
    }

    // 领取新人福利
    public function new_welfare()
    {
        $result = array('code' => 0, 'msg' => lang('Collection_failed'));

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $config = load_cache('config');

        $user_info = check_login_token($uid, $token, ['last_login_ip']);

        $give_coin = $config['system_coin_registered'];

        if ($give_coin > 0) {
            $status = db('user_new_welfare')->where("uid=" . $user_info['id'] . " and type=1 and genre=1")->find();
            if (!$status) {
                $charging_coin = db('user')->where('id=' . $user_info['id'])->setInc('friend_coin', $give_coin);
                if ($charging_coin) {
                    update_new_welfare($user_info['id'], $give_coin, 1, 1);
                    // 账号变更记录 1:1对1通话计时 2:赠送礼物 3:赠送背包礼物 4:加速匹配 5：非好友聊天 13 座驾 14 头饰 15聊天气泡 16 新人福利
                    //upd_user_coin_log($user_info['id'], $give_coin, 2, 1, $user_info['last_login_ip'], $user_info['id'], 16);
                    $result['code'] = 1;
                    $result['msg'] = lang('Received_successfully');
                }
            } else {
                $result['msg'] = lang('Already_received_benefits');
            }

        } else {
            $result['msg'] = lang('End_of_activity');
        }

        return_json_encode($result);
    }

    //注销账号
    public function logout()
    {
        $result = array('code' => 1, 'msg' => lang('Logout_successful'), 'data' => null);

        $code = intval(input('code'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));

        $userInfo = check_login_token($uid, $token, ['last_login_ip', 'mobile', 'mobile_area_code']);
        $mobile = $userInfo['mobile'];
        $area_code = $userInfo['mobile_area_code'];

        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
            return_json_encode($result);
        }

        $config = load_cache('config');

        $area_mobile = new PhoneNumber($mobile, $area_code);
        /*
         * 苹果上架审核
         * */
        if ($mobile == '13246579813' && $code == 111111 && $config['is_grounding'] == 1) {
            $ver = 1;
        } else {
            if ($this->otpConfig['is_open']) {
                // 判断如果开启了 OTP ，参数必须携带 opt_message_id 参数 string 类型，否则返回错误
                $opt_message_id = input('param.otp_message_id');
                if (!$opt_message_id) {
                    $result['code'] = 0;
                    $result['msg'] = 'otp_message_id is must!';
                    return_json_encode($result);
                }

                $authString = base64_encode($this->otpConfig['app_key'] . ':' . $this->otpConfig['app_secret']);
                $headers = array(
                    "Authorization: Basic " . $authString,
                    "Content-Type:application/json"
                );

                $sms_data = array(
                    'message_id'  => $opt_message_id,
                    'verify_code' => (string)$code,
                );
                $url = "https://otp.api.engagelab.cc/v1/verifications";
                $re = tripartite_post($url, json_encode($sms_data), $headers);

                // 打印请求响应
                bogokjLogPrint('OTP', [
                    'verify_res' => $re,
                ]);

                if (isset($re['code']) || !isset($re['verified']) || !$re['verified']) {
                    // 验证失败
                    $result['code'] = 0;
                    $result['msg'] = $re['message'];
                    return_json_encode($result);
                }

                $ver = true;
            } else {
                $ver = db('verification_code')
                    ->where('phone_area_code', $userInfo['mobile_area_code'])
                    ->where('code', $code)
                    ->where('account', $mobile)
                    ->where('expire_time', '>', NOW_TIME)
                    ->find();
            }
        }

        if ($ver) {
            db('user')->where('id', $uid)->update(['logout_time' => NOW_TIME, 'user_type' => 3, 'is_online' => 0]);
            // 关闭房间
            close_delete_voice($uid);

        } else {
            $result['code'] = 0;
            $result['msg'] = lang('CAPTCHA_NOT_RIGHT');
        }

        return_json_encode($result);
    }

    //提交绑定--渠道代理
    public function binding_agent()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $agent_code = trim(input('agent_code'));
        $user_info = check_login_token($uid, $token, ['last_login_ip', 'link_id']);

        $invite_record = db('invite_record')->where("invite_user_id='$uid'")->find();
        $voice_id = 0;

        if ($invite_record) {
            // 已经绑定了邀请关系了不需要绑定渠道代理
            $result['msg'] = lang('已绑定过邀请码');
            return_json_encode($result);
        } else {
            if ($user_info['link_id']) {
                $result['msg'] = lang('已绑定过邀请渠道码');
                return_json_encode($result);
            }
            if (!empty($agent_code)) {
                $agent = db('agent')->where("channel='" . $agent_code . "'")->find();
                if (!$agent) {
                    $result['msg'] = lang('邀请码错误');
                    return_json_encode($result);
                }
                // 是否自动跳转到房间
                if ($agent['voice_id']) {
                    $agent_voice = db('agent_voice')->where("voice_id='" . $agent['voice_id'] . "' and agent_id=" . $agent['agent_company'])->find();
                    if ($agent_voice['status'] == 1) {
                        // 判断房间是否还存在
                        $voice = db('voice')->where("id=" . $agent['voice_id'] . " and (live_in = 1 || live_in = 3)")->find();
                        if ($voice) {
                            $voice_id = $agent['voice_id'];
                        }
                    }
                }
                // 是否自动关注主播
                if ($agent['host_id']) {
                    $agent_voice = db('agent_host')->where("host_id='" . $agent['host_id'] . "' and agent_id=" . $agent['agent_company'])->find();
                    if ($agent_voice['status'] == 1) {
                        // 自动关注
                        $attention = db('user_attention')->where("uid=$uid and attention_uid=" . $agent['host_id'])->find();
                        if (!$attention) {
                            $data = array(
                                'uid'           => $uid,
                                'attention_uid' => $agent['host_id'],
                                'addtime'       => NOW_TIME
                            );
                            db('user_attention')->insert($data);
                            $msg = db("user_message")->where('type = 15')->find();
                            $content = $user_info['user_nickname'] . $msg['centent'];
                            $url = 'bogo://message?type=1&id=' . $uid;
                            push_sys_msg_user(19, $agent['host_id'], 1, $content, $url);
                        }
                    }
                }

                db('user')->where('id', '=', $uid)->setField('link_id', $agent['id']);
                $data = array(
                    'uid'      => $uid,
                    'agent_id' => $agent['id'],
                    'code'     => $agent_code,
                    'status'   => 1,
                    'addtime'  => time()
                );
                db('agent_register')->insert($data);
            }
        }
        $result['code'] = 1;
        $result['msg'] = lang('Binding_succeeded');
        // 如果存在voice_id --直接跳转到直播间
        $result['data'] = array(
            'voice_id' => $voice_id
        );
        return_json_encode($result);
    }

    //查询用户是否有绑定过渠道代理--- 邀请关系除外
    public function is_agent()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = check_login_token($uid, $token, ['last_login_ip', 'link_id', 'device_uuid']);

        $device_uuid_sum = db('user')->where('device_uuid', '=', $user_info['device_uuid'])->count();
        $status = 0; // 0未绑定过 1已绑定不需要重新绑定关系
        if (intval($device_uuid_sum) > 1) {
            // 一个设备号只能弹出一次  不需要绑定渠道代理
            $status = 1;
        } else {
            $invite_record = db('invite_record')->where("invite_user_id='$uid'")->find();
            if ($invite_record) {
                // 已经绑定了邀请关系了不需要绑定渠道代理
                $status = 1;
            } else {
                if ($user_info['link_id']) {
                    $status = 1;
                }
            }
        }
        $result['data'] = array(
            'state' => $status
        );
        return_json_encode($result);
    }

    /**
     * @param bool  $is_test_account
     * @param array $result
     * @return array
     */
    public function checkLimitAreaUser(bool $is_test_account, array $result): array
    {
        $ip = get_client_ip(0, true);
        $ipInfo = getCountryInfoByIP($ip);

        bogokjLogPrint('IpInfo', $ipInfo);

        if (IS_TEST && is_array($ipInfo) && isset($ipInfo['region']) && $ipInfo['region'] == 'Henan') {
            $result['msg'] = 'Not available in your region！';
            return_json_encode($result);
        }

        if (!in_array($ip, config('app.test_ip_list'))
            && !$is_test_account
            && config('app.is_open_limit_area')
            && (is_array($ipInfo) && isset($ipInfo['country']) && $ipInfo['country'] == 'CN')) {

            // 不在测试IP的列表禁止登录
            $result['msg'] = 'Not available in your region！';
            return_json_encode($result);
        }

        return $result;
    }
}
