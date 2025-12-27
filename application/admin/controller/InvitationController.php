<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14 0014
 * Time: 上午 10:34
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class InvitationController extends AdminBaseController {
    //邀请提现规则
    public function index(){
        $list = Db::name("user_invitation_withdrawal")->order("sort asc")->select()->toarray();

        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 提现添加
     */
    public function add() {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("user_invitation_withdrawal")->where("id=$id")->find();
            $this->assign('rule', $name);
        } else {
            $this->assign('rule', array('status' => 1));
        }
        return $this->fetch();
    }

    public function addPost() {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("user_invitation_withdrawal")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_invitation_withdrawal")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('Invitation/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    //删除类型
    public function del() {
        $param = request()->param();
        $result = Db::name("user_invitation_withdrawal")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
    }
    //修改排序
    public function upd() {

        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_invitation_withdrawal")->where("id=$k")->update(array('sort' => $v));
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

    public function invite_list(){
        $where = '';
        if(!empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['i.user_id'] = $this->request->param('user_id');
            $where['u.user_nickname'] = ['like',['%'.$this->request->param('user_nickname'.'%')]];
        }
        if(!empty($this->request->param('uid'))){
            $where['i.user_id'] = $this->request->param('user_id');

        }

        if(!empty($this->request->param('nickname'))){
            $where['u.user_nickname'] = ['like',['%'.$this->request->param('user_nickname'.'%')]];
        }
        if(!empty($this->request->param('start_time')) && !empty($this->request->param('end_time'))){
            $where['i.create_time'] = ['between',[$this->request->param('start_time'),$this->request->param('end_time')]];
        }


        $res = db('invite_record')
                ->alias('i')
                ->join('user u','u.id=i.user_id')
                ->field('i.*,u.user_nickname,u.user_status')
                ->group('user_id')
                ->where($where)
                ->select();
        $list = [];
        foreach($res as $val){
            //充值收益
            $where['user_id'] = $val['user_id'];
            $where['type'] = 2;
            $val['money'] = db('invite_profit_record')->where($where)->sum('money');
            //主播收益
            $val['auth_money'] = db('invite_profit_record')->where(['type'=>1,'user_id'=>$val['user_id']])->sum('money');
            //二级收益
            $val['er_money'] = db('invite_profit_record')->where(['user_id'=>$val['invite_user_id']])->sum('money');
            $list[] = $val;
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    //拉黑
    public function edit_black(){
        $id = input('param.id');
        $userInfo = db('user')->field('user_status')->find($id);
        if($userInfo['user_status']==0){
            $res = db('user')->where('id',$id)->update(['user_status'=>2]);
        }else{
            $res = db('user')->where('id',$id)->update(['user_status'=>0]);
        }

        if ($res) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->success(lang('operation_failed'));
        }
    }

    public function redbag(){
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('name') and !$this->request->param('colors') and !$this->request->param('type') ) {
            session("level_index", null);
            $data['type'] = '0';
            session("level_index", $data);

        } else if (empty($p)) {
            $data['name'] = $this->request->param('name');
            $data['colors'] = $this->request->param('colors');
            $data['type'] = $this->request->param('type');
            session("level_index", $data);
        }

        $level_name = session("level_index.name");
        $colors = session("level_index.colors");
        //$game_id = session("level_index.game_id");
        $type = session("level_index.type");
        $where = '';
        $where = "id > 0 ";
        $where .=$level_name ? " s.uid=".intval($level_name) : '';
        $where .= $colors ? " and u.user_nickname like '%".trim($colors)."%'" :'';
        $where .= $type ? " and type =".$type:'';
        $page = 10;
        $data = Db::name('invite_redbag')
            ->where($where)
            ->order('type,orderno')
            ->paginate($page, false, ['query' => request()->param()]);
        //$data = [];
        $config_log = load_cache('config');
        $this->assign('currency_name', $config_log['currency_name']);
        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign("data", session("level_index"));

        return $this->fetch();
    }

    public function redbag_add(){
        $id = input('param.id');
        if ($id) {
            $name = Db::name("invite_redbag")->where("id=$id")->find();
            $this->assign('level', $name);
        } else {
            $this->assign('level', array('type' => 1));
        }
        $config_log = load_cache('config');
        $this->assign('currency_name', $config_log['currency_name']);
        return $this->fetch();
    }

    public function redbagAddPost() {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("invite_redbag")->where("id=$id")->update($data);
        } else {
            $result = Db::name("invite_redbag")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('Invitation/redbag'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function redbag_del() {
        $param = request()->param();
        $result = Db::name("invite_redbag")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
    }
}
