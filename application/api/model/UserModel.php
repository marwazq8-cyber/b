<?php

namespace app\api\model;

use think\Model;
use think\Db;
use think\helper\Time;
use VideoCallRedis;

class UserModel extends Model
{
    // 统计粉丝总数
    public function fans_count($uid)
    {

        $fans_count = db('user_attention')->where("attention_uid=" . $uid)->count();

        return $fans_count ? $fans_count : 0;
    }

    // 通过手机号查询用户信息
    public function get_moble_user($mobile)
    {

        $user = db('user')->where("mobile =" . $mobile)->find();

        return $user;
    }

    // 获取用户信息
    public function get_user($where, $field)
    {

        $user = db('user')->field($field)->where($where)->find();

        return $user;
    }

    // 统计关注总数
    public function focus_count($uid)
    {

        $focus_count = db('user_attention')->where("uid=" . $uid)->count();

        return $focus_count ? $focus_count : 0;
    }

    // 获取粉丝列表
    public function fans_count_list($uid)
    {

        $fans_count = db('user_attention')->where("attention_uid=" . $uid)->select();

        return $fans_count;
    }

    // 获取用户关注和粉丝列表
    public function focus_fans_list($where, $page, $join_where)
    {

        $list = db('user_attention')->alias("a")
            ->join("user u", $join_where)
            ->where($where)
            ->field("u.id,u.avatar,u.user_nickname,u.sex,u.age,u.income_level,u.level,u.luck,u.vip_end_time")
            ->order("a.addtime desc")
            ->page($page, 20)
            ->select();
        foreach ($list as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
        }
        return $list;
    }

    // 是否关注对方
    public function is_focus_user($uid, $touid)
    {

        $focus = db('user_attention')->where("uid=" . $uid . " and attention_uid=" . $touid)->find();

        return $focus;
    }

    // 关注对方
    public function add_focus($uid, $touid)
    {
        $data = array(
            'uid' => $uid,
            'attention_uid' => $touid,
            'addtime' => NOW_TIME
        );
        $atte = db('user_attention')->insert($data);

        return $atte;
    }

    // 统计访客总数
    public function visitors_count($uid)
    {

        $visitors_count = db('user_visitors_log')->where("touid=" . $uid)->count();

        return $visitors_count ? $visitors_count : 0;
    }

    // 增加访客记录和修改访客时间
    public function add_visitors($uid, $touid)
    {
        $visitors = db('user_visitors_log')->where("touid=" . $touid . " and uid=" . $uid)->find();
        $data = array(
            'addtime' => NOW_TIME,
        );
        if ($visitors) {
            db('user_visitors_log')->where("id=" . $visitors['id'])->inc('num')->update($data);
        } else {
            $data['uid'] = $uid;
            $data['touid'] = $touid;
            db('user_visitors_log')->insert($data);
        }
    }

    // 获取用户的照片墙
    public function user_img($uid, $status = '')
    {

        $where = "uid=" . $uid;

        $where .= $status ? " and status=" . $status : '';

        $user_img = Db::name('user_img')->where($where)->order("addtime asc")->select();

        return $user_img;
    }

    // 获取用户的照片墙
    public function user_img_one($where)
    {

        $user_img = Db::name('user_img')->where($where)->find();

        return $user_img;
    }

    // 统计用户的照片墙
    public function user_img_count($uid)
    {

        $user_img = Db::name('user_img')->where('uid', '=', $uid)->count();

        return $user_img;
    }

    // 删除用户的照片墙
    public function del_user_img($where)
    {

        $user_img = Db::name('user_img')->where($where)->delete();

        return $user_img;
    }

    // 添加用户的照片墙 轮播图
    public function add_user_img($img)
    {

        $all_img = Db::name('user_img')->insertAll($img);

        return $all_img;
    }

    // 查询 是否有重复的昵称
    public function sel_user_nickname($uid, $user_nickname)
    {

        $name = Db::name('user')->where("user_nickname='" . $user_nickname . "' and id!=" . $uid)->find();

        return $name;
    }

    // 修改用户信息
    public function upd_user($uid, $token, $data)
    {

        $user = db('user')->where("id=" . $uid . " and token='" . $token . "'")->update($data);

        return $user;
    }

