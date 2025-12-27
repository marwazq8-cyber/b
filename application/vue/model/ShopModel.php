<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class ShopModel extends Model
{
    /* 获取商城列表 */
    public function get_shop($where, $p, $limit)
    {

        $list = db("shop")->alias('s')
            ->join("shop_price p", "s.id=p.shop_id and p.status=1")
            ->field("s.id,s.name,s.img,s.svga,s.type,p.coin,p.month")
            ->where($where)
            ->group("p.shop_id")
            ->order("s.sort desc,p.sort desc")
            ->limit($p, $limit)
            ->select();

        return $list;
    }

    /* vip 特权查询*/
    public function get_shop_vip($where)
    {

        $list = db("shop")->alias('s')
            ->field("s.id,s.name,s.img,s.svga,s.type")
            ->where($where)
            ->order("s.sort desc")
            ->select();

        return $list;
    }

    // 获取商品价格列表
    public function get_buy_list($where)
    {

        $list = db("shop_price")->where($where)->order("sort desc")->select();

        return $list;
    }

    // 查询商品是否续费的信息
    public function get_renewal($where)
    {

        $list = db("shop_user")->where($where)->order("endtime desc")->find();

        return $list;
    }

    // 获取商品购买信息
    public function get_shop_price($where)
    {

        $list = db("shop")->alias('s')
            ->join("shop_price p", "s.id=p.shop_id")
            ->field("s.id,s.name,s.type,p.coin,p.month")
            ->where($where)
            ->find();

        return $list;
    }

    // 修改购买商品的状态
    public function upd_shop_status($where, $data)
    {

        $list = db("shop_user")->where($where)->update($data);

        return $list;
    }

    // 添加购买商品记录

    public function add_shop_log($data)
    {

        $res = db('shop_user')->insertGetId($data);

        return $res;
    }

    // 获取商城背包
    public function get_shop_backpack($where)
    {

        $list = db("shop_user")->alias('u')
            ->join("shop s", "s.id=u.shop_id")
            ->field("s.svga,s.img,s.name,u.id,u.shop_id,u.shop_price_id,u.endtime,u.is_use,u.type")
            ->where($where)
            ->order("u.addtime desc")
            ->select();

        return $list;
    }
    // 获取用户开始使用的商品信息
    /*public function get_user_shop($uid,$type=''){
    	 
    	$where = "s.status=1 and u.status=1 and u.is_use=1 and u.uid=".$uid." and endtime >=".NOW_TIME;

        $where .= $type ? " and s.type=".$type : '';

    	$list = db("shop_user")->alias('u')
 		 	->join("shop s", "s.id=u.shop_id")
 		 	->field("s.svga,s.img,s.name,u.id,u.shop_id,u.shop_price_id,u.endtime,u.is_use,u.type")
            ->where($where)
            ->order("u.addtime desc")
            ->select();

        $data=array(
        	'car_url'=>'',
        	'car_name'=>'',
        	'car_svga_url'=>'',
        	'headwear_url'=>'',
        	'headwear_name'=>'',
        	'chat_bubble_url'=>'',
        	'chat_bubble_name'=>'',
        );
        if(count($list) > 0){
        	foreach ($list as $v) {
        		if($v['type'] == 1){
        			$data['car_name'] =$v['name'];
        			$data['car_url'] =$v['img'];
        			$data['car_svga_url'] =$v['svga'];
        		}
        		if($v['type'] == 2){
        			$data['headwear_name'] =$v['name'];
        			$data['headwear_url'] =$v['img'];
        		}
        		if($v['type'] == 3) {
        			$data['chat_bubble_name'] =$v['name'];
        			$data['chat_bubble_url'] =$v['img'];
        		}
        	}
        }

        return $data;
    }*/

    // 获取用户开始使用的商品信息 1勋章 2主页特效 3头像框 4聊天气泡 5聊天背景 6徽章 7进场特效 8麦克风 9昵称铭牌 10定制名片 11进场座驾
    public function get_user_shop($uid)
    {
        $os = trim(input('param.os'));
        $list = db('user_dress_up')
            ->alias('u')
            ->join('dress_up d', 'd.id=u.dress_id')
            ->field('d.*')
            ->where('u.uid = ' . $uid . ' and u.status = 1 and u.endtime > ' . NOW_TIME)
            ->select();

        $data = array(
            'medal_name' => '',
            'medal_url' => '',
            'home_page_name' => '',
            'home_page_url' => '',
            'headwear_url' => '',
            'headwear_svga' => '',
            'headwear_name' => '',
            'chat_bubble_url' => '',
            'chat_bubble_ios_url' => '',
            'chat_bubble_name' => '',
            'chat_bg_name' => '',
            'chat_bg_url' => '',
            'badge_name' => '',
            'badge_url' => '',
            'mike_name' => '',
            'mike_url' => '',
            'car_url' => '',
            'car_name' => '',
            'car_svga_url' => '',
            'nickname_card_name' => '',
            'nickname_card_url' => '',
            'business_card_name' => '',
            'business_card_url' => '',
            'entry_vehicles_name'=> '',
            'entry_vehicles_url'=>'',
            'entry_vehicles_svga_url'=>''
        );
        if (count($list) > 0) {
            foreach ($list as $v) {
                switch ($v['type']) {
                    case 1:
                        //勋章
                        $data['medal_name'] = $v['name'];
                        $data['medal_url'] = $v['icon'];
                        break;
                    case 2:
                        //主页特效
                        $data['home_page_name'] = $v['name'];
                        $data['home_page_url'] = $v['icon'];
                        break;
                    case 3:
                        //头像框
                        $data['headwear_name'] = $v['name'];
                        $data['headwear_url'] = $v['icon'];
                         $data['headwear_svga'] = $v['svga'];
                        break;
                    case 4:
                        //聊天气泡
                        $data['chat_bubble_name'] = $v['name'];
                        $data['chat_bubble_url'] = $v['icon'];
                        $data['chat_bubble_ios_url'] = $v['ios_icon'];
                        break;
                    case 5:
                        //聊天背景
                        $data['chat_bg_name'] = $v['name'];
                        $data['chat_bg_url'] = $v['icon'];
                        break;
                    case 6:
                        //6徽章
                        $data['badge_name'] = $v['name'];
                        $data['badge_url'] = $v['icon'];
                        break;
                    case 7:
                        //7进场动画
                        $data['car_name'] = $v['name'];
                        $data['car_url'] = $v['icon'];
                        $data['car_svga_url'] = $v['img_bg'];
                        break;
                    case 8:
                        //8麦克风
                        $data['mike_name'] = $v['name'];
                        $data['mike_url'] = $v['icon'];
                        break;
                    case 9:
                        //9昵称铭牌
                        $data['nickname_card_name'] = $v['name'];
                        $data['nickname_card_url'] = $v['icon'];
                        break;
                    case 10:
                        //10定制名片
                        $data['business_card_name'] = $v['name'];
                        $data['business_card_url'] = $v['icon'];
                        break;
                    case 11:
                        // 11 进场座驾
                        $data['entry_vehicles_name'] =$v['name'];
                        $data['entry_vehicles_url'] =$v['icon'];
                        $data['entry_vehicles_svga_url'] =$v['img_bg'];
                        break;
                }
            }
        }

        return $data;
    }
}

?>