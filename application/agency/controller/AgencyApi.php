<?php

namespace app\agency\controller;

use app\agency\controller\BaseApi;
use app\agency\model\Agency;
use app\agency\model\AgencyLog;
use app\agency\model\AgencyMenu;
use app\agency\model\User;
use think\Db;

class AgencyApi extends BaseApi
{
    /**
     * 给代理金币
     */
    public function agency_sell_coin(){
        $root = array('code' => 200, 'message' => apiLang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $agency_id = intval(input('param.agency_id')); // 给代理充值
        $number = intval(input('param.number')); // 售出数量
        $desc = strim(input('param.desc')); // 备注
        $user_info = redis_get_token($token);

        $where ="id =".$agency_id;

        $Agency = new Agency();
        $AgencyLog = new AgencyLog();
        $agency_one = $Agency -> select_Agency_one($where,"coin,name,platform_level,first_superior_id,two_superior_id");

        if (!$agency_one){
            $root['code'] = 0;
            $root['message'] = apiLang("user_does_not_exist");
            return_json_encode($root);;
        }
        if ($user_info['platform_level'] == 1) {
            if ($agency_one['first_superior_id'] !=$user_info['id']) {
                $root['code'] = 0;
                $root['message'] = apiLang("Lack_of_authority");
                return_json_encode($root);;
            }
        }elseif($user_info['platform_level'] == 2){
            if ($agency_one['two_superior_id'] !=$user_info['id']) {
                $root['code'] = 0;
                $root['message'] = apiLang("Lack_of_authority");
                return_json_encode($root);;
            }
        }
        $key = "agency_sell_coin_".$user_info['id'];
        redis_locksleep($key);
        $where ="id =".$user_info['id'];
        $user_info = $Agency -> select_Agency_one($where);
        if($user_info['coin'] < $number){
            $root['code'] = 0;
            $root['message'] = apiLang('insufficient_balance');
            redis_unlock($key);
            return_json_encode($root);;
        }

        Db::startTrans();
        try {
            // 扣除余额
            $Agency -> dec_agency("id=".$user_info['id'],"coin",$number);
            $Agency -> inc_agency("id=".$user_info['id'],"consumption_coin",$number);
            $insert = array(
                'operator_id' => $user_info['id'],
                'operator_old_coin' => $user_info['coin'],
                'coin' => $number,
                'old_coin' => $agency_one['coin'],
                'agency_id' => $agency_id,
                'type' => 1,
                'create_time' => NOW_TIME,
                'desc' => $desc,
                'platform_level' => $user_info['platform_level'],
                'first_superior_id' => $user_info['first_superior_id'],
                'two_superior_id' => $user_info['two_superior_id'],
                'agency_superior_id' => $agency_one['two_superior_id'] ? $agency_one['two_superior_id'] : $agency_one['first_superior_id'],
                'ip' => get_client_ip(),
                'admin_headers' => json_encode(getallheaders())
            );
            $AgencyLog -> add_agency_log($insert);
            // 增加代理充值金额
            $Agency -> inc_agency("id=".$agency_id,"coin",$number);
            $Agency -> inc_agency("id=".$agency_id,"coin_total",$number);
            Db::commit();
        }catch (Exception $e) {
            $root['code'] = 0;
            $root['message'] = $e->getMessage();
            // 回滚事务
            Db::rollback();
        }
        redis_unlock($key);
        return_json_encode($root);;
    }
    /**
     * 给用户售出金币
     */
    public function sell_coin(){
        $root = array('code' => 200, 'message' => lang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $touid = intval(input('param.touid')); // 收益人id
        $number = intval(input('param.number')); // 售出数量
        $desc = strim(input('param.desc')); // 备注
        $user_info = redis_get_token($token);

        $where ="id =".$touid." or luck=".$touid;
        $User = new User();
        $AgencyLog = new AgencyLog();
        $user_one = $User -> get_user_one($where,"id,user_nickname as nick_name,coin");
        if (!$touid || !$user_one){
            $root['code'] = 0;
            $root['message'] = apiLang("user_does_not_exist");
            return_json_encode($root);;
        }
        $key = "sell_coin_".$user_one['id'];
        redis_locksleep($key);
        $where ="id =".$user_info['id'];
        $Agency = new Agency();
        $Agency_one = $Agency -> select_Agency_one($where,"coin");
        if($Agency_one['coin'] < $number){
            $root['code'] = 0;
            $root['message'] = apiLang('insufficient_balance');
            redis_unlock($key);
            return_json_encode($root);;
        }

        Db::startTrans();
        try {
            // 扣除余额
            $Agency -> dec_agency("id=".$user_info['id'],"coin",$number);
            $Agency -> inc_agency("id=".$user_info['id'],"consumption_coin",$number);
            $insert = array(
                'operator_id' => $user_info['id'],
                'operator_old_coin' => $user_info['coin'],
                'coin' => $number,
                'old_coin' => $user_one['coin'],
                'uid' => $user_one['id'],
                'type' => 2,
                'create_time' => NOW_TIME,
                'desc' => $desc,
                'platform_level' => $user_info['platform_level'],
                'first_superior_id' => $user_info['first_superior_id'],
                'two_superior_id' => $user_info['two_superior_id'],
                'ip' => get_client_ip(),
                'admin_headers' => json_encode(getallheaders())
            );
            $AgencyLog -> add_agency_log($insert);
            // 增加用户充值金额
            $User->inc_user("id=".$user_one['id'],'coin',$number);
            $notice_id = NOW_TIME . $user_one['id'] . rand(1000,9999);//订单号码
            $order_info = [
                'uid' => $user_one['id'],
                'money' => 0,
                'coin' => $number,
                'refillid' => 0,
                'addtime' => NOW_TIME,
                'status' => 1,
                'type' => 8888888,
                'order_id' => $notice_id,
                'pay_type_id' => 0,
                'agency_id' => $user_info['id'],
            ];

            //增加订单记录
            db('user_charge_log')->insert($order_info);

            //发送系统消息
            push_msg_user(17, $user_one['id'], 3, apiLang("Recharge_to_account").":" . $number);
            Db::commit();
        }catch (Exception $e) {
            $root['code'] = 0;
            $root['message'] = $e->getMessage();
            // 回滚事务
            Db::rollback();
        }
        redis_unlock($key);
        return_json_encode($root);;
    }
    // 获取登录信息
    public function get_info(){
        $root = array('code' => 200, 'message' => '');
        $token = strim(input('param.token'));
        $user_info = redis_get_token($token);
        $roles = [];
        $AgencyMenu = new AgencyMenu();
        $list = $AgencyMenu->get_agency_menu("id > 0",'id,title,name');

        if($list){
            $m_config =  load_cache('config');//初始化手机端配置
            $is_agent_authority = 0;
            if ($user_info['platform_level'] < $m_config['recharge_background_level']) {
                // 是否有权限开启下级代理
                $is_agent_authority = 1;
            }
            foreach ($list as $v){
                if ($is_agent_authority == 0) {
                    if ($v['name'] != 'agent' && $v['name'] != 'agency_log') {
                        $roles[] = ['name'=>$v['name']];
                    }
                }else{
                    $roles[] = ['name'=>$v['name']];
                }
            }
        }

        $root['data']['roles'] = $roles;
        $root['data']['name'] = $user_info['name'];
        $root['data']['avatar'] = $user_info['logo'];
        $root['data']['introduction'] = $user_info['platform_level'];

        return_json_encode($root);;
    }
    // 获取充值记录
    public function recharge_log(){
        $root = array('code' => 200, 'message' => '', 'data' => array());
        $page = intval(input('param.page'));
        $limit = intval(input('param.limit'));
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $user_info = redis_get_token($token);

        $where ="type = 1 and agency_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and create_time >=".strtotime($createTimeStart) : '';

        $AgencyLog = new AgencyLog();
        $list = $AgencyLog -> get_recharge_log($where,$page,$limit);
        $sum = $AgencyLog -> get_recharge_log_count($where,"coin");

        $root['data'] = array(
            'current' => $list['current_page'],
            'pages' => $list['last_page'],
            'size' => $list['per_page'],
            'total' =>$list['total'],
            'records' => $list['data'],
            'sum' => $sum,
        );
        return_json_encode($root);
    }
    // 获取售出记录
    public function sell_log(){
        $root = array('code' => 200, 'message' => '', 'data' => array());
        $page = intval(input('param.page'));
        $limit = intval(input('param.limit'));
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $id = intval(input('param.id'));
        $nickName = strim(input('param.nickname'));
        $user_info = redis_get_token($token);

        $where ="l.type = 2 and l.operator_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and l.create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and l.create_time >=".strtotime($createTimeStart) : '';
        $where .= $id ? " and l.uid =".$id : '';
        $where .=$nickName ? " and u.user_nickname like '%".$nickName."%'" : '';

        $AgencyLog = new AgencyLog();
        $list = $AgencyLog -> get_sell_log($where,$page,$limit);
        $sum = $AgencyLog -> get_sell_log_count($where,"l.coin");

        $root['data'] = array(
            'current' => $list['current_page'],
            'pages' => $list['last_page'],
            'size' => $list['per_page'],
            'total' =>$list['total'],
            'records' => $list['data'],
            'sum' => $sum,
        );
        return_json_encode($root);;
    }
    /**
     * 下级充值记录
     */
    public function agency_log(){
        $root = array('code' => 200, 'message' => '', 'data' => array());
        $page = intval(input('param.page'));
        $limit = intval(input('param.limit'));
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $id = intval(input('param.id'));
        $nickName = strim(input('param.nickname'));
        $user_info = redis_get_token($token);

        $where ="l.type = 1 and l.operator_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and l.create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and l.create_time >=".strtotime($createTimeStart) : '';
        $where .= $id ? " and l.agency_id =".$id : '';
        $where .=$nickName ? " and a.name like '%".$nickName."%'" : '';

        $AgencyLog = new AgencyLog();
        $list = $AgencyLog -> get_agency_log($where,$page,$limit);
        $sum = $AgencyLog -> get_agency_log_count($where,"l.coin");

        $root['data'] = array(
            'current' => $list['current_page'],
            'pages' => $list['last_page'],
            'size' => $list['per_page'],
            'total' =>$list['total'],
            'records' => $list['data'],
            'sum' => $sum,
        );
        return_json_encode($root);;
    }
    /**
    * 获取下级充值导出
     */
    public function agency_log_export(){
        $root = array('code' => 200, 'message' => '', 'data' => array());
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $id = intval(input('param.id'));
        $nickName = strim(input('param.nickname'));
        $user_info = redis_get_token($token);

        $where ="l.type = 1 and l.operator_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and l.create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and l.create_time >=".strtotime($createTimeStart) : '';
        $where .= $id ? " and l.agency_id =".$id : '';
        $where .=$nickName ? " and a.name like '%".$nickName."%'" : '';

        $AgencyLog = new AgencyLog();
        $list = $AgencyLog -> get_agency_log_export($where);

        $root['data'] = $list;
        return_json_encode($root);;
    }
    // 获取售出记录 --导出
    public function sell_log_export(){
        $root = array('code' => 200, 'message' => '', 'data' => array());
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $id = intval(input('param.id'));
        $nickName = strim(input('param.nickname'));
        $user_info = redis_get_token($token);

        $where ="l.type = 2 and l.operator_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and l.create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and l.create_time >=".strtotime($createTimeStart) : '';
        $where .= $id ? " and l.uid =".$id : '';
        $where .=$nickName ? " and u.nick_name like '%".$nickName."%'" : '';

        $AgencyLog = new AgencyLog();
        $list = $AgencyLog -> get_sell_log_export($where);

        $root['data'] = $list;
        return_json_encode($root);;
    }
    // 获取充值记录 -- 导出
    public function recharge_log_export(){
        $root = array('code' => 200, 'message' => '', 'data' => array());
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $user_info = redis_get_token($token);

        $where ="type = 1 and agency_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and create_time >=".strtotime($createTimeStart) : '';

        $AgencyLog = new AgencyLog();
        $list = $AgencyLog -> get_recharge_log_export($where);

        $root['data'] =$list;
        return_json_encode($root);;
    }
}