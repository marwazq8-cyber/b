<?php

namespace app\api\controller;

use \app\api\controller\Base;
use UserOnlineStateRedis;
use app\api\model\PageDataModel;
use app\api\model\VoiceModel;
use app\api\model\UserModel;
use app\api\model\SkillsInfo;
use app\api\model\PlaywithModel;
use VideoCallRedis;

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

    // 获取国家列表
    public function get_country_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $where = "status=1";
        $filed = 'id,name,img,num_code';
        $order = "sort desc";

        $country = db('country')->field($filed)
            ->where($where)
            ->order($order)
            ->select();
        $result['data'] = $country;

        return_json_encode($result);
    }

    //轮播图
    public function shuffling()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $shuffling = intval(input('param.shuffling'));

        $where = "slide_id=$shuffling and status=1";
        $filed = 'id,image,title,url,is_auth_info,is_out_webview_open';
        $order = "list_order desc";

        $img = db('slide_item')->where($where)->order($order)->field($filed)->select();

        $result['data'] = $img;

        return_json_encode($result);
    }

    //搜索
    public function request_search()
    {

        $result = array('code' => 1, 'msg' => '');

        $key_word = trim(input('param.key_word'));
        $page = intval(input('param.page'));

        $uid = intval(input('param.uid'));
        $list = $this->UserModel->get_search($key_word, $uid, $page);
        $result['data']['list'] = $list;

        return_json_encode($result);
    }

    //搜索记录表
    public function search_log()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $list = db('search_log')
            ->where("uid=" . $uid)
            ->order("addtime desc")
            ->group('name')
            ->limit(20)
            ->select();

        $result['list'] = $list;

        return_json_encode($result);
    }

    //清空搜索记录
    public function clear_search_log()
    {
        $result = array('code' => 1, 'msg' => lang('Empty_successfully'));

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);
        //是否有搜索记录
        $search_log = db('search_log')->where("uid=" . $uid)->select();
        if ($search_log) {
            db('search_log')->where("uid=" . $uid)->delete();
        }

        return_json_encode($result);
    }

    //陪玩 首页-新人
    public function get_player_new_list()
    {
        //echo gmdate('H:i:s',0);die();
        $result = array('code' => 1, 'msg' => '');
        $page = input('page');
        $list = db("user_reference")
            ->alias('r')
            ->join('skills_recommend_label s', 's.id=r.recommend_label')
            ->join('skills_info i', 'i.id=r.uid')
            ->join('user u', 'u.id=i.uid')
            ->join('play_game g', 'g.id=i.game_id')
            ->field('i.*,u.user_nickname,u.sex,u.age,u.avatar,s.label_name,s.label_img,g.name as game_name')
            //->where(['r.type'=>1])
            ->where('i.status = 1 and r.type = 1')
            ->page($page, 3)
            ->select();

        foreach ($list as &$v) {
            $noble = get_noble_level($v['uid']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
        }

        //$res = $this->SkillsInfoModel->get_new($page);
        $result['data'] = $list;
        $config = load_cache('config');
        $result['advertise'] = $config['player_new_advertise'];
        return_json_encode($result);
    }

    //陪玩 陪玩列表
    public function get_player_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('page'));
        $sex = intval(input('sex'));//性别
        $game_id = intval(input('game_id'));//游戏
        $city = trim(input('city'));//城市
        $min_price = trim(input('min_price'));//最低价格
        $max_price = trim(input('max_price'));//最高价格
        $other = trim(input('other'));//其他
        $type = trim(input('type'));//类型 1推荐 2服务 3最新 4距离 5价格
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['longitude', 'latitude']);
        $where = [];
        switch ($type) {
            case 1:
                $order = 's.recommend_num desc';
                $where['s.recommend_num'] = '';
                $where['s.skills_order_num'] = '';
                break;
            case 2:
                $order = 's.skills_order_num desc';
                break;
            case 3:
                $order = 's.create_time desc';
                break;
            case 4:
                $order = 'distance asc';
                break;
            case 5:
                $order = 's.price asc';
                break;
            default:
                $order = '';
                break;
        }
        if ($sex) {
            $where['u.sex'] = $sex;
        }
        if ($game_id) {
            $where['s.game_id'] = $game_id;
        }
        if ($city) {
            $where['u.city'] = $city;
        }
        if ($min_price) {
            $where['s.price'] = ['egt', $min_price];
        }
        if ($max_price) {
            $where['s.price'] = ['elt', $max_price];
        }
        //echo $other;
        if ($other) {
            $arr = explode(',', $other);
            //dump($arr);
            foreach ($arr as $k => $v) {
                $where['s.other'] = ['like', '%' . $v . '%'];
            }
        }
        $where['u.is_player'] = 1;
        if ($game_id) {
            $res = $this->SkillsInfoModel->get_group_player_list($where, $page, $order, $user_info);
        } else {
            $res = $this->SkillsInfoModel->get_group_player_list($where, $page, '', $user_info);
        }

        $result['data'] = $res;
        return_json_encode($result);
    }

    //填写接单信息，获取游戏接单信息
    public function get_game_type()
    {
        $result = array('code' => 1, 'msg' => '');

        $game_id = intval(input('param.game_id'));//游戏ID
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        //$user_info = check_login_token($uid, $token);
        $data = $this->PlaywithModel->get_game_type($game_id);
        //接单类型
        //$order_type = ['局','name'=>'半小时','name'=>'一小时'];
        /*$order_type = [
            ['name'=>'局'],
            ['name'=>'半小时'],
            ['name'=>'一小时'],
        ];
        //价格
        */
        /*$price = [
            [
                'id'=>1,
                'name'=>'1-10币',
                'min_price'=>1,
                'max_price'=>10,
            ],
            [
                'id'=>2,
                'name'=>'11-20币',
                'min_price'=>11,
                'max_price'=>20,
            ],
            [
                'id'=>3,
                'name'=>'20币以上',
                'min_price'=>20,
                'max_price'=>0,
            ],
        ];*/
        $price = db('skills_search_price')->order('orderno')->select();

        $result['data']['list'] = $data;
        $result['data']['price'] = $price;;
        return_json_encode($result);
    }

    //遇见 猜你喜欢
    public function get_meet_like_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token);
        $page = input('page');
        //$res = $this->UserModel->get_meet_recommend($uid,$page);
        $list = db("user_reference")
            ->alias('r')
            ->join('skills_recommend_label s', 's.id=r.recommend_label')
            ->join('user u', 'u.id=r.uid')
            ->field('u.id,u.user_nickname,u.sex,u.age,u.avatar,s.label_name,s.label_img,u.city')
            ->where(['r.type' => 2, 'u.status' => 1, 'u.is_talker' => 1])
            ->page($page, 3)
            ->group('r.uid')
            ->select();
        $result['data'] = $list;
        return_json_encode($result);
    }

    //分类标签
    public function get_meet_type()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token);
        $list = db('auth_talker_label')->order('orderno')->select();
        $result['data'] = $list;
        return_json_encode($result);
    }

    //遇见
    public function get_meet_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = input('page');
        $key = trim(input('key'));//heart:热门 nearby:附近 标签id
        $lng = trim(input('lng'));//经度
        $lat = trim(input('lat'));//纬度
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['longitude', 'latitude']);
        if (!$lng || !$lat) {
            $lng = $user_info['longitude'];//经度
            $lat = $user_info['latitude'];//纬度
        }
        $res = $this->UserModel->get_meet_list($page, $key, $lng, $lat);
        $result['data'] = $res;
        return_json_encode($result);
    }

    //音遇
    public function get_audio_meet()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = input('page');
        $res = $this->UserModel->get_audio($page);
        $result['data'] = $res;
        return_json_encode($result);
    }

    //职业
    public function get_occupation()
    {
        $result = array('code' => 1, 'msg' => '');
        $res = db('user_occupation')->order('orderno')->select();
        $result['data'] = $res;
        return_json_encode($result);

    }

    //想认识你的人
    public function get_know_user()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token);
        //and l.status = 0
        $data = db('user_greet_log')
            ->alias('l')
            ->join('user u', 'u.id = l.uid')
            ->where('l.touid = ' . $uid . ' and l.uid != ' . $uid . ' and u.is_talker = 1')
            ->order('l.status asc')
            //->order('rand()')
            ->field('u.id,u.user_nickname,u.avatar,u.sex,u.age,u.is_online,u.city,u.label')
            ->page($page)
            ->select();
        $count = db('user_greet_log')
            ->alias('l')
            ->join('user u', 'u.id = l.uid')
            ->where('l.touid = ' . $uid . ' and l.status = 0 and l.uid != ' . $uid . ' and u.is_talker = 1')
            ->order('l.addtime desc')
            ->orderRaw('rand()')
            ->count();
        if (!$data) {
            $data = db('user')
                ->orderRaw('rand()')
                ->where('is_talker = 1 and id != ' . $uid)
                ->field('id,user_nickname,avatar,sex,age,is_online,city,label')
                ->page($page)
                ->select();
        }
        foreach ($data as &$v) {
            //$v['label'] = explode(',',$v['label']);
            if (!empty($v['label'])) {
                $v['label'] = explode(',', $v['label']);
            } else {
                $v['label'] = [];
            }
        }
        /*$data = db('user')
            ->order('rand()')
            ->where('is_talker = 1')
            ->field('id,user_nickname,avatar,is_online,city')
            ->page($page)
            ->select();*/
        $result['count'] = $count;
        $result['data'] = $data;
        return_json_encode($result);

    }

    //想认识你的人设置已读
    public function request_know_user_msg()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));
        $touid = intval(input('param.touid'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        //是否有未读消息
        $no_msg = db('user_greet_log')
            ->where('touid = ' . $uid . ' and status = 0 and uid = ' . $touid)
            ->find();
        if ($no_msg) {
            db('user_greet_log')->where(['id' => $no_msg['id']])->update(['status' => 1]);
        }
        return_json_encode($result);
    }

    //获取财富页相关信息
    public function get_wealth_page_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['friend_coin', 'score', 'coin_system', 'income']);

        //用户聊币月
        $result['coin'] = $user_info['coin'];
        $result['friend_coin'] = $user_info['friend_coin'];

        $config = load_cache('config');
        //分成比例
        $result['split'] = ($config['invite_income_ratio'] * 100);
        //总收益
        $result['income'] = $user_info['income'];

        return_json_encode($result);
    }

    // 1v1 首页全部
    public function get_1v1_all_list()
    {

        $page = intval(input('param.page'));

        $uid = intval(input('param.uid'));

        $user_info = get_user_base_info($uid, ['address']);

        $result = $this->PageDataModel->get_1v1_list($user_info, $page, 0);

        return_json_encode($result);
    }

    // 1v1新人列表
    public function get_news_user_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $page = intval(input('param.page'));

        $uid = intval(input('param.uid'));

        $user_info = get_user_base_info($uid, ['address']);

        $result = $this->PageDataModel->get_1v1_list($user_info, $page, 1);

        return_json_encode($result);
    }

    //首页1v1推荐接口
    public function recommend_user()
    {
        $page = intval(input('param.page'));

        $uid = intval(input('param.uid'));

        $user_info = get_user_base_info($uid, ['address']);

        $result = $this->PageDataModel->get_1v1_list($user_info, $page, 2);

        return_json_encode($result);
    }

    //1v1附近的人
    public function nearby_user()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $page = intval(input('param.page'));

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['latitude', 'longitude', 'address']);


        $result = $this->PageDataModel->get_1v1_list($user_info, $page, 3);

        return_json_encode($result);
    }

    // 随机匹配1v1聊天用户
    public function random_matching()
    {

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['address']);

        $result = $this->PageDataModel->random_matching($user_info);

        return_json_encode($result);
    }

    //随机获取随机匹配的主播
    public function random_1v1_chat()
    {

        $uid = intval(input('param.uid'));

        $result = $this->PageDataModel->about_love($uid);

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

    // 添加选中的标签
    public function add_voice_label_log()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        // 标签id
        $label_id = intval(input('param.label_id'));
        // 默认第一个
        $status = $this->VoiceModel->add_voice_label_log($uid, $label_id);

        if (!$status) {
            $result['code'] = 0;
            $result['msg'] = lang('Failed_to_add_label');
        }
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

    // 随机匹配1v1聊天列表
    public function random_1v1_chat00()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array(), 'count' => 0);
        //获取在线主播人数
        $online_emcee = db('user')->alias('u')->field('u.id,u.avatar,audio_file')->where('u.is_online', '=', 1)->where('u.is_auth', '=', 1)->limit(3)->select();

        $online_emcee_count = db('user')->where('is_online', '=', 1)->where('is_auth', '=', 1)->count();

        $result['data'] = count($online_emcee) > 0 ? $online_emcee : [];

        $result['count'] = $online_emcee_count ? $online_emcee_count : 0;

        return_json_encode($result);
    }

    //获取评价标签列表
    public function request_get_evaluate_list()
    {
        $result = array('code' => 1, 'msg' => '', 'list' => array());
        $uid = intval(input('param.uid'));

        $user_info = get_user_base_info($uid);

        $type = $user_info['sex'] == 2 ? 1 : 0;
        $result['list'] = db('evaluate_label')->where('type', '=', $type)->select();
        return_json_encode($result);
    }

    //我的收益
    public function get_user_income_page_info()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token, ['score', 'coin_system', 'income']);

        $config = load_cache('config');

        $result['income'] = $user_info['income'];
        $result['money'] = 0;
        if ($result['income'] > 0) {
            $result['money'] = number_format($result['income'] / $config['integral_withdrawal'], 2);
        }

        $result['list'] = db('user_cash_record')->where('user_id', '=', $uid)->page($page)->order('create_time desc')->select();

        foreach ($result['list'] as &$v) {
            $v['create_time'] = date('Y-m-d', $v['create_time']);

            if ($v['status'] == 0) {
                $v['status'] = lang('CHECK_LOADING');
            } else if ($v['status'] == 1) {
                $v['status'] = lang('Withdrawal_succeeded');
            } else {
                $v['status'] = lang('Withdrawal_failed');
            }
        }
        return_json_encode($result);
    }

    //举报类型
    public function get_report_type()
    {
        $result = array('code' => 1, 'msg' => '', 'list' => []);

        $result['list'] = db('user_report_type')->select();
        return_json_encode($result);

    }

    //黑名单
    public function black_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $p = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $list = db('user_black')
            ->alias('b')
            ->field('u.user_nickname,u.id,u.avatar,u.luck,u.sex,u.age')
            ->join('user u', 'u.id=b.black_user_id')
            ->where('b.user_id', '=', $uid)
            ->page($p)
            ->select();
        foreach ($list as &$v) {
            $noble = get_noble_level($v['id']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
        }

        $result['list'] = $list;

        return_json_encode($result);
    }

    //反馈
    public function buy_feedback()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $content = input('param.content');
        $tel = input('param.tel');
        $fileList = input('param.fileurl');

        $user_info = check_login_token($uid, $token, ['income_talker_total', 'income_player_total']);
        $data = [];
        $data['content'] = $content;
        $data['tel'] = $tel;
        $data['uid'] = $uid;
        $data['img'] = $fileList;
        $data['addtime'] = time();

        //添加记录
        $res = db('feedback')->insert($data);
        // var_dump(db()->getlastsql());exit;
        if ($res) {
            $result['code'] = 1;
            $result['msg'] = lang('Thank_you_for_your_comments');
        }
        return_json_encode($result);
    }

    /*
     * 获取声网验证token
     * */
    public function get_agora_token()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $channelName = input('channel_name');//频道名称
        //$voice_id = intval(input('voice_id'));//房间主播ID

        $user_info = check_login_token($uid, $token);
        require DOCUMENT_ROOT . '/system/agora/src/RtcTokenBuilder.php';
        $RtcTokenBuilder = new \RtcTokenBuilder();
        $role = $RtcTokenBuilder::RoleAttendee;
        $config = load_cache('config');
        $appID = $config['app_qgorq_key'];
        $appCertificate = $config['app_certificate'];
        //$appID = 'f123b66b25074d539d0c19a7dcb617c6';
        //$appCertificate = '751dff106c50461c8d4f736b5e378e66';
        //$channelName = $voice['id'];
        //$channelName = "7d72365eb983485397e3e3f9d460bdda";
        //$uid = 2882341273;
        //$uidStr = "2882341273";
        //$role = RtcTokenBuilder::RoleAttendee;
        $expireTimeInSeconds = 3600 * 24 * 3;
        $currentTimestamp = NOW_TIME;
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        $token = $RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);
        //dump($token);
        $result['token'] = $token;
        return_json_encode($result);
    }

}
