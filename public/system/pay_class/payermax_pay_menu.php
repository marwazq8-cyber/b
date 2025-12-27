<?php
require_once DOCUMENT_ROOT . '/system/payermax/vendor/autoload.php';

use payermax\sdk\client\PayermaxClient;
use payermax\sdk\config\MerchantConfig;
use payermax\sdk\utils\RSAUtils;

class payermax_pay
{
    public function get_payment_code($pay, $rule, $notice_id, $user_id)
    {
        $post_url = '';

        try {

            //构造参数
            $merchantConfig = new MerchantConfig();
            $merchantConfig->merchantNo = $pay['01010115240953']; //P01010115240953
            $merchantConfig->appId = $pay['6666c8b036a24579974497c2f98d99'];
            $merchantConfig->merchantPrivateKey = trim($pay['MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKExZ32j0CdzwZtJixKDHmwk6P6Xe2s1JyEVJ5VYBf7MDs/tD611KH6LnxCf6M3DbIJs2gPx6/nk70H94d7ZR+vDC0Ru7oC3YArGXxjcwkjivGJ4pkjj63+q5MorIm+5/s323y3HE8J81MTNsUK1G6B1mPsn5n6MziKj9Bc9SS4hAgMBAAECgYBb246RX5/IS8QB3VgedZAJqsMICoUvo/unc6m6Bo5sFBdA0GRFweUQsDo2PBpr37jfXm6jHuMN5jOeVLK5zvKXdGoRpkoxdUtYtR51KCWkzUkz6LRH+ooLuC7k3iUVVnZZ7zNLgQORRlFwMCA2gHa3mvbdzW3tP92rgdM3oCDHAQJBAN7jQ0C5eyfymjIRJ/AEJPw+oH7Vr+evFuJRahjViE3es7INpFZDmwBLwuHHLMATwNuQ5kniH02IzXA0h+hborECQQC5I81iab/RYJSY45pxTIusUqJGF4ZQg3ZxdnnNsxbtl0uMw17RArLF/czV3DwwCnGGepp9TNBkIrbglTj7R75xAkEA0jgfEkjes4rJjDdKJ8KA77hRv87jne0x9Ds9ija73FYTvffH6+TPqLPMFw64UmFPIMfFrCGtzH8e5JlnJexnwQJAA3UvuM7QzlBHdjOKBuOvGCDS9wwpbgeGhsf3rmfR3c4dkxtzAeRTAm+jC7t5RExtol1X1U9B9RzQ3ZDr54WHgQJAeicYgZYMymBbxcmlz6+GhvnNQWNh0vJcsKb3YQ/uolMv3ymiglj89QiInTJmvXsU8oEdSv7XE+Pq7Od+MrJ/NA==']);
            $merchantConfig->payermaxPublicKey = trim($pay['MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQChMWd9o9Anc8GbSYsSgx5sJOj+l3trNSchFSeVWAX+zA7P7Q+tdSh+i58Qn+jNw2yCbNoD8ev55O9B/eHe2UfrwwtEbu6At2AKxl8Y3MJI4rxieKZI4+t/quTKKyJvuf7N9t8txxPCfNTEzbFCtRugdZj7J+Z+jM4io/QXPUkuIQIDAQAB']);
            //ISV商户所需参数 非ISV商户不用传递如下两个字段
            //     $merchantConfig->spMerchantNo = "xxx";
            //     $merchantConfig->merchantAuthToken = "xxx";
            $pay['currency'] = 'USD'; // 默认美元支付
            //设置参数
            PayermaxClient::setConfig($merchantConfig, \payermax\sdk\constants\Env::$prod);
            $price = $rule['money'];
            $goodsName = $rule['name'];
            $goodsId = $rule['id'];
            $notifyUrl = get_domain() . "/api/notify_api/payermax_notify";
            $language = "en";
            $userId = $user_id; // 商户内部的用户Id，需要保证每个ID唯一性，支付方式保存后会根据userId进行支付方式推荐
            $frontCallbackUrl = "intent://" . $_SERVER['HTTP_HOST'] . "#Intent;scheme=moto;package=com.hasakitest.hasakiye;end"; // 商户指定的跳转URL，用户完成支付后会被跳转到该地址，以http/https开头或者商户应用的scheme地址（目前仅支持Andriod）https://payapi.okgame.com/v2/PayerMax/result.html  intent://域名#Intent;scheme=moto;package=com.hasakitest.hasakiye;end
            $subject = "top-up"; // 订单标题--充值
            //构造业务报文 -- https://docs.payermax.com/#/30?page_id=650&lang=zh-cn
            $requestData = '{
            "outTradeNo": "' . $notice_id . '",
            "subject": "' . $subject . '",
            "totalAmount": "' . $price . '",
            "currency": "' . $pay['currency'] . '",
            "userId": "' . $userId . '",
            "goodsDetails": [{
                "goodsId": "' . $goodsId . '",
                "goodsName": "' . $goodsName . '",
                "quantity": "1",
                "price": "' . $price . '",
                "goodsCurrency": "' . $pay['currency'] . '",
                "showUrl": ""
                }],
            "language": "' . $language . '",
            "reference": "' . $notice_id . '",
            "frontCallbackUrl": "' . $frontCallbackUrl . '",
            "notifyUrl": "' . $notifyUrl . '"
            }';
//            $requestData = '{
//            "outTradeNo": "'.$notice_id.'",
//            "subject": "'.$subject.'",
//            "totalAmount": "'.$price.'",
//            "currency": "'.$pay['currency'].'",
//            "country": "'.$pay['country_mark'].'",
//            "userId": "'.$userId.'",
//            "paymentDetail": {
//                "paymentMethod": "'.$paymentMethod.'",
//                "targetOrg": "'.$targetOrg.'",
//                "cardInfo": {
//                    "cardOrg": "",
//                    "cardIdentifierNo": "",
//                    "cardHolderFullName": "",
//                    "cardExpirationMonth": "",
//                    "cardExpirationYear": "",
//                    "cvv": ""
//                },
//                "payAccountInfo": [{
//                        "accountNo": "",
//                        "accountNoType": "EMAIL"
//                    },
//                    {
//                        "accountNo": "",
//                        "accountNoType": "PHONE"
//                    },
//                    {
//                        "accountNo": "",
//                        "accountNoType": "ACCOUNT"
//                    }
//                ]
//            },
//            "goodsDetails": [{
//                "goodsId": "'.$goodsId.'",
//                "goodsName": "'.$goodsName.'",
//                "quantity": "1",
//                "price": "'.$price.'",
//                "goodsCurrency": "'.$pay['currency'].'",
//                "showUrl": ""
//                }],
//            "language": "'.$language.'",
//            "reference": "'.$notice_id.'",
//            "frontCallbackUrl": "'.$frontCallbackUrl.'",
//            "notifyUrl": "'. $notifyUrl . '"
//            }';
            bogokjLogPrint("payermax_pay", $requestData);
            $json_decodeData = json_decode($requestData, true);
            //请求并获取业务返回
            $resp = PayermaxClient::send('orderAndPay', $json_decodeData);
            $resp_list = json_decode($resp, true);
            bogokjLogPrint("payermax_pay", $resp);
            if ($resp_list['code'] == 'APPLY_SUCCESS') {
                $post_url = $resp_list['data']['redirectUrl'];
            }
            //   $resp= json_encode($resp);
        } catch (Exception $e) {
            //    $resp = '';
            $result['code'] = 0;
            $result['msg'] = $e->getMessage();
            bogokjLogPrint("payermax_pay", $e);
            return_json_encode($result);
        }

