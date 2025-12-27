<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class UserModel extends Model
{
    // 获取系统消息
    public function get_user_message_log($where, $page)
    {

        $message_log = db("user_message_log")->where($where)->page($page)->order('addtime desc')->select();

        return $message_log;
    }

    // 后台管理员审核操作
    public function get_user_message($where)
    {

        $message_list = db("user_message")->where($where)->find();

        return $message_list;
    }

    // 后台系统消息
    public function get_user_message_all($where)
    {

        $message_list = db("user_message_all")->where($where)->find();

        return $message_list;
    }

    // 后台管理员审核操作
    public function get_user($where)
    {

        $message_list = db("user")->where($where)->find();

        return $message_list;
    }

    // 修改用户
    public function update_user($where, $data)
    {

        $list = db("user")->where($where)->update($data);
        return $list;
    }

    // 统计关注总数
    public function focus_count($uid)
    {

        $focus_count = db('user_attention')->where("uid=" . $uid)->count();

        return $focus_count ? $focus_count : 0;
    }

    // 获取粉丝列表
    public function fans_count_list($uid)
    {

        $fans_count = db('user_attention')->where("attention_uid=" . $uid)->select();

        return $fans_count;
    }

    // 获取用户关注和粉丝列表
    public function focus_fans_list($where, $page, $join_where)
    {

        $list = db('user_attention')->alias("a")
            ->join("user u", $join_where)
            ->where($where)
            ->field("u.id,u.avatar,u.user_nickname,u.sex,u.age,u.income_level,u.level,u.luck,u.vip_end_time")
            ->order("a.addtime desc")
            ->page($page, 9)
            ->select();
        foreach ($list as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }
        return $list;
    }

    // 是否关注对方
    public function is_focus_user($uid, $touid)
    {

        $focus = db('user_attention')->where("uid=" . $uid . " and attention_uid=" . $touid)->find();

        return $focus;
    }

    // 关注对方
    public function add_focus($uid, $touid)
    {
        $data = array(
            'uid' => $uid,
            'attention_uid' => $touid,
            'addtime' => NOW_TIME
        );
        $atte = db('user_attention')->insert($data);

        return $atte;
    }

    /* 扣除用户金额 */
    public function deduct_user_coin($user_info, $coin, $type)
    {

        $charging_coin = db('user')->where('id=' . $user_info['id'])->setDec('coin', $coin);

        if ($charging_coin || $coin <= 0) {
            // 账号变更记录 1文字聊天 2语音聊 3视频聊 4礼物 5背包礼物 6陪玩 7签到奖励 8任务奖励 15购买vip
            upd_user_coin_log($user_info['id'], $coin, $coin, $type, 1, 2, $user_info['last_login_ip'], $user_info['id']);
        }

        return $charging_coin;
    }
}
