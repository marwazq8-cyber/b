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

class RefillController extends AdminBaseController
{

    public function add_recharge()
    {
        $config = load_cache('config');
        $list[] = array(
            'id'   => 1,
            'name' => $config['currency_name']
        );
        $list[] = array(
            'id'   => 2,
            'name' => $config['virtual_currency_earnings_name']
        );
        $list[] = array(
            'id'   => 3,
            'name' => lang('Invitation_amount')
        );
        $list[] = array(
            'id'   => 4,
            'name' => $config['system_currency_name']
        );
        $list[] = array(
            'id'   => 5,
            'name' => lang('CPS_amount')
        );
        $id = intval(input('param.id'));
        $this->assign('id', $id);
        $this->assign('list', $list);

        return $this->fetch();
    }

    public function add_recharge_post()
    {
        $user_id = input('param.user_id');
        $type = input('param.type');
        $action = input('param.action');
        $count = intval(input('param.count'));

        $config = load_cache('config');
        $currency_name = $config['currency_name'];
        $profit_name = $config['virtual_currency_earnings_name'];
        $system_currency_name = $config['system_currency_name'];

        $max = COIN_INT;

        if ($count > $max) {
            $this->error(lang('number_operations_cannot_greater_than', ['n' => $max]), url('refill/add_recharge'));
            exit;
        }
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
            'uid'     => $user_id,
            'addtime' => NOW_TIME,
        );
        if ($type == 5) {
            $agent = db('agent')->field('income,income_total')->find($user_id);
        } else {
            $user_info = db('user')->field('coin,income,income_total,invitation_coin,friend_coin')->find($user_id);
        }

        if ($action == 1) {
            if ($type == 1) {
                if (($user_info['coin'] + $count) > $max) {
                    $this->error(lang('Total_balance_cannot_exceed', ['n' => $currency_name, 'm' => $max]), url('refill/add_recharge'));
                    exit;
                }
                db('user')->where('id = ' . $user_id)->setInc('coin', $count);

                save_coin_log($user_id, $count, 1, 100, 'Platform recharge');
            } else if ($type == 2) {
                if (($user_info['income'] + $count) > $max) {
                    $this->error(lang('Total_balance_cannot_exceed', ['n' => $profit_name, 'm' => $max]), url('refill/add_recharge'));
                    exit;
                }
                db('user')->where('id = ' . $user_id)->setInc('income', $count);
                db('user')->where('id = ' . $user_id)->setInc('income_total', $count);
                save_income_log($user_id, $count, 1, 100, 'Platform recharge');
            } else if ($type == 4) {
                if (($user_info['friend_coin'] + $count) > $max) {
                    $this->error(lang('Total_balance_cannot_exceed', ['n' => $system_currency_name, 'm' => $max]), url('refill/add_recharge'));
                    exit;
                }
                db('user')->where('id = ' . $user_id)->setInc('friend_coin', $count);
                save_coin_log($user_id, $count, 2, 100, 'Platform recharge');
            } else if ($type == 5) {
                //cps
                if (($agent['income'] + $count) > $max) {
                    $this->error(lang('Total_balance_cannot_exceed', ['n' => $system_currency_name, 'm' => $max]), url('refill/recharge'));
                    exit;
                }
                db('agent')->where('id = ' . $user_id)->setInc('income', $count);
                db('agent')->where('id = ' . $user_id)->setInc('income_total', $count);
            } else {
                if (($user_info['invitation_coin'] + $count) > $max) {
                    $this->error(lang('Total_balance_cannot_exceed', ['n' => '', 'm' => $max]), url('refill/add_recharge'));
                    exit;
                }
                db('user')->where('id = ' . $user_id)->setInc('invitation_coin', $count);
            }
        } else {
            if ($type == 1) {
                if ($user_info['coin'] < $count) {
                    $this->error($currency_name . lang('Insufficient_Balance'), url('refill/add_recharge'));
                    exit;
                }
                db('user')->where('coin >= ' . $count . ' and id = ' . $user_id)->setDec('coin', $count);
                save_coin_log($user_id, '-' . $count, 1, 100, 'Platform deduction');
            } else if ($type == 2) {
                if ($user_info['income'] < $count) {
                    $this->error($profit_name . lang('Insufficient_Balance'), url('refill/add_recharge'));
                    exit;
                }
                db('user')->where('income >= ' . $count . ' and id = ' . $user_id)->setDec('income', $count);
                db('user')->where('income_total >= ' . $count . ' and id = ' . $user_id)->setDec('income_total', $count);
                save_income_log($user_id, '-' . $count, 1, 100, 'Platform deduction');
            } else if ($type == 4) {
                if ($user_info['friend_coin'] < $count) {
                    $this->error($system_currency_name . lang('Insufficient_Balance'), url('refill/recharge'));
                    exit;
                }
                db('user')->where('friend_coin >= ' . $count . ' and id = ' . $user_id)->setDec('friend_coin', $count);
                save_coin_log($user_id, '-' . $count, 2, 100, 'Platform deduction');
            } else if ($type == 5) {
                //cps
                if (($agent['income'] < $count)) {
                    $this->error($system_currency_name . lang('Insufficient_Balance'), url('refill/recharge'));
                    exit;
                }
                db('agent')->where('id = ' . $user_id)->setDec('income', $count);
                db('agent')->where('id = ' . $user_id)->setDec('income_total', $count);
            } else {
                if ($user_info['invitation_coin'] < $count) {
                    $this->error(lang('Insufficient_Balance'), url('refill/add_recharge'));
                    exit;
                }
                db('user')->where('invitation_coin > ' . $count . ' and id = ' . $user_id)->setDec('invitation_coin', $count);
            }
        }

