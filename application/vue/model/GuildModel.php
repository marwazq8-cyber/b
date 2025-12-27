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

class GuildModel extends Model
{
    /**
     * 获取公会列表
     */
    public function get_guild_list($where, $limit, $number = '20')
    {
        $field = "id,name,logo";
        $list = db('guild')->field($field)
            ->where($where)
            ->order('total_earnings desc')
            ->limit($limit, $number)
            ->select();
        foreach ($list as &$v) {
            // 获取公会下总人数
            $v['guild_join_sum'] = db('guild_join')->where("status = 1 and guild_id=" . $v['id'])->count();
        }
        return $list;
    }
}