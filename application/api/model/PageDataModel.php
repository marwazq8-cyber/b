<?php

namespace app\api\model;

use think\Model;
use think\Db;
use think\helper\Time;
use VideoCallRedis;

class PageDataModel extends Model
{
    /**
     *   1v1列表
     *    user_info 本用户信息 page页数 type 0全部 1新人 2推荐 3附近
     *   sex 性别 address 0不限 1同城 age_small 最小年龄 age_big 最大年龄
     *   charging_coin_small 最少消费, charging_coin_big 最大消费 visualize_name 兴趣标签 多个用英文逗号隔开
     */
    public function get_1v1_list($user_info, $page, $type)
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        // 性别 1男2女
        $sex = intval(input('param.sex'));
        // 最小年龄
        $age_small = intval(input('param.age_small'));
        // 最大年龄
        $age_big = intval(input('param.age_big'));
        // 0不限 1同城
        $address = intval(input('param.address'));
        // 最小收费金额
        $charging_coin_small = intval(input('param.charging_coin_small'));
        // 最大收费金额
        $charging_coin_big = intval(input('param.charging_coin_big'));
        // 兴趣标签 多个用英文逗号隔开
        $visualize_name = trim(input('param.visualize_name'));

        $config = load_cache('config');

        $field = 'id,sex,user_nickname,avatar,income_level,level,custom_video_charging_coin,signature,is_online,city,province,is_auth,latitude,longitude,audio_file,visualize_name,audio_time,age,is_voice_online,vip_end_time';

        $order = 'is_online desc,create_time desc';

        $where = 'user_status!=0 and id!=1  and id !=' . $user_info['id'];
        //是否展示离线用户
        $where .= $config['is_show_offline_user'] != 1 ? ' and is_online=1' : '';
        // 用户是否已认证 未认证只能查看认证过的用户
        $where .= $user_info['is_auth'] == 1 ? '' : ' and is_auth=1';

        if ($type == 1) {
            // 是否是最新列表
            $time = Time::dayToNow(1000);

            $where .= ' and create_time >=' . $time[0];

        } else if ($type == 2) {
            // 推荐
            $where .= ' and reference=1';

            $order = "sort desc,is_online desc,level desc,income_total desc";

        } else if ($type == 3) {
            //单位米
            $radius = 22222;

            $lng = $user_info['longitude'];

            $lat = $user_info['latitude'];

            $scope = calcScope($lat, $lng, $radius);   // 调用范围计算函数，获取最大最小经纬度

            $where .= ' and latitude < ' . $scope['maxLat'] . ' and latitude > ' . $scope['minLat'] . ' and longitude < ' . $scope['maxLng'] . ' and longitude > ' . $scope['minLng'];

            $order = "SQRT((" . $lng . "- longitude)*(" . $lng . "- longitude)+(" . $lat . "- latitude )*(" . $lat . "- latitude))";
        }
        // 匹配筛选

        $where .= $sex > 0 ? ' and sex=' . $sex : '';
        $where .= $age_small > 0 ? ' and age >=' . $age_small : '';
        $where .= $age_big > 0 ? ' and age <=' . $age_big : '';
        if ($address == 1) {
            $where .= $user_info['city'] ? ' and province="' . $user_info['province'] . '" and city="' . $user_info['city'] . '"' : ' and address="' . $user_info['address'] . '"';
        }

        $where .= $charging_coin_small > 0 ? ' and custom_video_charging_coin >=' . $charging_coin_small : '';
        $where .= $charging_coin_big > 0 ? ' and custom_video_charging_coin <=' . $charging_coin_big : '';

        if ($visualize_name) {

            $var = explode(",", $visualize_name);

            $visualize_where = '';

            foreach ($var as $v) {
                $visualize_where .= $v ? ' visualize_name like "%' . $v . '%" or' : '';
            }

            $visualize_where = rtrim($visualize_where, "or");

            $where .= ' and (' . $visualize_where . ')';
        }

        $user_list = db('user')->where($where)->field($field)->order($order)->page($page)->select();
        foreach ($user_list as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
            if ($type == 3) {
                // 获取 距离
                $v['distance'] = getDistance($lng, $lat, $v['longitude'], $v['latitude'], 2, 2) . 'km';

                $v['signature'] = empty($v['signature']) ? '等你来撩！' : $v['signature'];
            }
        }

        $result['data'] = user_info_complete($user_list);

        return_json_encode($result);
    }

    /**
     *   随机匹配1v1聊天用户
     *    user_info 本用户信息 sex 性别 address 0不限 1同城
     *    charging_coin_small 最少消费, charging_coin_big 最大消费 touid 去除当前匹配的用户
     */
    public function random_matching($user_info)
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $sex = intval(input('param.sex'));

        $address = intval(input('param.address'));             // 0不限 1同城

        $charging_coin_small = trim(input('param.charging_coin_small'));

        $charging_coin_big = trim(input('param.charging_coin_big'));

        $touid = trim(input('param.touid'));               // 去除当前匹配的用户

        $where = "a.is_online=1 and a.is_voice_online=1 and a.is_auth=1 and a.id !=" . $user_info['id'] . " and v.id is null and c.id is null and l.id is null";

        $where .= $sex > 0 ? " and a.sex=" . $sex : '';

        if ($address == 1) {
            $where .= $user_info['city'] ? ' and a.province="' . $user_info['province'] . '" and a.city="' . $user_info['city'] . '"' : ' and a.address="' . $user_info['address'] . '"';
        }

        $where .= $charging_coin_small > 0 ? " and a.custom_video_charging_coin >=" . $charging_coin_small : '';

        $where .= $charging_coin_big ? " and a.custom_video_charging_coin <=" . $charging_coin_big : '';

        $where .= $touid ? " and a.id !=" . $touid : '';
        //字段
        $field = 'a.id,a.sex,a.user_nickname,a.avatar,a.income_level,a.level,a.custom_video_charging_coin,a.signature,a.is_online,a.city,a.province,a.is_auth,a.audio_file,a.visualize_name,a.audio_time,a.age,a.vip_end_time';

        //获取在线主播人数
        //    $online_emcee = db('user')->field($field)->where($where)->order("RAND()")->find();


