<?php

namespace app\api\model;

use think\Model;
use think\Db;
use think\Config;

class VoiceModel extends Model
{
    /*
    * 创建房间
    */
    public function add_voice($uid)
    {

        $voice = db('voice')->where('user_id=' . $uid)->find();
        $room_id = $uid . '_' . NOW_TIME;
        if (!$voice) {
            $data = array(
                'user_id' => $uid,
                'live_in' => 2,
                'create_time' => NOW_TIME,
                'room_id' => $room_id
            );
            db('voice')->insert($data);
        } else {
            db('voice')->where('user_id=' . $uid)->update(array('room_id' => $room_id));
        }
    }

    /*
    * 获取直播间信息
    */
    public function sel_voice_one($uid)
    {

        $voice_where = "v.user_id=" . $uid;
        // 查询语音房间
        $field = "a.user_nickname,a.avatar,a.host_more_voice_ratio,v.title,v.id,v.group_id,v.user_id,v.voice_bg,v.wheat_type,v.announcement,v.avatar as voice_avatar,v.voice_type,a.luck,v.live_in,v.type,v.online_number,v.online_count,v.room_id,v.voice_label,a.vip_end_time,v.voice_status,v.voice_psd,v.charm_status,v.room_type";
        $voice = db('user')->alias('a')
            ->join('voice v', 'v.user_id=a.id')
            ->field($field)
            ->where($voice_where)
            ->find();
        if ($voice) {
            $voice['is_vip'] = get_is_vip($voice['vip_end_time']);
            if (!$voice['luck']) {
                $voice['luck'] = $voice['user_id'];
            }
        }
        return $voice;
    }

    /*
    * 获取直播间信息 根据ID
    */
    public function sel_voice_one_id($id)
    {

        $voice_where = "v.id=" . $id;
        // 查询语音房间
        $field = "a.user_nickname,a.avatar,a.host_more_voice_ratio,v.title,v.id,v.group_id,v.user_id,v.voice_bg,v.wheat_type,v.announcement,v.avatar as voice_avatar,v.voice_type,a.luck,v.live_in,v.type,v.online_number,v.online_count,v.room_id,v.voice_label,a.vip_end_time,v.voice_status,v.voice_psd";
        $voice = db('user')->alias('a')
            ->join('voice v', 'v.user_id=a.id')
            ->field($field)
            ->where($voice_where)
            ->find();
        if ($voice) {
            $voice['is_vip'] = get_is_vip($voice['vip_end_time']);
        }
        return $voice;
    }

    /**
     * 直播间累加和累减
     * $uid 房主id $name 要修改的名称 $number改变的数 $type 1累计2累减
     */
    public function upd_cumulative($uid, $name, $number, $type)
    {

        if ($type == 1) {
            // 累加
            $voice_status = db('voice')->where("user_id=" . $uid)->setInc($name, $number);
        } else {
            // 累减
            $voice_status = db('voice')->where("user_id=" . $uid)->where($name . '>' . $number)->setDec($name, $number);
        }

        return $voice_status;
    }

    /*
    * 获取直播间信息
    */
    public function sel_voice_user_one($uid, $status = '')
    {

        $where = 'user_id=' . $uid;

        $where .= $status ? ' and status=' . $status : '';

        $voice = db('voice')->where($where)->find();

        return $voice;
    }

    /* 编辑房间信息 */
    public function upd_user_voice($uid, $data)
    {

        $voice = db('voice')->where('user_id=' . $uid)->update($data);

        return $voice;
    }

    /* 增加房间收益 */
    public function add_voice_vote_number($id, $vote_number)
    {

        $voice = db('voice')->where("user_id=" . $id)->setinc('vote_number', $vote_number);

        return $voice;
    }

    /** 增加房间流水 *
     * @param $id
     * @param $coin_number
     * @return mixed|string
     * @throws \think\Exception
     */
    public function add_voice_coin_number($id, $coin_number)
    {
        return db('voice')->where("user_id=" . $id)->setinc('coin_number', $coin_number);
    }

    /*
    * 获取房间背景图
    */
    public function get_voice_bg_one($voice_bg)
    {

        $voice_bg_image = '';
        // 获取背景图片
        if ($voice_bg) {
            $voice_type_id = db('voice_bg')->where("id=" . $voice_bg . " and status=1")->find();

            if ($voice_type_id['image']) {

                $voice_bg_image = $voice_type_id['image'];
            }
        }

        return $voice_bg_image;
    }

