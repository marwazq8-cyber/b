<?php
namespace app\api\controller;
use think\Model;
use think\Db;

class VoiceDataController extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        header('Access-Control-Allow-Origin:*');
    }
    public function JoinToRoom() {
        $uid = input('param.uid');
        $token = input('param.token');
        $voice_id = input('param.voice_id');
        check_login_token($uid, $token);
        $data = [
            'user_id' => $uid,
            'voice_id' => $voice_id,
        ];
        $voice = Db::name('voice_room_users')->where('user_id', $uid)->where('voice_id', $voice_id)->find();
        $data2 = [
            'JoinTime' => date('Y-m-d H:i:s', time())
        ];
        if($voice){
            $updateVoice = Db::name('voice_room_users')
                ->where('user_id', $uid)
                ->where('voice_id', $voice_id)
                ->update($data2);
            return json(['code' => 200,'message' => 'You Joined to room Again!']);

        }
        $voice = Db::name('voice_room_users')->insertGetId($data);
        
        if ($voice) {
            return json(['code' => 200,'success' => 'Joined to room successfully']);
        }else {
            return json(['code' => 400, 'Task Field' => 'Join to room Field']);
        }
    }
    public function GetVoiceUserRooms() {
        $result = array('code' => 200, 'msg' => '', 'data' => array());
        $user_id = input('param.uid');
        $data = Db::name('voice_room_users u')
            ->join('voice v', 'v.id = u.voice_id')
            ->where('u.user_id' , $user_id)
            ->order('u.JoinTime desc')
            ->limit(10)
            ->select();
        $result['data'] = $data;
        return_json_encode($result);
    }
}