    // 获取通话评价总数
    public function video_call_record_log_count($uid, $is_fabulous)
    {

        $where = "anchor_id=" . $uid;

        $where .= $is_fabulous ? " and is_fabulous=" . $is_fabulous : '';

        $user = db('video_call_record_log')->where($where)->count();

        return $user;
    }

    // 获取通话时长
    public function call_time($uid)
    {

        $where = "user_id=" . $uid . " or call_be_user_id=" . $uid;

        $call_time = db('video_call_record_log')->where($where)->sum('call_time');

        return $call_time;
    }

    // 用户是否拉黑
    public function user_black($uid, $touid)
    {
        //是否被对方拉黑
        $black_where = '(user_id=' . $uid . ' and black_user_id=' . $touid . ') or (user_id=' . $touid . ' and black_user_id=' . $uid . ')';

        $black = db('user_black')->where($black_where)->find();

        return $black;
    }

    // 用户是否拉黑对方
    public function user_black_one($uid, $touid)
    {

        $black = db('user_black')->where('user_id=' . $uid . " and black_user_id=" . $touid)->find();

        return $black;
    }

    // 获取用户选中的个性标签
    public function visualize_name($visualize_name)
    {
        $visualize = [];
        if ($visualize_name) {
            $name = explode(",", $visualize_name);
            // 获取所有的个性标签
            $user_img = db('visualize_table')->field("id,visualize_name,color")->order("sort desc")->select();
            foreach ($user_img as $v) {
                // 判断个性标签是否存在
                if (in_array($v['visualize_name'], $name)) {
                    $visualize[] = $v;
                }
            }
        }
        return $visualize;
    }

    /* 扣除用户金额 */
    public function deduct_user_coin($user_info, $coin, $type)
    {

        $charging_coin = db('user')->where('id=' . $user_info['id'])->setDec('coin', $coin);

        if ($charging_coin || $coin <= 0) {
            // 账号变更记录 1文字聊天 2语音聊 3视频聊 4礼物 5背包礼物 6陪玩 7签到奖励 8任务奖励
            upd_user_coin_log($user_info['id'], $coin, $coin, $type, 1, 2, $user_info['last_login_ip'], $user_info['id']);
        }

        return $charging_coin;
    }

    /* 扣除用户金额
     * $user_info 用户信息
     * $coin 金额
     * $coin_type 1心币(充值) 2友币
     */
    public function deduct_user_coin_new($user_info, $coin, $coin_type, $type, $touid = 1)
    {

        if ($coin_type == 1) {
            $charging_coin = 0;
            if ($coin > 0) {
                $charging_coin = db('user')->where('id=' . $user_info['id'])->setDec('coin', $coin);
            }
        } else {
            $charging_coin = db('user')
                ->where('id=' . $user_info['id'])
                ->where('friend_coin >= ' . $coin)
                ->setDec('friend_coin', $coin);
        }

        if ($charging_coin || $coin <= 0) {
            //账号变更记录 1文字聊天 2语音聊 3视频聊 4礼物 5背包礼物 6陪玩 7签到奖励 8任务奖励
            upd_user_coin_log($user_info['id'], $coin, $coin, $type, 1, 2, $user_info['last_login_ip'], $touid);
        }

        return $charging_coin;
    }

    /*
     * 增加主播收益
     * 陪聊师
     */
    public function add_user_earnings($uid, $income_total, $to_user, $type, $is_guild = '0')
    {

        if ($is_guild == 1) {
            //income 剩余收益 income_total 总收益  guild_income_total 公会总收益数
            $user_earnings = db('user')
                ->where('id=' . $uid)
                ->inc('income', $income_total)
                ->inc('income_total', $income_total)
                ->inc('guild_income_total', $income_total)
                ->update();
        } else {
            //income 剩余收益 income_total 总收益  income_talker_total 陪聊主播中总受益
            $user_earnings = db('user')
                ->where('id=' . $uid)
                ->inc('income', $income_total)
                ->inc('income_total', $income_total)
                ->inc('income_talker_total', $income_total)
                ->update();
        }

        if ($user_earnings) {
            // 账号变更记录 1文字聊天 2语音聊 3视频聊 4礼物 5背包礼物 6陪玩 7签到奖励 8任务奖励
            //upd_user_coin_log($uid,$income_total,2,1,$to_user['last_login_ip'],$to_user['id'],$type);
            $config = load_cache('config');
            $integral= 0;
            if($income_total > 0 && $config['integral_withdrawal'] > 0){
                $integral = number_format($income_total / $config['integral_withdrawal'], 2);
            }

            upd_user_coin_log($uid, $income_total, $integral, $type, 2, 1, $to_user['last_login_ip'], $to_user['id']);
        }
        return $user_earnings;
    }

