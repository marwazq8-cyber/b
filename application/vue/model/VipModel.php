<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class VipModel extends Model
{
    /**
     * 获取vip等级l
     */
    public function get_vip_level($vip_id = "0")
    {
        $vip = redis_hGet('vip_level_list', 1);
        if (!$vip) {
            $vip = db('vip')->alias("v")->field("v.*,u.icon as headwear_url,a.icon as approach_url,b.icon as bubble_url")
                ->join('dress_up u', "u.id=v.headwear_id", "left")
                ->join('dress_up a', "a.id=v.approach_id", "left")
                ->join('dress_up b', "b.id=v.bubble_id", "left")
                ->where("v.status= 1")
                ->order("v.sort desc,v.id asc")
                ->select();
            foreach ($vip as &$v) {
                $v['img_sum'] = count(array_filter([$v['identity_url'], $v['headwear_id'], $v['approach_id'], $v['sound_wave_app'], $v['bubble_id'], $v['room_card_app']]));
                $v['privilege_sum'] = count(array_filter([$v['is_nickname'], $v['sign_in_coin'], $v['is_rank'], $v['is_visitors'], $v['is_private_chat'], $v['shop_coin'], $v['maximum_fans'], $v['maximum_attention'], $v['is_stealth'], $v['is_ban_attention'], $v['is_kick'], $v['level_acceleration']]));

                redis_hSet("vip_level", $v['id'], json_encode($v));
            }
            redis_hSet('vip_level_list', 1, json_encode($vip));
        } else {
            $vip = json_decode($vip, true);
        }
        if ($vip_id) {
            // 获取单个详情 一位数组
            $vip = redis_hGet('vip_level', $vip_id);
            return json_decode($vip, true);
        } else {
            // 二维数组
            return $vip;
        }
    }

    /**
     * 获取当前vip等级
     */
    public function get_user_vip_level($uid)
    {
        $user_vip = redis_hGet('vip_level_user', $uid);
        if ($user_vip) {
            $user_vip = json_decode($user_vip, true);
            if ($user_vip && $user_vip['end_time'] <= NOW_TIME) {
                $user_vip = '';
            }
        }
        if (!$user_vip || $user_vip == '') {
            $user_vip = db('vip_user')->alias("l")
                ->join("vip v", "v.id =l.vip_id")
                ->field("v.*,l.end_time,l.vip_id,l.id as lid")
                ->where("l.uid=" . $uid . " and l.status= 1 and l.end_time >=" . NOW_TIME)
                ->order("l.create_time desc")
                ->find();
            if ($user_vip) {
                redis_hSet('vip_level_user', $uid, json_encode($user_vip));
            }
        }
        $result = array(
            'uid' => $uid,
            'vip_end_time' => $user_vip && $user_vip['end_time'] > NOW_TIME ? $user_vip['end_time'] : 0,
            'vip_id' => $user_vip && $user_vip['end_time'] > NOW_TIME ? $user_vip['vip_id'] : 0,
            'id' => $user_vip && $user_vip['end_time'] > NOW_TIME ? $user_vip['lid'] : 0,
            'sort' => $user_vip && $user_vip['end_time'] > NOW_TIME ? $user_vip['sort'] : 0,
        );
        return $result;
    }

    /**
     * 修改购买vip记录
     */
    public function save_vip_log($where, $update)
    {
        return db("vip_user")->where($where)->update($update);
    }

    /**
     * 增加购买记录
     */
    public function add_vip_log($add)
    {
        return db("vip_user")->insertGetId($add);
    }

    // 获取vip特权
    public function get_vip_rule_details()
    {

        $list = db("vip_rule_details")->where("status=1")->order("sort desc")->group("type")->select();

        return $list;
    }

    // 获取vip价格 只显示3个
    public function get_vip_price()
    {

        $list = db("vip_rule")->where("status=1")->order("sort desc")->limit(0, 3)->select();

        return $list;
    }
}
