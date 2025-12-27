<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20 0020
 * Time: 上午 10:38
 */

namespace app\agent\controller;

use cmf\controller\AdminBaseController;
use QcloudApi;
use think\Db;

class AgentController extends BaseController
{
    /**
     *   渠道管理
     */
    public function index()
    {
        //搜索条件
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('agent_login') and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('agent_level') and !$this->request->param('channel') and !$this->request->param('agent_id') and !$this->request->param('subordinate_id')) {
            session("agent", null);
            $data['agent_level'] = 0;
            $data['start_time'] =  '';
            $data['end_time'] = '';
            session("agent", $data);
        } else if (empty($p)) {
            $data['agent_level'] = $this->request->param('agent_level');
            $data['user_login'] = $this->request->param('agent_login');
            $data['channel'] = $this->request->param('channel');
            $data['agentid'] = $this->request->param('agent_id');
            $data['subordinate_id'] = $this->request->param('subordinate_id');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("agent", $data);
        }

        $agent_level = intval(session("agent.agent_level"));
        $user_login = session("agent.user_login");
        $channel = session("agent.channel");
        $agentid = intval(session("agent.agentid"));

        $start_times = session("agent.start_time");
        $end_times = session("agent.end_time");

        $start_time = $start_times ? strtotime($start_times) : '0';

        $end_time = $end_times ? strtotime($end_times.' 24:00:00') : time();

        $agent = session('AGENT_USER');

        $where = "addtime >= $start_time and addtime <= $end_time";
        $agent_voice = [];
        $agent_host = [];
        if ($agent['agent_level'] == 1) {
            // 公司
            $where .= " and agent_company=" . $agent['id'];
            // 获取所有的房间id
            $agent_voice = Db::name('agent_voice')->field("voice_id,remarks")->where("status=1 and agent_id=". $agent['id'])->order("update_time desc")->select()->toArray();
            // 获取所有的主播id
            $agent_host = Db::name('agent_host')->field("host_id,remarks")->where("status=1 and agent_id=". $agent['id'])->order("update_time desc")->select()->toArray();
            $agent_voice_0 = array('voice_id'=>0,'remarks'=>'清空');
            $agent_host_0 = array('host_id'=>0,'remarks'=>'清空');
            if ($agent_voice){
                array_unshift($agent_voice,$agent_voice_0);
            }
            if($agent_host){
                array_unshift($agent_host,$agent_host_0);
            }


        }elseif ($agent['agent_level'] == 2){
            // 员工
            $where .= " and agent_staff=" . $agent['id'];
        }else{
            //推广员
            $where .= " and id=" . $agent['id'];
        }

        $where .= $user_login ? " and (agent_login like'%".$user_login."%' or login_name like'%".$user_login."%')" :'';
        $where .= $channel ? " and channel like '%".$channel."%'" :'';
        $where .= $agentid ? ' and id=' . $agentid : '';
        $where .= $agent_level ? ' and agent_level=' . $agent_level : '';

