<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/11/25
 * Time: 9:23 上午
 * Name:
 */
namespace app\guild_api\controller;

use app\guild_api\model\GuildModel;
use think\Controller;
use think\Db;
use think\config;
use think\Model;

class InfoApi extends Base{
    // 获取申请退会列表
    public function quit_guild_list(){
        $result = array('code' => 1, 'msg' => '');

        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $guild_id = $user_info['guild_id'];

        $user_id = intval(input('user_id'));
        $nickname = trim(input('nickname'));
        $status = intval(input('status'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));

        $where = 'g.guild_join_status = 1 and g.guild_id = '.$guild_id;
        $where .= $user_id ? " and g.user_id=".$user_id : '';
        $where .= $nickname ? " and u.user_nickname like '%".$nickname."%'" : '';

        if ($status != -1) {
            $where .= ' and g.status = '.$status;
        }
        $where .= $start_time ? ' and g.create_time >= '.strtotime($start_time) : '';
        $where .= $end_time ? ' and g.create_time <= '.strtotime($end_time) : '';

        $list= db('guild_join_quit')
            ->alias('g')
            ->join('user u','u.id=g.user_id')
            ->field('g.*,u.user_nickname')
            ->where($where)
            ->order('g.create_time desc')
            ->page($page,$limit)
            ->select();
        $total= db('guild_join_quit')
            ->alias('g')
            ->join('user u','u.id=g.user_id')
            ->where($where)
            ->count();

        $result['data'] = $list;
        $result['total'] = $total;
        return_json_encode($result);
    }
    // 操作退会状态
    public function save_quit_guild_status(){
        $result = array('code' => 1, 'msg' => lang('Operation_successful'));
        $id = intval(input('id'));
        $status = intval(input('status')) == 1 ? 1 : 2;
        $explain = trim(input('explain'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);

        $guild_id = $user_info['guild_id'];

        $info = db('guild_join_quit')->where('guild_id = '.$guild_id.' and id = '.$id)->find();
        if($info){
            if($status == 1){
                $guild_join = db('guild_join')->where('id=' . $info['guild_join_id'])->find();
                if ($guild_join) {
                    // 删除公会列表
                    db('guild_join')->where('id=' . $guild_join['id'])->delete();
                    // 清除公会直播间
                    db('voice')->where('user_id=' . $guild_join['user_id']." and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid'=>'']);
                    db('user')->where('id=' . $guild_join['user_id'])->update(['guild_id'=> 0]);
                }
            }
            $update_data = array(
                'status' => $status,
                'explain' => $explain,
                'end_time' => NOW_TIME
            );
            db('guild_join_quit')->where('id=' . $info['id'])->update($update_data);
        }else{
            $result['code'] = 0;
            $result['msg'] = lang('data_error');
        }
        return_json_encode($result);
    }
    //首页信息
    public function get_index(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        //公会总人数
        $user_count = db('guild_join')->alias('g')->join('user u','g.user_id=u.id')->where('g.guild_id = '.$user_info['id'])->count();
        $result['data'] = array(
            'user_count' => $user_count
        );
        return_json_encode($result);
    }

    //陪玩订单走势
    public function get_player_chart(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $type = trim(input('type'));// 1今日 2昨日 3最近7天
        $startTime = trim(input('startTime'));// 开始时间
        $endTime = trim(input('endTime'));// 结束时间
        $user_info = $this->check_token($uid,$token);
        $user_id = $user_info['user_id'];
        $xData = [];
        $date = [];
        $where = 's.guild_uid = '.$user_id;

        if ($startTime && $endTime) {
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $num = ($endTime - $startTime)/86400;
            for ($i=$num;$i>-1;$i--){
                $m = date('m-d',$endTime - (86400 *$i));
                $y = date('Y-m-d',$endTime - (86400 *$i));
                array_push($xData,$m);
                array_push($date,$y);
            }
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            $startTime = $time;  //今天
            $endTime = NOW_TIME;  //今天
            if ($type==3) {
                //7天内
                $start_day = $time - (86400*7);
                for ($i=6;$i>-1;$i--){
                    $m = date('m-d',$time - (86400 *$i));
                    $y = date('Y-m-d',$time - (86400 *$i));
                    array_push($xData,$m);
                    array_push($date,$y);
                }
                $startTime = $start_day;
            }else{
                if($type==2){
                    //昨天
                    $yesterday = $time-8600;
                    $startTime = $yesterday;
                    $endTime = $time;
                }
                $xData = [date('m-d',$startTime)];
                $date = [date('Y-m-d',$startTime)];
            }
        }
        $where .= ' and s.addtime >= '.$startTime.' and s.addtime < '.$endTime;

        //收益数 total_coin 订单数 num
        $list = db('skills_order')->alias('s')
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

        //时间
        $result['xData'] = $xData;
        $result['income_list'] = $income_list;
        $result['order_list'] = $order_list;
        return_json_encode($result);
    }
    //语音厅走势
    public function get_voice_chart(){
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
            $where .= ' and v.create_time >= '.$startTime.' and v.create_time <= '.$endTime;
        }else{
            $time = strtotime(date('Y-m-d',NOW_TIME));
            if($type==1){
                //今天
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and v.create_time >= '.$time;
            }else if($type==2){
                //昨天
                $yesterday = $time-8600;
                $xData = [date('m-d',$yesterday)];
                $date = [date('Y-m-d',$yesterday)];
                $where .= ' and v.create_time >= '.$yesterday.' and v.create_time <= '.$time;
            }else if($type==3){
                //7天内
                $start_day = $time - (86400*7);
                for ($i=6;$i>-1;$i--){
                    $m = date('m-d',$time - (86400 *$i));
                    $y = date('Y-m-d',$time - (86400 *$i));
                    array_push($xData,$m);
                    array_push($date,$y);
                }
                $where .= ' and v.create_time >= '.$start_day.' and v.create_time < '.NOW_TIME;
            }else{
                $xData = [date('m-d',$time)];
                $date = [date('Y-m-d',$time)];
                $where .= ' and v.create_time >= '.$time;
            }
        }
        //房间数
        $voice = db('guild_join')
            ->alias('g')
            ->join('voice v','v.user_id=g.user_id')
            ->field('count(v.id) as num,v.create_time,DATE_FORMAT(FROM_UNIXTIME(v.create_time),"%Y-%m-%d") as format_time')
            ->where($where)
            ->group('format_time')
            ->select();

        //房间收益数  $where = 'g.guild_status = 1 and g.type  = 4 and g.guild_id = '.$user_info['id'];
        $list = db('user_gift_log')
            ->alias('v')
            ->join('voice i','i.user_id=v.voice_user_id')
            ->join('guild_join g','g.user_id=i.user_id')
            ->where($where." and v.guild_status = 1 and v.type  = 4 and v.guild_id = ".$user_info['guild_id'])
            ->field('sum(v.gift_coin) as total,v.create_time,DATE_FORMAT(FROM_UNIXTIME(v.create_time),"%Y-%m-%d") as format_time')
            ->group('format_time')
            ->select();

        $income_list = [];
        $order_list = [];

        if($date && ($voice || $list)){
            foreach ($date as $v){
                $total_coin = 0;
                $num = 0;
                foreach ($voice as $k1=>$v1){
                    if($v1['format_time']==$v){
                        $num = $v1['num'];
                        //unset($voice[$k1]);
                    }
                }
                array_push($order_list,$num);
                foreach ($list as $k=>$v2){
                    if($v2['format_time']==$v){
                        $total_coin = $v2['total'];
                        //unset($list[$k]);
                    }
                }
                array_push($income_list,$total_coin);
            }
        }
        //时间
        $result['xData'] = $xData;
        $result['income_list'] = $income_list;
        $result['order_list'] = $order_list;
        return_json_encode($result);
    }

    //待审核列表
    public function get_user_list(){
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $user_id = intval(input('user_id'));
        $nickname = trim(input('nickname'));
        $mobile = intval(input('mobile'));
        $is_talker = intval(input('is_talker'));
        $is_player = intval(input('is_player'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $where = 'j.status = 0 and j.guild_id = '.$user_info['id'];
        if($user_id){
            $where .= ' and j.user_id = '.$user_id;
        }
        if($nickname){
            $where .= ' and u.user_nickname like "%'.$nickname.'%"';
        }
        if($mobile){
            $where .= ' and u.mobile = '.$mobile;
        }
        if($is_talker>0){
            if($is_talker==2){
                $is_talker = 0;
            }
            $where .= ' and u.is_talker = '.$is_talker;
        }
        if($is_player>0){
            if($is_player==2){
                $is_player = 0;
            }
            $where .= ' and u.is_player = '.$is_player;
        }
        $list = db('guild_join')
            ->alias('j')
            ->join('user u','u.id=j.user_id')
            ->field('j.id,j.status,j.create_time,j.user_id,u.user_nickname,u.avatar,u.sex,u.mobile,u.mobile,u.is_player,u.is_talker')
            ->where($where)
            ->page($page,$limit)
            ->select();
        $total = 0;
        if($list){
            $total = db('guild_join')->alias('j')->join('user u','u.id=j.user_id')->where($where)->count();
        }
        $result['data']['list'] = $list;
        $result['data']['total'] = $total;
        return_json_encode($result);
    }

    //
    public function set_join_status(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $status = intval(input('status'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $info = db('guild_join')->where('guild_id = '.$user_info['id'].' and id = '.$id)->find();
        if($info){
            if($status==1){
                db('guild_join')->where('guild_id = '.$user_info['id'].' and id = '.$id)->update(['status'=>1]);
                // 添加公会直播间
                db('voice')->where('user_id=' . $info['user_id']." and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid'=>$user_info['user_id']]);
                db('user')->where('id=' . $info['user_id'])->update(['guild_id'=>$user_info['guild_id']]);
            }else{
                db('guild_join')->where('guild_id = '.$user_info['id'].' and id = '.$id)->delete();
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = lang('user_does_not_exist');
        }
        return_json_encode($result);
    }

    //踢出公会
    public function kick_out(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_info['id'] = $user_info['guild_id'];
        $info = db('guild_join')->where('guild_id = '.$user_info['id'].' and user_id = '.$id)->find();
        if($info){
            db('guild_join')->where('guild_id = '.$user_info['id'].' and user_id = '.$id)->delete();
            // 清除公会直播间
            db('voice')->where('user_id=' . $id." and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid'=>'']);
            db('user')->where('id=' . $id)->update(['guild_id'=>0]);
        }else{
            $result['code'] = 0;
            $result['msg'] = lang('user_does_not_exist');
        }
        return_json_encode($result);
    }

    //封禁语音房间
    public function request_ban(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $info = db('voice')->where('user_id = '.$id)->find();
        if($info){
            if($info['status']==1){
                $data = ['status'=>2];
            }else{
                $data = ['status'=>1];
            }
            db('voice')->where('user_id = '.$id)->update($data);
        }else{
            $result['code'] = 0;
            $result['msg'] = lang('Room_does_not_exist');
        }
        return_json_encode($result);
    }

    //修改提现账户
    public function update_cash_account(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $cash_account = trim(input('cash_account'));
        $account_name = trim(input('account_name'));
        $user_info = $this->check_token($uid,$token);

        $GuildModel = new GuildModel();
        $GuildModel->updateCashAccount($user_info['user_id'],$cash_account,$account_name);

        $result['account_name'] = $account_name;
        $result['cash_account'] = $cash_account;
        return_json_encode($result);
    }

    //月报
    public function get_monthly_report(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $user_info = $this->check_token($uid,$token);
        $where = 'guild_id = '.$user_info['guild_id'];
        if($start_time && $end_time){
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);
            $where .= ' and report_time >= '.$start_time.' and report_time <= '.$end_time;
        }
        $list = db('guild_monthly_report')->where($where)->page($page,$limit)->select();
        $total = db('guild_monthly_report')->where($where)->count();
        $result['total'] = $total;
        $result['list'] = $list;
        return_json_encode($result);
    }

    //删除月报
    public function del_monthly_report(){
        $result = array('code' => 1, 'msg' => lang('DELETE_SUCCESS'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $id = intval(input('id'));
        $user_info = $this->check_token($uid,$token);
        $where = 'guild_id = '.$user_info['guild_id'].' and id = '.$id;

        $list = db('guild_monthly_report')->where($where)->find();
        if($list){
            db('guild_monthly_report')->where($where)->delete();
        }
        return_json_encode($result);
    }
}