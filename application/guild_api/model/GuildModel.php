<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/12/16
 * Time: 1:37 下午
 * Name:
 */
namespace app\guild_api\model;

use think\Model;
use think\Db;
use think\helper\Time;

class GuildModel extends Model
{
    public function guildInfo($where){
        return db('guild')->where($where)->find();
    }

    public function updateCashAccount($id,$cash_account,$account_name){

        $user_cash_account = db('user_cash_account')->where('uid = '.$id)->find();
        if ($user_cash_account) {
            return db('user_cash_account')->where('uid = '.$id)->update(['pay'=>$cash_account,'name'=>$account_name]);
        }else{
            $insert = array(
                'uid' => $id,
                'pay' => $cash_account,
                'name' =>$account_name,
                'addtime' => NOW_TIME
            );
            return db('user_cash_account')->insert($insert);
        }

    }

    public function adminList($id,$where){
        return db('guild_admin')->where($where)->where('guild_id = '.$id)->select();
    }
}
