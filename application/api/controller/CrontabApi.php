<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 11:33
 */

namespace app\api\controller;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

use app\common\Enum;
use think\Db;
use app\api\model\UserModel;

class CrontabApi extends Base
{
    /**
     * 幸运奖励 -- 头奖处理
     *  每分钟请求
     */
    function lucky_jackpot()
    {
        $jackpot_time = strtotime(date('Y-m-d H:i', NOW_TIME)) - 60;
        // 获取所有的幸运礼物
        $lucky_gift_list = Db::name("gift_lucky")->alias("l")
            ->field("l.*,g.name,g.coin as gift_coin")
            ->join("gift g", "g.id=l.gift_id")
            ->where("l.status", 1)
            ->order('l.create_time desc')
            ->select();
        $lucky_jackpot_time = redis_get("lucky_jackpot_time_val");

        if (intval($lucky_jackpot_time) && intval($lucky_jackpot_time) >= $jackpot_time) {
            return false;
        }
        redis_set("lucky_jackpot_time", $jackpot_time);
        $winners_user = [];
        foreach ($lucky_gift_list as $v) {
            $gift_id = $v['gift_id'];
            $user_number = redis_Scard($gift_id . "_" . $jackpot_time); // 获取这段时间内总消费用户
            $user_list = redis_smembers($gift_id . "_" . $jackpot_time);// 当前时间所有用户消费信息

            // 是否达到人数
            if (intval($user_number) >= intval($v['lucky_jackpot_limit']) && intval($user_number) >= intval($v['lucky_jackpot_winners'])) {

                $lucky_reward_lock = 'lucky_reward_lock:' . $gift_id;
                redis_locksleep_nx($lucky_reward_lock, true); // 加锁
                $lucky_reward_pools_val = redis_hGet('lucky_reward_gift', $gift_id);
                $lucky_reward_pools = json_decode($lucky_reward_pools_val, true);
                $lucky_jackpot_pot_amount = $lucky_reward_pools['coin']; // 获取奖池剩余金额
                bogokjLogPrint("lucky_jackpot", "coin =" . $lucky_jackpot_pot_amount . ";lucky_gift=" . json_encode($v));
                // 奖池中的金额是否已达到
                if (intval($lucky_jackpot_pot_amount) >= $v['lucky_jackpot_pot_amount']) {
                    $text = '';
                    for ($i = 0; $i < intval($v['lucky_jackpot_winners']); $i++) {
                        $text = lucky_jackpot_rand($user_list, $user_number, $text);
                    }
                    bogokjLogPrint("lucky_jackpot", "text =" . $text);
                    if ($text) {
                        $text = trim($text, ",");
                        $winners_user = explode(",", $text);
                        add_lucky_jackpot_winners($winners_user, $v, $lucky_reward_pools, $jackpot_time);
                    }
                }
                redis_unlock_nx($lucky_reward_lock);// 解锁
            }
        }
        echo json_encode($winners_user);
    }

    // 解除拉黑状态 --- 每3秒执行一次
    public function remove_pull_black()
    {
        $user_list = db('user')->where("shielding_time <=" . NOW_TIME . " and shielding_time>0 && user_status=0")->select();
        foreach ($user_list as $v) {
            $data['shielding_time'] = 0;
            $data['user_status'] = 1;
            db('user')->where('id =' . $v['id'] . " and user_type=2")->update($data);
        }
    }

    /**
     * 九宫格定时器
     * */
    public function crontab_gongge()
    {
        $redis_game = "Crontab_gongge_game";
        $list = array();
        $num = 5;
        for ($i = 0; $i <= $num; $i++) {
            $list_num = redis_lPop($redis_game);
            if ($list_num) {
                $list[] = json_decode($list_num, true);
            }
        }
        $config = load_cache('config');

        foreach ($list as $v_list) {
            foreach ($v_list as $v) {
                if ($v['gift_id'] > 0) {
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
                }
                //增加付费记录
                $private_chat_log = [
                    'uid'         => $v['uid'],
                    'voice_id'    => $v['voice_id'],
                    'gongge_id'   => $v['gongge_id'],
                    'gongge_coin' => $v['gongge_coin'],
                    'gift_id'     => $v['gift_id'],
                    'gift_coin'   => $v['coin'],
                    'sum'         => $v['count'],
                    'create_time' => $v['time'],
                    'frequency'   => $v['frequency'],
                    'order'       => $v['only'],
                    'upd_time'    => NOW_TIME
                ];
                $table_id = db('gongge_log')->insertGetId($private_chat_log);

                if ($v['log_id']) {
                    //更新消费记录
                    db('user_consume_log')->where("id=" . $v['log_id'] . " and table_id=0 and user_id=" . $v['uid'])->update(['table_id' => $table_id]);
                }
                //系统推送
                if ($v['is_system_push'] == 1 && $v['gift_id'] > 0) {
                    // 恭喜*** 抽中了价值**  礼物名称
                    $messagetype = lang('congratulations') . "您抽 《" . $v['gongge_name'] . "》,获得价值" . $v['coin'] . $config['currency_name'] . "的" . $v['name'] . " x" . $v['count'];
                    $message = array(
                        'uid'         => 0,
                        'touid'       => $v['uid'],
                        'messageid'   => 11,
                        'messagetype' => $messagetype,
                        'type'        => 2,
                        'status'      => 1,
                        'addtime'     => NOW_TIME,
                    );
                    db('user_message_log')->insertGetId($message);
                }
                //发广播 -- 公屏或飘屏
                if (($v['is_all_notify'] == 1 || $v['is_male_screen'] == 1) && $v['gift_id'] > 0) {
                    $v['sum'] = $v['count'];
                    $this->push_all_gift_msg($v['user_info'], $v, $v['voice_id'], 2);
                }
            }
        }
    }

