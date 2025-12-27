<?php
namespace app\agency\model;

use think\Db;
use think\Model;

class User extends Model
{
    /**
     * 获取充值总人数
     */
    public function getUserListCount($where){
        return $this->alias('u')
            ->join('agency_log p', 'p.uid = u.id')
            ->where($where)
            ->group("u.id")
            ->select();
    }

    /**
     * 增加收益
     * @param $where
     * @return bool
     */
    public function inc_user($where,$field,$val){
        return $this->where($where)-> Inc($field,$val) -> update();
    }
    /**
    * 查询单个用户信息
     */
    public function get_user_one($where,$field='*'){
        $List = $this->where($where)->field($field)->find();
        if(is_object($List)){
            $List = $List->toArray();
        }
        return $List;
    }


}