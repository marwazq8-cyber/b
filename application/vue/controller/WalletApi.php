<?php

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\WalletModel;
use app\api\model\GiftModel;

class WalletApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        $this->WalletModel = new WalletModel();

        $this->GiftModel = new GiftModel();
    }

    // 获取后台配置的银行卡类型
    public function get_bank_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        check_login_token($uid, $token);
        $cash_card_name = db('cash_card_name')->field("id,name")->where('status=1')->order("sort desc")->select();
        $user_cash_account = db('user_cash_account')->where('uid=' . $uid)->find();

        $account = array(
            'bank_card' => '',
            'bank_card_id' => '',
            'bank_card_name' => '',
            'name' => '',
        );
        if ($user_cash_account) {
            $account['bank_card'] = $user_cash_account['bank_card'];
            $account['bank_card_id'] = $user_cash_account['bank_card_id'];
            foreach ($cash_card_name as $v) {
                if ($account['bank_card_id'] == $v['id']) {
                    $account['bank_card_name'] = $v['name'];
                    break;
                }
            }
            $account['name'] = $user_cash_account['name'];
        }
        $result['data'] = array(
            'cash_card_name' => $cash_card_name,
            'account' => $account
        );
        return_json_encode($result);
    }

    // vue --- 绑定银行卡账号
    public function binding_bank()
    {
        $result = array('code' => 0, 'msg' => lang('Binding_failed'));
        $uid = input('param.uid');
        $token = input('param.token');

        $bank_id = intval(input('param.bank_id')); // 银行卡id
        $name = input('param.bank_name'); // 银行卡人姓名
        $bank_number = input('param.bank_number'); // 银行卡号

        check_login_token($uid, $token);
        // 账号
        $pays = $this->WalletModel->user_cash_account($uid);
        $cash_card_name = db('cash_card_name')->field("id,name")->where('status=1 and id=' . $bank_id)->find();
        if (!$cash_card_name) {
            $result['msg'] = lang('Wrong_bank_name');
        }
        $withdrawal = array(
            'name' => $name,
            'bank_card' => $bank_number,
            'bank_card_id' => $bank_id,
            'uid' => $uid,
            'addtime' => NOW_TIME
        );
        $status1 = true;
        if ($pays) {

            // 判断是否修改绑定的账户
            if ($pays['bank_card'] != $bank_number || $pays['name'] != $name || $pays['bank_card_id'] != $bank_id) {
                // 修改绑定
                $status1 = $this->WalletModel->upd_user_cash_account($uid, $withdrawal);
            }
        } else {
            // 添加绑定
            $status1 = $this->WalletModel->add_user_cash_account($withdrawal);
        }
        if ($status1) {
            $result['code'] = 1;
            $result['msg'] = lang('Binding_succeeded');
        }

        $result['data'] = $withdrawal;

        return_json_encode($result);
    }

    // 获取公众号提现
    public function withdrawal()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $config = load_cache('config');
        $user_info = check_login_token($uid, $token, ['income']);
        $integral = $user_info['income'] / $config['integral_withdrawal'];
        $user_info['income'] = number_format($integral, 2);
        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        $user_info['pay'] = '';
        $user_info['name'] = '';
        if ($pays) {
            $user_info['pay'] = $pays['pay'];
            $user_info['name'] = $pays['name'];
        }
        $user_info['notice_text'] = $config['withdraw_notice_text'];
        $result['data'] = $user_info;
        return_json_encode($result);
    }

    // 公众号 --- 提现绑定账号
    public function apply_withdrawal()
    {
        $result = array('code' => 1, 'msg' => lang('Binding_succeeded'));
        $uid = input('param.uid');
        $token = input('param.token');

        $pay = input('param.pay');
        $name = input('param.name');

        check_login_token($uid, $token);
        // 账号
        $pays = $this->WalletModel->user_cash_account($uid);

        $withdrawal = array(
            'name' => $name,
            'pay' => $pay,
            'uid' => $uid,
            'addtime' => NOW_TIME
        );
        $status = false;
        if ($pays) {
            // 判断是否修改绑定的账户
            if ($pays['pay'] != $pay || $pays['name'] != $name) {
                // 修改绑定
                $status = $this->WalletModel->upd_user_cash_account($uid, $withdrawal);
            }
        } else {
            // 添加绑定
            $status = $this->WalletModel->add_user_cash_account($withdrawal);
        }
        if ($status == false) {
            $result['code'] = 0;
            $result['msg'] = lang('Binding_failed');
        }

        $result['data'] = $withdrawal;
        return_json_encode($result);
    }

    // 钱包
    public function index()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $config = load_cache('config');
        // 获取用户信息
        $filed = ['income', 'coin'];
        // 查询用户信息
        $user = $this->WalletModel->get_user_info($uid, $filed);

        $user['uid'] = $uid;

        $user['token'] = $token;
        // 系统金币单位名称
        $user['coin_name'] = $config['currency_name'];
        // 系统钻石单位名称
        $user['income_name'] = $config['virtual_currency_earnings_name'];
        // $user_info = check_login_token($uid, $token);
        $result['data'] = $user;

        return_json_encode($result);
    }

    // 获取充值规则
    public function get_recharge()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $config = load_cache('config');

        // 获取用户信息
        $filed = ['user_nickname', 'coin', 'token'];
        // 查询用户信息
        $user = $this->WalletModel->get_user_info($uid, $filed);

        $user['uid'] = $uid;
        // 充值规则
        $list = $this->WalletModel->get_user_charge_rule();
        // 支付类型
        $pay_list = $this->WalletModel->get_pay_menu();

        //不是苹果的话剔除苹果支付
        if (input('os_type') != 'ios') {
            for ($i = 0; $i < count($pay_list); $i++) {
                if ($pay_list[$i]['pay_name'] == '苹果内购') {

                    unset($pay_list[$i]);
                }
            }
        }

        // 充值协议
        $portal = $this->WalletModel->get_agreement("充值协议");
        // 协议内容    
        $data['post_content'] = htmlspecialchars_decode($portal['post_content']);
        // 系统金币单位名称
        $data['coin_name'] = $config['currency_name'];
        $data['list'] = $list;
        $data['user'] = $user;
        $data['pay_list'] = $pay_list;
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 兑换列表
    public function get_exchange()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $config = load_cache('config');
        // 获取用户信息
        $filed = ['income', 'token'];
        // 查询用户信息
        $user = $this->WalletModel->get_user_info($uid, $filed);

        $user['uid'] = $uid;

        // 系统金币单位名称
        $user['coin_name'] = $config['currency_name'];
        // 系统钻石单位名称
        $user['income_name'] = $config['virtual_currency_earnings_name'];
        // 兑换规则
        $list = $this->WalletModel->get_exchange();

        $data['list'] = $list;
        $data['user'] = $user;
        $result['data'] = $data;

        return_json_encode($result);
    }

    // 兑换
    public function exchange_post()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $id = intval(input('param.id'));
        // 获取兑换表数据
        $list = $this->WalletModel->get_user_exchange_list("id=$id and status=1");

        if (!$list) {

            $result['msg'] = lang('Exchange_rule_does_not_exist');

            return_json_encode($result);
        }
        // 获取用户信息
        $filed = ['income', 'token'];
        // 查询用户信息
        $user = $this->WalletModel->get_user_info($uid, $filed);

        if (!$user || $user['token'] != $token) {

            $result['msg'] = lang('login_timeout');

            return_json_encode($result);
        }

        if ($user['income'] < intval($list['earnings'])) {

            $result['msg'] = lang('Insufficient_balance_cannot_be_exchanged');

            return_json_encode($result);
        }

        $charging_coin_res = $this->WalletModel->deduct_user_earnings($uid, $list['coin'], $list['earnings']);
        if (!$charging_coin_res) {

            $result['msg'] = lang('Exchange_failed');

            return_json_encode($result);
        }
        // 钻石变更记录
        save_coin_log($uid, $list['coin'], 1, 15);
        // 收益变更记录
        save_income_log($uid, $list['earnings'], 1, 15);
        // 添加兑换记录
        $coin_log = [
            'uid' => $uid,
            'touid' => $uid,
            'exchange_id' => $list['id'],
            'earnings' => $list['earnings'],
            'coin' => $list['coin'],
            'addtime' => NOW_TIME,
        ];

        // 添加兑换记录
        $this->WalletModel->add_user_exchange_log($coin_log);
        // 获取用户信息
        $filed = ['income'];
        // 查询用户信息
        $user = $this->WalletModel->get_user_info($uid, $filed);

        $result['income'] = $user['income'];

        $result['msg'] = lang('Successfully_redeemed');

        $result['code'] = 1;

        return_json_encode($result);
    }

    // 兑换记录
    public function get_for_record()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        // 系统金币单位名称
        $config = load_cache('config');
        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;

        $limit = $page * 10;
        // user_exchange_log
        $where = "uid=" . $uid;
        // 获取兑换记录
        $list = $this->WalletModel->get_for_record($where, $limit, 10);

        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['addtime'] = date('Y-m-d H:i', $v['addtime']);
            }
        }
        // 系统钻石单位名称
        $data['coin_name'] = $config['currency_name'];
        // 系统钻石单位名称
        $data['income_name'] = $config['virtual_currency_earnings_name'];

        $data['list'] = $list;
        $result['data'] = $data;

        return_json_encode($result);
    }

    // 提现
    public function get_withdrawal()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;

        $limit = $page * 10;

        $where = 'user_id = ' . $uid;
        // 获取提现记录
        $list = $this->WalletModel->get_user_cash_record($where, $limit, 10);

        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
            }
        }
        $config = load_cache('config');
        // 系统钻石单位名称
        $data['income_name'] = $config['virtual_currency_earnings_name'];

        $data['list'] = $list;
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 消费记录
    public function get_consumption_log()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;

        $limit = $page * 15;

        $where = 'l.user_id = ' . $uid . ' and l.coin>0';
        // 消费记录表
        $list = $this->WalletModel->get_consumption_log($where, $limit, 15);
        foreach ($list as &$v) {

            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $name = '';
            if ($v['user_nickname']) {

                $name = mb_strlen($v['user_nickname']) > 8 ? mb_substr($v['user_nickname'], 1, 8) . '..' : $v['user_nickname'];
            }

            if ($v['type'] == 1) {
                $v['content'] = lang('purchase') . $name . lang('Video_consumption');
            } elseif ($v['type'] == 2) {
                $v['content'] = lang('purchase') . $name . lang('Private_license_consumption');
            } elseif ($v['type'] == 3) {
                //获取礼物名称
                $gift = $this->GiftModel->get_user_gift_one_log($v['table_id']);

                $gift_name = $gift ? $gift['name'] : '';

                $v['content'] = lang('to') . $name . lang('Reward') . $gift_name . lang('consumption');

            } elseif ($v['type'] == 4) {
                $v['content'] = $name . lang('Call_consumption');
            } elseif ($v['type'] == 5) {
                $v['content'] = $name . lang('Private_letter_payment');
            } elseif ($v['type'] == 6) {
                $v['content'] = lang('purchase') . $name . lang('Guard_consumption');
            } elseif ($v['type'] == 7) {
                $v['content'] = lang('Turntable_lottery_consumption');
            } elseif ($v['type'] == 8) {
                $v['content'] = lang('Purchase_medal_consumption');
            } elseif ($v['type'] == 9) {
                $v['content'] = lang('Egg_smashing_consumption');
            } elseif ($v['type'] == 11) {
                $v['content'] = lang('Purchase_beautiful_number_for_consumption');
            } elseif ($v['type'] == 12) {
                $v['content'] = lang('Accelerate_matching_consumption');
            }
        }

        $config = load_cache('config');
        // 系统钻石单位名称
        $data['coin_name'] = $config['currency_name'];

        $data['list'] = $list;
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 收益记录
    public function get_earnings_log()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;

        $limit = $page * 15;

        $where = 'l.to_user_id = ' . $uid . ' and l.profit > 0';
        // 消费记录表
        $list = $this->WalletModel->get_earnings_log($where, $limit, 15);
        foreach ($list as &$v) {

            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $name = '';
            if ($v['user_nickname']) {

                $name = mb_strlen($v['user_nickname']) > 8 ? mb_substr($v['user_nickname'], 1, 8) . '..' : $v['user_nickname'];
            }

            if ($v['type'] == 1) {
                $v['content'] = $name . lang('Purchase_video_revenue');
            } elseif ($v['type'] == 2) {
                $v['content'] = $name . lang('Income_from_purchasing_private_license');
            } elseif ($v['type'] == 3) {
                //获取礼物名称
                $gift = $this->GiftModel->get_user_gift_one_log($v['table_id']);

                $gift_name = $gift ? $gift['name'] : '';

                $v['content'] = $name . lang('Reward') . $gift_name . lang('profit');

            } elseif ($v['type'] == 4) {
                $v['content'] = $name . lang('ADMIN_CALL_INCOME');
            } elseif ($v['type'] == 5) {
                $v['content'] = $name . lang('ADMIN_CHAT_INCOME');
            } elseif ($v['type'] == 6) {
                $v['content'] = $name . lang('Purchase_Guardian_income');
            } elseif ($v['type'] == 7) {
                $v['content'] = lang('Turntable_lottery_income');
            } elseif ($v['type'] == 8) {
                $v['content'] = lang('Purchase_medal_income');
            } elseif ($v['type'] == 9) {
                $v['content'] = lang('Egg_smashing_income');
            } elseif ($v['type'] == 11) {
                $v['content'] = lang('Income_from_purchase_beautiful_number');
            } elseif ($v['type'] == 12) {
                $v['content'] = lang('Accelerated_matching_benefits');
            }
        }

        $config = load_cache('config');
        // 系统钻石单位名称
        $data['earnings_name'] = $config['virtual_currency_earnings_name'];

        $data['list'] = $list;
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 充值记录
    public function get_wallet_charge()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;

        $limit = $page * 15;

        $where = 'uid = ' . $uid . " and status=1";

        $list = $this->WalletModel->get_wallet_charge($where, $limit, 15);

        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['addtime'] = date('Y-m-d H:i', $v['addtime']);
            }
        }

        $data['list'] = $list;

        $config = load_cache('config');
        // 系统钻石单位名称
        $data['coin_name'] = $config['currency_name'];

        $result['data'] = $data;
        return_json_encode($result);
    }

    // 提现方式
    public function withdrawal_way()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $config = load_cache('config');

        $is_binding_account = $config['is_binding_account'];

        $list = explode(",", $is_binding_account);

        $data = array(
            'wx' => 0,
            'alipay' => 0,
            'bank_card' => 0,
        );
        foreach ($list as $key => $v) {
            if ($v == 0) {
                $data['wx'] = 1;
            }
            if ($v == 1) {
                $data['alipay'] = 1;
            }
            if ($v == 2) {
                $data['bank_card'] = 1;
            }
        }
        // 系统钻石单位名称
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 获取银行卡名称
    public function get_columns()
    {
        $result = array('code' => 1, 'msg' => '');

        // 获取银行卡名称
        $list = $this->WalletModel->get_cash_card();
        // 系统钻石单位名称
        $result['data'] = $list;
        return_json_encode($result);
    }

    // 收益提现规则
    public function get_withdrawal_rules()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $type = intval(input('param.type')) ? intval(input('param.type')) : 1;         //1微信2支付宝3银行卡

        $config = load_cache('config');
        // 获取用户信息
        $filed = ['income'];
        // 查询用户信息
        $user = $this->WalletModel->get_user_info($uid, $filed);

        // 获取提现规则
        $list = $this->WalletModel->user_earnings_withdrawal();

        foreach ($list as &$v) {
            $v['money'] = number_format($v['coin'] / $config['integral_withdrawal']);
        }
        // 账号
        $pays = $this->WalletModel->user_cash_account($uid);
        $user['account'] = '';
        $user['account_name'] = '';
        $user['pay_type'] = $type;
        if ($pays) {
            if ($type == 1) {
                $user['account'] = $pays['wx'];
            } elseif ($type == 2) {
                $user['account'] = $pays['pay'];
            } else {
                $user['account'] = $pays['bank_card'];
            }
            $user['bank_card_id'] = $pays['bank_card_id'] ? $pays['bank_card_id'] : 0;
            $user['account_name'] = $pays['name'];
        }

        // 提现上下限
        // 每日提现次数限制
        $user['cash_day_limit'] = $config['cash_day_limit'];
        // 每次提现最大数值上限(元)
        $user['max_cash_income'] = $config['max_cash_income'];
        // 每次提现最低数值下限(元)
        $user['min_cash_income'] = $config['min_cash_income'];

        $data['list'] = $list;
        // 提现兑换比例
        $data['integral_withdrawal'] = $config['integral_withdrawal'];
        $data['income_name'] = $config['virtual_currency_earnings_name'];
        $data['user'] = $user;

        $result['data'] = $data;
        return_json_encode($result);
    }

    // 收益申请提现
    public function cash_income()
    {

        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        //1微信2支付宝3银行卡
        $type = intval(input('param.type')) ? intval(input('param.type')) : 1;
        // 自定义金额
        $money = trim(input('param.money'));
        // 账号
        $account = trim(input('param.account'));
        // 姓名
        $account_name = trim(input('param.account_name'));
        // 银行卡id
        $bank_card_id = trim(input('param.bank_card_id'));
        if ($type == 3) {
            $card = $this->WalletModel->get_cash_card_one("id=" . $bank_card_id . " and status=1");
            if (!$card) {
                $result['msg'] = lang('Wrong_bank_name');
                return_json_encode($result);
            }
        }
        $user_info = check_login_token($uid, $token, ['income', 'is_auth']);

        //检查是否认证
        if ($user_info['is_auth'] != 1) {

            $result['msg'] = lang('Withdrawal_after_certification');
            return_json_encode($result);
        }
        if (empty($account)) {

            $result['msg'] = lang('Enter_withdrawal_account_number');
            return_json_encode($result);
        }
        if (empty($account_name)) {

            $result['msg'] = lang('Please_enter_your_name');
            return_json_encode($result);
        }

        $config = load_cache('config');

        if ($money < $config['min_cash_income']) {
            $result['msg'] = lang('Minimum_withdrawal') . $config['min_cash_income'] . lang('ADMIN_MONEY');
            return_json_encode($result);
        }
        // 查询是否超过当日最大提现次数
        $day_time = Time::today();

        $day_cash_num = db('user_cash_record')->where('user_id', '=', $uid)->where('create_time>' . $day_time[0])->count();

        if ($day_cash_num == $config['cash_day_limit']) {
            $result['msg'] = lang('maximum_withdrawal_times_per_day_are') . $day_cash_num;
            return_json_encode($result);
        }

        if ($money > $config['max_cash_income']) {
            $result['msg'] = lang('Maximum_cash_withdrawal_each_time') . $config['max_cash_income'] . lang('ADMIN_MONEY');
            return_json_encode($result);
        }

        // 账号
        $pays = $this->WalletModel->user_cash_account($uid);

        $withdrawal = array(
            'name' => $account_name,
            'uid' => $uid,
            'addtime' => NOW_TIME
        );
        if ($type == 1) {
            $withdrawal['wx'] = $account;

        } elseif ($type == 2) {
            $withdrawal['pay'] = $account;
        } else {
            $withdrawal['bank_card'] = $account;
            $withdrawal['bank_card_id'] = $bank_card_id;
        }
        if ($pays) {

            $account_old = $type == 1 ? $pays['wx'] : $pays['pay'];
            // 判断是否修改绑定的账户
            if ($account_old != $account || $pays['name'] != $account_name) {
                // 修改绑定
                $this->WalletModel->upd_user_cash_account($uid, $withdrawal);
            }
            $bangding_id = $pays['id'];
        } else {
            // 添加绑定
            $bangding_id = $this->WalletModel->add_user_cash_account($withdrawal);
        }
        // 扣除的收益数
        $income = $config['integral_withdrawal'] * $money;

        if ($user_info['income'] < $income) {

            $result['msg'] = lang('Withdrawal_failed');
            return_json_encode($result);
        }
        //扣除剩余提现额度
        $inc_income = $this->WalletModel->deduct_user_income($uid, $income);

        if ($inc_income) {
            // 变更记录
            save_income_log($uid, '-' . $income, 2, 7);
            // 添加提现记录
            $record = ['gathering_name' => $account_name, 'gathering_number' => $account, 'user_id' => $uid, 'income' => $income, 'paysid' => $bangding_id, 'create_time' => NOW_TIME, 'type' => $type, 'money' => $money, 'source' => 2];
            /*
            if($type == 3){
                $record['cash_card_name'] = $card['name'];
            }
            */
            // 操作数据
            $this->WalletModel->add_user_cash_record($record);

            $result['code'] = 1;
            $result['msg'] = lang('Application_succeeded_waiting_approval');
        }

        return_json_encode($result);
    }


}