<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/12/9
 * Time: 5:04 下午
 * Name:
 */
namespace app\guild_api\controller;

use think\Controller;
use think\Db;
use think\config;
class VoiceApi extends Base
{
    //数据概览
    public function index(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $config = load_cache('config');
        // 开关 开启公会语音厅收益 开启公会陪玩约单 开启公会私聊礼物 开启公会私聊消息 开启公会视频通话 开启公会语音通话
        $switch = array(
            'guild_voice_open' => intval($config['guild_voice_open']),
            'guild_player_open' => intval($config['guild_player_open']),
            'guild_chat_gift_open' => intval($config['guild_chat_gift_open']),
            'guild_chat_news_open' => intval($config['guild_chat_news_open']),
            'guild_video_call_open' => intval($config['guild_video_call_open']),
            'guild_audio_call_open' => intval($config['guild_audio_call_open'])
        );

        $voice_income_where = "g.type=4 and g.gift_type <3";
        if ($switch['guild_voice_open'] != 1) {
            $voice_income_where .= " and g.type  != 4";
        }
        if ($switch['guild_chat_gift_open'] != 1) {
            $voice_income_where .= " and g.type !=3";
        }
        if ($switch['guild_video_call_open'] != 1 && $switch['guild_audio_call_open'] != 1) {
            $voice_income_where .= " and g.type !=5";
        }

        $where = 'j.status = 1 and j.guild_id = '.$user_info['id'];
        //累计房间数
        $num = db('guild_join')
            ->alias('j')
            ->join('voice v','v.user_id=j.user_id')
            ->where($where." and v.luck !=''")
            ->count();

        // 先构建一个子查询，找出满足guild_uid条件的所有user_id
        $subQuery = Db::name('voice')
            ->where('guild_uid',  $user_info['user_id'])
            ->field('user_id')
            ->buildSql();
        // 再将子查询作为一个新的表，与bogo_user_gift_log进行连接查询
        $total_income = Db::name('user_gift_log')
            ->alias('g')
            ->join([$subQuery => 'v'],'v.user_id = g.voice_user_id')
            ->where($voice_income_where)
            ->sum('g.gift_coin');

        //今日收益 今日有效 有收益的厅
        $dayTime = strtotime(date('Y-m-d',NOW_TIME));

        // 再将子查询作为一个新的表，与bogo_user_gift_log进行连接查询
        $day_income = Db::name('user_gift_log')
            ->alias('g')
            ->join([$subQuery => 'v'],'v.user_id = g.voice_user_id')
            ->where($voice_income_where.' and g.create_time >= '.$dayTime)
            ->sum('g.gift_coin');

        $day_count = db('guild_join')
            ->alias('j')
            ->join('voice v','v.user_id=j.user_id')
            ->join('user_gift_log g','g.voice_user_id = j.user_id')
            ->where($where.' and '.$voice_income_where)
            ->where('g.create_time >= '.$dayTime)
            ->group('g.voice_user_id')
            ->count();

        $result['num'] = $num;
        $result['total_income'] = $total_income;
        $result['day_income'] = $day_income;
        $result['day_count'] = $day_count;
        $result['switch_list'] = $switch;
        return_json_encode($result);
    }

