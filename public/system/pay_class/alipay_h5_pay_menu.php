<?php
/**
 * Created by PhpStorm.
 * User: yth
 * Date: 2021/05/21
 * Time: 23:51
 */

class alipay_h5_pay
{

    public function get_payment_code($pay,$rule,$notice_id)
    {
        $order_id = $notice_id;
        $good_name = $rule['name'];
        $money = $rule['money'];
        $good_name = '虚拟币充值';
        require_once DOCUMENT_ROOT . '/system/pay/alipay/AopSdk.php';

        $aop = new AopClient;
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = trim($pay['app_id']);
        $aop->rsaPrivateKey = trim($pay['private_key']);//'请填写开发者私钥去头去尾去回车，一行字符串';
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        //$aop->postCharset='GBK';
        $aop->alipayrsaPublicKey = trim($pay['public_key']);//'请填写支付宝公钥，一行字符串';
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeAppPayRequest();
        //$request->methodName = "alipay.trade.wap.pay";
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $quit_url = SITE_URL.'/api/wechat_pay_api/recharge';
        $bizcontent = "{\"body\":\"$good_name\","
            . "\"subject\": \"$good_name\","
            . "\"out_trade_no\": \"$order_id\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"$money\","
            . "\"quit_url\":\"$quit_url\","
            . "\"product_code\":\"QUICK_WAP_PAY\""
            . "}";
        $request->setNotifyUrl(SITE_URL . "/api/notify_api/alipay_notify");
        $request->setApiMethodName("alipay.trade.wap.pay");
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->pageExecute($request,'GET');

        /*$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $response->$responseNode->code;*/
        //dump($response);die();
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        $pay_info['pay_info'] =  $response;//就是orderString 可以直接给客户端请求，无需再做处理。
        $pay_info['is_wap']  = 0;
        $pay_info['type'] = 1;
        $pay_info['order_id'] = $order_id;

        return $pay_info;
    }

    public function notify($request){

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $request["out_trade_no"]) -> find();

        $pay_info = db('pay_menu') -> find($order_info['pay_type_id']);
        if(!$pay_info){
            notify_log( $request["merchant_order_no"],'0','充值回调成功,error:充值渠道不存在');
            echo 'success';
            exit;
        }

        require_once DOCUMENT_ROOT . '/system/pay/alipay/AopSdk.php';

        $app_id = $pay_info['app_id'];

        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = $pay_info['public_key'];
        $flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");

        if($flag && $request['app_id'] == $app_id && $request['trade_status'] == 'TRADE_SUCCESS'){
            pay_call_service($request["out_trade_no"],'','',$request['trade_no']);
        }else{
            pay_call_service_no($request["out_trade_no"],$request['trade_no']);
        }


        echo 'success';
        exit;
    }
}