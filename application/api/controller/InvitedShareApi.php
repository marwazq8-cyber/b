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
class InvitedShareApi extends Base
{


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
        //邀请总人数
        $count = db('invite_record')->where("user_id=" . $uid)->count();
        $data = array(
            'invitation_coin' => $invitation_coin,
            'uid' => $uid,
            'count' => $count,
            'invite_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/api/download_api/index?invite_code=' . $invite_code,
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

    //邀请的用户
    public function user()
    {
        $uid = input("param.uid");
        //邀请记录
        $invite_user_list = db('invite_record')->alias('i')
            ->field('u.avatar,u.id,u.user_nickname,u.level,l.level_icon')
            ->join(config('database.prefix') . 'user u', 'i.invite_user_id=u.id')
            ->join(config('database.prefix') . 'level l', 'l.level_name=u.level')
            ->where('i.user_id', '=', $uid)
            ->order('i.create_time desc')
            ->select();
        foreach ($invite_user_list as &$v) {
            //用户奖励总数
            $v['income_total'] = db('invite_profit_record')->where('user_id', '=', $uid)->where('invite_user_id', '=', $v['id'])->sum('income');
        }
        $this->assign("data", $invite_user_list);
        return $this->fetch();
    }

    //邀请提现页面
    public function withdrawal()
    {

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user = db("user")->where("id=$uid")->field('invitation_coin')->find();

        $invitation_coin = $user['invitation_coin'];
        $data['uid'] = $uid;
        $data['token'] = $token;
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

        //余额
        $data['invitation_coin'] = $invitation_coin;

        //提现记录
        $page = intval(input("param.page")) ? intval(input("param.page")) : 1;
        $p = ($page - 1) * 20;

        $list = Db::name('invite_cash_record')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->join('user_cash_account a', 'a.uid = i.uid')
            ->field('i.addtime,i.coin,i.status')
            ->where(['i.uid' => $uid])
            ->order('i.addtime desc')
            ->limit($p, 20)
            ->select();

        $this->assign("data", $data);

        $this->assign("list", $list);
        return $this->fetch();
    }

    //提现
    public function add_rewards()
    {
        $val = input("param.val");
        $uid = input("param.uid");
        $token = input("param.token");
        $root = array('msg' => lang('Parameter_transfer_error'), 'status' => '0');
        $user = check_login_token($uid, $token, ['invitation_coin']);

        $pay = db('user_cash_account')->where('uid=' . $uid)->field('wx,pay,id')->find();

        if ($pay['wx'] == '' and $pay['pay'] == '') {
            $root['msg'] = lang('Please_fill_in_Alipay_wechat_account');
            $root['status'] = 3;
            echo json_encode($root);
            exit;
        }
        //判断是否是100倍的

        /*  if ($val % 100 != 0) {
              //是100的整数
              $root['msg'] = '必须是100的倍数才能提现！';
              echo json_encode($root);
              exit;
          }*/
        $data = array(
            'uid' => $uid,
            'coin' => $val,
            'pay' => $pay['id'],
            'addtime' => NOW_TIME,
            'status' => 0,
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
            'uid' => $uid,
            'pay' => $pay,
            'wx' => $wx,
            'addtime' => NOW_TIME,
            'name' => $name,
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


}