    /*
     * 走势*/
    public function get_chart(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $type = trim(input('type'));// 1今日 2昨日 3最近7天
        $startTime = trim(input('startTime'));// 开始时间
        $endTime = trim(input('endTime'));// 结束时间
        $user_info = $this->check_token($uid,$token);
        //$user_info['id'] = $user_info['guild_id'];
        $xData = [];
        $date = [];
        $where = 'g.status = 1 and v.guild_uid = '.$user_info['user_id'];
        if($startTime && $endTime){
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $num = ($endTime - $startTime)/86400;
            for ($i=$num;$i>-1;$i--){
                $m = date('m-d',$endTime - (86400 *$i));
                $y = date('Y-m-d',$endTime - (86400 *$i));
                array_push($xData,$m);
                array_push($date,$y);
            }
            $where .= ' and s.create_time >= '.$startTime.' and s.create_time < '.$endTime;
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            if($type==1){
                //今天
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and s.create_time >= '.$time;
            }else if($type==2){
                //昨天
                $yesterday = $time-8600;
                $xData = [date('m-d',$yesterday)];
                $date = [date('Y-m-d',$yesterday)];
                $where .= ' and s.create_time >= '.$yesterday.' and s.create_time < '.$time;
            }else if($type==3){
                //7天内
                $start_day = $time - (86400*7);
                for ($i=6;$i>-1;$i--){
                    $m = date('m-d',$time - (86400 *$i));
                    $y = date('Y-m-d',$time - (86400 *$i));
                    array_push($xData,$m);
                    array_push($date,$y);
                }
                $where .= ' and s.create_time >= '.$start_day.' and s.create_time < '.NOW_TIME;
            }else{
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and s.create_time >= '.$time;
            }
        }

        //收益数 total_coin 订单数 num
        $list = db('guild_join')
            ->alias('g')
            ->join('user_gift_log s','s.voice_user_id=g.user_id')
            ->join('voice v','v.user_id=s.user_id')
            ->field('g.user_id,count(s.id) as num,sum(s.gift_coin) as total_coin,s.create_time,DATE_FORMAT(FROM_UNIXTIME(s.create_time),"%Y-%m-%d") as format_time')
            ->where($where." and  s.type  = 4  ")
            ->group('format_time')
            ->select();

        $income_list = [];
        $order_list = [];
        if($list && $date){
            foreach ($date as $v){
                $total_coin = 0;
                $num = 0;
                foreach ($list as $key=>$val){
                    if($val['format_time']==$v){
                        $total_coin = $val['total_coin'];
                        $num = $val['num'];
                        unset($list[$key]);
                    }
                }
                array_push($income_list,$total_coin);
                array_push($order_list,$num);
            }
        }

        //时间
        $result['xData'] = $xData;
        $result['income_list'] = $income_list;
        $result['order_list'] = $order_list;
        //$result['ranking'] = $ranking;
        return_json_encode($result);
    }

    //排行
    public function get_rank(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $type = trim(input('type'));// 1今日 2昨日 3最近7天
        $startTime = trim(input('startTime'));// 开始时间
        $endTime = trim(input('endTime'));// 结束时间
        $user_info = $this->check_token($uid,$token);
        //$user_info['id'] = $user_info['guild_id'];

        $rank_where = 'g.status = 1 and g.guild_id = '.$user_info['guild_id']." and s.type = 4 and s.gift_type <3";
        $where_online = 'g.status = 1 and g.guild_id = '.$user_info['guild_id'];
        $where = 'g.status = 1 and g.guild_id = '. $user_info['guild_id'];
        if($startTime && $endTime){
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $rank_where .= ' and s.create_time >= '.$startTime.' and s.create_time < '.$endTime;
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            if($type==1){
                $rank_where .= ' and s.create_time >= '.$time;
                $where .= ' and v.addtime >= '.$time;
            }else if($type==2){
                //昨天
                $yesterday = $time-8600;
                $rank_where .= ' and s.create_time >= '.$yesterday.' and s.create_time < '.$time;
                $where .= ' and v.addtime >= '.$yesterday.' and v.addtime < '.$time;
            }else if($type==3){
                //7天内
                $start_day = $time - (86400*7);
                $rank_where .= ' and s.create_time >= '.$start_day.' and s.create_time < '.NOW_TIME;
                $where .= ' and v.addtime >= '.$start_day.' and v.addtime < '.NOW_TIME;
            }else{
                //$rank_where .= ' and s.create_time >= '.$time;
                //$where .= ' and v.addtime >= '.$time;
                //$where_online .= ' and v.create_time >= '.$time;
            }
        }

        //累计收益值排行
        $income_ranking = db('guild_join')
            ->alias('g')
            ->join('voice v','v.user_id=g.user_id')
            ->join('user u','u.id=g.user_id')
            ->join('user_gift_log s','s.voice_user_id=g.user_id')
            ->field('u.id,sum(s.gift_coin) as total_coin,u.user_nickname,u.avatar')
            ->where($rank_where)
            ->group('s.voice_user_id')
            ->order('total_coin desc')
            ->limit(10)
            ->select();
        foreach ($income_ranking as &$v1){
            if($v1['total_coin']>10000){
                $v1['total_coin'] = round($v1['total_coin']/10000,2);
            }
        }

        //累计被收藏数排行
        $collect_ranking = db('guild_join')
            ->alias('g')
            ->join('voice_collect v','v.voice_id=g.user_id')
            ->join('user u','u.id=g.user_id')
            ->field('count(v.id) as num,u.user_nickname,u.avatar,u.id')
            ->where($where)
            ->group('v.voice_id')
            ->order('num desc')
            ->limit(10)
            ->select();
        foreach ($collect_ranking as &$v2){
            if($v2['num']>10000){
                $v2['num'] = round($v2['num']/10000,2);
            }
        }

        //在线人数排名排行
        $online_num_ranking = db('guild_join')
            ->alias('g')
            ->join('voice v','v.user_id=g.user_id')
            ->join('user u','u.id=g.user_id')
            ->field('u.id,v.online_count,u.user_nickname,u.avatar')
            ->where($where_online)
            ->group('v.user_id')
            ->order('online_count desc')
            ->limit(10)
            ->select();
        foreach ($online_num_ranking as &$v3){
            if($v3['online_count']>10000){
                $v3['online_count'] = round($v3['online_count']/10000,2);
            }
        }
        $ranking['income_ranking'] = $income_ranking;
        $ranking['collect_ranking'] = $collect_ranking;
        $ranking['online_num_ranking'] = $online_num_ranking;
        $result['ranking'] = $ranking;
        return_json_encode($result);
    }

