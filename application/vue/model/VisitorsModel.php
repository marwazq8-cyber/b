<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class VisitorsModel extends Model
{
    // 获取我访客的用户
    public function get_user_visitors($where, $page)
    {

        $list = Db::name('user_visitors_log')->alias("l")
            ->where($where)
            ->field("u.id,u.user_nickname,u.avatar,u.age,u.sex,l.addtime,l.num,u.luck")
            ->join("user u", "u.id=l.touid")
            ->order("l.addtime DESC")
            ->group("u.id")
            ->page($page)
            ->select();

        return $list;
    }

    // 获取访客我的用户
    public function get_touser_visitors($where, $page)
    {

        $list = Db::name('user_visitors_log')->alias("l")
            ->where($where)
            ->field("u.id,u.user_nickname,u.avatar,u.age,u.sex,l.addtime,l.num,u.luck")
            ->join("user u", "u.id=l.uid")
            ->order("l.addtime DESC")
            ->group("u.id")
            ->page($page)
            ->select();

        return $list;
    }

}
