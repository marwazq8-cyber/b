<?php

namespace app\api\controller;

use app\api\model\BzoneReplyModel;
use BuguPush;
use FontLib\Table\Type\name;
use think\App;
use think\Db;
use think\Request;
use app\api\model\UserModel;
use app\api\model\BzoneModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

class BzoneApi extends Base
{
    protected $UserModel;
    protected $BzoneModel;
    protected $BzoneReplyModel;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->UserModel = new UserModel();
        $this->BzoneModel = new BzoneModel();
        $this->BzoneReplyModel = new BzoneReplyModel();
    }

    //发布动态
    public function add_dynamic_new()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //文本内容
        $content = trim(input('param.msg_content', '', 'htmlspecialchars_decode'));//文本内容
        $video_url = trim(input('param.video_url'));//视频地址
        $cover_url = trim(input('param.cover_url'));//视频封面
        $city = trim(input('param.city'));//城市
        $adress = trim(input('param.adress'));//城市
        $lat = trim(input('param.lat'));//纬度
        $lng = trim(input('param.lng'));//经度
        $at_user_nickname = trim(input('param.at_user_nickname'));//@用户名 逗号隔开
        $at_uid = trim(input('param.at_uid'));//@用户id 逗号隔开
        $imgs = trim(input('param.imgs')); //图片地址 逗号隔开
        $type = intval(input('param.type')); //1:图片,2:视频

        $user_info = check_login_token($uid, $token, ['last_login_ip', 'is_auth', 'is_player', 'is_talker']);
        $config = load_cache('config');
        //$user_identity = get_user_identity($uid);
        if ($config['upload_bzone_auth'] == 1) {
            if ($user_info['is_auth'] != 1) {
                $result['msg'] = lang('Real_name_authentication_release_trends');
                return_json_encode($result);
            }
        } else if ($config['upload_bzone_auth'] == 2) {
            if ($user_info['is_player'] != 1) {
                $result['msg'] = lang('Release_certified_accompanist');
                return_json_encode($result);
            }
        } else if ($config['upload_bzone_auth'] == 3) {
            if ($user_info['is_talker'] != 1) {
                $result['msg'] = lang('Certified_anchor_release_dynamics');
                return_json_encode($result);
            }
        }

        if (!$content && !$imgs && !$video_url) {
            $result['code'] = 0;
            $result['msg'] = lang('At_least_one_entry_required');
            return_json_encode($result);
        }

        if ($imgs && $video_url) {
            $result['code'] = 0;
            $result['msg'] = lang('Upload_item_pictures_and_videos');
            return_json_encode($result);
        }

        $content = strlen($content) == 0 ? "" : $content;
        /*if (strlen($content) > 200) {
            $result['code'] = 0;
            $result['msg'] = '内容长度超出限制！';
            return_json_encode($result);
        }*/

        $data = [
            'uid' => $uid,
            'type' => $type,
            'msg_content' => $content,
            'video_url' => $video_url,
            'cover_url' => $cover_url,
            'publish_time' => NOW_TIME,
            'addtime' => NOW_TIME,
            'city' => $city,
            'adress' => $adress,
            'lat' => $lat,//纬度
            'lng' => $lng,//经度
            'geo_hash' => get_geo_hash($lat, $lng),//经度
            'at_user_nickname' => $at_user_nickname,//@用户名 逗号隔开
            'at_uid' => $at_uid,//@用户id 逗号隔开
        ];
        $zid = $this->BzoneModel->add($data);

        if (!$zid) {
            $result['code'] = 0;
            $result['msg'] = lang('Publishing_failed');
            return_json_encode($result);
        }

        //$param_list = input('param.');
        if ($imgs) {
            //上传图片

            $imgs_arr = explode(',', $imgs);
            foreach ($imgs_arr as $k => $v) {
                $img_info['zone_id'] = $zid;
                $img_info['addtime'] = NOW_TIME;
                $img_info['img'] = $v;
                db('bzone_images')->insertGetId($img_info);
            }
        }

        task_reward(2, $uid);
        $result['msg'] = lang('Published_successfully');
        return_json_encode($result);
    }

    //评论
    public function add_dynamic_reply()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $content = input('param.body');
        //动态id
        $zone_id = input('param.zone_id');
        $replay_id = input('param.reply_id', 0);//评论ID

        $user_info = check_login_token($uid, $token);

        if (empty($content) || empty($zone_id)) {
            $result['code'] = 0;
            $result['msg'] = lang('Parameter_transfer_error');
            return_json_encode($result);
        }

        $content = emoji_encode($content);

        //查询该动态信息
        //$zone = db('bzone')->find($zone_id);
        $zone = $this->BzoneModel->selFind($zone_id);
        if (!$zone) {
            $result['code'] = 0;
            $result['msg'] = lang('Dynamic_does_not_exist');
            return_json_encode($result);
        }

        $data = [
            'uid' => $uid,
            'body' => $content,
            'zone_id' => $zone_id,
            'reply_id' => $replay_id,
            'addtime' => time(),
        ];
        //$res = Db::name('bzone_reply')->insertGetId($data);
        $res = $this->BzoneReplyModel->saveOneId($data);
        if ($res) {
            //发送动态评论推送
            /*require_once DOCUMENT_ROOT . '/system/umeng/BuguPush.php';
            $config = load_cache('config');
            $push = new BuguPush($config['umengapp_key'], $config['umeng_message_secret']);
            $push->sendAndroidCustomizedcast('go_app', $zone['uid'], 'buguniao', '动态消息', '有人评论了你的动态快去查看吧', $content, json_encode([]));
            $push->sendIOSCustomizedcast('go_app', $zone['uid'], 'buguniao', '动态消息', '有人评论了你的动态快去查看吧', $content, json_encode([]));*/

            $result['msg'] = lang('Published_successfully');
            return_json_encode($result);
        }

        $result['code'] = 0;
        $result['msg'] = lang('Server_busy');
        return_json_encode($result);

    }

    //获取评论
    public function get_reply_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        $zone_id = intval(input('param.zone_id'));

        $user_info = check_login_token($uid, $token);

        $list = Db::name('bzone_reply')
            ->where('zone_id = ' . $zone_id . ' and reply_id = 0')
            ->order('id')
            ->page($page)
            ->select();
        //循环下从缓存中取出用户数据
        $temp_list = array();
        foreach ($list as $k => $v) {
            $user_info = get_user_base_info($v['uid']);
            $v['addtime'] = time_trans($v['addtime']);
            $v['body'] = emoji_decode($v['body']);
            $temp_list[$k] = $v;
            $temp_list[$k]['userInfo'] = $user_info;
            $reply = Db::name('bzone_reply')
                ->where('zone_id = ' . $zone_id . ' and reply_id = ' . $v['id'])
                ->order('id')->select();
            //循环下从缓存中取出用户数据
            //$reply_list = array();
            foreach ($reply as $key => $val) {
                $val['userInfo'] = get_user_base_info($val['uid']);
                $val['addtime'] = time_trans($val['addtime']);
                $val['body'] = emoji_decode($val['body']);
                $temp_list[$k]['reply_list'][] = $val;
            }
        }

        $list = $temp_list;
        $result['list'] = $list;
        return_json_encode($result);
    }

    //获取回复评论
    public function get_reply_comment()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));
        $reply_id = intval(input('param.reply_id'));
        $page = get_input_param_int('page');

        $user_info = check_login_token($uid, $token);

        $list = Db::name('bzone_reply')
            ->where('zone_id = ' . $zone_id . ' and reply_id = ' . $reply_id)
            ->order('id')
            ->page($page)
            ->select();
        //循环下从缓存中取出用户数据
        $temp_list = array();
        foreach ($list as $k => $v) {
            $user_info = get_user_base_info($v['uid']);
            $v['addtime'] = time_trans($v['addtime']);
            $v['body'] = emoji_decode($v['body']);
            $temp_list[$k] = $v;
            $temp_list[$k]['userInfo'] = $user_info;
        }

        $list = $temp_list;
        $result['list'] = $list;
        return_json_encode($result);
    }

    /*
     * 删除评论*/
    public function del_reply()
    {
        $result = array('code' => 1, 'msg' => '');
        //评论ID
        $reply_id = input('param.id');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token);

        /*$config = load_cache('config');
        if($config['bzone_comment_status']==1){
            $result['code'] = 0;
            $result['msg'] = '评论功能暂未开启';
            return_json_encode($result);
        }*/

        $bzone_reply = db('bzone_reply')->where('uid = ' . $uid . ' and id = ' . $reply_id)->find();
        if (!$bzone_reply) {
            $result['code'] = 0;
            $result['msg'] = lang('Comment_does_not_exist');
            return_json_encode($result);
        }

        $res = db('bzone_reply')->where('uid = ' . $uid . ' and id = ' . $reply_id)->delete();

        if ($res) {
            //修改评论下的子评论
            Db::name('bzone_reply')->where('reply_id = ' . $reply_id)->update(['reply_id' => 0]);
            $result['msg'] = lang('DELETE_SUCCESS');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('DELETE_FAILED');
        }

        return_json_encode($result);

    }

    //动态点赞
    public function request_like()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));

        $user_info = check_login_token($uid, $token);
        $zone_info = $this->BzoneModel->get_bzone_info($zone_id, $uid);
        //查询下是否已经点了喜欢按钮
        $is_like = Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => $zone_id])->find();
        if ($is_like) {
            //取消点赞
            Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => $zone_id])->delete();
            $zone_info['like_count'] = $zone_info['like_count'] - 1;
            $zone_info['is_like'] = 0;
        } else {
            //点赞
            $data = [
                'uid' => $uid,
                'zone_id' => $zone_id,
                'addtime' => time(),
            ];
            Db::name('bzone_like')->insert($data);
            $msg = db("user_message")->where('type = 13')->find();
            $content = $user_info['user_nickname'] . $msg['centent'];
            $url = 'bogo://message?type=2&id=' . $zone_id;
            push_sys_msg_user(17, $zone_info['uid'], 1, $content, $url);

            $zone_info['like_count'] = $zone_info['like_count'] + 1;
            $zone_info['is_like'] = 1;
        }

        $result['data'] = $zone_info;
        return_json_encode($result);

    }

    //删除查询
    public function del_dynamic()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));

        $user_info = check_login_token($uid, $token);

        //先删除所有评论
        //Db::name('bzone_reply')->where('zone_id = ' . $zone_id)->delete();
        //删除所有点赞
        Db::name('bzone_like')->where('zone_id = ' . $zone_id)->delete();
        //最后删除动态
        Db::name('bzone')->where('id = ' . $zone_id)->delete();

        return_json_encode($result);
    }

    //获取动态 列表
    public function get_dynamic_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = trim(input('param.type'));//att 关注 he他 she她 nearby附近 heart热门 news最新
        $page = intval(input('param.page'));//分页

        /*if ($imgs && $video_url ) {
            $result['code'] = 0;
            $result['msg'] = '图片，视频只能上传一项！';
            return_json_encode($result);
        }*/

        $user_info = check_login_token($uid, $token);
        $where = [];
        $order = '';
        if ($type == 'att') {
            //关注
            //$userModel = new UserModel();
            $attention = $this->UserModel->get_user_attention($uid);
            $attention_uid = [];
            if ($attention) {
                foreach ($attention as $val) {
                    array_push($attention_uid, $val['attention_uid']);
                }
            }
            $where['b.uid'] = ['in', $attention_uid];
            //$result['data']['list'] = $attention_uid;
            //return_json_encode($result);
        } else if ($type == 'he') {
            //他 所有男性
            $where['u.sex'] = 1;
        } else if ($type == 'she') {
            //她 所有女性
            $where['u.sex'] = 2;
        } else if ($type == 'nearby') {
            //附近
            $lat = trim(input('param.lat'));//纬度
            $lng = trim(input('param.lng'));//经度
            $list = $this->BzoneModel->get_nearby_list($uid, $lat, $lng, $where, $page);
            $result['data']['list'] = $list;
            return_json_encode($result);
        } else if ($type == 'heart') {
            $order = 'b.heart desc';
        } else if ($type == 'news') {
            $order = 'b.publish_time desc';
        }
        $list = $this->BzoneModel->get_list($uid, $where, $page, $order);
        $result['data']['list'] = $list;
        add_look_bv_log($uid, 1);
        return_json_encode($result);

    }

    //动态详情
    public function get_dynamic_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));
        $user_info = check_login_token($uid, $token);
        $zone_info = $this->BzoneModel->get_bzone_info($zone_id, $uid);

        if (!$zone_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Dynamic_does_not_exist');
            return_json_encode($result);
        }
        //是否拉黑
        $zone_info['is_black'] = 0;
        $black_record = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $zone_info['uid'])->find();
        if ($black_record) {
            $zone_info['is_black'] = 1;
        }
        $result['data'] = $zone_info;
        return_json_encode($result);
    }

    //打赏榜单
    public function get_reward_rank()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));//动态ID
        $page = intval(input('param.page'));//分页 页数
        $user_info = check_login_token($uid, $token);
        $zone_info = $this->BzoneModel->get_reward_list($zone_id, $page);

        $result['data'] = $zone_info;
        return_json_encode($result);
    }

    /*
     * 点赞用户列表
     * */
    public function like_user_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $to_user_id = intval(input('param.to_user_id'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token);
        //$zone_id = intval(input('param.zone_id'));//动态ID
        $page = intval(input('param.page'));//分页 页数
        if ($to_user_id) {
            $list = $this->BzoneModel->getMyLike($to_user_id, $page);
        } else {
            $list = $this->BzoneModel->getMyLike($uid, $page);
        }

        $result['data'] = $list;
        return_json_encode($result);
    }

    /*
     * 动态点赞列表*/
    public function get_like_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token);
        $zone_id = intval(input('param.zone_id'));//动态ID
        $page = intval(input('param.page'));//分页 页数
        $list = $this->BzoneModel->getLikeList($zone_id, $page);
        $result['data'] = $list;
        return_json_encode($result);
    }
}
