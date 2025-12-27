<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/12/16
 * Time: 2:56 下午
 * Name:
 */
namespace app\guild_api\controller;

use app\guild_api\model\GuildModel;
use think\Controller;
use think\Db;
use think\config;
use think\Model;

class UserApi extends Base
{
    // 获取公会长账户
    public function get_wallet(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $user_id = $user_info['user_id'];
        $config = load_cache('config');

        $user = db('user')->field("income,income_total")->where('id = '.$user_id)->find();
        // 获取提现申请中的金额
        $Withdrawal_income = db("user_cash_record")->where('user_id='.$user_id." and transfer_status=0 and (status=0 or status=1)")->sum("money");
        // 已提现金额
        $Withdrawal_income_total = db("user_cash_record")->where('user_id='.$user_id." and transfer_status=1")->sum("money");
        // 到账账户
        $pays = db('user_cash_account')->where('uid=' . $user_id)->find();
        $user_cash_account = "";
        if ($pays) {
            if (!empty($pays['pay'])) {
                $user_cash_account = $pays['pay'];
            }
        }
        // 可提现金额比例
        $integral_withdrawal = $config['integral_withdrawal'];
        // 提现说明
        $notice_text = $config['withdraw_notice_text'];

        $result['list'] = array(
            'income'=> $user['income'],
            'income_total'=> $user['income_total'],
            'Withdrawal_income'=> $Withdrawal_income,
            'Withdrawal_income_total'=> $Withdrawal_income_total,
            'Withdrawal_account'=> $user_cash_account,
            'integral_withdrawal'=> $integral_withdrawal,
            'notice_text'=> $notice_text,
        );
        return_json_encode($result);
    }
    //申请提现
    public function request_user_apply()
    {
        $result = array('code' => 0, 'msg' => '');

        $money = intval(input('param.money'));//金额
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_login = $this->check_token($uid,$token);
        $uid = intval($user_login['user_id']);

        $user_info = db('user')->field('income,is_auth,user_status')->where(['id' => $uid])->find();

        if ($user_info['is_auth'] != 1) {
            $result['msg'] = lang('Withdrawal_after_certification');
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Due_to_suspected_violation');
            return_json_encode($result);
        }

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        if ($pays) {
            if (empty($pays['pay'])) {
                $result['msg'] = lang('Please_bind_account');
                return_json_encode($result);
            }
        } else {
            $result['msg'] = lang('Please_bind_account');
            return_json_encode($result);
        }

        $config = load_cache('config');
        if($money<$config['min_cash_income']){
            $result['msg'] = lang('minimum_withdrawal_amount_is').$config['min_cash_income'];
            return_json_encode($result);
        }

        if($money>$config['max_cash_income']){
            $result['msg'] = lang('maximum_withdrawal_amount_is').$config['max_cash_income'];
            return_json_encode($result);
        }

        $integral = $user_info['income']/$config['integral_withdrawal'];
        if ($integral < $money) {
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        //查询是否超过当日最大提现次数
        $day_time = strtotime(date('Y-m-d'));
        $day_cash_num = db('user_cash_record')->where('user_id', '=', $uid)->where('create_time >' . $day_time)->count();

        if ($day_cash_num == $config['cash_day_limit']) {
            $result['msg'] = lang('maximum_withdrawal_times_per_day_are') . $day_cash_num . '！';
            return_json_encode($result);
        }

        //扣除剩余提现额度
        $coin = $config['integral_withdrawal'] * $money;
        $inc_income = db('user')->where('id', '=', $uid)->where('income >= '.$coin)->setDec('income', $coin);
        if ($inc_income) {
            //添加提现记录
            $record = ['gathering_name' => $pays['name'], 'gathering_number' => $pays['pay'], 'user_id' => $uid, 'paysid' => $pays['id'], 'income' => $coin, 'money' => $money, 'create_time' => NOW_TIME];
            db('user_cash_record')->insert($record);
            $result['msg'] = lang('Withdrawal_succeeded_waiting_approval');
            $result['code'] = 1;

        }
        return_json_encode($result);
    }
    public function get_admin_list(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $status = trim(input('status'));
        $user_info = $this->check_token($uid,$token);
        $where = '';
        if($status){
            $where = 'status = '.$status;
        }
        $GuildModel = new GuildModel();
        $list = $GuildModel->adminList($user_info['guild_id'],$where);
        $result['list'] = $list;
        return_json_encode($result);
    }
    //提现记录
    public function withdrawal_record()
    {
        $result = array('code'=>1,'msg'=>'','data'=>array());
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $login_uid = intval(input('uid'));
        $token = trim(input('token'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $status = intval(input('status'));
        $user_info = $this->check_token($login_uid,$token);
        $uid = $user_info['user_id'];

        $where = 'user_id = ' . $uid;
        $where .= $status >= 0 ? ' and status = '.$status : '';
        $where .= $start_time ? ' and create_time >= '.strtotime($start_time) : '';
        $where .= $end_time ? ' and create_time <= '.strtotime($end_time) : '';

        $list = Db("user_cash_record")
            ->order('create_time desc')
            ->where($where)
            ->page($page, $limit)
            ->select();

        $total =  Db("user_cash_record")->where($where)->count();
        if (is_object($list)) {
            $list = json_decode( json_encode($list),true);
        }
        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
            }
        }
        $result['data']['list'] = $list;
        $result['data']['total'] = $total;
        return_json_encode($result);
    }
    // 获取收益分类类型
    public function get_user_consume_type(){
        $result = array('code'=>1,'msg'=>'','data'=>array());
        $config = load_cache('config');
        // 开关 开启语音厅 开启陪玩约单 开启私聊礼物 开启私聊消息 开启视频通话 开启公语音通话 开启短视频礼物
        $switch = array(
            'guild_voice_open' => intval($config['guild_voice_open']),
            'guild_player_open' => intval($config['guild_player_open']),
            'guild_chat_gift_open' => intval($config['guild_chat_gift_open']),
            'guild_chat_news_open' => intval($config['guild_chat_news_open']),
            'guild_video_call_open' => intval($config['guild_video_call_open']),
            'guild_audio_call_open' => intval($config['guild_audio_call_open']),
            'guild_video_gift_open' => intval($config['guild_video_gift_open'])
        );
        // 分类0无1语音房间2视频3动态4私信
        $type= array();
        if($switch['guild_voice_open']) {
            $type[]=array(
                'id' => 1,
                'title'=> lang('Voice_room'),
            );
        }
        if($switch['guild_player_open']) {
            $type[]=array(
                'id' => 2,
                'title'=> lang('play_with'),
            );
        }
        if($switch['guild_chat_gift_open']) {
            $type[]=array(
                'id' => 3,
                'title'=> lang('Private_chat_gift'),
            );
        }
        if($switch['guild_chat_news_open']) {
            $type[]=array(
                'id' => 4,
                'title'=> lang('Private_chat'),
            );
        }
        if($switch['guild_video_call_open']) {
            $type[]=array(
                'id' => 5,
                'title'=> lang('Video_call'),
            );
        }
        if($switch['guild_audio_call_open']) {
            $type[]=array(
                'id' => 6,
                'title'=> lang('voice_call'),
            );
        }
        if($switch['guild_video_gift_open']) {
            $type[]=array(
                'id' => 7,
                'title'=> lang('Short_video_gift'),
            );
        }
        $result['data']= $type;
        return_json_encode($result);
    }
    // 收益记录
    public function get_profit_log(){
        $result = array('code'=>1,'msg'=>'','data'=>array());
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $login_uid = intval(input('uid'));
        $token = trim(input('token'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $to_user_id = intval(input('to_user_id'));
        $classification_id = intval(input('classification_id'));
        $type = intval(input('type'));
        $user_info = $this->check_token($login_uid,$token);
        $uid = $user_info['user_id'];
        $config = load_cache('config');
        // 开关 开启语音厅 开启陪玩约单 开启私聊礼物 开启私聊消息 开启视频通话 开启公语音通话 开启短视频礼物
        $switch = array(
            'guild_voice_open' => intval($config['guild_voice_open']),
            'guild_player_open' => intval($config['guild_player_open']),
            'guild_chat_gift_open' => intval($config['guild_chat_gift_open']),
            'guild_chat_news_open' => intval($config['guild_chat_news_open']),
            'guild_video_call_open' => intval($config['guild_video_call_open']),
            'guild_audio_call_open' => intval($config['guild_audio_call_open']),
            'guild_video_gift_open' => intval($config['guild_video_gift_open'])
        );

        $where = 'l.guild_uid = ' . $uid." and l.guild_earnings >0 and l.status=1";
        $where .= $to_user_id ? ' and l.to_user_id = '.$to_user_id : '';
        $where .= $start_time ? ' and l.create_time >= '.strtotime($start_time) : '';
        $where .= $end_time ? ' and l.create_time <= '.strtotime($end_time) : '';
        $where .= $classification_id ? ' and l.classification_id = '.$classification_id : '';

        // 筛选类型
        switch ($type)
        {
            case 1:
                $where .= ' and l.classification = 1'; break;
            case 2:
                $where .= ' and l.type = 7'; break;
            case 3:
                $where .= ' and l.classification = 4'; break;
            case 4:
                $where .= ' and l.type = 5'; break;
            case 5:
                $where .= ' and l.type = 2'; break;
            case 6:
                $where .= ' and l.type = 1'; break;
            case 7:
                $where .= ' and l.classification = 2'; break;
            default:
                break;
        }

        // 后台奖励类型权限
        $where .= $switch['guild_voice_open'] == 0 ? ' and l.classification != 1' : '';
        $where .= $switch['guild_player_open'] == 0 ? ' and l.type != 7' : '';
        $where .= $switch['guild_chat_gift_open'] == 0 ? ' and l.classification != 4' : '';
        $where .= $switch['guild_chat_news_open'] == 0 ? ' and l.type != 5' : '';
        $where .= $switch['guild_video_call_open'] == 0 ? ' and l.type != 2' : '';
        $where .= $switch['guild_audio_call_open'] == 0 ? ' and l.type != 1' : '';
        $where .= $switch['guild_video_gift_open'] == 0 ? ' and l.classification != 2' : '';

        $field = "u.user_nickname as uname,t.user_nickname as tname,l.user_id,l.to_user_id,l.id,l.coin,l.create_time,l.type,l.content,l.guild_earnings,l.guild_commission,l.classification,l.classification_id";

        $list = Db("user_consume_log")->alias("l")
            ->join("user u","u.id=l.to_user_id")
            ->join("user t","t.id=l.user_id")
            ->field($field)
            ->order('l.create_time desc')
            ->where($where)
            ->page($page, $limit)
            ->select();

        $total =  Db("user_consume_log")->alias("l")->field("count(l.id) as count,sum(l.coin) as coin,sum(l.guild_earnings) as guild_earnings")->where($where)->find();
        if (is_object($list)) {
            $list = json_decode( json_encode($list),true);
        }
        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
            }
        }
        $result['data']['list'] = $list;
        $result['data']['total'] = $total ? $total['count'] : 0;
        $result['data']['coin_total'] = $total ? $total['coin'] : 0;
        $result['data']['guild_earnings_total'] = $total ? $total['guild_earnings'] : 0;
        $result['currency_name'] = $config['currency_name'];
        $result['revenue_name'] = $config['virtual_currency_earnings_name'];
        return_json_encode($result);
    }
    // 导出收益记录
    public function profit_log_handle(){
        $result = array('code'=>1,'msg'=>'','data'=>array());
        $login_uid = intval(input('uid'));
        $token = trim(input('token'));
        $start_time = trim(input('start_time'));
        $end_time = trim(input('end_time'));
        $to_user_id = intval(input('to_user_id'));
        $classification_id = intval(input('classification_id'));
        $type = intval(input('type'));
        $user_info = $this->check_token($login_uid,$token);
        $uid = $user_info['user_id'];
        $config = load_cache('config');
        // 开关 开启语音厅 开启陪玩约单 开启私聊礼物 开启私聊消息 开启视频通话 开启公语音通话 开启短视频礼物
        $switch = array(
            'guild_voice_open' => intval($config['guild_voice_open']),
            'guild_player_open' => intval($config['guild_player_open']),
            'guild_chat_gift_open' => intval($config['guild_chat_gift_open']),
            'guild_chat_news_open' => intval($config['guild_chat_news_open']),
            'guild_video_call_open' => intval($config['guild_video_call_open']),
            'guild_audio_call_open' => intval($config['guild_audio_call_open']),
            'guild_video_gift_open' => intval($config['guild_video_gift_open'])
        );

        $where = 'l.guild_uid = ' . $uid." and l.guild_earnings >0 and l.status=1";
        $where .= $to_user_id ? ' and l.to_user_id = '.$to_user_id : '';
        $where .= $start_time ? ' and l.create_time >= '.strtotime($start_time) : '';
        $where .= $end_time ? ' and l.create_time <= '.strtotime($end_time) : '';
        $where .= $classification_id ? ' and l.classification_id = '.$classification_id : '';

        // 筛选类型
        switch ($type)
        {
            case 1:
                $where .= ' and l.classification = 1'; break;
            case 2:
                $where .= ' and l.type = 7'; break;
            case 3:
                $where .= ' and l.classification = 4'; break;
            case 4:
                $where .= ' and l.type = 5'; break;
            case 5:
                $where .= ' and l.type = 2'; break;
            case 6:
                $where .= ' and l.type = 1'; break;
            case 7:
                $where .= ' and l.classification = 2'; break;
            default:
                break;
        }

        // 后台奖励类型权限
        $where .= $switch['guild_voice_open'] == 0 ? ' and l.classification != 1' : '';
        $where .= $switch['guild_player_open'] == 0 ? ' and l.type != 7' : '';
        $where .= $switch['guild_chat_gift_open'] == 0 ? ' and l.classification != 4' : '';
        $where .= $switch['guild_chat_news_open'] == 0 ? ' and l.type != 5' : '';
        $where .= $switch['guild_video_call_open'] == 0 ? ' and l.type != 2' : '';
        $where .= $switch['guild_audio_call_open'] == 0 ? ' and l.type != 1' : '';
        $where .= $switch['guild_video_gift_open'] == 0 ? ' and l.classification != 2' : '';

        $field = "u.user_nickname as uname,t.user_nickname as tname,l.user_id,l.to_user_id,l.id,l.coin,l.create_time,l.type,l.content,l.guild_earnings,l.guild_commission,l.classification,l.classification_id";

        $list = Db("user_consume_log")->alias("l")
            ->join("user u","u.id=l.to_user_id")
            ->join("user t","t.id=l.user_id")
            ->field($field)
            ->order('l.create_time desc')
            ->where($where)
            ->select();

        if (is_object($list)) {
            $list = json_decode( json_encode($list),true);
        }
        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
            }
        }
        $result['data'] = $list;
        return_json_encode($result);
    }
    //添加管理员
    public function add_admin(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $avatar = trim(input('avatar'));
        $login = trim(input('login'));
        $psd = trim(input('psd'));
        $mobile = trim(input('mobile'));
        $status = trim(input('status'));
        $id = trim(input('id'));

        $user_info = $this->check_token($uid,$token);
        $guild = db('guild')->where('login = '.'"'.$login.'"')->find();
        if($id){
            $guild_admin = db('guild_admin')->where('id != '.$id.' and login = '.'"'.$login.'"')->find();
            if($guild || $guild_admin){
                $result['code'] = 0;
                $result['msg'] = lang('User_name_already_exists');
                return_json_encode($result);
            }
            $data = [
                'logo'=>$avatar,
                'login'=>$login,
                'mobile'=>$mobile,
                'status'=>$status,
                'guild_id'=>$user_info['guild_id'],
                //'create_time'=>NOW_TIME,
            ];
            if($psd){
                $data['psd']=cmf_password($psd);
            }
            db('guild_admin')->where('id = '.$id)->update($data);
        }else{
            $guild_admin = db('guild_admin')->where('login = '.'"'.$login.'"')->find();
            if($guild || $guild_admin){
                $result['code'] = 0;
                $result['msg'] = lang('User_name_already_exists');
                return_json_encode($result);
            }
            $data = [
                'logo'=>$avatar,
                'login'=>$login,
                'psd'=>cmf_password($psd),
                'mobile'=>$mobile,
                'status'=>$status,
                'guild_id'=>$user_info['guild_id'],
                'create_time'=>NOW_TIME,
            ];
            db('guild_admin')->insert($data);
        }

        return_json_encode($result);
    }

    //修改管理员状态，删除管理员
    public function upd_admin(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $id = trim(input('id'));
        $type = trim(input('type'));

        $user_info = $this->check_token($uid,$token);
        $info = db('guild_admin')->where('guild_id = '.$user_info['guild_id'].' and id = '.$id)->find();
        if(!$info){
            $result['code'] = 0;
            $result['msg'] = lang('Login_account_does_not_exist');
            return_json_encode($result);
        }
        if($type==1){
            db('guild_admin')->where('id = '.$id)->update(['status'=>2]);
        }else if($type==3){
            db('guild_admin')->where('id = '.$id)->update(['status'=>1]);
        }else if($type==2){
            db('guild_admin')->where('id = '.$id)->delete();
        }
        return_json_encode($result);
    }

    //所有菜单
    public function get_admin_menu(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $list = db('guild_admin_menu')->field('id,title,menu_id')->where('menu_id = 0')->select();
        $list_children = db('guild_admin_menu')->field('id,title,menu_id')->where('menu_id != 0')->select();

        $list_all = [];
        if($list){
            foreach ($list as $v){
                $arr = [];
                $arr['id'] = $v['id'];
                $arr['label'] = $v['title'];
                $arr['children'] = [];
                $children = [];
                foreach ($list_children as $key=>$val){
                    if($v['id']==$val['menu_id']){
                        $children['id'] = $val['id'];
                        $children['label'] = $val['title'];
                        unset($list_children[$key]);
                        $arr['children'][] = $children;
                    }
                }
                $list_all[] = $arr;
            }
        }
        $checked_list = db('guild_admin_menu_user')->where('guild_id = '.$id)->select();
        $checked_key_id = [];
        $half_checked_key_id = [];
        if($checked_list){
            foreach ($checked_list as $v){
                if($v['is_half']==1){
                    array_push($half_checked_key_id,$v['menu_id']);
                }else{
                    array_push($checked_key_id,$v['menu_id']);
                }
            }
        }

        $result['menu_list'] = $list_all;
        $result['checked_keys'] = $checked_key_id;
        $result['half_checked_keys'] = $half_checked_key_id;

        return_json_encode($result);
    }

    //设置菜单
    public function set_admin_menu(){
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('id'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        $checked_keys = trim(input('checked_keys'));
        $half_checked_keys = trim(input('half_checked_keys'));
        $list = db('guild_admin_menu_user')->where('guild_id = '.$id)->select();
        if($checked_keys){
            $checked_key = explode(',',$checked_keys);
        }else{
            $checked_key = [];
        }
        if($half_checked_keys){
            $half_checked_key = explode(',',$half_checked_keys);
        }else{
            $half_checked_key = [];
        }
        $menu_id = [];
        $checked_key_id = [];
        $half_checked_key_id = [];
        if($list){
            foreach ($list as $v){
                if($v['is_half']==1){
                    array_push($half_checked_key_id,$v['menu_id']);
                }else{
                    array_push($checked_key_id,$v['menu_id']);
                }
            }
        }
        //存在的 交集
        $checked_intersect = array_intersect($checked_key,$checked_key_id);
        //选中不存在的添加
        $checked_diff = array_diff($checked_key,$checked_intersect);
        $checked_diff_del_id = array_diff($checked_key_id,$checked_intersect);
        if($checked_diff_del_id){
            db('guild_admin_menu_user')->where('is_half = 0 and guild_id = '.$id)->where('menu_id','in',$checked_diff_del_id)->delete();
        }
        //一级菜单
        $half_intersect = array_intersect($half_checked_key,$half_checked_key_id);
        $half_diff = array_diff($half_checked_key,$half_intersect);
        $half_diff_del_id = array_diff($half_checked_key_id,$half_intersect);
        if($half_diff_del_id){
            db('guild_admin_menu_user')->where('is_half = 1 and guild_id = '.$id)->where('menu_id','in',$half_diff_del_id)->delete();
        }

        $data = [];
        if($checked_diff){
            foreach ($checked_diff as $v){
                $arr = [];
                $arr['guild_id'] = $id;
                $arr['menu_id'] = $v;
                $arr['is_half'] = 0;
                $arr['create_time'] = NOW_TIME;
                $data[] = $arr;
            }
        }
        if($half_diff){
            foreach ($half_diff as $v){
                $arr = [];
                $arr['guild_id'] = $id;
                $arr['menu_id'] = $v;
                $arr['is_half'] = 1;
                $arr['create_time'] = NOW_TIME;
                $data[] = $arr;
            }
        }
        //dump();
        if($data){
            db('guild_admin_menu_user')->insertAll($data);
        }
        return_json_encode($result);
    }
}