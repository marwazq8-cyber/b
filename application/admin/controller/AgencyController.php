<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20 0020
 * Time: 上午 10:38
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AgencyController extends AdminBaseController
{
    /**
    * 后台充值
     */
    public function recharge(){
        $id = $this->request->param('id', 0, 'intval');
        $this->assign('id', $id);
        return $this->fetch();
    }
    /**
    * 充值
     */
    public function add_recharge_post(){
        $agency_id = intval(input('param.agency_id'));
        $coin = intval(input('param.coin'));
        $userPassword = input('user_password', '', 'trim');
        if (empty($userPassword)) {
            $this->error(lang('PASSWORD_NOT_RIGHT'), url('refill/add_recharge'));
            exit;
        }
        $adminUser = db('user')->where('id=' . cmf_get_current_admin_id())->find();
        if (!cmf_compare_password($userPassword, $adminUser['user_pass'])) {
            $this->error(lang('PASSWORD_NOT_RIGHT'));
            exit;
        }
        $data = array(
            'agency_id' => $agency_id,
            'coin' => $coin,
            'create_time' => NOW_TIME,
            'type' => 1,
            'desc' => ''
        );
        $agenct = db('agency')->where('id = '. $agency_id)->find();
        if (!$agenct) {
            $this->error(lang('AGENCY_NOT_EXIST'), url('refill/add_recharge'));
            exit;
        }
        $data['old_coin'] = $agenct['coin'];
        db('agency')->where('id = '. $agency_id)->setInc('coin', $coin);
        db('agency_log')->insert($data);



        $this->success(lang('EDIT_SUCCESS'), url('agency/recharge'));
    }
    /**
    * 获取充值后台列表
     */
    public function index(){
        //搜索条件
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('id') and !$this->request->param('name') and !$this->request->param('status') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("admin_agent", null);
            $data['start_time'] = '';
            $data['end_time'] = '';
            $data['status'] = '';
            session("admin_agency", $data);
        } else if (empty($p)) {
            $data['id'] = $this->request->param('id');
            $data['name'] = $this->request->param('name');
            $data['status'] = $this->request->param('status');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("admin_agency", $data);
        }
        $id = intval(session("admin_agency.id"));
        $start_time = session("admin_agency.start_time") ? strtotime(session("admin_agency.start_time")) : '';
        $end_time = session("admin_agency.end_time") ? strtotime(session("admin_agency.end_time") . " 23:59:59") : '';
        $name = session("admin_agency.name");
        $status = session("admin_agency.status");

        $where = "id > 0";
        $where .= $id ? " and id=".$id : '';
        $where .= $status >= '0' ? " and status=".$status : '';
        $where .= $name ? " and name like '%".$name."%'" : '';
        $where .= $start_time ?  " and create_time >= ".$start_time : '';
        $where .= $end_time ?  " and create_time <= ".$end_time : '';

        $agency = Db::name('agency')->where($where)
            ->order("create_time DESC")
            ->paginate(10, false, ['query' => request()->param()]);
        // 获取分页显示
        $page = $agency->render();
        $agency_list_array = $agency->toArray();

        $this->assign("page", $page);
        $this->assign("users", $agency_list_array['data']);
        $this->assign("data", session("admin_agency"));
        return $this->fetch();
    }
    /**
     * 账号编辑 --充值后台
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        if($id){
            $list = Db::name('agency')->where(["id" => $id])->find();
        }else{
            $list=array(
                'id'=>0,
                'status'=>1,
                'platform_level'=>1,
            );
        }
        $this->assign("list",$list);
        return $this->fetch();
    }
    /**
     * 账号编辑提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $id = $_POST['id'];
            $login = $_POST['login'];
            $where="login ='$login'";
            $where.=$id ? " and id !=".$id:'';
            if(empty($login)) {
                $this->error(lang("请输入登录账号"));
            }
            $user = DB::name('agency')->where($where)->find();
            if ($user) {
                $this->error(lang("操作失败,账号已存在！"));
            }

            $agent_name = $_POST['name'];
            if(empty($agent_name)) {
                $this->error(lang("请输入账号昵称"));
            }

            $data=array(
                'login' =>$login,
                'status' =>intval($_POST['status']),
                'name'=>$_POST['name'],
                'tel'=>$_POST['tel'],
                'endtime' => time(),
            );

            if($id){
                if ($_POST['psd']) {
                    $data['psd'] = cmf_password($_POST['psd']);
                }
                $result = Db::name('agency')->where("id=".$id)->update($data);
            }else{
                if (!$_POST['psd']) {
                    $this->error(lang("请输入登录渠道账户密码！"));
                }
                $data['psd'] = cmf_password($_POST['psd']);
                $data['create_time'] = time();

                // 是否自动生成推广码
                $link=rand_str_number(4);
                $where_link="invitation_code='".$link."'";
                $where_link.=$id ? " and id !=".$id:'';
                $link_status = Db::name('agency')->where($where_link)->find();
                if($link_status){
                    $link= rand_str_number(4);
                }
                $data['invitation_code']=$link;
                $result = DB::name('agency')->insertGetId($data);
            }
            if ($result !== false) {
                $this->success(lang('Saved_successfully'), url("agency/index"));
            } else {
                $this->error(lang('Save_failed'));
            }
        }
    }
    /**
     * 删除账号
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');

        if (Db::name('agency')->delete($id) !== false) {
            $this->success();
            $result['code'] = 1;
            $result['msg'] = lang('DELETE_SUCCESS');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('DELETE_FAILED');
        }

        echo json_encode($result);exit;
    }
    /**
     * 后台充值记录
     */
    public function recharge_agency_log(){
        $where = "l.type =1";
        if (!input('request.page')) {
            session('recharge_agency_log', null);
        }
        if (input('request.agency_id') || input('request.start_time') || input('request.end_time')) {
            session('recharge_agency_log', input('request.'));
        }
        $where .= session('recharge_agency_log.agency_id') ? " and l.agency_id = ".intval(session('recharge_agency_log.agency_id')) : '';
        $where .= session('recharge_agency_log.end_time') ? " and l.create_time <= ".strtotime(session('recharge_agency_log.end_time')) : '';
        $where .= session('recharge_agency_log.start_time') ? " and l.create_time >= ".strtotime(session('recharge_agency_log.start_time')) : '';

        $user = Db::name('agency_log')->alias("l")
            ->where($where)
            ->join('agency a','a.id = l.agency_id','left')
            ->field("l.*,a.name as aname")
            ->order("l.create_time DESC")
            ->paginate(20, false, ['query' => request()->param()]);

        $lists = $user->toArray();

        // 总统计
        $money = Db::name('agency_log')->alias("l")
            ->join('agency a','a.id = l.agency_id','left')
            ->where($where)
            ->sum('l.coin');

        $config = load_cache('config');
        $currency_name = $config['currency_name'];

        $this->assign('currency_name', $currency_name);
        $this->assign('money', $money);
        $this->assign('data', $lists['data']);
        $this->assign('request', session('recharge_agency_log'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }
    /**
     * 充值用户记录
     */
    public function recharge_user_log(){
        $where = "l.type =2";
        if (!input('request.page')) {
            session('recharge_user_log', null);
        }
        if (input('request.uid') || input('request.operator_id') || input('request.start_time') || input('request.end_time')) {
            session('recharge_user_log', input('request.'));
        }
        $where .= session('recharge_user_log.uid') ? " and l.uid = ".intval(session('recharge_user_log.uid')) : '';
        $where .= session('recharge_user_log.operator_id') ? " and l.operator_id  = ".intval(session('recharge_user_log.operator_id')) : '';
        $where .= session('recharge_user_log.end_time') ? " and l.create_time <= ".strtotime(session('recharge_user_log.end_time')) : '';
        $where .= session('recharge_user_log.start_time') ? " and l.create_time >= ".strtotime(session('recharge_user_log.start_time')) : '';

        $user = Db::name('agency_log')->alias("l")
            ->where($where)
            ->join("user u", "u.id=l.uid",'left')
            ->join('agency a','a.id = l.operator_id','left')
            ->field("l.*,a.name as aname,u.user_nickname")
            ->order("l.create_time DESC")
            ->paginate(20, false, ['query' => request()->param()]);

        $lists = $user->toArray();

        // 总统计
        $money = Db::name('agency_log')->alias("l")
            ->join("user u", "u.id=l.uid",'left')
            ->join('agency a','a.id = l.operator_id','left')
            ->where($where)
            ->sum('l.coin');

        $config = load_cache('config');
        $currency_name = $config['currency_name'];

        $this->assign('currency_name', $currency_name);
        $this->assign('money', $money);
        $this->assign('data', $lists['data']);
        $this->assign('request', session('recharge_user_log'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }
}
