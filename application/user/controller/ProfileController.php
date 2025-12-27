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
namespace app\user\controller;

use cmf\lib\Storage;
use think\Validate;
use think\Image;
use cmf\controller\UserBaseController;
use app\user\model\UserModel;
use think\Db;

class ProfileController extends UserBaseController
{

    function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心首页
     */
    public function center()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 编辑用户资料
     */
    public function edit()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch('edit');
    }

    /**
     * 编辑用户资料提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'user_nickname' => 'chsDash|max:32',
                'sex'     => 'number|between:0,2',
                'birthday'   => 'dateFormat:Y-m-d|after:-88 year|before:-1 day',
                'user_url'   => 'url|max:64',
                'signature'   => 'chsDash|max:128',
            ]);
            $validate->message([
                'user_nickname.chsDash' => lang('Nicknames_can_only_Chinese_alphanumeric'),
                'user_nickname.max' => lang('maximum_length_nickname_32_characters'),
                'sex.number' => lang('Please_select_gender'),
                'sex.between' => lang('Invalid_gender_option'),
                'birthday.dateFormat' => lang('Incorrect_birthday_format'),
                'birthday.after' => lang('date_birth_is_too_early'),
                'birthday.before' => lang('date_birth_is_too_late'),
                'user_url.url' => lang('Personal_web_address_error'),
                'user_url.max' => lang('exceed_64_characters'),
                'signature.chsDash' => lang('Personal_signature_only_Chinese_characters'),
                'signature.max' => lang('Signature_length_not_more_than_128_characters'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $editData = new UserModel();
            if ($editData->editData($data)) {
                $this->success(lang('EDIT_SUCCESS'), "user/profile/center");
            } else {
                $this->error(lang('No_new_modification_information'));
            }
        } else {
            $this->error(lang('Request_error'));
        }
    }

    /**
     * 个人中心修改密码
     */
    public function password()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 个人中心修改密码提交
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'old_password' => 'require|min:6|max:32',
                'password'     => 'require|min:6|max:32',
                'repassword'   => 'require|min:6|max:32',
            ]);
            $validate->message([
                'old_password.require' => lang('Old_password_cannot_be_empty'),
                'old_password.max'     => lang('Old_password_cannot_exceed_32_characters'),
                'old_password.min'     => lang('Old_password_no_less_than_6_characters'),
                'password.require'     => lang('New_password_cannot_be_empty'),
                'password.max'         => lang('new_password_cannot_exceed_32_characters'),
                'password.min'         => lang('new_password_cannot_less_than_6_characters'),
                'repassword.require'   => lang('Duplicate_password_cannot_be_empty'),
                'repassword.max'       => lang('Duplicate_password_cannot_exceed_32_characters'),
                'repassword.min'       => lang('Duplicate_password_cannot_less_than_6_characters'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $login = new UserModel();
            $log   = $login->editPassword($data);
            switch ($log) {
                case 0:
                    $this->success(lang('Modified_successfully'));
                    break;
                case 1:
                    $this->error(lang('Password_input_is_inconsistent'));
                    break;
                case 2:
                    $this->error(lang('original_password_is_incorrect'));
                    break;
                default :
                    $this->error(lang('Unaccepted_requests'));
            }
        } else {
            $this->error(lang('Request_error'));
        }

    }

    // 用户头像编辑
    public function avatar()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    // 用户头像上传
    public function avatarUpload()
    {
        $file   = $this->request->file('file');
        $result = $file->validate([
            'ext'  => 'jpg,jpeg,png',
            'size' => 1024 * 1024
        ])->move('.' . DS . 'upload' . DS . 'avatar' . DS);

        if ($result) {
            $avatarSaveName = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            $avatar         = 'avatar/' . $avatarSaveName;
            session('avatar', $avatar);

            return json_encode([
                'code' => 1,
                "msg"  => lang('Upload_successful'),
                "data" => ['file' => $avatar],
                "url"  => ''
            ]);
        } else {
            return json_encode([
                'code' => 0,
                "msg"  => $file->getError(),
                "data" => "",
                "url"  => ''
            ]);
        }
    }

    // 用户头像裁剪
    public function avatarUpdate()
    {
        $avatar = session('avatar');
        if (!empty($avatar)) {
            $w = $this->request->param('w', 0, 'intval');
            $h = $this->request->param('h', 0, 'intval');
            $x = $this->request->param('x', 0, 'intval');
            $y = $this->request->param('y', 0, 'intval');

            $avatarPath = "./upload/" . $avatar;

            $avatarImg = Image::open($avatarPath);
            $avatarImg->crop($w, $h, $x, $y)->save($avatarPath);

            $result = true;
            if ($result === true) {
                $storage = new Storage();
                $result  = $storage->upload($avatar, $avatarPath, 'image');

                $userId = cmf_get_current_user_id();
                Db::name("user")->where(["id" => $userId])->update(["avatar" => $avatar]);
                session('user.avatar', $avatar);
                $this->success(lang('Avatar_updated_successfully'));
            } else {
                $this->error(lang('Failed_to_save_Avatar'));
            }

        }
    }

    /**
     * 绑定手机号或邮箱
     */
    public function binding()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 绑定手机号
     */
    public function bindingMobile()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username'          => 'require|number|unique:user,mobile',
                'verification_code' => 'require',
            ]);
            $validate->message([
                'username.require'          => lang('Mobile_number_cannot_be_empty'),
                'username.number'          => lang('Mobile_number_can_only_numbers'),
                'username.unique'          => lang('Mobile_number_already_exists'),
                'verification_code.require' => lang('CAPTCHA_REQUIRED'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }
            $userModel = new UserModel();
            $log       = $userModel->bindingMobile($data);
            switch ($log) {
                case 0:
                    $this->success(lang('Binding_succeeded'));
                    break;
                default :
                    $this->error(lang('Unaccepted_requests'));
            }
        } else {
            $this->error(lang('Request_error'));
        }
    }

    /**
     * 绑定邮箱
     */
    public function bindingEmail()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username'          => 'require|email|unique:user,user_email',
                'verification_code' => 'require',
            ]);
            $validate->message([
                'username.require'          => lang('Email_address_cannot_be_empty'),
                'username.email'            => lang('Incorrect_email_address'),
                'username.unique'           => lang('Email_address_already_exists'),
                'verification_code.require' => lang('CAPTCHA_REQUIRED'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }
            $userModel = new UserModel();
            $log       = $userModel->bindingEmail($data);
            switch ($log) {
                case 0:
                    $this->success(lang('Binding_succeeded'));
                    break;
                default :
                    $this->error(lang('Unaccepted_requests'));
            }
        } else {
            $this->error(lang('Request_error'));
        }
    }

}