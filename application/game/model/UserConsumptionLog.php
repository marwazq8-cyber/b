<?php
namespace app\game\model;

use think\Db;
use think\Model;

class UserConsumptionLog extends Model
{
    /**
     * 增加用户消费记录
     */
    public function insert_all($data)
    {
        return $this->insertAll($data);
    }
    /**
     * 增加用户消费记录
     */
    public function insert_one($data)
    {
        return $this->insert($data);
    }

}