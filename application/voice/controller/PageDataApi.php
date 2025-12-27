<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-11-27
 * Time: 16:04
 */

namespace app\voice\controller;

use \app\api\controller\Base;
use think\Request;
use UserOnlineStateRedis;
use app\api\model\PageDataModel;
use app\api\model\VoiceModel;
use app\api\model\UserModel;
use app\api\model\SkillsInfo;
use app\api\model\PlaywithModel;
use think\Db;

class PageDataApi extends Base
{
    public $PageDataModel;
    public $VoiceModel;
    public $UserModel;
    public $SkillsInfoModel;
    public $PlaywithModel;

    protected function _initialize()
    {
        parent::_initialize();

        $this->PageDataModel = new PageDataModel();
        $this->VoiceModel = new VoiceModel();
        $this->UserModel = new UserModel();
        $this->SkillsInfoModel = new SkillsInfo();
        $this->PlaywithModel = new PlaywithModel();
    }

    //社区
    public function get_community_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //$user_info = check_login_token($uid, $token,['host_more_voice_ratio']);
        //小视频
        $where = "a.type=1";
        $video = Db::name('user_video')->alias('a')->field('a.*,u.user_nickname,u.avatar')->join('user u', 'a.uid=u.id')
            ->where($where)
            ->order('addtime desc')
            ->limit(9)
            ->select();
        foreach ($video as &$v) {
            $v['title'] = emoji_decode($v['title']);
        }

        //遇见
        $meet_list = db("user_reference")
            ->alias('r')
            ->join('skills_recommend_label s', 's.id=r.recommend_label')
            ->join('user u', 'u.id=r.uid')
            ->field('u.id,u.user_nickname,u.sex,u.age,u.avatar,s.label_name,s.label_img,u.city')
            ->where(['r.type' => 2, 'u.is_talker' => 1])
            ->group('u.id')
            ->limit(9)
            ->select();

        //聊天室
        $config = load_cache('config');
        $sorting_weight = $this->PageDataModel->get_config_weight();

        $last_names = array_column($sorting_weight, 'val');

        array_multisort($last_names, SORT_DESC, $sorting_weight);

        //排序 人数 消费数量 用户等级
        $order = '';
        foreach ($sorting_weight as $k => $val) {
            switch ($val['code']) {
                case 'online_number_weight':
                    $order .= ',v.online_number desc';
                    break;
                case 'vote_number_weight':
                    $order .= ',v.vote_number desc';
                    break;
                case 'level_weight':
                    $order .= ',a.level desc';
                    break;
                case 'sort_weight':
                    $order .= ',v.sort desc';
                    break;
                case 'is_online_weight':
                    $order .= ',a.is_online desc';
                    break;
                default:
                    '';
            }
        }

        $order = ltrim($order, ",");

        $where = "a.user_status!=0 and a.is_auth=1 and v.status=1 and v.live_in=1";
        // 是否展示离线用户
        //$where .=$config['is_show_offline_user'] == 1 ? '':" and a.is_online=1";

        // $where .= " and a.reference=1";
        // $where = '';
        // var_dump($where);exit;
        $voice = $this->PageDataModel->get_voice_list($where, $order, 1);

        //动态、小视频未度
        $bzone_log = db('look_bzone_video_log')->where('uid = ' . $uid . ' and type = 1')->find();
        $video_log = db('look_bzone_video_log')->where('uid = ' . $uid . ' and type = 2')->find();
        if ($bzone_log) {
            $bzone_count = db('bzone')->where('addtime > ' . $bzone_log['addtime'])->count();
        } else {
            $bzone_count = 0;
        }
        if ($video_log) {
            $video_count = db('user_video')->where('addtime > ' . $video_log['addtime'])->count();
        } else {
            $video_count = 0;
        }

        $result['data']['meet_list'] = $meet_list;
        $result['data']['video_list'] = $video;
        $result['data']['voice_list'] = $voice;
        $result['data']['bzone_count'] = $bzone_count;
        $result['data']['video_count'] = $video_count;

