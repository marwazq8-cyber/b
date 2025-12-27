<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 11:23
 */

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\config;
use think\Request;

class WechatPayApi extends Controller
{
    public function wechat_pay()
    {
        $config = load_cache('config');
        if ($config['wechat_public_type'] == 1) {
            $url = SITE_URL . '/api/wechat_pay_api/recharge';
            $this->redirect($url);
        } else if ($config['wechat_public_type'] == 2) {
            $is_wechat = $this->isWChat();
            if ($is_wechat) {
                $APP_ID = trim($config['wechat_public_appid']);
                if ($APP_ID) {
                    $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $APP_ID . '&redirect_uri=' . SITE_URL . '/api/wechat_pay_api/index&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect';
                    $this->redirect($url);
                } else {
                    echo lang('Recharge_has_not_been_started_yet');
                    exit;
                }
            } else {
                $url = SITE_URL . '/api/wechat_pay_api/recharge';
                $this->redirect($url);
            }
        } else {
            $APP_ID = trim($config['wechat_public_appid']);
            if ($APP_ID) {
                $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $APP_ID . '&redirect_uri=' . SITE_URL . '/api/wechat_pay_api/index&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect';
                $this->redirect($url);
            } else {
                echo lang('Recharge_has_not_been_started_yet');
                exit;
            }
        }
    }

    function isWChat()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function recharge()
    {
        //充值规则
        $pay_list = db('user_charge_rule')
            ->where('type = 1 and recharge_type = 2')
            ->order('orderno')
            ->select();
        $config = load_cache('config');
        //$this->assign('uid',$uid);
        //充值方式
        $pay_type = db('pay_menu')->field('id,pay_name,icon')->where('recharge_type = 2 and status = 1 and pay_name != "苹果内购"')->select();
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('pay_list', $pay_list);
        $this->assign('pay_type', $pay_type);
        $this->assign('recharge_min_money', $config['recharge_min_money']);
        $this->assign('recharge_money_proportion', $config['recharge_money_proportion']);
        return $this->fetch();
    }

    /*
     * h5充值*/
    public function index()
    {
        $is_wechat = $this->isWChat();
        if (!$is_wechat) {
            $url = SITE_URL . '/api/wechat_pay_api/recharge';
            $this->redirect($url);
            exit;
        }
        $result = array('code' => 1, 'msg' => '');
        $code = trim(input('param.code'));

        $arr = $this->geturl($code);
        if (!isset($arr['openid'])) {
            echo '获取openid失败';
            exit;
        }
        //充值规则
        $pay_list = db('user_charge_rule')
            ->where('type = 1 and recharge_type = 2')
            ->order('orderno')
            ->select();
        //充值方式
        $pay_type = db('pay_menu')
            ->field('id,pay_name,icon')
            ->where('recharge_type = 2 and status = 1 and class_name like "%wechat%"')
            ->select();
        $config = load_cache('config');
        //$this->assign('uid',$uid);
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('pay_list', $pay_list);
        //$arr['openid'] = 1;
        $this->assign('openid', $arr['openid']);
        $this->assign('pay_type', $pay_type);
        $this->assign('recharge_min_money', $config['recharge_min_money']);
        $this->assign('recharge_money_proportion', $config['recharge_money_proportion']);
        return $this->fetch();
    }

    /*
     * JSAPI 微信内充值
     * */
    public function pay()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        //$token = trim(input('param.token'));
        $pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rule_id'));//充值规则ID
        $money = intval(input('param.money'));//自定义金额
        $open_id = trim(input('param.openid'));//openid
        $user = db('user')->where('user_type = 2 and id = ' . $uid)->find();
        if (!$user) {
            $result['code'] = 0;
            $result['msg'] = lang('user_does_not_exist');
            echo json_encode($result, true);
            exit;
        }
        //$user_info = check_login_token($uid, $token);
        //$pid = 11;
        //充值渠道
        $pay_type = db('pay_menu')->find($pid);

