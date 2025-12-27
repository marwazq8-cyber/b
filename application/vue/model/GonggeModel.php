<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-18
 * Time: 11:16
 */

namespace app\vue\model;

use think\Model;
use think\Db;
use think\helper\Time;

class GonggeModel extends Model
{
    /**
     * 获取宫格列表
     */
    public function get_gongge_list($where = "status=1")
    {
        $list = db('gongge')->where($where)->order('sort')->select();
        foreach ($list as &$v) {
            $v['luck_draw'] = explode(",", $v['frequency']);
        }
        return $list;
    }

    /**
     * 获取宫格列表
     */
    public function get_gongge_one($where)
    {
        return db('gongge')->where($where)->find();
    }

    /**
     * 获取宫格礼物列表
     */
    public function get_gongge_gift($where, $sum = '10')
    {
        $field = "f.id,f.gift_name,f.gift_img,g.name,g.img,g.coin,f.gift_id,f.gongge_id,f.odds,f.is_system_push,f.is_male_screen,f.is_all_notify,f.is_double";
        $list = db('gongge_gift')->alias('f')
            ->field($field)
            ->join('gift g', 'g.id = f.gift_id', 'left')
            ->where($where)
            ->order('f.sort')
            ->limit($sum)
            ->select();
        foreach ($list as &$v) {
            $v['name'] = $v['gift_id'] > 0 ? $v['name'] : $v['gift_name'];
            $v['img'] = $v['gift_id'] > 0 ? $v['img'] : $v['gift_img'];
            $v['coin'] = $v['gift_id'] > 0 ? $v['coin'] : 0;
        }
        return $list;
    }

    /**
     * 获取公告滚动通知栏信息
     * @param $where
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_gongge_roll_log($where)
    {
        // 系统金币单位名称
        $config = load_cache('config');
        $field = "g.name,g.coin,l.gift_id,l.gongge_id,u.user_nickname";
        $list = db('gongge_log')->alias('l')
            ->field($field)
            ->join('user u', 'u.id = l.uid')
            ->join('gift g', 'g.id = l.gift_id')
            ->where($where)
            ->order('l.create_time desc')
            ->limit(10)
            ->select();
        foreach ($list as &$v) {
            $v['user_nickname'] = emoji_decode($v['user_nickname']);
            $v['text'] = "恭喜 <font color='#ffc238'>" . cutSubstr($v['user_nickname']) . "</font> 抽中了价值 <font color='#ffc238'>" . $v['coin'] . $config['currency_name'] . "</font> 的 <font color='#ffc238'>" . $v['name'] . "</font>";
        }
        return $list;
    }

    /**
     * 获取单个用户抽奖记录表
     * @param        $where
     * @param        $limit
     * @param string $number
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_gongge_log($where, $limit, $number = '20')
    {
        $field = "g.name,g.coin,g.img,l.gift_id,l.gongge_id,l.create_time,l.sum";
        $list = db('gongge_log')->alias('l')
            ->field($field)
            ->join('gift g', 'g.id = l.gift_id')
            ->where($where)
            ->order('l.create_time desc')
            ->limit($limit, $number)
            ->select();
        foreach ($list as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        return $list;
    }

    /**
     * 获取排行榜
     * @param        $where
     * @param        $limit
     * @param string $number
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_gongge_ranking_log($where, $limit, $number = '20')
    {
        $field = "u.user_nickname,u.avatar,sum(l.gift_coin * l.sum) as ranking_coin";
        $list = db('gongge_log')->alias('l')
            ->field($field)
            ->join('user u', 'u.id = l.uid')
            ->where($where)
            ->group("l.uid")
            ->order('ranking_coin desc')
            ->limit($limit, $number)
            ->select();
        foreach ($list as &$v) {
            $v['user_nickname'] = emoji_decode($v['user_nickname']);
        }
        return $list;
    }
}
