<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/11/30
 * Time: 10:21 上午
 * Name:
 */
namespace app\guild_api\controller;

use think\Controller;
use think\Db;
use think\config;
use think\helper\Str;

class PlayerApi extends Base
{
    //数据概览
    public function index(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];

        $config = load_cache('config');
        // 开关 开启公会私聊礼物 开启公会私聊消息 开启公会视频通话 开启公会语音通话
        $switch = array(
            'guild_voice_open' => intval($config['guild_voice_open']),
            'guild_player_open' => intval($config['guild_player_open']),
            'guild_chat_gift_open' => intval($config['guild_chat_gift_open']),
            'guild_chat_news_open' => intval($config['guild_chat_news_open']),
            'guild_video_call_open' => intval($config['guild_video_call_open']),
            'guild_audio_call_open' => intval($config['guild_audio_call_open'])
        );

        //累计总金额:total_income 累计订单金额:player_income
        //私聊礼物总收益:gift_income 私聊消息总收益:chat_income 视频通话总收益:video_income 语音通话总收益:audio_income
        $income_where = " u.is_player=1";
        if ($switch['guild_player_open'] != 1) {
            $income_where .= " and l.type != 2";
        }
        if ($switch['guild_chat_news_open'] != 1) {
            $income_where .= " and l.type !=3";
        }

        if ($switch['guild_audio_call_open'] != 1) {
            $income_where .= " and l.type !=4";
        }
        if ($switch['guild_video_call_open'] != 1) {
            $income_where .= " and l.type !=5";
        }
        $income = db('guild_log')
            ->alias('l')
            ->join('user u','u.id = l.user_id')
            ->field('sum(CASE WHEN u.is_player=1 and l.type=1 THEN user_consumption ELSE 0 END) as gift_income, sum(CASE WHEN l.type=2 THEN user_consumption ELSE 0 END) as player_income,sum(CASE WHEN u.is_player=1 and l.type=3 THEN user_consumption ELSE 0 END) as chat_income,sum(CASE WHEN u.is_player=1 and l.type=4 THEN user_consumption ELSE 0 END) as audio_income,sum(CASE WHEN u.is_player=1 and type=5 THEN user_consumption ELSE 0 END) as video_income,sum(CASE WHEN '.$income_where.' THEN user_consumption ELSE 0 END) as total_income')
            ->where('l.guild_id = '.$user_info['id'])
            ->find();
        //累计认证大神
        $player_num = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->where('j.status = 1 and u.is_player = 1 and j.guild_id = '.$user_info['id'])
            ->count();
        //累计订单量
        $player_order_num = db('guild_join')
            ->alias('j')
            ->join('skills_order s','s.touid=j.user_id')
            ->where('j.status = 1 and j.guild_id = '.$user_info['id'])
            ->count();



        $chat_gift_income = db('guild_join')
            ->alias('g')
            ->join('user_gift_log l','g.user_id=l.to_user_id')
            ->join('user u','u.id=g.user_id')
            ->where('g.status = 1 and u.is_player = 1 and l.type = 3 and g.guild_id = '.$user_info['id'])
            ->sum('l.gift_coin');

        $income['switch_list'] = $switch;
        $income['player_num'] = $player_num;
        $income['player_order_num'] = $player_order_num; //
        $income['chat_gift_income'] = $chat_gift_income; // 私聊礼物总收益