    /* 获取房间背景图片 */
    public function get_voice_bg_list()
    {

        $voice_bg = db('voice_bg')->where("status=1")->order("sort DESC")->select();

        return $voice_bg;
    }

    /*
    * 获取送礼物人id是否在麦位上
    * id 直播间id $to_user_id多个用逗号隔开
    */
    public function sel_voice_even_wheat_gift($id, $to_user_id)
    {

        $voice_even = db('voice_even_wheat_log')
            ->field("id,user_id,MIN(status) as status")
            ->where("voice_id=" . $id . " and (user_id in (" . $to_user_id . "))")
            ->group("user_id")
            ->order("addtime desc")
            ->select();

        return $voice_even;
    }

    /*
   * 获取麦位信息
   */
    public function sel_voice_even_wheat_user_list($voice_id)
    {

        $voice = db('voice')->where("user_id=" . $voice_id)->find();
        $even_wheat_type = db('voice_even_wheat_log')->where("voice_id=" . $voice_id . " and status=1 and user_id=" . $voice['user_id'])->find();
        if ($even_wheat_type) {
            $where = "v.voice_id=" . $voice_id . " and v.status=1";
        } else {
            $where = "v.voice_id=" . $voice_id . " and (v.status=1 or v.user_id=" . $voice['user_id'] . ")";
        }
        // $where = "v.voice_id=" . $voice_id . " and v.status=1";

        $voice_even = db('user')->alias('a')
            ->join('voice_even_wheat_log v', 'v.user_id=a.id')
            ->field("v.*,a.user_nickname,a.avatar,a.vip_end_time")
            ->where($where)
            ->group("v.user_id")
            ->order('v.location')
            ->select();

        foreach ($voice_even as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }

        return $voice_even;
    }

    /*
    * 获取派单厅麦位信息
    */
    public function sel_voice_even_wheat_user_list_dispatch($voice_id)
    {
        $where = "v.voice_id=" . $voice_id . " and v.status=1";
        $voice_even = db('user')->alias('a')
            ->join('voice_even_wheat_log v', 'v.user_id=a.id')
            ->field("v.*,a.user_nickname,a.avatar,a.vip_end_time")
            ->where($where)
            ->order('location')
            ->group("v.user_id")
            ->select();

        foreach ($voice_even as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }

        return $voice_even;
    }

    /*
    * 获取上麦人数列表
    */
    public function get_voice_even_wheat_log_list($uid, $status = '1')
    {

        $where = "voice_id=" . $uid;

        $where .= $status == '-1' ? ' and (status=1 or status =0)' : " and status=" . $status;

        $even_wheat = db('voice_even_wheat_log')->where($where)->select();

        return $even_wheat;
    }

    /*
    *  获取麦位信息
    *  $voice_id 房主id $uid用户id  $status上麦状态-1 是查询申请和正在麦位上 $location 麦位位置
    */
    public function get_voice_even_wheat_log_one($voice_id, $uid, $status = '', $location = '')
    {

        $where = "voice_id=" . $voice_id;

        $where .= $uid ? " and user_id=" . $uid : '';

        $where .= $location ? " and location=" . $location : '';

        if (!empty($status)) {

            $where .= $status == '-1' ? ' and (status=1 or status =0)' : " and status=" . $status;
        }

        $wheat_id = db('voice_even_wheat_log')->where($where)->order("endtime desc")->find();

        return $wheat_id;
    }

    /*
    * 用户上麦
    */
    public function add_voice_even_wheat_log($data)
    {
        // 加入连麦记录
        $status = db('voice_even_wheat_log')->insertGetId($data);

        return $status;
    }

    /*
    * 用户下麦
    */
    public function upd_voice_even_wheat_log_status($where, $data)
    {

        $upd_voice = db('voice_even_wheat_log')->where($where)->update($data);

        return $upd_voice;
    }

    /*
    * 修改麦位
    */
    public function upd_voice_even_wheat_log($wheat_id, $data)
    {

        $upd_voice = db('voice_even_wheat_log')->where("id=" . $wheat_id)->update($data);

        return $upd_voice;
    }

