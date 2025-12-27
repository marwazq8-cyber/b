<?php
namespace app\game\model;

use think\Db;
use think\Model;

class TripartiteGameLog extends Model
{
    /**
     * 增加三方游戏信息
     */
    public function insert_one($data)
    {
        return $this->insertGetId($data);
    }
    /**
    * 增加三方游戏信息
    */
    public function insertAll($data)
    {
        return db("third_party_game_log")->insertAll($data);
    }

}