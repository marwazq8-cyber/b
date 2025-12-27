<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/4
 * Time: 20:31
 */

namespace app\api\controller;

use think\Db;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class LevelApi extends Base
{

    //等级
    public function app_index()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $result['level_my'] = get_grade_level($uid);
        //等级列表
        $result['level'] = load_cache('level');

        return_json_encode($result);
    }

    /**
     * h5 页面 等级
     */
    public function index()
    {
        $uid = input('param.uid');
        $token = input('param.token');
        if (empty($uid) || empty($token)) {
            echo lang('Parameter_transfer_error');
            exit;
        }

        $user_info = Db::name("user")->where("id=$uid and token='$token'")->field("sex")->find();
        if (empty($user_info)) {
            echo lang('login_timeout');
            exit;
        }

        $level_my = get_grade_level($uid);
        //等级列表
        $level = load_cache('level');

        //var_dump($Level);exit;
        $this->assign('level_my', $level_my);
        $this->assign('level', $level);
        $this->assign('name', $user_info);

        return $this->fetch();
    }

}