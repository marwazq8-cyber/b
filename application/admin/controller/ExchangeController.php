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

class ExchangeController extends AdminBaseController {
    //兑换规则
    public function index(){

        $list = Db::name("user_exchange_list")->order("sort asc")->select()->toarray();
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 兑换添加
     */
    public function add() {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("user_exchange_list")->where("id=$id")->find();
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
            $result = Db::name("user_exchange_list")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_exchange_list")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('Exchange/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    //删除兑换
    public function del() {
        $param = request()->param();
        $result = Db::name("user_exchange_list")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
    }
    //修改兑换排序
    public function upd() {

        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_exchange_list")->where("id=$k")->update(array('sort' => $v));
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
    //兑换记录
    public function user_log(){
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('touid') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("exchange_user_log", null);
        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['touid'] = $this->request->param('touid');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');

            session("exchange_user_log", $data);
        }

        $uid = session("exchange_user_log.uid");
        $touid = session("exchange_user_log.touid");
        $start_time = session("exchange_user_log.start_time");
        $end_time = session("exchange_user_log.end_time");

        $where = 'a.id > 0';
        $where .= $uid ? " and a.uid=".$uid : '';
        $where .= $touid ? " and a.touid=".$touid : '';
        $where .= $start_time ? " and a.addtime >=".strtotime($start_time) : '';
        $where .= $end_time ? " and a.addtime <=".strtotime($end_time) : '';

        $users = Db::name('user_exchange_log')->alias("a")
            ->where($where)
            ->field("u.user_nickname as uname,a.*,s.user_nickname as toname")
            ->join("user u", "u.id=a.uid")
            ->join("user s", "s.id=a.touid")
            ->order("a.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();
        $sum_one = Db::name('user_exchange_log')->alias("a")
            ->where($where)
            ->field("sum(a.earnings) as earnings,sum(a.coin) as coin")
            ->join("user u", "u.id=a.uid")
            ->join("user s", "s.id=a.touid")
            ->find();
        $sum = array(
            'earnings' => $sum_one ? intval($sum_one['earnings']) : 0,
            'coin' => $sum_one ? intval($sum_one['coin']) : 0,
        );
        $this->assign("sum", $sum);
        $this->assign("page", $page);
        $this->assign("list", $name['data']);
        $this->assign("data", session("exchange_user_log"));
        return $this->fetch();

    }
}