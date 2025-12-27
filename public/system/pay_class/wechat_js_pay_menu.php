<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/6/22
 * Time: 17:34
 */

class wechat_js_pay
{
    public function get_payment_code($pay,$rule,$notice_id,$open_id,$trade_type="JSAPI")
    {
        $order_id = $notice_id;
        $good_name = $rule['name'];
        $money = $rule['money'];
        $good_name = '虚拟币充值';

        header("Content-type: text/html; charset=utf-8");

        require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';

        $noceStr = md5(rand(100,1000).time());//获取随机字符串
        $mch_id = $pay['merchant_id'];
        $key = trim($pay['private_key']);

        $config = load_cache('config');
        $pay['app_id'] = trim($config['wechat_public_appid']);
        $money = $money * 100;

        $paramarr = array(
            "appid"       =>    trim($pay['app_id']),
            "body"        =>    $good_name,//说明内容
            "mch_id"      =>    $mch_id,
            "nonce_str"   =>    $noceStr,
            "notify_url"  =>    SITE_URL . "/api/notify_api/wechatpay_notify",
            "openid"  =>    $open_id,
            "out_trade_no"=>    $order_id,
            "total_fee"   =>    $money,
            "trade_type"  =>    'JSAPI'
        );
        $sign = sign($paramarr,$key);//生成签名
        $paramarr['sign'] = $sign;
        $paramXml = "<xml>";
        foreach($paramarr as $k => $v){
            $paramXml .= "<" . $k . ">" . $v . "</" . $k . ">";

        }
        $paramXml .= "</xml>";

        $ch = curl_init ();
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        @curl_setopt($ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/pay/unifiedorder");
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $paramXml);
        @$resultXmlStr = curl_exec($ch);
        if(curl_errno($ch)){
            print curl_error($ch);
        }
        curl_close($ch);

        $result = xmlToArray($resultXmlStr);
        //dump($result);die();
        $time2 = time();
        $prepayid = $result['prepay_id'];
        $sign = "";
        $noceStr = md5(rand(100,1000).time());//获取随机字符串
        $paramarr2 = array(
            "appId"     =>  trim($pay['app_id']),
            "nonceStr"  =>  $noceStr,
            "package"   =>  "prepay_id=".$prepayid,
            "signType" =>  "MD5",
            "timeStamp" =>  $time2
        );
        //dump($paramarr2);die();
        $paramarr2["sign"] = sign($paramarr2,$key);//生成签名
        $pay_info['is_wap']  = 0;
        $pay_info['type'] = 2;
        $pay_info['pay_info'] = $paramarr2;
        //file_put_contents('./alipay.txt',$response);
        return $pay_info;

    }

    public function notify($request){

        //libxml_disable_entity_loader(true);

        require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';
        $xmlInfo = $GLOBALS['HTTP_RAW_POST_DATA'];

        $arrayInfo = xmlToArray($xmlInfo);

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $arrayInfo["out_trade_no"]) -> find();

        $pay_info = db('pay_menu') -> find($order_info['pay_type_id']);
        $config = load_cache('config');
        $pay_info['app_id'] = trim($config['wechat_public_appid']);

        /* 测试数据打印log数组转字符串=============== */
        $test = "";
        foreach($arrayInfo as $k => $v){
            $test .= $k.":".$v."\t\r\n";
        }


        if($arrayInfo['return_code'] == "SUCCESS"){

            $wxSign = $arrayInfo['sign'];
            unset($arrayInfo['sign']);
            $arrayInfo['appid']  = $pay_info['app_id'];
            $arrayInfo['mch_id'] = $pay_info['merchant_id'];
            ksort($arrayInfo);//按照字典排序参数数组
            $sign = sign($arrayInfo,$pay_info['private_key']);//生成签名


            if(checkSign($wxSign,$sign)){
                pay_call_service($arrayInfo["out_trade_no"],'','',$arrayInfo['transaction_id']);
                returnInfo("SUCCESS","OK");
            }else{
                pay_call_service_no($arrayInfo["out_trade_no"],$arrayInfo['transaction_id']);
                returnInfo("FAIL","签名失败");
            }
        }else{
            returnInfo("FAIL","签名失败");
        }
    }
}
