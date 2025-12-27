<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/21 0021
 * Time: 上午 10:25
 */

namespace app\agent\controller;

use cmf\controller\AdminBaseController;
use QcloudApi;
use think\Db;

class SettlementController extends BaseController
{
    //结算记录
    public function index()
    {
        $id = intval(session('AGENT_ID'));
        $agent = Db::name('agent')->field("income,income_total")->where("id=".$id)->find();
        $config = load_cache('config');
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("Settlement", null);
            $data['status'] = $this->request->param('status');
            session("Settlement", $data);
        } else if (empty($p)) {
            $data['status'] = $this->request->param('status');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("Settlement", $data);
        }

        $start_times = session("Settlement.start_time");
        $end_times = session("Settlement.end_time");
        $status = session("Settlement.status");

        $start_time = $start_times ? strtotime($start_times) : '0';
        $end_time = $end_times ? strtotime($end_times) : time();


        $where = 'addtime >=' . $start_time . ' and addtime <=' . $end_time.' and agent_id='.$id;
        $where .= $status >='0' ? ' and status='.$status : '' ;
        $list = Db::name('agent_withdrawal')->order("addtime desc")->where($where)->paginate(10);
        // 获取分页显示
        $page = $list->render();

        $user = $list->toArray();

        //统计获取的总金额
        $count = Db::name('agent_withdrawal')->where($where)->sum("money");

        $data = array(
            'count' => $count ? $count : '0',
            'end_time' => $end_times,
            'start_time' => $start_times,
            'status' => $status
        );

