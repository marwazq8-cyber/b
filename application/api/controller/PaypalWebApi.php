<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-08-25
 * Time: 15:04
 */

namespace app\api\controller;

use think\Db;
use app\api\controller\BaseApi;

class PaypalWebApi extends Base
{
    /**
     * 展示支付页面
     */
    public function pay_index()
    {

        $result = array('code' => 0, 'msg' => '');
            $client_id = "ATySIIpm5wnt3xKnHpuRipPfq5pcZTzzA4t9elskTDYwAIfIrjUR0LKJvzbiFFdH_ztJ-Gmpz0jaGJA2";
        $order_id = trim($_REQUEST['notice_id']);//订单号id

        $user_id = intval($_REQUEST['user_id']);
        $token = trim($_REQUEST['token']);//支付id

        $user_info = db('user')
            ->field("id")
            ->where(['id' => $user_id, 'token' => $token])
            ->find();
        if (!$user_info) {
            $result['msg'] = lang('Login_expiration');
            return_json_encode($result);
        }

        $pay_config = db('pay_menu')->where("class_name ='PayPalWeb_pay' and status = 1")->find();
        if (!$pay_config) {
            $result['msg'] = lang('Recharge_type_does_not_exist');
            return_json_encode($result);
        }

        $client_id = $pay_config['public_key'];

        // 获取订单价格
        $orders = db('user_charge_log')->where('order_id="' . $order_id . '"')->find();

        $this->assign('Loading_desperately', "加载中...");
        $this->assign('got_it', '知道了');
        $this->assign('order', $orders);
        $this->assign('token', $token);
        $this->assign('client_id', $client_id);

        return $this->fetch();

    }

    /**
     * 支付成功第三方回调
     */
    public function Callback()
    {
        //    if(!$this->request->isPost()) die();
        $notify_str = '失败';
        //记录支付回调信息
        if (!empty($_POST)) {
            $notify_str = "支付回调信息:\r\n";
            foreach ($_POST as $key => $value) {
                $notify_str .= $key . "=" . $value . ";\r\n";
            }
        }
        // 记录日志
        $this->log_results($notify_str);

        //ipn验证
        $data = $_POST;
        $data['cmd'] = '_notify-validate';

        $url = IS_PAYPALWEB == 1 ? "https://www.sandbox.paypal.com/cgi-bin/webscr" : "https://www.paypal.com/cgi-bin/webscr";  // 沙盒

        $res = $this->https_request($url, $data);
        //记录支付ipn验证回调信息
        $this->log_results($res);


        if (!empty($res)) {
            if (strcmp($res, "VERIFIED") == 0) {

                if ($_POST['payment_status'] == 'Completed' || $_POST['payment_status'] == 'Pending') {

                    $this->upd_status($_POST['txn_id']);
                    //付款完成，这里修改订单状态
                    return 'success';
                }
            } elseif (strcmp($res, "INVALID") == 0) {
                //未通过认证，有可能是编码错误或非法的 POST 信息
                return 'fail';
            }
        } else {
            //未通过认证，有可能是编码错误或非法的 POST 信息

            return 'fail';

        }
        return 'fail';
    }

    /**
     * 接口支付成功改变状态
     */
    public function payment_successful()
    {
        $order_id = $_REQUEST['orderid'];//支付id

        $token = $_REQUEST['token'];//支付id

        // 记录日志
        $path = CMF_ROOT . "/data/runtime/paypalweb";

        $this->log_results($order_id);
        // $this->log_results('token:'.$token.'orderid'.$order_id);

        // $user_info = db('user')->field("id")->where(['token' => $token])->find();
        // if (!$user_info) {
        //     $result['status'] = 0;
        //     $result['error'] = '用户未登录';
        //     return_json_encode($result);
        // }
        // 查询订单信息
        $result = $this->upd_status($order_id);

        return_json_encode($result);
    }

