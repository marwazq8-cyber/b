<?php

namespace app\game\controller;

use app\api\controller\Base;
use app\common\model\User;
use app\game\service\LXGameService;
use think\Request;

class LXGameApi extends Base
{
    private $signKey = '';

    protected function _initialize()
    {
        parent::_initialize();

        $this->signKey = 'a52d9f6588232f2b62f94002efd26501';
    }

    /**
     * 获取用户信息
     * */
    public function getUserInfo()
    {
        // 获取Request实例
        $gameId = get_input_param_str('gameId');
        $uid = get_input_param_str('uid');
        $token = get_input_param_str('token');
        $sign = get_input_param_str('sign');

        // 验签 sign=MD5(gameId+uid+key)

        if (md5($gameId . $uid . $this->signKey) != $sign) {
            return json(['errorCode' => 1, 'errorMsg' => '验签失败', 'data' => null]);
        }

        $userInfo = User::getByToken($token);

        if (!$userInfo) {
            return json(['errorCode' => 1, 'errorMsg' => '用户不存在', 'data' => null]);
        }

        $returnUserInfo = [
            'uid'      => $userInfo['id'],
            'nickname' => $userInfo['user_nickname'],
            'avatar'   => $userInfo['avatar'],
            'coin'     => $userInfo['coin'],
            'vipLevel' => $userInfo['level'],
        ];

        return json(['errorCode' => 0, 'errorMsg' => '', 'data' => $returnUserInfo]);
    }


    /**
     * 用户玩游戏过程中发生游戏币的变化都会实时请求APP服务器
     * */
    public function submitFlow()
    {
        // 获取参数并验签 sign=MD5(orderId+gameId+uid+coin+type+key)
        $gameId = get_input_param_str('gameId');
        $uid = get_input_param_str('uid');
        $token = get_input_param_str('token');
        $orderId = get_input_param_str('orderId');
        $coin = get_input_param_str('coin');
        $type = get_input_param_str('type');
        $sign = get_input_param_str('sign');

        if (md5($orderId . $gameId . $uid . $coin . $type . $this->signKey) != $sign) {
            return json(['errorCode' => 1, 'errorMsg' => '验签失败', 'data' => null]);
        }

        $data = [
            'orderId' => $orderId,
            'gameId'  => $gameId,
            'uid'     => $uid,
            'coins'   => $coin,
            'type'    => $type,
        ];

        $gameService = new LXGameService();
        //$gameService->submitFlow($token, $data);

        return json(['errorCode' => 0, 'errorMsg' => '', 'data' => $gameService->submitFlow($token, $data)]);
    }
}