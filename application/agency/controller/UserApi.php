<?php

namespace app\agency\controller;

use app\agency\controller\BaseApi;
use app\agency\model\User;
use app\agency\model\AgencyPromotion;
use app\agency\model\Agency;
/**
 * @OA\Info(title="系统初始化相关接口", version="0.1")
 */
class UserApi extends BaseApi
{
    /**
    * 添加或编辑代理充值账户
     */
    public function add_save_agency(){
        $root = array('code' => 0, 'message' => apiLang("operation_failed"), 'data' => '');
        $token = strim(input('param.token'));
        $user_info = redis_get_token($token);
        $id = intval(input('param.id'));
        $two_superior_id = intval(input('param.two_superior_id'));
        $data = array(
            'login' => strim(input('param.login')),
            'name' => strim(input('param.name')),
            'tel' => strim(input('param.tel')),
            'status' => intval(input('param.status')) == 2 ? 2 : 1,
            'create_time' => NOW_TIME,
        );
        $Agency = new Agency();
        if ($user_info['platform_level'] == 1) {
            if ($two_superior_id) {
                $two_superior_user = $Agency-> select_Agency_one("id=".$two_superior_id);
                if (!$two_superior_user || $two_superior_user['first_superior_id'] != $user_info['id'] || $two_superior_user['platform_level'] == 3) {
                    $root['message'] = apiLang("Lack_of_authority");
                    return_json_encode($root);;
                }
                $data['two_superior_id'] = $two_superior_id;
                $data['platform_level'] = 3;
            }else{
                $data['platform_level'] = 2;
            }
            $data['first_superior_id'] = $user_info['id'];
        }else{
            $data['two_superior_id'] = $user_info['id'];
            $data['first_superior_id'] = $user_info['first_superior_id'];
            $data['platform_level'] = 3;
        }
        if(empty($data['login'])){
            $root['message'] = apiLang("login.usernamePlaceholder");
            return_json_encode($root);;
        }
        if (strim(input('param.psd'))) {
            $data['psd'] = cmf_password(strim(input('param.psd')));
        }
        $select_where = "login ='".$data['login']."'";
        $select_where .= $id ? " and id !=".$id : '';
        $select_Agency_one = $Agency-> select_Agency_one($select_where);
        if ($select_Agency_one) {
            $root['message'] = apiLang("existing_account");
            return_json_encode($root);;
        }
        if($id){
            $result = $Agency -> save_admin("id=".$id,$data);
        }else{
            $result = $Agency -> add_admin($data);
            $invitation_code = initcode($result);
            // 生成邀请码
            $Agency -> save_admin("id=".$result,['invitation_code'=>$invitation_code]);
        }
        if($result){
            $root['code'] = 200;
            $root['message'] = apiLang("Operation_succeeded");
        }
        return_json_encode($root);;
    }
    /**
     * 获取代理管理
     */
    public function subordinate_agency_list(){
        $root = array('code' => 200, 'message' => apiLang("User_information"), 'data' => array());
        $page = intval(input('param.page'));
        $limit = intval(input('param.limit'));
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $id = intval(input('param.id'));
        $superior_id = intval(input('param.superior_id'));
        $nickName = strim(input('param.nickname'));
        $user_info = redis_get_token($token);

        if ($user_info['platform_level'] == 1) {
            $where ="g.first_superior_id =".$user_info['id'];
            if ($superior_id == $user_info['id']) {
                $where .= $superior_id ? " and g.two_superior_id = 0" : '';
            }
        }elseif ($user_info['platform_level'] == 2){
            $where ="g.two_superior_id =".$user_info['id'];
            $where .= $superior_id ? " and g.two_superior_id =".$superior_id : '';
        }

        $where .= $createTimeEnd ? " and g.create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and g.create_time >=".strtotime($createTimeStart) : '';
        $where .= $id ? " and g.id =".$id : '';
        $where .=$nickName ? " and g.name like '%".$nickName."%'" : '';

        $Agency = new Agency();
        $list = $Agency -> subordinate_agency_list($limit,$page,$where);
        $root['data'] = array(
            'current' => $list['current_page'],
            'pages' => $list['last_page'],
            'size' => $list['per_page'],
            'total' =>$list['total'],
            'records' => $list['data']
        );
        return_json_encode($root);;
    }
    /**
     * 停止使用
     */
    public function save_agency_status(){
        $root = array('code' => 200, 'message' => apiLang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $id = intval(input('param.id'));
        $user_info = redis_get_token($token);
        $where ="id=".$id;
        $data= array(
            'status' => 2,
            'endtime' => NOW_TIME
        );
        $Agency = new Agency();
        $result = $Agency -> select_Agency_one($where);
        if (!$result) {
            $root['code'] = 0;
            $root['message'] = apiLang("user_does_not_exist");
            return_json_encode($root);;
        }
        if ($result['status'] == 2) {
            $root['code'] = 200;
            $root['message'] = apiLang("Operation_succeeded");
            return_json_encode($root);;
        }
        if ($user_info['platform_level'] == 1) {
            if ($result['first_superior_id'] != $user_info['id']) {
                $root['code'] = 0;
                $root['message'] = apiLang("Lack_of_authority");
                return_json_encode($root);;
            }
        }elseif ($user_info['platform_level'] == 2){
            if ($result['two_superior_id'] != $user_info['id']) {
                $root['code'] = 0;
                $root['message'] = apiLang("Lack_of_authority");
                return_json_encode($root);;
            }
        }
        // 修改代理充值后台登录状态
        $result_status = $Agency ->save_admin($where,$data);
        if (!$result_status) {
            $root['code'] = 0;
            $root['message'] = apiLang("operation_failed");
        }
        return_json_encode($root);;
    }
    /**
    * 用户管理
     */
    public function member_list(){
        $root = array('code' => 200, 'message' => apiLang("User_information"), 'data' => array());
        $page = intval(input('param.page'));
        $limit = intval(input('param.limit'));
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $id = intval(input('param.id'));
        $nickName = strim(input('param.nickname'));
        $user_info = redis_get_token($token);

        $where ="u.is_authentication !=2 and a.status = 1 and a.agency_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and a.create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and a.create_time >=".strtotime($createTimeStart) : '';
        $where .= $id ? " and a.uid =".$id : '';
        $where .=$nickName ? " and u.nick_name like '%".$nickName."%'" : '';

        $AgencyPromotion = new AgencyPromotion();
        $list = $AgencyPromotion -> get_member_list($where,$page,$limit);
        $root['data'] = array(
            'current' => $list['current_page'],
            'pages' => $list['last_page'],
            'size' => $list['per_page'],
            'total' =>$list['total'],
            'records' => $list['data']
        );
        return_json_encode($root);;
    }
    /**
    * 获取主播管理
     */
    public function host_list(){
        $root = array('code' => 200, 'message' => apiLang("User_information"), 'data' => array());
        $page = intval(input('param.page'));
        $limit = intval(input('param.limit'));
        $token = strim(input('param.token'));
        $createTimeEnd = strim(input('param.createTimeEnd'));
        $createTimeStart = strim(input('param.createTimeStart'));
        $id = intval(input('param.id'));
        $nickName = strim(input('param.nickname'));
        $user_info = redis_get_token($token);

        $where ="u.is_authentication =2 and a.status = 1 and a.agency_id =".$user_info['id'];
        $where .= $createTimeEnd ? " and a.create_time <=".strtotime($createTimeEnd) : '';
        $where .= $createTimeStart ? " and a.create_time >=".strtotime($createTimeStart) : '';
        $where .= $id ? " and a.uid =".$id : '';
        $where .=$nickName ? " and u.nick_name like '%".$nickName."%'" : '';

        $AgencyPromotion = new AgencyPromotion();
        $list = $AgencyPromotion -> get_host_list($where,$page,$limit);
        $root['data'] = array(
            'current' => $list['current_page'],
            'pages' => $list['last_page'],
            'size' => $list['per_page'],
            'total' =>$list['total'],
            'records' => $list['data']
        );
        return_json_encode($root);;
    }
    /**
    * 解除推广用户
     */
    public function save_member_status(){
        $root = array('code' => 200, 'message' => apiLang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $id = intval(input('param.id'));
        $user_info = redis_get_token($token);

        $where ="status = 1 and agency_id =".$user_info['id']." and id=".$id;
        $data= array(
            'status' => 2,
            'endtime' => NOW_TIME
        );
        $AgencyPromotion = new AgencyPromotion();
        $result = $AgencyPromotion -> save_update($where,$data);
        if (!$result) {
            $root['code'] = 0;
            $root['message'] = apiLang("operation_failed");
        }

        return_json_encode($root);;
    }
    /**
    * 获取用户信息
     */
    public function get_user_info(){
        $root = array('code' => 200, 'message' => '', 'data' => array());
        $token = strim(input('param.token'));
        $user_info = redis_get_token($token);
        $Agency = new Agency();
        $user_info = $Agency -> select_Agency_one("id =".$user_info['id']);
        $root['data'] = $user_info;
        return_json_encode($root);;
    }
    /**
    * 修改用户信息
     */
    public function save_user_info(){
        $root = array('code' => 200, 'message' => apiLang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $name = strim(input('param.name'));
        $tel = strim(input('param.tel'));
        $user_info = redis_get_token($token);

        $where ="id =".$user_info['id'];
        $data= array(
            'name' => $name,
            'tel' => $tel
        );
        $Agency = new Agency();
        $result = $Agency -> save_admin($where,$data);
        if (!$result) {
            $root['code'] = 0;
            $root['message'] = apiLang("operation_failed");
        }else{
            $login = $Agency ->select_Agency_one($where);
            redis_set($token, json_encode($login), get_d());
        }

        return_json_encode($root);;
    }
    /**
    * 修改密码
     */
    public function update_psd(){
        $root = array('code' => 200, 'message' => apiLang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $old_psd = strim(input('param.old_psd'));
        $psd = strim(input('param.psd'));
        $user_info = redis_get_token($token);

        if ($user_info['psd'] != cmf_password($old_psd)) {
            $root['code'] = 0;
            $root['message'] = apiLang("Original_password_error");
            return_json_encode($root);;
        }
        if (strlen($psd) < 6) {
            $root['code'] = 0;
            $root['message'] = apiLang("passwordNotLessThan6");
            return_json_encode($root);;
        }
        $where ="id =".$user_info['id'];

        $data= array(
            'psd' => cmf_password($psd)
        );
        $Agency = new Agency();
        $result = $Agency -> save_admin($where,$data);
        if (!$result) {
            $root['code'] = 0;
            $root['message'] = apiLang("operation_failed");
        }
        return_json_encode($root);;
    }
    /**
    * 检测用户是否存在
     */
    public function detection_user(){
        $root = array('code' => 200, 'message' => apiLang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $touid = intval(input('param.touid'));
        $user_info = redis_get_token($token);
        $where ="id =".$touid. " or luck=".$touid;

        $User = new User();
        $result = $User -> get_user_one($where,"user_nickname as nick_name");
        $nick_name = '';
        if ($result) {
            $nick_name = $result['nick_name'];
        }
        $root['data']= array(
            'nick_name' => $nick_name
        );
        return_json_encode($root);;
    }
    /**
    * 检测代理是否存在
     */
    public function detection_agency(){
        $root = array('code' => 200, 'message' => apiLang("Operation_succeeded"), 'data' => array());
        $token = strim(input('param.token'));
        $agency_id = intval(input('param.agency_id'));
        $user_info = redis_get_token($token);
        $where ="id =".$agency_id;

        $Agency = new Agency();
        $result = $Agency -> select_Agency_one($where,"name,platform_level,first_superior_id,two_superior_id");
        $nick_name = '';
        if ($result) {
            $nick_name = $result['name'];
        }
        if($result) {
            if ($user_info['platform_level'] == 1) {
                if ($result['first_superior_id'] !=$user_info['id']) {
                    $nick_name = '';
                }
            }elseif($user_info['platform_level'] == 2){
                if ($result['two_superior_id'] !=$user_info['id']) {
                    $nick_name = '';
                }
            }
        }
        $root['data']= array(
            'nick_name' => $nick_name
        );
        return_json_encode($root);;
    }
}
