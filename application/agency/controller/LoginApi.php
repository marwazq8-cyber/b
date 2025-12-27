<?php

/**
 * 布谷科技商业系统
 * 初始化设置相关接口
 * @author 山东布谷鸟网络科技有限公司
 * @create 2020-08-05 17:58
 */


namespace app\agency\controller;

use app\agency\model\Agency;
use app\agency\controller\BaseApi;
use think\Db;

/** 登录接口
 *
 */
class LoginApi extends BaseApi
{
    // 登录验证
    public function login(){
        $username = strim(input('param.username'));
        $password = strim(input('param.password'));
        $LoginModel = new Agency();
        $root = $LoginModel->Login($username,$password);
        return_json_encode($root);;
    }

    // 退出
    public function logout(){
        $LoginModel = new Agency();
        $token = strim(input('param.token')) ? strim(input('param.token')) :  $_SERVER['HTTP_X_TOKEN'];
        $root = $LoginModel->logout($token);
        return_json_encode($root);;
    }
}
