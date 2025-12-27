<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/11
 * Time: 09:53
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class EvaluateController extends AdminBaseController
{
    public function index()
    {
        $list = db('evaluate_label')->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    public function add()
    {
        $id = intval(input('param.id'));
        if ($id != 0) {
            $data = db('evaluate_label')->find($id);
        } else {
            $data['type'] = 1;
        }
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function del()
    {

        $id = intval(input('param.id'));
        db('evaluate_label')->delete($id);

        echo '1';
    }

    public function addPost()
    {
        $label_name = input('param.label_name');
        $type = intval(input('param.type'));
        $orderno = input('param.orderno');
        $id = intval(input('param.id'));

        $data['label_name'] = $label_name;
        $data['orderno'] = $orderno;
        $data['create_time'] = time();
        $data['type'] = $type;

        if ($id != 0) {
            db('evaluate_label')->where('id', '=', $id)->update($data);
        } else {
            db('evaluate_label')->insert($data);
        }

        $this->success(lang('Operation_successful'));
    }

    public function list_order()
    {

        $param = request()->param();
        $data = '';
        foreach ($param['list_orders'] as $k => $v) {
            $status = db("evaluate_label")->where("id=$k")->update(array('orderno' => $v));
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