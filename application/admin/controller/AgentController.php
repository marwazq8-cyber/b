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

class AgentController extends AdminBaseController
{
    /**
     * 消费记录
     */
    public function consumption(){
        $where = "l.agent_company > 0";
        if (!input('request.page')) {
            session('consume_index', null);
        }
        if (input('request.uid') || input('request.touid') || input('request.agent_company') || input('request.agent_staff') || input('request.agent_id')  || input('request.start_time') || input('request.end_time')) {
            session('consume_index', input('request.'));
        }
        $where .= session('consume_index.uid') ? " and l.user_id = ".intval(session('consume_index.uid')) : '';
        $where .= session('consume_index.touid') ? " and l.to_user_id  = ".intval(session('consume_index.touid')) : '';
        $where .= session('consume_index.agent_company') ? " and l.agent_company = ".intval(session('consume_index.agent_company')) : '';
        $where .= session('consume_index.agent_staff') ? " and l.agent_staff = ".intval(session('consume_index.agent_staff')) : '';
        $where .= session('consume_index.agent_id') ? " and l.agent_id = ".intval(session('consume_index.agent_id')) : '';
        $where .= session('consume_index.end_time') ? " and l.create_time <= ".strtotime(session('consume_index.end_time')) : '';
        $where .= session('consume_index.start_time') ? " and l.create_time >= ".strtotime(session('consume_index.start_time')) : '';


        $user = Db::name('user_consume_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->join("user u", "u.id=l.user_id")
            ->join("user t", "t.id=l.to_user_id")
            ->join('agent s','s.id = l.agent_staff','left')
            ->join('agent c','c.id = l.agent_company','left')
            ->field("l.id,c.login_name as cname,l.user_id as uid,u.create_time as utime,l.to_user_id,t.user_nickname as tname,l.content,l.coin,l.agent_id,l.agent_staff,l.classification,l.classification_id,l.agent_company,a.login_name,u.user_nickname,s.login_name as sname,l.create_time,l.agent_company as agent_company,l.agent_staff as agent_staff")
            ->order("l.create_time DESC")
            ->paginate(20, false, ['query' => request()->param()]);

        $lists = $user->toArray();

        // 总统计
        $money = Db::name('user_consume_log')->alias("l")
            ->join('agent a','a.id = l.agent_id')
            ->where($where)
            ->sum( 'l.coin');

        $config = load_cache('config');
        $currency_name = $config['currency_name'];

        $this->assign('currency_name', $currency_name);
        $this->assign('money', $money);
        $this->assign('data', $lists['data']);
        $this->assign('request', session('consume_index'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }
    /**
     * 导出消费记录
     */
    public function export_consumption(){
        $where = "l.agent_company > 0";
        if (!input('request.page')) {
            session('consume_index', null);
        }
        if (input('request.uid') || input('request.touid') || input('request.agent_company') || input('request.agent_staff') || input('request.agent_id')  || input('request.start_time') || input('request.end_time')) {
            session('consume_index', input('request.'));
        }
        $where .= session('consume_index.uid') ? " and l.user_id = ".intval(session('consume_index.uid')) : '';
        $where .= session('consume_index.touid') ? " and l.to_user_id  = ".intval(session('consume_index.touid')) : '';
        $where .= session('consume_index.agent_company') ? " and l.agent_company = ".intval(session('consume_index.agent_company')) : '';
        $where .= session('consume_index.agent_staff') ? " and l.agent_staff = ".intval(session('consume_index.agent_staff')) : '';
        $where .= session('consume_index.agent_id') ? " and l.agent_id = ".intval(session('consume_index.agent_id')) : '';
        $where .= session('consume_index.end_time') ? " and l.create_time <= ".strtotime(session('consume_index.end_time')) : '';
        $where .= session('consume_index.start_time') ? " and l.create_time >= ".strtotime(session('consume_index.start_time')) : '';

        $list = Db::name('user_consume_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->join("user u", "u.id=l.user_id")
            ->join('agent s','s.id = l.agent_staff','left')
            ->field("l.id,l.user_id as uid,u.create_time as utime,l.to_user_id,l.content,l.coin,l.agent_id,l.agent_staff,l.classification,l.classification_id,l.agent_company,a.login_name,u.user_nickname,s.login_name as sname,l.create_time,l.agent_company as agent_company,l.agent_staff as agent_staff")
            ->order("l.create_time DESC")
            ->select();

        $title = '消费明细记录';
        if ($list != null) {
            $dataResult = array();
            foreach ($list as $k=>$v) {
                $dataResult[$k]['user_nickname'] = $v['user_nickname'];
                $dataResult[$k]['uid'] = $v['uid'] ;
                $dataResult[$k]['login_name'] = $v['login_name'] ;
                $dataResult[$k]['agent_id'] = $v['agent_id'];
                $dataResult[$k]['content'] = $v['content'];
                $dataResult[$k]['coin'] = $v['coin'];
                $dataResult[$k]['to_user_id'] = $v['to_user_id'];
                $dataResult[$k]['time'] = date('Y-m-d H:i:s',$v['create_time']);
                $dataResult[$k]['utime'] = date('Y-m-d H:i:s',$v['utime']);
            }
            $str = lang("消费用户,用户ID,邀请渠道,邀请渠道ID,消费物品,消费金额,收礼物账号,消费时间,注册时间");
            $this->excelData($dataResult, $str, $title); exit();

        }else{
            $this->error(lang("暂无数据"));
        }
    }
    /**
    * 推广员统计列表
     */
    public function extension(){
        $data =[];
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('date') and !$this->request->param('agent_company') and !$this->request->param('uid') and !$this->request->param('agent_staff') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['date'] ='day';
            $data['start_time'] ='';
            $data['end_time']  = '';
            $data['uid']  = '';
            session("extension", $data);
        } else if (empty($p)) {
            $data['date'] = $this->request->param('date');
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['agent_company'] = $this->request->param('agent_company');
            $data['uid'] = $this->request->param('uid');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');

            session("extension", $data);
        }

        $start_time = session("extension.start_time") ? session("extension.start_time") : '';
        $end_time = session("extension.end_time") ? session("extension.end_time") : '';

        $agent_staff = intval(session("extension.agent_staff"));
        $agent_company = intval(session("extension.agent_company"));
        $agent_id = intval(session("extension.uid"));
        $date = session("extension.date");
        $where = "a.agent_level=3";
        $where .= $agent_staff ? " and s.agent_staff=".$agent_staff : '';
        $where .= $agent_company ? " and s.agent_company=".$agent_company : '';
        $where .= $agent_id ? " and s.uid=".$agent_id : '';
        // 按日统计或按月统计
        if ($date == 'month') {
            $where .= $end_time ? " and s.month_time <='" . date('Ym',strtotime($end_time)) . "'" : '';
            $where .= $start_time ? " and s.month_time >='" . date('Ym',strtotime($start_time)) . "'" : '';
            $group = "s.uid,s.month_time";
        }else{
            $where .= $end_time ? " and s.date_time <='" . $end_time . "'" : '';
            $where .= $start_time ? " and s.date_time >='" . $start_time . "'" : '';
            $group = "s.uid,s.date_time";
        }
        $filed ="sum(s.consumption) as consumption,s.uid,a.agent_login,g.agent_company,g.agent_login as glogin,s.agent_staff,e.agent_login as elogin,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum,s.date_time,s.month_time";
        $agent_list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->join("agent g","g.id = s.agent_staff",'left')
            ->join("agent e","e.id = s.agent_company",'left')
            ->field($filed)
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);
        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum";

        $agent_money = Db::name('agent_statistical')->alias("s")->join("agent a","a.id = s.uid")->field($filed)->where($where)->find();

        // 获取分页显示
        $page = $agent_list->render();
        $user = $agent_list->toArray();

        $this->assign("agent_money", $agent_money);
        $this->assign("page", $page);
        $this->assign("data", session("extension"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }
    /**
    * 员工统计管理
     */
    public function staff(){
        $data =[];
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('date') and !$this->request->param('agent_company') and !$this->request->param('uid') and !$this->request->param('start_time') and !$this->request->param('agent_id') and !$this->request->param('end_time')) {
            $data['date'] ='day';
            $data['start_time'] ='';
            $data['end_time']  = '';
            $data['uid']  = '';
            session("staff", $data);
        } else if (empty($p)) {
            $data['date'] = $this->request->param('date');
            $data['uid'] = $this->request->param('uid');
            $data['agent_company'] = $this->request->param('agent_company');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');

            session("staff", $data);
        }

        $start_time = session("staff.start_time") ? session("staff.start_time") : '';
        $end_time = session("staff.end_time") ? session("staff.end_time") : '';

        $agent_staff = intval(session("staff.uid"));
        $agent_company = intval(session("staff.agent_company"));
        $date = session("staff.date");

        $where = "a.agent_level=2";

        $where .= $agent_staff ? " and s.uid=".$agent_staff : '';
        $where .= $agent_company ? " and s.agent_company=".$agent_company : '';
        // 按日统计或按月统计
        if ($date == 'month') {
            $where .= $end_time ? " and s.month_time <='" . date('Ym',strtotime($end_time)) . "'" : '';
            $where .= $start_time ? " and s.month_time >='" . date('Ym',strtotime($start_time)) . "'" : '';
            $group = "s.uid,s.month_time";
        }else{
            $where .= $end_time ? " and s.date_time <='" . $end_time . "'" : '';
            $where .= $start_time ? " and s.date_time >='" . $start_time . "'" : '';
            $group = "s.uid,s.date_time";
        }

        $filed ="sum(s.consumption) as consumption,s.uid,a.agent_login,g.agent_company,g.agent_login as glogin,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum,s.date_time,s.month_time";

        $agent_list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->join("agent g","g.id = a.agent_company")
            ->field($filed.",s.date_time,s.month_time,a.login_name,s.uid")
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);

        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum";

        $agent_money = Db::name('agent_statistical')->alias("s")->join("agent a","a.id = s.uid")->field($filed)->where($where)->find();

        // 获取分页显示
        $page = $agent_list->render();
        $user = $agent_list->toArray();

        $this->assign("agent_money", $agent_money);
        $this->assign("page", $page);
        $this->assign("data", session("staff"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }
    /**
     *  公司统计管理
     */
    public function statistics(){

        $data =[];
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('date') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['start_time'] ='';
            $data['end_time']  = '';
            $data['uid']  = '';
            $data['date'] ='day';
            session("conversion", $data);
        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['date'] = $this->request->param('date');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');

            session("conversion", $data);
        }
        $start_time = session("conversion.start_time");
        $end_time = session("conversion.end_time");
        $uid = intval(session("conversion.uid"));
        $date = session("conversion.date");

        $where = "s.id > 0";
        $where .= $uid ? " and s.uid =".$uid : '';

        // 按日统计或按月统计
        if ($date == 'month') {
            $where .= $end_time ? " and s.month_time <='" . date('Ym',strtotime($end_time)) . "'" : '';
            $where .= $start_time ? " and s.month_time >='" . date('Ym',strtotime($start_time)) . "'" : '';
            $group = "s.uid,s.month_time";
        }else{
            $where .= $end_time ? " and s.date_time <='" . $end_time . "'" : '';
            $where .= $start_time ? " and s.date_time >='" . $start_time . "'" : '';
            $group = "s.uid,s.date_time";
        }

        $filed ="sum(s.consumption) as consumption,s.uid,a.agent_login,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum,s.date_time,s.month_time";
        $agent_list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->field($filed)
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);

        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum";

        $agent_money = Db::name('agent_statistical')->alias("s")->field( $filed)->where($where)->find();

        // 获取分页显示
        $page = $agent_list->render();
        $user = $agent_list->toArray();

        $this->assign("page", $page);
        $this->assign("agent_money", $agent_money);
        $this->assign("data", session("conversion"));
        $this->assign("users", $user['data']);

        return $this->fetch();
    }
    /**
    * 获取公司下的员工和推广人列表
     */
    public function promoter(){
        //搜索条件
        $p = $this->request->param('page');
        if (empty($p)and !$this->request->param('voice_id') and !$this->request->param('host_id') and !$this->request->param('agent_login') and !$this->request->param('agent_company') and !$this->request->param('agent_staff') and !$this->request->param('starttime') and !$this->request->param('id') and !$this->request->param('endtime')) {
            session("admin_agent", null);
            $data['starttime'] = '';
            $data['endtime'] = '';
            $data['status'] = $this->request->param('status') >='0' ? $this->request->param('status') : '-1';
            session("admin_agent", $data);
        } else if (empty($p)) {
            $data['voice_id'] = $this->request->param('voice_id');
            $data['host_id'] = $this->request->param('host_id');
            $data['agent_login'] = $this->request->param('agent_login');
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['agent_company'] = $this->request->param('agent_company');
            $data['id'] = $this->request->param('id');
            $data['starttime'] = $this->request->param('starttime');
            $data['endtime'] = $this->request->param('endtime');
            $data['status'] =$this->request->param('status') >='0' ? $this->request->param('status') : '-1';
            session("admin_agent", $data);
        }

        $user_login = session("admin_agent.agent_login");
        $id = intval(session("admin_agent.id"));
        $voice_id = intval(session("admin_agent.voice_id"));
        $host_id = intval(session("admin_agent.host_id"));
        $agent_staff = intval(session("admin_agent.agent_staff"));
        $agent_company = intval(session("admin_agent.agent_company"));
        $starttime = session("admin_registered.starttime") ? strtotime(session("admin_registered.starttime")) : '';
        $endtime = session("admin_registered.endtime") ? strtotime(session("admin_registered.endtime") . " 23:59:59") : '';
        $status = session("admin_agent.status");

        $where = "a.id >0";
        $where .= $id ? " and a.id=".$id : '';
        $where .= $agent_staff ? " and a.agent_staff=".$agent_staff : '';
        $where .= $agent_company ? " and a.agent_company=".$agent_company : '';
        $where .= $user_login ? " and (a.agent_login like '%". $user_login."%' or a.login_name like '%". $user_login."%')" : '';
        $where .= $status != '-1' ? " and a.status=".$status : '';
        $where .= $starttime ?  " and u.create_time >= ".$starttime : '';
        $where .= $endtime ?  " and u.create_time <= ".$endtime : '';
        $where .= $voice_id ? " and a.voice_id=".$voice_id : '';
        $where .= $host_id ? " and a.host_id=".$host_id : '';

        $agent_result = Db::name('agent')->alias("a")
            ->field('a.*,c.login_name as cname,s.login_name as sname')
            ->join('agent c','c.id = a.agent_company','left')
            ->join('agent s','s.id = a.agent_staff','left')
            ->where($where)
            ->order("a.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $agent_result->render();
        $agent_list_array = $agent_result->toArray()['data'];
        foreach ($agent_list_array as &$v){
            // 获取注册总数
            $v['register'] =Db::name('user')-> where("link_id=".$v['id'])->count();
            $v['recharge_coin'] =Db::name('agent_order_log')-> where("type=0 and agent_id=".$v['id'])->sum("money");
        }

        // 获取总人数
        $number =Db::name('agent')->alias("a")-> where($where)->count();

        $this->assign("number", $number);
        $this->assign("page", $page);
        $this->assign("users", $agent_list_array);
        $this->assign("data", session("admin_agent"));
        return $this->fetch();
    }
    /**
     * 渠道账号管理  --公司
     */
    public function index()
    {
        //搜索条件
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('agent_login') and !$this->request->param('agent_id')) {
            session("admin_agent", null);
            $data['status'] = $this->request->param('status') >='0' ? $this->request->param('status') : '-1';
            session("admin_agent", $data);
        } else if (empty($p)) {
            $data['agent_login'] = $this->request->param('agent_login');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['status'] =$this->request->param('status') >='0' ? $this->request->param('status') : '-1';
            session("admin_agent", $data);
        }
        $user_login = session("admin_agent.agent_login");
        $agentid = intval(session("admin_agent.agent_id"));
        $status = intval(session("admin_agent.status"));

        $where = "a.agent_level = 1";
        if ($user_login) {
            $where .= " and (a.agent_login like '%". $user_login."%' or a.login_name like '%". $user_login."%')";
        }
        if ($status != '-1') {
            $where .= " and a.status=". $status;
        }
        if ($agentid) {
            $where .= " and a.id=". $agentid;
        }
        $agent_result = Db::name('agent')->alias("a")
            ->field('a.*')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $agent_result->render();
        $agent_list_array = $agent_result->toArray()['data'];
        foreach ($agent_list_array as &$v){
            // 获取员工总数
            $v['staff'] =Db::name('agent')-> where("agent_company=".$v['id']." and agent_level=2")->count();
            // 获取推广人总数
            $v['promoter'] =Db::name('agent')-> where("agent_company=".$v['id']." and agent_level=3")->count();
        }

        $this->assign("page", $page);
        $this->assign("users", $agent_list_array);
        $this->assign("data", session("admin_agent"));
        return $this->fetch();
    }
    /**
     * 账号编辑
     */
    public function edit()
    {

        $id = $this->request->param('id', 0, 'intval');
        if($id){
            $list = Db::name('agent')->where(["id" => $id])->find();
            if($list){
                $agent = Db::name('agent')->where(["id" => $list['agent_company']])->find();
                if(!$agent){
                    $agent=array(
                        'commission'=>1,
                        'agent_level'=>1,
                    );
                }
            }
        }else{
            $list=array(
                'id'=>0,
                'min_commission' =>0,
                'status'=>1,
                'agent_level'=>1,
                'commission'=>1,
                'is_ban_all'=> 0,
                'is_empty_user'=> 0,
            );
            $agent=array(
                'commission'=>1,
                'agent_level'=>1,
            );
        }

        $agent_sum=array();
        $agent_one=array();

        for ($i=3; $i >= $agent['agent_level'];$i--){
            $agent_one['id'] = $i;
            $agent_one['name'] = lang('agent_level3');
            if ($i == 2) {
                $agent_one['name'] = lang('agent_level2');
            }elseif ($i == 1){
                $agent_one['name'] = lang('agent_level1');
            }
            $agent_sum[] = $agent_one;
        }

        $this->assign("list",$list);
        $this->assign("agent_sum", $agent_sum);
        $this->assign("user", $agent);
        $this->assign("channel_level_sum", 2);

        return $this->fetch();
    }
    /**
     * 账号编辑提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $id = $_POST['id'];
            $login = $_POST['agent_login'];
            $where="agent_login ='$login'";
            $where.=$id ? " and id !=".$id:'';
            if(empty($login)) {
                $this->error(lang("请输入登录账号"));
            }
            $user = DB::name('agent')->where($where)->find();
            if ($user) {
                $this->error(lang("操作失败,账号已存在！"));
            }

            $agent_name = $_POST['agent_name'];
            if(empty($agent_name)) {
                $this->error(lang("请输入账号昵称"));
            }
            $is_ban_all = 0;
            $ban_endtime = 0;
            if (intval($_POST['status']) == 0){
                $is_ban_all = 1;
                $ban_endtime = time();
            }elseif (intval($_POST['status']) == 2){
                $is_ban_all = 2;
                $ban_endtime = time();
            }
            $data=array(
                'agent_login' =>$login,
                'status' =>intval($_POST['status']) == 1 ? 1 : 0,
                'commission'=>$_POST['commission'],
                'agent_level'=>$_POST['agent_level'],
                'mobile'=>$_POST['mobile'],
                'remarks'=>$_POST['remarks'],
                'login_name' => $_POST['agent_name'],
                'is_ban_all' => $is_ban_all,
            );
            if ($ban_endtime) {
                $data['ban_endtime'] = $ban_endtime;
            }
            if ($is_ban_all == 0) {
                $data['is_empty_user'] = 0;
            }
            // agent_company
            if($id){
                if($data['agent_level'] == 1) { // 公司
                    $data['agent_company'] = $id;
                    $data['agent_staff'] = 0;
                }elseif($data['agent_level'] == 2){
                    $data['agent_company'] = intval($_POST['superior_id']);
                    $data['agent_staff'] = $id;
                }
                if ($_POST['agent_pass']) {
                    $data['agent_pass'] = cmf_password($_POST['agent_pass']);
                }
                $result = Db::name('agent')->where("id=".$id)->update($data);
                if($result && $is_ban_all == 2 && $user['agent_level'] < 3){
                    $save_where = "";
                    if($user['agent_level'] == 1){
                        $save_where= "agent_company=".$id;
                    }elseif($user['agent_level'] == 2){
                        $save_where= "agent_staff=".$id;
                    }
                    if($save_where){
                        // 关闭旗下的所有账号
                        Db::name('agent')->where($save_where)->update(['status'=>0,'ban_endtime'=>$ban_endtime]);
                    }
                }
            }else{
                if (!$_POST['agent_pass']) {
                    $this->error(lang("请输入登录渠道账户密码！"));
                }
                $data['agent_pass'] = cmf_password($_POST['agent_pass']);
                $data['addtime'] = time();

                // 是否自动生成推广码
                $link=rand_str_number(4);
                $where_link="channel='".$link."'";
                $where_link.=$id ? " and id !=".$id:'';
                $link_status = Db::name('agent')->where($where_link)->find();
                if($link_status){
                    $link= rand_str_number(4);
                }

                $data['channel']=$link;
                $result = DB::name('agent')->insertGetId($data);
                if($data['agent_level'] == 1) { // 公司
                    $save['agent_company'] = $result;
                    $save['agent_staff'] = 0;
                    Db::name('agent')->where("id=".$result)->update($save);
                }elseif($data['agent_level'] == 2){
                    $save['agent_company'] = intval($_POST['superior_id']);
                    $save['agent_staff'] = $result;
                    Db::name('agent')->where("id=".$result)->update($save);
                }
                $datas = array(
                    'agent_id' => $result,
                    'mobile' => $data['mobile'],
                );
                Db::name('agent_information')->insertGetId($datas);
            }
            if ($result !== false) {
                $this->success(lang('Saved_successfully'), url("agent/index"));
            } else {
                $this->error(lang('Save_failed'));
            }
        }
    }
    /**
    * 清空绑定的代理数据
     */
    public function clear_empty_user(){
        $result = ['code'=>0,'msg'=>''];
        $id = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        $is_empty_user = $status == 1 ? 1 : 2;
        $empty_endtime = time();
        $where = "id=".$id;
        $user = DB::name('agent')->where($where)->find();
        if (!$user){
            $result['msg'] = lang("数据不存在");
            echo json_encode($result);exit;
        }
        $update = array(
            'is_empty_user' =>$is_empty_user,
            'empty_endtime' => $empty_endtime
        );
        Db::name('agent')->where("id=".$id)->update($update);
        if ($is_empty_user == 1){
            // 清空当前绑定
            Db::name('user')->where("link_id=".$id)->update(['link_id'=>'']);
            Db::name('agent_register')->where("agent_id=".$id)->update(['status'=>2,'updtime'=>time()]);
        }else{
            // 清空所有绑定
            if($user['agent_level'] == 1) { // 公司
                $where="a.agent_company=".$id;
            }elseif($user['agent_level'] == 2){
                $where="a.agent_staff=".$id;
            }else{
                $where="a.id=".$id;
            }
            DB::name('agent')->alias("a")
                ->join("user u","u.link_id =a.id")
                ->where($where)
                ->update(['link_id'=>'']);
            DB::name('agent')->alias("a")
                ->join("agent_register r","r.agent_id =a.id")
                ->where($where)
                ->update(['status'=>2,'updtime'=>time()]);
        }
        $result['code'] = 1;
        $result['msg'] = lang("操作成功");
        echo json_encode($result);exit;
    }
    /**
     *  渠道代理收益列表
     * */
    public function earnings()
    {
        //搜索条件
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('order_id') and !$this->request->param('agent_company') and !$this->request->param('agent_staff') and !$this->request->param('start_time') and !$this->request->param('agent_id') and !$this->request->param('end_time') and !$this->request->param('user_id')) {
            session("admin_agent", null);
            $data['start_time'] = '';
            $data['end_time'] = '';
            session("admin_agent", $data);
        } else if (empty($p)) {
            $data['order_id'] = $this->request->param('order_id');
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['agent_company'] = $this->request->param('agent_company');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['user_id'] = $this->request->param('user_id');

            session("admin_agent", $data);
        }
        $user_id = intval(session("admin_agent.user_id"));
        $start_time = session("admin_agent.start_time") ? strtotime(session("admin_agent.start_time")) : '';
        $end_time = session("admin_agent.end_time") ? strtotime(session("admin_agent.end_time") . " 23:59:59") : '';
        $order_id = session("admin_agent.order_id");
        $agent_staff = intval(session("admin_agent.agent_staff"));
        $agent_company = intval(session("admin_agent.agent_company"));
        $agent_id = intval(session("admin_agent.agent_id"));

        $where = "l.id > 0";
        $where .= $user_id ? " and l.uid=".$user_id : '';
        $where .= $agent_staff ? " and a.agent_staff=".$agent_staff : '';
        $where .= $agent_company ? " and a.agent_company=".$agent_company : '';
        $where .= $agent_id ? " and l.agent_id=".$agent_id : '';
        $where .= $order_id ?  " and l.order_id like '%".$order_id."%'" : '';
        $where .= $start_time ?  " and l.addtime >= ".$start_time : '';
        $where .= $end_time ?  " and l.addtime <= ".$end_time : '';

        $agent_result = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->join('agent c','c.id = a.agent_company','left')
            ->join('agent s','s.id = a.agent_staff','left')
            ->field("l.*,a.login_name,u.user_nickname,c.login_name as cname,s.login_name as sname,u.create_time,a.agent_company as agent_company,a.agent_staff as agent_staff")
            ->join("user u", "u.id=l.uid")
            ->order("l.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $agent_result->render();
        $agent_list_array = $agent_result->toArray();
        $order['agent_money'] = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->sum("l.agent_money");
        $order['money'] = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->sum( 'l.money');
        $order['money']  = round($order['money'],2);
        $this->assign("page", $page);
        $this->assign("money", $order);
        $this->assign("users", $agent_list_array['data']);
        $this->assign("data", session("admin_agent"));
        return $this->fetch();
    }

    //提交代理
    public function addpost()
    {
        if ($this->request->isPost()) {
            $login = $_POST['agent_login'];
            $user = DB::name('agent')->where("agent_login ='$login'")->find();
            if ($user) {
                $this->error(lang('Failed_to_add_account_already_exists'));
            }
            $_POST['agent_pass'] = cmf_password($_POST['agent_pass']);

            $_POST['agent_level'] = '1';

            $_POST['addtime'] = time();
            $_POST['channel_agent_link'] = $_POST['channel'] . "_" . $_POST['channel'];
            $_POST['channel_agent'] = $_POST['channel'];

            $result = DB::name('agent')->insertGetId($_POST);

            $datas = array(
                'agent_id' => $result,
            );

            $data = array(
                'uid' => $result,
                'channel' => $_POST['channel'],
                'channel_agent' => $_POST['channel'],
                'money' => '0',
                'registered' => '0',
                'data_time' => date('Y-m-d'),
                'commission' => $_POST['commission'],
                'agent_earnings' => 0,
                'addtime' => time(),
            );

            DB::name('agent_statistical')->insert($data);
            DB::name('agent_information')->insertGetId($datas);
            if ($result !== false) {
                $this->success(lang('ADD_SUCCESS'), url("agent/index"));
            } else {
                $this->error(lang('ADD_FAILED'));
            }
        }
    }

    /**
     * 删除账号
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');

        if (Db::name('agent')->delete($id) !== false) {
            $this->success(lang('DELETE_SUCCESS'));
        } else {
            $this->error(lang('DELETE_FAILED'));
        }
    }

    /**
     * 停用账号
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('agent')->where(["id" => $id])->setField('status', '0');
            if ($result !== false) {
                $this->success(lang('Seal_successful'), url("agent/index"));
            } else {
                $this->error(lang('Seal_failed'));
            }
        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    /**
     * 启用账号
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('agent')->where(["id" => $id])->setField('status', '1');
            if ($result !== false) {
                $this->success(lang('Unsealing_succeeded'), url("agent/index"));
            } else {
                $this->error(lang('Unsealing_failed'));
            }
        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    /*
     * 代理提现记录
     *
    */
    public function withdrawal()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('agent_id')) {
            session("admin_withdrawal", null);
        } else if (empty($p)) {

            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['agent_id'] = $this->request->param('agent_id');
            session("admin_withdrawal", $data);
        }

        $start_times = session("admin_withdrawal.start_time");
        $end_times = session("admin_withdrawal.end_time");
        $agentid = session("admin_withdrawal.agent_id");


        $start_time = $start_times ? strtotime($start_times) : '0';

        $end_time = $end_times ? strtotime($end_times) : time();
        $where = [];
        $where['a.addtime'] = array('between', array($start_time, $end_time));

        if ($agentid) {
            $where['u.id'] = $agentid;
        }

        $withdrawal = Db::name('agent_withdrawal')->alias("a")
            ->join("agent u", "u.id=a.agent_id")
            ->where($where)
            ->field("u.agent_login,a.*")
            ->order("a.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);
        $sum = Db::name('agent_withdrawal')->alias("a")
            ->join("agent u", "u.id=a.agent_id")
            ->where($where)
            ->sum("money");
        $page = $withdrawal->render();
        $name = $withdrawal->toArray();

        $this->assign("sum", $sum);
        $this->assign("page", $page);
        $this->assign("list", $name['data']);
        $this->assign("data", session("admin_withdrawal"));
        return $this->fetch();
    }

    //修改提现状态
    public function addwithdrawal()
    {
        $root=array('status'=>0,'error'=>'操作失败!');
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $remarks = $this->request->param('remarks');
        $data = array(
            'status' => $status,
            'remarks' => $remarks,
            'updatetime' => time(),
        );
        $result = Db::name('agent_withdrawal')->where("id=$id")->update($data);
        if ($result) {
            if($status==2){
                $agent_info = Db::name('agent_withdrawal')->where("id=$id")->find();
                //返还金额
                db('agent')
                    ->where('id = '.$agent_info['agent_id'])
                    ->inc('income',$agent_info['income'])
                    ->update();
            }

            $root=array('status'=>1,'error'=>lang('Operation_successful'));
        }
        echo json_encode($root);exit;
    }

    //注册详情
    public function registered()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('channel') and !$this->request->param('agent_company') and !$this->request->param('agent_staff') and !$this->request->param('starttime') and !$this->request->param('link_id') and !$this->request->param('endtime') and !$this->request->param('user_id')) {
            session("admin_registered", null);
            $data['starttime'] = '';
            $data['endtime'] = '';
            session("admin_registered", $data);
        } else if (empty($p)) {
            $data['channel'] = $this->request->param('channel');
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['agent_company'] = $this->request->param('agent_company');
            $data['link_id'] = $this->request->param('link_id');
            $data['starttime'] = $this->request->param('starttime');
            $data['endtime'] = $this->request->param('endtime');
            $data['user_id'] = $this->request->param('user_id');

            session("admin_registered", $data);
        }

