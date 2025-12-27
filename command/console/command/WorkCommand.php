<?php

namespace app\console\command;

use Redis;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WorkCommand extends Command
{
    protected function configure()
    {
        $this->setName('work:start')
            ->setDescription('启动队列消费');
    }

    protected function execute(Input $input, Output $output)
    {
        require_once('./public/system/tim/TimApi.php');
        require_once('./public/system/common.php');
        require_once(ROOT_PATH . 'application/common/log.php');

        $redis = new Redis();
        $connectRes = $redis->connect('127.0.0.1', 6379);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        // 设置键名前缀
        $redisPrefix = 'bogokj-intl-voice-chat00001:';
        $redis->setOption(Redis::OPT_PREFIX, $redisPrefix);

        $output->writeln('连接：' . $connectRes);

        $GLOBALS['redis'] = $redis;

        // 要监听的队列数组
        $queues = array('gift');

        while (true) { // 通过while true实现无限循环
            $result = $redis->blPop($queues, 0); // 使用BLPOP替换LPOP，以实现当消息队列中没有消息时，阻塞等待

            if ($result) {
                // 获取队列名和元素值
                $queueName = $result[0];
                $element = $result[1];

                // 根据不同的队列名执行不同的处理逻辑
                switch ($queueName) {
                    case $redisPrefix . 'gift':
                        // 处理 queue1 的逻辑
                        echo "从队列 gift 中获取到了元素：\n";
                        var_dump($element);

                        $this->sendGift(json_decode($element, true));
                        // 执行 queue1 的处理逻辑
                        break;
                    case 'queue2':
                        // 处理 queue2 的逻辑
                        echo "从队列 queue2 中获取到了元素：$element\n";
                        // 执行 queue2 的处理逻辑
                        break;
                    case 'queue3':
                        // 处理 queue3 的逻辑
                        echo "从队列 queue3 中获取到了元素：$element\n";
                        // 执行 queue3 的处理逻辑
                        break;
                    default:
                        // 如果队列名不在预期范围内，可以选择忽略或者记录日志
                        echo "未知队列：$queueName\n";
                }
            }
        }
    }

    private function sendGift($data)
    {
        $api = createTimAPI();

        $config = load_cache('config');
        echo "\n";
        //var_dump($config);

        echo "\n";
        var_dump($data);

        $ret = $api->group_send_group_msg2($config['tencent_identifier'], $data['group_id'], $data['msg_content']);

        echo "\n";
        var_dump($ret);
    }
}