    /*
     * 增加主播收益
     * 陪玩师
     */
    public function add_user_earnings_player($uid, $income_total, $to_user, $type)
    {

        //income 剩余收益 income_total 总收益  income_talker_total 陪聊主播中总受益
        $user_earnings = db('user')
            ->where('id=' . $uid)
            ->inc('income', $income_total)
            ->inc('income_total', $income_total)
            ->inc('income_player_total', $income_total)
            ->update();

        if ($user_earnings) {
            // 账号变更记录 1文字聊天 2语音聊 3视频聊 4礼物 5背包礼物 6陪玩 7签到奖励 8任务奖励
            $config = load_cache('config');
            $integral = number_format($income_total / $config['integral_withdrawal'], 2);
            upd_user_coin_log($uid, $income_total, $integral, 6, 2, 1, $to_user['last_login_ip'], $to_user['id'], $type);
        }
        return $user_earnings;
    }

    /* 获取用户剩余金额 */
    public function user_coin($uid)
    {

        $deduction = db('user')->field("coin")->where("id=" . $uid)->find();

        return $deduction;
    }

    /* 获取 身份认证 */
    public function get_auth_form_record($uid)
    {

        $auth_form_record = db('auth_form_record')->where('user_id', '=', $uid)->find();

        return $auth_form_record;
    }

    /* 删除用户认证 */
    public function del_auth_form_record($uid)
    {

        $auth_form_record = db('auth_form_record')->where('user_id', '=', $uid)->delete();

        return $auth_form_record;
    }

    /* 用户申请认证 */
    public function add__auth_form_record($data)
    {

        $res = db('auth_form_record')->insert($data);

        return $res;
    }

    /*获取关注列表 所有关注*/
    public function get_user_attention($uid)
    {
        $res = db('user_attention')->where(['uid' => $uid])->select();

        return $res;
    }

    /*获取主播认证信息*/
    public function get_user_auth_anchor($uid)
    {
        $res = db('auth_talker')->where(['uid' => $uid])->find();
        if ($res) {
            $res['img_list'] = [];
            $img_list = db('auth_talker_img')->where(['aid' => $res['id']])->select();
            if ($img_list) {
                //认证图片
                $res['img_list'] = $img_list;
            }
        }

        return $res;
    }

    /*删除主播认证信息*/
    public function del_auth_anchor($uid)
    {
        $auth_info = db('auth_talker')->where(['uid' => $uid])->find();
        $res = 0;
        if ($auth_info) {
            $res = db('auth_talker')->where(['uid' => $uid])->delete();
            //删除图片
            db('auth_talker_img')->where(['aid' => $auth_info['id']])->delete();
        }
        return $res;
    }

    /*提交主播认证信息*/
    public function add_user_auth_anchor($data, $img)
    {
        $res = db('auth_talker')->insertGetId($data, $img);
        //认证图片
        $img_arr = explode(',', $img);
        foreach ($img_arr as $k => $v) {
            $img_data = [
                'img' => $v,
                'aid' => $res,
                'addtime' => time(),
            ];
            db('auth_talker_img')->insert($img_data);
        }
        return $res;
    }

    /*获取官方认证信息*/
    public function get_user_auth_platform($uid)
    {
        $res = db('platform_auth')->where(['user_id' => $uid])->find();
        if ($res) {
            $res['img_list'] = [];
            $img_list = db('platform_auth_img')->where(['pid' => $res['id']])->select();
            if ($img_list) {
                //认证图片
                $res['img_list'] = $img_list;
            }
        }

        return $res;
    }

