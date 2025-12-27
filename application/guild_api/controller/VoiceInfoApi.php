<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/12/21
 * Time: 4:15 下午
 * Name: 房间信息
 */
namespace app\guild_api\controller;

use think\Controller;
use think\Db;
use think\config;
class VoiceInfoApi extends Base
{
    //房间信息 房主信息 房间总数据
    public function index(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $config = load_cache('config');
        //$user_info['id'] = $user_info['guild_id'];
        //房间信息 实时在线人数:online_number
        $voice = db('voice')->field('id,title,avatar,user_id,create_time,online_number')->find($id);
        if(!$voice){
            $result['code'] = 0;
            $result['msg'] = '房间信息错误';
            return_json_encode($result);
        }

        //房主信息
        $where = 'j.status = 1 and j.guild_id = '.$user_info['guild_id'].' and j.user_id = '.$voice['user_id'];
        $join_info = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->field('u.id,u.user_nickname,u.avatar,u.mobile,j.create_time')
            ->where($where)
            ->find();

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

        $coin_where= 'voice_user_id = '.$voice['user_id'].' and gift_type <3';

        //今日流水
        $day_total = db('user_gift_log') ->where($coin_where.' and create_time >='.$day_start_time)->sum('gift_coin');
        //昨日流水
        $yesterday_total = db('user_gift_log') ->where($coin_where.' and create_time >='.$yesterday_start_time.' and create_time<='.$yesterday_end_time)->sum('gift_coin');
        //本周流水
        $week_total = db('user_gift_log') ->where($coin_where.' and create_time >='.$week_start_time)->sum('gift_coin');
        //上周流水
        $last_week_total = db('user_gift_log') ->where($coin_where.' and create_time >='.$last_week_start_time.' and create_time<='.$last_week_end_time)->sum('gift_coin');
        // 本月流水
        $month_total = db('user_gift_log') ->where($coin_where.' and create_time >='.$month_start_time)->sum('gift_coin');
        //总流水
        $total = db('user_gift_log') ->where($coin_where)->sum('gift_coin');

        $income = [
            'total'=>$total,
            'day_total'=>$day_total,
            'yesterday_total'=>$yesterday_total,
            'week_total'=>$week_total,
            'last_week_total'=>$last_week_total,
            'month_total'=>$month_total,
        ];

        $result['income'] = $income;
        $result['currency_name'] = $config['currency_name'];
        $result['voice'] = $voice;
        $result['join_info'] = $join_info;
        return_json_encode($result);
    }

    public function get_chart(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $type = trim(input('type'));// 1今日 2昨日 3最近7天
        $startTime = trim(input('startTime'));// 开始时间
        $endTime = trim(input('endTime'));// 结束时间
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        //$user_info['id'] = $user_info['guild_id'];
        //房间信息 实时在线人数:online_number
        $voice = db('voice')->field('id,title,avatar,user_id,create_time,online_number')->find($id);
        if(!$voice){
            $result['code'] = 0;
            $result['msg'] = '房间信息错误';
            return_json_encode($result);
        }
        $where = 'voice_user_id = '.$voice['user_id'];
        $xData = [];
        $date = [];
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
            $where .= ' and create_time >= '.$startTime.' and create_time < '.$endTime;
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            if($type==1){
                //今天
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and create_time >= '.$time;
            }else if($type==2){
                //昨天
                $yesterday = $time-8600;
                $xData = [date('m-d',$yesterday)];
                $date = [date('Y-m-d',$yesterday)];
                $where .= ' and create_time >= '.$yesterday.' and create_time < '.$time;
            }else if($type==3){
                //7天内
                $start_day = $time - (86400*7);
                for ($i=6;$i>-1;$i--){
                    $m = date('m-d',$time - (86400 *$i));
                    $y = date('Y-m-d',$time - (86400 *$i));
                    array_push($xData,$m);
                    array_push($date,$y);
                }
                $where .= ' and create_time >= '.$start_day.' and create_time < '.NOW_TIME;
            }else{
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and create_time >= '.$time;
            }
        }

        $list = db('user_gift_log')
            ->where($where)
            ->field('sum(gift_coin) as total,create_time,DATE_FORMAT(FROM_UNIXTIME(create_time),"%Y-%m-%d") as format_time')
            ->group('format_time')
            ->select();

        $income_list = [];
        if($list && $date){
            foreach ($date as $v){
                $total_coin = 0;
                foreach ($list as $key=>$val){
                    if($val['format_time']==$v){
                        $total_coin = $val['total'];
                    }
                }
                array_push($income_list,$total_coin);
            }
        }
        //时间
        $result['xData'] = $xData;
        $result['income_list'] = $income_list;
        return_json_encode($result);
    }

    public function get_chart_pie(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $id = intval(input('id'));
        $user_info = $this->check_token($uid,$token);
        $voice = db('voice')->field('id,title,avatar,user_id,create_time,online_number')->find($id);
        if(!$voice){
            $result['code'] = 0;
            $result['msg'] = '房间信息错误';
            return_json_encode($result);
        }

        //收益数 total_coin 订单数 num
        $where = 's.voice_id = '.$voice['user_id'];
        $list_sex = db('voice_collect')
            ->alias('s')
            ->join('user u','u.id=s.user_id')
            ->field('count(u.id) as num,u.sex')
            ->where($where)
            ->group('u.sex')
            ->select();

        $list = db('voice_collect')
            ->alias('s')
            ->join('user u','u.id=s.user_id')
            ->field('u.sex,u.age')
            ->where($where)
            //->group('s.voice_id')
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

    public function get_list(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $user_id = intval(input('user_id'));
        $to_user_id = intval(input('to_user_id'));
        $startTime = trim(input('start_time'));// 开始时间
        $endTime = trim(input('end_time'));// 结束时间
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        //房间信息 实时在线人数:online_number
        $voice = db('voice')->field('id,title,avatar,user_id,create_time,online_number')->find($id);
        if(!$voice){
            $result['code'] = 0;
            $result['msg'] = '房间信息错误';
            return_json_encode($result);
        }
        $where = 'l.voice_user_id = '.$voice['user_id'];

        if($startTime && $endTime){
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $where .= ' and l.create_time >= '.$startTime.' and l.create_time < '.$endTime;
        }
        if($user_id){
            $where .= ' and l.user_id = '.$user_id;
        }
        if($to_user_id){
            $where .= ' and l.to_user_id = '.$to_user_id;
        }

        $list = db('user_gift_log')->alias("l")
            ->where($where)
            ->join("user u","u.id = l.user_id","left")
            ->join("user s","s.id = l.to_user_id","left")
            ->field('u.user_nickname as uname,s.user_nickname as sname,l.id,l.user_id,l.to_user_id,l.gift_name,l.gift_coin,l.gift_count,l.gift_total,l.create_time')
            ->order('l.create_time desc')
            ->page($page,$limit)
            ->select();
        $list = $list->toarray();
        foreach ($list as &$v){
            $v['content'] = "赠送礼物".$v['gift_name']." x".$v['gift_count'];
            $v['coin'] = $v['gift_coin'];
        }
        $total = db('user_gift_log')->alias("l")->where($where)->count();
        $gift_coin = db('user_gift_log')->alias("l")->where($where)->sum("gift_coin");
        $result['gift_coin'] = $gift_coin;
        $result['total'] = $total;
        $result['list'] = $list;
        return_json_encode($result);
    }
}