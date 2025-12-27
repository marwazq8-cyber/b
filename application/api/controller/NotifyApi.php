<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/29
 * Time: 11:12
 */

namespace app\api\controller;


use alipay_app_pay;
use kuaijiealipay_app_pay;
use qianyingalipay_app_pay;
use qianyingnewalipay_app_pay;
use wechat_app_pay;
use payermax_pay;
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class NotifyApi
{
    // payermax回调
    public function payermax_notify(){
        require_once DOCUMENT_ROOT . "/system/pay_class/payermax_pay_menu.php";
        $o = new payermax_pay();

        $notify = file_get_contents('php://input');
        $post = json_decode($notify, true);
        bogokjLogPrint("payermax_notify", $notify);
        $o->notify($post);
    }
    //快接支付回调
    public function kuaijie_notify(){

        require_once DOCUMENT_ROOT."/system/pay_class/kuaijiealipay_app_pay_menu.php";
        $o = new kuaijiealipay_app_pay();
        bogokjLogPrint("kuaijie_notify",json_encode($_REQUEST));
        $o->notify($_REQUEST);
    }

    //官方支付宝回调
    public function alipay_notify(){

        require_once DOCUMENT_ROOT."/system/pay_class/alipay_app_pay_menu.php";
        $o = new alipay_app_pay();
        bogokjLogPrint("alipay_notify",json_encode($_REQUEST));
        $o->notify($_REQUEST);
    }
    //官方微信回调
    public function wechatpay_notify(){

        require_once DOCUMENT_ROOT."/system/pay_class/wechat_app_pay_menu.php";
        $o = new wechat_app_pay();
        bogokjLogPrint("wechatpay_notify",json_encode($_REQUEST));
        $o->notify($_REQUEST);
    }

    public function qianyingnew_notify(){
        require_once DOCUMENT_ROOT."/system/pay_class/qianyingnewalipay_app_pay_menu.php";
        $o = new qianyingnewalipay_app_pay();
        bogokjLogPrint("qianyingnew_notify",json_encode($_REQUEST));
        $o->notify($_REQUEST);
    }

    public function apple_notify(){
        $result = array('code' => 1, 'msg' => '');
        //$uid = intval(input('param.uid'));
        //$token = trim(input('param.token'));
        $order_id = trim(input('param.order_id'));
        //$user_info = check_login_token($uid, $token);
        $result = [
            'order_id'=>$order_id,
        ];
        require_once DOCUMENT_ROOT."/system/pay_class/applepay_app_pay_menu.php";
        $apple = new \applepay_app_pay();
        bogokjLogPrint("apple_notify",json_encode($result));
        $apple->notify($result);
        //return_json_encode($result);
    }
}