    /*删除主播认证信息*/
    public function del_auth_platform($uid)
    {
        $auth_info = db('platform_auth')->where(['user_id' => $uid])->find();
        $res = 0;
        if ($auth_info) {
            $res = db('platform_auth')->where(['user_id' => $uid])->delete();
            //删除图片
            db('platform_auth_img')->where(['pid' => $auth_info['id']])->delete();
        }
        return $res;
    }

    /*提交主播认证信息*/
    public function add_user_auth_platform($data, $img)
    {
        $res = db('platform_auth')->insertGetId($data, $img);
        //认证图片
        if ($res) {
            $img_arr = explode(',', $img);
            foreach ($img_arr as $k => $v) {
                $img_data = [
                    'img' => $v,
                    'pid' => $res,
                    'addtime' => time(),
                ];
                db('platform_auth_img')->insert($img_data);
            }
        }

        return $res;
    }

    /*陪玩认证信息*/
    public function get_user_auth_player($uid, $game_id)
    {
        $res = db('auth_player')->where(['uid' => $uid, 'game_id' => $game_id])->find();
        if ($res) {
            $res['img_list'] = [];
            $img_list = db('auth_player_img')
                ->where(['pid' => $res['id']])
                ->field('id,img')
                ->select();
            if ($img_list) {
                //认证图片
                $res['img_list'] = $img_list;
            }

        }
        return $res;
    }

    /*添加陪玩认证信息*/
    public function add_user_auth_player($data, $img)
    {
        $res = db('auth_player')->insertGetId($data);
        if ($res) {
            //认证图片
            $img_arr = explode(',', $img);
            foreach ($img_arr as $k => $v) {
                $img_data = [
                    'uid' => $data['uid'],
                    'img' => $v,
                    'pid' => $res,
                    'addtime' => time(),
                ];
                db('auth_player_img')->insert($img_data);
            }
        }
        return $res;
    }

    /**/
    public function del_user_auth_player($uid, $game_id)
    {
        $player_info = db('auth_player')->where(['uid' => $uid, 'game_id' => $game_id])->find();
        $res = 0;
        if ($player_info) {
            $res = db('auth_player')->where(['uid' => $uid, 'game_id' => $game_id])->delete();

            //认证图片
            db('auth_player_img')
                ->where(['pid' => $player_info['id']])
                ->delete();
        }
        return $res;
    }

    /*
     * 搜索
     * $key  关键字
     * $page 页数
     * */
    public function get_search($key, $uid, $page)
    {
        //如果是数字 ID
        $field = 'avatar,id,user_nickname,sex,age,luck';
        /*if(is_numeric($key)){
            $where['id'] =['like','%'.$key.'%'];
            $list = db('user')->where($where)->where('id != 1')->field($field)->page($page)->select();
        }else{
            $where['user_nickname'] =['like','%'.$key.'%'];
            $list = db('user')->where($where)->where('id != 1')->field($field)->page($page)->select();
        }*/

        $where = 'user_nickname like "%' . $key . '%" or id like "%' . $key . '%" or label like "%' . $key . '%" or luck like "%' . $key . '%"';
        $list = db('user')->where($where)->where('id != 1 and user_status != 0')->field($field)->page($page)->select();
        if ($list) {
            foreach ($list as &$val) {
                $val['is_attention'] = get_attention($uid, $val['id']);
                $noble = get_noble_level($val['id']);
                $val['noble_img'] = $noble['noble_img'];
                $val['user_name_colors'] = $noble['colors'];

            }
        }
        //添加搜索记录
        $data = [
            'uid' => $uid,
            'name' => $key,
            'type' => 1,
            'addtime' => NOW_TIME,
        ];
        db('search_log')->insertGetId($data);
        return $list;
    }

    //遇见 猜你喜欢
    public function get_meet_recommend($uid, $page)
    {
        $list = Db::name('user')
            ->alias('u')
            ->join('skills_recommend_label r', 'r.id = u.recommend_label')
            ->where('u.is_talker = 1 and u.reference = 1')
            ->where('u.id != ' . $uid)
            ->field('u.id,u.user_nickname,u.avatar,u.city,u.sex,u.age,r.label_name,r.label_img')
            ->page($page, 3)
            ->select();
        return $list;
    }

