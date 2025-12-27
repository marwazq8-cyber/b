<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\agent\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class PublicController extends BaseController
{
    public function _initialize()
    {
        $config_log = load_cache('config');

        $this->assign("config_log", $config_log);
    }

    /**
     * 后台登陆界面
     */
    public function login()
    {

        $admin_id = session('AGENT_ID');
        if(IS_AGENT != 1){
            $result['code'] = 0;
            $result['msg'] = lang('Login_mode_is_turned_off');
        }
        if (!empty($admin_id)) {//已经登录
            redirect(url("agent/Index/index"));
        } else {
            $site_admin_url_password = config("cmf_SITE_ADMIN_URL_PASSWORD");
            $upw = session("__CMF_UPW__");
            if (!empty($site_admin_url_password) && $upw != $site_admin_url_password) {
                redirect(ROOT_PATH . "/");
            } else {
                session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__", true);
                $result = hook_one('admin_login');
                if (!empty($result)) {
                    return $result;
                }
                return $this->fetch(":login");
            }
        }
    }

    /**
     * 登录验证
     */
    public function doLogin()
    {


        $captcha = $this->request->param('captcha');
        if (empty($captcha)) {
            $this->error(lang('CAPTCHA_REQUIRED'));
        }
        //验证码
        if (!cmf_captcha_check($captcha)) {
            $this->error(lang('CAPTCHA_NOT_RIGHT'));
        }

        $name = $this->request->param("username");
        if (empty($name)) {
            $this->error(lang('Please_enter_account_number'));
        }
        $pass = $this->request->param("password");
        if (empty($pass)) {
            $this->error(lang('Please_input_password'));
        }

        $where['agent_login'] = $name;

        $result = Db::name('agent')->where($where)->find();

        if (!empty($result)) {

            if (cmf_compare_password($pass, $result['agent_pass'])) {
                //登入成功页面跳转
                session('AGENT_ID', $result["id"]);
                session('AGENT_USER', $result);
                session('agent_name', $result["agent_login"]);
                $result['last_login_ip'] = get_client_ip(0, true);
                $result['last_login_time'] = time();
                $token = cmf_generate_user_token($result["id"], 'agent');
                if (!empty($token)) {
                    session('token', $token);
                }
                Db::name('agent')->update($result);
                cookie("admin_username", $name, 3600 * 24 * 30);
                $this->success(lang('LOGIN_SUCCESS'), url("agent/Index/index"));
            } else {
                $this->error(lang('PASSWORD_NOT_RIGHT'));
            }
        } else {
            $this->error(lang('Account_does_not_exist'));
        }
    }

    /**
     * 后台管理员退出
     */
    public function logout()
    {
        session('AGENT_ID', null);
        session('AGENT_USER', null);
        return redirect(url('/agent/public/login.html', [], false, true));
    }
}