    /**
     * 房间列表
     */
    public function get_list(){
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $user_id = $user_info['user_id'];
        $to_uid = intval(input('title'));
        $status = intval(input('status'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $sort = intval(input('sort'));
        $config = load_cache('config');
        // 开关 开启公会语音厅收益
        $switch = array(
            'guild_voice_open' => intval($config['guild_voice_open'])
        );
        $where = 'v.guild_uid = '.$user_id;
        $where .= $to_uid ? " and v.user_id=".$to_uid : '';
        $where .= $start_time ? ' and v.create_time >= '.strtotime($start_time) : '';
        $where .= $end_time ? ' and v.create_time <= '.strtotime($end_time) : '';
        $where .= $status ? " and v.status=".$status : '';

        $total=0; // 总数量
        $list_tow = array(); // 房间
        if($switch['guild_voice_open']){
            $list = db('voice')->alias('v')
                ->field("v.id,v.title,v.avatar as voice_avatar,v.coin_number,v.online_number,v.status,v.create_time,v.online_count,v.endtime,v.user_id")
                ->where($where)
                ->order('v.create_time desc')
                ->page($page,$limit)
                ->select();
            $list_tow = $list->toArray();

            //获取本周的起止时间
            $week_start_time=strtotime(date('Y-m-d', strtotime("this week Monday", NOW_TIME)));
            //获取上周的起止时间
            $last_week_start_time=strtotime(date('Y-m-d', strtotime("last week Monday", NOW_TIME)));
            $last_week_end_time=strtotime(date('Y-m-d', strtotime("last week Sunday", NOW_TIME))) + 24 * 3600 - 1;
            //获取本月的起止时间
            $month_start_time=strtotime(date('Y-m-01'));
            //获取今日的起止时间
            $day_start_time=strtotime(date('Y-m-d'));
            //获取昨日的起止时间
            $yesterday_start_time=strtotime(date('Y-m-d 00:00:00',strtotime('yesterday')));
            $yesterday_end_time=strtotime(date('Y-m-d 23:59:59',strtotime('yesterday')));
            //获取上月的起止时间
            $last_month_start_time = strtotime(date('Y-m-01', strtotime('last month')));
            foreach ($list_tow as &$v){
                //今日流水
                $v['day_coin'] = db('user_gift_log')->where('voice_user_id = '.$v['user_id'].' and create_time >= '.$day_start_time)->sum('gift_coin');
                //昨日流水
                $v['yesterday_coin'] = db('user_gift_log')->where('voice_user_id = '.$v['user_id'].' and create_time >= '.$yesterday_start_time.' and create_time <='.$yesterday_end_time)->sum('gift_coin');
                //本周流水
                $v['week_coin'] = db('user_gift_log')->where('voice_user_id = '.$v['user_id'].' and create_time >= '.$week_start_time)->sum('gift_coin');
                //上周流水
                $v['last_week_coin'] = db('user_gift_log')->where('voice_user_id = '.$v['user_id'].' and create_time >= '.$last_week_start_time.' and create_time <='.$last_week_end_time)->sum('gift_coin');
                // 本月流水
                $v['month_coin'] = db('user_gift_log')->where('voice_user_id = '.$v['user_id'].' and create_time >= '.$month_start_time)->sum('gift_coin');
                // 上月流水
                $v['last_month_coin'] = db('user_gift_log')->where('voice_user_id = '.$v['user_id'].' and create_time >= '.$last_month_start_time." and create_time <".$month_start_time)->sum('gift_coin');
            }

            $total = db('voice')->alias('v')->where($where)->count();
        }
        $result['list'] = $list_tow;
        $result['total'] = $total;
        $result['currency_name'] = $config['currency_name'];
        return_json_encode($result);
    }
}
