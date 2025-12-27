<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 11:23
 */

namespace app\api\controller;
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

use Psr\Log\LogLevel;
use think\helper\Time;

class PayApi extends Base
{

    //充值金币
    public function pay_vip()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rid'));//充值规则ID

        $user_info = check_login_token($uid, $token);

        //充值渠道
        $pay_type = db('pay_menu')->find($pid);

        if (!$pay_type) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_type_does_not_exist');
            return_json_encode($result);
        }

        //充值规则
        $rule = db('vip_rule')->find($rule_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = lang('Purchase_rule_does_not_exist');
            return_json_encode($result);
        }

        //$notice_id = NOW_TIME . $uid . rand(0000,9999);//订单号码
        $time_order = explode(" ", microtime());
        $time_order = $time_order [1] . ($time_order [0] * 1000);
        $time2 = explode(".", $time_order);
        $time_order = $time2 [0];
        $notice_id = $time_order . $uid . rand(0000, 9999);//订单号码
        $order_info = [
            'uid'         => $uid,
            'money'       => $rule['money'],
            'coin'        => 0,
            'refillid'    => $rule_id,
            'addtime'     => NOW_TIME,
            'status'      => 0,
            'type'        => 7777777,
            'order_id'    => $notice_id,
            'pay_type_id' => $pid,
        ];


        //增加订单记录
        if (!db('user_charge_log')->insert($order_info)) {
            $result['code'] = 0;
            $result['msg'] = 'Failed to generate order, please try again!';
            return_json_encode($result);
        }

        $class_name = $pay_type['class_name'];
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $pay = $o->get_payment_code($pay_type, $rule, $notice_id);

        $result['pay'] = $pay;

        return_json_encode($result);
    }

    //充值金币
    public function pay()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rid'));//充值规则ID
        $os = input('param.os');//充值系统

        $user_info = check_login_token($uid, $token);

        //充值渠道
        $pay_type = db('pay_menu')->find($pid);

        if (!$pay_type) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_type_does_not_exist');
            return_json_encode($result);
        }

        if (!$pay_type['is_google_pay']) {
            if (empty($pay_type['merchant_id'])) {
                $result['code'] = 0;
                $result['msg'] = lang('Payment_merchant_No_empty');
                return_json_encode($result);
            }

            if (empty($pay_type['private_key'])) {
                $result['code'] = 0;
                $result['msg'] = lang('Payment_private_key_is_empty');
                return_json_encode($result);
            }

            if (empty($pay_type['app_id'])) {
                $result['code'] = 0;
                $result['msg'] = lang('Appid_is_empty');
                return_json_encode($result);
            }
        }

        //充值规则
        $rule = db('user_charge_rule')->find($rule_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_rule_does_not_exist');
            return_json_encode($result);
        }

        // $notice_id = NOW_TIME . $uid;//订单号码
        $time_order = explode(" ", microtime());
        $time_order = $time_order [1] . ($time_order [0] * 1000);
        $time2 = explode(".", $time_order);
        $time_order = $time2 [0];
        $notice_id = $time_order . $uid . rand(0000, 9999);//订单号码
        $order_info = [
            'uid'         => $uid,
            'money'       => $rule['money'],
            'coin'        => $rule['coin'] + $rule['give'],
            'refillid'    => $rule_id,
            'addtime'     => NOW_TIME,
            'status'      => 0,
            'type'        => $pid,
            'order_id'    => $notice_id,
            'pay_type_id' => $pid,
            'os'          => $os,
        ];

        //增加订单记录
        if (!db('user_charge_log')->insert($order_info)) {
            $result['code'] = 0;
            $result['msg'] = 'Failed to generate order, please try again!';
            return_json_encode($result);
        }

        $class_name = $pay_type['class_name'];

        if (!$pay_type['is_google_pay']) {

            bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
            $o = new $class_name;
            if ($class_name == 'payermax_pay') {

                $pay = $o->get_payment_code($pay_type, $rule, $notice_id, $uid);
            } else {
                $pay = $o->get_payment_code($pay_type, $rule, $notice_id);
            }

            $result['pay'] = $pay;
        } else {
            $result['pay']['order_id'] = $notice_id;
        }

        return_json_encode($result);
    }

    public function pay_display_html()
    {

        $order_id = $_REQUEST['order_id'];
        $class_name = $_REQUEST['class_name'];

        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $o->display_html($order_id);
    }

    //PayPal获取token
    public function check_pay_pal_order_status()
    {

        $result = array('code' => 1, 'msg' => '');

        $order_id = trim($_REQUEST['order_id']);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $r_id = intval(input('param.r_id'));

        $test_type = OPEN_SANDBOX == 1 ? 0 : 1;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay/paypal/fun.php");
        $token_data = pay_pal_access_token($test_type);

        if (!$token_data) {
            $result['code'] = 0;
            $result['msg'] = lang('Get_PayPal_verification_token_error');
            return_json_encode($result);
        }

        $pay_result = pay_pal_get_curl_order($order_id, $token_data['access_token'], $test_type);
        if (!$pay_result) {
            $result['code'] = 0;
            $result['msg'] = lang('No_order_found');
            return_json_encode($result);
        }

        $rule = db('user_charge_rule')->find($r_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_rule_query_error');
            return_json_encode($result);
        }

        $exits = db('user_charge_log')->where('order_id', '=', $order_id)->find();
        if ($exits) {
            $result['code'] = 0;
            $result['msg'] = lang('Orders_processed');
            return_json_encode($result);
        }

        $notice_id = $order_id;//订单号码
        $order_info = [
            'uid'           => $uid,
            'money'         => $rule['money'],
            'coin'          => $rule['coin'] + $rule['give'],
            'refillid'      => $r_id,
            'addtime'       => NOW_TIME,
            'status'        => 1,
            'type'          => 11111111,
            'order_id'      => $notice_id,
            'pay_type_id'   => 1111111,
            'pay_pal_money' => $pay_result['transactions'][0]['amount']['total']
        ];

        if ($pay_result['state'] != 'approved') {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_failed');
            return_json_encode($result);
        }

        //增加订单记录
        $add_log = db('user_charge_log')->insertGetId($order_info);
        if ($add_log) {

            $coin = $rule['coin'] + $rule['give'];
            //增加用户钻石
            db('user')->where('id', '=', $order_info['uid'])->setInc('coin', $coin);
            //邀请奖励分成
            invite_back_now_recharge($order_info['money'], $order_info['uid'], $add_log);
            //增加代理用户分成数据
            agent_order_recharge($order_info['money'], $order_info['uid'], $add_log);

        }

        return_json_encode($result);

    }

    public function wechat_verification()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token);
        $out_trade_no = input('order_id');
        //$out_trade_no = '1600831950362';
        $order_info = db('user_charge_log')->where('order_id', '=', $out_trade_no)->find();
        if ($order_info['status'] == 0) {
            $pay_info = db('pay_menu')->find($order_info['pay_type_id']);

            // dump($pay_info);die();
            require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';

            $noceStr = md5(rand(100, 1000) . time());//获取随机字符串

            $paramarr = [
                'appid'        => $pay_info['app_id'],
                'mch_id'       => $pay_info['merchant_id'],
                'out_trade_no' => $out_trade_no,
                'nonce_str'    => $noceStr,
            ];
            ksort($paramarr);
            $key = trim($pay_info['private_key']);
            $sign = sign($paramarr, $key);//生成签名
            $paramarr['sign'] = $sign;
            $paramXml = "<xml>";
            foreach ($paramarr as $k => $v) {
                $paramXml .= "<" . $k . ">" . $v . "</" . $k . ">";

            }
            $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
            $paramXml .= "</xml>";
            $resultXmlStr = $this->curl_https($url, $paramXml);
            $arrayInfo = xmlToArray($resultXmlStr);
            if ($arrayInfo['return_code'] == 'SUCCESS') {
                pay_call_service($arrayInfo["out_trade_no"]);
            } else {
                $result['code'] = 0;
                $result['msg'] = lang('Recharge_failed');
            }
        }
        return_json_encode($result);
        // print_r($arrayInfo);
    }

    public function curl_https($url, $data = array(), $header = array(), $timeout = 30)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);

        if ($error = curl_error($ch)) {
            die($error);
        }
        curl_close($ch);

        return $response;

    }

    /*
     * 内购充值创建订单*/
    public function apple_pay()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('uid');
        $token = input('token');
        //$pid = isset($this->param_info['pid'])?$this->param_info['pid']:0;
        $rule_id = input('rid');//充值规则

        $user_info = check_login_token($uid, $token);

        //充值规则
        $rule = db('user_charge_rule')->find($rule_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_rule_does_not_exist');
            return_json_encode($result);
        }

        if ($rule['name'] == "0" || $rule['name'] == " ") {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_rule_does_not_exist');
            return_json_encode($result);
        }

        $notice_id = NOW_TIME . $uid;//订单号码

        //防止重复下单
        $exits = db('user_charge_log')->where('order_id=' . $notice_id)->find();
        if ($exits) {
            $result['code'] = 0;
            $result['msg'] = lang('Frequent_operation');
            return_json_encode($result);
        }

        $order_info = [
            'uid'         => $uid,
            'money'       => $rule['ios_money'],
            'coin'        => $rule['apple_pay_coin'] + $rule['give'],
            'refillid'    => $rule_id,
            'addtime'     => NOW_TIME,
            'status'      => 0,
            'type'        => 0,
            'order_id'    => $notice_id,
            'pay_type_id' => 13,
        ];
        //增加订单记录
        db('user_charge_log')->insert($order_info);
        $result['order_id'] = $notice_id;
        $result['name'] = $rule['apple_pay_name'];
        return_json_encode($result);
    }

    /*
     * 余额充值*/
    public function request_pay_coin()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //$pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rid'));//充值规则ID

        $user_info = check_login_token($uid, $token, ['income']);

        if ($user_info['is_auth'] != 1) {
            $result['msg'] = lang('Withdrawal_after_certification');
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Due_to_suspected_violation');
            return_json_encode($result);
        }

        //充值规则
        $rule = db('user_charge_rule')->find($rule_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_rule_does_not_exist');
            return_json_encode($result);
        }
        $money = $rule['income_num'];
        if ($user_info['income'] < $money) {
            $result['code'] = 0;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        $inc_income = db('user')->where('id', '=', $uid)->where('income >= ' . $money)->setDec('income', $money);
        if ($inc_income) {
            //增加金币
            $coin = $rule['coin'] + $rule['give'];
            db('user')->where('id', '=', $uid)->setInc('coin', $coin);
            // $notice_id = NOW_TIME . $uid;//订单号码
            $time_order = explode(" ", microtime());
            $time_order = $time_order [1] . ($time_order [0] * 1000);
            $time2 = explode(".", $time_order);
            $time_order = $time2 [0];
            $notice_id = $time_order . $uid . rand(0000, 9999);//订单号码
            $order_info = [
                'uid'         => $uid,
                'money'       => $rule['money'],
                'coin'        => $coin,
                'refillid'    => $rule_id,
                'addtime'     => NOW_TIME,
                'status'      => 1,
                'type'        => 1,
                'order_id'    => $notice_id,
                'pay_type_id' => 1,
            ];
            //增加订单记录
            db('user_charge_log')->insert($order_info);
            $result['msg'] = lang('Recharge_succeeded');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Insufficient_Balance');
        }

        return_json_encode($result);
    }


    public function requestGoogleVerification()
    {
        $uid = input('param.uid', 0, 'intval');
        $token = input('param.token', '', 'trim');

        $productId = input('productId', 0, 'trim');
        $orderToken = input('purchaseToken', '', 'trim');
        $packageName = input('packageName', '', 'trim');
        $appOrderId = input('appOrderId', '', 'trim');

        if (empty($productId) || empty($orderToken) || empty($packageName)) {

            bogokjLogPrint('google_pay', '参数错误！');

            return_json_encode_data('', 0, 'Param error！');
        }

        // 锁防止重复请求没有消费掉的商品重复加钱
        $lockKey = md5($appOrderId . $productId . $orderToken . $packageName);

//        $exitsRequestTask = redis_is_lock($lockKey);
//        if ($exitsRequestTask) {
//            return_json_encode_data('', 0, 'Repeat request 1001!');
//        }

        redis_lock($lockKey, true, 30);

        $user_info = check_login_token($uid, $token);

        $payRule = db('user_charge_rule')->where(['google_pay_id' => $productId])->find();
        if (!$payRule) {
            return_json_encode_data(['p' => $productId], 0, 'The purchased item is invalid!');
        }

        $client = new \Google\Client();

        $client->setAuthConfig(config('GoogleConfig.authConfigPath'));

        $refreshToken = config('GoogleConfig.refreshToken');
        //$refreshToken = $client->getRefreshToken();

        $accessToken = '';
        ## 可以用此行代码获取AccessToken
        $result = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        bogokjLogPrint('google_pay', ['status' => 'fetchAccessTokenWithRefreshToken', 'result' => $result, 'refreshToken' => $refreshToken]);

        if (isset($result['access_token'])) {
            $accessToken = $result['access_token'];
        } else {
            return_json_encode_data('', 0, 'Miss access tokens！');
        }

        $url = 'https://www.googleapis.com/androidpublisher/v3/applications/' . $packageName . '/purchases/products/' . $productId . '/tokens/' . $orderToken . '?access_token=' . $accessToken;

        try {
            $result_data = file_get_contents($url);
        } catch (\Exception $e) {
            redis_unlock($lockKey);

            bogokjLogPrint('google_pay', ['url' => $url, 'errorMsg' => $e->getMessage()]);

            return_json_encode_data('', 0, 'HTTP request failed! HTTP/1.0 400 Bad Request');
        }

        if ($result_data === false) {
            redis_unlock($lockKey);
            return_json_encode_data('', 0, print_r(error_get_last(), true));
        }

        $data = json_decode($result_data, true);

        bogokjLogPrint('google_pay', ['status' => '请求 api 返回结果', 'data' => $result_data]);

//            {
//                "error": {
//                    "code": 400,
//                    "message": "The purchase token does not match the product ID.",
//                    "errors": [
//                        {
//                            "message": "The purchase token does not match the product ID.",
//                            "domain": "androidpublisher",
//                            "reason": "purchaseTokenDoesNotMatchProductId",
//                            "location": "token",
//                            "locationType": "parameter"
//                        }
//                    ]
//                }
//            }

//            {
//                "purchaseTimeMillis": "1616491551983",
//                "purchaseState": 1,
//                "consumptionState": 0,
//                "developerPayload": "",
//                "orderId": "GPA.3321-7534-7058-39510",
//                "purchaseType": 0,
//                "acknowledgementState": 0,
//                "kind": "androidpublisher#productPurchase",
//                "regionCode": "IN"
//            }

        //有错误信息
        if (isset($data['error'])) {
            return_json_encode_data('', 0, $data['error']['message']);
        }

        //支付状态 purchaseState 1未支付， 0已支付
        if (array_key_exists('purchaseState', $data) && $data['purchaseState'] == 0
            //是否已消费 consumptionState 0 未消费 1 已消费
            && array_key_exists('consumptionState', $data) && $data['consumptionState'] == 1) {

            bogokjLogPrint('google_pay', ['status' => '验证完成，进入增加账户余额步骤', 'order_id' => $appOrderId]);

            $hasPaymentNotice = db('user_charge_log')
                ->where(
                    [
                        'order_id' => $appOrderId,
                        //'pay_order_id' => $data['orderId'],
                    ]
                )
                ->find();

            if (!$hasPaymentNotice) {
                bogokjLogPrint('google_pay', ['status' => '未查询到订单，充值失败！', 'order_id' => $appOrderId]);

                redis_unlock($lockKey);
                return_json_encode_data('', 0, 'Order information error!');
            }

            if ($hasPaymentNotice['status'] == 1) {
                bogokjLogPrint('google_pay', ['status' => '订单是已消费订单，充值失败！', 'order_id' => $appOrderId]);

                redis_unlock($lockKey);
                // 该订单已经在服务端消费过了，需要客户端消费
                return_json_encode_data('', 1002, '');
            }

            $payResult = pay_call_service($appOrderId, 1, 'google_pay', $data['orderId']);
            if (!$payResult) {
                bogokjLogPrint('google_pay', ['status' => '订单处理结果为失败，充值失败', 'order_id' => $appOrderId]);
                redis_unlock($lockKey);
                return_json_encode_data('', 0, 'Fail!');
            }

        }

        $root['accessToken'] = $accessToken;

        redis_unlock($lockKey);
        return_json_encode_data($root, 1, 'Success！');

    }
}