        $link_id = intval(session("admin_registered.link_id"));
        $channel = session("admin_registered.channel");
        $agent_staff = intval(session("admin_registered.agent_staff"));
        $agent_company = intval(session("admin_registered.agent_company"));
        $user_id = intval(session("admin_registered.user_id"));
        $starttime = session("admin_registered.starttime") ? strtotime(session("admin_registered.starttime")) : '';
        $endtime = session("admin_registered.endtime") ? strtotime(session("admin_registered.endtime") . " 23:59:59") : '';
        $where = "u.link_id > 0";
        $where .= $link_id ? " and u.link_id=".$link_id : '';
        $where .= $agent_staff ? " and a.agent_staff=".$agent_staff : '';
        $where .= $agent_company ? " and a.agent_company=".$agent_company : '';
        $where .= $channel ? " and a.channel like'%".$channel."%'" : '';

        $where .= $user_id ? " and u.id=".$user_id : '';
        $where .= $starttime ?  " and u.create_time >= ".$starttime : '';
        $where .= $endtime ?  " and u.create_time <= ".$endtime : '';

        //注册列表
        $list = Db::name('user')->alias("u")
            ->join('agent a','a.id = u.link_id')
            ->join('agent c','c.id = a.agent_company','left')
            ->join('agent s','s.id = a.agent_staff','left')
            ->field("a.login_name,a.id,u.user_nickname,u.last_login_time,u.brand,u.model,u.public_ip,u.intranet_ip,u.register_device,u.id as uid,c.login_name as cname,s.login_name as sname,u.create_time,a.channel,a.agent_staff,a.agent_company")
            ->where($where)
            ->order("u.create_time desc")
            ->paginate(20, false, ['query' => request()->param()]);
        $number = Db::name('user')->alias("u") ->join('agent a','a.id = u.link_id')->where($where)->count();
        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray();

