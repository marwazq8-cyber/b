<?php

namespace app\api\model;

use think\Model;
use think\Db;

class LoginModel extends Model
{
    /* 获取验证码 */
    public function get_verification_code($code, $mobile)
    {

        $list = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();

        return $list;
    }
}

?>