        $this->assign("page", $page);
        $this->assign("text", $config['channel_notice_text']);
        $this->assign("channel_withdrawal", $config['channel_withdrawal']);
        $this->assign("data", $data);
        $this->assign("users", $user['data']);
        $this->assign("agent", $agent);
        return $this->fetch();
    }
    //统计列表 -- 天统计 -- 公司
    public function conversion()
    {
        $agent = session('AGENT_USER');
        $data =[];
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('date') and !$this->request->param('start_time') and !$this->request->param('end_time')) {

            $data['start_time'] ='';
            $data['end_time']  = '';
            $data['date'] ='day';
            session("conversion", $data);
        } else if (empty($p)) {
            $data['date'] = $this->request->param('date');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');

            session("conversion", $data);
        }

        $start_time = session("conversion.start_time") ? session("conversion.start_time") : '';
        $end_time = session("conversion.end_time") ? session("conversion.end_time") : '';


        $date = session("conversion.date");
        // 公司
        $where = "a.agent_level = 1 and s.uid=" . $agent['id'];

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

        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum,s.date_time,s.month_time";
        $agent_list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->field($filed)
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);

        $filed ="sum(s.consumption) as consumption,sum(s.money + s.invitation_money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum + s.invitation_register_sum) as register_sum";

        $agent_money = Db::name('agent_statistical')->alias("s") ->join("agent a","a.id = s.uid")->field( $filed)->where($where)->find();

        // 获取分页显示
        $page = $agent_list->render();
        $user = $agent_list->toArray();

        $this->assign("agent_money", $agent_money);
        $this->assign("page", $page);
        $this->assign("agent", $agent);
        $this->assign("conversion", session("conversion"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }
    //账号统计  -- 员工
    public function statistics(){
        $agent = session('AGENT_USER');
        $data =[];
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('date') and !$this->request->param('agent_staff') and !$this->request->param('start_time') and !$this->request->param('agent_id') and !$this->request->param('end_time')) {
            $data['date'] ='day';
            $data['start_time'] ='';
            $data['end_time']  = '';
            session("conversion", $data);
        } else if (empty($p)) {
            $data['date'] = $this->request->param('date');
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');

            session("conversion", $data);
        }

        $start_time = session("conversion.start_time") ? session("conversion.start_time") : '';
        $end_time = session("conversion.end_time") ? session("conversion.end_time") : '';

        $agent_staff = intval(session("conversion.agent_staff"));
        $date = session("conversion.date");

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "s.agent_company=" . $agent['id'];
        }else{
            // 员工
            $where = "s.uid=" . $agent['id'];
        }
        $where .= $agent_staff ? " and s.uid=".$agent_staff : '';
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
        $where .= " and a.agent_level=2";

        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum";

        $agent_list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->field($filed.",s.date_time,s.month_time,a.login_name,s.uid")
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);

        $filed ="sum(s.consumption) as consumption,sum(s.money + s.invitation_money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum + s.invitation_register_sum) as register_sum";
        $agent_money = Db::name('agent_statistical')->alias("s")->join("agent a","a.id = s.uid")->field($filed)->where($where)->find();

        // 获取分页显示
        $page = $agent_list->render();
        $user = $agent_list->toArray();

        $this->assign("agent_money", $agent_money);
        $this->assign("page", $page);
        $this->assign("agent", $agent);
        $this->assign("conversion", session("conversion"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }
    //账号统计  -- 推广员
    public function subordinate(){

        $agent = session('AGENT_USER');
        $data =[];
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('date') and !$this->request->param('agent_id') and !$this->request->param('agent_staff') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['date'] ='day';
            $data['start_time'] ='';
            $data['end_time']  = '';
            session("conversion", $data);
        } else if (empty($p)) {
            $data['date'] = $this->request->param('date');
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');

            session("conversion", $data);
        }

        $start_time = session("conversion.start_time") ? session("conversion.start_time") : '';
        $end_time = session("conversion.end_time") ? session("conversion.end_time") : '';

        $agent_staff = intval(session("conversion.agent_staff"));
        $agent_id = intval(session("conversion.agent_id"));
        $date = session("conversion.date");

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "s.agent_company=" . $agent['id'];
        }elseif($agent['agent_level'] == 2){
            // 员工
            $where = "s.agent_staff=" . $agent['id'];
        }else{
            $where = "s.uid=" . $agent['id'];
        }
        $where .= $agent_staff ? " and s.agent_staff=".$agent_staff : '';
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
        $where .= " and a.agent_level=3";
        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money) as agent_money,sum(s.register_sum) as register_sum";
        $agent_list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->join("agent g","g.id = s.agent_staff",'left')
            ->field($filed.",s.date_time,s.month_time,a.login_name,s.uid,g.login_name as gname,s.agent_staff")
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);

        $agent_money = Db::name('agent_statistical')->alias("s")->join("agent a","a.id = s.uid")->field($filed)->where($where)->find();

        // 获取分页显示
        $page = $agent_list->render();
        $user = $agent_list->toArray();

        $this->assign("agent_money", $agent_money);
        $this->assign("page", $page);
        $this->assign("agent", $agent);
        $this->assign("conversion", session("conversion"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }

    //提现
    public function addwithdrawal()
    {
        $root=array('code'=>0,'msg'=>lang('Application_failed'));

        $config = load_cache('config');
        $id = session('AGENT_ID');
        $income = intval($this->request->param('income'));

        $agent_withdrawal = Db::name("agent_withdrawal")->where("agent_id=".$id)->order("addtime desc")->find();
        $agent_information = Db::name("agent_information")->where("agent_id=".$id)->find();

        if((!$agent_information['pay'] || !$agent_information['pay_name'])){
            $root['msg'] = '请绑定账号';
            echo  json_encode($root);exit;
        }
        if($agent_withdrawal && $agent_withdrawal['status'] == 0){
            $root['msg'] = '提现失败,还有未通过审核的提现记录';
            echo  json_encode($root);exit;
        }
        $month = strtotime(date('Y-m'));
        $channel_withdrawal_times = intval($config['channel_withdrawal_times']);
        $agent_withdrawal_times = Db::name("agent_withdrawal")->where("agent_id=".$id." and status !=2 and addtime >=".$month)->count();

        if ($agent_withdrawal_times >= $channel_withdrawal_times) {
            $root['msg'] = '每月最多提现'.$channel_withdrawal_times.'次,请下月重新申请吧!';
            echo  json_encode($root);exit;
        }
        $agent_info = db('agent')->find($id);
        if ($income <= 0) {
            $root['msg'] = '请输入提现金额';
            echo  json_encode($root);exit;
        }
        if ($agent_info['income'] < $income) {
            $root['msg'] = '余额不足';
            echo  json_encode($root);exit;
        }
        // 获取日期内提现
        if (empty($config['channel_withdrawal_day'])) {
            $root['msg'] = '管理员禁止了提现功能';
            echo  json_encode($root);exit;
        }
        $days = explode("-",$config['channel_withdrawal_day']);
        $today = date('d');

        if (count($days) > 1) {
            if ($today < $days[0] || $today > $days[1]) {
                $root['msg'] = '请再'.$days[0]."-".$days[1]."号内提现";
                echo  json_encode($root);exit;
            }
        }else{
            if ($today < $days[0]) {
                $root['msg'] = '请再'.$days[0]."号后提现";
                echo  json_encode($root);exit;
            }
        }

        $channel_withdrawal = floatval($config['channel_withdrawal']);
        if (!$channel_withdrawal) {
            $root['msg'] = '后台禁止提现';
            echo  json_encode($root);exit;
        }
        $money = floor($income / $channel_withdrawal * 100) / 100;

        if (intval($config['channel_withdrawal_max']) < $money) {
            $root['msg'] = '每次提现最大数值上限'.intval($config['channel_withdrawal_max']."美元");
            echo  json_encode($root);exit;
        }
        if (intval($config['channel_withdrawal_min']) > $money) {
            $root['msg'] = '每次提现最低数值下限'.intval($config['channel_withdrawal_min']."美元");
            echo  json_encode($root);exit;
        }

        $data = array(
            'income' => $income,
            'money' => $money,
            'agent_id' => $id,
            'addtime' => time(),
            'status' =>0,
            'pay_name' => $agent_information['pay_name'],
            'pay' => $agent_information['pay'],
            'pay_type' => $agent_information['pay_type'],
        );
        $user = Db::name("agent_withdrawal")->insertGetId($data);
        if ($user) {
            //扣除金额
            db('agent')
                ->where('id = '.$id)
                ->dec('income',$income)
                ->update();
            $root['code'] =1;
            $root['msg'] = '申请成功';
        }
        echo json_encode($root);exit;

    }

    /**
     * 导出 -- 公司
     */
    public function export(){
        $agent = session('AGENT_USER');
        $date = $this->request->param('date');
        $start_time = $this->request->param('start_time') ? $this->request->param('start_time') : '';
        $end_time = $this->request->param('end_time') ? $this->request->param('end_time') : '';

        // 公司
        $where = "agent_company=" . $agent['id'];
        if ($agent['agent_level'] != 1) {
            // 参数错误返回空值
            $where .= " and agent_company=1";
        }

        // 按日统计或按月统计
        if ($date == 'month') {
            $where .= $end_time ? " and month_time <='" . date('Ym',strtotime($end_time)) . "'" : '';
            $where .= $start_time ? " and month_time >='" . date('Ym',strtotime($start_time)) . "'" : '';
            $group = "month_time";
        }else{
            $where .= $end_time ? " and date_time <='" . $end_time . "'" : '';
            $where .= $start_time ? " and date_time >='" . $start_time . "'" : '';
            $group = "date_time";
        }

        $filed ="sum(consumption) as consumption,sum(money) as money,sum(agent_money + invitation_agent_money) as agent_money,sum(register_sum) as register_sum,sum(invitation_money) as invitation_money,sum(invitation_register_sum) as invitation_register_sum,date_time,month_time";
        $list = Db::name('agent_statistical')
            ->field($filed)
            ->where($where)
            ->order("date_time desc")
            ->group($group)
            ->select();

        $title = lang('agent_level1').'流水记录';
        if ($list != null) {
            $dataResult = array();
            foreach ($list as $k=>$v) {
                $dataResult[$k]['time'] = $date == 'day' ? $v['date_time'] : $v['month_time'];
                $dataResult[$k]['register_sum'] = $v['register_sum'] ? $v['register_sum'] : '0';

                $dataResult[$k]['money'] = floatval($v['money']) ? floatval($v['money']) : '0';

                $dataResult[$k]['invitation_register_sum'] = $v['invitation_register_sum'] ? $v['invitation_register_sum'] : '0';

                $dataResult[$k]['invitation_money'] = floatval($v['invitation_money']) ? floatval($v['invitation_money']) : '0';
                $dataResult[$k]['consumption'] = $v['consumption'] ? $v['consumption'] : '0';

                $dataResult[$k]['agent_money'] = floatval($v['agent_money']) ? floatval($v['agent_money']) : '0';
            }
            $str = "日期,当前渠道注册新增数,当前渠道充值数,下级渠道注册新增数,下级渠道充值数,消费总数,虚拟币总收益";
            $this->excelData($dataResult, $str, $title); exit();
        }else{
            $this->error("暂无数据");
        }
    }
    /**
     * 导出 -- 员工
     */
    public function export_statistics(){
        $agent = session('AGENT_USER');

        $date = $this->request->param('date');
        $agent_staff = intval($this->request->param('agent_staff'));
        $start_time = $this->request->param('start_time') ? $this->request->param('start_time') : '';
        $end_time = $this->request->param('end_time') ? $this->request->param('end_time') : '';

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "s.agent_company=" . $agent['id'];
        }else{
            // 员工
            $where = "s.uid=" . $agent['id'];
        }
        $where .= $agent_staff ? " and s.uid=".$agent_staff : '';
        // 按日统计或按月统计
        if ($date == 'month') {
            $where .= $end_time ? " and s.month_time <='" . date('Ym',strtotime($end_time)) . "'" : '';
            $where .= $start_time ? " and s.month_time >='" . date('Ym',strtotime($start_time)) . "'" : '';
            $group = "s.month_time";
        }else{
            $where .= $end_time ? " and s.date_time <='" . $end_time . "'" : '';
            $where .= $start_time ? " and s.date_time >='" . $start_time . "'" : '';
            $group = "s.date_time";
        }
        $where .= " and a.agent_level=2";
        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money + s.invitation_agent_money) as agent_money,sum(s.register_sum) as register_sum,sum(s.invitation_money) as invitation_money,sum(s.invitation_register_sum) as invitation_register_sum";
        $list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->field($filed.",s.date_time,s.month_time,a.login_name,s.uid")
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);

        $title = lang('agent_level2').'流水记录';
        if ($list != null) {
            $dataResult = array();
            foreach ($list as $k=>$v) {
                $dataResult[$k]['time'] = $date == 'day' ? $v['date_time'] : $v['month_time'];
                $dataResult[$k]['uid'] = $v['uid'] ? $v['uid'] : '';
                $dataResult[$k]['login_name'] = $v['login_name'] ? $v['login_name'] : '';
                $dataResult[$k]['register_sum'] = $v['register_sum'] ? $v['register_sum'] : '0';

                $dataResult[$k]['money'] = floatval($v['money']) ? floatval($v['money']) : '0';
                $dataResult[$k]['invitation_register_sum'] = $v['invitation_register_sum'] ? $v['invitation_register_sum'] : '0';

                $dataResult[$k]['invitation_money'] = floatval($v['invitation_money']) ? floatval($v['invitation_money']) : '0';
                $dataResult[$k]['consumption'] = $v['consumption'] ? $v['consumption'] : '0';
            }
            $str = "日期,id,账号昵称,当前渠道注册新增数,当前渠道充值数,下级渠道注册新增数,下级渠道充值数,消费总数";
            $this->excelData($dataResult, $str, $title); exit();
        }else{
            $this->error("暂无数据");
        }
    }
    /**
     * 导出 -- 推广员
     */
    public function export_subordinate(){
        $agent = session('AGENT_USER');

        $start_time = $this->request->param('start_time') ? $this->request->param('start_time') : '';
        $end_time = $this->request->param('end_time') ? $this->request->param('end_time') : '';
        $agent_staff = intval($this->request->param('agent_staff'));
        $agent_id = intval($this->request->param('agent_id'));
        $date = $this->request->param('date');

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "s.agent_company=" . $agent['id'];
        }elseif($agent['agent_level'] == 2){
            // 员工
            $where = "s.agent_staff=" . $agent['id'];
        }else{
            $where = "s.uid=" . $agent['id'];
        }
        $where .= $agent_staff ? " and s.agent_staff=".$agent_staff : '';
        $where .= $agent_id ? " and s.uid=".$agent_id : '';
        // 按日统计或按月统计
        if ($date == 'month') {
            $where .= $end_time ? " and s.month_time <='" . date('Ym',strtotime($end_time)) . "'" : '';
            $where .= $start_time ? " and s.month_time >='" . date('Ym',strtotime($start_time)) . "'" : '';
            $group = "s.month_time";
        }else{
            $where .= $end_time ? " and s.date_time <='" . $end_time . "'" : '';
            $where .= $start_time ? " and s.date_time >='" . $start_time . "'" : '';
            $group = "s.date_time";
        }
        $where .= " and a.agent_level=3";

        $filed ="sum(s.consumption) as consumption,sum(s.money) as money,sum(s.agent_money) as agent_money,sum(s.register_sum) as register_sum";
        $list = Db::name('agent_statistical')->alias("s")
            ->join("agent a","a.id = s.uid")
            ->join("agent g","g.id = s.agent_staff",'left')
            ->field($filed.",s.date_time,s.month_time,a.login_name,s.uid,g.login_name as gname,s.agent_staff")
            ->where($where)
            ->order("s.date_time desc")
            ->group($group)
            ->paginate(10);

        $title = lang('agent_level3').'流水记录';
        if ($list != null) {
            $dataResult = array();
            foreach ($list as $k=>$v) {
                $dataResult[$k]['time'] = $date == 'day' ? $v['date_time'] : $v['month_time'];
                $dataResult[$k]['uid'] = $v['uid'] ? $v['uid'] : '';
                $dataResult[$k]['login_name'] = $v['login_name'] ? $v['login_name'] : '';
                $dataResult[$k]['register_sum'] = $v['register_sum'] ? $v['register_sum'] : '0';

                $dataResult[$k]['money'] = floatval($v['money']) ? floatval($v['money']) : '0';
                $dataResult[$k]['consumption'] = $v['consumption'] ? $v['consumption'] : '0';
                $dataResult[$k]['gname'] = $v['gname'] ? $v['gname'] : '';
            }
            $str = "日期,id,账号昵称,当前渠道注册新增数,当前渠道充值数,消费总数,推荐人";
            $this->excelData($dataResult, $str, $title); exit();
        }else{
            $this->error("暂无数据");
        }
    }
    //获取链接
    public function link()
    {
        $id = session('AGENT_ID');

        $user = Db::name("agent")->where("id=$id")->find();
        $link = $user['channel'];
        //短链接 /share/ 替换了   /agent/public/admin/download/index?agent=
        //正则 rewrite  "^/share/([a-z0-9_]{1,32})$" /agent/public/admin/download/index?agent=$1 last;  伪静态
        $config = load_cache('config');
        if($config['open_cps_url']==1){
            //通用落地页
            $url = $config['cps_url'];
            if(strrpos($url,'?')>0){
                $url .= '&agent='.$link;
            }else{
                $url .= '?agent='.$link;
            }
            $user['link'] = $url;

            $user['qq_link'] = $url;
            $user['wx_link'] = $url;
        }else{
            //通用落地页
            $user['link'] = get_domain() . "/agent/download/index?agent=" . $link;

            $user['qq_link'] = $config['qq_link'] . "/agent/download/index?agent=" . $link;
            $user['wx_link'] = $config['wx_link'] . "/agent/download/index?agent=" . $link;
        }


        $this->assign("user", $user);

        return $this->fetch();
    }
}
