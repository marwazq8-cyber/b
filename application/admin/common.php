<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 8:59
 */

/*
*  首页统计数据
* */
function admin_index_list()
{
    //获取当天
    $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
    //获取昨天
    $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
    $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
    //支付
    $data['charge'] = admin_index_charge($beginToday, $endToday, $beginYesterday, $endYesterday);
    //注册
    $data['registered'] = admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday);
    //消费和收益
    $data['consumption'] = admin_index_consumption($beginToday, $endToday, $beginYesterday, $endYesterday);
    //审核
    $data['audit'] = admin_index_audit();

    //充值类型统计
    $data['charge_type'] = admin_index_charge_type($beginToday, $endToday, $beginYesterday, $endYesterday);
    return $data;
}

//充值类型统计
function admin_index_charge_type($beginToday, $endToday, $beginYesterday, $endYesterday)
{

    $where1 = "addtime >=" . $beginToday . " and addtime <" . $endToday . " and status=1";
    $where2 = "addtime >=" . $beginYesterday . " and addtime <" . $endYesterday . " and status=1";

    //ios 和 android
    //今天
    $data['android_day_log'] = db("user_charge_log")->where($where1 . " and os='android'")->sum("money");
    $data['ios_day_log'] = db("user_charge_log")->where($where1 . " and os='ios'")->sum("money");

    //昨天
    $data['android_Yesterday_log'] = db("user_charge_log")->where($where2 . " and os='android'")->sum("money");
    $data['ios_Yesterday_log'] = db("user_charge_log")->where($where2 . " and os='ios'")->sum("money");

    //总数
    $data['android_total_log'] = db("user_charge_log")->where("status=1 and os='android'")->sum("money");
    $data['ios_total_log'] = db("user_charge_log")->where("status=1 and os='ios'")->sum("money");

    if ($data['android_total_log'] && $data['android_total_log'] > 10000) {
        $data['android_total_log'] = round($data['android_total_log'] / 10000, 2) . lang('万');
    }
    if ($data['ios_total_log'] && $data['ios_total_log'] > 10000) {
        $data['ios_total_log'] = round($data['ios_total_log'] / 10000, 2) . lang('万');
    }
    return $data;
}

//充值统计
function admin_index_charge($beginToday, $endToday, $beginYesterday, $endYesterday)
{

    $where1 = "addtime >=" . $beginToday . " and addtime <" . $endToday;
    $where2 = "addtime >=" . $beginYesterday . " and addtime <" . $endYesterday;
    //今天
    $data['day_log'] = db("user_charge_log")->field("sum(money) as money,count(id) as ordersum")->where($where1 . " and status=1")->find();
    $data['user_day'] = db("user_charge_log")->where($where1 . " and status=1")->group("uid")->count();
    //昨天
    $data['Yesterday_log'] = db("user_charge_log")->field("sum(money) as money,count(id) as ordersum")->where($where2 . " and status=1")->find();
    $data['user_Yesterday'] = db("user_charge_log")->where($where2 . " and status=1")->group("uid")->count();
    //总数
    $data['total_log'] = db("user_charge_log")->field("sum(money) as money,count(id) as ordersum")->where("status=1")->find();
    $data['user_total'] = db("user_charge_log")->where("status=1")->group("uid")->count();


    //今天和昨天 支付金额比较
    $data['day_than'] = day_Yesterday_than(intval($data['day_log']['money']), intval($data['Yesterday_log']['money']));

    //今天和昨天 支付订单比较
    $data['day_ordersum_than'] = day_Yesterday_than(intval($data['day_log']['ordersum']), intval($data['Yesterday_log']['ordersum']));

    //今天和昨天 支付人数比较
    $data['day_user_than'] = day_Yesterday_than(intval($data['user_day']), intval($data['user_Yesterday']));

    if ($data['total_log'] && $data['total_log']['ordersum'] > 10000) {
        $data['total_log']['ordersum'] = round($data['total_log']['ordersum'] / 10000, 2) . lang('万');
    }
    if ($data['total_log'] && $data['total_log']['money'] > 10000) {
        $data['total_log']['money'] = round($data['total_log']['money'] / 10000, 2) . lang('万');
    }
    if ($data['user_total'] && $data['user_total'] > 10000) {
        $data['user_total'] = round($data['user_total'] / 10000, 2) . lang('万');
    }
    return $data;
}