    /**
     * 浇树定时器 每3秒执行一次
     **/
    public function crontab_tree()
    {
        $redis_game = "Crontab_tree_game";
        $list = redis_lPop($redis_game);
        $config = load_cache('config');

        if ($list) {
            $list = json_decode($list, true);
            foreach ($list as $v) {
                if ($v['gift_id'] > 0) {
                    $user_bag = db('user_bag')->where("uid=" . $v['uid'] . " and giftid=" . $v['gift_id'])->find();
                    if ($user_bag) {
                        db('user_bag')->where("id=" . $user_bag['id'])->setInc('giftnum', intval($v['count']));
                    } else {
                        //添加背包记录
                        $gift_log = [
                            'uid'     => $v['uid'],
                            'giftid'  => $v['gift_id'],
                            'giftnum' => $v['count'],
                        ];
                        db('user_bag')->insert($gift_log);
                    }
                }
                //增加付费记录
                $private_chat_log = [
                    'uid'            => $v['uid'],
                    'user_name'      => $v['user_nickname'],
                    'gift_id'        => $v['gift_id'],
                    'gift_name'      => $v['name'],
                    'gift_img'       => $v['img'],
                    'gift_coin'      => $v['coin'],
                    'sum'            => $v['count'],
                    'coin_id'        => $v['sum_id'],
                    'coin_explain'   => $v['coin_explain'],
                    'coin_sum'       => $v['coin_sum'],
                    'coin_sum_money' => $v['coin_sum_money'],
                    'coin_money'     => $v['coin_money'],
                    'surplus_coin'   => $v['surplus_coin'],
                    'only'           => $v['only'],
                    'voice_id'       => $v['voice_id'],
                    'voice_user_id'  => $v['voice_user_id'],
                    'voice_profit'   => $v['voice_profit'],
                    'cycles'         => $v['cycles'],
                    'pool_json'      => json_encode($v['pool_json']),
                    'create_time'    => NOW_TIME
                ];
                $table_id = db('game_tree_log')->insertGetId($private_chat_log);
                if ($v['log_id']) {
                    //更新消费记录
                    db('user_consume_log')->where("id=" . $v['log_id'] . " and table_id=0 and user_id=" . $v['uid'])->update(['table_id' => $table_id]);
                }
                //系统推送
                if ($v['is_system_push'] == 1 && $v['gift_id'] > 0) {
                    // 恭喜*** 抽中了价值**  礼物名称
                    $messagetype = lang('congratulations') . "《" . $v['game_name'] . "》,Get gift ：" . $v['name'] . " x" . $v['count'] . " (" . $v['coin'] . $config['currency_name'] . ")";
                    $message = array(
                        'uid'         => 0,
                        'touid'       => $v['uid'],
                        'messageid'   => 11,
                        'messagetype' => $messagetype,
                        'type'        => 2,
                        'status'      => 1,
                        'addtime'     => NOW_TIME,
                    );
                    db('user_message_log')->insertGetId($message);
                }
                //发广播 -- 公屏或飘屏
                if (($v['is_all_notify'] == 1 || $v['is_male_screen'] == 1) && $v['gift_id'] > 0) {
                    $v['sum'] = $v['count'];
                    $this->push_all_gift_msg($v['user_info'], $v, $v['voice_id'], 2);
                }
            }
        }
        var_dump($list);
    }

