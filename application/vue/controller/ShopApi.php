<?php

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\ShopModel;
use app\api\model\UserModel;


class ShopApi extends Base
{
	protected function _initialize()
    {
        parent::_initialize();

        $this->ShopModel = new ShopModel();

        $this->UserModel = new UserModel();
    }

    // 获取商城列表
	public function get_shop(){

		$result = array('code'=>1,'msg'=>'');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 分页
        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;
        // 1座驾 2头饰 3 聊天气泡
        $type = intval(input('param.type')) ? intval(input('param.type')) : 1;
        // 系统金币单位名称
        $config = load_cache('config');
        
        $limit = 10 ;
        $p = $page * $limit;

        $where = "s.type=".$type." and s.status=1 and s.is_vip=0";
        // 获取商城列表
        $list = $this->ShopModel ->get_shop($where,$p,$limit);
        // 系统钻石单位名称
        $data['coin_name'] =$config['currency_name'];
        $data['type'] =$type;
    
        $data['list'] = $list;
        $result['data'] = $data;
                
        return_json_encode($result);
	}
	// 显示购买的价格
	public function get_buy_list(){

		$result = array('code'=>1,'msg'=>'');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 商品id
        $id = intval(input('param.id'));
        // 1座驾 2头饰 3聊天气泡
        $type = intval(input('param.type'));

        $where = "shop_id=".$id." and status=1";
        // 获取商城价格列表
        $list = $this->ShopModel ->get_buy_list($where);
        // 查询商品是否续费的信息
        $shop_user_where="uid=".$uid." and shop_id=".$id." and type=".$type." and status=1 and endtime >".NOW_TIME;

        $buy_log= $this->ShopModel ->get_renewal($shop_user_where);
		
		$data['endtime'] = $buy_log ? date('Y-m-d',$buy_log['endtime']) : '';

		$data['list'] = $list;

        $result['data'] = $data;
                
        return_json_encode($result);
	}
	// 商品支付接口
	public function shop_price_pay(){

		$result = array('code'=>0,'msg'=>'');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 商品id
        $id = intval(input('param.id'));
        // 价格表id
        $price_id = intval(input('param.price_id'));
         // 默认是否开启
        $shop_open = intval(input('param.shop_open'));

        $user_info = check_login_token($uid, $token,['is_voice_online','last_login_ip']);

        $where="s.id=".$id." and s.status=1 and p.status=1 and p.id=".$price_id;

        $recharge=$this->ShopModel ->get_shop_price($where);

        if(!$recharge){
        	$result['msg'] = lang('data_error');
        	return_json_encode($result);
        }
        if($recharge['coin'] > $user_info['coin']){
        	$result['msg'] = lang('Insufficient_Balance');
        	return_json_encode($result);
        }
        // 查询商品是否续费的信息 NOW_TIME
        $shop_user_where="uid=".$uid." and shop_id=".$id." and type=".$recharge['type']." and status=1";

        $buy_log= $this->ShopModel ->get_renewal($shop_user_where);

        $time = NOW_TIME;
        // 启动事务
        db()->startTrans();
        try {
        	$is_renewal = 0 ;
	        if($buy_log){
	        	// 未购买前结束的时间
	        	if($buy_log['endtime'] > NOW_TIME){

	        		$time =  $buy_log['endtime'];
	        		// 1续费
	        		$is_renewal= 1;
	        	}

	        	$upd_shop_where = "id = ".$buy_log['id'];
	        	$upd_data=array(
	        		'status' =>0,
	        		'is_use' => 0,
	        	);
	        	// 修改购买商品
	        	$this->ShopModel ->upd_shop_status($upd_shop_where,$upd_data);
	        }
            if($shop_open){
                // 修改购买商品
                $this->ShopModel ->upd_shop_status("uid=".$uid." and type=".$recharge['type'],array('is_use' => 0));
            }
	       
	        // 获取购买商品的结束时间
	        $endtime = $time + 60*60*24*30*$recharge['month'];
	        // 获取记录类型
	        if($recharge['type'] == 1){
                $type =13;
            }else{
                 $type =  $recharge['type'] == 2 ? 14 : 15;
            }
	        // 扣除用户金额 减少用户钻石 type 13 座驾 14 头饰 15聊天气泡
            $charging_coin_res = $this->UserModel -> deduct_user_coin($user_info,$recharge['coin'],$type);

            if($charging_coin_res || $recharge['coin'] <= 0){
            	// 增加用户购买商品记录
            	$shop_data=array(
            		'uid' =>$uid,
            		'shop_id' =>$id,
            		'shop_price_id' =>$price_id,
            		'coin'  =>$recharge['coin'],
            		'month' =>$recharge['month'],
					'type' =>$recharge['type'],
					'addtime' =>NOW_TIME,
					'endtime' =>$endtime,
					'is_renewal' =>$is_renewal,
					'status' =>1,
					'is_use' =>$shop_open ? 1 : $buy_log['is_use'],
            	);
				
				$shop_log_id = $this->ShopModel ->add_shop_log($shop_data);
				// 增加总消费记录
                add_charging_log($uid, 0, $type, $recharge['coin'], $shop_log_id, 0);
                $result['code'] = 1;
                $result['msg'] = lang('Purchase_succeeded');
    			 // 提交事务
            	db()->commit();
            }else{
            	$result['msg'] = lang('Failed_to_deduct_amount');
	            // 回滚事务
	            db()->rollback();
            }
        } catch (\Exception $e) {

            $result['msg'] = lang('Purchase_failed');
            // 回滚事务
            db()->rollback();
        }
        return_json_encode($result);
    }
    // 获取商城背包
    public function get_shop_backpack(){

    	$result = array('code'=>1,'msg'=>'');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 1座驾 2头饰 3 聊天气泡
        $type = intval(input('param.type')) ? intval(input('param.type')) : 1;

        $where = "s.type=".$type." and s.status=1 and u.status=1 and u.uid=".$uid." and endtime >=".NOW_TIME;
        // 获取商城列表
        $list = $this->ShopModel ->get_shop_backpack($where);

        $result['data'] = $list;
                
        return_json_encode($result);
    }
    // 开启使用商城中的背包操作
    public function upd_shop_backpack(){
    	
    	$result = array('code'=>1,'msg'=>'');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 购买记录列表id
        $id = intval(input('param.id'));
         // 1座驾 2头饰 3 聊天气泡
        $type = intval(input('param.type')) ? intval(input('param.type')) : 1;

        $status = $this->ShopModel ->get_renewal("uid=".$uid." and type=".$type." and id=".$id);
        if (!$status) {
            $result['code']= 0;
            $result['msg'] =lang('operation_failed');
            return_json_encode($result);
        }

        $is_use = $status['is_use'] == 1 ? 0 : 1 ;
        // 修改购买商品
        if($is_use == 1){
            $this->ShopModel ->upd_shop_status("uid=".$uid." and type=".$type,array('is_use' => 0));
        }
	    // 修改购买商品
	    $status = $this->ShopModel ->upd_shop_status("uid=".$uid." and type=".$type." and id=".$id,array('is_use' => $is_use));

	    if(!$status){
	    	$result['code']= 0;
	    	$result['msg'] =lang('operation_failed');
	    }

	    return_json_encode($result);
    }
}
?>