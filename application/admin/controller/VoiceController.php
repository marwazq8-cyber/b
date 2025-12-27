<?php

namespace app\admin\controller;

use app\api\model\VoiceModel;
use app\common\Enum;
use app\voice\controller\VoiceApi;
use cmf\controller\AdminBaseController;
use think\Model;

class VoiceController extends AdminBaseController
{
    //语音直播列表
    public function index()
    {

        $where = "v.live_in != 0";
        if ($this->request->param('user_id')) {
            $where .= " and v.user_id=" . intval($this->request->param('user_id'));
        }
        if ($this->request->param('voice_id')) {
            $where .= " and v.id=" . intval($this->request->param('voice_id'));
        }
        if($this->request->param('status') != '-1'){
            $where .= $this->request->param('status') == '0' ? " and v.status=0" : " and v.status=1";
        }
        if ($this->request->param('voice_type')) {
            $where .= " and v.voice_type=" . intval($this->request->param('voice_type'));
        }
        if ($this->request->param('type') == '2' || $this->request->param('type') == '1') {
            $where .= " and v.room_type=" . intval($this->request->param('type'));
        }
        if ($this->request->param('voice_status') == '0' || $this->request->param('voice_status') == '1') {
            $where .= " and v.voice_status=" . intval($this->request->param('voice_status'));
        }

        $list = db('voice')->alias("v")
            ->join("voice_type t", "t.id=v.voice_type")
            ->join("user u", "v.user_id=u.id")
            ->where($where)
            ->field("t.name,v.*,u.user_nickname,u.sex,u.reference")
            ->group("v.user_id")
            ->order("v.sort DESC,v.id desc")
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();
        $page = $list->render();
        $voice_type = db('voice_type')->where("status=1")->order("sort desc")->select();

        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('voice_type', $voice_type);
        return $this->fetch();
    }

    //增加语音房间
    public function add_index()
    {
        $id = $this->request->param('id');
        $voice_type = db('voice_type')->where("status=1")->order("sort desc")->select();
        $voice_bg = db('voice_bg')->order("sort DESC")->select();
        if ($id) {
            $list = db('voice')->where("id=" . $id)->find();
        } else {
            $list['voice_status'] = 0;
            $list['status'] = 1;
            $list['type'] = 1;
            $list['avatar'] = '';
            $list['voice_type'] = 1;
            $list['room_type'] = 1;
            $list['voice_bg'] = $voice_bg[0]['id'];
        }

        $this->assign('voice_bg', $voice_bg);
        $this->assign('voice_type', $voice_type);
        $this->assign('list', $list);
        return $this->fetch();
    }

