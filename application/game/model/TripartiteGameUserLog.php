<?php
namespace app\game\model;

use think\Db;
use think\Model;

class TripartiteGameUserLog extends Model
{
    /**
     * 增加用户下注记录
     */
    public function insert_all($data)
    {
        return $this->insertAll($data);
    }
    /**
     * 增加三方游戏信息
     */
    public function insert_one($data)
    {
        return $this->insertGetId($data);
    }
}