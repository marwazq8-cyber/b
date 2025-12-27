<?php

namespace app\api\controller;

use think\Db;
use think\helper\Time;

class UserinfoApi extends Base
{
    //房间营收数据
    public function voice_revenue()
    {
        $id = intval(input('param.id'));

        $url = SITE_URL . '/api/userinfo_api/export?id=' . $id;
        $this->assign("url", $url);
        return $this->fetch();
    }

    /*导出*/
    public function export()
    {
        if (!intval(input('param.id'))) {
            echo lang('data_error');
            exit;
        }
        $id = intval(input('param.id'));
        $where = "v.user_id=" . $id;

        $start_time = strtotime(date("Y-m-d 00:00:00"));//或者Y-m-d H:i:s
        //获取三个月后的时间戳
        $addtime = strtotime("-3 month", $start_time);
        $endtime = strtotime(date("Y-m-d 23:59:59", strtotime("-1 day")));

        if (strtotime(input('param.start_time'))) {
            $start_time = strtotime(input('param.start_time')) < $addtime ? $addtime : strtotime(input('param.start_time'));
        } else {
            $start_time = $addtime;
        }
        if (strtotime(input('param.end_time'))) {
            $end_time = strtotime(input('param.end_time'));// > $endtime ? $endtime : strtotime(input('param.end_time'));
        } else {
            $end_time = $endtime;
        }

        $where .= " and l.endtime <=" . $end_time;
        $where .= " and l.endtime >=" . $start_time;

        $list = db("voice_even_wheat_log")
            ->alias("l")
            ->join("voice v", "v.user_id=l.voice_id")
            ->join("user u", "u.id=l.user_id")
            ->join("user_gift_log g", "l.user_id=g.to_user_id")
            ->field("l.voice_id,v.title,l.user_id,l.user_nickname,v.user_id as voice_user_id,l.endtime,g.create_time")
            ->where("l.voice_id = $id and g.create_time >= $start_time and g.create_time <= $end_time")
            ->group("l.user_id")
            ->order('g.create_time desc')
            ->select();

        //房间总收益人数
        $count = db("voice_even_wheat_log")->alias("l")
            ->join("voice v", "v.user_id=l.voice_id")
            ->join("user u", "u.id=l.user_id")
            ->where($where)
            ->group("l.user_id")
            ->order('l.endtime desc')
            ->count();

        //房间总收益
        $profit = db("user_gift_log")->alias("l")
            ->join("voice v", "v.user_id=l.voice_user_id")
            ->where("v.user_id=" . $id . " and l.create_time >=" . $start_time . " and l.create_time <=" . $end_time)
            ->field("sum(l.profit) as voice_profit,v.title,v.id,sum(l.gift_coin) as gift_coin")
            ->find();

        $voice_coin = $profit && $profit['voice_profit'] ? $profit['voice_profit'] : 0;
        $gift_coin = $profit && $profit['gift_coin'] ? $profit['gift_coin'] : 0;

        $title = "房间收礼总钻石" . date('Y.m.d', $start_time) . "-" . date('Y.m.d', $end_time);
        $titlename = "<tr><th>" . $profit['title'] . "</th><th></th><th></th></tr>";
        $titlename .= "<tr><th>房间ID:" . $profit['id'] . "</th><th>收礼人数:" . $count . "</th><th>钻石总计:" . $gift_coin . "</th></tr>";
        //$titlename .= "<tr><td>" . $profit['id'] . "</tdh><td>" . $profit['title'] . "</td><td>" . $count . "</td><td>" . $gift_coin . "</td><td>" . $voice_coin . "</td></tr>\n";
        $titlename .= "<tr><th>昵称</th><th>收礼钻石</th><th>" . lang('ADMIN_DATE') . "</th></tr>";
        $str = "<style>tr{height: 4rem;font-size: 2rem;}</style><html >\r\n<head>\r\n<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\r\n<title>房间营收数据</title>\r\n\r\n</head>\r\n<body>";
        $str .= "<table border=1 style='width: 100%'>" . $titlename;
        //echo($str);die();
        foreach ($list as $k => $v) {
            //gift_coin
            $voice_profit = db("user_gift_log")
                ->where("to_user_id=" . $v['user_id'] . " and voice_user_id=" . $v['voice_user_id'] . " and create_time >=" . $start_time . " and create_time <=" . $end_time)
                ->field("sum(profit) as voice_profit,sum(gift_coin) as gift_coin")
                ->find();
            //dump($voice_profit);
            if ($voice_profit['gift_coin']) {
                $user_id = $v['user_id'] ? $v['user_id'] : lang('No_information');
                $user_nickname = $v['user_nickname'] ? $v['user_nickname'] : lang('No_information');
                $gift_coin = $voice_profit ? $voice_profit['gift_coin'] : '0';
                //$voice_profit = $voice_profit ? $voice_profit['voice_profit'] : '0';
                $endtimes = $v['create_time'] ? date('Y-m-d', $v['create_time']) : lang('No_information');

                //$str .= "<tr>";
                $str .= "<tr><td>{$user_nickname}\n{$user_id}</td><td>{$gift_coin}</td><td>{$endtimes}</td></tr>";
            }

            //$str .= "</tr>\n";
        }
        $str .= "</table></body></html>";
        echo($str);
        die();
        $filename = "房间收礼总钻石" . date('Y.m.d', $start_time) . "-" . date('Y.m.d', $end_time) . ".xls";
        header("Content-Type: application/vnd.ms-excel; name='excel'");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $str;
        exit;
    }


