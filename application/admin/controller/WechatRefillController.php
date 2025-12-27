<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/6/30
 * Time: 10:14 上午
 * Name:
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class WechatRefillController extends AdminBaseController
{

    /**
     * 充值列表
     */
    public function index()
    {
        $num = input('num');
        $nummoney = 0;
        if (empty($num)) {
            $where = [];
        } else if ($num == 0) {
            $where = [];
        } else {
            $nummoney = db('user_charge_log')->where(['refillid' => $num])->sum('money');
            $where = ['id' => $num];
        }
        $list = Db::name("user_charge_rule")->where($where)->where('recharge_type = 2')->order("orderno asc")->select();
        $lists = Db::name("user_charge_rule")->where('recharge_type = 2')->order("orderno asc")->select();
        $this->assign(['list' => $list, 'lists' => $lists]);
        $this->assign('nummoney', $nummoney);
        return $this->fetch();
    }

    /**
     * 充值添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("user_charge_rule")->where("id=$id")->find();
            $this->assign('rule', $name);
        } else {
            $this->assign('rule', array('type' => 0,'status'=>1));
        }
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        $data['recharge_type'] = 2;
        if ($id) {
            $result = Db::name("user_charge_rule")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_charge_rule")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('WechatRefill/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除类型
    public function del()
    {
        $param = request()->param();
        $result = Db::name("user_charge_rule")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
    }

    //修改排序
    public function upd()
    {

        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_charge_rule")->where("id=$k")->update(array('orderno' => $v));
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

    //支付渠道列表
    public function pay_menu()
    {

        $list = db('pay_menu')->where('recharge_type = 2')->select();
        $lists = $list->toArray();
        foreach ($lists as &$v){
            $v['total_pay'] = db('user_charge_log')->where("pay_type_id = {$v['id']} and status = 1")->sum('money');
            unset($v);
        }
        $this->assign('list', $lists);
        return $this->fetch();
    }

    //添加充值渠道
    public function add_pay_menu()
    {

        return $this->fetch();
    }

    //添加充值渠道
    public function add_pay_menu_post()
    {

        $param = $this->request->param();
        $data = $param['post'];
        $data['recharge_type'] = 2;
        $result = Db::name("pay_menu")->insert($data);
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('wechat_refill/pay_menu'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //编辑充值渠道
    public function edit_pay_menu()
    {

        $id = input('param.id');

        $data = db('pay_menu')->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    //编辑支付渠道
    public function edit_pay_menu_post()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['recharge_type'] = 2;
        if ($id) {
            $result = Db::name("pay_menu")->where("id=$id")->update($data);
        } else {
            $result = Db::name("pay_menu")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('wechat_refill/pay_menu'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除类型
    public function del_pay_menu()
    {
        $param = request()->param();
        $result = Db::name("pay_menu")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

}

