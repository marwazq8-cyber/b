<?php
class alipay_fund_transfer
{
    /*
     * 最新需证书
     * $pay 支付宝信息
     * $out_biz_no 生成的订单号
     * $title 标题
     * $money 打款金额
     * $identity 对方支付宝账号
     * $name 对方支付宝账号名称
     * $remark 备注
     * */
    public function pay_transfer($pay,$out_biz_no,$money,$title,$identity,$name,$remark){
        //$payer_name = $pay['pay_name'];
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/AopCertClient.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/AopClient.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayFundTransUniTransferRequest.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/AopCertification.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayTradeQueryRequest.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayTradeWapPayRequest.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayTradeAppPayRequest.php';
        //AlipayFundTransToaccountTransferRequest
        //$aop = new AopClient ();
        $aop = new AopCertClient;
        $appCertPath = "应用证书路径（要确保证书文件可读），例如：/home/admin/cert/appCertPublicKey.crt";
        $alipayCertPath = "支付宝公钥证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayCertPublicKey_RSA2.crt";
        $rootCertPath = "支付宝根证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayRootCert.crt";

        //调用getPublicKey从支付宝公钥证书中提取公钥
        $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $aop->isCheckAlipayPublicCert = true;
        //调用getCertSN获取证书序列号
        $aop->appCertSN = $aop->getCertSN($appCertPath);
        //调用getRootCertSN获取支付宝根证书序列号
        $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);

        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $pay['app_id'];
        $aop->rsaPrivateKey = trim($pay['private_key']);
        $aop->alipayrsaPublicKey= trim($pay['public_key']);
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayFundTransUniTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"$out_biz_no\"," .
            "\"trans_amount\":$money," .
            "\"product_code\":\"TRANS_ACCOUNT_NO_PWD\"," .
            "\"biz_scene\":\"DIRECT_TRANSFER\"," .
            "\"order_title\":\"$title\"," .
            //"\"original_order_id\":\"20190620110075000006640000063056\"," .
            "\"payee_info\":{" .
            "\"identity\":\"$identity\"," .
            "\"identity_type\":\"ALIPAY_LOGON_ID\"," . //ALIPAY_USER_ID 支付宝的会员ID ,ALIPAY_LOGON_ID：支付宝登录号，支持邮箱和手机号格式
            "\"name\":\"$name\"" .
            "    }," .
            "\"remark\":\"$remark\"" .
            "  }");
        $result = $aop->execute ( $request);
        dump($result);die();
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            //echo "成功";
            $res['code'] = 1;
            $res['msg'] = '';
            $res['order_id'] = $result->$responseNode->order_id;//支付宝转账订单号
            $res['pay_fund_order_id'] = $result->$responseNode->pay_fund_order_id;//支付宝资金流水号
        } else {
            //echo "失败";
            $res['code'] = 0;
            $res['msg'] = $result->$responseNode->sub_msg;
        }
        return $res;
    }

    public function pay_transfer_new($pay,$out_biz_no,$money,$title,$identity,$name,$remark){
        //$payer_name = $pay['pay_name'];
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/AopCertClient.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/AopClient.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayFundTransUniTransferRequest.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/AopCertification.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayTradeQueryRequest.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayTradeWapPayRequest.php';
        require_once DOCUMENT_ROOT . '/system/pay/alipay-sdk-PHP-4_9_2/aop/request/AlipayTradeAppPayRequest.php';
        //AlipayFundTransToaccountTransferRequest
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $pay['app_id'];
        $aop->rsaPrivateKey = trim($pay['private_key']);
        $aop->alipayrsaPublicKey= trim($pay['public_key']);
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayFundTransUniTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"$out_biz_no\"," .
            "\"trans_amount\":$money," .
            "\"product_code\":\"TRANS_ACCOUNT_NO_PWD\"," .
            "\"biz_scene\":\"DIRECT_TRANSFER\"," .
            "\"order_title\":\"$title\"," .
            //"\"original_order_id\":\"20190620110075000006640000063056\"," .
            "\"payee_info\":{" .
            "\"identity\":\"$identity\"," .
            "\"identity_type\":\"ALIPAY_LOGON_ID\"," . //ALIPAY_USER_ID 支付宝的会员ID ,ALIPAY_LOGON_ID：支付宝登录号，支持邮箱和手机号格式
            "\"name\":\"$name\"" .
            "    }," .
            "\"remark\":\"$remark\"" .
            "  }");
        $result = $aop->execute ( $request);
        dump($result);die();
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            //echo "成功";
            $res['code'] = 1;
            $res['msg'] = '';
            $res['order_id'] = $result->$responseNode->order_id;//支付宝转账订单号
            $res['pay_fund_order_id'] = $result->$responseNode->pay_fund_order_id;//支付宝资金流水号
        } else {
            //echo "失败";
            $res['code'] = 0;
            $res['msg'] = $result->$responseNode->sub_msg;
        }
        return $res;
    }

    /*
     * 老sdk接口
     * $pay 支付宝信息
     * $out_biz_no 生成的订单号
     * $title 标题
     * $money 打款金额
     * $identity 对方支付宝账号
     * $name 对方支付宝账号名称
     * $remark 备注
     * */
    public function old_pay_transfer($pay,$out_biz_no,$money,$title,$identity,$name,$remark){

        $payer_name = $pay['pay_name'];
        require_once DOCUMENT_ROOT . '/system/pay/alipay/AopSdk.php';
        //AlipayFundTransToaccountTransferRequest
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $pay['app_id'];
        $aop->rsaPrivateKey = trim($pay['private_key']);
        $aop->alipayrsaPublicKey= trim($pay['public_key']);
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayFundTransToaccountTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"".$out_biz_no."\"," .//商户生成订单号
            "\"payee_type\":\"ALIPAY_LOGONID\"," .//收款方支付宝账号类型
            "\"payee_account\":\"".$identity."\"," .//收款方账号
            "\"amount\":\"".$money."\"," .//总金额
            "\"payer_show_name\":\"".$payer_name."\"," .//付款方账户
            "\"payee_real_name\":\"".$name."\"," .//收款方姓名
            "\"remark\":\"".$remark."\"" .//转账备注
            "}");
        $result = $aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        /*dump($result);
        dump($result->$responseNode);
        die();*/
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            //echo "成功";
            $res['code'] = $resultCode;
            $res['sub_msg'] = '';
            $res['msg'] = '';
            $res['order_id'] = $result->$responseNode->order_id;//支付宝转账订单号
            //$res['pay_fund_order_id'] = $result->$responseNode->pay_fund_order_id;//支付宝资金流水号
        } else {
            //echo "失败";
            $res['code'] = $resultCode;
            $res['sub_code'] = $result->$responseNode->sub_code;
            $res['sub_msg'] = $result->$responseNode->sub_msg;
            $res['msg'] = $result->$responseNode->msg;
        }
        return $res;
    }
}