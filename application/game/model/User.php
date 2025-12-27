<?php
namespace app\game\model;

use think\Db;
use think\Model;

class User extends Model
{
    /**
     * 增加用户消费记录
     */
    public function save_all($data)
    {
        return $this->saveAll($data);
    }

    /**
     * 累加用户 金额
     * @param $where
     * @param $sum
     * @return bool
     */
    public function updateUserInc($where,$sum){
        return db('user')->where($where)->inc("coin",$sum)->update();
    }
    /**
     * 累减用户 金额
     * @param $where
     * @param $sum
     * @return bool
     */
    public function updateUserDecInc($where,$sum){
        return db('user')->where($where)->Dec("coin",$sum)->update();
    }

    /**
     * 日志记录
     * */
    public function add_user_log($uid, $coin, $type, $genre,$center=''){
        $data = [
            'uid' => $uid,
            'coin' => $coin,
            'type' => $type,
            'genre' => $genre,
            'buy_type' => 21,
            'money' => 0,
            'addtime' => NOW_TIME,
            'center' =>$center
        ];
        //增加消费记录
        $insert_id = db('user_log')->insertGetId($data);
    }
    /**
     * 日志记录
     * */
    public function add_user_log_all($insert){
        //增加消费记录
        $insert_id = db('user_log')->insertAll($insert);
    }
    /**
     *  用户消费记录
     * @param $where
     * @param $sum
     * @return bool
     */
    public function add_user_consume_log_all($insert){
        $insert_id = db('user_consume_log')->insertAll($insert);
    }
}