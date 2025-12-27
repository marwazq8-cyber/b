<?php

namespace app\api\controller;
require_once DOCUMENT_ROOT . '/system/im_common.php';

use think\helper\Time;
use UserOnlineStateRedis;
use app\api\model\VoiceModel;
use app\api\model\UserModel;

/*语音直播间追加接口*/

class VoiceAdditionalApi extends Base
{

    protected function _initialize()
    {
        parent::_initialize();

        $this->VoiceModel = new VoiceModel();
        $this->UserModel = new UserModel();
    }

    // 获取公告内容
    public function get_announcement()
    {

        $result = array('code' => 1, 'msg' => '');
        // 用户id
        $uid = intval(input('param.uid'));
        // 直播间信息
        $voice = $this->VoiceModel->sel_voice_user_one($uid);

        $result['data']['announcement'] = $voice['announcement'];

        return_json_encode($result);
    }

    //编辑公告
    public function upd_announcement()
    {

        $result = array('code' => 1, 'msg' => lang('Modified_successfully'));

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);
        // 修改的内容
        $announcement = trim(input('param.announcement'));

        $name = array('announcement' => $announcement);
        // 修改公告
        $voice_status = $this->VoiceModel->upd_user_voice($uid, $name);

        if (!$voice_status) {
            $result['code'] = '0';
            $result['msg'] = lang('operation_failed');
        }

        return_json_encode($result);
    }

    //用户坐等上麦列表
    public function waiting_wheat()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 获取上麦人数
        $list = $this->VoiceModel->get_voice_even_wheat_log_list($voice_id, '0');
        // 是否申请上麦0否1审核中2已在麦上
        $voice_status = 0;
        // 查询用户是否在麦位上
        $user_type = $this->VoiceModel->get_voice_even_wheat_log_one($voice_id, $uid, '1');

        if ($user_type) {

            $voice_status = 2;
        } else {

            foreach ($list as $v) {

                if ($v['user_id'] == $uid) {
                    $voice_status = 1;
                }
            }
        }

        $result['voice_status'] = $voice_status;
        $result['voice_count'] = count($list);
        $result['list'] = $list;

        return_json_encode($result);
    }

    // 获取表情分类列表
    public function room_memes_type_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $list = db('room_memes_type')->field("id,name,img")->where("status=1")->order("sort desc")->select();
        $result['list'] = $list;
        return_json_encode($result);
    }

    //语音表情包列表
    public function room_memes_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $memes_type_id = intval(input('param.memes_type_id')); // 表情分类id
        $where = "last_id=0 and status=1";
        $where .= $memes_type_id ? " and memes_type_id =" . $memes_type_id : '';
        $list = db('room_memes')->where($where)->order("sort desc")->select();
        $result['list'] = $list;
        return_json_encode($result);
    }

    //展示语音表情包列表
    public function random_room_memes_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('param.id'));
        $where = "l.type = 1 and r.status=1";
        if ($id) {
            $where .= " and l.id =" . $id;
        }
        $room_memes = db('room_memes')->alias("r")
            ->join("room_memes l", "l.id=r.last_id")
            ->where($where)
            ->field("r.*")
            ->order("r.sort desc")
            ->select();
        if (count($room_memes) > 0) {
            $sum = count($room_memes) - 1;
            $i = mt_rand(0, $sum);
            $url = $room_memes[$i]['img'];
        } else {
            $url = '';
        }
        $result['data']['url'] = $url;
        return_json_encode($result);
    }

    //搜索直播间
    public function search_room()
    {
        $result = array('code' => 1, 'msg' => '');
        $key_word = trim(input('param.key_word'));
        $string = 'select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|and|union|order|or|into|load_file|outfile';
        $arr = explode('|', $string);
        $key_word = str_ireplace($arr, '', $key_word);
        $result['list'] = db('voice')->alias("l")->field('l.avatar,l.id,l.title,l.voice_status,l.voice_psd,l.user_id,u.luck')
            ->join('user u', 'l.user_id=u.id')
            ->where("l.status !=0 and l.live_in=1 and (l.user_id like '%" . $key_word . "%' or u.luck like '%" . $key_word . "%' or l.title like '%" . $key_word . "%')")
            ->select();
        foreach ($result['list'] as &$v) {
            //获取房间人数
            $v['sum'] = voice_userlist_sum($v['user_id']);
        }
        return_json_encode($result);
    }

    //记录搜索直播间
    public function search_room_log()
    {
        $result = array('code' => 1, 'msg' => '');
        $key_word = trim(input('param.key_word'));
        $string = 'select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|and|union|order|or|into|load_file|outfile';
        $arr = explode('|', $string);
        $key_word = str_ireplace($arr, '', $key_word);
        $uid = intval(input('param.uid'));
        $type = intval(input('param.type'));
        $data = array(
            'name' => $key_word,
            'uid' => $uid,
            'type' => $type,
            'addtime' => NOW_TIME,
        );
        $search = db('search_log')->where("name='" . $key_word . "' and uid=$uid")->find();
        if (!$search) {
            db('search_log')->insert($data);   //记录搜索
        }
        return_json_encode($result);
    }

    //清空搜索直播间数据
    public function search_empty()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $type = intval(input('param.type'));
        $where = "uid=$uid";
        $where .= $type > 0 ? " and type=" . $type : '';
        db('search_log')->where($where)->delete();
        return_json_encode($result);
    }
}