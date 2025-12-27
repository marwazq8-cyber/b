<?php
/**
 * 布谷科技商业系统
 * 文章
 * @author 山东布谷鸟网络科技有限公司
 * @create 2020-08-05 00:02
 */

namespace app\agency\model;

use think\Db;
use think\Model;

class AdminLang extends Model
{
    /**
     * 获取列表
     * @param $limit
     * @param $page
     * @param $title
     * @return array|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function select_list($limit,$page,$title){

        $config['page'] = $page ? $page : 1;
        $field ="*";
        $where = $title ? "title Like  '%".$title."%'": '1=1';
        $list = $this ->field($field)
            ->where($where)
            ->order('sort', 'desc')
            ->paginate($limit, false, $config);
        if(is_object($list)){
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 当前信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function select_one($where,$field="*",$order="sort desc"){
        return $this->where($where)->field($field)->order($order)->find();
    }

    /**
     * 添加管理员
     * @param $insert
     * @return int|string
     */
    public function add($insert){
        return $this->insertGetId($insert);
    }
    /**
     * 修改管理员信息
     * @param $where
     * @param $update
     * @return bool
     */
    public function save_lang($where,$update){
        return $this ->where($where)->update($update);
    }

    /**
     *   删除信息
     * @param $where
     * @return bool
     */
    public function delete_lang($where){
        return $this->where($where)->delete();
    }
    /**
     * 语言包属性增加
     * @param $name 增加的属性值
     */
    public function add_lang_parameter($name) {
        $sql = "alter table " . config('database.connections.mysql.prefix') . "admin_lang_parameter add ".$name." varchar(20)";

        return Db::query($sql);
    }

    /**
     * 获取语言包配置
     * @param $where
     * @param $field
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lang_parameter_list($where,$field) {
        $list = Db::name('admin_lang_parameter') ->field($field)
            ->where($where)
            ->order('id', 'asc')
            ->select();
        if(is_object($list)){
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 修改语言包配置
     * @param $list  数组修改多条
     * @return
     */
    public function SaveLangParameterupdate($where, $save){
       return Db::name('admin_lang_parameter')->where($where)->update($save);
    }
    /**
     * 添加语言包配置
     * @param $insert
     * @return int|string
     */
    public function AddLangParameter($insert){
        return Db::name('admin_lang_parameter')->insertGetId($insert);
    }
    /**
    * 接口获取语言配置西信息
     */
    public function getLangParameter() {

    }
}