        return_json_encode($result);
    }

    //获取语音直播频道类型
    public function voice_type()
    {
        $result = array('code' => 1, 'msg' => '');

        $genre = intval(input('param.genre')) == 2 ? 2 : 1;   //1单人直播 2多人直播
        // 获取直播间分类
        $result['data'] = $this->VoiceModel->sel_voice_type($genre);

        return_json_encode($result);
    }

    // 获取开直播前信息
    public function voice_label()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['host_more_voice_ratio']);
        // 创建直播间
        $this->VoiceModel->add_voice($uid);
        // 获取单人直播间分类
        $voice_label = $this->VoiceModel->sel_voice_label("a.status=1 and v.status=1 and v.type=1");
        // 获取多人直播间分类
        $voice_label_more = $this->VoiceModel->sel_voice_label("a.status=1 and v.status=1 and v.type = 2");
        // 获取选中标签的记录
        $voice_label_log = $this->VoiceModel->sel_voice_label_log("v.status=1 and y.type = 1 and a.uid=" . $uid);
        // 获取选中标签的记录
        $voice_label_log_more = $this->VoiceModel->sel_voice_label_log("v.status=1 and y.type = 2 and a.uid=" . $uid);

        if (count($voice_label_log) <= 0) {
            if ($voice_label[0]) {
                // 默认第一个
                $this->VoiceModel->add_voice_label_log($uid, $voice_label[0]['id']);
                $voice_label_log [] = $voice_label[0];
            }
        }
        if (count($voice_label_log_more) <= 0) {
            if ($voice_label_more[0]) {
                // 默认第一个
                $this->VoiceModel->add_voice_label_log($uid, $voice_label_more[0]['id']);
                $voice_label_log_more [] = $voice_label_more[0];
            }
        }

        // 获取用户开直播上传的图片
        $voice_img = $this->VoiceModel->user_voice_img($uid);
        // 获取房间信息
        $voice = $this->VoiceModel->sel_voice_user_one($uid);

        $data['voice_announcement'] = $voice ? $voice['announcement'] : '';
        $data['voice_title'] = $voice ? $voice['title'] : '';
        $data['voice_label'] = $voice_label;
        $data['voice_label_log'] = $voice_label_log;
        $data['voice_label_more'] = $voice_label_more;
        $data['voice_label_log_more'] = $voice_label_log_more;
        $data['voice_img'] = $voice_img ? $voice_img : array();
        $data['voice_ratio'] = $user_info['host_more_voice_ratio'];

        $result['data'] = $data;
        return_json_encode($result);
    }

    // 语聊直播大厅
    public function voice_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $type = intval(input('param.type')) ? intval(input('param.type')) : 0;   //0推荐 其他的是频道类型id
        $genre = intval(input('param.genre')) == 2 ? 2 : 1;   //1单人直播 2多人直播
        $countryCode = input('param.countryCode', 0, 'intval');
        if($type == 101){
            $user_id = input('param.uid');
            $data = Db::name('voice_room_users u')
                ->join('voice v', 'v.user_id = u.voice_id')
                ->where('u.user_id' , $user_id)
                ->order('u.JoinTime desc')
                ->limit(10)
                ->select();
            $result['data'] = $data;
            return_json_encode($result);
        }
        // 获取默认的类型
        $genre_where = $genre == 1 ? "status=1 and type=1" : "status=1 and type=2";

        $genre_one = $this->VoiceModel->get_voice_label_default($genre_where);

        $config = load_cache('config');
        // 获取后台设置的首页权重
        $sorting_weight = $this->PageDataModel->get_config_weight();

        $last_names = array_column($sorting_weight, 'val');

        array_multisort($last_names, SORT_DESC, $sorting_weight);
        //排序 人数 消费数量 用户等级
        $order = '';
        foreach ($sorting_weight as $k => $v) {
            switch ($v['code']) {
                case 'online_number_weight':
                    $order .= ',v.online_number desc';
                    break;
                case 'vote_number_weight':
                    $order .= ',v.vote_number desc';
                    break;
                case 'level_weight':
                    $order .= ',a.level desc';
                    break;
                case 'sort_weight':
                    $order .= ',v.sort desc';
                    break;
                case 'is_online_weight':
                    $order .= ',a.is_online desc';
                    break;
                default:
                    break;
            }
        }

        $order = ltrim($order, ",");
        $where = "a.user_status!=0 and v.status=1 and v.live_in=1 and v.voice_status !=1";
        // 频道类型
        //   $where .= $type > 0 ? " and t.type_id=".$type:" and a.reference=1";
        // 语聊分类
        // $where .=" and v.type=".$genre;
        if ($type > 0) {
            $where .= " and v.voice_type=" . $type;
        } else {
            $where .= " and a.reference=1";
        }
        // 国家代码筛选
        if ($countryCode != 0) {
            $where .= " and a.country_code = " . $countryCode;
        }
        $result['data'] = $this->PageDataModel->get_voice_list($where, $order, $page);
        return_json_encode($result);
    }

    //首页关注房间列表
    public function follow_voice_list_old()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        //$field = 'a.user_nickname,a.is_online,v.title,y.name,v.id,v.voice_type,v.avatar,v.type,v.voice_status,v.voice_psd,v.user_id,a.vip_end_time';

        $field = 'v.type,v.voice_type,v.title,v.avatar,v.id,v.voice_status,v.voice_psd,v.user_id,u.luck,y.name,u.user_nickname,u.is_online,u.vip_end_time';
        $order = 'a.addtime desc';
        $where = "a.uid=" . $uid . ' and v.live_in = 1';
        $list = db('voice')
            ->alias('v')
            ->join('user_attention a', 'a.attention_uid =v.user_id')
            ->join('user u', 'u.id =v.user_id')
            ->join('voice_type y', 'y.id=v.voice_type')
            ->field($field)
            ->where($where)
            ->order($order)
            ->page($page)
            ->select();

        foreach ($list as &$v) {
            //获取房间人数
            $v['watch_number'] = voice_userlist_sum($v['user_id']);
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
            //获取房间人数
            //$v['watch_number']=voice_userlist_sum($v['user_id']);
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //交友房间列表
    public function dating_voice_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $field = "v.id,v.avatar,v.release_title,v.voice_status,v.voice_psd,user_id,u.luck";
        $order = "v.release_time desc";
        $data = db('voice')->alias('v')
            ->join('user u', 'u.id =v.user_id')
            ->where("v.status=1 and v.release_time !=0")
            ->field($field)
            ->order($order)
            ->page($page)
            ->select();
        foreach ($data as &$v) {
            //获取房间人数
            $v['watch_number'] = voice_userlist_sum($v['user_id']);
        }

        $result['data'] = $data;
        return_json_encode($result);
    }

    //搜索 房间
    public function request_search_voice()
    {

        $result = array('code' => 1, 'msg' => '');

        $key_word = trim(input('param.key_word'));
        $page = intval(input('param.page'));

        $uid = intval(input('param.uid'));
        //$order=ltrim($order, ",");
        $config = load_cache('config');

        $where = "a.user_status!=0 and a.is_auth=1 and v.status=1 and v.live_in=1";
        // 是否展示离线用户
        //$where .=$config['is_show_offline_user'] == 1 ? '':" and a.is_online=1";
        $where .= ' and (v.title like "%' . $key_word . '%" or a.id like "%' . $key_word . '%" or a.luck like "%' . $key_word . '%")';
        $order = '';
        $list = $this->PageDataModel->get_voice_list($where, $order, $page);

        $result['data'] = $list;

        return_json_encode($result);
    }

    //收藏房间列表
    public function follow_voice_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        $user_info = check_login_token($uid, $token);
        $field = 'u.user_nickname,u.is_online,v.title,y.name,y.img,v.id,v.voice_type,v.avatar,v.type,v.voice_status,v.voice_psd,v.user_id,u.vip_end_time';

        //$field="v.id,v.avatar,v.release_title,v.voice_status,v.voice_psd,v.user_id,u.luck";
        $order = "v.release_time desc";
        $data = db('voice_collect')
            ->alias('c')
            ->join('voice v', 'v.user_id=c.voice_id')
            ->join('voice_type y', 'y.id=v.voice_type')
            ->join('user u', 'u.id =v.user_id')
            ->where("c.user_id = " . $uid . ' and c.status = 1')
            ->field($field)
            ->order($order)
            ->page($page)
            ->select();
        //dump($data);
        foreach ($data as &$v) {
            //获取房间人数
            $v['watch_number'] = voice_userlist_sum($v['user_id']);
        }

        $result['data'] = $data;
        return_json_encode($result);
    }

    /*
     * 派单厅列表*/
    public function get_dispatch_voice_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        $user_info = check_login_token($uid, $token);
        $result['data']['list'] = $this->VoiceModel->dispatch_voice_list($page);
        return_json_encode($result);
    }

    /*
     * 人气推荐*/
    public function get_popular_recommendation_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        $user_info = check_login_token($uid, $token);
        $result['data'] = $this->PageDataModel->get_popular_recommendation_list($page);
        return_json_encode($result);
    }
}
