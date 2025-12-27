<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2021/02/19
 * Time: 09:23
 * Name: 宝箱游戏接口
 */

namespace app\voice\controller;

use app\api\controller\Base;
use app\common\Enum;
use think\Request;
use think\Db;

class GameBoxApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();


        // 测试55
        //$this->return_decrypt_info();
        $this->voice_game_box_gift_spare = 'voice_game_box_gift_spare';//礼物信息
        $this->voice_game_box_coin_sum = 'voice_game_box_coin_sum'; // 总金额 和 触发奖的金额
        $this->voice_game_box_grand_prix = 'voice_game_box_grand_prix';// 触发奖 -- 大奖信息
        $this->voice_game_box_coin = 'voice_game_box_coin'; //剩余总金额
        $this->Crontab_game_box_winning = 'Crontab_game_box_winning';//加入定时器缓存 --- 服务端定时器
        $this->user_voice_cycles_game_box = 'user_voice_cycles_game_box'; //循环次数
        $this->winning_gift_key_id = 'winning_gift_key_id'; // 清除缓存
    }

    /**
     * 奖池逻辑 --- 使用redis 队列形式
     */
    public function pool_play_list($user_info, $voice, $box_id, $total_coin, $sum, $money, $sum_id, $game_box_type_name)
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'));
        $uid = $user_info['id'];
        $redis_name = 'gameBox' . "_" . $box_id;
        $pool_json = [];
        db()->startTrans();
        try {
            //扣费
            db('user')->where('coin >= ' . $total_coin . ' and id =' . $uid)->Dec('coin', intval($total_coin))->update();

            $user_info['coin'] = $user_info['coin'] - intval($total_coin);
            //增加总消费记录
            $log_id = add_charging_log($uid, 0, 22, $total_coin, $uid, 0);

            // 钻石记录
            save_coin_log($uid, '-' . $total_coin, 1, 17, "gameBox");
            $users = $user_info;
            $time = NOW_TIME;
            $microtime_time = microtime(true);

            $only = $uid . '_' . $microtime_time . "_" . $sum . "_" . rand(10000, 99999);
            // 总金额 和 触发奖的金额
            $grand_prize_amount = json_decode(redis_hGet($this->voice_game_box_coin_sum, $redis_name), true);
            if (empty($grand_prize_amount)) {
                $this->redis_bubble_gift_save($box_id);
                $grand_prize_amount = json_decode(redis_hGet($this->voice_game_box_coin_sum, $redis_name), true);
            }


            $grand_prix = json_decode(redis_hGet($this->voice_game_box_grand_prix, $redis_name), true); // 触发奖 -- 大奖信息

            // 剩余总金额
            $voice_game_box_coin = intval(redis_hGet($this->voice_game_box_coin, $redis_name));

            $trigger_bonus_amount = 0; // 触发奖金额
            if (!empty($grand_prize_amount)) {
                $trigger_bonus_amount = intval($grand_prize_amount['grand_prize_amount']);
            }


            $winning = array();
            $prize_coin = 0;

            $pool_json['trigger_bonus_amount'] = $trigger_bonus_amount;// 触发奖金额
            $pool_json['voice_game_box_coin'] = $voice_game_box_coin;// 本次开奖前剩余总金额
            $coin_sum_money = floor($total_coin / $sum); // 平均单次的价格

            for ($i = 1; $i <= $sum; $i++) {
                // 礼物信息
                $gift_spare = json_decode(redis_hGet($this->voice_game_box_gift_spare, $redis_name), true);
                // 剩余总金额
                $voice_game_box_coin = intval(redis_hGet($this->voice_game_box_coin, $redis_name));
                $is_grand_prix = 0;
                $Maximum_reward_coin = 0;
                if (!empty($grand_prix)) {
                    $gift_val = $gift_spare[$grand_prix];
                    $Maximum_reward_coin = $gift_val['coin'];
                }
                $is_Grand_Prix_one = redis_hGet('is_Grand_Prix', $redis_name); //是否设置过大奖
                if ($voice_game_box_coin > $Maximum_reward_coin && $voice_game_box_coin <= $trigger_bonus_amount && !empty($grand_prix) && $is_Grand_Prix_one == 1) {

                    $gift_val_id = $grand_prix;
                    $is_grand_prix = 1;
                    $grand_prix = [];
                    redis_hSet($this->voice_game_box_grand_prix, $redis_name, json_encode($grand_prix));
                } else {
                    // 队列中没有值则重新获取队列信息
                    $gift_val_id = $this->get_redis_bubble_gift($box_id);
                    // 礼物信息
                    $gift_spare = json_decode(redis_hGet($this->voice_game_box_gift_spare, $redis_name), true);
                    // 剩余总金额
                    $voice_game_box_coin = intval(redis_hGet($this->voice_game_box_coin, $redis_name));
                }
                if (!isset($gift_spare[$gift_val_id])) {
                    $result['code'] = 0;
                    $result['msg'] = lang('No_data_prize_pool');
                    return_json_encode($result);
                }
                $gift_val = $gift_spare[$gift_val_id];

                if ($gift_val) {
                    //中奖项
                    $gift_type = 0;
                    foreach ($winning as &$vo) {
                        if ($gift_val['id'] == $vo['id'] && $gift_val['cycles'] == $vo['cycles']) {
                            $gift_type = 1;
                            $vo['sum'] = $vo['sum'] + $gift_val['count'];
                            $vo['coin_sum'] = $vo['coin_sum'] + 1;
                            $vo['count'] = $vo['sum'];
                        }
                    }
                    if ($gift_type != 1) {
                        $gift_val['money'] = $money;
                        $gift_val['box_id'] = $box_id;
                        $gift_val['sum'] = $gift_val['count'];
                        $gift_val['only'] = $only;
                        $gift_val['uid'] = $uid;
                        $gift_val['voice_id'] = $voice['id'];
                        $gift_val['time'] = $time;
                        $gift_val['sum_id'] = $sum_id;
                        $gift_val['voice_user_id'] = $voice['user_id'];
                        $gift_val['user_nickname'] = $user_info['user_nickname'];
                        $gift_val['user_info'] = $user_info;
                        $gift_val['log_id'] = $log_id;
                        $gift_val['total_coin'] = $total_coin;
                        $gift_val['coin_sum'] = 1;
                        $gift_val['coin_sum_money'] = $coin_sum_money;
                        $gift_val['game_box_type_name'] = $game_box_type_name;
                        $gift_val['is_grand_prix'] = $is_grand_prix;
                        $winning[] = $gift_val;
                    }
                    $prize_coin += $gift_val['count'] * $gift_val['coin'];
                    $voice_game_box_coin = $voice_game_box_coin - $gift_val['count'] * $gift_val['coin'];
                    redis_hSet($this->voice_game_box_coin, $redis_name, $voice_game_box_coin); //剩余总金额
                } else {
                    $result['code'] = 0;
                    $result['msg'] = lang('No_gift_data_in_prize_pool');
                    // 关闭缓存
                    db()->rollback();  // 回滚事务
                    return_json_encode($result);
                }
            }
            db()->commit();   // 提交事务
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
            db()->rollback();  // 回滚事务
            return $result;
        }

        $pool_json['voice_game_box_new_coin'] = $voice_game_box_coin;// 本次开奖后剩余总金额
        $pool_json['only'] = $only;// 本次唯一标识
        $pool_json['prize_coin'] = $prize_coin; // 中奖总金币

        // 临时数组
        $winning_list = array();
        $winning_list_timer = array();
        foreach ($winning as $item) {
            $key = $item['gift_id'];
            if (count($winning_list) && isset($winning_list[$key])) {
                // 如果已存在该键，合并数据
                $winning_list[$key]['sum'] = $winning_list[$key]['sum'] + $item['count'];
                $winning_list[$key]['count'] = $winning_list[$key]['sum'];
            } else {
                // 如果不存在该键，添加数据
                $winning_list[$key] = $item;
            }
            $winning_list_timer[] = $item;
        }
        $is_Grand_Prix_one = redis_hGet('is_Grand_Prix', $redis_name); //是否设置过大奖
        $winning_list_new = [];
        $grand_prix = json_decode(redis_hGet($this->voice_game_box_grand_prix, $redis_name), true); // 触发奖 -- 大奖信息
        $pool_json['is_award_status'] = empty($grand_prix) && $is_Grand_Prix_one == 1 ? 1 : 0; // 是否出大奖0未出大奖 1已出大奖
        foreach ($winning_list as $vi) {

            // 减少后台设置的单个数量 sum
            $gift_number = intval(redis_hGet($this->winning_gift_key_id, $vi['id']));
            if ($gift_number) {
                $gift_number_sum = $gift_number + $vi['sum'];
            } else {
                $gift_number_sum = $vi['sum'];
            }
            if ($gift_number_sum > $vi['rate']) {
                $gift_number_sum = 0;
            }
            $key = $this->winning_gift_key_id . '_' . $vi['id'];
            add_incr($key, $vi['sum']);
            redis_hSet($this->winning_gift_key_id, $vi['id'], $gift_number_sum); //剩余总数量

            $vi['pool_json'] = $pool_json;
            $vi['residue_number_sum'] = $gift_number_sum;
            $winning_list_new[] = $vi;
        }
        foreach ($winning_list_timer as &$v) {
            $v['pool_json'] = $pool_json;
        }
        // 加入定时器缓存 --- 服务端定时器
        redis_RPush($this->Crontab_game_box_winning, json_encode($winning_list_timer));

        bogokjLogPrint("box", json_encode($winning_list_new));
        $result['data']['winning'] = $winning_list_new;
        $result['data']['winning_coin'] = $prize_coin;
        $result['data']['only'] = $only;
        $result['msg'] = "";
        $result['code'] = 1;
        $result['data']['user'] = $users;
        return $result;
    }

    // 更新奖池
    public function redis_bubble_gift_save($box_id)
    {
        $redis_name = 'gameBox' . "_" . $box_id;
        $gameBox_key = "gameBox_gift_" . $box_id;
        // 备用
        $cycles = redis_hGet($this->user_voice_cycles_game_box, $redis_name);
        $cycles = intval($cycles) + 1;
        $grand_prix = []; // 触发奖 -- 大奖信息

        // 获取礼物列表 i.arrival_times > 0
        $where = "r.type_id=" . $box_id . " and r.count > 0 and r.status=1 and t.status = 1";
        $gift_spare_list = db('game_box_gift_list_rate')
            ->alias('r')
            ->join('game_box_list t', 't.id = r.type_id')
            ->join('gift g', 'g.id = r.gift_id')
            ->field('r.*,g.img,g.coin,g.name,t.name as type_name,t.grand_prize_amount')
            ->where($where)
            ->select();
        if (count($gift_spare_list) <= 0) {
            $result['code'] = 0;
            $result['msg'] = lang('No_data_prize_pool');
            return_json_encode($result);
        }
        // 触发奖的金额
        $grand_prize_amount_val = $gift_spare_list[0]['grand_prize_amount'];
        $coin_sum = db('game_box_gift_list_rate')
            ->alias('r')
            ->join('game_box_list t', 't.id = r.type_id')
            ->join('gift g', 'g.id = r.gift_id')
            ->where($where)
            ->sum("g.coin*r.count*r.rate");
        $grand_prize_amount = array(
            'coin_sum'           => $coin_sum,
            'grand_prize_amount' => $grand_prize_amount_val
        );
        redis_hSet($this->voice_game_box_coin_sum, $redis_name, json_encode($grand_prize_amount)); // 奖池总金额
        $arr = array();
        $is_grand_prix = 0; // 是否有大奖
        $coin_sum = 0;
        if (!empty($grand_prize_amount)) {
            if ($grand_prize_amount['coin_sum'] >= $grand_prize_amount['grand_prize_amount']) {
                $is_grand_prix = 1;
            }
            $coin_sum = $grand_prize_amount['coin_sum'];
        }
        $gift_spare = array();
        foreach ($gift_spare_list as $key => $val) {
            $val['cycles'] = $cycles;  //次数
            // 中将次数大于0 才又奖项
            if ($val['rate'] > 0) {
                $val['surplus'] = $val['rate'];
                for ($i = 1; $i <= $val['rate']; $i++) {
                    if ($is_grand_prix == 1 && $val['is_trigger_jackpot'] == 1 && empty($grand_prix)) {
                        $grand_prix = $val['id']; // 大奖不计算
                    } else {
                        // 加入redis队列
                        $arr[] = $val['id'];
                    }
                }
                $gift_spare[$val['id']] = $val;
            }
            redis_unlock_nx($this->winning_gift_key_id . "_" . $val['id']);// 清除剩余数量
        }
        // 打乱数组
        shuffle($arr);

        foreach ($arr as $av) {
            // 加入redis 队列--等待出奖
            redis_RPush($gameBox_key, $av);
        }
        // 获取中奖的礼物 备用
        redis_hSet($this->voice_game_box_gift_spare, $redis_name, json_encode($gift_spare)); // 礼物信息
        redis_hSet($this->voice_game_box_grand_prix, $redis_name, json_encode($grand_prix)); // 触发奖 -- 大奖信息
        redis_hSet($this->voice_game_box_coin, $redis_name, $coin_sum); // 触发奖总金额 -- 用户开一次减一次金额
        redis_hSet('is_Grand_Prix', $redis_name, $grand_prix ? 1 : 0); //是否设置过大奖

        // 循环的次数
        redis_hSet($this->user_voice_cycles_game_box, $redis_name, $cycles);
        redis_unlock_nx($this->winning_gift_key_id); // 清除缓存
    }

    // 奖池没有后更新 --- redis 队列更新
    public function get_redis_bubble_gift($box_id)
    {
        // 获取中奖的礼物
        $gameBox_key = "gameBox_gift_" . $box_id;
        $redis_name = 'gameBox' . "_" . $box_id;
        $gift_val_id = redis_lPop($gameBox_key);
        if (!$gift_val_id) {
            // 备用
            $cycles = redis_hGet($this->user_voice_cycles_game_box, $redis_name);
            $cycles = intval($cycles) + 1;
            $grand_prix = []; // 触发奖 -- 大奖信息

            // 获取礼物列表 i.arrival_times > 0
            $where = "r.type_id=" . $box_id . " and r.count > 0 and r.status=1 and t.status = 1";
            $gift_spare_list = db('game_box_gift_list_rate')
                ->alias('r')
                ->join('game_box_list t', 't.id = r.type_id')
                ->join('gift g', 'g.id = r.gift_id')
                ->field('r.*,g.img,g.coin,g.name,t.name as type_name,t.grand_prize_amount')
                ->where($where)
                ->select();
            if (count($gift_spare_list) <= 0) {
                $result['code'] = 0;
                $result['msg'] = lang('No_data_prize_pool');
                return_json_encode($result);
            }
            // 触发奖的金额
            $grand_prize_amount_val = $gift_spare_list[0]['grand_prize_amount'];
            $coin_sum = db('game_box_gift_list_rate')
                ->alias('r')
                ->join('game_box_list t', 't.id = r.type_id')
                ->join('gift g', 'g.id = r.gift_id')
                ->where($where)
                ->sum("g.coin*r.count*r.rate");
            $grand_prize_amount = array(
                'coin_sum'           => $coin_sum,
                'grand_prize_amount' => $grand_prize_amount_val
            );
            redis_hSet($this->voice_game_box_coin_sum, $redis_name, json_encode($grand_prize_amount)); // 奖池总金额
            $arr = array();
            $is_grand_prix = 0; // 是否有大奖
            $coin_sum = 0;
            if (!empty($grand_prize_amount)) {
                if ($grand_prize_amount['coin_sum'] >= $grand_prize_amount['grand_prize_amount']) {
                    $is_grand_prix = 1;
                }
                $coin_sum = $grand_prize_amount['coin_sum'];
            }
            $gift_spare = array();
            foreach ($gift_spare_list as $key => $val) {
                $val['cycles'] = $cycles;  //次数
                // 中将次数大于0 才又奖项
                if ($val['rate'] > 0) {
                    $val['surplus'] = $val['rate'];
                    for ($i = 1; $i <= $val['rate']; $i++) {
                        if ($is_grand_prix == 1 && $val['is_trigger_jackpot'] == 1 && empty($grand_prix)) {
                            $grand_prix = $val['id']; // 大奖不计算
                        } else {
                            // 加入redis队列
                            $arr[] = $val['id'];
                        }
                    }
                    $gift_spare[$val['id']] = $val;
                }
            }
            // 打乱数组
            shuffle($arr);
            foreach ($arr as $av) {
                // 加入redis 队列--等待出奖
                redis_RPush($gameBox_key, $av);
            }
            // 获取中奖的礼物 备用
            redis_hSet($this->voice_game_box_gift_spare, $redis_name, json_encode($gift_spare)); // 礼物信息
            redis_hSet($this->voice_game_box_grand_prix, $redis_name, json_encode($grand_prix)); // 触发奖 -- 大奖信息
            redis_hSet($this->voice_game_box_coin, $redis_name, $coin_sum); // 触发奖总金额 -- 用户开一次减一次金额
            // 循环的次数
            redis_hSet($this->user_voice_cycles_game_box, $redis_name, $cycles);
            redis_unlock_nx($this->winning_gift_key_id); // 清除缓存

            $gift_val_id = redis_lPop($gameBox_key);
        }

        return $gift_val_id;
    }

    /*
     * 榜单
     * */
    public function get_rank()
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'), 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //$voice_id = intval(input('param.voice_id'));
        $user_info = check_login_token($uid, $token, ['coin', 'income']);

        $size = 20;
        $list = db('game_box_log')
            ->alias('l')
            ->join('user u', 'u.id = l.uid')
            ->field('l.*,sum(l.sum*l.coin) as total,u.luck')
            ->group('uid')
            ->order('total desc')
            //->where('l.type = 3')
            ->page($page, $size)
            ->select();
        //等级
        foreach ($list as &$val) {
            $level = get_level($val['uid']);
            $val['level'] = $level;
        }
        $result['code'] = 1;
        $result['data'] = $list;
        return_json_encode($result);
    }

    /*
     * 中奖纪录
     * */
    public function get_log()
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'), 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['coin', 'income']);

        $size = 20;
        $list = db('game_box_log')
            ->field('*')
            ->order('addtime desc')
            ->where('uid = ' . $uid)
            ->page($page, $size)
            ->select();
        foreach ($list as &$v) {
            $v['addtime'] = date('m-d H"i', $v['addtime']);
        }
        $result['code'] = 1;
        $result['msg'] = '';
        $result['data'] = $list;
        return_json_encode($result);
    }

    /*
     * 宝箱信息接口
     * 次数、价格
     * */
    public function get_box_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //$type = intval(input('param.type',1));//1普通宝箱 2至尊宝箱
        $user_info = check_login_token($uid, $token, ['coin', 'income', 'game_box_deduction']);
        $config = load_cache('config');
        $data['user_info']['coin'] = $user_info['coin'];
        $data['user_info']['game_box_deduction'] = $user_info['game_box_deduction'];
        //宝箱列表
        $list = db('game_box_list')->where('status = 1')->order('orderno')->select();
        $data['list'] = $list;
        $game_list = db('game_list')->where('status = 1 and type=6')->find();
        $data['rule'] = '';
        if ($game_list) {
            //规则
            $data['rule'] = html_entity_decode($game_list['rule']);
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    /*
     * 开启关闭直接扣费
     * */
    public function game_box_set()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //1扣费 2其他
        $type = intval(input('param.type'));
        $user_info = check_login_token($uid, $token, ['coin', 'income', 'game_box_deduction']);
        if ($type == 1) {
            if ($user_info['game_box_deduction'] == 1) {
                $data['game_box_deduction'] = 0;
            } else {
                $data['game_box_deduction'] = 1;
            }
            db('user')->where('id = ' . $uid)->update($data);
            $result['msg'] = lang('Modified_successfully');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Modification_failed');
            $data['game_box_deduction'] = $user_info['game_box_deduction'];
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    /*
     * 宝箱信息
     * box_id 宝箱ID
     * */
    public function get_box_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $box_id = intval(input('param.box_id'));
        //普通宝箱，至尊宝箱
        $user_info = check_login_token($uid, $token, ['coin', 'income']);

        $data['user_info']['coin'] = $user_info['coin'];
        //宝箱信息
        $box_info = db('game_box_list')->where('status = 1 and id = ' . $box_id)->find();
        $data['box_info'] = $box_info;
        //开箱次数
        $data['list'] = db('game_box_type')->where('status = 1 and type = ' . $box_id)->select();
        foreach ($data['list'] as &$v) {
            $box = db('game_box_list')->where('id = ' . $v['type'] . ' and status = 1')->find();
            $v['money'] = $box['money'] * $v['sum'];
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    /**
     * 开宝箱 -new
     * uid
     * token
     * voice_id 语音直播间ID
     * box_id  宝箱ID
     * sum_id 次数ID
     * */
    public function request_play()
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $sum_id = intval(input('param.sum_id'));
        $box_id = intval(input('param.box_id'));
        $voice_id = intval(input('param.voice_id'));

        $user_info = check_login_token($uid, $token, ['coin', 'income', 'is_named_user']);
        $voice = db('voice')->where('id=' . $voice_id)->find();    //获取房间
        if (!$voice) {
            $result['msg'] = lang('Room_does_not_exist');
            return_json_encode($result);
        }
        $game_list = db('game_list')->where('status = 1 and type=6')->find();
        if (!$game_list && $uid != 56110 && $uid != 55874 && $uid != 59251 && $uid != 59248 && $uid != 59246 && $uid != 59244) {
            $result['msg'] = lang('Treasure_chest_does_not_exist');
            return_json_encode($result);
        }
        $box = db('game_box_list')->where('id = ' . $box_id . ' and status = 1')->find();
        if (!$box) {
            $result['msg'] = lang('Treasure_chest_does_not_exist');
            return_json_encode($result);
        }
        $bubble_type = db('game_box_type')->where('status = 1 and type = ' . $box_id . ' and id = ' . $sum_id)->find();
        if (!$bubble_type) {
            $result['msg'] = lang('No_unpacking_times');
            return_json_encode($result);
        }

        $sum = $bubble_type['sum'];
        $game_box_type_name = $box['name'];
        $money = $box['money'];

        $total_coin = $sum * $money;
        if ($total_coin > $user_info['coin']) {
            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        $result = $this->pool_play_list($user_info, $voice, $box_id, $total_coin, $sum, $money, $sum_id, $game_box_type_name);
        // $result = $this->pool_play($user_info,$voice,$box_id,$total_coin,$sum,$money,$sum_id,$game_box_type_name);


        return_json_encode($result);
    }

    /**
     * 奖池逻辑
     */
    public function pool_play($user_info, $voice, $box_id, $total_coin, $sum, $money, $sum_id, $game_box_type_name)
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'));
        $uid = $user_info['id'];
        db()->startTrans();
        try {
            //扣费
            $add_user = db('user')->where('coin >= ' . $total_coin . ' and id =' . $uid)->Dec('coin', intval($total_coin))->update();
            if (!$add_user) {
                db()->rollback();  // 回滚事务
                $result['code'] = 10002;
                $result['msg'] = lang('Insufficient_Balance');
                return_json_encode($result);
            }

            $user_info['coin'] = $user_info['coin'] - intval($total_coin);
            //增加总消费记录
            $log_id = add_charging_log($uid, 0, 22, $total_coin, $uid, 0);

            $time_lock = time() . rand(10000, 99999);
            $redis_lock_nx_name = 'redis_lock_nx_bx';
            redis_locksleep_nx($redis_lock_nx_name, $time_lock);

            $users = $user_info;
            $time = NOW_TIME;
            $microtime_time = microtime(true);

            $only = $uid . '_' . $microtime_time . "_" . $sum . "_" . rand(10000, 99999);
            $redis_name = 'gameBox' . "_" . $box_id;

            // 获取中奖的礼物
            $all_list = json_decode(redis_hGet("user_game_box_list", $redis_name), true);
            // 备用
            $prize_arr = json_decode(redis_hGet("user_game_box_list_spare", $redis_name), true);
            // 总金额 和 触发奖的金额
            $grand_prize_amount = json_decode(redis_hGet("voice_game_box_coin_sum", $redis_name), true);
            $trigger_bonus_amount = 0; // 触发奖金额
            if (!empty($grand_prize_amount)) {
                $trigger_bonus_amount = intval($grand_prize_amount['grand_prize_amount']);
            }
            // 剩余总金额
            $voice_game_box_coin = intval(redis_hGet("voice_game_box_coin", $redis_name));
            $voice_game_box_coin = intval($voice_game_box_coin) > 0 ? intval($voice_game_box_coin) : 0;
            $grand_prix = json_decode(redis_hGet("voice_game_box_grand_prix", $redis_name), true); // 触发奖 -- 大奖信息

            $winning = array();
            $prize_coin = 0;

            for ($i = 1; $i <= $sum; $i++) {
                $is_grand_prix = 0;
                if (!empty($all_list) && $voice_game_box_coin <= $trigger_bonus_amount && !empty($grand_prix)) {
                    $prid = $grand_prix['id'];
                    $is_grand_prix = 1;
                    $grand_prix = [];
                    redis_hSet("voice_game_box_grand_prix", $redis_name, json_encode($grand_prix));
                } else {
                    //根据概率获取奖项id
                    if (empty($all_list) || !$all_list || count($all_list) <= 0) {
                        $all_list = $this->get_bubble_gift($box_id);
                        // 备用
                        $prize_arr = json_decode(redis_hGet("user_game_box_list_spare", $redis_name), true);
                        // 总金额 和 触发奖的金额
                        $grand_prize_amount = json_decode(redis_hGet("voice_game_box_coin_sum", $redis_name), true);
                        $trigger_bonus_amount = 0; // 触发奖金额
                        if (!empty($grand_prize_amount)) {
                            $trigger_bonus_amount = $grand_prize_amount['grand_prize_amount'];
                        }
                        // 剩余总金额
                        $voice_game_box_coin = intval(redis_hGet("voice_game_box_coin", $redis_name));
                        $voice_game_box_coin = $voice_game_box_coin > 0 ? $voice_game_box_coin : 0;
                        $grand_prix = json_decode(redis_hGet("voice_game_box_grand_prix", $redis_name), true); // 触发奖 -- 大奖信息

                    }
                    if (empty($all_list)) {
                        $result['msg'] = lang('No_gift_data_in_prize_pool');
                        // 关闭缓存
                        redis_unlock_nx($redis_lock_nx_name);
                        db()->rollback();  // 回滚事务
                    }
                    // 打乱数组
                    shuffle($all_list);
                    $rid = count($all_list) > 0 ? $this->get_rand($all_list) : 0;
                    $prid = $all_list[$rid];
                    // 删除中奖数据
                    unset($all_list[$rid]);
                }

                if ($prize_arr[$prid]) {
                    // 清除 中奖次数
                    $prize_arr[$prid]['surplus'] = $prize_arr[$prid]['surplus'] - 1;
                    if ($prize_arr[$prid]['surplus'] >= 0) {
                        //中奖项
                        $gift_val = $prize_arr[$prid];
                        $gift_type = 0;
                        foreach ($winning as &$vo) {
                            if ($gift_val['id'] == $vo['id'] && $gift_val['cycles'] == $vo['cycles']) {
                                $gift_type = 1;
                                $vo['sum'] = $vo['sum'] + $gift_val['count'];
                                $vo['count'] = $vo['sum'];
                            }
                        }
                        if ($gift_type != 1) {
                            $gift_val['money'] = $money;
                            $gift_val['box_id'] = $box_id;
                            $gift_val['sum'] = $gift_val['count'];
                            $gift_val['only'] = $only;
                            $gift_val['uid'] = $uid;
                            $gift_val['voice_id'] = $voice['id'];
                            $gift_val['time'] = $time;
                            $gift_val['sum_id'] = $sum_id;
                            $gift_val['voice_user_id'] = $voice['user_id'];
                            $gift_val['user_nickname'] = $user_info['user_nickname'];
                            $gift_val['user_info'] = $user_info;
                            $gift_val['log_id'] = $log_id;
                            $gift_val['total_coin'] = $total_coin;
                            $gift_val['game_box_type_name'] = $game_box_type_name;
                            $gift_val['is_grand_prix'] = $is_grand_prix;

                            $winning[] = $gift_val;
                        }
                        $prize_coin += $gift_val['count'] * $gift_val['coin'];
                        $voice_game_box_coin = $voice_game_box_coin - $gift_val['count'] * $gift_val['coin'];
                    }

                } else {
                    $all_list = $this->get_bubble_gift($box_id);
                    // 备用
                    $prize_arr = json_decode(redis_hGet("user_game_box_list_spare", $redis_name), true);
                    // 总金额 和 触发奖的金额
                    $grand_prize_amount = json_decode(redis_hGet("voice_game_box_coin_sum", $redis_name), true);
                    $trigger_bonus_amount = 0; // 触发奖金额
                    if (!empty($grand_prize_amount)) {
                        $trigger_bonus_amount = $grand_prize_amount['grand_prize_amount'];
                    }
                    // 剩余总金额
                    $voice_game_box_coin = intval(redis_hGet("voice_game_box_coin", $redis_name));
                    $voice_game_box_coin = $voice_game_box_coin > 0 ? $voice_game_box_coin : 0;
                    $grand_prix = json_decode(redis_hGet("voice_game_box_grand_prix", $redis_name), true); // 触发奖 -- 大奖信息
                }
            }
            // 临时数组
            $winning_list = array();
            foreach ($winning as $item) {
                $key = $item['gift_id'];
                if (count($winning_list) && isset($winning_list[$key])) {
                    // 如果已存在该键，合并数据
                    $winning_list[$key]['sum'] = $winning_list[$key]['sum'] + $item['count'];
                    $winning_list[$key]['count'] = $winning_list[$key]['sum'];
                } else {
                    // 如果不存在该键，添加数据
                    $winning_list[$key] = $item;
                }
            }
            $winning_list_new = [];
            foreach ($winning_list as $vi) {
                $winning_list_new[] = $vi;
            }
            // 加入定时器缓存 --- 服务端定时器
            redis_RPush('Crontab_game_box_winning', json_encode($winning_list_new));
            redis_hSet("user_game_box_list", $redis_name, json_encode($all_list));
            redis_hSet("user_game_box_list_spare", $redis_name, json_encode($prize_arr));

            redis_hSet("voice_game_box_coin", $redis_name, $voice_game_box_coin); //剩余总金额

            // 关闭缓存
            redis_unlock_nx($redis_lock_nx_name);

            $result['data']['winning'] = $winning_list_new;
            $result['data']['winning_coin'] = $prize_coin;
            $result['data']['only'] = $only;
            $result['msg'] = "";
            $result['code'] = 1;
            $result['data']['user'] = $users;
            db()->commit();   // 提交事务
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
            db()->rollback();  // 回滚事务
        }

        return $result;
    }

    // 奖池没有后更新
    public function get_bubble_gift($box_id)
    {
        $redis_name = 'gameBox' . "_" . $box_id;
        // 备用
        $gift_spare = json_decode(redis_hGet("user_game_box_list_spare", $redis_name), true);

        $cycles = redis_hGet("user_voice_cycles_game_box", $redis_name);
        $cycles = intval($cycles) + 1;
        $grand_prix = []; // 触发奖 -- 大奖信息
        $grand_prize_amount = json_decode(redis_hGet("voice_game_box_coin_sum", $redis_name), true); // 总金额
        if (empty($gift_spare) || !$gift_spare) {
            // 获取礼物列表 i.arrival_times > 0
            $where = "r.type_id=" . $box_id . " and r.count > 0 and r.status=1 and t.status = 1";
            $gift_spare_list = db('game_box_gift_list_rate')
                ->alias('r')
                ->join('game_box_list t', 't.id = r.type_id')
                ->join('gift g', 'g.id = r.gift_id')
                ->field('r.*,g.img,g.coin,g.name,t.name as type_name,t.grand_prize_amount')
                ->where($where)
                ->select();
            if (count($gift_spare_list) <= 0) {
                $root['msg'] = lang('No_data_prize_pool');
                db()->rollback();  // 回滚事务
            }

            // 触发奖的金额
            $grand_prize_amount_val = $gift_spare_list[0]['grand_prize_amount'];
            $coin_sum = db('game_box_gift_list_rate')
                ->alias('r')
                ->join('game_box_list t', 't.id = r.type_id')
                ->join('gift g', 'g.id = r.gift_id')
                ->where($where)
                ->sum("g.coin*r.count*r.rate");
            $grand_prize_amount = array(
                'coin_sum'           => $coin_sum,
                'grand_prize_amount' => $grand_prize_amount_val
            );
            redis_hSet("voice_game_box_coin_sum", $redis_name, json_encode($grand_prize_amount)); // 奖池总金额

            $gift_spare = array();
            foreach ($gift_spare_list as $key => $val) {
                $val['cycles'] = $cycles;  //次数
                $gift_spare[$val['id']] = $val;
            }
        }
        $arr = array();
        $is_grand_prix = 0; // 是否有大奖
        $coin_sum = 0;
        if (!empty($grand_prize_amount)) {
            if ($grand_prize_amount['coin_sum'] >= $grand_prize_amount['grand_prize_amount']) {
                $is_grand_prix = 1;
            }
            $coin_sum = $grand_prize_amount['coin_sum'];
        }
        foreach ($gift_spare as $key => $val) {
            // 中将次数大于0 才又奖项
            if ($val['rate'] > 0) {
                $val['surplus'] = $val['rate'];
                for ($i = 1; $i <= $val['rate']; $i++) {
                    if ($is_grand_prix == 1 && $val['is_trigger_jackpot'] == 1 && empty($grand_prix)) {
                        $grand_prix = $val; // 大奖不计算
                    } else {
                        $arr[] = $val['id'];
                    }
                }
            }
            $val['cycles'] = $cycles;
            $gift_spare[$key] = $val;
        }

        // 获取中奖的礼物 备用
        redis_hSet("user_game_box_list_spare", $redis_name, json_encode($gift_spare));
        redis_hSet("user_game_box_list", $redis_name, json_encode($arr));
        redis_hSet("voice_game_box_grand_prix", $redis_name, json_encode($grand_prix)); // 触发奖 -- 大奖信息
        redis_hSet("voice_game_box_coin", $redis_name, $coin_sum); // 触发奖总金额 -- 用户开一次减一次金额
        // 循环的次数
        redis_hSet("user_voice_cycles_game_box", $redis_name, $cycles);
        return $arr;
    }

    //中奖几率封装
    public function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = count($proArr);
        $randNum = mt_rand(0, $proSum - 1);

        $i = 0;
        //概率数组循环
        foreach ($proArr as $key => $proCur) {

            if (count($proArr) > 1) {

                if ($proCur > 0 && $i == $randNum) {

                    $result = $key;
                    break;
                }
            } else {
                $result = $key;
                break;
            }
            $i++;
        }
        unset ($proArr);

        return $result;
    }

    //奖池-new
    public function get_prize_pool()
    {
        $root = array('code' => 1, 'msg' => '');
        $box_id = intval(input('param.box_id'));

        $list = db('game_box_gift_list_rate')->alias('i')
            ->join('gift g', 'g.id = i.gift_id')
            ->field('i.count,i.id,g.img,g.coin,g.name')
            ->where("i.count >0 ")
            ->where("i.type_id = " . $box_id)
            ->group("i.gift_id")
            ->order("g.coin desc")
            ->select();
        $root['list'] = $list;
        return_json_encode($root);
    }

    //发送全局礼物消息
    private function push_all_gift_msg($user_info, $data, $voice_id)
    {
        $config = load_cache('config');

        $broadMsg['type'] = Enum::GLOBAL_GIFT;
        $sender['user_nickname'] = $user_info['user_nickname'];
        $sender['user_id'] = $user_info['id'];
        $sender['img'] = $data['img'];
        $sender['sum'] = $data['sum'];
        $sender['gift_name'] = $data['name'];
        $sender['money'] = $data['sum'] * $data['coin'];
        //$sender['send_msg'] = "恭喜“" . $user_info['user_nickname'] . '” 开宝箱获得';
        $sender['send_msg'] = /*"“".$user_info['user_nickname'].*/
            lang('Open_treasure_chest_to_obtain') . '“' . $data['name'] . '”';
        $broadMsg['is_all_channel'] = $data['is_all_notify'] == 1 ? 1 : 2;    //是否是飘屏 1是 2否
        //$broadMsg['is_all_channel'] = 1;    //是否是飘屏 1是 2否
        $broadMsg['is_male_screen'] = $data['is_male_screen'] == 1 ? 1 : 2;    //是否是全频道 1是 2否

        $broadMsg['voice_id'] = $voice_id; //房间id
        $broadMsg['sender'] = $sender;

        #构造rest API请求包
        $msg_content = array();
        //创建$msg_content 所需元素
        $msg_content_elem = array(
            'MsgType'    => 'TIMCustomElem',       //定义类型为普通文本型
            'MsgContent' => array(
                'Data' => json_encode($broadMsg)    //转为JSON字符串
            )
        );

        //将创建的元素$msg_content_elem, 加入array $msg_content
        array_push($msg_content, $msg_content_elem);

        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        $api = createTimAPI();

        $ret = $api->group_send_group_msg2($config['tencent_identifier'], $config['acquire_group_id'], $msg_content);

        return $ret;

    }

    public function add_rate_gift($user_id, $only)
    {
        //$result = array('code' => 0, 'msg' => '');
        //$user_id = isset($this->param_info['uid'])?$this->param_info['uid']:0;


        //$only = isset($this->param_info['only'])?$this->param_info['only']:0;
        $status = redis_hGet("Crontab_user_list_game_box_rate", $only);
        // 微秒
        //$microtime_start =microtime(true);
        $list = json_decode($status, true);
        //dump($list);die();
        //return_json_encode($list);
        if ($list && count($list) > 0) {
            redis_hDelOne("Crontab_user_list_game_box_rate", $only);
            $status = redis_hGet("Crontab_user_list_game_box_rate", $only);
            while ($status != false) {
                redis_hDelOne("Crontab_user_list_game_box_rate", $only);
                $status = redis_hGet("Crontab_user_list_game_box_rate", $only);
            }

            foreach ($list as $v) {
                $user_bag = db('user_bag')->where("uid=" . $v['uid'] . " and giftid=" . $v['gift_id'])->setInc('giftnum', intval($v['count']));
                if (!$user_bag) {  //背包中是否存在这个礼物
                    //添加背包记录
                    $gift_log = [
                        'uid'     => $v['uid'],
                        'giftid'  => $v['gift_id'],
                        'giftnum' => $v['count'],
                    ];
                    db('user_bag')->insert($gift_log);
                }
                //增加付费记录
                $private_chat_log = [
                    'uid'           => $v['uid'],
                    'user_nickname' => $v['user_nickname'],
                    'avatar'        => $v['user_info']['avatar'],
                    'box_id'        => $v['box_id'],
                    'gift_id'       => $v['gift_id'],
                    'name'          => $v['name'],
                    'img'           => $v['img'],
                    'coin'          => $v['coin'],
                    'voice_id'      => $v['voice_id'],
                    'sum'           => $v['count'],
                    //'type'   =>$v['type'],
                    'sum_id'        => $v['sum_id'],
                    'addtime'       => $v['time'],
                    'only'          => $v['only'],
                    //'pool_id' =>$v['pool_id'],
                    'sex'           => $v['user_info']['sex'],
                    'voice_user_id' => $v['voice_user_id'],
                    'voice_profit'  => $v['money'],
                    /*'cycles'  =>$v['cycles'],*/

                ];
                $table_id = db('game_box_log')->insertGetId($private_chat_log);
                //dump($table_id);die();
                //增加总消费记录
                /*add_charging_log($v['uid'], 0, 22, $v['sum']*$config['game_box_coin'], $table_id, 0);*/
                //系统推送
                if ($v['is_system_push'] == 1) {
                    $messagetype = lang("box_system_push", ['name' => $v['user_nickname'], 'n' => $v['sum'] * $v['coin'] . " " . $v['name']]);
                    //  $messagetype = lang('congratulations') . $v['user_nickname'] . lang('Gain_value_by_opening_treasure_chest') . $v['count'] * $v['coin'] . $v['name'] . lang('ADMIN_GIFT');
                    $message = array(
                        'uid'         => 0,
                        'touid'       => 0,
                        'messageid'   => 11,
                        'messagetype' => $messagetype,
                        'type'        => 2,
                        'status'      => 1,
                        'addtime'     => NOW_TIME,
                    );
                    db('user_message_log')->insertGetId($message);
                }
                //发广播 $v['user_info']['bubble_radio'] == 1 && (
                $v['sum'] = $v['count'];
                if ($v['is_all_notify'] == 1 || $v['is_male_screen'] == 1) {
                    $res = $this->push_all_gift_msg($v['user_info'], $v, $v['voice_id']);
                    //dump($res);
                }
            }
        }
        //return_json_encode($result);
    }
}