        $list = Db::name('agent')->where($where)->order("id DESC")->paginate(10);

        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray()['data'];
        foreach ($user as &$v){
            // 获取注册总数
            $v['register'] =Db::name('user')-> where("link_id=".$v['id'])->count();
            $v['recharge_coin'] =Db::name('agent_order_log')-> where("type=0 and agent_id=".$v['id'])->sum("money");
        }
        $number = Db::name('agent')->where($where)->count();
        $agent_voice = json_encode($agent_voice);
        $agent_host = json_encode($agent_host);
        $this->assign("page", $page);
        $this->assign("data", session("agent"));
        $this->assign("users", $user);
        $this->assign("number", $number);
        $this->assign("agent", $agent);
        $this->assign("agent_voice", $agent_voice);
        $this->assign("agent_host", $agent_host);
        return $this->fetch();
    }

    /**
     * 账号编辑
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $agent = session('AGENT_USER');
        if ($id) {
            $list = Db::name('agent')->where(["id" => $id])->find();
        }else{
            $list = array(
                'status' => 1,
                'channel' => '',
                'id' => 0,
                'agent_level' => 3,
            );
        }
        $this->assign("list",$list);
        $this->assign("user", $agent);

        return $this->fetch();
    }

    /**
     * 账号编辑提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            // 后台用户
            $agent = session('AGENT_USER');

            $id = $_POST['id'];
            if (empty($_POST['login_name'])) {
                $this->error("请输入账号昵称");exit;
            }
            $data = array(
                'login_name' => $_POST['login_name'],
                'mobile' => $_POST['mobile'],
                'remarks' => $_POST['remarks'],
                'agent_level'=>intval($_POST['agent_level']),
                'status' => intval($_POST['status'])
            );
            // 渠道号码
            if($agent['agent_level']  == 3){
                $this->error(lang('Failed_to_add_no_permission_to_open_account'));
            }
            // 更新上级渠道 1员工的上级是公司 2 推广员上级可能是员工也可能是推广员
            $superior_id = $agent['id'];
            if ($agent['agent_level'] == 1) {
                // 公司操作 == 推广员
                if (isset($_POST['superior_id']) && $_POST['superior_id'] > 0) {
                    // 是否是公司下的员工
                    $superior = Db::name('agent')->where("id=".$_POST['superior_id'])->find();
                    if (!$superior) {
                        $this->error("账号不存在");
                    }
                    if($superior['superior_id'] != $agent['id'] || $superior['agent_company'] != $agent['id']) {
                        $this->error(lang('agent_level2')."ID错误");
                    }
                    $superior_id = $_POST['superior_id'];
                }
                $data['agent_company'] = $agent['id']; // 公司
                if ($data['agent_level'] == 3) { // 推广员
                    $data['agent_staff'] = $superior_id == $agent['id'] ? 0 : $superior_id; // 员工
                }else{
                    // 员工
                    $data['agent_company'] = $agent['id']; // 公司
                }
            }else{
                // 员工操作
                $data['agent_company'] = $agent['agent_company']; // 公司
                $data['agent_staff'] = $agent['id'];
            }

            if ($id) {
                if($data['agent_level'] == 2) { // 员工
                    $data['agent_staff'] = $id;
                }
                if (empty($_POST['agent_pass'])) {
                    unset($_POST['agent_pass']);
                } else {
                    $data['agent_pass'] = cmf_password($_POST['agent_pass']);
                }
                $result = Db::name('agent')->where("id=".$id)->update($data);
            }else{
                $login = $_POST['agent_login'];
                if (empty($login)) {
                    $this->error("请输入登录账号");exit;
                }
                $user = Db::name('agent')->where("agent_login ='$login'")->find();
                if ($user) {
                    $this->error(lang('Failed_to_add_account_already_exists'));exit;
                }
                if (empty($_POST['agent_pass'])) {
                    $this->error("请输入登录密码");exit;
                }
                $data['agent_login'] = $login;
                $data['agent_pass'] = cmf_password($_POST['agent_pass']);
                // 自动生成推广码 渠道号
                $data['channel'] = rand_str_number(4);
                // 1员工 2推广员
                $data['agent_level'] = $data['agent_level'] == 2 ? 2 : 3;
                if($agent['agent_level'] == 3){
                    $this->error(lang('Failed_to_add_no_permission_to_open_account'));
                }
                // 渠道号码
                $data['addtime'] = time();
                $result = DB::name('agent')->insertGetId($data);
                if($data['agent_level'] == 2) { // 员工
                    $save['agent_staff'] = $result;
                    Db::name('agent')->where("id=".$result)->update($save);
                }
                $information = array(
                    'agent_id' => $result,
                    'mobile' => $data['mobile']
                );
                Db::name('agent_information')->insertGetId($information);
            }
            if ($result !== false) {
                $this->success(lang('Saved_successfully'));
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

        if (Db::name('agent')->delete($id) !== false) {
            $this->success(lang('Deleted_successfully'));
        } else {
            $this->error(lang('Delete_failed'));
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

    /**
     * 用户充值列表
     */
    public function userindex()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('order_id') and !$this->request->param('agent_staff') and !$this->request->param('start_time') and !$this->request->param('agent_id') and !$this->request->param('end_time') and !$this->request->param('uid')) {
            session("userindex", null);
            $data['start_time'] = '';
            $data['end_time'] = '';
            session("userindex", $data);
        } else if (empty($p)) {
            $data['order_id'] = $this->request->param('order_id');
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['uid'] = $this->request->param('uid');
            session("userindex", $data);
        }
        $uid = intval(session("userindex.uid"));
        $start_times = session("userindex.start_time");
        $end_times = session("userindex.end_time");
        $order_id = session("userindex.order_id");
        $agent_staff = intval(session("userindex.agent_staff"));
        $agent_id = intval(session("userindex.agent_id"));

        $start_time = $start_times ? strtotime($start_times) : '';
        $end_time = $end_times ? strtotime($end_times.' 24:00:00') : '';

        $agent = session('AGENT_USER');

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "a.agent_company=" . $agent['id'];
        }elseif ($agent['agent_level'] == 2){
            // 员工
            $where = "a.agent_staff=" . $agent['id'];
        }else{
            //推广员
            $where = "a.id=" . $agent['id'];
        }
        $where .= $agent_staff ? " and a.agent_staff=".$agent_staff : '';
        $where .= $uid ? " and l.uid =".$uid : '';

        $where .= $agent_id ? " and l.agent_id=".$agent_id : '';
        $where .= $order_id ?  " and l.order_id like '%".$order_id."%'" : '';
        $where .= $start_time ?  " and l.addtime >= ".$start_time : '';
        $where .= $end_time ?  " and l.addtime <= ".$end_time : '';

        $list = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->join("user u", "u.id=l.uid")
            ->join('pay_menu p', 'l.pay_type_id=p.id', 'LEFT')
            ->join('agent s','s.id = a.agent_staff','left')
            ->field("l.*,a.login_name,u.user_nickname,s.login_name as sname,u.create_time,a.agent_company as agent_company,a.agent_staff as agent_staff,p.pay_name")
            ->order("l.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray();
        // 总统计
        $order['agent_money'] = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->sum("l.agent_money");
        $order['money'] = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->sum( 'l.money');

        $this->assign("agent", $agent);
        $this->assign("page", $page);
        $this->assign("money", $order);
        $this->assign("data", session("userindex"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }

    /**
     *用户注册列表
     */
    public function userlist()
    {
        $agent = session('AGENT_USER');
        $p = $this->request->param('page');
        $data =[];

        if (empty($p) and !$this->request->param('uid')  and !$this->request->param('agent_login') and !$this->request->param('user_nickname') and !$this->request->param('agent_level') and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('agent_id') and !$this->request->param('subordinate_id')) {

            $data['agent_level'] = 0;
            $data['start_time'] = '';
            $data['end_time'] =  '';
            $data['agent_id'] =  '';
        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['agent_login'] = $this->request->param('agent_login');
            $data['user_nickname'] = $this->request->param('user_nickname');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['agent_level'] = $this->request->param('agent_level');
            $data['subordinate_id'] = $this->request->param('subordinate_id');
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') : '';
        }
        if(count($data) > 0){
            session("conversion", $data);
        }
        $start_times = session("conversion.start_time");
        $end_times = session("conversion.end_time");

        $uid = intval(session("conversion.uid"));
        $agent_login = session("conversion.agent_login");
        $user_nickname = session("conversion.user_nickname");
        $agent_id = intval(session("conversion.agent_id"));
        $agent_level = intval(session("conversion.agent_level"));

        $start_time = $start_times ? strtotime($start_times) : '';

        $end_time = $end_times ? strtotime($end_times.' 24:00:00') : '';

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "a.agent_company=" . $agent['id'];
        }elseif ($agent['agent_level'] == 2){
            // 员工
            $where = "a.agent_staff=" . $agent['id'];
        }else{
            //推广员
            $where = "a.id=" . $agent['id'];
        }

        $where .= $start_time ? " and u.create_time >='" . $start_time . "'" : '';
        $where .= $end_time ? " and u.create_time <='" . $end_time . "'" : '';

        $where .= $uid ? " and u.id ='" . $uid . "'" : '';
        $where .= $agent_id ? ' and a.id=' . $agent_id : '';

        $where .= $agent_login ? " and (a.agent_login like'%".$agent_login."%' or a.login_name like'%".$agent_login."%')" :'';
        $where .= $agent_level ? ' and agent_level=' . $agent_level : '';
        $where .= $user_nickname ? " and u.user_nickname like'%".$user_nickname."%'" :'';

        $filed="u.user_nickname,u.id as uid,u.register_device,u.create_time,u.last_login_time,a.login_name,a.agent_level,a.id,u.create_time,a.agent_staff,a.agent_company,u.brand,u.model,u.public_ip,u.intranet_ip";
        $agent_list = Db::name('user')->alias("u")
            ->join("agent a","a.id = u.link_id")
            ->field($filed)
            ->where($where)
            ->order("u.create_time desc")
            ->paginate(10);

        // 获取分页显示
        $page = $agent_list->render();
        $user = $agent_list->toArray();
        $number = Db::name('user')->alias("u") ->join("agent a","a.id = u.link_id") ->where($where) ->count();

        $this->assign("page", $page);
        $this->assign("number", $number);
        $this->assign("agent", $agent);
        $this->assign("data", session("conversion"));
        $this->assign("users", $user['data']);

        return $this->fetch();
    }
    /**
     *消费明细
     */
    public  function consumption(){
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('agent_staff') and !$this->request->param('agent_id') and !$this->request->param('uid') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("consumption", null);
            $data['start_time'] = '';
            $data['end_time'] = '';
            session("userindex", $data);
        } else if (empty($p)) {
            $data['agent_staff'] = $this->request->param('agent_staff');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['uid'] = $this->request->param('uid');
            session("consumption", $data);
        }
        $config_log = load_cache('config');

        $uid = intval(session("consumption.uid"));
        $start_times = session("consumption.start_time");
        $end_times = session("consumption.end_time");
        $agent_staff = intval(session("consumption.agent_staff"));
        $agent_id = intval(session("consumption.agent_id"));

        $start_time = $start_times ? strtotime($start_times) : '';
        $end_time = $end_times ? strtotime($end_times.' 24:00:00') : '';

        $agent = session('AGENT_USER');

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "l.agent_company=" . $agent['id'];
        }elseif ($agent['agent_level'] == 2){
            // 员工
            $where = "l.agent_staff=" . $agent['id'];
        }else{
            //推广员
            $where = "l.agent_id=" . $agent['id'];
        }
        $where .= $agent_staff ? " and l.agent_staff=".$agent_staff : '';
        $where .= $uid ? " and l.user_id =".$uid : '';
        $where .= $agent_id ? " and l.agent_id=".$agent_id : '';

        $where .= $start_time ?  " and l.create_time >= ".$start_time : '';
        $where .= $end_time ?  " and l.create_time <= ".$end_time : '';

        $list = Db::name('user_consume_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->join("user u", "u.id=l.user_id")
            ->join("user t", "t.id=l.to_user_id")
            ->join('agent s','s.id = l.agent_staff','left')
            ->field("l.id,l.user_id as uid,u.create_time as utime,l.to_user_id,t.user_nickname as tname,l.content,l.coin,l.agent_id,l.agent_staff,l.classification,l.classification_id,l.agent_company,a.login_name,u.user_nickname,s.login_name as sname,l.create_time,l.agent_company as agent_company,l.agent_staff as agent_staff")
            ->order("l.create_time DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray();
        // 总统计
        $money = Db::name('user_consume_log')->alias("l")
            ->join('agent a','a.id = l.agent_id')
            ->where($where)
            ->sum( 'l.coin');

        $this->assign("agent", $agent);
        $this->assign("page", $page);
        $this->assign("money", $money);
        $this->assign("data", session("consumption"));
        $this->assign("users", $user['data']);
        $this->assign("currency_name", $config_log['currency_name']);
        return $this->fetch();
    }
    /***
     * 房间管理
     **/
    public function voice(){
        //搜索条件
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('voice_id') and !$this->request->param('status')) {
            session("voice", null);
            $data['status'] = 0;
            $data['voice_id'] =  '';
            session("voice", $data);
        } else if (empty($p)) {
            $data['status'] = $this->request->param('status');
            $data['voice_id'] = $this->request->param('voice_id');
            session("voice", $data);
        }

        $status = intval(session("voice.status"));
        $voice_id = intval(session("voice.voice_id"));
        $agent = session('AGENT_USER');

        $where = "agent_id =".$agent['id'];
        $where .= $status ? ' and status=' . $status : '';
        $where .= $voice_id ? ' and voice_id=' . $voice_id : '';
        $list = Db::name('agent_voice')->where($where)->order("create_time DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray()['data'];

        $this->assign("page", $page);
        $this->assign("data", session("voice"));
        $this->assign("users", $user);
        return $this->fetch();
    }
    /**
     * 主播管理
     */
    public function host(){
        //搜索条件
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('host_id') and !$this->request->param('status')) {
            session("host", null);
            $data['status'] = 0;
            $data['host_id'] =  '';
            session("host", $data);
        } else if (empty($p)) {
            $data['status'] = $this->request->param('status');
            $data['host_id'] = $this->request->param('host_id');
            session("host", $data);
        }

        $status = intval(session("host.status"));
        $host_id = intval(session("host.host_id"));
        $agent = session('AGENT_USER');

        $where = "agent_id =".$agent['id'];
        $where .= $status ? ' and status=' . $status : '';
        $where .= $host_id ? ' and host_id=' . $host_id : '';
        $list = Db::name('agent_host')->where($where)->order("create_time DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray()['data'];

        $this->assign("page", $page);
        $this->assign("data", session("host"));
        $this->assign("users", $user);
        return $this->fetch();
    }
    /**
     * 编辑主播ID
     */
    public function add_host(){
        $host_id = $this->request->param('host_id', 0, 'intval');
        $remarks = $this->request->param('remarks');
        $agent = session('AGENT_USER');

        $user = Db::name('user')->where("id=".$host_id)->find();
        if (!$user) {
            $this->error("主播ID错误");exit;
        }
        $agent_host= Db::name('agent_host')->where("agent_id=".$agent['id']." and host_id=".$host_id)->find();
        if ($agent_host) {
            $this->error("主播ID已存在，不需要重新添加");exit;
        }
        $insert = array(
            'agent_id' => $agent['id'],
            'remarks' => $remarks,
            'host_id' => $host_id,
            'status' => 1,
            'create_time'=>time(),
            'update_time'=>time()
        );
        $list = Db::name('agent_host')->insert($insert);
        if ($list) {
            $this->success("添加成功");exit;
        }else{
            $this->success("添加失败");exit;
        }
    }
    /**
     * 修改主播id状态
     */
    public function save_host(){
        $host_id = $this->request->param('host_id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        $agent = session('AGENT_USER');
        $agent_host= Db::name('agent_host')->where("agent_id=".$agent['id']." and host_id=".$host_id)->find();
        if(!$agent_host){
            $this->error("主播ID不存在");exit;
        }
        $save = array(
            'status' => $status,
            'update_time' => time()
        );
        $result= Db::name('agent_host')->where("agent_id=".$agent['id']." and host_id=".$host_id)->update($save);
        if ($result && $status == 3) {
            // 清空所有的主播
            if ($agent['agent_level'] == 1){
                $where="agent_company=".$agent['id'];
            }else{
                $where="agent_company=".$agent['agent_company'];
            }
            Db::name('agent')->where($where)->update(['host_id'=> 0]);
        }
        if ($result) {
            $this->success("操作成功");exit;
        }else{
            $this->error("操作失败");exit;
        }
    }
    /**
     * 绑定主播号 -- 自动关注
     */
    public function binding_host(){
        $host_id = $this->request->param('host_id', 0, 'intval');
        $id = $this->request->param('id', 0, 'intval');
        $agent = session('AGENT_USER');
        if($host_id){
            $agent_voice= Db::name('agent_host')->where("agent_id=".$agent['id']." and host_id=".$host_id)->find();
            if(!$agent_voice){
                $this->error("主播ID不存在");exit;
            }
        }
        $where="id=".$id;
        if ($agent['agent_level'] == 1){
            $where .=" and agent_company=".$agent['id'];
        }else{
            $where .=" and agent_company=".$agent['agent_company'];
        }
        $result = Db::name('agent')->where($where)->update(['host_id'=> $host_id]);
        if ($result) {
            $this->success("操作成功");exit;
        }else{
            $this->error("操作失败");exit;
        }
    }
    /**
     * 编辑房间ID
     */
    public function add_voice(){
        $voice_id = $this->request->param('voice_id', 0, 'intval');
        $remarks = $this->request->param('remarks');
        $agent = session('AGENT_USER');

        $voice = Db::name('voice')->where("id=".$voice_id)->find();
        if (!$voice) {
            $this->error("房间不存在");exit;
        }
        if ($voice['live_in'] == 0 || $voice['live_in'] == 2) {
            $this->error("房间已关闭");exit;
        }
        $agent_voice= Db::name('agent_voice')->where("agent_id=".$agent['id']." and voice_id=".$voice_id)->find();
        if ($agent_voice) {
            $this->error("房间已存在，不需要重新添加");exit;
        }
        $insert = array(
            'agent_id' => $agent['id'],
            'remarks' => $remarks,
            'voice_id' => $voice_id,
            'status' => 1,
            'create_time'=>time(),
            'update_time'=>time()
        );
        $list = Db::name('agent_voice')->insert($insert);
        if ($list) {
            $this->success("添加成功");exit;
        }else{
            $this->success("添加失败");exit;
        }
    }
    /**
     * 修改房间状态
     */
    public function save_voice(){
        $voice_id = $this->request->param('voice_id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        $agent = session('AGENT_USER');
        $agent_voice= Db::name('agent_voice')->where("agent_id=".$agent['id']." and voice_id=".$voice_id)->find();
        if(!$agent_voice){
            $this->error("房间不存在");exit;
        }
        $save = array(
            'status' => $status,
            'update_time' => time()
        );
        $result= Db::name('agent_voice')->where("agent_id=".$agent['id']." and voice_id=".$voice_id)->update($save);
        if ($result && $status == 3) {
            // 清空房间
            if ($agent['agent_level'] == 1){
                $where="agent_company=".$agent['id'];
            }else{
                $where="agent_company=".$agent['agent_company'];
            }
            Db::name('agent')->where($where)->update(['voice_id'=> 0]);
        }
        if ($result) {
            $this->success("操作成功");exit;
        }else{
            $this->error("操作失败");exit;
        }
    }
    /**
     * 绑定渠道房间号
     */
    public function binding_voice(){
        $voice_id = $this->request->param('voice_id', 0, 'intval');
        $id = $this->request->param('id', 0, 'intval');
        $agent = session('AGENT_USER');
        if($voice_id){
            $agent_voice= Db::name('agent_voice')->where("agent_id=".$agent['id']." and voice_id=".$voice_id)->find();
            if(!$agent_voice){
                $this->error("房间不存在");exit;
            }
        }
        $where="id=".$id;
        if ($agent['agent_level'] == 1){
            $where .=" and agent_company=".$agent['id'];
        }else{
            $where .=" and agent_company=".$agent['agent_company'];
        }
        $result = Db::name('agent')->where($where)->update(['voice_id'=> $voice_id]);
        if ($result) {
            $this->success("操作成功");exit;
        }else{
            $this->error("操作失败");exit;
        }
    }
    /**
     * 导出注册明细表
     */
    public function export_userlist(){
        $agent = session('AGENT_USER');

        $uid = $this->request->param('uid');
        $agent_login = $this->request->param('agent_login');
        $user_nickname = $this->request->param('user_nickname');
        $agent_id = $this->request->param('agent_id');
        $agent_level = $this->request->param('agent_level');

        $start_times = $this->request->param('start_time') ? $this->request->param('start_time') : '';
        $end_times = $this->request->param('end_time') ? $this->request->param('end_time') : '';

        $start_time = $start_times ? strtotime($start_times) : '';

        $end_time = $end_times ? strtotime($end_times.' 24:00:00') : '';

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "a.agent_company=" . $agent['id'];
        }elseif ($agent['agent_level'] == 2){
            // 员工
            $where = "a.agent_staff=" . $agent['id'];
        }else{
            //推广员
            $where = "a.id=" . $agent['id'];
        }

        $where .= $start_time ? " and u.create_time >='" . $start_time . "'" : '';
        $where .= $end_time ? " and u.create_time <='" . $end_time . "'" : '';

        $where .= $uid ? " and u.id ='" . $uid . "'" : '';
        $where .= $agent_id ? ' and a.id=' . $agent_id : '';

        $where .= $agent_login ? " and (a.agent_login like'%".$agent_login."%' or a.login_name like'%".$agent_login."%')" :'';
        $where .= $agent_level ? ' and agent_level=' . $agent_level : '';
        $where .= $user_nickname ? " and u.user_nickname like'%".$user_nickname."%'" :'';

        $filed="u.user_nickname,u.id as uid,a.login_name,a.agent_level,a.id,u.create_time,a.agent_staff,a.agent_company,u.brand,u.model,u.intranet_ip,u.public_ip,u.register_device";
        $list = Db::name('user')->alias("u")
            ->join("agent a","a.id = u.link_id")
            ->field($filed)
            ->where($where)
            ->order("u.create_time desc")
            ->select();

        $title = '注册明细记录';
        if ($list != null) {
            $dataResult = array();
            foreach ($list as $k=>$v) {
                $dataResult[$k]['user_nickname'] = $v['user_nickname'];
                $dataResult[$k]['uid'] = $v['uid'] ;
                $dataResult[$k]['login_name'] = $v['login_name'] ;
                $dataResult[$k]['id'] = $v['id'];
                $dataResult[$k]['agent_staff'] = $v['agent_staff'];
                $dataResult[$k]['brand'] = $v['brand'];
                $dataResult[$k]['model'] = $v['model'];
                $dataResult[$k]['intranet_ip'] = $v['intranet_ip'];
                $dataResult[$k]['public_ip'] = $v['public_ip'];
                $dataResult[$k]['register_device'] = $v['register_device'];
                $dataResult[$k]['time'] = date('Y-m-d H:i:s',$v['create_time']);
            }

            $str = "注册用户,注册ID,邀请渠道,邀请渠道ID,渠道推荐人,手机厂商,注册机型,注册IP(终端ip),注册IP(公网),注册设备号,注册时间";
            $this->excelData($dataResult, $str, $title); exit();
        }else{
            $this->error("暂无数据");
        }
    }
    /**
     * 导出充值记录表
     */
    public function export_userindex(){
        /**搜索条件**/
        $order_id = $this->request->param('order_id');
        $agent_staff = intval($this->request->param('agent_staff'));
        $agent_id = intval($this->request->param('agent_id'));
        $start_times = $this->request->param('start_time');
        $end_times = $this->request->param('end_time');
        $uid = intval($this->request->param('uid'));

        $start_time = $start_times ? strtotime($start_times) : '';
        $end_time = $end_times ? strtotime($end_times.' 24:00:00') : '';

        $agent = session('AGENT_USER');

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "a.agent_company=" . $agent['id'];
        }elseif ($agent['agent_level'] == 2){
            // 员工
            $where = "a.agent_staff=" . $agent['id'];
        }else{
            //推广员
            $where = "a.id=" . $agent['id'];
        }
        $where .= $agent_staff ? " and a.agent_staff=".$agent_staff : '';
        $where .= $uid ? " and l.uid =".$uid : '';

        $where .= $agent_id ? " and l.agent_id=".$agent_id : '';
        $where .= $order_id ?  " and l.order_id like '%".$order_id."%'" : '';
        $where .= $start_time ?  " and l.addtime >= ".$start_time : '';
        $where .= $end_time ?  " and l.addtime <= ".$end_time : '';

        $list = Db::name('agent_order_log')->alias("l")
            ->where($where)
            ->join('agent a','a.id = l.agent_id')
            ->join("user u", "u.id=l.uid")
            ->join('pay_menu p', 'l.pay_type_id=p.id', 'LEFT')
            ->join('agent s','s.id = a.agent_staff','left')
            ->field("l.*,a.login_name,u.user_nickname,s.login_name as sname,u.create_time,a.agent_company as agent_company,a.agent_staff as agent_staff,p.pay_name")
            ->order("l.addtime DESC")
            ->select();

        $title = '充值明细记录';
        if ($list != null) {
            $dataResult = array();
            foreach ($list as $k=>$v) {
                $dataResult[$k]['user_nickname'] = $v['user_nickname'];
                $dataResult[$k]['uid'] = $v['uid'] ;
                $dataResult[$k]['login_name'] = $v['login_name'] ;
                $dataResult[$k]['agent_id'] = $v['agent_id'];
                $dataResult[$k]['pay_name'] = $v['pay_name'];
                $dataResult[$k]['money'] = floatval($v['money']);
                $dataResult[$k]['order_id'] = $v['order_id'];
                $dataResult[$k]['time'] = date('Y-m-d H:i:s',$v['addtime']);
                $dataResult[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
            $str = "充值用户,用户ID,邀请渠道,邀请渠道ID,充值方式,充值金额,订单号,充值时间,注册时间";
            $this->excelData($dataResult, $str, $title); exit();
        }else{
            $this->error("暂无数据");
        }
    }
    /**
     * 导出消费明细记录
     */
    public function export_consumption(){

        $uid = intval($this->request->param('uid'));
        $start_times = $this->request->param('start_time');
        $end_times =$this->request->param('end_time');
        $agent_staff = intval($this->request->param('agent_staff'));
        $agent_id = intval($this->request->param('agent_id'));

        $start_time = $start_times ? strtotime($start_times) : '';
        $end_time = $end_times ? strtotime($end_times.' 24:00:00') : '';

        $agent = session('AGENT_USER');

        if ($agent['agent_level'] == 1) {
            // 公司
            $where = "l.agent_company=" . $agent['id'];
        }elseif ($agent['agent_level'] == 2){
            // 员工
            $where = "l.agent_staff=" . $agent['id'];
        }else{
            //推广员
            $where = "l.id=" . $agent['id'];
        }
        $where .= $agent_staff ? " and l.agent_staff=".$agent_staff : '';
        $where .= $uid ? " and l.user_id =".$uid : '';
        $where .= $agent_id ? " and l.agent_id=".$agent_id : '';

        $where .= $start_time ?  " and l.create_time >= ".$start_time : '';
        $where .= $end_time ?  " and l.create_time <= ".$end_time : '';

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
            $str = "消费用户,用户ID,邀请渠道,邀请渠道ID,消费物品,消费金额,收礼物账号,消费时间,注册时间";
            $this->excelData($dataResult, $str, $title); exit();

        }else{
            $this->error("暂无数据");
        }

    }
}