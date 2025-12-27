<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/26
 * Time: 14:42
 */

namespace app\api\controller;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
use QcloudApi;
use think\Db;
use think\helper\Time;

class ShortVideoApi extends Base
{
    //获取上传视频sign
    public function get_upload_sign()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');

        $user_info = check_login_token($uid, $token);

        //检查是否认证
        $auth_where = [
            'user_id' => $user_info['id'],
            'status' => 1,
        ];
        $auth_info = db('auth_form_record')->where($auth_where)->find();

        if (!$auth_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Unable_upload_video_without_authentication');
            return_json_encode($result);
        }


        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = lang('Due_to_suspected_violation');
            return_json_encode($result);
        }

        $config = load_cache('config');
        // 确定APP的云API密钥
        $secret_id = $config['tencent_api_secret_id'];
        $secret_key = $config['tencent_api_secret_key'];

        // 确定签名的当前时间和失效时间
        $current = NOW_TIME;
        $expired = $current + 86400;  // 签名有效期：1天

        // 向参数列表填入参数
        $arg_list = array(
            "secretId" => $secret_id,
            "currentTimeStamp" => $current,
            "expireTime" => $expired,
            "random" => rand());

        // 计算签名
        $orignal = http_build_query($arg_list);
        $result['sign'] = base64_encode(hash_hmac('SHA1', $orignal, $secret_key, true) . $orignal);

        return_json_encode($result);
    }

    //视频费用检查
    public function video_coin_check()
    {
        $result = array('code' => 1, 'msg' => '');

        $money = intval(input('param.money'));

        $config = load_cache('config');
        //是否在合理范围内
        $range = explode('-', $config['video_coin_range']);
        if ($money > 0 && count($range) == 2) {

            if ($money < $range[0] || $money > $range[1]) {
                $result['code'] = 0;
                $result['msg'] = lang('charging_range_short_video_is') . $config['video_coin_range'];
                return_json_encode($result);
            }
        }

        return_json_encode($result);
    }

    //添加短视频记录
    public function add_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        //$money = intval(input('param.money'));
        //$video_id = input('param.video_id');
        $video_url = input('param.video_url');
        $cover_url = input('param.cover_url');
        $lng = input('param.lng');
        $lat = input('param.lat');
        //$status = intval(input('param.status'))?intval(input('param.status'))?0;
        $title = input('param.title');

        $user_info = check_login_token($uid, $token, ['is_auth', 'is_player', 'is_talker']);
        $config = load_cache('config');
        //$user_identity = get_user_identity($uid);
        if ($config['upload_video_auth'] == 1) {
            if ($user_info['is_auth'] != 1) {
                $result['msg'] = lang('Release_small_video_after_real_name_authentication');
                return_json_encode($result);
            }
        } else if ($config['upload_video_auth'] == 2) {
            if ($user_info['is_player'] != 1) {
                $result['msg'] = lang('Release_video_after_certification_accompanying_player');
                return_json_encode($result);
            }
        } else if ($config['upload_video_auth'] == 3) {
            if ($user_info['is_talker'] != 1) {
                $result['msg'] = lang('Only_certified_anchors_can_publish_videos');
                return_json_encode($result);
            }
        }

        if (empty($video_url)) {
            $result['code'] = 10101;
            $result['msg'] = lang('Video_URL_is_empty');
            return_json_encode($result);
        }

        if (empty($cover_url)) {
            $result['code'] = 10102;
            $result['msg'] = lang('Cover_URL_is_empty');
            return_json_encode($result);
        }

        /*if (empty($video_id)) {
            $result['code'] = 10103;
            $result['msg'] = '视频ID为空';
            return_json_encode($result);
        }*/

        if (empty($title)) {
            $result['code'] = 0;
            $result['msg'] = lang('Title_cannot_be_empty');
            return_json_encode($result);
        }

        $title = emoji_encode($title);

        $data = [
            'uid' => $uid,
            'title' => $title,
            'video_url' => $video_url,
            'img' => $cover_url,
            'addtime' => NOW_TIME,
            'status' => 1,
            'lng' => $lng,
            'lat' => $lat,
            'type' => 1,
            //'video_id' => $video_id,
        ];

        $res = Db::name('user_video')->insert($data);

        return_json_encode($result);

    }


    //删除视频
    public function del_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $video_id = input('param.video_id');

        $user_info = check_login_token($uid, $token);

        $video = Db::name('user_video')->where('uid', '=', $uid)->where('id', '=', $video_id)->find();
        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = lang('DELETE_FAILED');
            return_json_encode($result);
        }
        //删除点赞
        db('user_video_attention')->where(['videoid' => $video_id])->delete();
        //删除视频
        db('user_video')->where('uid', '=', $uid)->where('id', '=', $video_id)->delete();

        return_json_encode($result);

    }

    //小视频列表
    public function get_video_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => []);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $p = intval(input('page'));
        $keywords = trim(input('keywords'));

        $user_info = check_login_token($uid, $token);
        $map = [];
        if ($keywords) {
            $map['v.title'] = ['like', '%' . $keywords . '%'];
        }
        //查询该用户的视频列表
        $video_list = db('user_video')
            ->alias('v')
            ->join('user u', 'u.id=v.uid')
            ->where('v.type', '=', 1)
            ->where($map)
            ->order('v.addtime desc')
            ->field('v.*,u.user_nickname,sex,age,avatar')
            ->page($p)
            ->select();
        foreach ($video_list as &$v) {
            $v['title'] = emoji_decode($v['title']);
            //是否点赞
            $v['is_follow'] = 0;
            $follow_record = db('user_video_attention')->where('uid', '=', $uid)->where('videoid', '=', $v['id'])->where('status = 1')->find();
            if ($follow_record) {
                $v['is_follow'] = 1;
            }
            //获取视频点赞数
            $v['follow_num'] = db("user_video_attention")
                ->where("videoid=" . $v['id'])
                ->where("status=1")
                ->count();
            //获取主播关注总数
            //$v['host_count'] = db("user_attention")->where("attention_uid=" . $v['uid'])->count();

            //当前视频主播的信息
            $emcee_user_info = get_user_base_info($v['uid'], ['is_player', 'is_talker']);

            $v['avatar'] = $emcee_user_info['avatar'];
            $v['user_nickname'] = $emcee_user_info['user_nickname'];
            $v['sex'] = $emcee_user_info['sex'];
            $v['is_player'] = $emcee_user_info['is_player'];
            $v['is_talker'] = $emcee_user_info['is_talker'];

            //观看数量+1
            db('user_video')->where(['id' => $v['id']])->setInc('viewed', 1);

            $v['is_attention'] = 1;
            if ($v['uid'] != $uid) {
                $v['is_attention'] = get_attention($uid, $v['uid']); //获取是否关注
            }
            //是否可以收礼物
            $user_identity = get_user_identity($v['uid']);
            $v['is_gift'] = 1;
            if ($user_identity < 2) {
                $v['is_gift'] = 0;
            }
        }
        $result['data'] = $video_list;
        //$result['list'] = $video_list;
        add_look_bv_log($uid, 2);

        return_json_encode($result);
    }

    //获取视频信息
    public function get_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $video_id = input('param.video_id');

        $user_info = check_login_token($uid, $token);

        //$video = db('user_video')->find($video_id);
        $video = db('user_video')
            ->alias('v')
            ->join('user u', 'u.id=v.uid')
            ->where('v.type = 1 and v.id = ' . $video_id)
            ->order('v.addtime desc')
            ->field('v.*,u.user_nickname,u.sex,u.age,u.avatar,u.is_player,u.is_talker')
            ->find();

        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = lang('Error_getting_video_information');
            return_json_encode($result);
        }

        $result['data'] = $video;
        //是否点赞
        $result['data']['is_follow'] = 0;
        $follow_record = db('user_video_attention')->where('uid', '=', $uid)->where('videoid', '=', $video_id)->where('status = 1')->find();
        if ($follow_record) {
            $result['data']['is_follow'] = 1;
        }
        //获取视频点赞数
        $result['data']['follow_num'] = db("user_video_attention")
            ->where("videoid=$video_id")
            ->where("status=1")
            ->count();
        //获取主播关注总数
        $result['data']['host_count'] = db("user_attention")->where("attention_uid=" . $video['uid'])->count();

        //当前视频主播的信息
        $emcee_user_info = get_user_base_info($video['uid'], ['is_player', 'is_talker']);

        $result['data']['avatar'] = $emcee_user_info['avatar'];
        $result['data']['user_nickname'] = $emcee_user_info['user_nickname'];
        $result['data']['sex'] = $emcee_user_info['sex'];
        $result['data']['is_player'] = $emcee_user_info['is_player'];
        $result['data']['is_talker'] = $emcee_user_info['is_talker'];
        $noble = get_noble_level($video['uid']);
        //$v['noble_img'] = $noble['noble_img'];
        $result['data']['user_name_colors'] = $noble['colors'];
        //观看数量+1
        db('user_video')->where(['id' => $video_id])->setInc('viewed', 1);

        $result['data']['is_attention'] = 1;
        if ($video['uid'] != $uid) {
            $result['data']['is_attention'] = get_attention($uid, $video['uid']); //获取是否关注
        }
        //是否可以收礼物
        $user_identity = get_user_identity($video['uid']);
        $result['data']['is_gift'] = 1;
        if ($user_identity < 2) {
            $result['data']['is_gift'] = 0;
        }

        return_json_encode($result);

    }

    //获取自己的视频
    public function get_video_my_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $p = intval(input('page'));

        $user_info = check_login_token($uid, $token);

        //查询该用户的视频列表
        $video_list = db('user_video')->where('uid', '=', $uid)->order('addtime desc')->page($p)->select();
        foreach ($video_list as &$v) {
            $v['title'] = emoji_decode($v['title']);
        }
        $result['data']['list'] = $video_list;

        return_json_encode($result);
    }

    //获取其他用户的视频
    public function get_other_user_video_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $p = intval(input('page'));

        $user_info = check_login_token($uid, $token, ['is_talker', 'is_player']);

        //查询该用户的视频列表
        $video_list = db('user_video')
            ->alias('v')
            ->join('user u', 'u.id=v.uid')
            ->where('v.type', '=', 1)
            ->where('v.uid', '=', $to_user_id)
            ->order('v.addtime desc')
            ->field('v.*,u.avatar,u.user_nickname,u.sex,u.age,u.is_talker,u.is_player')
            ->page($p)
            ->select();
        foreach ($video_list as &$v) {
            $v['title'] = emoji_decode($v['title']);
            //是否点赞
            $v['is_follow'] = 0;
            $follow_record = db('user_video_attention')->where('uid', '=', $uid)->where('videoid', '=', $v['id'])->where('status = 1')->find();
            if ($follow_record) {
                $v['is_follow'] = 1;
            }
            //获取视频点赞数
            $v['follow_num'] = db("user_video_attention")
                ->where("videoid=" . $v['id'])
                ->where("status=1")
                ->count();
            //获取主播关注总数
            $v['host_count'] = db("user_attention")->where("attention_uid=" . $v['uid'])->count();

            //当前视频主播的信息
            /*$emcee_user_info = get_user_base_info($v['uid']);

            $v['avatar'] = $emcee_user_info['avatar'];
            $v['user_nickname'] = $emcee_user_info['user_nickname'];
            $v['sex'] = $emcee_user_info['sex'];
            $v['age'] = $emcee_user_info['age'];*/

            //观看数量+1
            //db('user_video')->where(['id' => $video_id])->setInc('viewed', 1);

            $v['is_attention'] = 1;
            if ($v['uid'] != $uid) {
                $v['is_attention'] = get_attention($uid, $v['uid']); //获取是否关注
            }
            //是否可以收礼物
            $user_identity = get_user_identity($v['uid']);
            $v['is_gift'] = 1;
            if ($user_identity < 2) {
                $v['is_gift'] = 0;
            }
        }
        $result['data']['list'] = $video_list;

        return_json_encode($result);
    }

    //视频点赞/取消点赞
    public function follow_video()
    {

        $result = array('code' => 1, 'msg' => lang('Like_succeeded'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $video_id = intval(input('param.video_id'));

        $user_info = check_login_token($uid, $token);

        //查询视频是否存在
        $video = db('user_video')->find($video_id);
        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = lang('Video_does_not_exist');
            return_json_encode($result);
        }

        //是否点过赞
        $follow_record = db('user_video_attention')->where('uid', '=', $uid)->where('videoid', '=', $video_id)->find();
        if ($follow_record) {
            if ($follow_record['status'] == 1) {
                //取消点赞
                //点赞数量-1
                db('user_video')->where('id', '=', $video_id)->setDec('follow_num', 1);
                db('user_video_attention')
                    ->where(['uid' => $uid, 'videoid' => $video_id])
                    ->update(['status' => 0]);
                $result['msg'] = lang('Cancel_like_succeeded');
            } else {
                //点赞
                //点赞数量+1
                db('user_video')->where('id', '=', $video_id)->setInc('follow_num', 1);
                db('user_video_attention')
                    ->where(['uid' => $uid, 'videoid' => $video_id])
                    ->update(['status' => 1]);
                $result['msg'] = lang('Like_succeeded');
            }
        } else {
            //添加点赞记录
            $data = [
                'uid' => $uid,
                'videoid' => $video_id,
                'touid' => $video['uid'],
                'status' => 1,
                'addtime' => time(),
            ];

            //点赞数量+1
            db('user_video')->where('id', '=', $video_id)->setInc('follow_num', 1);
            db('user_video_attention')->insert($data);
        }
        $video_info = db('user_video')->where('id', '=', $video_id)->field('follow_num')->find();
        if ($video_info) {
            $result['follow_num'] = $video_info['follow_num'];
        } else {
            $result['follow_num'] = 0;
        }

        return_json_encode($result);

    }

    //付费视频
    public function buy_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $video_id = input('param.video_id');

        $user_info = check_login_token($uid, $token);

        //查询视频是否存在
        $video = Db::name('user_video')->find($video_id);
        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = lang('Video_does_not_exist');
            return_json_encode($result);
        }

        if ($user_info['coin'] < $video['coin']) {

            $result['code'] = 10002;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }

        //扣费购买视频
        $charge_res = db('user')->where('id', '=', $uid)->setDec('coin', $video['coin']);

        if (!$charge_res) {
            $result['code'] = 0;
            $result['msg'] = lang('Fee_deduction_failed');
            return_json_encode($result);
        }

        //增加主播收益
        $income_total = host_income_commission(1, $video['coin'], $video['uid']);
        db('user')->where(['id' => $video['uid']])->inc('income', $income_total)->inc('income_total', $income_total)->update();

        //购买记录
        $video_pay_record = ['uid' => $uid, 'toid' => $video['uid'], 'videoid' => $video_id, 'coin' => $video['coin'], 'type' => 1, 'addtime' => time()];

        $buy_record_id = db('user_video_buy')->insertGetId($video_pay_record);

        add_charging_log($uid, $video['uid'], 1, $video['coin'], $buy_record_id, $income_total);

        return_json_encode($result);

    }


}
