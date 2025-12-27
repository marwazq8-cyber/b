<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/28
 * Time: 10:01
 */

class applepay_app_pay
{
    public function get_payment_code($pay,$rule,$order_id)
    {
        //md5加密
        $pay_info['order_id'] = $order_id;
        $pay_info['post_url'] = $_SERVER['SERVER_NAME'];       //生成指定网址
        $pay_info['is_wap']  = 0;
        return $pay_info;
    }


    public function notify($request){

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $request["order_id"]) -> find();
        /*$pay_info = db('pay_menu') -> find($order_info['pay_type_id']);*/

        if(!$order_info){
            notify_log( $request["order_id"],'0','充值回调成功,error:充值记录不存在');
            echo 'fail';
            exit;
        }

        //$key = $pay_info['public_key'];          //商户密钥，千应官网注册时密钥
        $orderid = $request["order_id"];        //订单号

        //是否是沙盒模式，上架后改成0
        $sandbox = 1;
        
        $receipt_data = trim(input('receipt_key'));
        $data = null;
        $result = array('code' => 1, 'msg' => '');

        if($sandbox == 1)
        {
            $data = $this->acurl($receipt_data,0);
            //如果是沙盒数据 则验证沙盒模式
            if($data['status']=='21007'){
                //请求验证
               //log_err_file(array(__FILE__,__LINE__,__METHOD__,$data));
                $data = $this->acurl($receipt_data, 1);
            }
        }
        else
        {
            $data = $this->acurl($receipt_data, 0);
        }

        if ($data['status'] == 0)
        {
            $payres = pay_call_service($orderid,'appley_pay',$data);
        }
        else
        {
            notify_log( $request["order_id"],'0','验证失败1');
            $result['code'] = 0;
            $result['msg'] = '支付失败，验证失败(001)';
        }
        return_json_encode($result);
    }

    private function acurl($receipt_data,$sandbox) {
        //正式购买地址 沙盒购买地址
        $url_buy     = "https://buy.itunes.apple.com/verifyReceipt";
        $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
        $url = $sandbox ? $url_sandbox : $url_buy;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("receipt-data" => $receipt_data)));//$this->encodeRequest());
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        curl_close($ch);
        if ($errno != 0) {
            //throw new Exception($errmsg, $errno);
            $data = array();
            $data['status'] = $errno;
            $data['error'] = $errmsg;

            return $data;
        }else{
            return json_decode($response,1);
        }
    }
}