<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class LevelModel extends Model
{
    // 获取我访客的用户
    public function get_level($where, $page)
    {

        $list = Db::name('user_visitors_log')->alias("l")
            ->where($where)
            ->field("u.id,u.user_nickname,u.avatar,l.addtime")
            ->join("user u", "u.id=l.touid")
            ->order("l.addtime DESC")
            ->group("u.id")
            ->page($page)
            ->select();

        return $list;
    }


}
