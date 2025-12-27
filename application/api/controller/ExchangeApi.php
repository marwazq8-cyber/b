<?php

namespace app\api\controller;

use think\Db;
use think\helper\Time;

class ExchangeApi extends Base
{
    //兑换列表
    public function integral()
    {
        //user_exchange_list
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token')) ? trim(input('param.token')) : session('Exchange_token');
        $lang = trim(input('param.lang'));

        //系统金币单位名称
        $config = load_cache('config');
        $coin_name = $config['currency_name'];
        $earnings_name = $config['virtual_currency_earnings_name'];
        $minimum_exchange_quantity = $config['minimum_exchange_quantity'];
        $user_info = Db::name("user")->where("id=" . $uid . " and token='$token'")->find();
        if ($user_info) {
            session('Exchange_token', $token);
        }
        $list = Db::name("user_exchange_list")->where("status=1")->order("sort desc")->select();

        $is_exchange = 0; // 帮好友兑换
        if ($config['friend_exchange_switch'] == 2) {
            $is_exchange = 1;
        } elseif ($config['friend_exchange_switch'] == 1) {
            // 指定得用户id
            $is_exchange = $user_info['is_exchange'];
        }

        $this->assign("lang", $lang);
        $this->assign("coin_name", $coin_name);
        $this->assign("earnings_name", $earnings_name);
        $this->assign("user", $user_info);
        $this->assign("list", $list);
        $this->assign("open_custom_exchange", $config['open_custom_exchange']); // 是否开启了自定义兑换功能
        $this->assign("integral_coin", $config['integral_coin']); // 自定义兑换价格
        $this->assign("is_exchange", $is_exchange);
        $this->assign("minimum_exchange_quantity", $minimum_exchange_quantity); // 最少兑换数量

        return $this->fetch();
    }

    //收益记录
    public function earnings_record()
    {
        $uid = intval(input('param.uid'));
        $lang = trim(input('param.lang'));
        $list = db('user_gift_log')->alias('l')
            ->join('user u', 'u.id = l. user_id')
            ->field('l.gift_name,l.profit,l.create_time,u.user_nickname')
            ->where("l.to_user_id=" . $uid)
            ->order("l.create_time desc")
            ->limit(30)
            ->select();
        $this->assign("lang", $lang);
        $this->assign("list", $list);
        $this->assign("uid", $uid);
        return $this->fetch();
    }

    //收益分页记录
    public function earnings_record_page()
    {
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1;
        $limit = 30;
        if ($page > 1) {
            $page = $page * 30;
        }
        $list = db('user_gift_log')->alias('l')
            ->join('user u', 'u.id = l. user_id')
            ->field('l.gift_name,l.profit,l.create_time,u.user_nickname')
            ->where("l.to_user_id=" . $uid)
            ->order("l.create_time desc")
            ->limit($page, $limit)
            ->select();
        echo json_encode($list);
        exit;
    }

    //兑换记录
    public function for_record()
    {
        $uid = intval(input('param.uid'));
        $lang = trim(input('param.lang'));
        //系统金币单位名称
        $config = load_cache('config');
        $coin_name = $config['currency_name'];
        //user_exchange_log
        $list = db('user_exchange_log')->alias('l')
            ->join('user u', 'u.id = l.touid')
            ->field('l.*,u.user_nickname')
            ->where("uid=" . $uid)
            ->order("addtime desc")
            ->select();
        $this->assign("lang", $lang);
        $this->assign("list", $list);
        $this->assign("uid", $uid);
        $this->assign("coin_name", $coin_name);
        return $this->fetch();
    }

    //修改密码
    public function exchange_password2()
    {
        $lang = trim(input('param.lang'));
        $uid = intval(input('param.uid'));

        $this->assign("lang", $lang);
        $this->assign("uid", $uid);
        return $this->fetch();
    }

    //判断用户是否存在
    public function is_user()
    {
        $data = array('code' => 0, 'msg' => lang('User_friend_does_not_exist'));
        $touid = intval(input('param.touid'));
        $user_info = Db::name("user")->where("id=" . $touid . " or luck=" . $touid)->find();
        if ($user_info) {
            $data['code'] = 1;
        }
        echo json_encode($data);
        exit;
    }

    //修改密码
    public function exchange_upd_password()
    {
        $data = array('code' => 0, 'msg' => lang('operation_failed'));
        $uid = intval(input('param.uid'));
        $token = session('Exchange_token');
        $code = trim(input('param.code'));
        $phone = trim(input('param.phone'));
        $psd = trim(input('param.psd'));

        $ver = db('verification_code')->where("code='$code' and account='$phone' and expire_time > " . NOW_TIME)->find();
        if (!$ver) {
            $data['msg'] = lang('CAPTCHA_NOT_RIGHT');
            echo json_encode($data);
            exit;
        }
        $user_info = Db::name("user")->where("id=" . $uid . " and token='$token'")->find();
        if (!$user_info) {
            $data['msg'] = lang('login_timeout');
            echo json_encode($data);
            exit;
        }
        if ($user_info['mobile'] != $phone) {
            $data['msg'] = lang('Bind_mobile_number_account');
            echo json_encode($data);
            exit;
        }
        $update = array(
            'exchange_password' => md5($psd)
        );
        $user_psd = Db::name("user")->where("id=" . $uid)->update($update);
        if ($user_psd) {
            $data['msg'] = lang('Operation_successful');
            $data['code'] = 1;
        }
        echo json_encode($data);
        exit;
    }

