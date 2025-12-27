<?php

namespace app\game\model;

use think\Model;

class LxGameFundFlowLog extends Model
{

    protected function initialize()
    {
        parent::initialize();
        // 判断表是否存在，不存在的话创建
        if (!$this->tableExists()) {
            $this->createTable();
        }
    }

    public function tableExists()
    {
        $sql = "SHOW TABLES LIKE '{$this->getTable()}'";
        $result = $this->query($sql);
        return !empty($result);
    }

    public function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->getTable()}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `userId` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
            `transactionId` varchar(255) NOT NULL DEFAULT '' COMMENT '流水号',
            `coins` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '游戏币',
            `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '类型 1：加币 2：减币',
            `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
            `createTime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='LX游戏流水表';";
        $this->execute($sql);
        return true;
    }

}