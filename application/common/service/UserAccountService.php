<?php

namespace app\common\service;

use app\common\exception\ApiException;
use app\common\model\UserLog;

class UserAccountService
{

    /**
     * 账户日志变更
     * @param int    $userId      用户id
     * @param string $changeUnity 变更账户 diamonds=钻石,income=收益
     * @param int    $changeType  变更操作 1=减少,2=增加
     * @param array  $data
     * */
    public function logAccountChange(int $userId, string $changeUnity, int $changeType, array $data)
    {
        if (!isset($data['buy_type']) || !isset($data['log_type'])) {
            throw new ApiException('系统错误！', 200003);
        }

        $userLogData = [
            'genre' => $data['genre'],
            'coin'  => $data['coin'],
        ];
        $userLogData['uid'] = $userId;
        $userLogData['center'] = $data['content'];
        $userLogData['type'] = $changeUnity == 'diamonds' ? 1 : 2;
        $userLogData['buy_type'] = $data['buy_type'];
        $userLogData['addtime'] = time();

        $userLog = new UserLog();
        if (!$userLog->save($userLogData)) {
            throw new ApiException('系统错误！', 200001);
        }

        if ($changeUnity == 'diamonds') {
            save_coin_log($userId, $changeType == 2 ? $data['coin'] : '-' . $data['coin'], 1, $data['log_type'],
                $data['content'], $data['user_balance']);
        }
    }

}