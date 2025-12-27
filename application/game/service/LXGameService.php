<?php

namespace app\game\service;

use app\common\exception\ApiException;
use app\common\exception\LXApiException;
use app\common\model\User;
use app\common\model\UserConsumeLog;
use app\common\service\UserAccountService;
use Exception;
use think\Db;

class LXGameService
{
    protected $gameLogName = 'LXGame';

    /**
     * 处理订单
     */
    public function submitFlow($token, $data)
    {

        bogokjLogPrint($this->gameLogName, ['tag' => 'submitFlow', 'data' => $data]);

        $userInfo = User::getByToken($token);

        if (!$userInfo) {
            throw new LXApiException('用户不存在', 0);
        }

        $uid = $userInfo['id'];

        $config = load_cache('config');
        //$limitIds = explode(',', config('joy_game.limitUserIds'));
//        $limitIds = explode(',', $config['joy_game_test_user_ids']);
//
//        $isOpenGame = $config['joy_game_is_open'];
//        if (!$isOpenGame && !in_array($uid, $limitIds)) {
//            throw new LXApiException('游戏已关闭', 0);
//        }

        bogokjLogPrint($this->gameLogName, ['tag' => '用户信息', 'data' => json_encode($userInfo)]);

        $transactionId = $data['orderId'];
        // 根据流水号查询订单记录
        $gameFoundFlowLog = model('LxGameFundFlowLog');
        $orderRecord = $gameFoundFlowLog->where('transactionId', $transactionId)->find();

        // 处理查询结果
        if ($orderRecord) {
            // 没有找到订单记录
            throw new LXApiException('交易订单已存在！', 0);
        }

        try {
            // 启动事务
            $availableCoin = Db::transaction(function () use ($data, $userInfo, $gameFoundFlowLog) {

                $userBalanceCoin = db('user')->where('id', $userInfo['id'])->lock(true)->value('coin');

                if ($data['coins'] < 0) {
                    throw new LXApiException('金额不能小于0！', 0);
                }

                $remark = 'LXGame:' . $data['type'] . ':' . $data['coins'];

                $insert = [
                    'userId'        => $userInfo['id'],
                    'transactionId' => $data['orderId'],
                    'coins'         => $data['coins'],
                    'type'          => $data['type'],
                    'remark'        => $remark,
                    'createTime'    => time(),
                ];

                $gameFoundFlowLog->insert($insert);

                $userAccountService = new UserAccountService();
                $userLogData = array(
                    'content'      => $remark,
                    'genre'        => $data['type'] == 1 ? 2 : 1,
                    'coin'         => $data['coins'],
                    'log_type'     => 23,
                    'buy_type'     => 23,
                    'user_balance' => $userBalanceCoin,
                );
                $userAccountService->logAccountChange(
                    $userInfo['id'],
                    'diamonds',
                    $data['type'],
                    $userLogData
                );

                // 1：减金币，2：加金币
                if ($data['type'] == 1) {
                    $coin = bcsub($userBalanceCoin, $data['coins'], 2);
                    // 减金币
                    if ($coin < 0) {
                        throw new LXApiException('用户余额不足！', 0);
                    }

                    $userConsumeLogModel = new UserConsumeLog();
                    $logConsume = [
                        'coin'      => $coin,
                        'table_id'  => 0,
                        'host_coin' => 0,
                        'content'   => $userLogData['content'],
                    ];

                    $userConsumeLogModel->userConsumeLog($userInfo['id'], 0, UserConsumeLog::TYPE_LX_GAME, $logConsume);
                } else {
                    // 加金币
                    $coin = bcadd($userBalanceCoin, $data['coins'], 2);
                }

                User::update(['id' => $userInfo['id'], 'coin' => $coin]);

                // 查询用户余额
                //$userInfo = User::get($userInfo['id']);
                bogokjLogPrint($this->gameLogName, ['tag' => 'decryptParam', 'data' => '返回余额：' . $coin . '-流水号：' . $data['orderId']]);

                return $coin;
            });
        } catch (Exception $e) {
            // 这里你可以获取事务失败的相关信息
            bogokjLogPrint($this->gameLogName, ['tag' => 'error', 'msg' => $e->getMessage()]);
            throw new LXApiException($e->getMessage(), 200004);
        }

        return ['availableCoin' => (int)$availableCoin];

    }
}