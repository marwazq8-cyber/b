<?php

namespace app\common\model;
use think\Model;

class OperationLogModel extends Model
{
    protected $table;

    protected function initialize()
    {
        parent::initialize();

        // 判断表是否存在，不存在创建表
        if (!$this->tableExists($this->getTable())) {
            $sql = "CREATE TABLE `{$this->getTable()}` (
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `description` varchar(255) NOT NULL DEFAULT '',
                    `request_method` varchar(10) NOT NULL DEFAULT '',
                    `request_url` varchar(255) NOT NULL DEFAULT '',
                    `request_head` text,
                    `request_data` text,
                    `response_status` int(11) NOT NULL DEFAULT '0',
                    `response_data` text,
                    `ip` varchar(50) NOT NULL DEFAULT '',
                    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $this->execute($sql);
        }
    }

    // 定义日志记录的字段
    protected $field = [
        'description', // 操作描述
        'request_method', // 请求方法
        'request_url', // 请求URL
        'request_data', // 请求数据
        'request_head', // 请求头
        'response_status', // 响应状态
        'response_data', // 响应数据
        'ip', // IP地址
        'create_time', // 创建时间
    ];

    private function tableExists($table)
    {
        $sql = "SHOW TABLES LIKE '{$table}'";
        $result = $this->query($sql);
        return !empty($result);
    }
}