//注册统计
function admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday)
{

    $where1 = "create_time >=" . $beginToday . " and create_time <" . $endToday;
    $where2 = "create_time >=" . $beginYesterday . " and create_time <" . $endYesterday;
    //今天
    $data['day_user'] = db("user")->where($where1)->count();
    //昨天
    $data['Yesterday_user'] = db("user")->where($where2)->count();
    //总数
    $data['total_user'] = db("user")->count();
    //统计代理
    //今天
    $data['day_agent'] = db("user")->where($where1 . " and link_id !='0'")->count();
    //昨天
    $data['Yesterday_agent'] = db("user")->where($where2 . " and link_id !='0'")->count();
    //总数
    $data['total_agent'] = db("user")->where("link_id !='0'")->count();
    //邀请统计cmf_invite_record
    //今天
    $data['day_invitation'] = db("user")->alias("u")->join("invite_record i", "i.user_id=u.id")->where("u.create_time >=" . $beginToday . " and u.create_time <" . $endToday)->count();
    //昨天
    $data['Yesterday_invitation'] = db("user")->alias("u")->join("invite_record i", "i.user_id=u.id")->where("u.create_time >=" . $beginYesterday . " and u.create_time <" . $endYesterday)->count();
    //总数
    $data['total_invitation'] = db("user")->alias("u")->join("invite_record i", "i.user_id=u.id")->count();

    //今天和昨天 注册比较
    $data['day_registered_than'] = day_Yesterday_than(intval($data['day_user']), intval($data['Yesterday_user']));
    //今天和昨天 代理比较
    $data['agent_registered_than'] = day_Yesterday_than(intval($data['day_agent']), intval($data['Yesterday_agent']));
    //今天和昨天 邀请比较
    $data['invitation_registered_than'] = day_Yesterday_than(intval($data['day_invitation']), intval($data['Yesterday_invitation']));

    if ($data['total_user'] && $data['total_user'] > 10000) {
        $data['total_user'] = round($data['total_user'] / 10000, 2) . lang('万');
    }
    if ($data['total_agent'] && $data['total_agent'] > 10000) {
        $data['total_agent'] = round($data['total_agent'] / 10000, 2) . lang('万');
    }


    return $data;
}

