<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/21
 * Time: 23:51
 */

class alipay_app_pay
{

    public function get_payment_code($pay,$rule,$notice_id,$type=1)
    {
        if(empty($pay['public_key'])){
            $result['code'] = 0;
            $result['msg'] = '支付公钥为空';
            echo json_encode($result);exit;
        }

        $order_id = $notice_id;
      //  $good_name = $rule['name'];
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
        $aop->alipayrsaPublicKey = trim($pay['public_key']);//'请填写支付宝公钥，一行字符串';
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeAppPayRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"$good_name\","
            . "\"subject\": \"$good_name\","
            . "\"out_trade_no\": \"$order_id\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"$money\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";
        $request->setNotifyUrl(SITE_URL . "/api/notify_api/alipay_notify");
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        if($type=='h5'){
            $response = $aop->pageExecute($request);
        }else{
            $response = $aop->sdkExecute($request);
        }
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        $pay_info['pay_info'] =  $response;//就是orderString 可以直接给客户端请求，无需再做处理。
        $pay_info['is_wap']  = 0;
        $pay_info['type'] = 1;

        return $pay_info;
    }

    public function notify($request){

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $request["out_trade_no"]) -> find();

        $pay_info = db('pay_menu') -> find($order_info['pay_type_id']);
        if(!$pay_info){
            notify_log( $request["out_trade_no"],'0','充值回调成功,error:充值渠道不存在');
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