    /*
    * 删除麦位列表
    */
    public function del_voice_even_wheat_log($where)
    {

        return db('voice_even_wheat_log')->where($where)->delete();

    }

    /*
    *  增加用户连麦收益记录
    */
    public function add_voice_even_wheat_log_coin($id, $income_total)
    {

        $wheat_log = db('voice_even_wheat_log')->where('id=' . $id)->inc('gift_earnings', $income_total)->inc('the_gift_earnings', $income_total)->update();

        return $wheat_log;
    }

    /* 选择房间类型 */
    public function sel_voice_type($type = 0)
    {

        $voice_type = db('voice_type')->field("id,name,type")->where("status=1")->order("sort desc")->select();

        return $voice_type;
    }

    /* 获取房间标签分类的id */
    public function sel_voice_label_type($where, $group)
    {

        $voice_label = db('voice_label')->field("id,name,voice_type_id")->where($where)->group($group)->select();

        return $voice_label;
    }

    /* 获取房间默认分类的id */
    public function get_voice_label_default($where)
    {

        $voice_label = db('voice_type')->field("id,name")->where($where)->order("sort desc")->find();

        return $voice_label;
    }

    /* 获取房间标签 */
    public function sel_voice_label($where)
    {

        $voice_label = db('voice_label')->alias('a')
            ->join('voice_type v', 'v.id=a.voice_type_id')
            ->field("a.id,a.name,v.type")
            ->where($where)
            ->order("a.sort desc")
            ->select();

        return $voice_label;
    }

    /* 获取用户房间标签记录 */
    public function sel_voice_label_log($where)
    {

        $data = db('voice_label_log')->alias('a')
            ->join('voice_label v', 'v.id=a.voice_label_id')
            ->join('voice_type y', 'y.id=v.voice_type_id')
            ->field("v.id,v.name,y.type")
            ->where($where)
            ->order("a.addtime desc")
            ->limit("0,4")
            ->select();

        return $data;
    }

    /* 增加用户房间标签 */
    public function add_voice_label_log($uid, $id)
    {

        $voice_label_log = db('voice_label_log')->where("uid=" . $uid . " and voice_label_id=" . $id)->find();

        if ($voice_label_log) {

            $voice_label = db('voice_label_log')->where("id=" . $voice_label_log['id'])->update(array('addtime' => NOW_TIME));
        } else {
            $data = array(
                'uid' => $uid,
                'voice_label_id' => $id,
                'addtime' => NOW_TIME
            );
            $voice_label = db('voice_label_log')->insert($data);
        }

        return $voice_label;
    }

    /* 获取用户直播上传的封面图 */
    public function user_voice_img($uid)
    {

        $voice_img = db('voice_img')->field("img,status")->where("uid=" . $uid)->find();

        return $voice_img;
    }

    /* 修改用户直播上传的封面图 */
    public function upd_user_voice_img($uid, $img)
    {

        $config = load_cache('config');
        // 获取是否有审核的图片
        $voice_img = db('voice_img')->field("img,status")->where("uid=" . $uid)->find();
        if ($img) {
            $data = array(
                'uid' => $uid,
                'img' => $img,
                'addtime' => NOW_TIME
            );
            // 封面图是否审核
            $data['status'] = $config['voice_img_audit'] == 1 ? 0 : 1;

            if ($voice_img) {
                // 修改封面图
                db('voice_img')->where("uid=" . $uid)->update($data);
            } else {
                // 增加封面图
                db('voice_img')->insert($data);
            }
            // 获取直播间图片
            $avatar = $data['status'] == 1 ? $img : $config['user_avatar'];
        } else {
            // 获取直播间图片
            $avatar = $voice_img['status'] == 1 || $config['voice_img_audit'] == 1 ? $voice_img['img'] : $config['user_avatar'];
        }

        return $avatar;
    }

    /* 获取直播首页分类是否审核 */
    public function get_voice_type_log($uid, $voice_genre)
    {

        $data = db('voice_type_log')->alias('l')
            ->join('voice_type t', 't.id=l.type_id')
            ->field("l.*")
            ->where("t.status=1 and t.type=" . $voice_genre . " and l.uid=" . $uid)
            ->find();

        return $data;
    }

    /* 添加直播分类 */
    public function add_voice_type_log($data)
    {

        $res = db('voice_type_log')->insertAll($data);

        return $res;
    }

