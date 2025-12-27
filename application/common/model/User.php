<?php

namespace app\common\model;

use think\Model;

class User extends Model
{
    /**
     * 通过token获取用户信息
     *
     * @param string $token
     * @return array
     */
    public static function getByToken($token)
    {
        return self::where('token', $token)->find();
    }

}