    // 申请退出公会,会长如果没有拒绝申请一周后自动退出 /api/crontab_api/save_guild_join_quit  每天, 0点30分 执行
    public function save_guild_join_quit()
    {
        $endtime = NOW_TIME - 7 * 24 * 60 * 60; //前端显示申请退出公会，会长如果没有拒绝申请一周后自动退出
        $list = db('guild_join_quit')->where("status= 0 and create_time <=" . $endtime)->select();
        foreach ($list as $v) {
            $guild_join = db('guild_join')->where('id=' . $v['guild_join_id'])->find();
            if ($guild_join) {
                // 删除公会列表
                db('guild_join')->where('id=' . $guild_join['id'])->delete();
            }
            $update_data = array(
                'status'   => 1,
                'explain'  => lang('Did_not_refuse_to_quit_for_one_week'),
                'end_time' => NOW_TIME
            );
            db('guild_join_quit')->where('id=' . $v['id'])->update($update_data);
        }
    }

    //每分钟 请求一次
    public function minute_crontab()
    {
        $this->player_cancel_order();//取消超时订单
    }

    public function service_crontab()
    {
        crontab_do_end_voice();
        crontab_do_end_live();
        crontab_do_end_call();

        //清除所有过期的心跳
        $config = load_cache('config');

        //时间
        $time = NOW_TIME - $config['heartbeat_interval'] - 60;//偏移量5秒


        db('monitor')->where('monitor_time', '<', $time)->delete();
    }

    /*
    *   定时获取当天的游戏数据
    */
    public function game_earnings_sum()
    {

        $time = date("Y-m-d", strtotime("-1 day"));
        $start_time = strtotime($time);
        $end_time = strtotime($time . " 24:00:00");
        $where = "l.addtime >=" . $start_time . " and l.addtime <=" . $end_time;

        $is_insert = db('bubble_day_log')->where("date='" . $time . "'")->find();
        if ($is_insert) {
            echo 1;
            exit;
        }
        $game_list = db('game_list')->where("status=1")->select();
        $data = [];
        foreach ($game_list as $k => $v) {
            if ($v['type'] == 2) {
                // 打泡泡  统计当天的指定用户消费金额
                $vip_magic_wand_log = db('magic_wand_log')->alias('l')
                    ->join('user u', 'u.id=l.uid')
                    ->where($where . " and u.is_named_user=1")
                    ->sum("l.coin");

                //统计当天的普通用户消费金额
                $magic_wand_log = db('magic_wand_log')->alias('l')
                    ->join('user u', 'u.id=l.uid')
                    ->where($where . " and u.is_named_user !=1")
                    ->sum("l.coin");

                //统计当天的普通用户获得的礼物
                $playing_bubble_log = db('playing_bubble_log')->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->field("sum(l.expend) as expend,sum(l.sum) as gift_sum,sum(l.sum*g.coin) as gift_coin")
                    ->where($where . " and l.type =1")
                    ->find();
                //统计当天的指定用户获得的礼物
                $vip_playing_bubble_log = db('playing_bubble_log')->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->field("sum(l.expend) as expend,sum(l.sum) as gift_sum,sum(l.sum*g.coin) as gift_coin")
                    ->where($where . " and l.type =2")
                    ->find();

                $value1 = array(
                    'coin'      => intval($vip_magic_wand_log),
                    'magic_sum' => $vip_playing_bubble_log['expend'] ? $vip_playing_bubble_log['expend'] : 0,
                    'gift_sum'  => $vip_playing_bubble_log['gift_sum'] ? $vip_playing_bubble_log['gift_sum'] : 0,
                    'gift_coin' => $vip_playing_bubble_log['gift_coin'] ? $vip_playing_bubble_log['gift_coin'] : 0,
                    'type'      => 2,
                    'date'      => $time,
                    'game_id'   => $v['id'],
                );
                $data[] = $value1;

                $value2 = array(
                    'coin'      => intval($magic_wand_log),
                    'magic_sum' => $playing_bubble_log['expend'] ? $playing_bubble_log['expend'] : 0,
                    'gift_sum'  => $playing_bubble_log['gift_sum'] ? $playing_bubble_log['gift_sum'] : 0,
                    'gift_coin' => $playing_bubble_log['gift_coin'] ? $playing_bubble_log['gift_coin'] : 0,
                    'type'      => 1,
                    'date'      => $time,
                    'game_id'   => $v['id'],
                );
                $data[] = $value2;

            } elseif ($v['type'] == 1) {
                //统计当天的砸蛋消费
                $user_eggs_log = db('user_eggs_log')->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->field("sum(l.coin) as coin,sum(l.sum) as gift_sum,sum(l.sum*g.coin) as gift_coin")
                    ->where($where)
                    ->find();
                $value3 = array(
                    'coin'      => $user_eggs_log['coin'],
                    'magic_sum' => 0,
                    'gift_sum'  => $user_eggs_log['gift_sum'],
                    'gift_coin' => $user_eggs_log['gift_coin'],
                    'type'      => 1,
                    'date'      => $time,
                    'game_id'   => $v['id'],
                );
                $data[] = $value3;

            }
        }

        if (count($data)) {
            db('bubble_day_log')->insertAll($data);
        }
    }