    /* 获取直播首页权重 */
    public function get_config_weight()
    {

        $sorting_weight = db('config')->field("code,val")->where("group_id='排序权重'")->select();

        return $sorting_weight;
    }

    /* 获取房间管理员列表 */
    public function get_voice_administrator_list($uid)
    {

        $administrator = db('voice_administrator')->where("voice_id=" . $uid)->select();

        return $administrator;
    }

    /* 获取房间管理员列表 */
    public function get_voice_administrator_one($voice_id, $touid)
    {

        $administrator = db('voice_administrator')->where("user_id=" . $touid . " and voice_id=" . $voice_id)->find();

        return $administrator;
    }

    /* 添加管理员 */
    public function add_voice_administrator($data)
    {

        $administrator = db('voice_administrator')->insertGetId($data);

        return $administrator;
    }

    /* 获取房间管理员信息 */
    public function get_voice_administrator_user($voice_id, $uid)
    {

        $list = db('user')->alias('a')
            ->join('voice_administrator v', 'v.user_id=a.id')
            ->field("a.user_nickname,a.avatar,v.*,a.vip_end_time,a.sex,a.age")
            ->where("v.type = 1 and v.voice_id=" . $voice_id . " and v.voice_uid=" . $uid)
            ->select();
        foreach ($list as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }
        return $list;
    }

    /* 删除房间管理员 */
    public function del_voice_administrator($voice_id, $touid, $uid)
    {

        $upd_voice = db('voice_administrator')->where("type = 1 and voice_id=" . $voice_id . " and user_id=" . $touid . " and voice_uid=" . $uid)->delete();

        return $upd_voice;
    }

    /* 是否是管理员 */
    public function is_voice_admin($voice_id, $uid)
    {
        $administrator = db('voice_administrator')->where('type = 1 and user_id = ' . $uid . ' and voice_id = ' . $voice_id)->find();

        return $administrator ? 1 : 0;
    }

    /* 是否是主持人 */
    public function is_voice_host($voice_id, $uid)
    {
        $administrator = db('voice_administrator')->where('type = 2 and user_id = ' . $uid . ' and voice_id = ' . $voice_id)->find();

        return $administrator ? 1 : 0;
    }

    /* 重置房间收益 */
    public function add_voice_gift_reset($data)
    {

        $voice_gift_reset = db('voice_gift_reset')->insertGetId($data);

        return $voice_gift_reset;
    }

    /* 清理删除音乐下载 */
    public function del_judge_music($where)
    {

        $music_download = db('music_download')->where($where)->delete();

        return $music_download;
    }

    /* 关闭直播间时 清理直播数据加入直播记录表 */
    public function add_voice_room_log($voice)
    {
        $voice_log = 0;

        if ($voice) {

            $data = array('live_in' => 0, 'status' => 2, 'endtime' => NOW_TIME);

            db('voice')->where("id=" . $voice['id'])->update($data);

            unset($voice['id']);

            $voice['status'] = 2;
            $voice['live_in'] = 0;
            $voice['endtime'] = NOW_TIME;

            $voice_log = db('voice_log')->insertGetId($voice);
        }
        return $voice_log;
    }

    // 直播间收入(礼物)记录
    public function sel_voice_gift_log($where)
    {

        $list = db('user_gift_log')->alias('l')
            ->join('gift g', 'l.gift_id=g.id')
            ->field("g.name,g.img,sum(l.gift_count) as gift_sum")
            ->group("l.gift_id")
            ->order("gift_sum desc")
            ->where($where)
            ->select();

        return $list;
    }

    // 统计直播间收益
    public function sum_voice_gift_log($where, $field)
    {

        $list = db('user_gift_log')->where($where)->sum($field);

        return $list;
    }

    // 获取房间消费贡献榜
    public function get_voice_three($where)
    {
        $list = db('user')->alias('a')
            ->join('user_gift_log l', 'l.user_id=a.id')
            ->field("a.id,a.avatar,sum(l.gift_coin) as  gift_coin")
            ->where($where)
            ->group("a.id")
            ->order("gift_coin desc")
            ->cache(60 * 10)
            ->limit(3)
            ->select();
        return $list;
    }