        $this->assign("page", $page);
        $this->assign("number", $number);
        $this->assign("data", session("admin_registered"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }
    /*
     *  代理详情
     * */
    public function information()
    {
        $id = $this->request->param('id');
        $user_name = Db::name('agent')->field("agent_login")->where("id=$id")->find();
        if ($user_name) {
            $user = Db::name('agent_information')->where("agent_id=$id")->find();
            $result = $user;

        }
        $result['agent_login'] = $user_name['agent_login'];
        //  var_dump($id);exit;
        $this->assign("users", $result);
        return $this->fetch();
    }

    //渠道绑定用户
    public function add_invitation()
    {
        $id = input('param.id', 0, 'intval');
        $user_id = intval(input('param.user_id'));
        $link = Db::name('agent')->field("channel_agent_link")->where("id=$id")->find();
        $user = Db::name('user')->where("id=$user_id")->find();
        if ($user) {
            if (empty($user['link_id'])) {
                if (Db::name('user')->where("id='$user_id'")->update(['link_id' => $link['channel_agent_link']])) {
                    $data = array(
                        'user_id' => $user_id,
                        'admin_id' => cmf_get_current_admin_id(),
                        'content' => $user_id . '添加渠道' . $link['channel_agent_link'],
                        'create_time' => time(),
                    );
                    Db::name('user_invitation_log')->insert($data);
                    $this->success(lang('Operation_successful'));
                } else {
                    $this->error(lang('operation_failed'));
                }
            } else {
                $this->error(lang('operation_failed'));
            }

        } else {
            $this->error(lang('user_does_not_exist'));
        }
    }
    /**
    * 导出充值记录
     */
    public function export_earnings(){
        //搜索条件
        $order_id = $this->request->param('order_id');
        $agent_staff = $this->request->param('agent_staff');
        $agent_company = $this->request->param('agent_company');
        $agent_id = $this->request->param('agent_id');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        $user_id = $this->request->param('user_id');

        $where = "l.id > 0";
        $where .= $user_id ? " and l.uid=".$user_id : '';
        $where .= $agent_staff ? " and a.agent_staff=".$agent_staff : '';
        $where .= $agent_company ? " and a.agent_company=".$agent_company : '';
        $where .= $agent_id ? " and l.agent_id=".$agent_id : '';
        $where .= $order_id ?  " and l.order_id like '%".$order_id."%'" : '';
        $where .= $start_time ?  " and l.addtime >= ".$start_time : '';
        $where .= $end_time ?  " and l.addtime <= ".$end_time : '';

        $agent_result = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->join('agent c','c.id = a.agent_company','left')
            ->join('agent s','s.id = a.agent_staff','left')
            ->join('pay_menu p', 'l.pay_type_id=p.id', 'LEFT')
            ->join("user u", "u.id=l.uid")
            ->field("l.*,p.pay_name,a.login_name,u.user_nickname,c.login_name as cname,s.login_name as sname,u.create_time,a.agent_company as agent_company,a.agent_staff as agent_staff")
            ->order("l.addtime DESC")
            ->select();

        $title = '充值明细记录';
        $lists = $agent_result->toArray();
        if ($lists != null) {
            $dataResult = array();
            foreach ($lists as $k=>$v) {
                $dataResult[$k]['user_nickname'] = $v['user_nickname'];
                $dataResult[$k]['uid'] = $v['uid'];
                $dataResult[$k]['login_name'] = $v['login_name'];
                $dataResult[$k]['agent_id'] = $v['agent_id'];
                $dataResult[$k]['cname'] = $v['cname'] ? $v['cname'] : '';
                $dataResult[$k]['agent_company'] = $v['agent_company'] ? $v['agent_company'] : '';
                $dataResult[$k]['pay_name'] = $v['pay_name'] ? $v['pay_name'] : '0';
                $dataResult[$k]['money'] = $v['money'] ? $v['money'] : '0';
                $dataResult[$k]['order_id'] = $v['order_id'] ? $v['order_id'] : '';
                $dataResult[$k]['addtime'] = $v['addtime'] ? date('Y-m-d h:i', $v['addtime']) : '';
                $dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : '';
            }

            $str = lang("充值用户,用户ID,邀请渠道,邀请渠道ID,").lang('agent_level1').",".lang('agent_level1').lang("渠道ID,充值方式,充值金额,订单号,收益时间,注册时间");
            $this->excelData($dataResult, $str, $title);
            exit();
        }else{
            $this->error(lang('No_data'));
        }

    }
    /**
    * 导出注册记录
     */
    public function export_registered(){

        $channel = $this->request->param('channel');
        $agent_staff = $this->request->param('agent_staff');
        $agent_company = $this->request->param('agent_company');
        $link_id = $this->request->param('link_id');
        $starttime = $this->request->param('starttime');
        $endtime = $this->request->param('endtime');
        $user_id = $this->request->param('user_id');

        $where = "u.link_id > 0";
        $where .= $link_id ? " and u.link_id=".$link_id : '';
        $where .= $agent_staff ? " and a.agent_staff=".$agent_staff : '';
        $where .= $agent_company ? " and a.agent_company=".$agent_company : '';
        $where .= $channel ? " and a.channel like'%".$channel."%'" : '';

        $where .= $user_id ? " and u.id=".$user_id : '';
        $where .= $starttime ?  " and u.create_time >= ".$starttime : '';
        $where .= $endtime ?  " and u.create_time <= ".$endtime : '';

        //注册列表
        $list = Db::name('user')->alias("u")
            ->join('agent a','a.id = u.link_id')
            ->join('agent c','c.id = a.agent_company','left')
            ->join('agent s','s.id = a.agent_staff','left')
            ->field("a.login_name,a.id,u.user_nickname,u.id as uid,c.login_name as cname,s.login_name as sname,u.create_time,a.channel,a.agent_staff,a.agent_company,u.brand,u.model,u.intranet_ip,u.public_ip,u.register_device")
            ->where($where)
            ->order("u.create_time desc")
            ->select();

        $title = '注册明细记录';
        $lists = $list->toArray();
        if ($lists != null) {
            $dataResult = array();
            foreach ($lists as $k=>$v) {
                $dataResult[$k]['user_nickname'] = $v['user_nickname'];
                $dataResult[$k]['uid'] = $v['uid'];
                $dataResult[$k]['login_name'] = $v['login_name'];
                $dataResult[$k]['id'] = $v['id'];
                $dataResult[$k]['cname'] = $v['cname'] ? $v['cname'] : '';
                $dataResult[$k]['agent_company'] = $v['agent_company'] ? $v['agent_company'] : '';
                $dataResult[$k]['brand'] = $v['brand'];
                $dataResult[$k]['model'] = $v['model'];
                $dataResult[$k]['intranet_ip'] = $v['intranet_ip'];
                $dataResult[$k]['public_ip'] = $v['public_ip'];
                $dataResult[$k]['register_device'] = $v['register_device'];
                $dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : lang('No_information');
            }
            $str = lang("注册用户,注册ID,邀请渠道,邀请渠道ID,").lang('agent_level1').",".lang('agent_level1').lang("渠道ID,手机厂商,注册机型,注册IP(终端ip),注册IP(公网),注册设备号,注册时间");
            $this->excelData($dataResult, $str, $title);
            exit();
        }else{
            $this->error(lang('No_data'));
        }
    }
    /**
    * 导出公司统计表记录
     */
    public function export_statistics(){
        /**搜索条件**/
        $uid = $this->request->param('uid');
        $date = $this->request->param('date');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');

        $where = "s.id > 0";
        $where .= $uid ? " and s.uid =".$uid : '';
        // 按日统计或按月统计
        if ($date == 'month') {
            $where .= $end_time ? " and s.month_time <='" . date('Ym',strtotime($end_time)) . "'" : '';
            $where .= $start_time ? " and s.month_time >='" . date('Ym',strtotime($start_time)) . "'" : '';
            $group = "s.uid,s.month_time";
        }else{
            $where .= $end_time ? " and s.date_time <='" . $end_time . "'" : '';
            $where .= $start_time ? " and s.date_time >='" . $start_time . "'" : '';
            $group = "s.uid,s.date_time";
        }

        $filed ="sum(s.consumption) as consumption,s.uid,a.agent_login,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum,s.date_time,s.month_time";
        $agent_list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->field($filed)
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->select();

        $title = lang('agent_level1').'统计记录';
        $lists = $agent_list->toArray();
        if ($lists != null) {
            $dataResult = array();
            foreach ($lists as $k=>$v) {
                $dataResult[$k]['date'] = $date == 'month' ? $v['month_time'] : $v['date_time'];
                $dataResult[$k]['agent_login'] = $v['agent_login'];
                $dataResult[$k]['uid'] = $v['uid'];
                $dataResult[$k]['register_sum'] = $v['register_sum'] ? $v['register_sum'] : '0';
                $dataResult[$k]['money'] = floatval($v['money']) ? floatval($v['money']) : '0';
                $dataResult[$k]['invitation_register_sum'] = $v['invitation_register_sum'] ? $v['invitation_register_sum'] : '0';
                $dataResult[$k]['invitation_money'] = floatval($v['invitation_money']) ? floatval($v['invitation_money']) : '0';
                $dataResult[$k]['consumption'] = $v['consumption'] ? $v['consumption'] : '0';
                $dataResult[$k]['agent_money'] = floatval($v['agent_money']) ? floatval($v['agent_money']) : '0';
            }
            $str = lang("日期,").lang('agent_level1').",".lang('agent_level1')."ID,当前渠道注册新增,当前渠道充值数,下级渠道注册新增,下级渠道充值数,消费总数,总收益";
            $this->excelData($dataResult, $str, $title);
            exit();
        }else{
            $this->error(lang('No_data'));
        }

    }
}
