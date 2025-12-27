<?php

namespace app\api\controller;

use app\vue\model\ShopModel;

class ShopApi extends Base
{
    // 获取用户购买的商品使用列表
    public function get_user_shop()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $ShopModel = new ShopModel();
        // 1座驾 2头饰 3 聊天气泡
        $type = intval(input('param.type')) ? intval(input('param.type')) : '';
        // 获取商城列表
        $list = $ShopModel->get_user_shop($uid, $type);

        $result['data'] = $list;

        return_json_encode($result);
    }
}