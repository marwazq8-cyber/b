<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/12 0012
 * Time: 上午 11:59
 */

namespace app\api\controller;

use app\api\controller\Base;
use think\Db;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class InvitationApi extends Base
{

    //邀请好友页面
    public function app_index()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);
        //获取邀请码
        $invite_code = create_invite_code_0910($uid);

        $user = db("user")->where("id=$uid")->field('invitation_coin')->find();
        $invitation_coin = $user['invitation_coin'];
        if (!$user || $invitation_coin <= 0) {
            $invitation_coin = 0;
        }

        $data = array(
            'invitation_coin' => $invitation_coin,
            'uid'             => $uid,
            'invite_url'      => 'http://' . $_SERVER['HTTP_HOST'] . '/api/download_api/index?invite_code=' . $invite_code,
        );

        //邀请规则
        $category = db('portal_category')->where("name='邀请奖励规则'")->find();
        if ($category) {
            $sex_type = db('portal_category_post')->where("category_id=" . $category['id'])->find();
            $oldTagIds = db('portal_post')->where("id=" . $sex_type['post_id'])->find();
            $data['rules'] = html_entity_decode($oldTagIds['post_content']);
        } else {
            $data['rules'] = '';
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    /*-------------------------    H5 邀请奖励 页面-------------------------------------------*/
    /*-------------------------    H5 邀请奖励 页面-------------------------------------------*/
    //推广邀请奖励规则
    public function index()
    {
        //invited _withdrawal_less     invited _withdrawal_more
        $uid = input("param.uid");

        //获取邀请码
        $invite_code = create_invite_code_0910($uid);

        $user = db("user")->where("id=$uid")->field('invitation_coin')->find();
        $invitation_coin = $user['invitation_coin'];
        if (!$user || $invitation_coin <= 0) {
            $invitation_coin = 0;
        }

        $data = array(
            'invitation_coin' => $invitation_coin,
            'uid'             => $uid,
            'invite_url'      => 'http://' . $_SERVER['HTTP_HOST'] . '/api/download_api/index?invite_code=' . $invite_code,
        );

        //邀请规则
        $category = db('portal_category')->where("name='邀请奖励规则'")->find();
        if ($category) {
            $sex_type = db('portal_category_post')->where("category_id=" . $category['id'])->find();
            $oldTagIds = db('portal_post')->where("id=" . $sex_type['post_id'])->find();
            $rules = html_entity_decode($oldTagIds['post_content']);
        } else {
            $rules = '';
        }

        $this->assign("rules", $rules);

        $this->assign('uid', $uid);
        $this->assign("data", $data);
        return $this->fetch();
    }

    //我的邀请人充值金额和邀请人列表
    public function inviter()
    {
        $uid = input("param.uid");
        $type = input("param.type") ? input("param.type") : '1';


        if ($type == '2') {
            $invite_user_list = Db::name('invite_record')->alias('i')
                ->field('u.user_nickname,l.create_time,l.money,u.id')
                ->join('user u', 'i.invite_user_id=u.id')
                ->join('invite_profit_record l', 'l.invite_user_id=i.invite_user_id')
                ->where('i.user_id', '=', $uid)
                ->limit(20)
                ->order("l.create_time desc")
                ->select();
        } else {
            $invite_user_list = Db::name('invite_record')->alias('i')
                ->field('u.user_nickname,u.avatar,u.create_time,u.id')
                ->join('user u', 'i.invite_user_id=u.id')
                ->where('i.user_id', '=', $uid)
                ->limit(20)
                ->order("u.create_time desc")
                ->select();

            foreach ($invite_user_list as &$v) {
                $money = Db::name('invite_profit_record')
                    ->where("invite_user_id=" . $v['id'] . " and user_id=" . $uid)
                    ->field("sum(money) as money")
                    ->find();
                $v['money'] = $money['money'] ? $money['money'] : '0';
            }
        }

        $this->assign("list", $invite_user_list);
        $this->assign('uid', $uid);
        $this->assign('type', $type);
        return $this->fetch();
    }

    //邀请人分页
    public function inviter_page()
    {

        $uid = input("param.uid");
        $type = input("param.type");
        $page = input("param.page") * 20;
        if ($type == '2') {
            //用户充值详情
            $invite_user_list = Db::name('invite_record')->alias('i')
                ->field('u.user_nickname,l.create_time,l.money,u.id')
                ->join('user u', 'i.invite_user_id=u.id')
                ->join('invite_profit_record l', 'l.invite_user_id=i.invite_user_id')
                ->where('i.user_id', '=', $uid)
                ->limit($page, 20)
                ->order("l.create_time desc")
                ->select();

        } else {
            //用户列表
            $invite_user_list = Db::name('invite_record')->alias('i')
                ->field('u.user_nickname,u.avatar,u.create_time,u.id')
                ->join('user u', 'i.invite_user_id=u.id')
                ->where('i.user_id', '=', $uid)
                ->limit($page, 20)
                ->order("u.create_time desc")
                ->select();

            foreach ($invite_user_list as &$v) {
                $money = Db::name('invite_profit_record')
                    ->where("invite_user_id=" . $v['id'] . " and user_id=" . $uid)
                    ->field("sum(money) as money")
                    ->find();
                $v['money'] = $money['money'] ? $money['money'] : '0';
                $v['create_time'] = date('Y/m/d', $v['create_time']);

            }
        }


        echo json_encode($invite_user_list);
    }

    /*
     * 邀请收益     //invited_withdrawal_less     invited _withdrawal_more
     * */
    public function rewards()
    {

        $uid = input("param.uid");
        //user_cash_account
        $user = db("user")->where("id=$uid")->field('invitation_coin,user_status')->find();
        if ($user['user_status'] == 0) {
            echo lang('Unable_withdraw_cash_account_blackout');
            exit;
        }
        $result = db("invite_cash_record")->where("uid=$uid")->order("addtime desc")->select();
        $invitation_coin = $user['invitation_coin'];
        if (!$user || $invitation_coin <= 0) {
            $invitation_coin = 0;
        } else {
            $invitation_coin = round($invitation_coin, 2);
        }
        $config = load_cache('config');
        //最低提现金额

        $data['less'] = $config['invited_withdrawal_less'];
        //最高提现金额
        $data['more'] = $config['invited_withdrawal_more'];
        //每日最大提现次数
        $data['cash_day_limit'] = $config['cash_day_limit'];
        $pays = db('user_cash_account')->where('uid=' . $uid)->find();

        $this->assign("invitation_coin", $invitation_coin);
        $this->assign('list', $result);
        $this->assign('pay', $pays);
        $this->assign('uid', $uid);
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function add_rewards()
    {
        $val = input("param.val");
        $uid = input("param.uid");
        $root = array('msg' => lang('Parameter_transfer_error'), 'status' => '0');

        if (!$uid) {
            $root['msg'] = lang('login_timeout');
            echo json_encode($root);
            exit;
        }
        $pay = db('user_cash_account')->where('uid=' . $uid)->field('wx,pay,id')->find();

        if ($pay['wx'] == '' and $pay['pay'] == '') {
            $root['msg'] = lang('Please_fill_in_Alipay_wechat_account');
            echo json_encode($root);
            exit;
        }
        //判断是否是100倍的

        if ($val % 100 != 0) {
            //是100的整数
            $root['msg'] = lang('100_times_to_withdraw');
            echo json_encode($root);
            exit;
        }
        $data = array(
            'uid'     => $uid,
            'coin'    => $val,
            'pay'     => $pay['id'],
            'addtime' => NOW_TIME,
            'status'  => 0,
        );

        $cash_record = db("invite_cash_record")->where('uid', '=', $uid)->where('status', '=', 0)->select();
        //查询提现记录
        if ($cash_record) {
            $root['msg'] = lang('Failed_withdrawal_records');
            echo json_encode($root);
            exit;
        }
        $config = load_cache('config');

        $timetoday = strtotime(date("Y-m-d", time()));//今天0点的时间点
        $time = $timetoday + 3600 * 24;//今天24点的时间点，两个值之间即为今天一天内的数据
        $count = db("invite_cash_record")->where('uid', '=', $uid)->where("addtime >='$timetoday' and addtime <= '$time'")->count();
        if ($count > $config['cash_day_limit']) {
            $root['msg'] = lang('Today_delivery_has_been_online');
            echo json_encode($root);
            exit;
        }
        //是否足够余额
        $user_info = db('user')->where('id=' . $uid)->field('invitation_coin')->find();
        if ($user_info && $user_info['invitation_coin'] < $val) {
            $root['msg'] = lang('Insufficient_Balance');
            echo json_encode($root);
            exit;
        }

        //最低提现金额
        $less = $config['invited_withdrawal_less'];
        //最高提现金额
        $more = $config['invited_withdrawal_more'];
        if ($less > $val) {
            $root['msg'] = lang('Minimum_cash_withdrawal_each_time') . $less . lang('ADMIN_MONEY');
            echo json_encode($root);
            exit;
        }
        if ($more < $val) {
            $root['msg'] = lang('Maximum_cash_withdrawal_each_time') . $more . lang('ADMIN_MONEY');
            echo json_encode($root);
            exit;
        }
        db('user')->where('id=' . $uid)->dec('invitation_coin', $val)->update();
        $result = db("invite_cash_record")->insert($data);

        if ($result) {
            $root['status'] = 1;
            $root['msg'] = lang('Withdrawal_succeeded_waiting_approval');
        } else {
            $root['msg'] = lang('Withdrawal_failed');
        }
        echo json_encode($root);
    }

    //分享中心
    public function sharing_center()
    {
        $uid = input("param.uid");
        //邀请男性好友人数
        $invite_reg_reward_man = Db::name('invite_record')
            ->alias('i')
            ->join('user u', 'u.id = i.invite_user_id')
            ->where(['i.user_id' => $uid, 'u.sex' => 1])->count();
        //邀请女性好友人数
        $invite_reg_reward_female = Db::name('invite_record')
            ->alias('i')
            ->join('user u', 'u.id = i.invite_user_id')
            ->where(['i.user_id' => $uid, 'u.sex' => 2])->count();
        //获取用户人数
        $invite_user_list = Db::name('invite_record')->where('user_id', '=', $uid)->select();

        $sum = 0;

        foreach ($invite_user_list as &$v) {
            //用户奖励总数
            $v['income_total'] = db('invite_profit_record')->where('user_id', '=', $uid)->where('invite_user_id', '=', $v['invite_user_id'])->sum('money');

            $sum += $v['income_total'];
        }
        $day = date('Y-m-d');
        $sql = Db::query("SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as name , sum(money) as value FROM cmf_invite_profit_record GROUP BY name ");
        foreach ($sql as $val) {
            if ($val['name'] == $day) {
                $daynum = $val['value'];
            }
        }
        if (empty($daynum)) {
            $daynum = 0;
        }

        $data = array(
            'sum'                      => count($invite_user_list),
            'reward_total'             => number_format($sum, 2),
            'invite_reg_reward_man'    => $invite_reg_reward_man,
            'invite_reg_reward_female' => $invite_reg_reward_female,
            'uid'                      => $uid,
            'daynum'                   => $daynum,//当日收益
        );

        $this->assign("data", $data);
        return $this->fetch();
    }

    //奖励明细
    public function detailed()
    {
        $uid = input("param.uid");
        //获取用户人数
        $invite_user_list = Db::name('invite_record')->where('user_id', '=', $uid)->select();

        $sum = 0;

        foreach ($invite_user_list as &$v) {
            //用户奖励总数
            $v['income_total'] = db('invite_profit_record')->where('user_id', '=', $uid)->where('invite_user_id', '=', $v['invite_user_id'])->sum('money');

            $sum += $v['income_total'];
        }

        $peo = Db::name('invite_profit_record')
            ->alias('i')
            ->join('user u', 'u.id = i.invite_user_id')
            ->field('u.user_nickname,i.type,i.money')
            ->where(['i.user_id' => $uid])->order('i.create_time desc')->limit(100)->select();

        $data = array(
            'sum'          => count($invite_user_list),
            'reward_total' => number_format($sum, 2),
            'uid'          => $uid,
            'peo'          => $peo

        );

        $this->assign("data", $data);
        return $this->fetch();
    }

    public function invitation_user_sex()
    {
        $uid = input("param.uid");
        $type = input("param.type");
        //获取用户人数
        $invite_user_list = Db::name('invite_record')->where('user_id', '=', $uid)->select();

        $sum = 0;

        foreach ($invite_user_list as &$v) {
            //用户奖励总数
            $v['income_total'] = db('invite_profit_record')->where('user_id', '=', $uid)->where('invite_user_id', '=', $v['invite_user_id'])->sum('money');

            $sum += $v['income_total'];
        }

        if ($type == 1) {
            $sex = lang('Men_I_invited');
            //邀请男性好友
            $invite_reg_reward_man = Db::name('invite_record')
                ->alias('i')
                ->join('user u', 'u.id = i.invite_user_id')
                ->field('u.id,u.user_nickname,u.create_time')
                ->where(['i.user_id' => $uid, 'u.sex' => 1])->order('u.create_time desc')->limit(100)->select();
        } else if ($type == 2) {
            $sex = lang('Women_I_invited');
            //邀请女性好友
            $invite_reg_reward_man = Db::name('invite_record')
                ->alias('i')
                ->join('user u', 'u.id = i.invite_user_id')
                ->field('u.id,u.user_nickname,u.create_time')
                ->where(['i.user_id' => $uid, 'u.sex' => 2])->order('u.create_time desc')->limit(100)->select();
        } else {
            return lang('Page_error');
        }

        $data = array(
            'sum'          => count($invite_user_list),
            'reward_total' => number_format($sum, 2),
            'uid'          => $uid,
            'peo'          => $invite_reg_reward_man,
            'sex'          => $sex,

        );

        $this->assign("data", $data);
        return $this->fetch();
    }

    //收入明细
    public function income()
    {
        $uid = input("param.uid");
        $money = Db::name('invite_profit_record')->alias('i')
            ->join('user u', 'u.id = i.invite_user_id')
            ->field('u.id,u.user_nickname,i.create_time,i.money')
            ->where(['i.user_id' => $uid])
            ->order('i.create_time desc')
            ->limit(20)
            ->select();
        $sum = Db::name('invite_profit_record')->where(['user_id' => $uid])->sum("money");
        $this->assign("data", $money);
        $this->assign("sum", $sum);
        $this->assign("uid", $uid);
        return $this->fetch();
    }

    //收入明细分页
    public function income_page()
    {
        $uid = input("param.uid");
        $page = input("param.page") * 20;

        $money = Db::name('invite_profit_record')->alias('i')
            ->join('user u', 'u.id = i.invite_user_id')
            ->field('u.id,u.user_nickname,i.create_time,i.money')
            ->where(['i.user_id' => $uid])
            ->order('i.create_time desc')
            ->limit($page, 20)
            ->select();
        echo json_encode($money);
    }

    //绑定账号
    public function insert_rewards()
    {
        $uid = input("param.uid");
        $pay = input("param.pay");
        $wx = input("param.wx");
        $name = input("param.name");
        $root = array('msg' => lang('Parameter_transfer_error'), 'status' => '0');
        if (!$uid) {
            $root['msg'] = lang('login_timeout');
            echo json_encode($root);
            exit;
        }

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        $data = array(
            'uid'     => $uid,
            'pay'     => $pay,
            'wx'      => $wx,
            'addtime' => NOW_TIME,
            'name'    => $name,
        );
        if ($pays) {
            $result = db('user_cash_account')->where('uid=' . $uid)->update($data);
        } else {
            $result = db("user_cash_account")->insert($data);
        }

        if ($result) {
            $root['status'] = 1;
            $root['msg'] = lang('Binding_succeeded');
        } else {
            $root['msg'] = lang('Binding_failed');
        }
        echo json_encode($root);
    }

    //查询提现记录
    public function withdrawal()
    {
        //invite_cash_record
        $uid = input("param.uid");
        $money = Db::name('invite_cash_record')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->join('user_cash_account a', 'a.uid = i.uid')
            ->field('i.addtime,i.coin,i.status')
            ->where(['i.uid' => $uid])
            ->order('i.addtime desc')
            ->limit(20)
            ->select();


        $this->assign("list", $money);
        $this->assign("uid", $uid);
        return $this->fetch();
    }

    //提现记录分页
    public function withdrawal_page()
    {
        $uid = input("param.uid");
        $page = input("param.page") * 20;
        $money = Db::name('invite_cash_record')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->join('user_cash_account a', 'a.uid = i.uid')
            ->field('i.addtime,i.coin,a.*,i.status')
            ->where(['i.uid' => $uid])
            ->order('i.addtime desc')
            ->limit($page, 20)
            ->select();
        foreach ($money as &$v) {
            if ($v['status'] == '1') {
                $v['name'] = lang('Withdrawn_cash');
            } elseif ($v['status'] == '2') {
                $v['name'] = lang('Withdrawal_failed');
            } else {
                $v['name'] = lang('CHECK_LOADING');
            }
        }
        echo json_encode($money);

    }

    //綁定頁面
    public function bound_pay()
    {
        $uid = input("param.uid");
        //user_cash_account
        $user = db("user")->where("id=$uid")->field('user_nickname')->find();

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();

        $this->assign('pay', $pays);
        $this->assign('uid', $uid);
        $this->assign('user', $user);
        return $this->fetch();
    }

}
