<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 上午 11:02
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Cache;
use app\admin\model\AdminMenuModel;

class LevelController extends AdminBaseController
{
    /**
     * 获取等级聊天图标
     */
    public function level_type()
    {
        $where = "id > 0";
        $level = Db::name("level_type")->where($where)->order("sort DESC")->select();
        $this->assign('level', $level);
        return $this->fetch();
    }

    /**
     * 等级添加
     */
    public function level_type_add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("level_type")->where("id=$id")->find();
        } else {
            $name['icon'] = '';
            $name['level_icon'] = '';
            $name['level_type_name'] = '';
            $name['type'] = 1;
        }
        $this->assign('level', $name);
        return $this->fetch();
    }

    public function levelTypePost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['create_time'] = time();

        if ($id) {
            $result = Db::name("level_type")->where("id=$id")->update($data);
        } else {
            $result = Db::name("level_type")->insert($data);
        }

        if ($result) {

            $cKey = 'level_type:list';
            cache($cKey, null);
            $this->success(lang('EDIT_SUCCESS'), url('level/level_type'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    // 删除
    public function level_type_del()
    {
        $param = request()->param();
        $result = Db::name("level_type")->where("id=" . $param['id'])->delete();

        $cKey = 'level_type:list';
        cache($cKey, null);

        return $result ? '1' : '0';
    }

    /**
     * 等级列表
     */
    public function level_index()
    {
        $where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('level_name') and !$this->request->param('colors') and !$this->request->param('type')) {
            session("level_index", null);
            $data['type'] = '1';
            session("level_index", $data);

        } else if (empty($p)) {
            $data['level_name'] = $this->request->param('level_name');
            $data['colors'] = $this->request->param('colors');
            $data['type'] = $this->request->param('type');
            session("level_index", $data);
        }

        $level_name = session("level_index.level_name");
        $colors = session("level_index.colors");
        $type = session("level_index.type");

        $where = "l.type=" . $type;
        $where .= $level_name ? " and l.level_name=" . trim($level_name) : '';
        $where .= $colors ? " and l.colors like '%" . trim($colors) . "%'" : '';

        $level = Db::name("level")->alias("l")
            ->join("level_type t", "t.id =l.level_type_id", "left")
            ->field("l.*,t.level_type_name,t.icon as ticon")
            ->where($where)
            ->order("l.sort DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $level->render();
        $name = $level->toArray();

        $this->assign('level', $name['data']);
        $this->assign("page", $page);
        $this->assign("data", session("level_index"));
        return $this->fetch();
    }

    /**
     * 等级添加
     */
    public function add()
    {
        $id = input('param.id');
        $level_type = Db::name("level_type")->order("sort desc")->select();
        if ($id) {
            $name = Db::name("level")->where("levelid=$id")->find();
        } else {
            $name['chat_icon'] = '';
            $name['level_icon'] = '';
            $name['type'] = 1;
            $name['level_type_id'] = 0;
        }
        $this->assign('level_type', $level_type);
        $this->assign('level', $name);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['levelid'];
        $data = $param['post'];
        $data['addtime'] = time();

        if ($id) {
            $result = Db::name("level")->where("levelid=$id")->update($data);
        } else {
            $result = Db::name("level")->insert($data);
        }

        if ($result) {
            load_cache_rm("level", ['type' => 1]);
            load_cache_rm("level", ['type' => 2]);
            load_cache_rm("level");

            $this->success(lang('EDIT_SUCCESS'), url('level/level_index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("level")->where("levelid=" . $param['id'])->delete();
        load_cache_rm("level", ['type' => 1]);
        load_cache_rm("level", ['type' => 2]);
        load_cache_rm("level");
        return $result ? '1' : '0';
    }

    //主播收费列表
    public function fee()
    {
        $list = db('host_fee')->order("sort asc")->select()->toarray();
        foreach ($list as &$v) {
            if ($v['level'] == '0') {
                $v['name'] = lang('All_users');
            } else {
                $level = Db::name("level")->where("level_name=" . $v['level'])->find();
                $v['name'] = "LV" . $level['level_name'] . lang('ADMIN_ANCHOR');
            }
        }

        $this->assign('list', $list);

        return $this->fetch();
    }

    //添加修改
    public function add_fee()
    {
        $id = input('param.id');
        if ($id) {
            $data = db("host_fee")->find($id);
        } else {
            $data['level'] = 0;
        }
        $level = db("level")->select();
        $this->assign('fee', $data);
        $this->assign('level', $level);
        return $this->fetch();
    }

    //操作收费数据
    public function upd_fee()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("host_fee")->where("id=$id")->update($data);
        } else {
            $result = Db::name("host_fee")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('level/fee'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del_fee()
    {
        $param = request()->param();
        $result = Db::name("host_fee")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //修改排序
    public function upd()
    {
        $param = request()->param();
        $data = '';

        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("host_fee")->where("id=$k")->update(array('sort' => $v));
            if ($status) {
                $data = $status;
            }
        }

        if ($data) {
            $this->success(lang('Sorting_succeeded'));
        } else {
            $this->error(lang('Sorting_error'));
        }
    }

    //勋章列表
    public function medal()
    {
        $list = db('medal')->order("sort desc")->select()->toarray();

        $this->assign('list', $list);
        return $this->fetch();
    }

    //添加修改
    public function add_medal()
    {
        $id = input('param.id');
        if ($id) {
            $data = db("medal")->find($id);
        } else {
            $data['icon'] = '';
        }

        $this->assign('list', $data);
        return $this->fetch();
    }

    //增加勋章
    public function addPost_medal()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if ($id) {
            $result = Db::name("medal")->where("id=$id")->update($data);
        } else {
            $result = Db::name("medal")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('level/medal'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除勋章
    public function del_medal()
    {
        $param = request()->param();
        $result = Db::name("medal")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }
}