    //兑换密码
    public function integral_exchange2()
    {
        $uid = intval(input('param.uid'));
        $touid = intval(input('param.touid'));
        $id = intval(input('param.id'));
        $number = intval(input('param.number'));
        $config = load_cache('config');
        $lang = trim(input('param.lang'));
        if ($number) {

            $integral_coin = $config['integral_coin'];  // 自定义兑换价格
            $coin = 0;
            if ($integral_coin) {
                $coin = floor($number / $integral_coin);
            }
            $list = array(
                'id'       => 0,
                'earnings' => $number,
                'coin'     => $coin,
            );
        } else {
            $list = Db::name("user_exchange_list")->where("id=$id and status=1")->find();
        }
        $user_id = $touid ? $touid : $uid;

        $coin_name = $config['currency_name'];
        $earnings_name = $config['virtual_currency_earnings_name'];

        $this->assign("lang", $lang);
        $this->assign("earnings_name", $earnings_name);
        $this->assign("coin_name", $coin_name);
        $this->assign("user_id", $user_id);
        $this->assign("uid", $uid);
        $this->assign("list", $list);
        return $this->fetch();
    }

    //兑换钻石
    public function exchange_post()
    {
        $data = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $touid = intval(input('param.touid')) ? intval(input('param.touid')) : intval(input('param.uid'));
        $id = intval(input('param.id'));
        $psd = trim(input('param.psd'));
        $token = session('Exchange_token');
        $earnings = intval(input('param.earnings'));
        $config = load_cache('config');
        if ($id == 0 && $earnings > 0) {
            if ($config['open_custom_exchange'] != 1) {
                $data['msg'] = lang('Operation_without_permission');
                echo json_encode($data);
                exit;
            }

            if ($config['minimum_exchange_quantity'] > $earnings) {
                $data['msg'] = lang('最少兑换', ['exchange_sum' => $config['minimum_exchange_quantity']]);
                echo json_encode($data);
                exit;
            }
            $integral_coin = $config['integral_coin'];  // 自定义兑换价格
            $coin = 0;
            if ($integral_coin) {
                $coin = floor($earnings / $integral_coin);
            }
            $list = array(
                'id'       => 0,
                'earnings' => $earnings,
                'coin'     => $coin,
            );
        } else {
            $list = Db::name("user_exchange_list")->where("id=$id and status=1")->find();
        }

        if (!$list) {
            $data['msg'] = lang('Exchange_rule_does_not_exist');
            echo json_encode($data);
            exit;
        }
        $user_info = Db::name("user")->where("id=" . $uid . " and token='$token'")->find();
        if (!$user_info) {
            $data['msg'] = lang('login_timeout');
            echo json_encode($data);
            exit;
        }
        if ($touid != $uid) {
            // 帮好友兑换 0关闭 1指定用户
            if ($config['friend_exchange_switch'] != 2) {
                if ($config['friend_exchange_switch'] == 0 || ($config['friend_exchange_switch'] == 1 && $user_info['is_exchange'] != 1)) {
                    $data['msg'] = lang('Operation_without_permission');
                    echo json_encode($data);
                    exit;
                }
            }
        }
        // 判断密码是空的不用检测密码
        if ($psd && md5($psd) != $user_info['exchange_password']) {
            $data['msg'] = lang('PASSWORD_NOT_RIGHT');
            echo json_encode($data);
            exit;
        }
        $touser_info = Db::name("user")->where("id=" . $touid . " or luck=" . $touid)->find();

        if (!$touser_info) {
            $data['msg'] = lang('User_friend_does_not_exist');
            echo json_encode($data);
            exit;
        }

        if ($user_info['income'] < intval($list['earnings'])) {
            $data['msg'] = lang('Insufficient_balance_cannot_be_exchanged');
            echo json_encode($data);
            exit;
        }
        // 启动事务
        db()->startTrans();
        try {
            $charging_coin_res = db('user')->where(['id' => $uid])->setDec('income', intval($list['earnings']));

            if ($charging_coin_res) {
                // 收益变更记录
                save_income_log($uid, '-' . intval($list['earnings']), 1, 15);
                $touser_coin = db('user')->where("id=" . $touser_info['id'])->setInc('coin', intval($list['coin']));

                if ($touser_coin) {
                    // 钻石变更记录
                    save_coin_log($touser_info['id'], intval($list['coin']), 1, 15);
                    $after_user = db('user')->where('id='.$uid)->find();
                    //添加兑换记录
                    $coin_log = [
                        'uid'         => $uid,
                        'touid'       => $touser_info['id'],
                        'exchange_id' => $list['id'],
                        'earnings'    => $list['earnings'],
                        'coin'        => $list['coin'],
                        'addtime'     => NOW_TIME,
                        'before_deduction'=> $user_info['income'],
                        'after_deduction'=> $after_user['income']
                    ];
                    db('user_exchange_log')->insert($coin_log);
                }
                $data['msg'] = lang('Successfully_redeemed');
                $data['code'] = 1;
            } else {
                $data['msg'] = lang('Exchange_failed');
            }
            db()->commit();   // 提交事务
        } catch (\Exception $e) {
            $data['msg'] = lang('Insufficient_Balance');
            db()->rollback();  // 回滚事务
        }

        echo json_encode($data);
        exit;
    }

}

?>