<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 8:59
 */

/**
 * 推送用户私照审核认证结果消息
 * */
function push_private_photo_auth_result_msg()
{


}
// 查询当天的统计
function day_count($agent_id){
    $beginToday =strtotime(date('Y-m-d ', time()));
    $endToday = strtotime(date('Y-m-d ', time() + 3600 * 24));
    $data_one=array(
        'addtime' => time(),
        'date_time' => date("Y-m-d", strtotime("-1 day")),
    );
    $day_where = "addtime >=" . $beginToday . " and addtime <" . $endToday;
    $agent_where = $day_where." and agent_id=".$agent_id;
    $field="sum(money) as money,sum(agent_money) as agent_money,sum(total_agent_money) as total_agent_money";
    // 获取本代理充值数据
    $user_money = db('agent_order_log')->field($field)->where($agent_where." and subordinate=0")->find();
    // 获取下级代理充值数据
    $subordinate_money = db('agent_order_log')->field($field)->where($agent_where." and subordinate !=0")->find();

    // 总充值金额
    $data_one['money'] = $user_money && $user_money['money'] ? $user_money['money'] : 0;
    $data_one['subordinate_money'] = $subordinate_money && $subordinate_money['money'] ? $subordinate_money['money'] : 0;
    // 当前代理收益
    $data_one['agent_money'] = $user_money && $user_money['agent_money'] ? $user_money['agent_money'] : 0;
    $data_one['subordinate_agent_money'] = $subordinate_money && $subordinate_money['agent_money'] ? $subordinate_money['agent_money'] : 0;
    // 总收益数(包括下级)
    $data_one['total_agent_money'] = $user_money && $user_money['total_agent_money'] ? $user_money['total_agent_money'] : 0;
    $data_one['subordinate_total_agent_money'] = $subordinate_money && $subordinate_money['total_agent_money'] ? $subordinate_money['total_agent_money'] : 0;
    // 获取本代理注册数量
    $agent_register = db('agent_register')->field($field)->where($agent_where." and subordinate=0")->count();
    // 获取下级代理注册数据
    $subordinate_register = db('agent_register')->field($field)->where($agent_where." and subordinate !=0")->count();
    $data_one['register_sum'] = $agent_register ? $agent_register : 0;
    $data_one['subordinate_register_sum'] = $subordinate_register ? $subordinate_register : 0;

    return $data_one;
}

/**
 *  首页统计数据
 */
function admin_index_list($agent)
{
    //获取当天
    $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
    //获取昨天
    $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
    $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
    //支付
    $data['charge'] = admin_index_charge($beginToday, $beginYesterday, $agent);
    //注册
    $data['registered'] = admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday, $agent);

    //结算
    if ($agent['agent_level'] == 1) {
        $data['settlement'] = agent_settlement($beginToday, $endToday, $beginYesterday, $endYesterday, $agent);
    }


    return $data;
}

//充值统计
function admin_index_charge($beginToday, $beginYesterday, $agent)
{

    $where1 = "date_time ='" . date("Y-m-d", $beginToday) . "'";
    $where2 = "date_time ='" . date("Y-m-d", $beginYesterday) . "'";

    // cmf_agent_order_log
    $channel = " agent_id='" . $agent['id'] . "' and subordinate=0";
    // 获取当前渠道充值金额
    //今天
    $data['day_log'] = agent_charge($where1 . " and " . $channel);
    //昨天
    $data['Yesterday_log'] = agent_charge($where2 . " and " . $channel);
    //所有的
    $data['total_log'] = agent_charge($channel);

    //今天和昨天 支付金额比较
    $data['day_than'] = day_Yesterday_than(intval($data['day_log']['money']), intval($data['Yesterday_log']['money']));

    //今天和昨天 总收益比较
    $data['day_user_than'] = day_Yesterday_than(intval($data['day_log']['agent_earnings']), intval($data['Yesterday_log']['agent_earnings']));

    // 获取下级渠道充值比例
    $channel_z = " agent_id=" . $agent['id'];
    //今天
    $data['day_log_channel'] = agent_charge($where1 . " and " . $channel_z);
    //昨天
    $data['Yesterday_log_channel'] = agent_charge($where2 . " and " . $channel_z);
    //总数
    $data['total_log_channel'] = agent_charge($channel_z);
    //今天和昨天 子渠道比较
    $data['day_than_channel'] = day_Yesterday_than(intval($data['day_log_channel']['money']), intval($data['Yesterday_log_channel']['money']));
    //今天和昨天 总收益比较
    $data['day_ordersum_than'] = day_Yesterday_than(intval($data['day_log_channel']['earnings']), intval($data['Yesterday_log_channel']['earnings']));


    return $data;
}

//代理注册统计
function admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday, $agent)
{

    $where1 = " addtime >=" . $beginToday . " and addtime <" . $endToday;
    $where2 = " addtime >=" . $beginYesterday . " and addtime <" . $endYesterday;

    //当前渠道邀请注册人数
    $where = " agent_id = ".$agent['id']." and subordinate=0";
    //今天
    $data['day_agent'] = agent_registered($where1 . " and " . $where);
    //昨天
    $data['Yesterday_agent'] = agent_registered($where2 . " and " . $where);
    //总数
    $data['total_agent'] = agent_registered($where);
    //今天和昨天 代理比较
    $data['agent_registered_than'] = day_Yesterday_than(intval($data['day_agent']), intval($data['Yesterday_agent']));

    // 下级渠道邀请注册人数
    $where_channel = " agent_id = ".$agent['id'];
    //今天
    $data['day_agent_channel'] = agent_registered($where1 . " and " . $where_channel);
    //昨天
    $data['Yesterday_agent_channel'] = agent_registered($where2 . " and " . $where_channel);
    //总数
    $data['total_agent_channel'] = agent_registered($where_channel);
    //今天和昨天 代理比较
    $data['agent_registered_than_channel'] = day_Yesterday_than(intval($data['day_agent_channel']), intval($data['Yesterday_agent_channel']));
    return $data;
}

/*获取渠道支付金额
 * */
function agent_charge($where)
{
    return db("agent_order_log")->field("sum(money) as money,sum(total_agent_money) as agent_earnings,sum(agent_money) as earnings")->where($where)->find();
}

/*
 * 获取代理注册统计
 * */
function agent_registered($where)
{
    return db('agent_register')->where($where)->order("date_time desc")->count();
}

//今日结算
function agent_settlement($beginToday, $endToday, $beginYesterday, $endYesterday, $agent)
{

    $where1 = "addtime >=" . $beginToday . " and addtime <" . $endToday;
    $where2 = "addtime >=" . $beginYesterday . " and addtime <" . $endYesterday;
    $agent_id = "agent_id=" . $agent['id'];
    //今天结算
    $data['day_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($where1 . " and (" . $agent_id . ")")->find();
    //昨天结算
    $data['Yesterday_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($where2 . " and  (" . $agent_id . ")")->find();
    //总数结算
    $data['total_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($agent_id)->find();
    return $data;
}

//今日和昨日比较
function day_Yesterday_than($day, $Yesterday)
{
    if ($day > $Yesterday) {
        $data['type'] = 1;
        if ($Yesterday != '0') {
            $data['than'] = round($day / $Yesterday * 100);
        } else {
            $data['than'] = $day;
        }
    } else {
        $data['type'] = 2;
        if ($day != '0') {
            $data['than'] = round($Yesterday / $day * 100);
        } else {
            $data['than'] = $Yesterday;
        }
    }
    return $data;

}

