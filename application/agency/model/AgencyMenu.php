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

class AgencyMenu extends Model
{
    /**
    * 获取权限
     */
    public function get_agency_menu($where,$field="*"){
        return $this->where($where)->field($field)->select();
    }
}