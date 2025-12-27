<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/7/5
 * Time: 10:48 上午
 * Name:
 */

namespace app\api\model;

use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\Exception;
use think\Model;
use think\Db;
use think\helper\Time;
use VideoCallRedis;


class SmsModel extends Model
{
    public function send_code($mobile, $code, $type = '0')
    {
        $where = "status=1";
        $where .= $type ? " and type=1" : " and type=0";
        $list = db('cloud_sms_config')->where($where)->find();

        $root = array('code' => 0, 'msg' => lang('Verification_code_sending_failed'));

        if ($list) {
            $gateways_config = [];
            //array_push($gateways,$list['val']);
            if ($list['val'] == 'aliyun') {
                $data['aliyun'] = [
                    'access_key_id'     => $list['app_key'],
                    'access_key_secret' => $list['app_secret'],
                    'sign_name'         => $list['sign_name']
                ];
            } else if ($list['val'] == 'yunpian') {
                $data['yunpian'] = [
                    'api_key'   => $list['app_key'],
                    //'access_key_secret' => $list['app_secret'],
                    'signature' => $list['sign_name'],
                ];
            } else if ($list['val'] == 'submail') {
                $data['submail'] = [
                    'app_id'  => $list['app_key'],
                    'api_key' => $list['app_key'],
                    //'access_key_secret' => $list['app_secret'],
                    //'signature' => $list['sign_name'],
                ];
            } else if ($list['val'] == 'luosimao') {
                $data['luosimao'] = [
                    'api_key' => $list['app_key'],
                ];
            } else if ($list['val'] == 'yuntongxun') {
                $data['yuntongxun'] = [
                    'app_id'         => $list['app_id'],
                    'account_sid'    => $list['app_key'],
                    'account_token'  => $list['app_secret'],
                    //'access_key_secret' => $list['app_secret'],
                    'is_sub_account' => false,
                ];
            } else if ($list['val'] == 'huyi') {
                $data['huyi'] = [
                    'api_id'    => $list['api_id'],
                    'api_key'   => $list['api_key'],
                    'signature' => $list['sign_name'],
                ];
            } else if ($list['val'] == 'juhe') {
                $data['juhe'] = [
                    'api_key' => $list['app_key'],
                ];
            } else if ($list['val'] == 'sendcloud') {
                $data['sendcloud'] = [
                    'sms_user'  => $list['app_id'],
                    'sms_key'   => $list['app_key'],
                    //'access_key_secret' => $list['app_secret'],
                    'timestamp' => false,
                ];
            } else if ($list['val'] == 'baidu') {
                $data['baidu'] = [
                    'ak'        => $list['app_key'],
                    'sk'        => $list['app_secret'],
                    'invoke_id' => $list['app_id'],
                    'domain'    => $list['sign_name'],
                ];
            } else if ($list['val'] == 'huaxin') {
                $data['huaxin'] = [
                    'user_id'  => $list['app_id'],
                    'password' => $list['app_secret'],
                    'account'  => $list['app_key'],
                    'ip'       => request()->ip(),
                    'ext_no'   => '',
                ];
            } else if ($list['val'] == 'chuanglan') {
                //云通讯
                $data['chuanglan'] = [
                    'account'        => $list['app_key'],
                    'password'       => $list['app_secret'],

                    // 国际短信时必填
                    'intel_account'  => '',
                    'intel_password' => '',

                    // \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_VALIDATE_CODE  => 验证码通道（默认）
                    // \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_PROMOTION_CODE => 会员营销通道
                    'channel'        => \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_VALIDATE_CODE,

                    // 会员营销通道 特定参数。创蓝规定：api提交营销短信的时候，需要自己加短信的签名及退订信息
                    'sign'           => '【通讯云】',
                    'unsubscribe'    => '回TD退订',
                ];
            } else if ($list['val'] == 'rongcloud') {
                $data['rongcloud'] = [
                    'app_key'    => $list['app_key'],
                    'app_secret' => $list['app_secret'],
                ];
            } else if ($list['val'] == 'tianyiwuxian') {
                $data['tianyiwuxian'] = [
                    'username' => $list['app_key'], //用户名
                    'password' => $list['app_secret'], //密码
                    'gwid'     => '', //网关ID
                ];
            } else if ($list['val'] == 'twilio') {
                $data['twilio'] = [
                    'account_sid' => $list['app_key'], // sid
                    'from'        => $list['app_id'], // 发送的号码 可以在控制台购买
                    'token'       => $list['app_secret'], // apitoken
                ];
            } else if ($list['val'] == 'tiniyo') {
                $data['tiniyo'] = [
                    'account_sid' => $list['app_key'], // auth_id from https://tiniyo.com
                    'from'        => $list['app_id'], // 发送的号码 可以在控制台购买
                    'token'       => $list['app_secret'], // auth_secret from https://tiniyo.com
                ];
            } else if ($list['val'] == 'qcloud') {
                //腾讯云
                $data['qcloud'] = [
                    'sdk_app_id' => $list['app_id'], // SDK APP ID
                    'secret_id'  => $list['app_key'], // APP KEYAKIDHNDo8BxUjiPpzuXt0qPkrhGZaMq3zf4C
                    'secret_key' => $list['app_secret'], // APP KEY  sUqwlJ4ZnkRs2vWqtf9NL2SbU7TZOwDD
                    'sign_name'  => $list['sign_name'], // 短信签名，如果使用默认签名，该字段可缺省（对应官方文档中的sign）
                ];
            } else if ($list['val'] == 'avatardata') {
                //阿凡达
                $data['avatardata'] = [
                    'app_key' => $list['app_key'], // APP KEY
                ];

            } else if ($list['val'] == 'huawei') {
                //华为云
                $data['huawei'] = [
                    'endpoint'   => $list['endpoint'], // APP接入地址
                    'app_key'    => $list['app_key'], // APP KEY
                    'app_secret' => $list['app_secret'], // APP SECRET
                    'from'       => [
                        'default' => $list['sign_name'], // 默认使用签名通道号
                    ],
                    'callback'   => '' // 短信状态回调地址
                ];
                $config = [
                    // HTTP 请求的超时时间（秒）
                    'timeout'  => 60.0,

                    // 默认发送配置
                    'default'  => [
                        // 网关调用策略，默认：顺序调用
                        //'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                        // 默认可用的发送网关
                        'gateways' => [
                            $list['val'],
                        ],
                    ],
                    'gateways' => $data,
                ];
                $easySms = new EasySms($config);
                try {
                    $easySms->send($mobile, [
                        'template' => $list['template'],
                        'data'     => [
                            $code
                        ],
                    ]);
                    $root['code'] = 1;
                    $root['msg'] = lang('Verification_code_sent_successfully');
                } catch (Exception $e) {
                    $a = $e->getExceptions();
                    $root['msg'] = $a;
                }
                return $root;

            } else if ($list['val'] == 'yunxin') {
                //网易云信
                $data['yunxin'] = [
                    'app_key'    => $list['app_key'],
                    'app_secret' => $list['app_secret'],
                    //'code_length' => 4, // 随机验证码长度，范围 4～10，默认为 4
                    'need_up'    => false, // 是否需要支持短信上行
                ];
                $config = [
                    // HTTP 请求的超时时间（秒）
                    'timeout'  => 60.0,

                    // 默认发送配置
                    'default'  => [
                        // 网关调用策略，默认：顺序调用
                        //'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                        // 默认可用的发送网关
                        'gateways' => [
                            $list['val'],
                        ],
                    ],
                    'gateways' => $data,
                ];
                $easySms = new EasySms($config);
                try {
                    $easySms->send($mobile, [
                        'template' => $list['template'],
                        'data'     => [
                            'code'   => $code, // 如果设置了该参数，则 code_length 参数无效
                            'action' => 'sendCode', // 默认为 `sendCode`，校验短信验证码使用 `verifyCode`
                        ],
                    ]);
                    $root['code'] = 1;
                    $root['msg'] = lang('Verification_code_sent_successfully');
                } catch (Exception $e) {
                    $a = $e->getExceptions();
                    $root['msg'] = $a;
                }
                return $root;
                exit;

            } else if ($list['val'] == 'yunzhixun') {
                //云之讯
                $data['yunxin'] = [
                    'sid'    => $list['app_id'],
                    'token'  => $list['app_secret'],
                    'app_id' => $list['app_key'],
                ];
                $config = [
                    // HTTP 请求的超时时间（秒）
                    'timeout'  => 60.0,

                    // 默认发送配置
                    'default'  => [
                        // 网关调用策略，默认：顺序调用
                        //'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                        // 默认可用的发送网关
                        'gateways' => [
                            $list['val'],
                        ],
                    ],
                    'gateways' => $data,
                ];
                $easySms = new EasySms($config);
                try {
                    $easySms->send($mobile, [
                        'template' => $list['template'],
                        'data'     => [
                            'params' => '',   // 模板参数，多个参数使用 `,` 分割，模板无参数时可为空
                            'uid'    => '',  // 用户 ID，随状态报告返回，可为空
                        ],
                    ]);
                    $root['code'] = 1;
                    $root['msg'] = lang('Verification_code_sent_successfully');
                } catch (Exception $e) {
                    $a = $e->getExceptions();
                    $root['msg'] = $a;
                }
                return $root;
                exit;

            } else if ($list['val'] == 'kingtto') {
                //凯信通
                $data['kingtto'] = [
                    'userid'   => $list['app_id'],
                    'account'  => $list['app_key'],
                    'password' => $list['app_secret'],
                ];
                $config = [
                    // HTTP 请求的超时时间（秒）
                    'timeout'  => 60.0,

                    // 默认发送配置
                    'default'  => [
                        // 网关调用策略，默认：顺序调用
                        //'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                        // 默认可用的发送网关
                        'gateways' => [
                            $list['val'],
                        ],
                    ],
                    'gateways' => $data,
                ];
                $easySms = new EasySms($config);
                try {
                    $easySms->send($mobile, [
                        'template' => $list['template'],
                        'data'     => [
                            'content' => '您的验证码为: ' . $code,
                        ],
                    ]);
                    $root['code'] = 1;
                    $root['msg'] = lang('Verification_code_sent_successfully');
                } catch (Exception $e) {
                    $a = $e->getExceptions();
                    $root['msg'] = $a;
                }
                return $root;
                exit;

            } else if ($list['val'] == 'qiniu') {
                //凯信通
                $data['qiniu'] = [
                    'secret_key' => $list['app_key'],
                    'access_key' => $list['app_secret'],
                ];
            } else if ($list['val'] == 'smsbao') {
                // 短信宝 ?u=tatajiaoyou&p=013208b87d11473797a67334a5892e9f&m={mobile}&c={content}

                $smsapi = "http://api.smsbao.com/";
                //短信平台帐号 + 短信key + 手机号 + 短信内容
                $content = "【" . $list['sign_name'] . "】您的验证码为" . $code;//要发送的短信内容
                $smsAction = $type == 1 ? "wsms" : "sms";
                $smsMobile = $type == 1 ? urlencode($mobile) : $mobile;
                $sendurl = $smsapi . "{$smsAction}?u=" . $list['app_id'] . "&p=" . $list['app_key'] . "&m=" . $smsMobile . "&c=" . urlencode($content);
                //debugHookMsgSend('发送国际验证码-'.$sendurl);
                $result = file_get_contents($sendurl);
                if ($result == 0) {
                    $root['code'] = 1;
                    $root['msg'] = '';
                } else {
                    $root['msg'] = $result;
                }
                return $root;
            }
            $config = [
                // HTTP 请求的超时时间（秒）
                'timeout'  => 60.0,

                // 默认发送配置
                'default'  => [
                    // 网关调用策略，默认：顺序调用
                    //'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                    // 默认可用的发送网关
                    'gateways' => [
                        $list['val'],
                    ],
                ],
                'gateways' => $data,
            ];

            $easySms = new EasySms($config);
            //dump($config);die();
            try {
                $easySms->send($mobile, [
                    'content'  => '您的验证码为: ' . $code,
                    'template' => $list['template'],
                    'data'     => [
                        'code' => $code
                    ],
                ]);
                $root['code'] = 1;
                $root['msg'] = lang('Verification_code_sent_successfully');
            } catch (Exception $e) {
                $a = $e->getExceptions();
                if ($list['val'] == 'aliyun') {
                    $root['msg'] = $a['aliyun']->raw['Message'];
                } elseif ($list['val'] == 'qcloud') {
                    $root['msg'] = $a['qcloud']->getMessage();;
                } else {
                    $root['msg'] = json_encode($a);
                }
            }
        }
        return $root;
    }
}