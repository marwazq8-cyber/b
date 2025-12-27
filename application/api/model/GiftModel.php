<?php

namespace app\api\model;

use think\Model;
use think\Db;

class GiftModel extends Model
{
    /* 增加送礼物记录 */
    public function add_user_gift_log($gift_log)
    {
        $gift_log['date_y_m_d'] = date('Y-m-d');
        $gift_log['date_y_m'] = date('Y-m');
        $gift_log['date_y_w'] = date('Y')."-".date('W');
        $gift_log['date_y_m_d_h'] = date('Y-m-d')."-".date('H');
        $table_id = db('user_gift_log')->insertGetId($gift_log);

        return $table_id;
    }

    /* 获取单个的背包礼物 */
    public function get_user_bag_one($uid, $id)
    {

        $bag = db('user_bag')->where(['uid' => $uid, 'giftid' => $id])->find();

        return $bag;
    }

    /* 扣除背包礼物数量 */
    public function del_user_bag_sum($uid, $gid, $num)
    {

        $user_bag = db('user_bag')->where(['uid' => $uid, 'giftid' => $gid])->setDec('giftnum', $num);

        return $user_bag;
    }

    /* 获取背包礼物 */
    public function sel_user_bag($uid)
    {

        $baglist = db('user_bag')->alias('u')
            ->join('gift g', 'u.giftid=g.id')
            ->field('u.giftnum,g.img,g.id,g.type,g.name,g.svga,g.coin,g.coin_type')
            ->where('u.uid = ' . $uid . ' and u.giftnum > 0')
            ->order('g.coin desc')
            ->select();

        return $baglist;
    }

    /*
     * 获取背包礼物总价值
     * */
    public function user_bag_count($uid)
    {
        $bag_count = db('user_bag')->alias('u')
            ->join('gift g', 'u.giftid=g.id')
            ->field('u.giftnum,g.img,g.id,g.type,g.name,g.svga,g.coin,sum(u.giftnum*g.coin) as total_coin,g.coin_type')
            ->where('u.uid = ' . $uid . ' and u.giftnum > 0')
            //->order('g.coin desc')
            ->find();
        return $bag_count['total_coin'] ? $bag_count['total_coin'] : 0;
    }

    /* 获取礼物寓意列表*/
    public function moral()
    {

        $list = db('gift_sum')->order('sort desc')->select();

        return $list;
    }

    // 获取记录表的礼物名称
    public function get_user_gift_one_log($id)
    {

        $gift = db('user_gift_log')->alias('l')
            ->join('gift g', 'l.gift_id=g.id')
            ->field('g.name')
            ->where('l.id = ' . $id)
            ->find();

        return $gift;
    }


}