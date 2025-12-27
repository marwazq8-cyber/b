<?php
/**
 * 布谷科技商业系统
 * 文章
 * @author 山东布谷鸟网络科技有限公司
 * @create 2020-08-05 00:02
 */


namespace app\agency\model;

use app\agency\model\Role;
use think\Db;
use think\Model;

class AgencyLog extends Model
{

    /**
     * 获取充值记录
     */
    public function get_recharge_log($where,$page,$limit){
        $config['page'] = $page ? $page : 1;
        $field="id,agency_id,coin,old_coin,create_time";
        $List = $this-> field($field)-> where($where)
            ->order("create_time desc")
            ->paginate($limit, false, $config);
        if(is_object($List)){
            $List = $List->toArray();
        }
        return $List;
    }
    /**
     * 获取充值记录 --导出
     */
    public function get_recharge_log_export($where){
        $field="id,agency_id,coin,old_coin,create_time";
        $List = $this-> field($field)-> where($where)
            ->order("create_time desc")
            ->select();
        return $List;
    }
    /**
     * 获取充值记录统计
     */
    public function get_recharge_log_count($where,$sum){
        return $this-> where($where)->sum($sum);
    }
    /**
     * 获取充值单数
     */
    public function sel_recharge_log_count($where){
        return $this-> where($where)->count();
    }
    /**
    * 售出记录
     */
    public function get_sell_log($where,$page,$limit){
        $config['page'] = $page ? $page : 1;
        $field="l.*,u.user_nickname as nick_name,u.luck";
        $List = $this->alias("l")
            ->join("user u","u.id = l.uid")
            ->field($field)
            -> where($where)
            ->order("l.create_time desc")
            ->paginate($limit, false, $config);
        if(is_object($List)){
            $List = $List->toArray();
        }
        foreach ($List['data'] as &$v) {
            $v['uid'] = $v['luck'] ? $v['luck'] : $v['uid'];
        }
        return $List;
    }
    /**
     * 售出记录 --导出
     */
    public function get_sell_log_export($where){
        $field="l.*,u.user_nickname as nick_name";
        $List = $this->alias("l")
            ->join("user u","u.id = l.uid")
            ->field($field)
            -> where($where)
            ->order("l.create_time desc")
            ->select();
        return $List;
    }
    /**
     * 获取充售出记录统计
     */
    public function get_sell_log_count($where,$sum){
        return $this->alias("l")->join("user u","u.id = l.uid")-> where($where)->sum($sum);
    }
    /**
     *  下级充值记录
     */
    public function get_agency_log($where,$page,$limit){
        $config['page'] = $page ? $page : 1;
        $field="l.*,a.name,g.name as gname";
        $List = $this->alias("l")
            ->join("agency a","a.id = l.agency_id")
            ->join("agency g","g.id = l.agency_superior_id","left")
            ->field($field)
            -> where($where)
            ->order("l.create_time desc")
            ->paginate($limit, false, $config);
        if(is_object($List)){
            $List = $List->toArray();
        }
        return $List;
    }
    /**
     *  下级充值记录 -- 导出
     */
    public function get_agency_log_export($where){
        $field="l.*,a.name,g.name as gname";
        $List = $this->alias("l")
            ->join("agency a","a.id = l.agency_id")
            ->join("agency g","g.id = l.agency_superior_id","left")
            ->field($field)
            -> where($where)
            ->order("l.create_time desc")
            ->select();
        return $List;
    }
    /**
     * 获取下级充值记录统计
     */
    public function get_agency_log_count($where,$sum){
        return $this->alias("l")->join("agency a","a.id = l.operator_id")-> where($where)->sum($sum);
    }
    /**
     * 添加管理员
     * @param $insert
     * @return int|string
     */
    public function add_agency_log($insert){
        return $this->insertGetId($insert);
    }

}