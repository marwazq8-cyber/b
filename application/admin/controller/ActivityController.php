<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-09-09
 * Time: 10:46
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class ActivityController extends AdminBaseController
{
    /**
     * 活动消息列表
     */
    public function index()
    {
        $page = 10;
        $list = Db::name("user_msg")
            ->where('type = 2')
            ->order('addtime desc')
            ->paginate($page, false, ['query' => request()->param()]);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $gift = Db::name("user_msg")->where("id=$id")->find();
            $gift['starttime'] = date('Y-m-d H:i:s', $gift['starttime']);
            $gift['endtime'] = date('Y-m-d H:i:s', $gift['endtime']);
        } else {
            $gift['coin_type'] = 1;
            $gift['type'] = 2;
            $gift['is_all_notify'] = 1;
            $gift['img'] = null;
            $gift['url'] = null;
            $gift['starttime'] = date('Y-m-d H:i:s');
            $gift['endtime'] = date('Y-m-d H:i:s', time() + 864000);
        }

        $this->assign('gift', $gift);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        //  print_r($param);exit;
        $id = $param['id'];
        $data = $param['post'];
        $data['img'] = $param['post']['img'];
        $data['url'] = $param['post']['url'];
        $data['starttime'] = strtotime($param['post']['starttime']);
        $data['endtime'] = strtotime($param['post']['endtime']);
        $data['addtime'] = time();
        $data['type'] = 2;
        if ($id) {
            $result = Db::name("user_msg")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_msg")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('activity/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("user_msg")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //修改排序
    public function upd()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("gift")->where("id=$k")->update(array('orderno' => $v));
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
}
