<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2021/02/19
 * Time: 09:23
 * Name: 宝箱游戏接口
 */

namespace app\vue\controller;

use think\Db;
use think\Request;

class TreeGameApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        $this->get_tree_gift();
    }

    /**
     * 开奖 -- 使用redis队列处理
     */
    public function playing_tree(){
        $result = array('code' => 0, 'msg' => lang('operation_failed'),'data'=>array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $sum_id = intval(input('param.sum')) > 0 ? intval(input('param.sum')) : 0;    //打的数量id
        $voice_id = intval(input('param.voice_id'));            //房间id
        $config = load_cache('config');
        $user_info = check_login_token($uid, $token,['coin']);
        // 发财树信息
        $game_list = db('game_list')->field("id,name,game_coin_picture,game_title,game_bg,rule")->where('status = 1 and type=7')->find();
        if (!$game_list || IS_TREE != 1) {
            $result['msg'] = lang('Game_closed');
            return_json_encode($result);
        }
        // 获取开奖次数
        $game_tree_coin = Db::name("game_tree_coin")->where("id=" . $sum_id)->find();
        if(!$game_tree_coin || $game_tree_coin['sum'] <= 0){
            $result['msg'] = lang("Parameter_transfer_error");
            return_json_encode($result);
        }
        $sum = intval($game_tree_coin['sum']);
        $coin = intval($game_tree_coin['coin']);
        $voice = db('voice')->where('id=' . $voice_id)->find();    //获取房间

        if ($user_info['coin'] < $coin) {
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        $time = NOW_TIME;
        $microtime_time =microtime(true);
        $only = $user_info['id'].'_'.$microtime_time;

        // 定时器缓存
        $Crontab_tree = "Crontab_tree_game";
        $tree_game_coin_key = "tree_game_coin";
        $coin_sum_money = floor($coin / $sum ); // 平均单次的价格
        $pool_json = [];
        db()->startTrans();
        try {
            $uid = $user_info['id'];
            //扣费
            db('user')->where(['id' => $uid])->where('coin >= '.$coin)->Dec('coin', $coin)->update();
            $user_info['coin'] = $user_info['coin'] - $coin;
            //增加总消费记录
            $content = $game_list['name']."--".$game_tree_coin['sum_name'];
            $log_id = add_charging_log($uid, 0, 25, $coin, $uid, 0,$content);
            // 钻石记录
            save_coin_log($uid,'-'.$coin,1,16,$content);
            // 剩余总金额
            $tree_game_coin = intval(redis_Get($tree_game_coin_key));
            $pool_json['tree_game_coin'] = $tree_game_coin;// 本次开奖前剩余总金额
            $winning = array();
            $prize_coin = 0;
            $treeGame_key = "treeBox_gift_one";
            for ($i = 1; $i <= $sum; $i++) {
                $gift_val1 = redis_lPop($treeGame_key);
                if (!$gift_val1) {
                    // 队列中没有值则重新获取队列信息
                    $gift_val = $this->get_redis_tree_gift($treeGame_key);
                    $tree_game_coin = intval(redis_Get($tree_game_coin_key));
                }else{
                    $gift_val = json_decode($gift_val1,true);
                }
                if($gift_val){
                    //中奖项
                    $gift_type = 0;
                    foreach ($winning as &$vo) {
                        if ($gift_val['id'] == $vo['id'] && $gift_val['cycles'] == $vo['cycles']) {
                            $gift_type = 1;
                            $vo['coin_sum'] = $vo['coin_sum'] + 1;
                            $vo['count'] = $vo['count'] + $gift_val['count'];
                        }
                    }
                    if ($gift_type != 1) {
                        $gift_val['game_name'] = $game_list['name'];
                        $gift_val['uid'] = $user_info['id'];
                        $gift_val['user_nickname'] = $user_info['user_nickname'];
                        $gift_val['sum_id'] = $sum_id;
                        $gift_val['coin_explain'] = $game_tree_coin['sum_name'].$game_tree_coin['coin_name'];
                        $gift_val['coin_sum'] = 1;
                        $gift_val['coin_sum_money'] = $coin_sum_money;
                        $gift_val['coin_money'] = $coin;
                        $gift_val['surplus_coin'] = $user_info['coin'];
                        $gift_val['only'] = $only;
                        $gift_val['voice_id'] = $voice ? $voice['id'] : 0;
                        $gift_val['voice_user_id'] = $voice ? $voice['user_id'] : 0;
                        $gift_val['voice_profit'] = 0;
                        $gift_val['time'] = $time;
                        $gift_val['user_info'] =$user_info;
                        $gift_val['log_id']= $log_id;

                        $winning[] = $gift_val;
                    }
                    $prize_coin += $gift_val['count'] * $gift_val['coin'];
                    $tree_game_coin = $tree_game_coin - $gift_val['count'] * $gift_val['coin'];
                }else{
                    $result['code'] = 0;
                    $result['msg'] = lang('No_gift_data_in_prize_pool');
                    // 关闭缓存
                    db()->rollback();  // 回滚事务
                    return_json_encode($result);
                }

            }
            $pool_json['tree_game_new_coin'] = $tree_game_coin;// 本次开奖后剩余总金额
            $pool_json['only'] = $only;// 本次唯一标识
            $pool_json['prize_coin'] = $prize_coin; // 中奖总金币

            // 临时数组
            $winning_list = array();
            $winning_list_new = array();
            foreach($winning as &$item) {
                $key = $item['gift_id'];
                if(count($winning_list) && isset($winning_list[$key])) {
                    // 如果已存在该键，合并数据
                    $winning_list[$key]['coin_sum'] =  $winning_list[$key]['coin_sum'] + $item['count'];
                    $winning_list[$key]['count'] = $winning_list[$key]['coin_sum'];
                }
                else {
                    // 如果不存在该键，添加数据
                    $winning_list[$key] = $item;
                }
                $item['pool_json'] = $pool_json;
            }

            foreach ($winning_list as $vi){
                $winning_list_new[]=$vi;
            }
            if (count($winning) > 0) {
                // 加入定时器缓存
                redis_RPush($Crontab_tree,json_encode($winning));
            }
            $result['data']['winning'] = $winning_list_new;
            $result['data']['winning_coin'] = $prize_coin; // 中奖总价格
            $result['data']['winning_coin_text'] = lang("Put_into_Backpack",['n'=>$prize_coin,'m'=>$config['currency_name']]); // 说明： 价值500钻石，已放入背包
            $result['data']['only'] = $only;
            $result['msg'] = "";
            $result['code'] = 1;
            $result['data']['user'] = $user_info;
            db()->commit();   // 提交事务
        } catch (\Exception $e) {
            $result['msg'] =$e->getMessage();
            db()->rollback();  // 回滚事务
        }
        return_json_encode($result);
    }
    // 奖池没有后更新 --- redis 队列更新
    public function get_redis_tree_gift($gameBox_key){
        // 循环次数
        $user_voice_cycles_tree_game = 'user_voice_cycles_tree_game';
        $tree_game_coin_key = 'tree_game_coin';
        $cycles =redis_hGet($user_voice_cycles_tree_game,1);
        $cycles=intval($cycles) + 1;

        // 获取礼物列表
        $gift_spare_list = db('game_tree_gift')->alias('i')
            ->join('gift g', 'g.id = i.gift_id')
            ->field('i.*,g.img,g.coin,g.name')
            ->where("i.count >0 ")
            ->group("i.gift_id")
            ->order("i.sort desc")
            ->select();
        if (count($gift_spare_list) <= 0) {
            $result['code'] = 0;
            $result['msg'] = lang('No_data_prize_pool');
            return_json_encode($result);
        }
        $arr =array();
        $tree_game_coin = 0;
        foreach ($gift_spare_list as $val) {
            // 中将次数大于0 才又奖项
            $val['cycles'] = $cycles;
            if($val['rate'] > 0){
                for ($i=1; $i <=$val['rate'];$i++){
                    $tree_game_coin = $tree_game_coin + $val['coin']*$val['count'];
                    $arr[] = $val;
                }
            }
        }
        // 打乱数组
        shuffle($arr);
        foreach ($arr as $av){
            // 加入redis 队列--等待出奖
            redis_RPush($gameBox_key, json_encode($av));
        }
        $gift_val = redis_lPop($gameBox_key);
        $gift_val = json_decode($gift_val,true);
        redis_set($tree_game_coin_key,$tree_game_coin,60*60*24*30);
        // 循环的次数
        redis_hSet($user_voice_cycles_tree_game,1,$cycles);

        return $gift_val;
    }
    /*
   * 浇树信息接口
   * 次数、价格
   * */
    public function get_tree_list(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token,['coin']);
        $config = load_cache('config');
        // 发财树信息
        $game_list = db('game_list')->field("id,name,game_coin_picture,game_title,game_bg,rule")->where('status = 1 and type=7')->find();
        if (!$game_list || IS_TREE != 1) {
            $result['code'] = 0;
            $result['msg'] = lang('Game_closed');
            return_json_encode($result);
        }
        // 消费次数
        $game_tree_coin = db('game_tree_coin')->field("id,sum,sum_name,coin,coin_name")->order('sort desc')->select();
        // 获取礼物列表
//        $keys_gift_list = "user_game_tree_gift_list";
//        $list = json_decode(redis_hGet($keys_gift_list,1),true);
        // 获取礼物列表
        $list = db('game_tree_gift')->alias('i')
            ->join('gift g', 'g.id = i.gift_id')
            ->field('i.*,g.img,g.coin,g.name')
            ->where("i.count >0 and i.status=1")
            ->group("i.gift_id")
            ->order("i.sort desc")
            ->select();

        if ($game_list) {
            //规则
            $game_list['rule'] = html_entity_decode($game_list['rule']);
        }
        $data= array(
            'gift' => $list,
            'consumption' => $game_tree_coin,
            'game_list'=>$game_list,
            'coin' => $user_info['coin'],
            'currency_name' => $config['currency_name']
        );
        $result['data'] = $data;
        return_json_encode($result);
    }
    // 礼物缓存
    private function get_tree_gift(){
        $keys_gift_list = "user_game_tree_gift_list";
        $keys_gift = "user_game_tree_gift";
        $keys_rate = "user_game_tree_rate";
        $redis_name = 1;
        // 礼物
        $gift_list = json_decode(redis_hGet($keys_gift,$redis_name),true);
        $gift_spare = json_decode(redis_hGet($keys_rate,$redis_name),true);
        $gift_spare_list = json_decode(redis_hGet($keys_gift_list,$redis_name),true);

        if(!$gift_list || !$gift_spare || !$gift_spare_list){
            // 获取礼物列表
            $gift_spare_list = db('game_tree_gift')->alias('i')
                ->join('gift g', 'g.id = i.gift_id')
                ->field('i.*,g.img,g.coin,g.name')
                ->where("i.count >0 and i.status=1")
                ->group("i.gift_id")
                ->order("i.sort desc")
                ->select();
            if (count($gift_spare_list) <= 0) {
                $result['msg'] = lang('No_data_prize_pool');
                db()->rollback();  // 回滚事务
            }

            $arr =array();
            $gift_spare=array();
            foreach ($gift_spare_list as $key => $val) {
                // 中将次数大于0 才又奖项
                if($val['rate'] > 0){
                    for ($i=1; $i <=$val['rate'];$i++){
                        $arr[] = $val['id'];
                    }
                }
                $gift_spare[$val['id']]=$val;
            }
            redis_hSet($keys_gift_list,$redis_name,json_encode($gift_spare_list));
            redis_hSet($keys_gift,$redis_name,json_encode($gift_spare));
            redis_hSet($keys_rate,$redis_name,json_encode($arr));
        }
    }
    /**
     * 开奖
     */
    public function playing_tree0(){
        $result = array('code' => 0, 'msg' => lang('operation_failed'),'data'=>array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $sum_id = intval(input('param.sum')) > 0 ? intval(input('param.sum')) : 0;    //打的数量id
        $voice_id = intval(input('param.voice_id'));            //房间id
        $config = load_cache('config');
        $user_info = check_login_token($uid, $token,['coin']);
        // 发财树信息
        $game_list = db('game_list')->field("id,name,game_coin_picture,game_title,game_bg,rule")->where('status = 1 and type=7')->find();
        if (!$game_list || IS_TREE != 1) {
            $result['msg'] = lang('Game_closed');
            return_json_encode($result);
        }
        // 获取开奖次数
        $game_tree_coin = Db::name("game_tree_coin")->where("id=" . $sum_id)->find();
        if(!$game_tree_coin || $game_tree_coin['sum'] <= 0){
            $result['msg'] = lang("Parameter_transfer_error");
            return_json_encode($result);
        }
        $sum = intval($game_tree_coin['sum']);
        $coin = intval($game_tree_coin['coin']);
        $voice = db('voice')->where('id=' . $voice_id)->find();    //获取房间

//        if(!$voice){
//            $result['msg'] = lang('Room_does_not_exist');
//            return_json_encode($result);
//        }
        if ($user_info['coin'] < $coin) {
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        $time = NOW_TIME;
        $microtime_time =microtime(true);
        $only = $user_info['id'].'_'.$microtime_time;
        $redis_lock_nx_name='Crontab_user_game_tree_rate'.$user_info['id'];
        $time_lock = time().rand(100000,999999);
        redis_lock_nx($redis_lock_nx_name, $time_lock,3);
        // 获取礼物列表
        $keys_gift = "user_game_tree_gift";
        $all_list  = json_decode(redis_hGet($keys_gift,1),true);
        // 概率
        $keys_rate ="user_game_tree_rate";
        $rate_arr = json_decode(redis_hGet($keys_rate,1),true);

        // 定时器缓存
        $Crontab_tree = "Crontab_tree_game";
        $coin_sum_money = floor($coin / $sum ); // 平均单次的价格
        $log_id = 0;

        db()->startTrans();
        try {
            $uid = $user_info['id'];
            //扣费
            $add_user = db('user')->where(['id' => $uid])->where('coin >= '.$coin)->Dec('coin', $coin)->update();
            if ($add_user) {
                $user_info['coin'] = $user_info['coin'] - $coin;
                //增加总消费记录
                $content = $game_list['name']."--".$game_tree_coin['sum_name'];
                $log_id = add_charging_log($uid, 0, 25, $coin, $uid, 0,$content);
            } else {
                db()->rollback();  // 回滚事务
                $result['code'] = 10002;
                $result['msg'] = lang('Insufficient_Balance');
                // 关闭缓存
                redis_unlock_nx($redis_lock_nx_name);
                return_json_encode($result);
            }

            $time_lock = time().rand(10000,99999);
            $redis_lock_nx_name='redis_lock_nx_bx';
            redis_locksleep_nx($redis_lock_nx_name,$time_lock);

            $winning = array();
            $prize_coin = 0;
            for ($i = 1; $i <= $sum; $i++) {
                // 打乱数组
                shuffle($rate_arr);
                $rid =count($rate_arr) > 0 ? $this->get_rand($rate_arr) : 0;
                $prid = $rate_arr[$rid];
                if($all_list[$prid]){
                    //中奖项
                    $gift_val = $all_list[$prid];
                    $gift_type = 0;
                    foreach ($winning as &$vo) {
                        if ($gift_val['id'] == $vo['id']) {
                            $gift_type = 1;
                            $vo['coin_sum'] = $vo['coin_sum'] + 1;
                            $vo['count'] = $vo['count'] + $gift_val['count'];
                        }
                    }
                    if ($gift_type != 1) {
                        $gift_val['game_name'] = $game_list['name'];
                        $gift_val['uid'] = $user_info['id'];
                        $gift_val['user_nickname'] = $user_info['user_nickname'];
                        $gift_val['sum_id'] = $sum_id;
                        $gift_val['coin_explain'] = $game_tree_coin['sum_name'].$game_tree_coin['coin_name'];
                        $gift_val['coin_sum'] = 1;
                        $gift_val['coin_sum_money'] = $coin_sum_money;
                        $gift_val['coin_money'] = $coin;
                        $gift_val['surplus_coin'] = $user_info['coin'];
                        $gift_val['only'] = $only;
                        $gift_val['voice_id'] = $voice ? $voice['id'] : 0;
                        $gift_val['voice_user_id'] = $voice ? $voice['user_id'] : 0;
                        $gift_val['voice_profit'] = 0;
                        $gift_val['time'] = $time;
                        $gift_val['user_info'] =$user_info;
                        $gift_val['log_id']= $log_id;
                        $winning[] = $gift_val;
                    }
                    $prize_coin += $gift_val['count'] * $gift_val['coin'];
                }else{
                    db()->rollback();  // 回滚事务
                    $result['msg'] = lang('No_data_prize_pool');
                    // 关闭缓存
                    redis_unlock_nx($redis_lock_nx_name);
                    return_json_encode($result);
                }
            }
            if (count($winning) > 0) {
                // 加入定时器缓存
                redis_RPush($Crontab_tree,json_encode($winning));
            }
            $result['data']['winning'] = $winning;
            $result['data']['winning_coin'] = $prize_coin; // 中奖总价格
            $result['data']['winning_coin_text'] = lang("Put_into_Backpack",['n'=>$prize_coin,'m'=>$config['currency_name']]); // 说明： 价值500钻石，已放入背包
            $result['data']['only'] = $only;
            $result['msg'] = "";
            $result['code'] = 1;
            $result['data']['user'] = $user_info;
            db()->commit();   // 提交事务
        } catch (\Exception $e) {
            $result['msg'] =$e->getMessage();
            db()->rollback();  // 回滚事务
        }
        // 关闭缓存
        redis_unlock_nx($redis_lock_nx_name);
        return_json_encode($result);
    }
    //中奖几率封装
    private function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = count($proArr);
        $randNum = mt_rand(0, $proSum - 1);

        $i=0;
        //概率数组循环
        foreach ($proArr as $key => $proCur) {

            if(count($proArr) > 1){

                if ($proCur > 0 && $i == $randNum) {

                    $result = $key;
                    break;
                }
            }else {
                $result = $key;
                break;
            }
            $i ++;
        }
        unset ($proArr);

        return $result;
    }
    /**
     * 中奖纪录
     * */
    public function get_log(){
        $result = array('code' => 0, 'msg' => lang('operation_failed'),'data'=>array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token);

        $size = 20;
        $list =db('game_tree_log')
            ->field('*')
            ->order('create_time desc')
            ->where('uid = '.$uid)
            ->page($page,$size)
            ->select();
        foreach ($list as &$v){
            $v['create_time'] = date('Y-m-d H:i',$v['create_time']);
        }
        $result['code']=1;
        $result['data']=$list;
        return_json_encode($result);
    }
    /**
     * 获取公告中奖列表
     */
    public function winning_announcement(){
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token);
        $list =db('game_tree_log')->field('id,uid,user_name,gift_id,gift_name,sum')
            ->order('create_time desc')
            ->limit(20)
            ->select();
        foreach ($list as &$v){
            $v['text']= lang("Congratulations_getting_gift_reward",['n'=>" <span style='color: #ffc562'>".$v['user_name']."</span> ",'m'=> " <span style='color: #ffc562'>".$v['gift_name']." x".$v['sum']."</span> "]); // 说明： 恭喜***获得礼物x1
        }
        $result['code']=1;
        $result['data']=$list;
        return_json_encode($result);
    }
    /**
     * 获取背包记录
     */
    public function user_bag(){
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token);

        $list = db('user_bag')->alias('b')
            ->join('gift g', 'g.id = b.giftid')
            ->field('b.*,g.img,g.coin,g.name')
            ->where("b.giftnum >0 and uid=".$uid)
            ->select();
        $result['code']=1;
        $result['data']=$list;
        return_json_encode($result);
    }

}
