<?php

$payment_lang = array(
    'name' => 'StripeWeb',
    'merchantId' => 'merchantId',
    'publicKey' => 'publicKey',
    'privateKey' => 'privateKey',
);

$config = array(
    'merchantId' => array(
        'INPUT_TYPE' => '0',
    ),
    'publicKey' => array(
        'INPUT_TYPE' => '0',
    ),
    'privateKey' => array(
        'INPUT_TYPE' => '0',
    ),
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true) {
    $module['class_name'] = 'PayPalWeb';
    /* 名称 */
    $module['name'] = $payment_lang['name'];

    /* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '4';

    /* 配送 */
    $module['config'] = $config;

    $module['lang'] = $payment_lang;

    $module['reg_url'] = '';

    return $module;
}


class StripeWeb_pay
{

    public function get_payment_code($pay_type, $rule, $payment_notice_id)
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $url =  SITE_URL . "/api/stripe_web_api/pay_index?pay_code=StripeWeb&notice_id=". $payment_notice_id."&user_id=".$uid."&token=".$token;
        $pay_info = array();
        $pay_info['is_wap'] = 1;
        $pay_info['class_name'] = "StripeWeb";
        $pay_info['is_without'] = 0; //
        $pay_info['url'] = $url;
        $pay_info['type'] = 1;
        $pay_info['sdk_code'] = array("pay_sdk_type" => "Stripe", "config" =>
            array(
                "url" => $url,
                "is_wap" => 1,
            )
        );
        $pay_info['notice_id'] = $payment_notice_id;

        $pay_info['pay_info'] = $url;
        $pay_info['post_url'] = $url;

        return $pay_info; // 返回支付url


    }

    public function response($request)
    {

    }

    public function notify($request)
    {

    }

    function get_display_code()
    {

    }

    public function display_code($payment_notice_id)
    {

    }
}