        if (!$pay_type) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_type_does_not_exist');
            echo json_encode($result, true);
            exit;
        }

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

        if ($money > 0) {
            $config = load_cache('config');
            $recharge_min_money = $config['recharge_min_money'];
            if ($money < $recharge_min_money) {
                $result['code'] = 0;
                $result['msg'] = lang('Minimum_recharge') . $recharge_min_money . lang('ADMIN_MONEY');
                echo json_encode($result, true);
                exit;
            }
            $recharge_money_proportion = $config['recharge_money_proportion'];
            $coin = $money * $recharge_money_proportion;
            $rule_id = -1;
            $rule['name'] = $config['currency_name'] . lang('Recharge');
            $rule['money'] = $money;
        } else {
            //充值规则
            $rule = db('user_charge_rule')->find($rule_id);
            if (!$rule) {
                $result['code'] = 0;
                $result['msg'] = lang('Recharge_rule_does_not_exist');
                echo json_encode($result, true);
                exit;
            }
            $money = $rule['money'];
            $coin = $rule['coin'] + $rule['give'];
        }

        $notice_id = NOW_TIME . $uid;//订单号码

        //防止重复下单
        $exits = db('user_charge_log')->where('order_id=' . $notice_id)->find();
        if ($exits) {
            $result['code'] = 0;
            $result['msg'] = lang('Frequent_operation');
            echo json_encode($result, true);
            exit;
        }

        $order_info = [
            'uid'           => $uid,
            'money'         => $money,
            'coin'          => $coin,
            'refillid'      => $rule_id,
            'addtime'       => NOW_TIME,
            'status'        => 0,
            'type'          => $pid,
            'order_id'      => $notice_id,
            'pay_type_id'   => $pid,
            'recharge_type' => 2,
        ];
        //增加订单记录
        db('user_charge_log')->insert($order_info);

        $class_name = 'wechat_js_pay';
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $pay = $o->get_payment_code($pay_type, $rule, $notice_id, $open_id);

        $result['pay'] = $pay;
        //dump($pay);die();
        echo json_encode($result, true);
        //return_json_encode($result);
    }

    /*
     * 微信外充值
     * */
    public function pay_coin()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rid'));
        $money = intval(input('param.money'));
        //$user_info = check_login_token($uid, $token);
        $user_info = get_user_base_info($uid);
        if (!$user_info) {
            $result['code'] = 0;
            $result['msg'] = lang('user_does_not_exist');
            echo json_encode($result, true);
            exit;
        }

        if ($money > 0) {
            $config = load_cache('config');
            $recharge_min_money = $config['recharge_min_money'];
            if ($money < $recharge_min_money) {
                $result['code'] = 0;
                $result['msg'] = lang('Minimum_recharge') . $recharge_min_money . lang('ADMIN_MONEY');
                echo json_encode($result, true);
                exit;
            }
            $recharge_money_proportion = $config['recharge_money_proportion'];
            $coin = $money * $recharge_money_proportion;
            $rule_id = -1;
            $rule['name'] = $config['currency_name'] . lang('Recharge');
            $rule['money'] = $money;
        } else {
            //充值规则
            $rule = db('user_charge_rule')->find($rule_id);
            if (!$rule) {
                $result['code'] = 0;
                $result['msg'] = lang('Recharge_rule_does_not_exist');
                echo json_encode($result, true);
                exit;
            }
            $money = $rule['money'];
            $coin = $rule['coin'] + $rule['give'];
        }
        //充值渠道
        $pay_type = db('pay_menu')->find($pid);

        if (!$pay_type) {
            $result['code'] = 0;
            $result['msg'] = lang('Recharge_type_does_not_exist');
            echo json_encode($result, true);
            exit;
        }

        $notice_id = NOW_TIME . $uid;//订单号码

        //防止重复下单
        $exits = db('user_charge_log')->where('order_id=' . $notice_id)->find();
        if ($exits) {
            $result['code'] = 0;
            $result['msg'] = lang('Frequent_operation');
            echo json_encode($result, true);
            exit;
        }

        $order_info = [
            'uid'           => $uid,
            'money'         => $money,
            'coin'          => $coin,
            'refillid'      => $rule_id,
            'addtime'       => NOW_TIME,
            'status'        => 0,
            'type'          => $pid,
            'order_id'      => $notice_id,
            'pay_type_id'   => $pid,
            'recharge_type' => 2,
        ];
        //增加订单记录
        db('user_charge_log')->insert($order_info);

        $class_name = $pay_type['class_name'];
        if (substr_count($pay_type['class_name'], 'wechat') > 0) {
            $class_name = 'wechat_h5_pay';
        } else {
            $class_name = 'alipay_h5_pay';
        }
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $pay = $o->get_payment_code($pay_type, $rule, $notice_id, 'h5');

        $result['pay'] = $pay;
        //dump($result);die();
        echo json_encode($result, true);
        exit;
    }

    public function get_user_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $user_info = db('user')->where('user_type = 2 and id = ' . $uid)->find();
        if ($user_info) {
            $result['data'] = $user_info;
        } else {
            $result['code'] = 0;
        }
        echo json_encode($result);
    }

    function geturl($CODE)
    {
        $config = load_cache('config');
        $APP_ID = trim($config['wechat_public_appid']);
        $SECRET = trim($config['wechat_public_secret']);
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $APP_ID . '&secret=' . $SECRET . '&code=' . $CODE . '&grant_type=authorization_code';
        $headerArray = array("Content-type:application/json;", "Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
        //dump($output);die();
        $output = json_decode($output, true);
        return $output;
    }

    /*
     * 充值协议*/
    public function protocol()
    {
        $portal = Db::name("portal_post")
            ->where("post_title like '%充值%' and post_type=1 and post_status=1")
            ->find();
        if ($portal) {
            $portal['post_content'] = htmlspecialchars_decode($portal['post_content']);
        } else {
            $portal = [
                'post_title'   => '',
                'post_content' => '',
            ];
        }
        $this->assign('portal', $portal);
        return $this->fetch();
    }

    /*
     * 充值记录*/
    public function get_charge_log()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = input('page');
        $list = db('user_charge_log')
            ->alias('c')
            ->join('user u', 'u.id=c.uid')
            ->join('pay_menu p', 'p.id=c.pay_type_id')
            //->where('p.recharge_type = 2')
            ->field('u.user_nickname,c.*,p.pay_name,p.merchant_id')
            ->order('c.addtime desc')
            ->page($page)
            ->select();
        $result['list'] = $list;
        return_json_encode($result);
    }
}