        $result['data']['info'] = $income;
        return_json_encode($result);
    }
    // 公会成员流水
    public function get_stream_list(){
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);

        $user_id = intval(input('title'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $status = intval(input('status')); // 0当日 1本周 2本月 3时间戳查询
        if($start_time && $end_time){
            $status = 3;
        }
        $time = NOW_TIME;
        switch ($status){
            case 0:
                $start_time = strtotime(date('Y-m-d'));
                $end_time = $start_time + 24*60*60;
                break;
            case 1:
                //本周一
                $start_time = strtotime(date('Y-m-d',strtotime("last Sunday+1days")));
                $end_time = $time;
                break;
            case 2:
                $start_time = strtotime(date('Y-m-01'));
                $end_time = $time;
                break;
            default:
                $start_time = strtotime($start_time);
                $end_time = strtotime($end_time);
        }


        $user_info['id'] = $user_info['guild_id'];

        $where = 'j.status = 1 and j.guild_id = '.$user_info['id'];

        $where .= $user_id ? ' and j.user_id = '.$user_id : '';
        $where1 ="u.id=l.user_id";
        $where1 .= $start_time ? ' and l.create_time >= ' . $start_time : '';
        $where1 .= $end_time ? ' and l.create_time <= '.$end_time : '';

        $field='j.user_id,u.user_nickname,u.avatar,sum(l.coin) as coin';
        $list = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->join('user_consume_log l',$where1,'left')
            ->field($field)
            ->group("u.id")
            ->order("coin desc,u.id desc")
            ->where($where)
            ->page($page,$limit)
            ->select();

        $total = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->field('j.id')
            ->where($where)->count();

        $result['data']['list'] = $list;
        $result['data']['total'] = $total;
        return_json_encode($result);
    }
    /*
     * 订单走势*/
    public function get_chart(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $type = trim(input('type'));// 1今日 2昨日 3最近7天
        $startTime = trim(input('startTime'));// 开始时间
        $endTime = trim(input('endTime'));// 结束时间
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $xData = [];
        $date = [];
        $where = 'g.status = 1 and g.guild_id = '.$user_info['guild_id'];
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
            $where .= ' and s.addtime >= '.$startTime.' and s.addtime <= '.$endTime;
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            if($type==1){
                //今天
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and s.addtime >= '.$time;
            }else if($type==2){
                //昨天
                $yesterday = $time-8600;
                $xData = [date('m-d',$yesterday)];
                $date = [date('Y-m-d',$yesterday)];
                $where .= ' and s.addtime >= '.$yesterday.' and s.addtime < '.$time;
            }else if($type==3){
                //7天内
                $start_day = $time - (86400*7);
                for ($i=6;$i>-1;$i--){
                    $m = date('m-d',$time - (86400 *$i));
                    $y = date('Y-m-d',$time - (86400 *$i));
                    array_push($xData,$m);
                    array_push($date,$y);
                }
                $where .= ' and s.addtime >= '.$start_day.' and s.addtime < '.NOW_TIME;
            }else{
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and s.addtime >= '.$time;
            }
        }
        //收益数 total_coin 订单数 num
        $list = db('skills_order')
            ->alias('s')
            ->join('guild_join g','s.touid=g.user_id')
            //->join('skills_order s','s.touid=g.user_id')
            ->field('s.touid,count(s.id) as num,sum(s.total_coin) as total_coin,s.addtime,DATE_FORMAT(FROM_UNIXTIME(s.addtime),"%Y-%m-%d") as format_time')
            ->where($where)
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
                        //unset($list[$key]);
                    }
                }
                array_push($income_list,$total_coin);
                array_push($order_list,$num);
            }
        }

        $game_order_list = db('skills_order')
            ->alias('s')
            ->join('play_game p','p.id=s.game_id')
            ->join('guild_join g','g.user_id=s.touid')
            ->field('s.game_id,p.name as game_name,DATE_FORMAT(FROM_UNIXTIME(s.addtime),"%Y-%m-%d") as format_time')
            ->where($where)
            ->select();
        if (is_object($game_order_list)) {
            $game_order_list = json_decode( json_encode($game_order_list),true);
        }
        $game_list = array_column($game_order_list,'game_name','game_id');
        $game_array = [];
        foreach ($game_list as $k=>$v){
            $data = ['name'=>$v,'data'=>[]];
            foreach ($date as $k1=>$v1){
                $num = 0;
                foreach ($game_order_list as $k2=>$v2){
                    if($k == $v2['game_id'] && $v1==$v2['format_time']){
                        $num ++;
                    }
                }
                array_push($data['data'],$num);
            }
            $game_array[] = $data;
        }

        //时间
        $result['xData'] = $xData;
        $result['income_list'] = $income_list;
        $result['order_list'] = $order_list;
        $result['game_array'] = $game_array;
        return_json_encode($result);
    }

    //排行
    public function get_chart_ranging(){
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
        $rank_where = 'g.status = 1 and g.guild_id = '.$user_info['guild_id'];
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
            $rank_where .= ' and s.addtime >= '.$startTime.' and s.addtime < '.$endTime;
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            if($type==1){
                $rank_where .= ' and s.addtime >= '.$time;
            }else if($type==2){
                //昨天
                $yesterday = $time-8600;
                $rank_where .= ' and s.addtime >= '.$yesterday.' and s.addtime < '.$time;
            }else if($type==3){
                //7天内
                $start_day = $time - (86400*7);
                $rank_where .= ' and s.addtime >= '.$start_day.' and s.addtime < '.NOW_TIME;
            }else{
                //$rank_where .= ' and s.create_time >= '.$time;
                //$where .= ' and v.addtime >= '.$time;
                //$where_online .= ' and v.create_time >= '.$time;
            }
        }

        //接单收益排行
        $order_income_ranking = db('guild_join')
            ->alias('g')
            ->join('skills_order s','s.touid=g.user_id')
            ->join('user u','u.id=g.user_id')
            ->field('s.touid,sum(s.total_coin) as total_coin,u.user_nickname,u.avatar')
            ->where($rank_where)
            ->group('s.touid')
            ->order('total_coin desc')
            ->limit(10)
            ->select();

        //游戏下单排行
        $order_game_ranking = db('guild_join')
            ->alias('g')
            ->join('skills_order s','s.touid=g.user_id')
            ->join('play_game p','p.id=s.game_id')
            ->field('count(s.id) as num,p.name,p.img')
            ->where($rank_where)
            ->group('s.game_id')
            ->order('num desc')
            ->limit(10)
            ->select();

        //累计接单数量排行
        $order_num_ranking = db('guild_join')
            ->alias('g')
            ->join('skills_order s','s.touid=g.user_id')
            ->join('user u','u.id=g.user_id')
            ->field('s.touid,count(s.id) as num,u.user_nickname,u.avatar')
            ->where($rank_where)
            ->group('s.touid')
            ->order('num desc')
            ->limit(10)
            ->select();
        $result['order_income_ranking'] = $order_income_ranking;
        $result['order_game_ranking'] = $order_game_ranking;
        $result['order_num_ranking'] = $order_num_ranking;

        $ranking['income_ranking'] = $order_income_ranking;
        $ranking['collect_ranking'] = $order_game_ranking;
        $ranking['online_num_ranking'] = $order_num_ranking;
        $result['ranking'] = $ranking;
        return_json_encode($result);
    }

    /*
     * 公会陪玩成员
     * */
    public function get_player_list(){
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);

        $user_id = intval(input('title'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $status = intval(input('status'));

        $user_info['id'] = $user_info['guild_id'];

        $where = 'j.status = 1 and j.guild_id = '.$user_info['id'];

        $where .= $user_id ? ' and j.user_id = '.$user_id : '';
        $where .= $start_time ? ' and j.create_time >= '.strtotime($start_time) : '';
        $where .= $end_time ? ' and j.create_time <= '.strtotime($end_time) : '';
        $where .= $status > 0 ? ' and j.status = '.$status : '';

        $list = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->field('j.status,j.create_time,j.user_id,u.user_nickname,u.avatar,u.sex,u.mobile,u.mobile,u.is_player,u.income_player_total')
            ->where($where)
            ->page($page,$limit)
            ->select();

        $total = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->field('j.id')
            ->where($where)->count();
        if (is_object($list)) {
            $list = json_decode( json_encode($list),true);
        }
        if($list){
            $guild_user_id = array_column($list,'user_id');
            $guild_user_id = implode(',',$guild_user_id);
            //$total = db('guild_join')->alias('j')->join('user u','u.id=j.user_id')->where($where)->count();
            //认证游戏名
            $auth_player = db('auth_player')
                ->alias('a')
                ->join('play_game g','g.id=a.game_id')
                ->field('a.uid,g.name')
                ->where('a.uid in ('.$guild_user_id.')')
                ->select();
            //交易总金额 总接单量 私聊礼物总收益 视频通话 语音通话总收益
            $order = db('skills_order')
                ->field('touid,sum(total_coin) as total_coin,count(id) as num')
                ->where('status != 1 and status != 7 and status != 10 and touid in ('.$guild_user_id.')')
                ->group('touid')
                ->select();
            foreach ($list as &$v){
                $v['game_name'] = [];
                if($auth_player){
                    foreach ($auth_player as $k=>$v1){
                        if($v['user_id']==$v1['uid']){
                            $v['game_name'][] = $v1['name'];
                            unset($auth_player[$k]);
                        }
                    }
                }
                $v['create_time']= date('Y-m-d H:i',$v['create_time']);
                $v['game_name'] = implode('、',$v['game_name']);
                $v['order_num'] = 0;
                $v['total_coin'] = 0;
                if($order){
                    foreach ($order as $k=>$v2){
                        if($v['user_id']==$v2['touid']){
                            $v['order_num'] = $v2['num'];
                            $v['total_coin'] = $v2['total_coin'];
                            unset($order[$k]);
                        }
                    }
                }
            }
        }
        $result['data']['list'] = $list;
        $result['data']['total'] = $total;
        return_json_encode($result);
    }

    //用户详情
    public function get_player_info(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id')); //用户ID
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $where = 'j.status = 1 and j.guild_id = '.$user_info['id'].' and u.id = '.$id;
        $list = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->field('j.create_time,j.user_id,u.user_nickname,u.avatar,u.sex,u.mobile,u.mobile,u.is_player,u.income_player_total')
            ->where($where)
            ->find();

        //认证游戏名
        $auth_player = db('auth_player')
            ->alias('a')
            ->join('play_game g','g.id=a.game_id')
            ->field('a.uid,g.name')
            ->where('a.uid = '.$id)
            ->select();
        $game_name = [];
        if($auth_player){
            foreach ($auth_player as $k=>$v1){
                if($id==$v1['uid']){
                    $game_name[] = $v1['name'];
                    unset($auth_player[$k]);
                }
            }
        }
        $list['game_name'] = implode('、',$game_name);
        //交易总金额 总接单量 私聊礼物总收益 视频通话 语音通话总收益
        $order = db('skills_order')
            ->field('touid,sum(total_coin) as total_coin,count(id) as num')
            ->where('status != 1 and status != 7 and status != 10 and touid = '.$id)
            ->find();
        $list['total_num'] = $order['num'];
        //礼物收益
        $gift_coin = db('user_gift_log')->where('to_user_id = '.$id)->sum('profit');
        $list['total_coin'] = $order['total_coin'] + $gift_coin ;
        $list['gift_coin'] = $gift_coin;
        //付费用户数量
        $list['user_num'] = db('user_consume_log')->where('to_user_id = '.$id)->group('user_id')->count();
        $result['list'] = $list;
        $config = load_cache('config');
        // 开关 开启公会私聊礼物 开启公会私聊消息 开启公会视频通话 开启公会语音通话
        $switch = array(
            'guild_voice_open' => intval($config['guild_voice_open']),
            'guild_player_open' => intval($config['guild_player_open']),
            'guild_chat_gift_open' => intval($config['guild_chat_gift_open']),
            'guild_chat_news_open' => intval($config['guild_chat_news_open']),
            'guild_video_call_open' => intval($config['guild_video_call_open']),
            'guild_audio_call_open' => intval($config['guild_audio_call_open'])
        );
        $result['data'] = $switch;
        return_json_encode($result);
    }

    //用户订单明细
    public function get_player_order_list(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id')); //用户ID
        $limit = intval(input('limit'));
        $page = intval(input('page'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $status = intval(input('status'));
        $game_id = intval(input('game_id'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $where = 'o.touid = '.$id;
        if($start_time && $end_time){
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);
            $where .= ' and o.addtime >= '.$start_time.' and o.addtime <= '.$end_time;
        }
        if($status){
            $where .= ' and o.status = '.$status;
        }
        if($game_id){
            $where .= ' and o.game_id = '.$game_id;
        }
        $list = db('skills_order')
            ->alias('o')
            ->join('play_game g','g.id=o.game_id')
            ->join('user u','u.id=o.uid')
            ->field('o.*,g.name as game_name,u.user_nickname')
            ->where($where)
            ->order('addtime desc')
            ->page($page,$limit)
            ->select();
        $total = db('skills_order')
            ->alias('o')
            ->where($where)
            ->count();
        $result['list'] = $list;
        $result['total'] = $total;
        return_json_encode($result);
    }

    //用户订单走势
    public function get_player_info_chart(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $type = trim(input('type'));// 1今日 2昨日 3最近7天
        $id = intval(input('id'));
        $startTime = trim(input('startTime'));// 开始时间
        $endTime = trim(input('endTime'));// 结束时间
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $xData = [];
        $date = [];
        $where = 'g.status = 1 and s.touid = '.$id;
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
            //$endTime += 86399;
            $where .= ' and s.addtime >= '.$startTime.' and s.addtime <= '.$endTime;
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            if($type==1){
                //今天
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and s.addtime >= '.$time;
            }else if($type==2){
                //昨天
                $yesterday = $time-8600;
                $xData = [date('m-d',$yesterday)];
                $date = [date('Y-m-d',$yesterday)];
                $where .= ' and s.addtime >= '.$yesterday.' and s.addtime <= '.$time;
            }else if($type==3){
                //7天内
                $start_day = $time - (86400*7);
                for ($i=6;$i>-1;$i--){
                    $m = date('m-d',$time - (86400 *$i));
                    $y = date('Y-m-d',$time - (86400 *$i));
                    array_push($xData,$m);
                    array_push($date,$y);
                }
                $where .= ' and s.addtime >= '.$start_day.' and s.addtime <= '.NOW_TIME;
            }else{
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and s.addtime >= '.$time;
            }
        }
        //收益数 total_coin 订单数 num
        $list = db('guild_join')
            ->alias('g')
            ->join('skills_order s','s.touid=g.user_id')
            ->field('s.touid,count(s.id) as num,sum(s.total_coin) as total_coin,s.addtime,DATE_FORMAT(FROM_UNIXTIME(s.addtime),"%Y-%m-%d") as format_time')
            ->where($where)
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
                        //unset($list[$key]);
                    }
                }
                array_push($income_list,$total_coin);
                array_push($order_list,$num);
            }
        }

        $game_order_list = db('skills_order')
            ->alias('s')
            ->join('play_game p','p.id=s.game_id')
            ->join('guild_join g','g.user_id=s.touid')
            ->field('s.game_id,p.name as game_name,DATE_FORMAT(FROM_UNIXTIME(s.addtime),"%Y-%m-%d") as format_time')
            ->where($where)
            ->select();
        if (is_object($game_order_list)) {
            $game_order_list = json_decode( json_encode($game_order_list),true);
        }
        $game_list = array_column($game_order_list,'game_name','game_id');
        $game_array = [];
        foreach ($game_list as $k=>$v){
            $data = ['name'=>$v,'data'=>[]];
            foreach ($date as $k1=>$v1){
                $num = 0;
                foreach ($game_order_list as $k2=>$v2){
                    if($k == $v2['game_id'] && $v1==$v2['format_time']){
                        $num ++;
                    }
                }
                array_push($data['data'],$num);
            }
            $game_array[] = $data;
        }

        //时间
        $result['xData'] = $xData;
        $result['income_list'] = $income_list;
        $result['order_list'] = $order_list;
        $result['game_array'] = $game_array;
        //$result['game_name'] = $game_name;
        return_json_encode($result);
    }

    //性别/年龄比例
    public function get_chart_pie(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $id = intval(input('id'));
        $user_info = $this->check_token($uid,$token);
        //收益数 total_coin 订单数 num
        $where = 'g.guild_id = '.$user_info['guild_id'].' and s.touid = '.$id;
        $list_sex = db('skills_order')
            ->alias('s')
            ->join('guild_join g','s.touid=g.user_id')
            ->join('user u','u.id=s.uid')
            ->field('count(u.id) as num,u.sex')
            ->where($where)
            ->group('u.sex')
            ->select();
        $list = db('skills_order')
            ->alias('s')
            ->join('guild_join g','s.touid=g.user_id')
            ->join('user u','u.id=s.uid')
            ->field('u.sex,u.age')
            ->where($where)
            ->group('s.uid')
            ->select();
        //dump($list);
        //25岁以下,26-33岁,34-40岁,41-48岁,49岁以上
        $age = [['name'=>'25岁以下','value'=>0],['name'=>'26-33岁','value'=>0],['name'=>'34-40岁','value'=>0],['name'=>'41-48岁','value'=>0],['name'=>'49岁以上','value'=>0]];
        if($list){
            foreach ($age as &$val){
                foreach ($list as $k=>$v){
                    if($v['age']<=25 && $val['name']=='25岁以下'){
                        $val['value'] ++;
                        unset($list[$k]);
                    }else if($v['age']<=33 && $v['age']>=26 && $val['name']=='26-33岁'){
                        $val['value'] ++;
                        unset($list[$k]);
                    }else if($v['age']>=34 && $v['age']<=40 && $val['name']=='34-40岁'){
                        $val['value'] ++;
                        unset($list[$k]);
                    }else if($v['age']>=41 && $v['age']<=48 && $val['name']=='41-48岁'){
                        $val['value'] ++;
                        unset($list[$k]);
                    }else if($v['age']>=49 && $val['name']=='49岁以上'){
                        $val['value'] ++;
                        unset($list[$k]);
                    }
                }
            }
        }
        $all = 0;
        $man = 0;
        $woman = 0;
        if($list_sex){
            foreach ($list_sex as $v){
                $all += $v['num'];
                if($v['sex']==1){
                    $man += $v['num'];
                }else{
                    $woman += $v['num'];
                }
            }
        }

        if($man>0){
            $man = round($man/$all,2)*100;
        }
        if($woman>0){
            $woman = round($woman/$all,2)*100;
        }
        $sex = [
            'man'=>$man,
            'woman'=>$woman
        ];
        //dump($sex);die();
        $result['sex'] = $sex;
        $result['age'] = $age;
        return_json_encode($result);
    }

    //所有游戏
    public function get_player_game(){
        $result = array('code' => 1, 'msg' => '');
        $result['list'] = db('play_game')->select();
        return_json_encode($result);
    }
}