        $pay_info['post_url'] = $post_url;       //生成指定网址 -- 就是orderString 可以直接给客户端请求，无需再做处理。
        $pay_info['is_wap'] = 1;

        return $pay_info;

    }

    /*回调 = {
    "code": "APPLY_SUCCESS",
    "msg": "",
    "keyVersion": "1",
    "appId": "3b242b56a8b64274bcc37dac281120e3",
    "merchantNo": "020213827212251",
    "notifyTime": "2022-01-17T09:33:54.540+00:00",
    "notifyType": "PAYMENT",
    "data": {
       "outTradeNo": "P1642410680681",
       "tradeToken": "TOKEN20220117070423078754880",
        "totalAmount": 10000,
        "currency": "IDR",
        "channelNo": "DMCP000000000177005",
        "thirdChannelNo": "4ikqJ6ktEqyRawE1dvqb9c",
        "paymentCode": "2312121212",
        "country": "ID",
        "status": "SUCCESS",
        "paymentDetails": [
            {
                "paymentMethodType": "WALLET",
                "targetOrg": "DANA"
            }
        ],
        "reference": "020213827524152"
    }
}
回调 =
{
    "code":"APPLY_SUCCESS",
     "msg":"Success.",
    "keyVersion":"1",
    "appId":"6bb161e2c987454f9e3c11371aa89539",
    "merchantNo":"P01010115898739",
    "notifyTime":"2024-01-10T09:34:56.434Z",
    "notifyType":"PAYMENT"
    "data":{
    "outTradeNo":"17048792441027262906",
    "tradeToken":"T2024011009618547031123",
    "totalAmount":"1.59",
    "currency":"USD",
        "reference":"17048792441027262906",
        "country":"HR",
        "paymentDetails":[{
            "targetOrg":"*",
            "cardInfo":{
                "cardOrg":"VISA",
                "cardIdentifierNo":"444433******1111",
                "cardIdentifierName":"JAMES******"
            },
            "paymentMethod":"CARD"
        }],
        "status":"SUCCESS"
    },

}

   {"msg":"Success.",,"data":{"redirectUrl":"https://cashier-n-uat.payermax.com/index.html#/cashier/home?merchantId=P01010115898739&merchantAppId=6bb161e2c987454f9e3c11371aa89539&tradeToken=T2024011100611147033821&language=en&token=8d773ca17bbd401abf15cb1137457669&amount=1.59&currency=USD&version=1.2&cashierId=T2024011100611147033821&frontCallbackUrl=https%3A%2F%2Fpayapi.okgame.com%2Fv2%2FPayerMax%2Fresult.html&pmaxLinkV=1","outTradeNo":"17049341151027277910","tradeToken":"T2024011100611147033821","status":"PENDING"}}
    {"appId":"6bb161e2c987454f9e3c11371aa89539",,"data":{"reference":"17049341151027277910","country":"HR","totalAmount":1.59,"outTradeNo":"17049341151027277910","currency":"USD","paymentDetails":[{"targetOrg":"*","cardInfo":{"cardOrg":"VISA","cardIdentifierNo":"444433******1111","cardIdentifierName":"JAMES******"},"paymentMethod":"CARD"}],"status":"SUCCESS"},"keyVersion":"1","merchantNo":"P01010115898739","notifyTime":"2024-01-11T00:48:56.474Z","notifyType":"PAYMENT"}
 */
    public function notify($data_val)
    {
        $root = array("code" => "SUCCESS", "msg" => "Success"); // 错误和成功都必须返回success

        if ($data_val['code'] != 'APPLY_SUCCESS') {
            notify_log($data_val['data']["outTradeNo"], '0', '充值回调失败,error:' . json_encode($data_val));
            echo json_encode($root);
            exit;
        }

        $data = $data_val['data'];
        //查询订单
        $order_info = db('user_charge_log')->where('order_id', '=', $data["outTradeNo"])->find();

        $pay_info = db('pay_menu')->find($order_info['pay_type_id']);

        if (!$pay_info) {
            notify_log($data["outTradeNo"], '0', '充值回调失败,error:充值渠道不存在');

            echo json_encode($root);
            exit;
        }
        $Headers = getHeader();
        $sign = $Headers['SIGN'];
        try {
            //转成json并签名
            $reqBody = json_encode($data_val);
//            bogokjLogPrint('payermax_api', 'payermax==notify==sign:' . $sign);
            // 验签
            $flag = RSAUtils::verify($reqBody, $sign, trim($pay_info['public_key']));
            //     bogokjLogPrint('payermax_api', 'payermax==notify==flag:' . $flag);
            if ($flag && $data['status'] == 'SUCCESS') {
                pay_call_service($data["outTradeNo"], '', '', $data['tradeToken']);
            } else {
                //失败 -- 只修改第三方订单号
                pay_call_service_no($data["outTradeNo"], $data['tradeToken']);
            }
        } catch (Exception $e) {
            notify_log($data["outTradeNo"], '0', '充值回调失败,error:' . $e->getMessage());
        }


        echo json_encode($root);
        exit;
    }
}