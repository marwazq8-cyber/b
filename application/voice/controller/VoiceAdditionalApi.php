<?php

namespace app\voice\controller;
require_once DOCUMENT_ROOT . '/system/im_common.php';

use app\api\controller\Base;
use app\common\Enum;
use app\vue\model\ShopModel;
use think\Config;
use think\Db;
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

    // 获取直播间 房间总流水coin_number（钻石）、房间收藏和房间今日流水（钻石）
    public function voice_earnings()
    {
        $result = array('code' => 0, 'msg' => lang('Parameter_transfer_error'));

        // 用户id
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token);

        // 直播间id 房主id
        $voice_id = intval(input('param.voice_id'));
        // 获取房间信息
        $voice = $this->VoiceModel->sel_voice_user_one($voice_id);
        if (!$voice) {
            return_json_encode($result);
        }
        if ($uid != $voice['user_id']) {
            // 不是房主查询主持或是否是管理员
            $voice_administrator = db('voice_administrator')->where('user_id = ' . $uid . ' and voice_id=' . $voice_id)->find();
            if (!$voice_administrator) {
                $result['msg'] = lang('No_permission');
                return_json_encode($result);
            }
        }

        // 收藏数
        $collect = db('voice_collect')->where('voice_id = ' . $voice_id . " and status = 1")->count();

        $end_time = NOW_TIME;
        //今日
        $startime = strtotime(date("Y-m-d", $end_time));
        //昨日
        $yesterday = strtotime(date('Y-m-d', NOW_TIME) . '-1 day');
        //获取本月的起止时间
        $month_start_time = strtotime(date('Y-m-01'));
        //获取上月的起止时间
        $last_month_start_time = strtotime(date('Y-m-01', strtotime('last month')));

        // 获取今日流水
        $gift_total = db('user_gift_log')->where("room_id=" . $voice['id'] . " and create_time >=" . $startime)->sum("gift_coin");
        $yesterday = db('user_gift_log')->where("room_id=" . $voice['id'] . " and create_time >=" . $yesterday . " and create_time<" . $startime)->sum("gift_coin");
        $month_total = db('user_gift_log')->where("room_id=" . $voice['id'] . " and create_time >=" . $month_start_time)->sum("gift_coin");
        $last_month_total = db('user_gift_log')->where("room_id=" . $voice['id'] . " and create_time >=" . $last_month_start_time . " and create_time <" . $month_start_time)->sum("gift_coin");

        $user_info = Db("user")->field("luck,id,luck_end_time")->where("id=" . intval($voice['user_id']))->find();
        $data = array(
            'id'              => intval($user_info['luck']) && $user_info['luck_end_time'] > NOW_TIME ? intval($user_info['luck']) : intval($user_info['id']),
            'title'           => $voice['title'],
            'avatar'          => $voice['avatar'],
            'collect'         => intval($collect),
            'coin_number'     => number_format(intval($voice['coin_number'])),
            'day_coin'        => number_format(intval($gift_total)),
            'yesterday_coin'  => number_format(intval($yesterday)),
            'month_coin'      => number_format(intval($month_total)),
            'last_month_coin' => number_format(intval($last_month_total))
        );
        $result['code'] = 1;
        $result['msg'] = '';
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 获取直播间消费前三名
    public function get_voice_three()
    {
        $result = array('code' => 1, 'msg' => '');
        // 直播间id 房主id
        $voice_id = intval(input('param.voice_id'));

        // 获取房间信息
        $voice = $this->VoiceModel->sel_voice_user_one($voice_id, 1);

        if (!$voice) {
            $result['code'] = 0;
            $result['msg'] = lang('Room_closed');
            return_json_encode($result);
        }
        // 本场贡献榜列表
        $where = "l.voice_user_id='" . $voice['user_id'] . "'";

        $data['list'] = $this->VoiceModel->get_voice_three($where);

        $result['data'] = $data;

        return_json_encode($result);
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

            foreach ($list as $k => $v) {
                $user = get_user_base_info($v['user_id'], ['age', 'sex']);
                $list[$k]['sex'] = $user['sex'];
                $list[$k]['age'] = $user['age'];
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

    // 抱人上麦列表
    public function voice_up_list()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = trim(input('param.voice_id'));
        // 获取房间信息
        $voice = $this->VoiceModel->sel_voice_user_one($voice_id, 1);
        // 获取在麦上的用户
        $wheat_logs = $this->VoiceModel->get_voice_even_wheat_log_list($voice_id, 1);

        $voice_str = [];
        foreach ($wheat_logs as $k => $v) {
            $voice_str[] = $v['user_id'];
        }
        // 获取房间人数列表
        $list = voice_userlist_arsort($voice_id);

        $data = [];
        foreach ($list as $k => $v) {
            $name = json_decode($v, true);

            if (in_array($name['user_id'], $voice_str)) {

                unset($list[$k]);
            } else {
                $user = get_user_base_info($name['user_id'], ['age', 'sex']);
                $name['rank_sum'] = intval(get_user_vip_authority($name['user_id'], "is_rank"));
                $name['sex'] = $user['sex'];
                $name['age'] = $user['age'];
                $name['is_admin'] = $this->VoiceModel->is_voice_admin($voice['user_id'], $name['user_id']);
                $name['is_host'] = $this->VoiceModel->is_voice_host($voice['user_id'], $name['user_id']);
                $data[] = $name;
            }
        }
        if (count($data)) {
            $data = arraySequence($data, 'rank_sum');
        }
        $result['list'] = $data;

        return_json_encode($result);
    }

    //语音表情包列表
    public function room_memes_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $list = db('room_memes')->where("last_id=0 and status=1")->order("sort desc")->select();
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
            'name'    => $key_word,
            'uid'     => $uid,
            'type'    => $type,
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

    //消费者给用户的排行榜
    public function user_ranking_list()
    {
        //查询语音房间
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $id = trim(input('param.id')); //房间id
        $to_user_id = trim(input('param.to_user_id')); //收益用户id

        $user_info = check_login_token($uid, $token);
        $sel_voice = db('voice')->where("id=" . $id)->find();

        $where = "l.type=4 and l.voice_user_id =" . $sel_voice['user_id'] . " and l.to_user_id=" . $to_user_id;
        //是否重置了榜单
        $gift_reset = db('voice_gift_reset')->where("user_id=" . $to_user_id)->order("addtime desc")->find();
        $todaystart = strtotime(date('Y-m-d' . '00:00:00', time()));

        $where .= $gift_reset && $gift_reset['addtime'] > $todaystart ? " and l.create_time >=" . $gift_reset['addtime'] : " and l.create_time >=" . $todaystart;

        $config = load_cache('config');
        if ($config['rank_coin_type'] == 1) {
            $profit = 'l.profit';
        } else {
            $profit = 'l.gift_coin';
        }
        $list = db('user_gift_log')->alias('l')
            ->join('user a', 'l.user_id=a.id')
            ->field("a.user_nickname,a.avatar,a.sex,a.age,a.level,sum($profit) as total_diamonds,a.id")
            ->where($where)
            ->group("a.id")
            ->order("total_diamonds desc")
            ->limit(0, 100)
            ->select();

        $user_info['ranking'] = 0;
        $user_info['total_diamonds'] = 0;
        foreach ($list as $k => $v) {
            if ($v['id'] == $uid) {
                $user_info['ranking'] = $k + 1;
                $user_info['total_diamonds'] = $v['total_diamonds'];
            }
            /*$medal=medal_one($v['id'],$v['medal_id'],$v['medal_end_time']);
            $list[$k]['medal_icon']=$medal['medal_icon'];
            $list[$k]['medal_name']=$medal['medal_name'];
            $list[$k]['medal_time']=$medal['medal_time'];*/
            //陪聊等级
            $talker_level = get_talker_level($v['id']);
            $list[$k]['talker_level_name'] = $talker_level['talker_level_name'];
            $list[$k]['talker_level_img'] = $talker_level['talker_level_img'];
            //陪玩等级
            $player_level = get_player_level($v['id']);
            $list[$k]['player_level_name'] = $player_level['player_level_name'];
            $list[$k]['player_level_img'] = $player_level['player_level_img'];
        }
        // 获取数据库前缀
        $prefix = Config::get('database.prefix');
        // 获取用户在排行榜名次
        $where .= ' and l.user_id = ' . $uid;
        $sql_zi = "SELECT *,@rownum := @rownum + 1 AS rownum FROM (SELECT @rownum := 0) r,(SELECT user_id,sum(gift_coin) coin_sum  FROM " . $prefix . "user_gift_log AS l WHERE " . $where . " ORDER BY coin_sum DESC) as tt";
        // 查询用户排名
        $whereb = 'b.user_id = ' . $uid;
        $sql = "SELECT b.* FROM (" . $sql_zi . ") AS b WHERE " . $whereb;
        //dump($sql);
        $user_ranking_log = Db::query($sql);
        $user_ranking = array(
            'ranking'       => $user_ranking_log ? $user_ranking_log[0]['rownum'] : 0,
            'coin_sum'      => $user_ranking_log ? $user_ranking_log[0]['coin_sum'] : 0,
            'avatar'        => $user_info['avatar'],
            'user_nickname' => $user_info['user_nickname'],
        );

        $result['user_ranking'] = $user_ranking;
        $result['list'] = $list;
        return_json_encode($result);
    }

    /*
     * 关闭开启麦位麦克风
     * */
    public function update_audio_status()
    {
        $result = array('code' => 1, 'msg' => lang('SUCCESS'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $voice_id = intval(input('param.voice_id'));//房主ID
        $wheat_id = intval(input('param.wheat_id'));//麦位ID
        $user_info = check_login_token($uid, $token);
        //$config = load_cache('config');
        $wheat_logs = db('voice_even_wheat_log')->where('voice_id=' . $voice_id . ' and location = ' . $wheat_id . ' and status = 1')->find();
        if ($wheat_logs) {
            if ($wheat_logs['audio_status'] == 1) {
                $audio_status = 0;
                $result['msg'] = lang('Closed_successfully');
            } else {
                $audio_status = 1;
                $result['msg'] = lang('Open_successfully');
            }
            db('voice_even_wheat_log')->where('id = ' . $wheat_logs['id'])->update(['audio_status' => $audio_status]);
            $result['data']['audio_status'] = $audio_status;
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Modification_failed');
        }
        return_json_encode($result);
    }

    //收藏房间
    public function voice_collect()
    {
        $result = array('code' => 1, 'msg' => lang('SUCCESS'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $voice_id = intval(input('param.voice_id'));//房主ID
        $user_info = check_login_token($uid, $token);
        $collect = db('voice_collect')->where('user_id = ' . $uid . ' and voice_id = ' . $voice_id)->find();
        if ($collect) {
            if ($collect['status'] == 1) {
                //取消收藏
                $data = ['status' => 2];
                $result['msg'] = lang('Cancel_collection_successfully');
                $is_collect = 0;
            } else {
                //收藏
                $data = ['status' => 1];
                $result['msg'] = lang('Collection_succeeded');
                $is_collect = 1;
            }
            db('voice_collect')->where('user_id = ' . $uid . ' and voice_id = ' . $voice_id)->update($data);
        } else {
            $data = [
                'user_id'  => $uid,
                'voice_id' => $voice_id,
                'status'   => 1,
                'addtime'  => NOW_TIME,
            ];
            db('voice_collect')->insertGetId($data);
            $is_collect = 1;
            $result['msg'] = lang('Collection_succeeded');
        }
        $result['data']['is_collect'] = $is_collect;
        return_json_encode($result);
    }

    // 房间主持列表
    public function voice_host_list()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));

//        if ($uid != $voice_id) {
//            $result['msg'] = lang('room_that_not_user_cannot_operated');
//            return_json_encode($result);
//        }
        $voice = $this->VoiceModel->sel_voice_one($voice_id);

        $list = db('voice_administrator')
            ->alias('a')
            ->join('user u', 'u.id=a.user_id')
            ->field('a.user_id,a.voice_id,u.avatar,u.user_nickname,u.age,u.sex')
            ->where('type = 2 and voice_id=' . $voice_id)
            ->select();

        $result['code'] = 1;
        $result['list'] = $list;
        return_json_encode($result);
    }

    // 添加房间主持
    public function add_voice_host()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 连麦人id
        $to_user_id = intval(input('param.to_user_id'));

        if ($to_user_id == $uid) {
            $result['msg'] = lang('Cannot_operate_your_own_account');
            return_json_encode($result);
        }

        if ($uid != $voice_id) {
            $result['msg'] = lang('room_that_not_user_cannot_operated');
            return_json_encode($result);
        }
        $voice = $this->VoiceModel->sel_voice_one($voice_id);

        $voice_administrator = db('voice_administrator')
            ->where("type = 2 and user_id=" . $to_user_id . " and voice_id=" . $voice_id)
            ->find();

        if ($voice_administrator) {
            $result['msg'] = lang('User_is_already_host');
            return_json_encode($result);
        }
        //主持人数量
        $config = load_cache('config');
        $count = db('voice_administrator')->where('type = 2 and voice_id = ' . $voice_id)->count();
        if ($count >= $config['voice_host_num']) {
            $result['msg'] = lang('Number_facilitators_reach_upper_limit') . $config['voice_host_num'];
            return_json_encode($result);
        }
        $name = array(
            'user_id'   => $to_user_id,
            'voice_id'  => $voice_id,
            'voice_uid' => $uid,
            'type'      => 2,
            'addtime'   => NOW_TIME,
        );
        // 加入管理员
        $administrator = $this->VoiceModel->add_voice_administrator($name);

        if (!$administrator) {
            $result['msg'] = lang('Failed_to_add_host');
            return_json_encode($result);
        }

        $result['code'] = 1;
        return_json_encode($result);
    }

    // 删除房间主持
    public function del_voice_host()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 连麦人id
        $to_user_id = intval(input('param.to_user_id'));

        if ($to_user_id == $uid) {
            $result['msg'] = lang('Cannot_operate_your_own_account');
            return_json_encode($result);
        }

        if ($uid != $voice_id) {
            $result['msg'] = lang('room_that_not_user_cannot_operated');
            return_json_encode($result);
        }
        $voice = $this->VoiceModel->sel_voice_one($voice_id);

        $voice_administrator = db('voice_administrator')
            ->where("type = 2 and user_id=" . $to_user_id . " and voice_id=" . $voice_id)
            ->find();

        if (!$voice_administrator) {
            $result['msg'] = lang('User_is_not_host');
            return_json_encode($result);
        }

        // 删除主持
        $administrator = db('voice_administrator')
            ->where("type = 2 and user_id=" . $to_user_id . " and voice_id=" . $voice_id)
            ->delete();

        if (!$administrator) {
            $result['msg'] = lang('Delete_hosting_failed');
            return_json_encode($result);
        }
        //是否在主持麦位上,下麦
        $wheat_logs = $this->VoiceModel->get_voice_even_wheat_log_one($voice['user_id'], $to_user_id, 1, 1);
        if ($wheat_logs) {
            // 修改连麦记录
            $where = 'id = ' . $wheat_logs['id'];
            $update = ['status' => 3];
            $this->VoiceModel->upd_voice_even_wheat_log_status($where, $update);
        }
        $result['code'] = 1;
        return_json_encode($result);
    }

    //获取在线观众
    public function voice_user_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $keywords = input('keywords');
        $voice_id = intval(input('param.voice_id'));        //房间id
        $voice = db('voice')->where("user_id=" . $voice_id)->find();

        //获取房间人数列表
        $list = voice_userlist_arsort($voice['user_id']);
        $num = 0;
        $user_list = [];
        if ($keywords) {
            foreach ($list as $v) {
                $value = json_decode($v, true);
                if (strstr($value['user_id'], $keywords) || strstr($value['user_nickname'], $keywords)) {
                    $value['rank_sum'] = intval(get_user_vip_authority($value['user_id'], "is_rank"));
                    $user_list[] = $value;
                    $num += 1;
                }
            }
        } else {
            foreach ($list as $v) {
                $value = json_decode($v, true);
                //$user = get_user_base_info($value['user_id'],['age']);
                //$value['age'] = $user['age'];
                $value['rank_sum'] = intval(get_user_vip_authority($value['user_id'], "is_rank"));
                $user_list[] = $value;
                $num += 1;
            }
        }
        if (count($user_list)) {
            $user_list = arraySequence($user_list, 'rank_sum');
        }
        $result['userlist'] = $user_list;
        $result['sum'] = $num;
        return_json_encode($result);
    }

    // 显示麦位管理
    public function get_voice_even_list()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 房主id
        $voice_id = intval(input('param.voice_id'));
        // 麦位类型 -1 是正在麦位和申请的麦位 0申请 1正在 2拒绝3结束
        $type = input('param.type') ? input('param.type') : '-1';
        $voice = $this->VoiceModel->sel_voice_one($voice_id);
        if (!$voice) {
            $result['list'] = [];
            return_json_encode($result);
        }

        //获取上麦人数
        $where = "voice_id = " . $voice_id;

        if ($type == -1) {
            $where .= ' and (status=1 or status =0)';
        } else {
            $where .= " and status=" . $type;
        }
        $config = load_cache('config');
        $even_wheat = db('voice_even_wheat_log')->where($where)->select();
        $ShopModel = new ShopModel();
        foreach ($even_wheat as &$v) {
            // 获取语音直播间收益
            if ($config['voice_charm_type'] == 1) {
                $v['gift_earnings'] = get_voice_earnings($v['user_id'], $voice['user_id']) . '.00';
            } else {
                $v['gift_earnings'] = get_voice_earnings($v['user_id'], $voice['user_id']);
            }
            // 查询用户是否禁止发音
            $ban_voice = redis_hGet('ban_voice_' . $voice_id, $v['user_id']);
            // 是否禁止发音 1禁0否
            $v['is_ban_voice'] = $ban_voice ? 1 : 0;
            // 获取用户开启使用商品信息
            $shop = $ShopModel->get_user_shop($v['user_id']);
            // 头饰图片
            $v['headwear_url'] = $shop['headwear_url'];
            // 头饰名称
            $v['headwear_name'] = $shop['headwear_name'];
            $user = get_user_base_info($v['user_id'], ['age', 'sex']);
            $v['sex'] = $user['sex'];
            $v['age'] = $user['age'];
        }

        $result['list'] = $even_wheat;

        return_json_encode($result);
    }

    // 举报直播间
    public function do_report_live()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $type = trim(input('param.type'));
        $content = trim(input('param.content'));

        check_login_token($uid, $token);
        $voice_id = intval(input('param.voice_id'));
        // 获取房间信息
        $voice = $this->VoiceModel->sel_voice_one_id($voice_id);
        if (!$voice) {
            $result['code'] = 0;
            $result['msg'] = "房间不存在";
            return_json_encode($result);
        }
        $to_user_id = $voice['user_id'];
        $type_info = db('user_report_type')->find($type);
        if (!$type_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Report_type_does_not_exist');
            return_json_encode($result);
        }
        if (empty(trim(input('param.img1')))) {
            $result['code'] = 0;
            $result['msg'] = '请上传举报图片';
            return_json_encode($result);
        }
        //添加记录
        $report_record = [
            'uid'        => $uid,
            'reportid'   => $to_user_id,
            'reporttype' => $type,
            'content'    => $content,
            'type'       => 2,
            'type_id'    => $voice_id,
            'addtime'    => NOW_TIME,
        ];

        $log_id = db('user_report')->insertGetId($report_record);
        if ($log_id) {
            $img_val = array();
            for ($i = 1; $i <= 6; $i++) {
                $imgs = trim(input('param.img' . $i));
                if (!empty($imgs)) {
                    $img_val[] = array(
                        'report'  => $log_id,
                        'addtime' => NOW_TIME,
                        'img'     => $imgs
                    );
                }
            }
            //举报截图
            db('user_report_img')->insertAll($img_val);
        }
        return_json_encode($result);
    }
}
