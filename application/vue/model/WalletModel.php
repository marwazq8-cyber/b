<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class WalletModel extends Model
{
    // 获取用户信息
    public function get_user_info($uid, $field)
    {

        $user = Db::name('user')->field($field)->where("id=" . $uid)->find();

        return $user;
    }

    /* 扣除用户收益 */
    public function deduct_user_income($uid, $income)
    {

        $charging_coin = db('user')->where('id=' . $uid)->setDec('income', $income);

        return $charging_coin;
    }

    // 获取充值列表
    public function get_user_charge_rule()
    {

        $list = db('user_charge_rule')->where('type=1')->field('id,name,money,coin,give,pay_pal_money,apple_pay_name,apple_pay_coin')->order("orderno asc")->select();

        return $list;
    }

    // 获取支付类型
    public function get_pay_menu()
    {

        $pay_list = db('pay_menu')->field('id,pay_name,icon')->where('status=1')->select();

        return $pay_list;
    }

    // 获取兑换规则
    public function get_exchange()
    {

        $list = Db::name("user_exchange_list")->field("id,earnings,coin")->where("status=1")->order("sort desc")->select();

        return $list;
    }

    // 获取银行卡名称
    public function get_cash_card()
    {
        $list = Db::name("cash_card_name")->field("id,name")->where("status=1")->order("sort desc")->select();

        return $list;
    }

    // 获取一个兑换数据
    public function get_user_exchange_list($where)
    {

        $list = Db::name("user_exchange_list")->where($where)->find();

        return $list;
    }

    // 兑换功能扣除用户收益增加用户钻石
    public function deduct_user_earnings($uid, $coin, $income_total)
    {

        $charging_coin = db('user')->where('id=' . $uid)->Dec('income', $income_total)->Inc('coin', $coin)->update();

        return $charging_coin;
    }

    // 添加兑换记录
    public function add_user_exchange_log($data)
    {

        $list = db('user_exchange_log')->insert($data);

        return $list;
    }

    // 兑换记录
    public function get_for_record($where, $limit, $number)
    {
        $list = db('user_exchange_log')->field("id,earnings,coin,addtime")
            ->where($where)
            ->limit($limit, $number)
            ->order("addtime desc")
            ->select();
        return $list;
    }

    // 协议
    public function get_agreement($title)
    {
        // 充值协议
        $portal = db("portal_category_post")->alias('a')
            ->where(" a.status=1 and b.post_type=1 and b.post_status=1 and c.name='$title'")
            ->join("portal_category c", "c.id=a.category_id")
            ->join("portal_post b", "b.id=a.post_id")
            ->field("b.id,b.post_content")
            ->find();

        return $portal;
    }

    // 提现记录
    public function add_user_cash_record($data)
    {

        $list = db('user_cash_record')->insert($data);;

        return $list;
    }

    // 提现申请
    public function get_user_cash_record($where, $limit, $number)
    {

        $list = Db("user_cash_record")
            ->order('create_time desc')
            ->where($where)
            ->limit($limit, $number)
            ->select();

        return $list;
    }

    // 消费记录
    public function get_consumption_log($where, $limit, $number)
    {

        $list = db('user_consume_log')
            ->alias('l')
            ->field('l.*,u.user_nickname')
            ->join('user u', 'l.to_user_id=u.id', 'left')
            ->where($where)
            ->limit($limit, $number)
            ->order('l.create_time desc')
            ->select();

        return $list;
    }

    // 收益记录
    public function get_earnings_log($where, $limit, $number)
    {

        $list = db('user_consume_log')
            ->alias('l')
            ->field('l.*,u.user_nickname')
            ->join('user u', 'l.user_id=u.id', 'left')
            ->where($where)
            ->limit($limit, $number)
            ->order('l.create_time desc')
            ->select();

        return $list;
    }

    // 充值记录
    public function get_wallet_charge($where, $limit, $number)
    {

        $list = Db("user_charge_log")
            ->field('money,coin,addtime,status')
            ->order('addtime desc')
            ->where($where)
            ->limit($limit, $number)
            ->select();

        return $list;
    }

    // 获取提现规则
    public function user_earnings_withdrawal()
    {

        $list = Db::name("user_earnings_withdrawal")->order("sort asc")->select();

        return $list;
    }

    // 获取绑定的账号
    public function user_cash_account($uid)
    {

        $pays = db('user_cash_account')->where("uid=" . $uid)->find();

        return $pays;
    }

    // 修改绑定账号
    public function upd_user_cash_account($uid, $data)
    {

        $status = db('user_cash_account')->where('uid=' . $uid)->update($data);

        return $status;
    }

    // 添加绑定账号
    public function add_user_cash_account($data)
    {

        $status = db("user_cash_account")->insertGetId($data);

        return $status;
    }

    // 获取绑定的账号
    public function get_cash_card_one($where)
    {

        $pays = db('cash_card_name')->where($where)->find();

        return $pays;
    }
}