//bogo_voice_even_wheat_log
        $online_emcee = db('user')->alias('a')
            ->join('video_call_record v', 'v.user_id=a.id', 'left')
            ->join('video_call_record c', 'c.call_be_user_id=a.id', 'left')
            ->join('voice_even_wheat_log l', 'l.user_id=a.id and l.status=1', 'left')
            ->field($field)
            ->group("a.id")
            ->where($where)
            ->orderRaw("RAND()")
            ->find();

        if ($online_emcee) {

            $config = load_cache('config');

            $level = get_level($online_emcee['id']);
            $online_emcee['income_level'] = get_income_level($online_emcee['id']);

            $online_emcee['level'] = $level;
            //分钟扣费金额
            $online_emcee['charging_coin'] = $config['video_deduction'];

            if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1 && isset($online_emcee['custom_video_charging_coin']) && $level >=
                $config['custom_video_money_level'] && $online_emcee['custom_video_charging_coin'] > 0) {

                $online_emcee['charging_coin'] = $online_emcee['custom_video_charging_coin'];
            }

            if (isset($online_emcee['custom_video_charging_coin'])) {

                unset($online_emcee['custom_video_charging_coin']);
            }

            //认证信息
            $auth_info = db('auth_form_record')->field('height')->where('user_id', '=', $online_emcee['id'])->find();

            if ($auth_info) {

                $online_emcee['height'] = $auth_info['height'] . 'CM';
            }
            $online_emcee['is_vip'] = get_is_vip($online_emcee['vip_end_time']);

            $online_emcee['vip_price'] = $online_emcee['charging_coin'] / 2;
        }

        if (count($online_emcee) <= 0) {

            $result['code'] = 0;

            $result['msg'] = lang('Match_failed');

            return_json_encode($result);
        }

        $result['data'] = $online_emcee;

        if ($user_info['coin'] < $online_emcee['charging_coin']) {

            $result['code'] = 10002;

            $result['msg'] = lang('Insufficient_Balance');

            return_json_encode($result);
        }

        return_json_encode($result);
    }

    /**
     *  随机获取一键约爱的主播
     *    uid 本用户id
     */
    public function about_love($uid)
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $where = 'is_open_do_not_disturb !=1 and is_auth =1 and is_online=1 and id !=1 and id !=' . $uid;

        $list = db('user')->where($where)->field("avatar,id")->limit(15)->order('rand()')->select();

        $count = db('user')->where($where)->count();

        $data = [];

        $i = 0;

        foreach ($list as $k => $v) {

            $is_call = db('video_call_record')->where('anchor_id=' . $v['id'] . ' and status  > 1')->find();

            if (!$is_call) {

                $data[] = $v;

                if ($i == 2) {

                    break;
                }

                $i++;
            }
        }

        $result['data'] = $data;

        $result['count'] = $count;

        return_json_encode($result);
    }

    /**
     * 获取语音房列表
     * */
    public function get_voice_list($where, $order, $page)
    {

        $field = 'a.luck,a.user_nickname,a.is_online,v.title,y.name,y.img,v.id,v.voice_type,v.avatar,v.type,v.voice_status,v.voice_psd,v.user_id,a.vip_end_time,a.country_code';

        $data = db('user')->alias('a')
            ->join('voice v', 'v.user_id=a.id')
            ->join('voice_type y', 'y.id=v.voice_type')
            ->field($field)
            ->group("a.id")
            ->where($where)
            ->order($order)
            ->page($page)
            ->select();

        // 获取国家列表
        $country_list = getNewArrayKFromV(getCountryList(), 'num_code');

        foreach ($data as &$v) {
            // 获取 房间 人数
            $v['watch_number'] = voice_userlist_sum($v['user_id']);
            $v['country_name'] = '';
            $v['country_flag_img_url'] = '';
            if (isset($v['country_code']) && $v['country_code'] != 0) {
                $country = get_country_one(intval($v['country_code'])); // 获取国家
                if ($country) {
                    $v['country_name'] = $country['name'];
                    $v['country_flag_img_url'] = $country['img'];
                }
            }
        }

        return $data;
    }

    /* 获取直播首页权重 */
    public function get_config_weight()
    {

        $sorting_weight = db('config')->field("code,val")->where("group_id='排序权重'")->select();

        return $sorting_weight;
    }

    /* 获取直播 列表 */
    public function get_popular_recommendation_list($page)
    {

        $field = 'a.user_nickname,a.is_online,v.title,y.name,y.img,v.id,v.voice_type,v.avatar,v.type,v.voice_status,v.voice_psd,v.user_id,a.vip_end_time';

        $data = db('user')->alias('a')
            ->join('voice v', 'v.user_id=a.id')
            ->join('voice_type y', 'y.id=v.voice_type')
            ->field($field)
            ->group("a.id")
            ->where('v.online_number > 0 and v.live_in = 1')
            ->orderRaw('rand()')
            ->page($page, 3)
            ->select();

        foreach ($data as &$v) {
            // 是否是vip
            $v['is_vip'] = get_is_vip($v['vip_end_time']);
            //获取房间人数
            $v['watch_number'] = voice_userlist_sum($v['user_id']);
        }

        return $data;
    }
}