    //创建房间
    public function addPost_index()
    {
        $param = $this->request->param();
        $data = $param['post'];

        $id = $param['id'];
        if (empty($data['title'])) {
            $this->error(lang('Please_enter_room_title'));
            exit;
        }
        if (empty($data['user_id'])) {
            $this->error(lang('Please_enter_user_ID'));
            exit;
        }
        $user = db('user')->where("user_type = 2 and id=" . $data['user_id'])->find();
        if (!$user) {
            $this->error(lang('user_does_not_exist'));
            exit;
        }
        $user_voice = db('voice')->where("user_id=" . $data['user_id'])->find();
        if ($user_voice && empty($id)) {
            $this->error(lang('Room_already_exists'));
            exit;
        }
        if ($data['room_type'] == 2) {
            $wheat_type = [
                ['wheat_id' => 1, 'type' => 0], ['wheat_id' => 2, 'type' => 1], ['wheat_id' => 3, 'type' => 0], ['wheat_id' => 4, 'type' => 0], ['wheat_id' => 5, 'type' => 0], ['wheat_id' => 6, 'type' => 0], ['wheat_id' => 7, 'type' => 0], ['wheat_id' => 8, 'type' => 0], ['wheat_id' => 9, 'type' => 0], ['wheat_id' => 10, 'type' => 0], ['wheat_id' => 11, 'type' => 0]
            ];
            $data['wheat_type'] = json_encode($wheat_type, true);
        } else {
            $wheat_type = [
                ['wheat_id' => 1, 'type' => 0], ['wheat_id' => 2, 'type' => 1], ['wheat_id' => 3, 'type' => 0], ['wheat_id' => 4, 'type' => 0], ['wheat_id' => 5, 'type' => 0], ['wheat_id' => 6, 'type' => 0], ['wheat_id' => 7, 'type' => 0], ['wheat_id' => 8, 'type' => 0], ['wheat_id' => 9, 'type' => 0], ['wheat_id' => 10, 'type' => 0], ['wheat_id' => 11, 'type' => 0]
            ];
            $data['wheat_type'] = json_encode($wheat_type, true);
        }
        if ($id) {
            $result = db('voice')->where("id=" . $id)->update($data);
        } else {
            $data['create_time'] = NOW_TIME;
            $result = db('voice')->insertGetId($data);
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            //创建群组
            $api = createTimAPI();
            $ret = $api->group_create_group('AVChatRoom', $result, 'admin');

            if ($ret['ActionStatus'] != 'OK') {
                $this->error(lang('Failed_to_create_group'));
            }
            $name = array('group_id' => $ret['GroupId']);
            db('voice')->where("id=" . $result)->update($name);
        }

        if ($result) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    /*
     * 删除语音直播间
     * */
    public function voice_del()
    {
        $id = intval(input('id'));
        //直播间信息
        $voice = db('voice')->find($id);
        $res = false;
        if ($voice) {

            $res = db('voice')->where('id = ' . $voice['id'])->delete();
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            // 发送消息通知用户退出房间
            $api = createTimAPI();
            $config = load_cache('config');
            $broadMsg['type'] = Enum::CLOSE_ROOM;
            #构造rest API请求包
            $msg_content = array();
            //创建$msg_content 所需元素
            $msg_content_elem = array(
                'MsgType'    => 'TIMCustomElem',       //定义类型为普通文本型
                'MsgContent' => array(
                    'Data' => json_encode($broadMsg)    //转为JSON字符串
                )
            );

            //将创建的元素$msg_content_elem, 加入array $msg_content
            array_push($msg_content, $msg_content_elem);
            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);

            //销毁群组
            $api = createTimAPI();
            $ret = $api->group_destroy_group($voice['group_id']);
            unset($voice['id']);

            $voice['status'] = 2;
            $voice['live_in'] = 0;

            db('voice_log')->insertGetId($voice);
        }
        return $res ? 1 : 0;
    }