    /**
     * 支付成功改变状态
     * 第三方订单号 $outer_notice_sn
     */
    public function upd_status($outer_notice_sn)
    {
        $result = array("error" => '付款失败，请重新付款', "status" => 0);
        $tokens = $this->access_token();

        $token = $tokens['access_token'];
        //查询是否有订单
        $order = $this->get_curlOrder($outer_notice_sn, $token, '');

        $this->log_results(json_encode($order));

        if ($order['status'] == 'COMPLETED') {
            // 服务器订单id
            $order_id = $order['purchase_units'][0]['reference_id'];
            if ($order_id) {
                // 支付成功改变状态
                $payment_notice = db('user_charge_log')->where('order_id="' . $order_id . '"')->find();

                if ($payment_notice && $payment_notice['action'] == 1) {
                    return array("error" => "支付成功APP查看", "status" => 1);
                } else {
                    //付款完成，这里修改订单状态 /修改第三方订单号
                    pay_call_service($order_id, $outer_notice_sn);
                    return array("error" => "支付成功APP查看", "status" => 1);
                }
            }
        }

        return $result;
    }

    /**
     * 获取token
     */
    public function access_token()
    {


        // $pay_config = db('pay_menu')->where("class_name ='PayPalWeb_pay' and status = 0")->find();

        // $clientId = $pay_config['public_key'];
        // $secret = $pay_config['private_key'];
        $clientId = 'ATySIIpm5wnt3xKnHpuRipPfq5pcZTzzA4t9elskTDYwAIfIrjUR0LKJvzbiFFdH_ztJ-Gmpz0jaGJA2';
        $secret = 'EHBwvOH7oTAIDx_7IcA9AzdoSktB7nbD7NQJZtgL0ofQwilnC-XvzOQaB-qeZPc7l4MVtutaz3sAEisc';
        // 1生产环境 2沙箱环境
        $url = IS_PAYPALWEB == 1 ? "https://api-m.sandbox.paypal.com/v1/oauth2/token/" : "https://api-m.paypal.com/v1/oauth2/token/";
        $url = 'https://api-m.paypal.com/v1/oauth2/token/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        $result = curl_exec($ch);
        curl_close($ch);
        if (empty ($result)) {
            return false;
        } else {
            return json_decode($result, true);
        }
    }

    // 获取curl订单信息 根据token查询订单方法
    public function get_curlOrder($orderid, $token, $type = '')
    {
        if (empty ($orderid) || empty ($token)) {
            return false;
        }

        // 1生产环境 2沙箱环境
        // $URL = IS_PAYPALWEB == 1 ? "https://api-m.sandbox.paypal.com/v2/checkout/orders/" : "https://api-m.paypal.com/v2/checkout/orders/";
        $URL = 'https://api-m.paypal.com/v2/checkout/orders/';
        $URL .= $orderid;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, '0');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, '0');
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => array(
                "Authorization: Bearer {$token}",
                "Content-Type:application/json"
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * 发送post请求
     * @param string $url       请求地址
     * @param array  $post_data post键值对数据
     * @return string
     * @author     ganyuanjiang  <3164145970@qq.com>
     * @createtime 2017-07-26 14:06:04
     */
    function https_request($url, $data = null)
    {
        header("Content-type: text/html; charset=utf-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }

        curl_close($ch);
        return $tmpInfo;

    }

    /**
     * 记录自定义日志
     * @param $msg  错误信息
     * @param $type 写入类型 wechat aliyun
     * @return     [type] [description]
     * @author     gyj  <375023402@qq.com>
     * @createtime 2018-08-24 14:12:01
     */
    function log_results($msg = '', $type = 'paypalweb')
    {
        // 记录日志
        $path = CMF_ROOT . "/data/runtime/" . $type;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . "/" . date("Y-m-d") . ".txt", "执行日期：" . date("Y-m-d H:i:s") . "\n " . $msg . "\n\n", FILE_APPEND);

    }
}