    // 获取房间收入贡献榜  和获取本周贡献榜
    public function get_voice_contribution($where, $page)
    {
        $list = db('user')->alias('a')
            ->join('user_gift_log l', 'l.user_id=a.id')
            ->field("a.user_nickname,a.avatar,sum(l.gift_coin) as  gift_coin,a.vip_end_time")
            ->where($where)
            ->group("a.id")
            ->order("gift_coin desc")
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }

        return $list;
    }

    // 获取收入榜小时榜 日榜 周榜
    public function get_earnings_ranking_list($where, $page)
    {
        $list = db('user')->alias('a')
            ->join('user_gift_log l', 'l.to_user_id=a.id')
            ->field("a.id,a.user_nickname,a.avatar,sum(l.profit) as  total_ticket,a.vip_end_time")
            ->where($where)
            ->group("a.id")
            ->order("total_ticket desc")
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }

        return $list;
    }

    // 获取给用户消费最多的3个用户
    public function get_earnings_ranking_three($where)
    {
        $list = db('user')->alias('a')
            ->join('user_gift_log l', 'l.user_id=a.id')
            ->field("a.user_nickname,a.avatar,sum(l.gift_coin) as  gift_coin,a.vip_end_time")
            ->where($where)
            ->group("a.id")
            ->order("gift_coin desc")
            ->limit(3)
            ->select();
        foreach ($list as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }
        return $list;
    }

    // 本场直播贡献榜 $where是查询排名用的
    public function get_user_earnings_ranking($where, $whereb)
    {
        // 获取数据库前缀
        $prefix = Config::get('database.prefix');
        // 获取用户在排行榜名次
        $sql_zi = "SELECT *,@rownum := @rownum + 1 AS rownum FROM (SELECT @rownum := 0) r,(SELECT user_id,sum(profit) coin_sum  FROM " . $prefix . "user_gift_log AS t WHERE " . $where . " ORDER BY coin_sum DESC) as tt";
        // 查询用户排名
        $sql = "SELECT b.* FROM (" . $sql_zi . ") AS b WHERE " . $whereb;

        $list = Db::query($sql);

        return $list;
    }

    // 本场直播贡献榜 $where是查询排名用的
    public function get_voice_home_course_ranking($where, $whereb)
    {
        // 获取数据库前缀
        $prefix = Config::get('database.prefix');
        // 获取用户在排行榜名次
        $sql_zi = "SELECT *,@rownum := @rownum + 1 AS rownum FROM (SELECT @rownum := 0) r,(SELECT user_id,sum(gift_coin) coin_sum  FROM " . $prefix . "user_gift_log AS t WHERE " . $where . " ORDER BY coin_sum DESC) as tt";
        // 查询用户排名
        $sql = "SELECT b.* FROM (" . $sql_zi . ") AS b WHERE " . $whereb;

        $list = Db::query($sql);

        return $list;
    }

    public function get_voice_even_wheat_list($where)
    {
        $list = db('voice_even_wheat_log')
            ->where($where)
            ->group('user_id')
            ->select();
        return $list;
    }

    //派单厅
    public function dispatch_voice_list($page)
    {
        $field = 'u.user_nickname,v.id,v.title,v.avatar,v.user_id,v.live_in,v.vote_number,v.voice_label,v.voice_status,v.voice_psd_show,v.group_id,v.heat,v.room_type';
        $list = db('voice')
            ->alias('v')
            ->join('user u', 'u.id=v.user_id')
            ->field($field)
            ->where('room_type = 2')
            ->page($page)
            ->select();
        if ($list) {
            $voce_user = array_column($list, 'user_id');
            $voce_user_id = implode(',', $voce_user);
            $voice_dispatch = db('voice_dispatch')
                ->alias('d')
                ->join('play_game p', 'p.id = d.game_id')
                ->field('p.name,voice_id')
                ->where('d.voice_id in (' . $voce_user_id . ')')
                ->select();
            foreach ($list as &$v) {
                $v['is_dispatch'] = 0;
                $v['dispatch_game'] = '';
                if ($voice_dispatch) {
                    foreach ($voice_dispatch as $val) {
                        if ($val['voice_id'] == $v['user_id']) {
                            $v['is_dispatch'] = 1;
                            $v['dispatch_game'] = $val['name'];
                        }
                    }
                }
            }
        }

        return $list;
    }

}
