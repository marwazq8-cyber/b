<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-06-04
 * Time: 10:44
 */

namespace app\api\controller;

use think\Db;
use think\helper\Time;
use app\api\model\UserModel;
use app\api\model\LoginModel;
use app\api\model\VoiceModel;
use app\api\model\BzoneModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class WalletApi extends Base
{
    private $UserModel;
    private $LoginModel;
    private $VoiceModel;
    private $BzoneModel;

    protected function _initialize()
    {
        parent::_initialize();

        header('Access-Control-Allow-Origin:*');
        $this->UserModel = new UserModel();
        $this->LoginModel = new LoginModel();
        $this->VoiceModel = new VoiceModel();
        $this->BzoneModel = new BzoneModel();
    }

    public function get_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $config = load_cache('config');
        $user_info = check_login_token($uid, $token, ['income', 'income_total', 'friend_coin']);
//        if ($user_info['income'] && $config['integral_withdrawal']) {
//            $integral = $user_info['income'] / $config['integral_withdrawal'];
//            $user_info['income'] = round($integral, 2);
//            $user_info['income_total'] = round($user_info['income_total'] / $config['integral_withdrawal'], 2);
//        }
        if ($user_info['coin'] > 0) {
            $user_info['coin'] = number_format($user_info['coin']);
        }
        $result['data']['user_info'] = $user_info;
        $result['data']['currency_name'] = $config['currency_name'];
        $result['data']['friend_name'] = $config['virtual_currency_earnings_name'];
        return_json_encode($result);
    }

    //充值金币
    public function get_recharge_rules()
    {
        $result = array('code' => 0, 'msg' => lang('Recharge_gold_coin'), 'data' => array());
        $os_type = input('os_type');
        $os = input('os');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['income', 'friend_coin']);

        $list = db('user_charge_rule')
            ->where('type', '=', 1)
            ->where('recharge_type', '=', 1)
            ->field('id,name,money,coin,give,pay_pal_money,apple_pay_name,apple_pay_coin,ios_money,google_pay_id')
            ->order("orderno asc")
            ->select();
        $config = load_cache('config');
        if ($os == 'iOS' && $config['ios_pay_switch'] == 1) {
            foreach ($list as $k => &$v) {
                $v['money'] = $v['ios_money'];
                $v['coin'] = $v['apple_pay_coin'];
                if (!$v['apple_pay_name']) {
                    unset($list[$k]);
                }
            }
        }
        $pay_list = db('pay_menu')
            ->where('recharge_type', '=', 1)
            ->field('id,pay_name,icon')
            ->where('status', '=', 1)
            ->select();
        //不是苹果的话剔除苹果支付
        if ($os_type != 'ios') {
            for ($i = 0; $i < count($pay_list); $i++) {
                if ($pay_list[$i]['pay_name'] == '苹果内购') {
                    unset($pay_list[$i]);
                }
            }
        }

        $result['code'] = 1;
        $result['data'] = $list;
        //$result['data']['pay_list'] = $pay_list;

        return_json_encode($result);
    }

    public function get_recharge_rules_ios()
    {
        $result = array('code' => 0, 'msg' => lang('Recharge_gold_coin'), 'data' => array());
        $os_type = input('os_type');
        $os = input('os');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['income', 'friend_coin']);

        $list = db('user_charge_rule')
            ->where('type', '=', 1)
            ->where('recharge_type', '=', 1)
            ->where('apple_pay_name', '<>', '')
            ->field('id,name,money,coin,give,pay_pal_money,apple_pay_name,apple_pay_coin,ios_money,google_pay_id')
            ->order("orderno asc")
            ->select();
        $config = load_cache('config');
        if ($os == 'iOS' && $config['ios_pay_switch'] == 1) {
            foreach ($list as $k => &$v) {
                $v['money'] = $v['ios_money'];
                $v['coin'] = $v['apple_pay_coin'];
                if (!$v['apple_pay_name']) {
                    unset($list[$k]);
                }
            }
        }
        $pay_list = db('pay_menu')
            ->where('recharge_type', '=', 1)
            ->field('id,pay_name,icon')
            ->where('status', '=', 1)
            ->select();
        //不是苹果的话剔除苹果支付
        if ($os_type != 'ios') {
            for ($i = 0; $i < count($pay_list); $i++) {
                if ($pay_list[$i]['pay_name'] == '苹果内购') {
                    unset($pay_list[$i]);
                }
            }
        }

        $result['code'] = 1;
        $result['data'] = $list;
        //$result['data']['pay_list'] = $pay_list;

        return_json_encode($result);
    }
    public function get_pay_type()
    {
        $result = array('code' => 0, 'msg' => lang('Recharge_gold_coin'), 'data' => array());

        $uid = input('param.uid');
        $token = input('param.token');
        $os = input('param.os');

        $user_info = check_login_token($uid, $token);

        $config = load_cache('config');
        $where = 'status = 1 and recharge_type = 1';

        if ($os == 'iOS' && $config['ios_pay_switch'] == 1) {
            $pay_list = [];
        } else {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];

            if (strpos($userAgent, 'Android') !== false) {
                $where .= ' and is_ios_pay != 1';
            } else {
                $where .= ' and is_google_pay != 1';
            }

            $pay_list = db('pay_menu')
                ->field('id,pay_name,icon,is_google_pay')
                ->where($where)
                ->select();
        }

        $result['code'] = 1;
        $result['data'] = $pay_list;

        return_json_encode($result);
    }


    public function vue_wallet_agreement()
    {
        $result = array('code' => 1, 'msg' => lang('Recharge_gold_coin'), 'data' => array());
        $list = db('portal_post')->find(13);
        $list['post_content'] = html_entity_decode($list['post_content']);
        $result['data'] = $list;
        return_json_encode($result);

    }

    //充值记录
    public function get_recharge_log()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['income', 'friend_coin']);
        $list = db('user_charge_log')
            ->where(['uid' => $uid])
            ->order('addtime desc')
            ->page($page)->select();
        foreach ($list as &$item) {
            $pay = db('pay_menu')->find($item['pay_type_id']);
            $item['pay_name'] = '';
            if ($pay) {
                $item['pay_name'] = $pay['pay_name'];
            }
            $item['addtime'] = date('m-d H:i', $item['addtime']);
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //提现记录
    public function earnings_page()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;
        //$limit = $page * 10;
        $where = 'user_id = ' . $uid;
        $list = Db("user_cash_record")
            ->order('create_time desc')
            ->where($where)
            ->page($page, 10)
            ->select();
        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
                if ($v['status'] == 1) {
                    $v['status_name'] = lang('Withdrawal_succeeded');
                } else if ($v['status'] == 2) {
                    $v['status_name'] = lang('Withdrawal_failed');
                } else {
                    $v['status_name'] = lang('Settlement_in_progress');
                }
            }
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //提现
    public function get_withdraw_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $config = load_cache('config');
        $user_info = check_login_token($uid, $token, ['income', 'friend_coin']);
        if ($config['integral_withdrawal'] <= 0) {
            $user_info['income'] = 0;
        } else {
            $integral = $user_info['income'] / $config['integral_withdrawal'];
            $user_info['income'] = number_format($integral, 2);
        }

        //$result['data']['user_info'] = $user_info;
        //$result['data']['currency_name'] = $config['currency_name'];
        //$result['data']['friend_name'] = '友币';
        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        //echo json_encode($pays);
        $user_info['pay'] = '';
        $user_info['name'] = '';
        if ($pays) {
            $user_info['pay'] = $pays['bank_card'];
            $user_info['name'] = $pays['name'];
        }
        $user_info['notice_text'] = $config['withdraw_notice_text'];
        $result['data'] = $user_info;
        return_json_encode($result);
    }

    //申请提现
    public function request_user_apply()
    {
        $result = array('code' => 0, 'msg' => '');

        $money = intval(input('param.money'));//金额
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['income']);
//
//            if ($user_info['is_auth'] != 1) {
//                $result['msg'] = lang('Withdrawal_after_certification');
//                return_json_encode($result);
//            }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Due_to_suspected_violation');
            return_json_encode($result);
        }

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        if (!$pays) {
            $result['msg'] = lang('Please_bind_account');
            return_json_encode($result);
        }
        if (empty($pays['bank_card'])) {
            $result['msg'] = lang('Please_bind_account');
            return_json_encode($result);
        }
        $cash_card_name = db('cash_card_name')->where('id=' . intval($pays['bank_card_id']))->find();
        if (!$cash_card_name) {
            $result['msg'] = lang('Please_bind_account');
            return_json_encode($result);
        }
        /*$list = db('user_earnings_withdrawal')->where("status=1 and id=$id")->find();
        if (!$list) {
            return_json_encode($result);
        }*/
        $config = load_cache('config');
        if ($money < $config['min_cash_income']) {
            $result['msg'] = lang('minimum_withdrawal_amount_is') . $config['min_cash_income'];
            return_json_encode($result);
        }

        if ($money > $config['max_cash_income']) {
            $result['msg'] = lang('maximum_withdrawal_amount_is') . $config['max_cash_income'];
            return_json_encode($result);
        }

        $integral = $user_info['income'] / $config['integral_withdrawal'];
        if ($integral < $money) {
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        //查询是否超过当日最大提现次数
        $day_time = Time::today();
        $day_cash_num = db('user_cash_record')->where('user_id', '=', $uid)->where('create_time >' . $day_time[0])->count();

        if ($day_cash_num == $config['cash_day_limit']) {
            $result['msg'] = lang('maximum_withdrawal_times_per_day_are') . $day_cash_num . '！';
            return_json_encode($result);
        }
        //$money = round($list['coin'] / $config['integral_withdrawal']);
        //扣除剩余提现额度
        $coin = $config['integral_withdrawal'] * $money;
        $inc_income = db('user')->where('id', '=', $uid)->where('income >= ' . $coin)->setDec('income', $coin);
        if ($inc_income) {
            //添加提现记录
            $record = [
                'gathering_name'   => $pays['name'],
                'gathering_number' => $pays['bank_card'],
                'user_id'          => $uid,
                'paysid'           => $pays['id'],
                'income'           => $coin,
                'money'            => $money,
                'bank_card_id'     => $cash_card_name['id'],
                'bank_name'        => $cash_card_name['name'],
                'create_time'      => NOW_TIME
            ];
            db('user_cash_record')->insert($record);
            $result['msg'] = lang('Withdrawal_succeeded_waiting_approval');
            $result['code'] = 1;

        }
        return_json_encode($result);
    }

    //绑定账号
    public function add_binding_account()
    {
        $root = array('code' => 0, 'msg' => lang('Binding_failed'));

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //$wx = trim(input('param.wx'));
        $pay = trim(input('param.pay'));//支付宝帐号
        $name = trim(input('param.name'));//姓名

        $user_info = check_login_token($uid, $token);

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        $data = array(
            'uid'     => $uid,
            'pay'     => $pay,
            //'wx' => $wx,
            'addtime' => NOW_TIME,
            'name'    => $name,
        );
        if ($pays) {
            //$root['msg'] = '账号已绑定过,不能更换';
            //echo json_encode($root);
            $result = db('user_cash_account')->where('uid=' . $uid)->update($data);
        } else {
            $result = db("user_cash_account")->insert($data);
        }
        if ($result) {
            $root['code'] = 1;
            $root['msg'] = lang('Binding_succeeded');
        }
        return_json_encode($root);
    }

    //收益分类
    public function get_income_type()
    {
        $root = array('code' => 1, 'msg' => '');
        $data = [
            ['id' => 0, 'name' => lang('Total_income')],
            //['id' => 1, 'name' => lang('Chat_revenue')],
            //['id' => 2, 'name' => lang('Voice_revenue')],
            //['id' => 3, 'name' => lang('Video_revenue')],
            //['id' => 4, 'name' => lang('ADMIN_GIFT_INCOME')],
            //['id' => 6, 'name' => lang('ADMIN_PLAYER_INCOME')],
            //['id' => 8, 'name' => lang('ADMIN_GUILD_INCOME')],
            /*['id'=>7,'name'=>'聊天室收益'],*/
        ];
        $root['data'] = $data;
        return_json_encode($root);
    }

    //收益
    public function get_income_log()
    {

        $root = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        //$pay = trim(input('param.pay'));
        $id = trim(input('param.id'));//筛选条件

        $user_info = check_login_token($uid, $token);
        //user_log
        if ($id) {
            $map['buy_type'] = $id;
        } else {
            $map['buy_type'] = ['in', [1, 2, 3, 4, 5, 6]];
        }
        $map['type'] = 2;
        $map['uid'] = $uid;
        $list = db('user_log')
            ->where($map)
            ->order('addtime desc')
            ->page($page)
            ->select();

        /*$visit_list = [];
        $curyear = date('Y-m',NOW_TIME);
        foreach ($list as $v) {
            if ($curyear == date('Y-m', $v['addtime'])) {
                $date = date('Y年m月', $v['addtime']);
            } else {
                $date = date('Y年m月', $v['addtime']);
            }
            $v['addtime'] = date('m月d日',$v['addtime']);

            $user = db('user')->field('user_nickname')->find($v['operator']);
            if($user){
                $v['user_nickname'] = $user['user_nickname'];
            }else{
                $v['user_nickname'] = '';
            }
            if($v['operator'] == 1){
                $v['user_nickname'] = '';
            }
            $visit_list[$date][] = $v;
        }*/
        foreach ($list as &$v) {
            //$v['addtime'] = date('m月d日',$v['addtime']);

            $user = db('user')->field('user_nickname')->find($v['operator']);
            if ($user) {
                $v['user_nickname'] = $user['user_nickname'];
            } else {
                $v['user_nickname'] = '';
            }
            if ($v['operator'] == 1) {
                $v['user_nickname'] = '';
            }
        }
        $root['data'] = $list;
        return_json_encode($root);
    }

    //收益
    public function get_income_log_new()
    {

        $root = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        //$pay = trim(input('param.pay'));
        $id = trim(input('param.id'));//筛选条件

        $user_info = check_login_token($uid, $token);
        $config = load_cache('config');
        if ($id) {
            $map['buy_type'] = $id;
        } else {
            $map['buy_type'] = ['not in', [7, 8, 12, 13]];
        }

        // 查询主播或公会收益
        $where = "((l.to_user_id=" . $uid . " and l.profit > 0) or (l.guild_uid=" . $uid . " and l.guild_earnings > 0)) and l.status=1";
        $field = "l.*,u.user_nickname as uname,t.user_nickname as tname";

        switch ($id) {
            case 1:
                // 聊天收益
                $where .= " and l.type=5 and l.to_user_id =" . $uid;
                break;
            case 2:
                // 语音收益
                $where .= " and l.type=1 and l.to_user_id =" . $uid;
                break;
            case 3:
                // 视频收益
                $where .= " and l.type=2 and l.to_user_id =" . $uid;
                break;
            case 4:
                // 礼物收益
                $where .= " and (l.type=3 or l.type=23) and l.to_user_id =" . $uid;
                break;
            case 6:
                //陪玩收益
                $where .= " and l.type=7 and l.to_user_id =" . $uid;
                break;
            case 8:
                // 公会收益
                $where .= " and l.guild_uid=" . $uid;
                break;
            default:
        }

        $list = db('user_consume_log')->alias("l")
            ->join('user u', "l.user_id=u.id", 'LEFT')
            ->join('user t', "t.id=l.to_user_id", 'LEFT')
            ->field($field)
            ->where($where)
            ->order('l.create_time desc')
            ->page($page)
            ->select();

        $visit_list = [];
        $curyear = date('Y-m', NOW_TIME);
        $profit_name = $config['virtual_currency_earnings_name'];
        foreach ($list as $v) {
            if ($curyear == date('Y-m', $v['create_time'])) {
                $date = date('Y-m', $v['create_time']);
            } else {
                $date = date('Y-m', $v['create_time']);
            }
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $value = array(
                'id'            => $v['id'],
                'addtime'       => $v['create_time'],
                'user_nickname' => '',
                'center'        => '',
                'coin'          => $v['coin'],
                'profit'        => '',
                'money'         => '',
            );
            $profit = $v['profit'];
            $uname = $v['uname'];
            $tname = $v['tname'];
            $guild_earnings = $v['guild_earnings'];

            if ($id == 8) {
                // 查询公会长收益
                $value['user_nickname'] = $tname;
                $value['profit'] = $guild_earnings;
            } else {
                if ($v['guild_uid'] == $v['to_user_id']) {
                    // 主播是公会长
                    $value['user_nickname'] = $uname;
                    $value['profit'] = $profit + $guild_earnings;
                } else {
                    // 普通主播
                    if ($v['to_user_id'] == $uid) {
                        $value['user_nickname'] = $uname;
                        $value['profit'] = $profit;
                    } else {
                        // 公会长
                        $value['user_nickname'] = $tname;
                        $value['profit'] = $guild_earnings;
                    }
                }
            }


            switch ($v['type']) {
                case 1:
                    if ($v['to_user_id'] == $uid) {
                        $value['center'] = lang('voice_call');
                    } else {
                        $value['center'] = lang('语音通话公会收益');
                    }
                    break;
                case 2:
                    if ($v['to_user_id'] == $uid) {
                        $value['center'] = lang('Video_call');
                    } else {
                        $value['center'] = lang('视频通话公会收益');
                    }
                    break;
                case 4:
                    if ($v['to_user_id'] == $uid) {
                        $value['center'] = lang('通话');
                    } else {
                        $value['center'] = lang('通话公会收益');
                    }
                    break;
                case 5:
                    if ($v['to_user_id'] == $uid) {
                        $value['center'] = lang('聊天');
                    } else {
                        $value['center'] = lang('聊天公会收益');
                    }
                    break;
                case 7:
                    if ($v['to_user_id'] == $uid) {
                        $value['center'] = lang('陪玩');
                    } else {
                        $value['center'] = lang('陪玩公会收益');
                    }
                    break;
                case 23:
                    if ($v['to_user_id'] == $uid) {
                        $value['center'] = lang('礼物');
                    } else {
                        $value['center'] = lang('礼物公会收益');
                    }
                    //背包礼物消费
                    break;
                default:
                    if ($v['to_user_id'] == $uid) {
                        $value['center'] = $v['content'];
                    } else {
                        $value['center'] = $v['content'] . " - " . lang('公会收益');
                    }
                    break;
            }

            $visit_list[$date][] = $value;
        }

        $list_array = [];
        foreach ($visit_list as $k => $val) {
            //$v['addtime'] = date('m月d日',$v['addtime']);
            $array = [];
            $array['time'] = $k;
            $array['list'] = $val;
            $list_array[] = $array;
        }
        $root['data'] = $list_array;
        return_json_encode($root);
    }
}
