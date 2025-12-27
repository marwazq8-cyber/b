<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-06-13
 * Time: 14:15
 */

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\UserModel;

//use app\api\model\LoginModel;
use app\vue\model\VoiceModel;
use app\vue\model\BzoneModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class ExchangeApi extends Base
{
    //兑换列表
    public function index()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['income', 'is_exchange']);

        //系统金币单位名称
        $config = load_cache('config');


        $list = Db::name("user_exchange_list")->where("status=1")->order("sort desc")->select();
        $is_exchange = 0; // 帮好友兑换
        if ($config['friend_exchange_switch'] == 2) {
            $is_exchange = 1;
        } elseif ($config['friend_exchange_switch'] == 1) {
            // 指定得用户id
            $is_exchange = $user_info['is_exchange'];
        }
        // 获取规则
        $portal = db("portal_category_post")->alias('a')
            ->where(" a.status=1 and b.post_type=1 and b.post_status=1 and a.category_id=34")
            ->join("portal_post b", "b.id=a.post_id")
            ->field("b.id,b.post_content")
            ->find();
        $post_content = $portal ? htmlspecialchars_decode($portal['post_content']) : '';

        $result['data'] = array(
            'list' => $list,
            'user_info' => $user_info,
            'coin_name' => $config['currency_name'],
            'earnings_name' => $config['virtual_currency_earnings_name'],
            'open_custom_exchange' => $config['open_custom_exchange'], // 是否开启了自定义兑换功能
            'integral_coin' => $config['integral_coin'], // 自定义兑换价格
            'is_exchange' => $is_exchange, // 帮好友兑换
            'post_content' => $post_content,
            'minimum_exchange_quantity' => $config['minimum_exchange_quantity'] // 最少兑换数量
        );
        return_json_encode($result);
    }

    //兑换钻石
    public function exchange_post()
    {
        $result = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['income', 'is_exchange']);
        $touid = intval(input('param.touid'));
        $id = intval(input('param.id'));
        $exchange_income = intval(input('param.exchange_income'));
        $config = load_cache('config');

        $is_exchange = 0; // 帮好友兑换
        if ($config['friend_exchange_switch'] == 2) {
            $is_exchange = 1;
        } elseif ($config['friend_exchange_switch'] == 1) {
            // 指定得用户id
            $is_exchange = $user_info['is_exchange'];
        }
        if ($is_exchange == 0) {
            if ($touid != $uid) {
                $result['msg'] = lang('Operation_without_permission');
                return_json_encode($result);
            }
        }
        $touid = $touid ? $touid : $uid;
        // 是否开启了自定义兑换
        if ($exchange_income > 0) {
            if ($config['open_custom_exchange'] != 1) {
                $result['msg'] = lang('Operation_without_permission');
                return_json_encode($result);
            }
            if ($config['minimum_exchange_quantity'] > $exchange_income) {
                $result['msg'] = lang('Minimum_redemption') . " " . $config['minimum_exchange_quantity'];
                return_json_encode($result);
            }
            $integral_coin = $config['integral_coin'];  // 自定义兑换价格
            $coin = 0;
            if ($integral_coin) {
                $coin = floor($exchange_income / $integral_coin);
            }
            $list = array(
                'id' => 0,
                'earnings' => $exchange_income,
                'coin' => $coin,
            );
        } else {
            $list = Db::name("user_exchange_list")->where("id=$id and status=1")->find();
        }
        if (!$list) {
            $result['msg'] = lang('Exchange_rule_does_not_exist');
            return_json_encode($result);
        }
        $touser_info = Db::name("user")->where("id=" . $touid . " or luck=" . $touid)->find();
        if (!$touser_info) {
            $result['msg'] = lang('User_friend_does_not_exist');
            return_json_encode($result);
        }
        if ($user_info['income'] < intval($list['earnings'])) {
            $result['msg'] = lang('Insufficient_balance_cannot_be_exchanged');
            return_json_encode($result);
        }
        // 启动事务
        db()->startTrans();
        try {
            $charging_coin_res = db('user')->where(['id' => $uid])->setDec('income', intval($list['earnings']));
            save_income_log($uid, '-' . intval($list['earnings']), 1, 15);
            if ($charging_coin_res) {
                $touser_coin = db('user')->where("id=" . $touser_info['id'])->setInc('coin', intval($list['coin']));
                save_coin_log($touser_info['id'], intval($list['coin']), 1, 15);
                if ($touser_coin) {
                    $after_user = db('user')->where('id=' . $uid)->find();
                    //添加兑换记录
                    $coin_log = [
                        'uid' => $uid,
                        'touid' => $touser_info['id'],
                        'exchange_id' => $list['id'],
                        'earnings' => $list['earnings'],
                        'coin' => $list['coin'],
                        'addtime' => NOW_TIME,
                        'before_deduction' => $user_info['income'],
                        'after_deduction' => $after_user['income']
                    ];
                    db('user_exchange_log')->insert($coin_log);
                }
                $result['msg'] = lang('Successfully_redeemed');
                $result['code'] = 1;
            } else {
                $result['msg'] = lang('Exchange_failed');
            }
            db()->commit();   // 提交事务
        } catch (\Exception $e) {
            $result['msg'] = lang('Insufficient_Balance');
            db()->rollback();  // 回滚事务
        }
        return_json_encode($result);
    }

    //收益记录
    public function earnings_record()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token, ['income', 'is_exchange']);
        // 分页
        $page = intval(input('param.page')) <= 0 ? 1 : intval(input('param.page'));
        //系统金币单位名称
        $config = load_cache('config');
        $list = db('user_gift_log')->alias('l')
            ->join('user u', 'u.id = l. user_id')
            ->field('l.gift_name,l.profit,l.create_time,u.user_nickname')
            ->where("l.to_user_id=" . $uid)
            ->order("l.create_time desc")
            ->page($page)
            ->select();
        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $v['text'] = '"' . $v['user_nickname'] . '": ' . $v['gift_name'];
                $v['coin_text'] = "+" . $v['profit'] . ' ' . $config['virtual_currency_earnings_name'];
            }
        }

        $result['data'] = array(
            'list' => $list,
            'coin_name' => $config['currency_name'],
            'earnings_name' => $config['virtual_currency_earnings_name'],
        );
        return_json_encode($result);
    }

    //兑换记录
    public function exchange_log()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token, ['income', 'is_exchange']);
        // 分页
        $page = intval(input('param.page')) <= 0 ? 1 : intval(input('param.page'));
        //系统金币单位名称
        $config = load_cache('config');

        $list = db('user_exchange_log')->alias('l')
            ->join('user u', 'u.id = l.touid')
            ->field('l.*,u.user_nickname')
            ->where("uid=" . $uid)
            ->order("addtime desc")
            ->page($page)
            ->select();

        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i:s', $v['addtime']);
                $v['text'] = $uid == $v['touid'] ? lang('兑换') : lang('兑换') . ": " . $v['user_nickname'];
                $v['coin_text'] = $v['coin'] . $config['currency_name'];
                $v['earnings_text'] = "-" . $v['earnings'] . ' ' . $config['virtual_currency_earnings_name'];
            }
        }

        $result['data'] = array(
            'list' => $list
        );
        return_json_encode($result);
    }
}