//今日消费和收益统计
function admin_index_consumption($beginToday, $endToday, $beginYesterday, $endYesterday)
{
    $where1 = "create_time >=" . $beginToday . " and create_time <" . $endToday;
    $where2 = "create_time >=" . $beginYesterday . " and create_time <" . $endYesterday;
    //今天
    $data['day_consumption'] = db("user_consume_log")->field("sum(coin) as coin,sum(profit) as profit")->where($where1)->find();
    //昨天
    $data['Yesterday_consumption'] = db("user_consume_log")->field("sum(coin) as coin,sum(profit) as profit")->where($where2)->find();
    //总数
    $data['total_consumption'] = db("user_consume_log")->field("sum(coin) as coin,sum(profit) as profit")->find();

    //提现记录统计
    //今天
    $data['day_withdrawal'] = db("user_cash_record")->field("sum(income) as income")->where($where1 . " and status !=2")->find();
    //昨天
    $data['Yesterday_withdrawal'] = db("user_cash_record")->field("sum(income) as income")->where($where2 . " and status !=2")->find();
    //总数
    $data['total_withdrawal'] = db("user_cash_record")->field("sum(income) as income")->find();

    //今天和昨天 消费比较
    $data['day_consumption_coin_than'] = day_Yesterday_than(intval($data['day_consumption']['coin']), intval($data['Yesterday_consumption']['coin']));
    //今天和昨天 收益比较
    $data['day_consumption_profit_than'] = day_Yesterday_than(intval($data['day_consumption']['profit']), intval($data['Yesterday_consumption']['profit']));
    //今天和昨天 提现比较
    $data['day_withdrawal_than'] = day_Yesterday_than(intval($data['day_withdrawal']['income']), intval($data['Yesterday_withdrawal']['income']));


    if ($data['day_consumption'] && $data['day_consumption']['coin'] > 10000) {
        $data['day_consumption']['coin'] = round($data['day_consumption']['coin'] / 10000, 2) . lang('万');
    }
    if ($data['day_consumption'] && $data['day_consumption']['profit'] > 10000) {
        $data['day_consumption']['profit'] = round($data['day_consumption']['profit'] / 10000, 2) . lang('万');
    }
    if ($data['Yesterday_consumption'] && $data['Yesterday_consumption']['coin'] > 10000) {
        $data['Yesterday_consumption']['coin'] = round($data['Yesterday_consumption']['coin'] / 10000, 2) . lang('万');
    }
    if ($data['Yesterday_consumption'] && $data['Yesterday_consumption']['profit'] > 10000) {
        $data['Yesterday_consumption']['profit'] = round($data['Yesterday_consumption']['profit'] / 10000, 2) . lang('万');
    }

    if ($data['total_withdrawal'] && $data['total_withdrawal']['income'] > 10000) {
        $data['total_withdrawal']['income'] = round($data['total_withdrawal']['income'] / 10000, 2) . lang('万');
    }
    if ($data['total_consumption'] && $data['total_consumption']['coin'] > 10000) {

        $data['total_consumption']['coin'] = $data['total_consumption']['coin'] > 100000000 ? round($data['total_consumption']['coin'] / 100000000, 2) . lang('亿') : round($data['total_consumption']['coin'] / 10000, 2) . lang('万');
    }
    if ($data['total_consumption'] && $data['total_consumption']['profit'] > 10000) {
        $data['total_consumption']['profit'] = $data['total_consumption']['profit'] > 100000000 ? round($data['total_consumption']['profit'] / 100000000, 2) . lang('亿') : round($data['total_consumption']['profit'] / 10000, 2) . lang('万');

    }
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

//首页审核统计
function admin_index_audit()
{

    //视频审核统计
    $data['video_audit'] = db("user_video")->field("sum(CASE WHEN type=0 THEN 1 ELSE 0 END) as review,sum(CASE WHEN type=1 THEN 1 ELSE 0 END) as through,sum(CASE WHEN type=2 THEN 1 ELSE 0 END) as refused,count(id) as countid")->find();
    //私照审核统计
    $data['private_photos'] = db("user_pictures")->field("sum(CASE WHEN status=0 THEN 1 ELSE 0 END) as review,sum(CASE WHEN status=1 THEN 1 ELSE 0 END) as through,sum(CASE WHEN status=2 THEN 1 ELSE 0 END) as refused,count(id) as countid")->find();
    //封面图审核统计
    $data['private_img'] = db("user_img")->field("sum(CASE WHEN status=0 THEN 1 ELSE 0 END) as review,sum(CASE WHEN status=1 THEN 1 ELSE 0 END) as through,sum(CASE WHEN status=2 THEN 1 ELSE 0 END) as refused,count(id) as countid")->find();
    //信息认证审核统计
    $data['auth_record'] = db("auth_form_record")->field("sum(CASE WHEN status=0 THEN 1 ELSE 0 END) as review,sum(CASE WHEN status=1 THEN 1 ELSE 0 END) as through,sum(CASE WHEN status=2 THEN 1 ELSE 0 END) as refused,count(id) as countid")->find();


    return $data;
}

/**
 * 今日、昨日、本周、上周 处理
 * start_time开始时间
 * end_time 结束时间 + 1(查询判断需要小于号)
 * */
function select_date_type($date_type): array
{
    $data = array();
    switch ($date_type) {
        case 'day':
            $data['start_time'] = strtotime(date('Y-m-d'));
            $data['end_time'] = strtotime("+1 day");
            break;
        case 'yesterday':
            $data['end_time'] = strtotime(date('Y-m-d'));
            $data['start_time'] = $data['end_time'] - 24 * 60 * 60;
            break;
        case 'week':
            $data['start_time'] = strtotime('this week monday');
            $data['end_time'] = strtotime('this week sunday 23:59:59') + 1;
            break;
        default:
            $data['start_time'] = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
            $data['end_time'] = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y')) + 1;
    }
    return $data;
}