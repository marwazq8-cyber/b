<?php
namespace app\api\controller;
use think\Model;
use think\Db;

class FamilyController extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        header('Access-Control-Allow-Origin:*');
    }
    public function CreateFamily() {
        

        $file = request()->file('file');
        if (!$file) {
            return json(['error' => 'No file uploaded'], 400);
        }
        $uid = request()->post('uid');
        $token = request()->post('token');
        $uploadPath = $file->move('uploads');
        $filePath = $uploadPath ? 'uploads/' . $uploadPath->getSaveName() : null;
        

        check_login_token($uid, $token);
        
        $data = [
            'owner_id' => $uid,
            'Family_name' => request()->post('Family_name'),
            'Family_icon' => $filePath,
            'Family_desc' => request()->post('Family_desc'),
        ];
        $family = Db::name('Family')->insertGetId($data);
        $member = Db::name('family_users')->insert(
            ["user_id" => $uid,"family_id" => $family,"status" => 1]);
        if ($family) {
            return json(['code' => 200, 'success' => 'Family createdsuccessfully']);
        }else {
            return json(['code' => 400, 'Task Field' => 'Family created Field']);
        }
        
    }
    public function GetFamilyByUserID() {
        $uid = input('param.uid');
        $result = array('code' => 200, 'msg' => '', 'data' => array());
        $data = DB::table('bogo_family_users')
        ->where('user_id', $uid)
        ->where('status' , 1)
        ->select();
        if(!$data){
            $result["data"] = "user hasn't family";
            return_json_encode($result);
        }
              
        $numberofmembers = DB::table('bogo_family_users')
        ->where('family_id', $data[0]['family_id'])
        ->where('status' , 1)
        ->count();
        $familydata = DB::table('bogo_family')
        ->where('id', $data[0]['family_id'])
        ->select();
        $userdata = DB::table('bogo_user')
        ->where('id', $familydata[0]['owner_id'])
        ->select();

        $result['data']['Family'] = $familydata[0];
        $result['data']['numberofmembers'] = $numberofmembers;
        $result['data']['owner'] = $userdata[0];
        return_json_encode($result);
    }
    public function GetFamilyByFamilyId() {
        $family_id = input('param.family_id');
        $result = array('code' => 200, 'msg' => '', 'data' => array());
        $familydata = DB::table('bogo_family')
        ->where('id', $family_id)
        ->select();
        $userdata = DB::table('bogo_user')
        ->where('id', $familydata[0]['owner_id'])
        ->select();
        $numberofmembers = DB::table('bogo_family_users')
        ->where('family_id', $familydata[0]['id'])
        ->where('status' , 1)
        ->count();

        $result['data']['Family'] = $familydata[0];
        $result['data']['numberofmembers'] = $numberofmembers;
        $result['data']['FamilyOwner'] = $userdata[0];

        return_json_encode($result);
    }
    public function GetAllFamiles()  {
        $result = array('code' => 200, 'msg' => '', 'data' => array());
        $familydata = DB::table('bogo_family')
        ->alias('f')
        ->join('bogo_user owner', 'f.owner_id = owner.id')
        ->where('f.Active', 1) 
        ->field('f.*, owner.user_nickname,owner.avatar as owner_avatar,owner.level') 
        ->select();
        $result['data'] = $familydata;
        return_json_encode($result);
    }
    public function RequestToJoinToFamily()  {
        $result = array('code' => 200, 'msg' => '', 'data' => array());
        $uid = request()->post('uid');
        $token = request()->post('token');
        $family = request()->post('family_id');
        check_login_token($uid, $token);
        $data = DB::table('bogo_family_users')
        ->where('user_id', $uid)
        ->where('status' , 1)
        ->select();
        if($data){
            $result["message"] = "You already Have one";
            return_json_encode($result);
        }
        $member = Db::name('family_users')->insert(
            ["user_id" => $uid,"family_id" => $family,"status" => 2]);
        $result["message"] = "Done, Wait for the owner To Accept you";
        return_json_encode($result);
    }
    public function LeaveFamily()  {
        $result = array('code' => 200, 'msg' => '', 'data' => array());
        $uid = request()->post('uid');
        $token = request()->post('token');
        $family = request()->post('family_id');
        $data = DB::table('bogo_family_users')
        ->where('family_id', $family)
        ->where('user_id', $uid) 
        ->update(['status' => 3]); 
        $result['msg'] = "Done You are out";
        return_json_encode($result);
    }
    public function ListofUserRequestToJoin() {
        $result = array('code' => 200, 'msg' => '', 'data' => array());
        $uid = input('param.uid');
        $familydata = DB::table('bogo_family')
            ->where('owner_id' , $uid)
            ->select();
        $data = DB::table('bogo_family_users')
        ->where('family_id', $familydata[0]['id'])
        ->where('status' , 2)
        ->select();
        $result['data'] = $data;
        return_json_encode($result);
        
    }
}