    /*
     * 遇见
     * $page 分页
     * $key 搜索关键词
     * $Lng 经度
     * $Lat 纬度
     * */
    public function get_meet_list($page, $key, $Lng, $Lat)
    {
        if (!$Lng || !$Lat) {
            $Lng = '116.4';
            $Lat = '39.9';
        }
        $field = 'u.id,u.sex,u.age,u.user_nickname,u.avatar,u.city,u.is_online,u.longitude,u.latitude,u.income_talker_total,u.income_player_total,u.nobility_level,u.is_talker,u.is_player,u.label';
        $distance = ",(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*($Lng- u.longitude)/360),2)+COS(PI()*$Lat/180)* COS(u.latitude * PI()/180)*POW(SIN(PI()*($Lat-u.latitude)/360),2)))) as distance";
        if ($key == 'heart') {
            $list = Db::name('auth_talker')
                ->alias('a')
                ->join('user u', 'u.id=a.uid')
                ->where('a.status = 1')
                //->where($where)
                ->field($field . $distance)
                ->order('u.is_online desc,u.income_total desc')
                ->page($page)
                ->select();
        } else if ($key == 'nearby') {
            $list = Db::name('auth_talker')
                ->alias('a')
                ->join('user u', 'u.id=a.uid')
                ->where('a.status = 1')
                ->where("u.longitude > 0 and (2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*($Lng- u.longitude)/360),2)+COS(PI()*$Lat/180)* COS(u.latitude * PI()/180)*POW(SIN(PI()*($Lat-u.latitude)/360),2)))) < 100")
                ->field($field . $distance)
                ->order('u.is_online desc,distance desc')
                ->page($page)
                ->select();
        } else {
            $where['a.type'] = $key;
            $list = Db::name('auth_talker')
                ->alias('a')
                ->join('user u', 'u.id=a.uid')
                ->where('a.status = 1')
                ->where($where)
                ->field($field . $distance)
                ->order('u.is_online desc,distance desc')
                ->page($page)
                ->select();
        }

        foreach ($list as &$v) {
            $v['uid'] = $v['id'];
            $talker_level = get_talker_level($v['id']);
            $player_level = get_player_level($v['id']);
            $v['talker_level_name'] = $talker_level['talker_level_name'];
            $v['talker_level_img'] = $talker_level['talker_level_img'];
            $v['player_level_name'] = $player_level['player_level_name'];
            $v['player_level_img'] = $player_level['player_level_img'];
            $noble = get_noble_level($v['id']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
            if (!empty($v['label'])) {
                $v['label'] = explode(',', $v['label']);
            } else {
                $v['label'] = [];
            }
            //勋章
            $uid_medal = get_user_dress_up($v['id'], 1);
            $v['user_medal'] = '';
            if ($uid_medal) {
                $v['user_medal'] = $uid_medal['icon'];
            }

        }

        return $list;
    }

    /*
     * 音遇*/
    public function get_audio($page)
    {

        $field = 'id,sex,age,user_nickname,avatar,city,is_online,longitude,latitude,audio_file,audio_time,label';

        $list = Db::name('user')
            ->where('is_talker = 1')
            ->field($field)
            ->orderRaw('rand()')
            ->page($page)
            ->select();
        if ($list) {
            foreach ($list as &$v) {
                //$item['label'] = explode(',',$item['label']);
                if (!empty($v['label'])) {
                    $v['label'] = explode(',', $v['label']);
                } else {
                    $v['label'] = [];
                }
                $noble = get_noble_level($v['id']);
                $v['noble_img'] = $noble['noble_img'];
                $v['user_name_colors'] = $noble['colors'];
            }
        }
        return $list;

    }

    /*
     * 装饰
     * uid 用户ID
     * type 1勋章,2主页特效,3头像框,4聊天气泡,5聊天背景
     * */
    public function get_dress_up($uid, $type)
    {
        $data = Db::name('user_dress_up')
            ->alias('u')
            ->join('dress_up d', 'd.id=u.dress_id')
            ->where(['u.uid' => $uid, 'type' => $type, 'status' => 1])
            ->field('d.*')
            ->find();
        return $data;
    }

}
