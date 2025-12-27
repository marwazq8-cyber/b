<?php
return [
    'gift_im_send_type'   => 1,//1:api同步请求 2:队列请求。暂时只支持1，第二个方案不成熟
    /**
     * 是否开启限地区
     * */
    'is_open_limit_area'  => true,
    /**
     * 开启地区限制后白名单邮箱账户
     * */
    'test_google_account' => [
        'weipeng201707@gmail.com'
    ],
    /**
     * 开启地区限制后白名单手机账户
     * */
    'test_mobile_account' => [
        '8615698137973',
//        '8613246579813',
//        '8615753857573',
    ],
    'test_ip_list'        => [
        '222.132.157.163'
    ],
    'OTP'                 => [
        'is_open'    => false,
        'app_key'    => 'cq9vk4sao9he12obd9sg',
        'app_secret' => '3xjc3wcw1477cau5hbxwk5ja',
    ],
    'open_bogo_im'        => 0
];