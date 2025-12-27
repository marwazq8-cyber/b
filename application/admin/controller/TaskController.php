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

class TaskController extends AdminBaseController
{
    /**
     * 任务列表
     */
    public function index()
    {
        $list = db('task')->where('admin_hide', '=', 0)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    //修改
    public function edit()
    {
        $id = request()->param('id');
        $task = db('task')->where('id', $id)->find();
        $this->assign('data', $task);
        return $this->fetch();
    }

    public function editPost()
    {
        $param = $this->request->param();
        $data['name'] = $param['name'];
        $data['status'] = intval($param['status']);
        $data['coin'] = intval($param['coin']);
        $data['orderno'] = intval($param['orderno']);
        $data['addtime'] = time();
        $id = intval($param['id']);
        $result = db('task')->where('id', $id)->update($data);
        if ($result) {
            $this->success(lang('Modified_successfully'), url('task/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    /**
     * 奖励记录
     */
    public function task_log()
    {


        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('type') and !$this->request->param('uid') and !$this->request->param('end_time') and !$this->request->param('start_time')) {
            session("task", null);
            $data['type'] = '0';
            $data['start_time'] = '';
            $data['end_time'] = '';
            session("task", $data);

        } else if (empty($p)) {
            $data['type'] = intval($this->request->param('type'));
            $data['uid'] = $this->request->param('uid');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("task", $data);
        }

        $type = session("task.type");
        $uid = session("task.uid");
        $start_time = session("task.start_time");
        $end_time = session("task.end_time");

        $where = "t.id >0";
        $where .= $type ? " and t.type=" . $type : '';
        $where .= $uid ? " and t.uid=" . $uid : '';
        $where .= $start_time > 0 && $end_time ? " and t.addtime >=" . $start_time . " and t.addtime <=" . strtotime($end_time) : '';

        $list = db('task_log')->alias("t")
            ->join('user u', 'u.id=t.uid')
            ->field("t.*,u.user_nickname as uname")
            ->where($where)
            ->order('t.addtime desc')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('data', $list);
        $this->assign('page', $list->render());
        $this->assign("request", session("task"));
        return $this->fetch();
    }

}
