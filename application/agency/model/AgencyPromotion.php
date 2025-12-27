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

class AgencyPromotion extends Model
{
    /**
     *  获取用户列表
     */
    public function get_member_list($where,$page,$limit){
        $config['page'] = $page ? $page : 1;
        $field="a.id,a.uid,u.user_nickname as nick_name,u.create_time,u.is_online,u.login_time,u.logout_time";
        $List = $this -> alias('a')
            -> join('user u','u.id = a.uid')
            -> field($field)
            -> where($where)
            ->order("a.create_time desc")
            ->paginate($limit, false, $config);
        if(is_object($List)){
            $List = $List->toArray();
        }
        return $List;
    }
    /**
    * 获取主播列表
     */
    public function get_host_list($where,$page,$limit){
        $config['page'] = $page ? $page : 1;
        $field="a.id,a.uid,u.user_nickname as nick_name,u.create_time,u.is_online,u.login_time,u.logout_time,u.len_time";
        $List = $this -> alias('a')
            -> join('user u','u.id = a.uid')
            -> field($field)
            -> where($where)
            ->order("a.create_time desc")
            ->paginate($limit, false, $config);
        if(is_object($List)){
            $List = $List->toArray();
        }
        $time = NOW_TIME;
        foreach ($List['data'] as &$v) {
            $day_time = $time - strtotime($v['create_time']);
            $v['create_day'] = $day_time ? get_live_time_lenght($day_time) : 0;
            $v['len_time'] = $v['len_time'] ? get_live_time_lenght($v['len_time']) : 0;
        }
        return $List;
    }
    /**
    * 修改代理充值机构推广状态
     */
    public function save_update($where,$update){
        return $this ->where($where)->update($update);
    }
}