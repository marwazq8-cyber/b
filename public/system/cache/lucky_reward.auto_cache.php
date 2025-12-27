<?php

use think\Db;

class lucky_reward_auto_cache
{
    private $lucky_reward_gift = "lucky_reward_gift";
    private $lucky_reward_lock = "lucky_reward_lock:";
    /*
     * 幸运奖励   user_id 送礼人id  $prop_id  礼物id  $podcast_id收礼人id  $num连送礼物数量
     * prop_lucky 奖金池
     * prop_lucky_log   中奖记录
     **/
    public function load($param)
    {
        $user_id = intval($param['user_id']);
        $gift_id = intval($param['gift_id']);
        $to_uses_sum = intval($param['to_uses_sum']);
        $user_name = trim($param['user_name']);
        $voice_id = intval($param['voice_id']);
        $num = intval($param['num']);
        //礼物信息
        $gift = load_cache("gift_id", array('id' => $gift_id));
        $data['uid'] = $user_id;  // 用户id
        $data['is_lucky_gift'] = 0;  //不是幸运奖励礼物
        $data['is_winning'] = 0;  //未中奖
        $data['user_money'] = 0;  //中奖的金额
        $data['user_multiple'] = 0;  //中奖的倍数
        $data['guild_lucky_coin'] = 0;  //公会收入
        // 获取是否是幸运礼物
        $is_lucky_reward_pools = redis_hGet($this->lucky_reward_gift,$gift_id);
        if (!$is_lucky_reward_pools || $is_lucky_reward_pools == null){
            // 不是幸运礼物
            $is_luck_one = $this->get_luck_one($gift_id);
            if(!$is_luck_one){
                return $data;
            }
        }

        $data['is_lucky_gift'] = 1;  //幸运礼物
        redis_locksleep_nx($this->lucky_reward_lock.$gift_id, true); // 加锁
        // 重新获取
        $lucky_reward_pools_val = redis_hGet($this->lucky_reward_gift,$gift_id);
        $lucky_reward_pools = json_decode($lucky_reward_pools_val,true);

        $data['lucky_platform'] = $lucky_reward_pools['lucky_platform']; // 平台收入(例如20%填写20)
        $data['lucky_host'] = $lucky_reward_pools['lucky_host']; //主播收入(例如20%填写20)
        $data['lucky_guild'] = $lucky_reward_pools['lucky_guild']; //公会收入(例如20%填写20)
        $data['lucky_rate'] = $lucky_reward_pools['lucky_rate']; //用户中奖概率(0-100)
        $data['lucky_multiple'] = $lucky_reward_pools['lucky_multiple'];//出奖倍数(多个使用逗号隔开)
        $data['lucky_multiple_rate'] = $lucky_reward_pools['lucky_multiple_rate'];//出奖倍数概率(对应lucky_multiple字段，多个使用逗号隔开)
        $data['name'] = $gift['name'];
        $jackpot_time = strtotime(date('Y-m-d H:i',NOW_TIME)); // 头奖计算时间标识
        if (intval($lucky_reward_pools['lucky_jackpot_limit']) > 0 && intval($lucky_reward_pools['lucky_jackpot_winners']) > 0 && intval($lucky_reward_pools['lucky_jackpot_bonus_multiple']) > 0) {
            // 加入头奖抽奖列表 --存入集合
            redis_sAdd($lucky_reward_pools['gift_id']."_".$jackpot_time,$user_id);
        }

        $prize_arr['1'] = intval($lucky_reward_pools['lucky_rate']);       // 中奖
        $prize_arr['2'] = intval(100 - $lucky_reward_pools['lucky_rate']);  //未中奖

        $rid = $this->get_rand($prize_arr);
        if ($rid == 1) {   //中奖
            $winning = $this->is_winning($gift,$lucky_reward_pools,$num*$to_uses_sum);
            $data['is_winning'] = $winning['is_winning'];  //中奖
            $data['user_money'] = $winning['user_money'];  //中奖的金额
            $data['user_multiple'] = $winning['user_multiple'];  //中奖的倍数
        }
       // 累计奖池 -- 不管中奖或未中奖的都需要累计奖池
        $diamonds = $gift['coin'];
        $pool_diamonds = 0; // 进入奖池金额
        $platform_diamonds = 0; // 平台扣除
        $host_diamonds = 0; // 主播扣除
        $guild_diamonds = 0; // 公会扣除
        // 获取进入奖池数量
        if ($diamonds) {
            // 去掉主播收益和平台收益，剩下的进入奖池
            $platform_diamonds = $data['lucky_platform'] > 0 ? round($data['lucky_platform'] * $diamonds) / 100 : 0;
            $host_diamonds = $data['lucky_host'] > 0 ? round($data['lucky_host'] * $diamonds) / 100 : 0;
            $guild_diamonds = $data['lucky_guild'] > 0 ? round($data['lucky_guild'] * $diamonds) / 100 : 0;
            $pool_diamonds = $diamonds - $platform_diamonds - $host_diamonds - $guild_diamonds;
        }
        $jackpot = $pool_diamonds * $num*$to_uses_sum;
        $diamonds_val = $diamonds * $num;
        $platform_coin = $platform_diamonds > 0 ? $platform_diamonds * $num : 0;
        $host_coin = $host_diamonds > 0 ? $host_diamonds * $num : 0;
        $guild_coin = $guild_diamonds > 0 ? $guild_diamonds * $num : 0;
        $jackpot_reward = $jackpot - $data['user_money']; // 结束更新奖池金额 -- 也可能是负数

        $notes="更改奖池前:".$lucky_reward_pools['coin']."; 变更奖池数量(进入奖池数-中奖金额): ".$jackpot_reward;

        // 奖池剩余
        $lucky_reward_pools['coin'] = $lucky_reward_pools['coin'] + $jackpot_reward;
        $lucky_reward_pools['winning_coin'] = $lucky_reward_pools['winning_coin'] + $data['user_money'];
        $lucky_reward_pools['platform_coin'] = $lucky_reward_pools['platform_coin'] + $platform_coin * $to_uses_sum;
        $lucky_reward_pools['host_coin'] = $lucky_reward_pools['host_coin'] + $host_coin * $to_uses_sum;
        $lucky_reward_pools['guild_coin'] = $lucky_reward_pools['guild_coin'] + $guild_coin * $to_uses_sum;
        $lucky_reward_pools['consumption_coin'] = $lucky_reward_pools['consumption_coin'] + $diamonds_val*$to_uses_sum;
        $lucky_reward_pools['prize_pool_coin'] = $lucky_reward_pools['prize_pool_coin'] + $jackpot;

        redis_hSet($this->lucky_reward_gift,$gift_id,json_encode($lucky_reward_pools));
        redis_unlock_nx($this->lucky_reward_lock.$gift_id);// 解锁

        if (intval($data['user_money']) > 0){
            // 更新用户收益
            db('user')->where('id='.$user_id)->setInc('coin', intval($data['user_money']));
            // 钻石变更记录
            save_coin_log($user_id, intval($data['user_money']),1, 14);
            // 操作记录
            upd_user_coin_log($user_id,intval($data['user_money']),0,16,1,1, get_client_ip(), $user_id);
        }
        unset($lucky_reward_pools['lucky_multiple_array']);
        // 更新奖池
        db('gift_lucky')->where('id='.$lucky_reward_pools['id'])->update($lucky_reward_pools);
        $notes .=";更改后奖池余额:".$lucky_reward_pools['coin'];

        //未中奖记录和中奖记录
        $insert = array(
            'uid'=> $data['uid'],
       //     'host_id'=> $data['host_id'],
            'gift_id'=> $gift_id,
            'coin'=> $diamonds_val,
            'num'=> $num,
            'ticket'=> $host_coin,
            'prize_pool_coin'=> $pool_diamonds * $num,
            'platform_coin'=> $platform_coin,
            'winning'=> $data['user_money'],
            'ratio'=> $data['user_multiple'],
            'status' => $data['is_winning'],
            'addtime' => NOW_TIME,
            'jackpot_time' => $jackpot_time,
            'user_name' => $user_name,
        //    'host_name'=> $host_name,
            'gift_name'=> $gift['name'],
            'voice_user_id'=>$voice_id,
            'notes' => $notes,
            'guild_coin'=> $guild_coin,
        //    'guild_id'=> $guild_id
        );

        $data['guild_lucky_coin'] = $guild_coin;  //公会收入
        $data['gift_lucky_log'] = $insert;
   //     db('gift_lucky_log')->insert($insert);
        return $data;
    }
    // 获取数据表中的幸运礼物
    public function get_luck_one($id){
        $gift_lucky = Db::name("gift_lucky")->where("gift_id",$id)->find();
        $is_gift_lucky = false;
        if($gift_lucky){
            $gift_lucky['lucky_multiple_array'] = $this->get_lucky_multiple($gift_lucky);
            redis_hSet($this->lucky_reward_gift, $gift_lucky['gift_id'], json_encode($gift_lucky));
            $is_gift_lucky = true;
        }
        return $is_gift_lucky;
    }
    /**
     * 获取倍数概率
     */
    public function get_lucky_multiple($Jackpot)
    {
        // 获取中奖倍数金额
        $lucky_multiple = explode(",", $Jackpot['lucky_multiple']);
        // 获取倍数概率
        $lucky_multiple_rate = explode(",", $Jackpot['lucky_multiple_rate']);
        $lucky_multiple_array = [];
        foreach ($lucky_multiple as $k => $v) {
            if (isset($lucky_multiple_rate[$k]) && $lucky_multiple_rate[$k] > 0) {
                $lucky_multiple_array[$v] = $lucky_multiple_rate[$k];
            }
        }
        return $lucky_multiple_array;
    }
    /**
     * 中奖操作
     */
    public function is_winning($gift, $lucky_reward_pools,$num){

        $data['is_winning'] = 0;  //中奖
        $data['user_money'] = 0;  //中奖的金额
        $data['user_multiple'] = 0;  //中奖的倍数
        // 倍数概率--数组
        $lucky_multiple = $lucky_reward_pools['lucky_multiple_array'];
        foreach ($lucky_multiple as $k=>$v){
            if ($k * $gift['coin'] * $num >= $lucky_reward_pools['coin']) {
                // 过滤掉大于奖池金额的
                unset($lucky_multiple[$k]);
            }
        }
        if(count($lucky_multiple) > 0){
            // 倍率
            $multiplier = $this->get_rand($lucky_multiple);
            $data['is_winning'] =1;  //中奖
            $data['user_money'] = $multiplier * $gift['coin'] * $num;  //中奖的金额
            $data['user_multiple'] = $multiplier * $num;  //中奖的倍数
        }
        return $data;
    }
    /**
     * 中奖几率
     */
    private function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        if(intval($proSum) <= 0){
            return $result;
        }
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);

            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

}

?>