        $data['coin'] = $count;
        $data['user_type'] = $type;
        $data['type'] = $action;
        $data['operator'] = cmf_get_current_admin_id();
        $data['ip'] = get_client_ip();
        db('recharge_log')->insert($data);

        $this->success(lang('EDIT_SUCCESS'), url('refill/recharge'));

    }

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
        $list = Db::name("user_charge_rule")->where($where)->where('recharge_type = 1')->order("orderno asc")->select();
        $lists = Db::name("user_charge_rule")->where('recharge_type = 1')->order("orderno asc")->select();
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
            $this->assign('rule', array('type' => 0, 'status' => 1));
        }
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("user_charge_rule")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_charge_rule")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('refill/index'));
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

    //充值记录
    public function log_index()
    {

        $where = [];
        //dump($_REQUEST);
        if (!isset($_REQUEST['status']) || $_REQUEST['status'] == '-1') {
            $_REQUEST['status'] = '-1';
        } else {
            $where['c.status'] = $_REQUEST['status'];
        }
         if (!isset($_REQUEST['is_refund']) || $_REQUEST['is_refund'] == '-1') {
             $_REQUEST['is_refund'] = '-1';
         } else {
             $where['c.is_refund'] = $_REQUEST['is_refund'];
         }

        if (!isset($_REQUEST['pay_type_id']) || $_REQUEST['pay_type_id'] == '-1') {
            $_REQUEST['pay_type_id'] = '-1';
        } else {
            $where['c.pay_type_id'] = $_REQUEST['pay_type_id'];
        }
        if (isset($_REQUEST['end_time']) && $_REQUEST['end_time'] != '' && isset($_REQUEST['start_time']) && $_REQUEST['start_time'] != '') {
            $where['c.addtime'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }

        if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
            $where['c.uid'] = intval($_REQUEST['uid']);
        }
        if (isset($_REQUEST['agency_id']) && $_REQUEST['agency_id'] != '') {
            $where['c.agency_id'] = intval($_REQUEST['agency_id']);
        }
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $where['c.order_id'] = ["like", "%" . $_REQUEST['order_id'] . "%"];
        }
        if (isset($_REQUEST['trade_no']) && $_REQUEST['trade_no'] != '') {
            $where['c.trade_no'] = ["like", "%" . $_REQUEST['trade_no'] . "%"];
        }

        $pageWhere = ['query' => request()->param()];

        $list = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->paginate(20, false, $pageWhere);

        $result = array();
        foreach ($list as &$v) {
            if ($v['type'] == 11111111) {
                $v['pay_name'] = 'PayPal';
            } else if ($v['type'] == 7777777) {
                $v['pay_name'] = $v['pay_name'] . '（' . lang('VIP_recharge') . '）';
            } else if ($v['type'] == 8888888) {
                $v['pay_name'] = lang('agency_recharge') . '（' . $v['agency_id'] . '）';
            }
            $result[] = $v;
        }

        // 总充值
        $total_money = db('user_charge_log')->alias('c')->where($where)->sum('money');
        $pay_menu = db('pay_menu')->where("status=1")->select();

        // 到账数
        $total_coin = db('user_charge_log')->alias('c')->where($where)->sum('coin');

        $this->assign('total_coin', $total_coin);
        $this->assign('pay_menu', $pay_menu);
        $this->assign('total_money', $total_money);
        $this->assign('refill', $_REQUEST);
        $this->assign('list', $result);
        $this->assign('page', $list->render());
        return $this->fetch();
    }
    // 标记退款状态
    public function user_charge_refund(){
        $param = $this->request->param();
        $id = intval($param['id']);
        $user_charge_log = db('user_charge_log')->where("id",$id)->find();
        $status = '0';
        if($user_charge_log){
            $status = $user_charge_log['is_refund'] == 0 ? 1 : 0;
            $result = Db::name("user_charge_log")->where("id",$id)->update(['is_refund'=>$status]);
            if($result){
                $status = '1';
            }
        }
        return $status; exit;
    }
    //支付渠道列表
    public function pay_menu()
    {

        $list = db('pay_menu')->where('recharge_type = 1')->select();
        $lists = $list->toArray();
        foreach ($lists as &$v) {
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
        $result = Db::name("pay_menu")->insert($data);
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('refill/pay_menu'));
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
        if ($id) {
            $result = Db::name("pay_menu")->where("id=$id")->update($data);
        } else {
            $result = Db::name("pay_menu")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('refill/pay_menu'));
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

    //查询手动充值记录
    public function recharge()
    {
        if (!input("param.page")) {
            $data['type'] = 0;
            $data['user_type'] = 0;
            session('refill_recharge', $data);
        }
        if (isset($_REQUEST['uid']) || isset($_REQUEST['type']) || isset($_REQUEST['user_type'])) {
            session('refill_recharge', $_REQUEST);
        }

        $uid = session('refill_recharge.uid') ? session('refill_recharge.uid') : '';
        $type = session('refill_recharge.type') ? session('refill_recharge.type') : '';
        $user_type = session('refill_recharge.user_type') ? session('refill_recharge.user_type') : '';
        $where = 'r.id >0 ';
        if ($uid) {
            $where .= " and r.uid=" . $uid;
        }
        if ($type) {
            $where .= " and r.type=" . $type;
        }
        if ($user_type > 0) {
            $where .= " and r.user_type=" . $user_type;
        }
        $where .= session('refill_recharge.end_time') ? " and r.addtime <=" . strtotime(session('refill_recharge.end_time')) : '';
        $where .= session('refill_recharge.start_time') ? " and r.addtime >=" . strtotime(session('refill_recharge.start_time')) : '';
        $pageWhere = ['query' => request()->param()];
        $list = Db::name('recharge_log')->alias("r")
            ->join("user u", "u.id=r.uid")
            ->where($where)
            ->field('u.user_nickname,r.*')
            ->order('r.addtime desc')
            ->paginate(20, false, $pageWhere);

        $config = load_cache('config');
        $currency_name = $config['currency_name'];
        $profit_name = $config['virtual_currency_earnings_name'];
        $system_currency_name = $config['system_currency_name'];

        $this->assign('system_currency_name', $system_currency_name);
        $this->assign('profit_name', $profit_name);
        $this->assign('currency_name', $currency_name);

        $coin = Db::name('recharge_log')->alias("r")
            ->join("user u", "u.id=r.uid")
            ->where($where)
            ->sum("r.coin");

        $this->assign("coin", sctonum($coin));

        $this->assign("list", $list);
        $this->assign("page", $list->render());
        $this->assign('recharge', session('refill_recharge'));
        return $this->fetch();
    }

    /*导出*/
    public function export()
    {
        $title = lang('Recharge_record');

        $where = [];
        //dump($_REQUEST);
        if (!isset($_REQUEST['status']) || $_REQUEST['status'] == '-1') {
            $_REQUEST['status'] = '-1';
        } else {
            $where['c.status'] = $_REQUEST['status'];
        }

        if (isset($_REQUEST['end_time']) && $_REQUEST['end_time'] != '' && isset($_REQUEST['start_time']) && $_REQUEST['start_time'] != '') {
            $where['c.addtime'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }

        if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
            $where['c.uid'] = intval($_REQUEST['uid']);
        }

        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $where['c.order_id'] = intval($_REQUEST['order_id']);
        }

        $list = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->paginate();

        $lists = $list->toArray();
        if ($lists['data'] != null) {

            foreach ($lists['data'] as $k => $v) {
                if ($v['type'] == 11111111) {
                    $v['pay_name'] = 'PayPal';
                }
                if ($v['status'] == '1') {
                    $status = lang('SUCCESS');
                } else {
                    $status = lang('FAILED');
                }
                $dataResult[$k]['uid'] = $v['uid'] ? $v['uid'] : lang('No_data');
                $dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : lang('No_data');
                $dataResult[$k]['order_id'] = $v['order_id'] ? $v['order_id'] : lang('No_information');
                $dataResult[$k]['money'] = $v['money'] ? $v['money'] : lang('No_information');
                $dataResult[$k]['pay_pal_money'] = $v['pay_pal_money'] ? $v['pay_pal_money'] : '0';
                $dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : lang('No_information');
                $dataResult[$k]['pay_name'] = $v['pay_name'] ? $v['pay_name'] : lang('No_information');
                $dataResult[$k]['addtime'] = $v['addtime'] ? date('Y-m-d h:i', $v['addtime']) : lang('No_information');
                $dataResult[$k]['status'] = $status;

            }

            $str = lang('ADMIN_CONSUME_USER') . "ID," . lang('ADMIN_CONSUME_USER') . ",订单号,充值金额(美元),PayPal(USD),金币数,充值方式,添加时间,充值状态";

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error(lang('No_data'));
        }

    }

    public function ref_data()
    {
        $where = [];
        //dump($_REQUEST);
        if (!isset($_REQUEST['status']) || $_REQUEST['status'] == '-1') {
            $_REQUEST['status'] = '-1';
        } else {
            $where['c.status'] = $_REQUEST['status'];
        }

        if (isset($_REQUEST['end_time']) && $_REQUEST['end_time'] != '' && isset($_REQUEST['start_time']) && $_REQUEST['start_time'] != '') {
            $where['c.addtime'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }

        if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
            $where['c.uid'] = intval($_REQUEST['uid']);
        }

        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $where['c.order_id'] = intval($_REQUEST['order_id']);
        }

        $list = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->select();

        $count = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->count();

        $result = array();
        foreach ($list as &$v) {
            if ($v['type'] == 11111111) {
                $v['pay_name'] = 'PayPal';
            }
            $result[] = $v;
        }

        $data = [
            "total" => $count,
            "rows"  => $result,
        ];
        echo json_encode($data);
    }

}