    /**
     * @dw 自动打招呼定时任务
     * */
    public function service_see_hi_crontab()
    {
        if (defined('OPEN_AUTO_SEE_HI_PLUGS') && OPEN_AUTO_SEE_HI_PLUGS == 1) {
            crontab_do_auto_talking_custom_tpl();
        } else {
            crontab_do_auto_talking();
        }
    }

    //获取昨天的营业
    public function business_day()
    {

        $data['time'] = date("Y-m-d", strtotime("-1 day"));
        //获取昨天
        $beginToday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;

        $where1 = "status=1 and addtime >=" . $beginToday . " and addtime <" . $endToday;
        $where2 = "status =1 and updatetime >=" . $beginToday . " and updatetime <" . $endToday;
        //昨天收入的金额(充值记录)
        $data['income'] = db("user_charge_log")->where($where1)->sum("money");
        //昨天支出的金额 (提现记录)
        //invite_cash_record    邀请收益提现  agent_withdrawal 代理提现  user_cash_record  用户主播提现
        $invite_cash_record = db("invite_cash_record")->where($where2)->sum("coin");
        $agent_withdrawal = db("agent_withdrawal")->where($where2)->sum("money");
        $user_cash_record = db("user_cash_record")->where($where2)->sum("money");

        $invite_cash_record = $invite_cash_record ? $invite_cash_record : '0';
        $agent_withdrawal = $agent_withdrawal ? $agent_withdrawal : '0';
        $user_cash_record = $user_cash_record ? $user_cash_record : '0';
        $data['income'] = $data['income'] ? $data['income'] : '0';
        $data['spending'] = $invite_cash_record + $agent_withdrawal + $user_cash_record;

        $data['invite_record'] = $invite_cash_record;
        $data['host_record'] = $user_cash_record;
        $data['agent_record'] = $agent_withdrawal;

        if ($data['income'] >= $data['spending']) {
            $data['type'] = 1;
            $data['statistical'] = $data['income'] - $data['spending'];
        } else {
            $data['type'] = 2;
            $data['statistical'] = $data['spending'] - $data['income'];
        }
        $data['addtime'] = time();

        $financial = db("admin_financial")->where("time = '" . $data['time'] . "'")->find();

        if ($financial) {
            db('admin_financial')->where("time='" . $data['time'] . "'")->update($data);
        } else {
            db('admin_financial')->insert($data);
        }

    }

    /**
     * 获取所有的cps 代理 LPush 每日凌晨只执行一次
     */
    public function get_agent_list()
    {
        // 获取所有的渠道代理
        $agent = db('agent')->order("id DESC")->select();
        $key = "agent_day";
        foreach ($agent as $v) {
            redis_lpush($key, json_encode($v));
        }
    }

