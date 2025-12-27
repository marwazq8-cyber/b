<?php

namespace app\api\controller;

use app\api\controller\Base;
use app\vue\model\ShopModel;
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST BOGO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

/**
 * Created by 山东布谷鸟网络科技有限公司.
 * User: weipeng
 * Date: 2018/8/17
 * Time: 00:14
 */
class VipApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        // 允许所有来源访问
        header('Access-Control-Allow-Origin:*');

        $this->ShopModel = new ShopModel();
    }

    /**
    * 获取访客--是否开启了vip权限
     */
    public function visitor_authority() {
        $result = array('code' => 1, 'msg' => '','data'=>array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        check_login_token($uid, $token);
        $is_visitors = intval(get_user_vip_authority($uid,'is_visitors'));

        $result['data']= array(
            'status' => $is_visitors == 1 ? 1 : 0
        );

        return_json_encode($result);
    }

    public function get_vip_page_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['vip_end_time']);

        $vip_day = intval(($user_info['vip_end_time'] - NOW_TIME) / (60 * 60 * 24));
        if ($vip_day < 0) {
            $vip_day = lang('Not_opened');
        }
        $result['vip_time'] = $vip_day;
        //支付方式
        $result['pay_list'] = db('pay_menu')->field('id,pay_name,icon')->where('status', '=', 1)->select();
        $result['vip_rule'] = db('vip_rule')->select();

        foreach ($result['vip_rule'] as &$v) {
            if ($v['money'] != 0 && $v['day_count'] != 0) {
                $day_money = $v['money'] / $v['day_count'];
            } else {
                $day_money = $v['money'];
            }
            $v['day_money'] = '¥' . round($day_money, 2);
        }

        //查询特权列表
        $result['detail_list'] = db('vip_rule_details')->select();

        return_json_encode($result);
    }

    public function index()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $user_info = get_user_base_info($uid, ['vip_end_time'], 1);
        //系统金币单位名称
        $config = load_cache('config');
        $coin_name = $config['currency_name'];
        $vip_day = intval(($user_info['vip_end_time'] - time()) / (60 * 60 * 24));

        $list = db('vip_rule')->select();
        foreach ($list as &$v) {
            $names = $v['money'] / $v['day_count'];
            $v['mean'] = round($names, 2);
        }
        $this->assign('user_info', $user_info);
        $this->assign('vip_day', $vip_day > 0 ? $vip_day : 0);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        $this->assign('list', $list);
        $this->assign('coin_name', $coin_name);
        return $this->fetch();
    }

    //购买会员
    public function buy_vip()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $vip_id = intval(input('param.id'));

        $user_info = check_login_token($uid, $token,['vip_end_time']);

        $rule = db('vip_rule')->find($vip_id);

        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = lang('Rule_does_not_exist');
            return_json_encode($result);
        }

        $user_info = get_user_base_info($uid, ['vip_end_time'], 1);

        if ($user_info['coin'] < $rule['money']) {

            $result['code'] = 0;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        $res = db('user')->where('id', '=', $uid)->setDec('coin', $rule['money']);
        if (!$res) {

            $result['code'] = 0;
            $result['msg'] = lang('Purchase_failed');
            return_json_encode($result);
        }

        $vip_time = $rule['month'] * 60 * 60 * 24*30;
        if ($user_info['vip_end_time'] > time()) {
            
            $shop_time = $user_info['vip_end_time'] + $vip_time;
            db('user')->where('id', '=', $uid)->setInc('vip_end_time', $vip_time);
        } else {

            $vip_time = time() + $vip_time;

            $shop_time =  $vip_time;

            db('user')->where('id', '=', $uid)->setField('vip_end_time', $vip_time);
        }

        // 获取vip商城
        $where = "s.status=1 and s.is_vip=1";
        // 获取商城列表
        $list = $this->ShopModel ->get_shop_vip($where);

        foreach ($list as $v) {
            // 查询商品是否续费的信息
            $shop_user_where="uid=".$uid." and shop_id=".$v['id']." and type=".$v['type']." and status=1 and endtime >".NOW_TIME;

            $shop_log_status = $this->ShopModel -> get_renewal($shop_user_where);

            if($shop_log_status){
                 // 修改购买商品
                $this->ShopModel ->upd_shop_status("id=".$shop_log_status['id'],array('endtime' => $shop_time));
            }else{
                $shop_data=array(
                    'uid' =>$uid,
                    'shop_id' =>$v['id'],
                    'shop_price_id' =>0,
                    'coin'  =>0,
                    'month' =>0,
                    'type' =>$v['type'],
                    'addtime' =>NOW_TIME,
                    'endtime' =>$shop_time,
                    'is_renewal' =>0,
                    'status' =>1,
                );
                        
                $this->ShopModel ->add_shop_log($shop_data);
            }
        }

        return_json_encode($result);
    }
}