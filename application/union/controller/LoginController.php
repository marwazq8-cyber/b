<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\union\controller;

use think\Validate;
use cmf\controller\UnionBaseController;
use app\union\model\UserModel;

class LoginController extends UnionBaseController
{

    /**
     * 登录
     */
    public function index()
    {
        if (session('union')) { //已经登录时直接跳到首页
            return redirect(url('index/index'));
        } else {
            return $this->fetch(":login");
        }
    }

    /**
     * 登录验证提交
     */
    public function doLogin()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username' => 'require',
                'password' => 'require|min:6|max:32',
            ]);
            $validate->message([
                'username.require' => lang('User_name_cannot_be_empty'),
                'password.require' => lang('PASSWORD_REQUIRED'),
                'password.max'     => lang('Password_cannot_exceed_32_characters'),
                'password.min'     => lang('Password_cannot_less_than_6_characters'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $userModel         = new UserModel();
            $user['psd'] = $data['password'];
       
            $user['login'] = $data['username'];
            $log                = $userModel->doName($user);
            switch ($log) {
                case 0:
                    cmf_user_action('login');

                    $this->success(lang('LOGIN_SUCCESS'), url('index/index'));
                    break;
                case 1:
                    $this->error(lang('PASSWORD_NOT_RIGHT'));
                    break;
                case 2:
                    $this->error(lang('Login_account_does_not_exist'));
                    break;
                case 3:
                    $this->error(lang('Account_forbidden_to_access_system'));
                    break;
                default :
                    $this->error(lang('Unaccepted_requests'));
            }
        } else {
            $this->error(lang('Request_error'));
        }
    }

    /**
     * 找回密码
     */
    public function findPassword()
    {
        return $this->fetch('/find_password');
    }

    /**
     * 用户密码重置
     */
    public function passwordReset()
    {

        if ($this->request->isPost()) {
            $validate = new Validate([
                'captcha'           => 'require',
                'verification_code' => 'require',
                'password'          => 'require|min:6|max:32',
            ]);
            $validate->message([
                'verification_code.require' => lang('CAPTCHA_REQUIRED'),
                'password.require'          => lang('PASSWORD_REQUIRED'),
                'password.max'              => lang('Password_cannot_exceed_32_characters'),
                'password.min'              => lang('Password_cannot_less_than_6_characters'),
                'captcha.require'           => lang('CAPTCHA_REQUIRED'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            if (!cmf_captcha_check($data['captcha'])) {
                $this->error(lang('CAPTCHA_NOT_RIGHT'));
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }

            $userModel = new UserModel();
            if ($validate::is($data['username'], 'email')) {

                $log = $userModel->emailPasswordReset($data['username'], $data['password']);

            } else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->mobilePasswordReset($data['username'], $data['password']);
            } else {
                $log = 2;
            }
            switch ($log) {
                case 0:
                    $this->success(lang('Password_reset_succeeded'), $this->request->root());
                    break;
                case 1:
                    $this->error(lang('account_has_not_been_registered'));
                    break;
                case 2:
                    $this->error(lang('Account_format_error'));
                    break;
                default :
                    $this->error(lang('Unaccepted_requests'));
            }

        } else {
            $this->error(lang('Request_error'));
        }
    }


}