    /**
     * 昨日cps代理渠道的用户统计入库 - 每秒执行一次
     **/
    public function add_channel_users()
    {

        $key = "agent_day";
        $agent = redis_lpop($key);
        if ($agent) {
            $agent = json_decode($agent, true);
            //获取昨天开始和结束时间
            $beginToday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
            $endToday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
            $data_one = array(
                'uid'           => $agent['id'],
                'agent_staff'   => $agent['agent_staff'],
                'agent_company' => $agent['agent_company'],
                'addtime'       => time(),
                'date_time'     => date("Y-m-d", strtotime("-1 day")),
                'month_time'    => date("Ym", strtotime("-1 day")),
            );
            $where = "addtime >=" . $beginToday . " and addtime <" . $endToday;
            $field = "sum(money) as money,sum(agent_money) as agent_money";

            $agent_where = $where . " and agent_id=" . $agent['id'];
            // 获取当前代理充值数据
            $user_money = db('agent_order_log')->field($field)->where($agent_where . " and type=0")->find();

            // 获取当日注册数
            $user_where = "create_time >=" . $beginToday . " and create_time <" . $endToday;
            $user_count = db('user')->where($user_where . " and link_id=" . $agent['id'])->count();

            if ($agent['agent_level'] == 1) { // 公司
                $user_where .= " and agent_company=" . $agent['id'];
            } elseif ($agent['agent_level'] == 2) { // 员工
                $user_where .= " and agent_staff=" . $agent['id'];
            } else {
                $user_where .= " and agent_id=" . $agent['id'];
            }

            // 获取当日的消费数
            $consume = Db::name('user_consume_log')
                ->where($user_where)
                ->sum('coin');

            // 获取总注册数 获取总充值数
            $user_money_level = array();

            $user_count_level = 0;
            if ($agent['agent_level'] < 3) {
                $field_level = "sum(l.money) as money,sum(l.agent_money) as agent_money";
                $where_level = "l.addtime >=" . $beginToday . " and l.addtime <" . $endToday . " and l.agent_id !=" . $agent['id'];
                $where_id_leve = '';
                if ($agent['agent_level'] == 1) { // 公司
                    $where_id_leve .= " and a.agent_company=" . $agent['id'];
                } elseif ($agent['agent_level'] == 2) { // 员工
                    $where_id_leve .= " and a.agent_staff=" . $agent['id'];
                }

                // 获取当前代理充值数据
                $user_money_level = Db::name('agent_order_log')->alias("l")
                    ->join('agent a', 'a.id = l.agent_id')
                    ->where($where_level . $where_id_leve . " and type=0")
                    ->field($field_level)
                    ->find();
                // 获取当日注册数
                $user_where_level = "u.create_time >=" . $beginToday . " and u.create_time <" . $endToday . " and u.link_id !=" . $agent['id'];
                $user_count_level = Db::name('user')->alias("u")
                    ->join("agent a", "a.id = u.link_id")
                    ->where($user_where_level . $where_id_leve)
                    ->count();

            }

            $data_one['money'] = $user_money ? floatval($user_money['money']) : 0;
            $data_one['agent_money'] = $user_money ? intval($user_money['agent_money']) : 0;
            $data_one['register_sum'] = $user_count ? intval($user_count) : 0;
            $data_one['consumption'] = $consume ? intval($consume) : 0;
            $data_one['invitation_money'] = $user_money_level ? floatval($user_money_level['money']) : 0;
            $data_one['invitation_agent_money'] = $user_money_level ? intval($user_money_level['agent_money']) : 0;
            $data_one['invitation_register_sum'] = $user_count_level ? intval($user_count_level) : 0;
            // 渠道注册详情
            db('agent_statistical')->insert($data_one);
        }
    }

