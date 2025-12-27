<?php
namespace app\agency\controller;

use app\agency\controller\BaseApi;
use app\agency\model\AgencyLog;
use app\agency\model\User;
use app\agency\model\Agency;

class IndexApi extends BaseApi
{
    public function index(){
        $root = array('code' => 200, 'message' => '');
        $token = strim(input('param.token'));
        $user_info = redis_get_token($token);

        $AgencyModel = new Agency();
        $AgencyLog = new AgencyLog();
        $User = new User();
        $Agency = $AgencyModel->select_Agency_one("id =".$user_info['id'],'coin,coin_total,consumption_coin');
        // 平台给代理总充值单数
        $Agency_count = $AgencyLog -> sel_recharge_log_count("agency_id=".$user_info['id']." and type=1");
        $agencyUser = 0; // 所有的
        $agency_sum = 0;// 下级
        $inter_push_agency = 0; // 下下级
        // 获取所有代理用户数
        if ($user_info['platform_level'] == 1) {
            $agency_sum = $AgencyModel->select_Agency_count("first_superior_id=".$user_info['id']." and two_superior_id=0");
            $inter_push_agency = $AgencyModel->select_Agency_count("first_superior_id=".$user_info['id']." and two_superior_id >0");
            $agencyUser = $agency_sum + $inter_push_agency;
        }elseif($user_info['platform_level'] == 2) {
            $agency_sum = $AgencyModel->select_Agency_count("two_superior_id=".$user_info['id']);
            $agencyUser = $agency_sum;
        }
        // 获取充值的用户 -- 统计充值用户个数
        $userList = $User->getUserListCount("p.agency_id=".$user_info['id']." and p.type=2");
        // 获取总订单数量
        $user_count = $AgencyLog -> sel_recharge_log_count("operator_id=".$user_info['id']." and type=2");

        $data=array(
            'totalCount'=> count($userList),
            'AgencyCount'=> $Agency_count,
            'UserCount'=> $user_count,
            'coin'=> $Agency['coin'],
            'coin_total'=> $Agency['coin_total'],
            'consumption_coin'=> $Agency['consumption_coin'],
            'agencyUser'=> $agencyUser,
            'agency_sum'=> $agency_sum,
            'inter_push_agency'=> $inter_push_agency,
        );
        $root['data'] = $data;
        return_json_encode($root);;
    }
}