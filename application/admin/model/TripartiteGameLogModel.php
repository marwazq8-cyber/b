<?php
namespace app\admin\model;

use think\facade\Cache;
use think\facade\Db;
use think\Model;

class TripartiteGameLogModel extends Model
{
    /**
     * 增加三方游戏信息--本轮信息
     */
    public function get_list($where)
    {
        $field ="l.*,a.title,a.id as game_id";
        $List = $this->field($field)->alias("l")
            ->join('tripartite_game a', 'a.type = l.game_type','left')
            ->where($where)
            ->order('l.create_time', 'desc')
            ->paginate(10, false, ['query' => request()->param()]);
        return $List;
    }
    /**
     * 增加三方游戏信息--本轮信息累计
     */
    public function get_list_sum($where)
    {
        $field ="sum(l.consumption_coin) as consumption_coin,sum(l.total_income) as total_income,sum(l.platform_total) as platform_total";

        return $this->field($field)->alias("l")
            ->where($where)
            ->find();
    }
    /**
    * 三方游戏用户消费明细
     */
    public function get_user_list($where){
        $field ="l.*,a.title,a.id as game_id,u.user_nickname";

        $List = db("tripartite_game_user_log")->field($field)->alias("l")
            ->join('tripartite_game a', 'a.type = l.game_type','left')
            ->join('user u', 'u.id = l.uid')
            ->where($where)
            ->order('l.create_time', 'desc')
            ->paginate(10, false, ['query' => request()->param()]);

        return $List;
    }
    /**
     * 三方游戏用户消费明细 -- 累计
     */
    public function get_user_list_sum($where)
    {
        $field ="sum(l.consumption_coin) as consumption_coin,sum(l.total_income) as total_income";
        return db("tripartite_game_user_log")->field($field)->alias("l")
            ->where($where)
            ->find();
    }

}