    /*
     * 宝箱游戏定时任务
     * 分次数奖池
     * */
    public function crontab_user_game_box_old()
    {
        $redis_lock_nx_name = 'crontab_redis_lock_nx_bx';
        $time_lock = time() . rand(100000, 999999);
        redis_locksleep_nx($redis_lock_nx_name, $time_lock);

        $add_list = redis_hVals("Crontab_user_list_game_box");

        // 微秒
        $microtime_start = microtime(true);
        $config = load_cache('config');
        if (count($add_list) > 0) {
            foreach ($add_list as $vs) {
                $list = json_decode($vs, true);
                //dump($list);
                if (count($list) > 0) {
                    $only = $list[0]['only'];

                    redis_hDelOne("Crontab_user_list_game_box", $only);
                    $status = redis_hGet("Crontab_user_list_game_box", $only);
                    while ($status != false) {
                        edis_hDelOne("Crontab_user_list_game_box", $only);
                        $status = redis_hGet("Crontab_user_list_game_box", $only);
                    }

                    foreach ($list as $v) {
                        $user_bag = db('user_bag')->where("uid=" . $v['uid'] . " and giftid=" . $v['gift_id'])->setInc('giftnum', intval($v['sum']));
                        if (!$user_bag) {  //背包中是否存在这个礼物
                            //添加背包记录
                            $gift_log = [
                                'uid'     => $v['uid'],
                                'giftid'  => $v['gift_id'],
                                'giftnum' => $v['sum'],
                            ];
                            db('user_bag')->insert($gift_log);
                        }
                        //增加付费记录
                        $private_chat_log = [
                            'uid'           => $v['uid'],
                            'voice_id'      => $v['voice_id'],
                            'bubble_id'     => $v['id'],
                            'gift_id'       => $v['gift_id'],
                            'sum'           => $v['sum'],
                            'addtime'       => $v['time'],
                            'type'          => $v['type'],
                            'expend'        => $v['sum'],
                            'continuous_id' => $v['sum_id'],
                            'pool_id'       => $v['pool_id'],
                            'voice_user_id' => $v['voice_user_id'],
                            'cycles'        => $v['cycles'],
                            'only'          => $v['only'],
                        ];
                        $table_id = db('game_box_log')->insertGetId($private_chat_log);
                        //增加总消费记录
                        add_charging_log($v['uid'], 0, 17, 0, $table_id, 0);
                        //系统推送
                        if ($v['is_system_push'] == 1) {
                            $messagetype = lang('congratulations') . $v['user_nickname'] . lang('Gain_value_by_opening_treasure_chest') . $v['sum'] * $v['coin'] . $v['name'] . lang('ADMIN_GIFT');
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
                        if ($v['is_all_notify'] == 1 || $v['is_male_screen'] == 1) {
                            $res = $this->push_all_gift_msg($v['user_info'], $v, $v['voice_id']);
                            //dump($res);
                        }
                        $coin = $v['sum'] * $config['game_box_coin'];
                        $prize_coin = $v['sum'] * $v['coin'];
                        $log = db('user_game_consumption')->where(['uid' => $v['uid'], 'type' => 3])->find();

                        if ($log) {
                            $pool_id = $log['pool_id'];
                            $consumption_coin = $coin + $log['consumption_coin'];
                            //$prize_coin =
                            $data_game['consumption_coin'] = $consumption_coin;
                            $data_game['prize_coin'] = $prize_coin + $log['prize_coin'];
                            $pool = db('game_box_pool')->find($pool_id);//当前奖池
                            //奖池消费记录表
                            $pool_log = db('game_box_pool_log')->where(['uid' => $v['uid'], 'pool_id' => $pool_id])->find();

                            //当前奖池消费数
                            $money = $pool_log['money'] + $coin;
                            if ($money >= $pool['consumption']) {
                                //跳转加奖池 清空消费
                                $data_log['money'] = 0;
                                $data_log['prize'] = 0;
                                $data_game['pool_id'] = $pool['next_pool'];//下一奖池ID
                                //下一奖池信息
                                $next_pool = db('game_box_pool')->find($pool['next_pool']);
                                //添加用户个池
                                if ($next_pool['pool_type'] == 2) {
                                    $user_pool = db('game_box_user_pool')
                                        ->where(['uid' => $v['uid'], 'pool_id' => $pool['next_pool'], 'continuous_id' => $v['sum_id']])
                                        ->find();
                                }
                                $bubble_type = db('game_box_type')->where('status = 1 and type = 1 and id = ' . $v['sum_id'])->select();
                                if ($bubble_type && $next_pool['pool_type'] == 2) {
                                    //foreach ($bubble_type as $val){
                                    $user_pool = db('game_box_user_pool')
                                        ->where(['uid' => $v['uid'], 'pool_id' => $pool['next_pool'], 'continuous_id' => $v['sum_id']])
                                        ->find();
                                    if (!$user_pool) {
                                        $bubble_list = db('game_box_gift_list')
                                            ->alias('i')
                                            ->join('gift g', 'g.id = i.gift_id')
                                            ->field('i.*,g.img,g.coin,g.name')
                                            ->where('i.pool_id = ' . $pool['next_pool'])
                                            ->where('i.continuous_id = ' . $v['sum_id'])
                                            ->select();
                                        $user_pool_data = [
                                            'uid'           => $v['uid'],
                                            'continuous_id' => $v['sum_id'],
                                            'pool_id'       => $pool['next_pool'],
                                            'pool'          => json_encode($bubble_list),
                                            'addtime'       => NOW_TIME,
                                        ];
                                        db('game_box_user_pool')->insert($user_pool_data);
                                    }
                                }
                                db('game_box_pool_log')->where(['uid' => $v['uid'], 'pool_id' => $pool_id])->update($data_log);
                                $next_pool_log = db('game_box_pool_log')->where(['uid' => $v['uid'], 'pool_id' => $pool['next_pool']])->find();
                                if ($next_pool_log) {
                                    db('game_box_pool_log')
                                        ->where(['uid' => $v['uid'], 'pool_id' => $pool['next_pool']])
                                        ->inc('money', $coin)
                                        ->inc('prize', $prize_coin)
                                        ->update();
                                } else {
                                    $data_log = [
                                        'uid'     => $v['uid'],
                                        'pool_id' => $pool['next_pool'],
                                        'money'   => $coin,
                                        'prize'   => $prize_coin,
                                        'addtime' => NOW_TIME,
                                    ];
                                    db('game_box_pool_log')->insertGetId($data_log);
                                }
                            }
                            db('user_game_consumption')->where(['uid' => $v['uid'], 'type' => 3])->update($data_game);
                        } else {
                            $pool = db('game_box_pool')->where('pool_type = 1')->order('id')->find();//当前奖池
                            $data_game = [
                                'uid'              => $v['uid'],
                                'type'             => 3,
                                'consumption_coin' => $coin,
                                'prize_coin'       => $prize_coin,
                                'pool_id'          => $pool['id'],
                                'rate'             => 0,
                            ];
                            $res = db('user_game_consumption')->insertGetId($data_game);
                            $data_log = [
                                'uid'     => $v['uid'],
                                'pool_id' => $pool['id'],
                                'money'   => $coin,
                                'prize'   => $prize_coin,
                                'addtime' => NOW_TIME,
                            ];
                            db('game_box_pool_log')->insertGetId($data_log);
                        }

                    }

                    // 微秒
                    $microtime_end = microtime(true);
                    $microtime = $microtime_end - $microtime_start;
                    $datas = array(
                        'microtime_start' => $microtime_start,
                        'microtime_end'   => $microtime_end,
                        'microtime'       => $microtime,
                        'microtime'       => count($add_list),
                        'only'            => $only,
                        'list'            => $list
                    );
                    redis_hSet("Crontab_user_game_box_ts3", time() . '_' . $microtime, json_encode($datas));
                }
            }
        }
        // 关闭缓存
        redis_unlock_nx($redis_lock_nx_name);
    }

    /*
     * 宝箱游戏定时任务 -new
     * */
    public function crontab_user_game_box()
    {
        for ($i = 1; $i <= 30; $i++) {
            $add_list = redis_lPop("Crontab_game_box_winning");
            if ($add_list) {
                $list = json_decode($add_list, true);
                foreach ($list as $v) {
                    $user_bag = db('user_bag')->where("uid=" . $v['uid'] . " and giftid=" . $v['gift_id'])->setInc('giftnum', intval($v['sum']));
                    if (!$user_bag) {  //背包中是否存在这个礼物
                        //添加背包记录
                        $gift_log = [
                            'uid'     => $v['uid'],
                            'giftid'  => $v['gift_id'],
                            'giftnum' => $v['sum'],
                        ];
                        db('user_bag')->insert($gift_log);
                    }
                    //增加付费记录
                    $private_chat_log = [
                        'uid'            => $v['uid'],
                        'user_nickname'  => $v['user_nickname'],
                        'avatar'         => $v['user_info']['avatar'],
                        'user_coin'      => $v['user_info']['coin'],
                        'total_coin'     => $v['total_coin'],
                        'voice_id'       => $v['voice_id'],
                        'pool_id'        => $v['id'],
                        'gift_id'        => $v['gift_id'],
                        'name'           => $v['name'],
                        'img'            => $v['img'],
                        'coin'           => $v['coin'],
                        'sum'            => $v['sum'],
                        'addtime'        => $v['time'],
                        'type'           => 1,
                        'sum_id'         => $v['sum_id'],
                        'box_id'         => $v['type_id'],
                        'box_name'       => $v['game_box_type_name'],
                        'voice_user_id'  => $v['voice_user_id'],
                        'cycles'         => $v['cycles'],
                        'only'           => $v['only'],
                        'coin_sum'       => $v['coin_sum'],
                        'coin_sum_money' => $v['coin_sum_money'],
                        'pool_json'      => json_encode($v['pool_json']),
                        'is_grand_prix'  => $v['is_grand_prix'],
                    ];

                    $table_id = db('game_box_log')->insertGetId($private_chat_log);

                    if ($v['log_id']) {
                        //更新消费记录
                        db('user_consume_log')->where("id=" . $v['log_id'] . " and table_id=0 and user_id=" . $v['uid'])->update(['table_id' => $table_id]);
                    }
                    $is_Push = 1;
                    if ($v['uid'] == 55874 || $v['uid'] == 56110 || $v['uid'] == 59251 || $v['uid'] == 59248 || $v['uid'] == 59246 || $v['uid'] == 59244) {
                        $is_Push = 0;
                    }
                    if ($is_Push == 1) {
                        //系统推送
                        if ($v['is_system_push'] == 1) {
                            $messagetype = lang('congratulations') . $v['user_nickname'] . lang('Gain_value_by_opening_treasure_chest') . $v['sum'] * $v['coin'] . $v['name'] . lang('ADMIN_GIFT');
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
                        //发广播
                        if ($v['is_all_notify'] == 1 || $v['is_male_screen'] == 1) {
                            $this->push_all_gift_msg($v['user_info'], $v, $v['voice_id']);
                        }
                    }
                }
            }
        }
    }

    //发送全局礼物消息
    private function push_all_gift_msg($user_info, $data, $voice_id, $type = '1')
    {

        $config = load_cache('config');

        $broadMsg['type'] = Enum::GLOBAL_GIFT;
        $sender['user_nickname'] = $user_info['user_nickname'];
        $sender['user_id'] = $user_info['id'];
        $sender['img'] = $data['img'];
        $sender['sum'] = $data['sum'];
        $sender['gift_name'] = $data['name'];
        $sender['money'] = $data['sum'] * $data['coin'];
//        if ($type == 1) {
//            $sender['send_msg'] = lang('congratulations') . $user_info['user_nickname'] . "开宝箱获得";
//        }else {
//            $sender['send_msg'] = lang('congratulations') . $user_info['user_nickname'] . "获得".$data['name'];
//        }
        if ($type == 1) {
            $sender['send_msg'] = lang('Get_rewards') . ' “' . $data['name'] . '” ';
        } else {
            $sender['send_msg'] = lang('Get_rewards') . " “" . $data['name'] . '” ';
        }

        $broadMsg['is_all_channel'] = $data['is_all_notify'] == 1 ? 1 : 2;    //是否是飘屏 1是 2否
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

    //陪玩订单到期自动取消
    public function player_cancel_order()
    {
        //所有订单
        $time = 15 * 60;//未接单超过15分钟自动取消订单，完成服务后30分钟自动确认订单4
        $map['status'] = ['in', [1, 2, 4]];
        $list = db('skills_order')->where($map)->select();
        if ($list) {
            foreach ($list as $val) {
                if ($val['status'] == 4) {
                    if ((NOW_TIME - $val['ordertime']) > (60 * 30)) {
                        $data = [
                            'status'    => 5,
                            'edit_time' => NOW_TIME,
                        ];
                        $res = db('skills_order')->where(['id' => $val['id']])->update($data);
                        if ($res) {
                            //给陪玩师增加收益 总收益
                            $total_income = $val['total_income'];
                            $user_earnings = db('user')
                                ->where('id=' . $val['touid'])
                                ->inc('income', $total_income)
                                ->inc('income_total', $total_income)
                                ->inc('income_player_total', $total_income)
                                ->update();
                            if ($user_earnings) {
                                if ($val['guild_earnings'] && $val['user_id']) {
                                    // 公会长--用户收益
                                    $UserModel = new UserModel();
                                    $to_user_info = db('user')->where("id=" . $val['touid'])->find();
                                    $UserModel->add_user_earnings($val['guild_uid'], $val['guild_earnings'], $to_user_info, 14);
                                }
                                upd_user_coin_log($val['touid'], $total_income, $total_income, 6, 2, 1, '127.0.0.1', $val['uid']);
                                player_order_msg($val['id'], 5);
                            }
                            db('user_consume_log')->where("type=7 and status=0 and table_id=" . $val['id'] . " and uid=" . $val['uid'])->update(['status' => 1]);
                        } else {
                            $result['code'] = 0;
                        }
                    }
                } else if ($val['status'] == 2) {
                    //订单是否逾期
                    if (NOW_TIME > $val['ordertime'] && (NOW_TIME - $val['ordertime']) > $time) {
                        //取消订单
                        $data = [
                            'status'    => 8,
                            'edit_time' => NOW_TIME,
                        ];
                        $res = db('skills_order')->where(['id' => $val['id']])->update($data);
                        if ($res) {
                            //返还金币
                            $coin = $val['total_coin'];
                            db('user')
                                ->where('id=' . $val['uid'])
                                ->inc('coin', $coin)
                                ->update();
                            //增加消费记录
                            upd_user_coin_log($val['uid'], $coin, $coin, 9, 1, 1, '127.0.0.1', 1);
                            //订单消息
                            player_order_msg($val['id'], 8);
                            //删除用户消费记录
                            del_user_consume_log($val['uid'], 7, $val['id']);
                        }
                    }
                } else {
                    //订单是否未接单
                    if ((NOW_TIME - $val['addtime']) > $time) {
                        //取消订单
                        $status = 8;
                        $res = db('skills_order')->where(['id' => $val['id']])->update(['status' => $status]);
                        if ($res) {
                            //返还金币
                            $coin = $val['total_coin'];
                            db('user')
                                ->where('id=' . $val['uid'])
                                ->inc('coin', $coin)
                                ->update();
                            //增加消费记录
                            upd_user_coin_log($val['uid'], $coin, $coin, 9, 1, 1, '127.0.0.1', 1);
                            //订单消息
                            player_order_msg($val['id'], 8);
                            //删除用户消费记录
                            del_user_consume_log($val['uid'], 7, $val['id']);
                        }
                    }
                }
                /*$status = 0;
                if($status!=0){
                    //取消订单
                    $status = 8;
                    $res = db('skills_order')->where(['id'=>$val['id']])->update(['status'=>$status]);
                    if($res){
                        //返还金币
                        $coin = $val['total_coin'];
                        db('user')
                            ->where('id='.$val['uid'])
                            ->inc('coin', $coin)
                            ->update();
                        //增加消费记录
                        upd_user_coin_log($val['uid'],$coin,$coin,9,1,1,'127.0.0.1',1);
                        //订单消息
                        player_order_msg($val['id'],8);
                    }
                }*/

            }
        }

    }
}