    //h5钱包
    public function wallet()
    {

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $list = Db::name("user_charge_rule")->order("orderno asc")->select();
        $pay_list = db('pay_menu')->field('id,pay_name,icon')->where('status', '=', 1)->select();
        //系统金币单位名称
        $config = load_cache('config');
        $coin_name = $config['currency_name'];

        $this->assign('pay_list', $pay_list);
        $this->assign('user_info', $user_info);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        $this->assign('list', $list);
        $this->assign('coin_name', $coin_name);
        return $this->fetch();
    }

    //h5我的收益
    public function earnings()
    {

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['income', 'income_total']);

        //系统金币单位名称
        $config = load_cache('config');
        $where = 'user_id = ' . $uid;
        $list = Db("user_cash_record")
            ->order('create_time desc')
            ->where($where)
            ->limit(10)
            ->select();
        foreach ($list as &$v) {
            $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
            if ($v['status'] == 1) {
                $v['status_name'] = lang('Settlement_succeeded');
            } else if ($v['status'] == 2) {
                $v['status_name'] = lang('Settlement_failed');
            } else {
                $v['status_name'] = lang('Settlement_in_progress');
            }
        }

        $this->assign('list', $list);
        $this->assign('user_info', $user_info);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        return $this->fetch();
    }

    //提现记录分页
    public function earnings_page()
    {

        $uid = intval(input('param.uid'));
        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;
        $limit = $page * 10;
        $where = 'user_id = ' . $uid;
        $list = Db("user_cash_record")
            ->order('create_time desc')
            ->where($where)
            ->limit($limit, 10)
            ->select();
        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
                if ($v['status'] == 1) {
                    $v['status_name'] = lang('Settlement_succeeded');
                } else if ($v['status'] == 2) {
                    $v['status_name'] = lang('Settlement_failed');
                } else {
                    $v['status_name'] = lang('Settlement_in_progress');
                }
            }
        }
        echo json_encode($list);
    }

    //我的等级
    public function level()
    {

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['income', 'income_total']);

        $level_my = get_grade_level($uid);

        //等级列表
        $level = load_cache('level');

        $sex_type = "";
        $this->assign('sex_type', $sex_type);
        $this->assign('level_my', $level_my);
        $this->assign('level', $level);
        $this->assign('user_info', $user_info);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        return $this->fetch();
    }

    //主播收益提现列表
    public function income_withdrawal()
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //    $user_info = check_login_token($uid, $token,['income']);
        $user_info = Db("user")->where("id=" . $uid)->find();

        //系统金币单位名称
        $config = load_cache('config');

        $user_info['money'] = round($user_info['income'] / $config['integral_withdrawal'], 2);  //转换成元
        $user_info['min_money'] = $config['min_cash_income'];
        $user_info['max_money'] = $config['max_cash_income'];
        $user_info['day_cash_num'] = $config['cash_day_limit'];
        $type = 1;
        if (intval($user_info['money']) < $user_info['min_money']) {
            $name = lang('Minimum_withdrawal') . $user_info['min_money'] . lang('ADMIN_MONEY');
            $type = 0;
        } elseif (intval($user_info['money']) > $user_info['max_money']) {
            $name = lang('Current_withdrawal_amount') . $user_info['max_money'] . lang('ADMIN_MONEY');
        } else {
            $name = lang('Current_withdrawal_amount') . floor($user_info['money']) . lang('ADMIN_MONEY');
        }


        $binding_account = $config['is_binding_account'];
        $list = explode(",", $binding_account);
        $pays['wx'] = 0;
        $pays['pay'] = 0;
        foreach ($list as $k => $v) {
            if ($v == '0') {
                $pays['wx'] = 1;
            } elseif ($v == 1) {
                $pays['pay'] = 1;
            }
        }

        $this->assign('userinfo', $user_info);
        $this->assign('pays', $pays);
        $this->assign('type', $type);
        $this->assign('name', $name);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        return $this->fetch();
    }

    //收益申请提现
    public function cash_income()
    {
        $result = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = intval(input('param.type')) ? intval(input('param.type')) : 1;         //1微信2支付宝

        $user_info = check_login_token($uid, $token, ['income']);

        //检查是否认证
        $auth_status = get_user_auth_status($uid);
        if ($auth_status != 1) {
            $result['msg'] = lang('Withdrawal_after_certification');
            return_json_encode($result);
        }
        $config = load_cache('config');
        $money = round($user_info['income'] / $config['integral_withdrawal'], 2);  //转换成元

        if ($money < $config['min_cash_income']) {
            $result['msg'] = lang('Minimum_withdrawal') . $config['min_cash_income'] . lang('ADMIN_MONEY');
            return_json_encode($result);
        }

        //查询是否超过当日最大提现次数
        $day_time = Time::today();
        $day_cash_num = db('user_cash_record')->where('user_id', '=', $uid)->where('create_time>' . $day_time[0])->count();
        if ($day_cash_num == $config['cash_day_limit']) {
            $result['msg'] = lang('maximum_withdrawal_times_per_day_are') . $day_cash_num;
            return_json_encode($result);
        }

        $pays = db('user_cash_account')->where("uid=" . $uid)->find();
        if (!$pays) {
            $result['msg'] = lang('Please_bind_account');
            return_json_encode($result);
        }
        if ($type == 1) {     //微信账户
            if (!$pays['wx']) {
                $result['msg'] = lang('Please_bind_wechat_account');
                return_json_encode($result);
            }
            $number = $pays['wx'];
        } else {
            if (!$pays['pay']) {
                $result['msg'] = lang('Please_bind_Alipay_account');
                return_json_encode($result);
            }
            $number = $pays['pay'];
        }
        $name = $pays['name'];   //提现人名称

        $money_log = intval($money) > intval($config['max_cash_income']) ? $config['max_cash_income'] : floor($money);  //提现金额

        $income = $config['integral_withdrawal'] * $money_log;    //扣除的收益数

        //扣除剩余提现额度
        $inc_income = db('user')->where('id', '=', $uid)->setDec('income', $income);
        if ($inc_income) {
            //添加提现记录
            $record = ['gathering_name' => $name, 'gathering_number' => $number, 'user_id' => $uid, 'income' => $income, 'paysid' => $pays['id'], 'create_time' => NOW_TIME, 'type' => $type, 'money' => $money_log];
            db('user_cash_record')->insert($record);
            $result['code'] = 1;
            $result['msg'] = lang('Withdrawal_succeeded_waiting_approval');
        }

        return_json_encode($result);
    }

    //绑定账户列表
    public function binding_account()
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $config = load_cache('config');
        $binding_account = $config['is_binding_account'];
        $list = explode(",", $binding_account);
        $account['wx'] = 0;
        $account['pay'] = 0;
        $account['name'] = '';
        foreach ($list as $k => $v) {
            if ($v == '0') {
                $account['wx'] = 1;
                $account['name'] .= lang('WECHAT');
            } elseif ($v == 1) {
                $account['pay'] = 1;
                $account['name'] .= lang('ALIPAY');
            }
        }
        $pays = db('user_cash_account')->where("uid=" . $uid)->find();
        $this->assign('list', $pays);
        $this->assign('account', $account);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        return $this->fetch();
    }

    //绑定账号
    public function add_binding_account()
    {
        $result = array('code' => 0, 'msg' => lang('Binding_failed'));

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $wx = trim(input('param.wx'));
        $pay = trim(input('param.pay'));
        $name = trim(input('param.name'));

        $user_info = check_login_token($uid, $token);

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        $data = array(
            'uid' => $uid,
            'pay' => $pay,
            'wx' => $wx,
            'addtime' => NOW_TIME,
            'name' => $name,
        );
        if ($pays) {
            $root['msg'] = lang('account_has_been_bound_cannot_replaced');
            echo json_encode($root);
            $result = db('user_cash_account')->where('uid=' . $uid)->update($data);
        } else {
            $result = db("user_cash_account")->insert($data);
        }
        if ($result) {
            $root['code'] = 1;
            $root['msg'] = lang('Binding_succeeded');
        }
        echo json_encode($root);
    }

}

?>