    //语音频道分类
    public function type()
    {
        $list = db('voice_type')->order("sort DESC")->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    //语音频道分类添加
    public function add()
    {

        $id = $this->request->param('id');
        if ($id) {
            $list = db('voice_type')->where("id=" . $id)->find();
        } else {
            $list['status'] = 1;
            $list['type'] = 1;
            $list['img'] = '';
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    //删除
    public function del()
    {
        $id = input('param.id');
        $result = db('voice_type')->where("id=" . $id)->delete();
        if ($result !== false) {
            $this->success(lang('DELETE_SUCCESS'));
        } else {
            $this->error(lang('DELETE_FAILED'));
        }
    }

    //增加语音频道类型
    public function addpost()
    {
        $id = input('param.id');
        $data['name'] = input('param.name');
        $data['status'] = input('param.status');
        $data['sort'] = input('param.sort');
        $data['type'] = input('param.type', 1);
        $data['img'] = input('param.img');

        if ($data['name'] == '') {
            $this->error(lang('Please_enter_voice_channel_type_name'));
        }

        if ($id) {
            $result = db('voice_type')->where("id=" . $id)->update($data);
        } else {
            $result = db('voice_type')->insertGetId($data);
        }

        if ($result) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    // 房间标签
    public function voice_label()
    {

        $where = "l.id >0";
        if ($this->request->param('voice_type')) {
            $where .= " and l.voice_type_id=" . intval($this->request->param('voice_type'));
        }
        if ($this->request->param('type') == '2' || $this->request->param('type') == '1') {
            $where .= " and t.type=" . intval($this->request->param('type'));
        }

        $list = db('voice_label')->alias("l")
            ->join("voice_type t", "t.id=l.voice_type_id")
            ->where($where)
            ->field("l.*,t.name as tname,t.type")
            ->order("l.sort desc")
            ->select();

        $voice_type = db('voice_type')->where("status=1")->order("sort desc")->select();

        $this->assign('voice_type', $voice_type);

        $this->assign('list', $list);
        return $this->fetch();
    }

    //语音频道分类添加
    public function add_voice_label()
    {

        $id = $this->request->param('id');
        $voice_type = db('voice_type')->where("status=1")->order("sort DESC")->select();
        if ($id) {
            $list = db('voice_label')->where("id=" . $id)->find();
        } else {
            $list['status'] = 1;
            $list['voice_type_id'] = 0;
        }

        $this->assign('list', $list);
        $this->assign('voice_type', $voice_type);
        return $this->fetch();
    }

    // 提交房间标签
    public function addPost_voice_label()
    {
        $param = $this->request->param();
        $data = $param['post'];
        $id = $param['id'];

        if ($data['name'] == '') {
            $this->error(lang('Please_enter_room_label_name'));
        }

        if ($id) {
            $result = db('voice_label')->where("id=" . $id)->update($data);
        } else {
            $result = db('voice_label')->insertGetId($data);
        }

        if ($result) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    //删除房间标签
    public function del_voice_label()
    {
        $id = input('param.id');
        $result = db('voice_label')->where("id=" . $id)->delete();
        if ($result !== false) {
            $this->success(lang('DELETE_SUCCESS'));
        } else {
            $this->error(lang('DELETE_FAILED'));
        }
    }


    //语音房间背景色
    public function voice_bg()
    {
        $list = db('voice_bg')->order("sort DESC")->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    //添加背景图片
    public function voice_bg_add()
    {
        $id = $this->request->param('id');
        if ($id) {
            $list = db('voice_bg')->where("id=" . $id)->find();
        } else {
            $list['status'] = 1;
            $list['image'] = '';
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    //增加语音频道类型
    public function voice_bgPost()
    {
        $id = input('param.id');
        $data['image'] = input('param.image');
        $data['status'] = input('param.status');
        $data['sort'] = input('param.sort');
        $data['name'] = input('param.name');

        if ($data['image'] == '') {
            $this->error(lang('Please_upload_voice_room_background'));
        }

        if ($id) {
            $result = db('voice_bg')->where("id=" . $id)->update($data);
        } else {
            $result = db('voice_bg')->insertGetId($data);
        }

        if ($result) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    //删除
    public function voice_bg_del()
    {
        $id = input('param.id');
        $result = db('voice_bg')->where("id=" . $id)->delete();
        if ($result !== false) {
            $this->success(lang('DELETE_SUCCESS'));
        } else {
            $this->error(lang('DELETE_FAILED'));
        }
    }

    //表情包
    public function room_memes()
    {

        $list = db('room_memes')->alias("r")
            ->join("room_memes_type t", "t.id=r.memes_type_id", 'left')
            ->order("r.sort desc")
            ->field("r.*,t.name as tname")
            ->where("r.last_id=0")
            ->select();

        $this->assign('list', $list);

        return $this->fetch();
    }

    //编辑
    public function add_room_memes()
    {
        $id = $this->request->param('id');
        $room_memes_type = db('room_memes_type')->where("status=1")->order("sort desc")->select();
        if ($id) {
            $list = db('room_memes')->where("id=" . $id)->find();
        } else {
            $list['status'] = 1;
            $list['type'] = 1;
            $list['img'] = '';
            $list['memes_type_id'] = 0;
        }

        $this->assign('room_memes_type', $room_memes_type);
        $this->assign('list', $list);
        return $this->fetch();
    }

    //提交表情包
    public function addPost_room_memes()
    {
        $id = input('param.id');
        $data['img'] = input('param.img');
        $data['name'] = input('param.name');
        $data['sort'] = input('param.sort');
        $data['status'] = input('param.status');
        $data['type'] = input('param.type');
        //    $data['memes_type_id'] = intval(input('param.memes_type_id'));

        if ($data['name'] == '') {
            $this->error(lang('Please_upload_Emoji_name'));
        }
        if ($data['img'] == '') {
            $this->error(lang('Please_upload_Emoji_pictures'));
        }

        if ($id) {
            $result = db('room_memes')->where("id=" . $id)->update($data);
        } else {
            $result = db('room_memes')->insertGetId($data);
        }

        if ($result) {
            $this->success(lang('Operation_successful'), url('Voice/room_memes'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    //删除表情包
    public function del_room_memes()
    {
        $id = input('param.id');
        $result = db('room_memes')->where("id=" . $id)->delete();
        if ($result !== false) {
            $this->success(lang('DELETE_SUCCESS'));
        } else {
            $this->error(lang('DELETE_FAILED'));
        }
    }

    //随机表情列表
    public function random_room_memes()
    {
        $id = input('param.id');
        $list = db('room_memes')->alias("r")
            ->join("room_memes l", "l.id=r.last_id")
            ->where("l.id =" . $id)
            ->field("r.*,l.name as lname")
            ->order("r.sort desc")
            ->select();

        $this->assign('list', $list);
        $this->assign('last_id', $id);
        return $this->fetch();
    }

    //编辑随机表情
    public function add_random_room_memes()
    {
        $id = $this->request->param('id');
        $last_id = $this->request->param('last_id');
        $room_memes = db('room_memes')->where("id=" . $last_id)->find();

        if ($id) {
            $list = db('room_memes')->where("id=" . $id)->find();
        } else {
            $list['status'] = 1;
            $list['img'] = '';
        }

        $this->assign('last_id', $last_id);
        $this->assign('list', $list);
        $this->assign('room_memes_name', $room_memes['name']);
        return $this->fetch();
    }

    //提交表情包
    public function addPost_random_room_memes()
    {
        $id = input('param.id');
        $data['img'] = input('param.img');
        $data['name'] = input('param.name');
        $data['sort'] = input('param.sort');
        $data['status'] = input('param.status');
        $data['last_id'] = input('param.last_id');


        if ($data['name'] == '') {
            $this->error(lang('Please_upload_Emoji_name'));
        }
        if ($data['img'] == '') {
            $this->error(lang('Please_upload_Emoji_pictures'));
        }

        if ($id) {
            $result = db('room_memes')->where("id=" . $id)->update($data);
        } else {
            $result = db('room_memes')->insertGetId($data);
        }

        if ($result) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    // 表情包分类列表
    public function room_memes_type()
    {
        $list = db('room_memes_type')->order("sort desc")->select();

        $this->assign('list', $list);

        return $this->fetch();
    }

    // 编辑表情包分类
    public function add_room_memes_type()
    {
        $id = $this->request->param('id');
        if ($id) {
            $list = db('room_memes_type')->where("id=" . $id)->find();
        } else {
            $list['status'] = 1;
            $list['img'] = '';
            $list['name'] = '';
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 提交表情分类信息
    public function addPost_room_memes_type()
    {
        $id = input('param.id');
        $data['img'] = input('param.img');
        $data['name'] = input('param.name');
        $data['sort'] = input('param.sort');
        $data['status'] = input('param.status');

        if ($data['name'] == '') {
            $this->error(lang('Please_enter_expression_classification_name'));
        }
        if ($data['img'] == '') {
            $this->error(lang('Please_upload_expression_classification_pictures'));
        }

        if ($id) {
            $result = db('room_memes_type')->where("id=" . $id)->update($data);
        } else {
            $data['create_time'] = time();
            $result = db('room_memes_type')->insertGetId($data);
        }

        if ($result) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    //排序
    public function upd_index_sort()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['sort'] as $k => $v) {
            $status = Db("voice")->where("id=$k")->update(array('sort' => $v));
            if ($status) {
                $data = $status;
            }
        }
        if ($data) {
            $this->success(lang('Sorting_succeeded'));
        } else {
            $this->error(lang('Sorting_error'));
        }
    }

    //语音直播收益记录
    public function earnings_log()
    {
        $where = "g.type=2";   //语音房间
        if ($this->request->param('user_id')) {
            $where .= " and g.user_id=" . intval($this->request->param('user_id'));
        }
        if ($this->request->param('to_user_id')) {
            $where .= " and g.to_user_id=" . intval($this->request->param('to_user_id'));
        }
        if ($this->request->param('voice_id')) {
            $where .= " and v.user_id=" . intval($this->request->param('voice_id'));
        }
        if ($this->request->param('start_time')) {
            $where .= " and g.create_time >=" . strtotime($this->request->param('start_time'));
        }
        if ($this->request->param('end_time')) {
            $where .= " and g.create_time <=" . strtotime($this->request->param('end_time'));
        }

        $list = db('voice')->alias("v")
            ->join("voice_even_wheat_log l", "l.voice_id=v.user_id or l.voice_id=v.user_id")
            ->join("user_gift_log g", "l.id=g.voice_log_id")
            ->join("user u", "v.user_id=u.id")
            ->join("user s", "g.user_id=s.id")
            ->join("user t", "g.to_user_id=t.id")
            ->where($where)
            ->field("t.user_nickname as tname,s.user_nickname as sname,u.user_nickname as uname,g.*,l.voice_id")
            ->order("g.create_time DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();
        $page = $list->render();

        $sum = db('voice')->alias("v")
            ->join("voice_even_wheat_log l", "l.voice_id=v.id or l.voice_id=v.user_id")
            ->join("user_gift_log g", "l.id=g.voice_log_id")
            ->where($where)
            ->field("sum(g.gift_coin) as gift_coin,sum(g.profit) as profit,sum(g.voice_profit) as voice_profit")
            ->order("g.create_time DESC")
            ->find();
        $this->assign('sum', $sum);

        $this->assign('list', $data['data']);
        $this->assign('page', $page);

        return $this->fetch();
    }

    /*
     * 房间流水*/
    public function voice_details()
    {
        //语音标题 ——房间ID——用户ID——房间类型——昨日——今日——本周——上周——累计总流水——创建时间——查询更多
        $where = 'v.id > 0 ';
        $id = input('id');
        $user_id = input('user_id');
        $type = input('type', -1);
        if ($user_id) {
            session('request.user_id', $user_id);
            $where .= ' and v.user_id = ' . $user_id;
        }
        //dump($type);die();
        if ($type > 0) {
            session('request.type', $type);
            if ($type == 1) {
                $list = db('voice')
                    ->alias('v')
                    ->join('user u', 'u.id=v.user_id')
                    ->join('voice_type t', 't.id=v.voice_type')
                    ->join('user_gift_log l', 'l.voice_user_id = v.user_id')
                    ->field('v.id,v.title,v.user_id,t.name,u.user_nickname,sum(l.gift_coin) as total,v.create_time')
                    ->where('v.live_in = 1')
                    ->where($where)
                    ->group('v.user_id')
                    ->order('total desc')
                    ->limit(1)
                    ->select();
            } else {
                $list = db('voice')
                    ->alias('v')
                    ->join('user u', 'u.id=v.user_id')
                    ->join('voice_type t', 't.id=v.voice_type')
                    ->join('user_gift_log l', 'l.voice_user_id = v.user_id')
                    ->field('v.id,v.title,v.user_id,t.name,u.user_nickname,sum(l.gift_coin) as total,v.create_time')
                    ->where('v.live_in = 1')
                    ->where($where)
                    ->group('v.user_id')
                    ->order('total asc')
                    ->limit(1)
                    ->select();
            }
            $list_arr = $list->toArray();
            $list_tow = $list_arr;
            $page = null;
        } else {
            session('request.type', -1);
            $list = db('voice')
                ->alias('v')
                ->join('user u', 'u.id=v.user_id')
                ->join('voice_type t', 't.id=v.voice_type')
                ->join('user_gift_log l', 'l.voice_user_id = v.user_id', 'LEFT')
                ->field('v.id,v.title,v.user_id,u.user_nickname,t.name,sum(l.gift_coin) as total,v.create_time')
                ->where('v.live_in = 1')
                ->where($where)
                ->order('total desc')
                ->group('v.user_id')
                ->paginate(10, false, ['query' => request()->param()]);
            $list_arr = $list->toArray();
            $list_tow = $list_arr['data'];
            $page = $list->render();
        }

        //今日
        $day_time = strtotime(date('Y-m-d', NOW_TIME));
        //昨日
        $yesterday = strtotime(date('Y-m-d', NOW_TIME) . '-1 day');

        //本周起止时间
        $beginThisweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('y'));
        //上一周起止时间
        $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') - 6, date('y'));
        $endLastWeek = mktime(23, 59, 59, date('m'), date('d') - date('w'), date('y'));


        foreach ($list_tow as &$v) {
            //今日收益
            $v['day_total'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time > ' . $day_time)->sum('gift_coin');
            //昨日收益
            $v['yesterday_total'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time > ' . $yesterday . ' and create_time < ' . $day_time)->sum('gift_coin');
            //本周收益
            $v['week_total'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time > ' . $beginThisweek)->sum('gift_coin');
            //上周收益
            $v['top_week_total'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time > ' . $beginLastweek . ' and create_time < ' . $endLastWeek)->sum('gift_coin');
        }
        //dump();die();

        //dump($list_arr);die();
        //$voice_type = db('voice_type')->where("status=1")->order("sort desc")->select();
        $this->assign('list', $list_tow);
        $this->assign('page', $page);
        $this->assign('data', session('request'));

        return $this->fetch();
    }

    //管理员列表
    public function voice_administrator()
    {
        // 房主id
        $voice_id = intval(input('param.voice_id'));

        $list = db('voice_administrator')->where("type = 1 and voice_id=" . $voice_id)->select();
        $list = $list->toArray();
        $user_list = [];
        if ($list) {
            $user_id = array_column($list, 'user_id');
            $user_id_str = implode(',', $user_id);
            $user_list = db('user')->field('id,user_nickname,avatar,sex')->where('id in (' . $user_id_str . ')')->select()->toArray();

            foreach ($user_list as &$v) {
                $v['addtime'] = '';
                foreach ($list as $val) {
                    if ($val['user_id'] == $v['id']) {
                        $v['addtime'] = $val['addtime'];
                    }
                }

            }
        }
        $this->assign('voice_id', $voice_id);
        $this->assign('list', $user_list);
        return $this->fetch();
    }

    // 删除管理员
    public function del_voice_administrator()
    {
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 要删除的用户id
        $to_user_id = intval(input('param.to_user_id'));
        $VoiceModel = new VoiceModel();
        // 查询语音房间
        //$sel_voice = $VoiceModel -> sel_voice_one($voice_id);

        // 删除管理员
        $upd_voice = $VoiceModel->del_voice_administrator($voice_id, $to_user_id, $voice_id);

        if ($upd_voice) {
            echo 1;
        } else {
            echo 0;
        }
    }

    // 添加房间管理员
    public function add_voice_administrator()
    {
        $result = ['code' => 0, 'msg' => ''];
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 连麦人id
        $to_user_id = intval(input('param.to_user_id'));
        $VoiceModel = new VoiceModel();
        $voice_administrator = $VoiceModel->get_voice_administrator_one($voice_id, $to_user_id);

        if ($voice_administrator) {
            $result['msg'] = lang('User_already_administrator');
            return_json_encode($result);
        }

        $name = array(
            'user_id'   => $to_user_id,
            'voice_id'  => $voice_id,
            'voice_uid' => $voice_id,
            'addtime'   => NOW_TIME,
        );
        // 加入管理员
        $administrator = $VoiceModel->add_voice_administrator($name);

        if ($administrator) {
            $result['code'] = 1;
            $result['msg'] = lang('ADD_SUCCESS');
            return_json_encode($result);
        } else {
            $result['msg'] = lang('ADD_FAILED');
            return_json_encode($result);
        }
    }

    // 主持人列表
    public function voice_host()
    {
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        //$VoiceModel = new VoiceModel();
        $list = db('voice_administrator')->where("type = 2 and voice_id=" . $voice_id)->select();
        $list = $list->toArray();
        $user_list = [];
        if ($list) {
            $user_id = array_column($list, 'user_id');
            $user_id_str = implode(',', $user_id);
            $user_list = db('user')->field('id,user_nickname,avatar,sex')->where('id in (' . $user_id_str . ')')->select()->toArray();

            foreach ($user_list as &$v) {
                $v['addtime'] = '';
                foreach ($list as $val) {
                    if ($val['user_id'] == $v['id']) {
                        $v['addtime'] = $val['addtime'];
                    }
                }

            }
        }
        $this->assign('voice_id', $voice_id);
        $this->assign('list', $user_list);
        return $this->fetch();
    }

    // 删除主持人
    public function del_voice_host()
    {
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 要删除的用户id
        $to_user_id = intval(input('param.to_user_id'));
        //$VoiceModel = new VoiceModel();
        // 查询语音房间
        //$sel_voice = $VoiceModel -> sel_voice_one($voice_id);
        $upd_voice = db('voice_administrator')
            ->where("type = 2 and user_id=" . $to_user_id . " and voice_id=" . $voice_id)
            ->delete();
        if ($upd_voice) {
            echo 1;
        } else {
            echo 0;
        }
    }

    // 添加主持人
    public function add_voice_host()
    {
        $result = ['code' => 0, 'msg' => ''];
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 连麦人id
        $to_user_id = intval(input('param.to_user_id'));
        $VoiceModel = new VoiceModel();
        $voice_administrator = db('voice_administrator')
            ->where("type = 2 and user_id=" . $to_user_id . " and voice_id=" . $voice_id)
            ->find();
        if ($voice_administrator) {
            $result['msg'] = lang('User_is_already_host');
            return_json_encode($result);
        }

        $name = array(
            'user_id'   => $to_user_id,
            'voice_id'  => $voice_id,
            'voice_uid' => $voice_id,
            'type'      => 2,
            'addtime'   => NOW_TIME,
        );
        // 加入管理员
        $administrator = $VoiceModel->add_voice_administrator($name);

        if ($administrator) {
            $result['code'] = 1;
            $result['msg'] = lang('ADD_SUCCESS');
            return_json_encode($result);
        } else {
            $result['msg'] = lang('ADD_FAILED');
            return_json_encode($result);
        }
    }

    // 麦位列表
    public function voice_wheat()
    {
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        $where = "l.status =1 and l.voice_id=" . $voice_id . " and (v.live_in = 1 or v.live_in = 3)";

        $list = db('voice_even_wheat_log')->alias("l")
            ->join("voice v", "v.user_id=l.voice_id")
            ->join("user u", "l.user_id=u.id")
            ->where($where)
            ->field("l.*,u.user_nickname,u.sex,v.title,v.id as vid")
            ->group("l.location")
            ->order("l.location asc")
            ->select();

        $this->assign('voice_id', $voice_id);
        $this->assign('list', $list);
        return $this->fetch();
    }
    // 退出房间
    public function exit_room()
    {
        $id = intval(input('param.id'));
        $where = "l.status =1 and l.id=" . $id . " and (v.live_in = 1 or v.live_in = 3)";

        $list = db('voice_even_wheat_log')->alias("l")
            ->join("voice v", "v.user_id=l.voice_id")
            ->join("user u", "l.user_id=u.id")
            ->where($where)
            ->field("l.*,u.user_nickname,u.avatar,u.level,u.sex,v.title,v.id as vid,v.group_id")
            ->find();

        if ($list) {
            $to_user_id = $list['user_id'];
            $VoiceModel = new VoiceModel();
            $name = array('status' => 3, 'endtime' => NOW_TIME);
            // 操作修改
            $VoiceModel->upd_voice_even_wheat_log($list['id'], $name);
            //房间人数减1
            db('voice')->where("id=" . $list['vid'])->setDec("online_number", 1);
            //解除禁言房间缓存
            redis_hDelOne('ban_voice_' . $list['voice_id'], $to_user_id);
            //删除直播间用户缓存
            voice_del_userlist($list['voice_id'], $to_user_id);
            //删除用户在直播间缓存
            redis_hDelOne("user_voice", $to_user_id);
            // 更新房间在线人数
            $online_number = voice_userlist_sum($list['voice_id']);
            db('voice')->where('user_id=' .$list['voice_id'])->update(array('online_number' => intval($online_number)));

            //退出语音房间
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

            $config = load_cache('config');
            $api = createTimAPI();
            $broadMsg['type'] = Enum::KICK_OUT_LIKE;
            $broadMsg['wheat_id'] = $list['voice_id'];
            $broadMsg['sender']['user_id'] = 1;
            $broadMsg['sender']['user_nickname'] = 'admin';
            $broadMsg['user']['user_id'] = $list['user_id'];
            $broadMsg['user']['user_nickname'] = $list['user_nickname'];
            $broadMsg['user']['avatar'] = $list['avatar'];
            $broadMsg['user']['user_level'] = $list['level'];
            #构造rest API请求包
            $msg_content = array();
            //创建$msg_content 所需元素
            $msg_content_elem = array(
                'MsgType'    => 'TIMCustomElem',       //定义类型为普通文本型
                'MsgContent' => array(
                    'Data' => json_encode($broadMsg)    //转为JSON字符串
                )
            );
            //将创建的元素$msg_content_elem, 加入array $msg_content
            array_push($msg_content, $msg_content_elem);
            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $list['group_id'], $msg_content);
        }
        echo $list ? 1 : 0;
    }
    // 获取在线观众列表
    public function voice_audience(){
        $voice_id = $this->request->param('voice_id');        //房主id


        //获取房间人数列表
        $list = voice_userlist_arsort($voice_id);
        $user_list = [];

        foreach ($list as &$v) {
            $value = json_decode($v, true);
            $user = get_user_base_info($value['user_id'], ['age']);

            if ($user['id'] != -1) {
                $value['age'] = $user['age'];
                $value['voice_id'] = $voice_id;
                $user_list[] = $value;
            } else {
                voice_del_userlist($voice_id, $value['user_id']);
            }
        }
        //获取房间人数
        $sum = voice_userlist_sum($voice_id);
        db('voice')->where('user_id=' .$voice_id)->update(array('online_number' => intval($sum)));

        $this->assign('sum', $sum);
        $this->assign('list', $user_list);
        return $this->fetch();
    }
    // 在线观众退出
    public function exit_room_user(){
        $id = intval(input('param.id')); // 用户id
        $vid = intval(input('param.vid')); // 房主id

        $voice = db('voice')->field("title,id,group_id,user_id")->where("user_id",$vid)->where("live_in = 1 or live_in = 3")->find();
        if($voice){
            $to_user_id = $id;
            $user = get_user_base_info($to_user_id, ['age']);
            $voice_even_wheat_log = db('voice_even_wheat_log')->where("voice_id=".$vid." and user_id = ".$id." and status=1")->find();

            if($voice_even_wheat_log){
                // 下麦
                $VoiceModel = new VoiceModel();
                $name = array('status' => 3, 'endtime' => NOW_TIME);
                // 操作修改
                $VoiceModel->upd_voice_even_wheat_log($voice_even_wheat_log['id'], $name);
            }
            //房间人数减1
          //  db('voice')->where("id=" . $voice['id'])->setDec("online_number", 1);
            //解除禁言房间缓存
            redis_hDelOne('ban_voice_' . $vid, $to_user_id);
            //删除直播间用户缓存
            voice_del_userlist($vid, $to_user_id);
            //删除用户在直播间缓存
            redis_hDelOne("user_voice", $to_user_id);
            $online_number = voice_userlist_sum($vid);
            db('voice')->where('user_id=' .$vid)->update(array('online_number' => intval($online_number)));
            //退出语音房间
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $config = load_cache('config');
            $api = createTimAPI();
            $broadMsg['type'] = Enum::KICK_OUT_LIKE;
            $broadMsg['wheat_id'] = $vid;
            $broadMsg['sender']['user_id'] = 1;
            $broadMsg['sender']['user_nickname'] = 'admin';
            $broadMsg['user']['user_id'] = $user['id'];
            $broadMsg['user']['user_nickname'] = $user['user_nickname'];
            $broadMsg['user']['avatar'] = $user['avatar'];
            $broadMsg['user']['user_level'] = $user['level'];
            #构造rest API请求包
            $msg_content = array();
            //创建$msg_content 所需元素
            $msg_content_elem = array(
                'MsgType'    => 'TIMCustomElem',       //定义类型为普通文本型
                'MsgContent' => array(
                    'Data' => json_encode($broadMsg)    //转为JSON字符串
                )
            );
            //将创建的元素$msg_content_elem, 加入array $msg_content
            array_push($msg_content, $msg_content_elem);
            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $voice['group_id'], $msg_content);
        }
        echo $voice ? 1 : 0;
    }
}
