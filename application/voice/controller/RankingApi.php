<?php

namespace app\voice\controller;

use app\api\controller\Base;
use app\api\model\VoiceModel;
use think\Config;
use think\Db;

class RankingApi extends Base
{

    protected function _initialize()
    {
        parent::_initialize();

        $this->VoiceModel = new VoiceModel();
    }

    /**
     * 首页-排行榜-财富榜
     **/
    public function wealth_ranking()
    {
        $result = array('code' => 1, 'msg' => '');

        $type = trim(input('param.type')) ? trim(input('param.type')) : "day";     //day日榜 weeks周榜 month月榜
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1; // 分页
    //    $country_code = intval(input('param.country_code')); // 国家代号

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['consumption_total']);

        $config = load_cache('config');
        if ($config['rank_coin_type'] == 1) {
            $profit = 'l.profit';
        } else {
            $profit = 'l.gift_coin';
        }

        $where = 'l.id > 0';
        if ($type == 'day') {
            $where .= ' and l.date_y_m_d = "' . date('Y-m-d').'"';
        } else if ($type == 'weeks') {
            $w= date('Y')."-".date('W');
            $where .= ' and l.date_y_w = "' .$w.'"';
        } else if ($type == 'month') {
            $where .= ' and l.date_y_m = "' . date('Y-m').'"';
        }

        $list = db('user_gift_log')->alias('l')
            ->field("sum($profit) as  total_value,l.user_id as id")
            ->where($where)
            ->group("l.user_id")
            ->order("total_value desc")
            ->page($page)
            ->select();

        foreach ($list as &$v) {
            $user_one = json_decode(redis_hGet("user_list",$v['id']),true);
            $v['user_nickname'] =$user_one ? $user_one['user_nickname'] : '';
            $v['avatar'] =$user_one ? $user_one['avatar'] : '';
            $v['sex'] =$user_one ? $user_one['sex'] : '';
            $v['age'] =$user_one ? $user_one['age'] : '';
            $v['level'] =$user_one ? $user_one['level'] : '';
            $v['country_code'] =$user_one ? $user_one['country_code'] : '';
            $consumption_total= $user_one && isset($user_one['consumption_total']) ? $user_one['consumption_total'] : 0;
            // 财富等级
            $level = getWealthLevelRuleInfoByTotalValue($consumption_total);
            $userdata =  $data = Db::name('user')
            ->where('id', $v['id'])
            ->find();
            $v['nobility_level'] =$userdata ? $userdata['nobility_level'] : 0;
            $noble =  $data = Db::name('noble')
            ->where('id', $v['nobility_level'])
            ->find();
            $v['level_img'] = $level ? $level['chat_icon'] : '';
            // 是否有vip等级
            //$v['vip_img'] = trim(get_user_vip_authority($v['id'], 'identity_app'));
            $v['vip_img'] = $noble['profile_icon'];
            $v['vip_badge'] = $noble['noble_img'];
            // 获取国家信息
            $country = get_country_one(intval($v['country_code'])); // 获取国家
            $v['country_flag_img_url'] = $country ? $country['img'] : '';
        }

        $result['data'] = $list;
        return_json_encode($result);
    }

    /**
     * 首页-排行榜-魅力榜
     **/
    public function glamour_ranking()
    {
        $result = array('code' => 1, 'msg' => '');
        $type = trim(input('param.type')) ? trim(input('param.type')) : "day";     //day日榜 weeks周榜 month月榜
      //  $country_code = intval(input('param.country_code')); // 国家代号
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1; // 分页

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token, ['charm_values_total']);

        $config = load_cache('config');
        if ($config['rank_coin_type'] == 1) {
            $profit = 'l.profit';
        } else {
            $profit = 'l.gift_coin';
        }
        $where = 'l.id > 0';
        if ($type == 'day') {
            $where .= ' and l.date_y_m_d = "' . date('Y-m-d').'"';
        } else if ($type == 'weeks') {
            $w= date('Y')."-".date('W');
            $where .= ' and l.date_y_w = "' .$w.'"';
        } else if ($type == 'month') {
            $where .= ' and l.date_y_m = "' . date('Y-m').'"';
        }

        $list = db('user_gift_log')->alias('l')
            ->field("sum($profit) as  total_value,l.to_user_id as id")
            ->where($where)
            ->group("l.to_user_id")
            ->order("total_value desc")
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            $user_one = json_decode(redis_hGet("user_list",$v['id']),true);
            $v['user_nickname'] =$user_one ? $user_one['user_nickname'] : '';
            $v['avatar'] =$user_one ? $user_one['avatar'] : '';
            $v['sex'] =$user_one ? $user_one['sex'] : '';
            $v['age'] =$user_one ? $user_one['age'] : '';
            $v['level'] =$user_one ? $user_one['level'] : '';
            $v['country_code'] =$user_one ? $user_one['country_code'] : '';
            $charm_values_total= $user_one && isset($user_one['charm_values_total']) ? $user_one['charm_values_total'] : 0;
            // 财富等级
            $level = getWealthLevelRuleInfoByTotalValue($charm_values_total, 2);
            $userdata =  $data = Db::name('user')
            ->where('id', $v['id'])
            ->find();
            $v['nobility_level'] =$userdata ? $userdata['nobility_level'] : 0;
            $noble =  $data = Db::name('noble')
            ->where('id', $v['nobility_level'])
            ->find();
            $v['level_img'] = $level ? $level['chat_icon'] : '';
            // 是否有vip等级
            //$v['vip_img'] = trim(get_user_vip_authority($v['id'], 'identity_app'));
            $v['vip_img'] = $noble['profile_icon'];
            $v['vip_badge'] = $noble['noble_img'];
            // 是否有vip等级
            //$v['vip_img'] = trim(get_user_vip_authority($v['id'], 'identity_app'));
            // 获取国家信息
            $country = get_country_one(intval($v['country_code'])); // 获取国家
            $v['country_flag_img_url'] = $country ? $country['img'] : '';
        }

        $result['data'] = $list;
        return_json_encode($result);
    }

    /**
     * 首页-排行榜-房间榜
     */
    public function room_ranking()
    {
        $result = array('code' => 1, 'msg' => '');
        $type = trim(input('param.type'));     //hour小时day日榜 weeks周榜 month 月
      //  $country_code = intval(input('param.country_code')); // 国家代号
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1; // 分页
        if ($page > 5) {
            $result['data'] = [];
            return_json_encode($result);
        }

        $where = "l.type=4 ";
        switch ($type){
            case 'hour':
                $where .= ' and l.date_y_m_d_h = "' . date('Y-m-d')."-".date('H').'"';
                break;
            case 'day':
                $where .= ' and l.date_y_m_d = "' . date('Y-m-d').'"';
                break;
            case 'weeks':
                $w= date('Y')."-".date('W');
                $where .= ' and l.date_y_w = "' .$w.'"';
                break;
            case 'month':
                $where .= ' and l.date_y_m = "' . date('Y-m').'"';
                break;
            default:
        }
        $config = load_cache('config');
        if ($config['rank_coin_type'] == 1) {
            $profit = 'l.profit';
        } else {
            $profit = 'l.gift_coin';
        }
        $list = db('user_gift_log')->alias('l')
            ->field("sum($profit) as  total_value,l.voice_user_id as id")
            ->where($where)
            ->group("l.voice_user_id")
            ->order("total_value desc")
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            $user_one = json_decode(redis_hGet("user_list",$v['id']),true);
            $v['user_nickname'] =$user_one ? $user_one['user_nickname'] : '';
            $v['avatar'] =$user_one ? $user_one['avatar'] : '';
            $v['sex'] =$user_one ? $user_one['sex'] : '';
            $v['age'] =$user_one ? $user_one['age'] : '';
            $v['level'] =$user_one ? $user_one['level'] : '';
            $v['country_code'] =$user_one ? $user_one['country_code'] : '';
            $userdata =  $data = Db::name('user')
            ->where('id', $v['id'])
            ->find();
            $v['nobility_level'] =$userdata ? $userdata['nobility_level'] : 0;
            $noble =  $data = Db::name('noble')
            ->where('id', $v['nobility_level'])
            ->find();
            $charm_values_total= $user_one && isset($user_one['charm_values_total']) ? $user_one['charm_values_total'] : 0;
            // 财富等级
            $level = getWealthLevelRuleInfoByTotalValue($charm_values_total, 2);
            $v['level_img'] = $level ? $level['chat_icon'] : '';
            // 是否有vip等级
            //$v['vip_img'] = trim(get_user_vip_authority($v['id'], 'identity_app'));
            $v['vip_img'] = $noble['profile_icon'];
            $v['vip_badge'] = $noble['noble_img'];
            // 获取国家信息
            $country = get_country_one(intval($v['country_code'])); // 获取国家
            $v['country_flag_img_url'] = $country ? $country['img'] : '';
            // 获取房间信息
            $voice_list_information= json_decode(redis_hGet("voice_list_information",$v['id']),true);

            $v['title'] =$voice_list_information ? $voice_list_information['title'] : '';
            $v['room_img'] =$voice_list_information ? $voice_list_information['avatar'] : '';
            $v['voice_label'] =$voice_list_information ? $voice_list_information['voice_label'] : '';
        }
        $result['data'] = $list;
        return_json_encode($result);
    }


    // 获取榜单说明信息
    public function get_list_description()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $config = load_cache('config');
        $data['list_description'] = $config['list_description'];
        $result['data'] = $data;
        return_json_encode($result);
    }

    // 房间内-排行榜-真爱榜
    public function consumption_ranking_list()
    {
        //查询语音房间
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));     //分页数据
        $type = trim(input('param.type'));     //all总榜 weeks周榜 month 月
        $id = trim(input('param.id'));    //房间id --- 如果空，就是查询所有的
        if ($type == 'month') {
            $sdefaultDate = date("Y-m-01 0:0:0");
            $startime = strtotime($sdefaultDate);
        } else if ($type == 'weeks') {
            $sdefaultDate = date("Y-m-d");
            //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
            $first = 1;
            //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
            $w = date('w', strtotime($sdefaultDate));
            //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
            $startime = strtotime(date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')));
        } else {
            $startime = '';
        }
        $config = load_cache('config');
        if ($config['rank_coin_type'] == 1) {
            $profit = 'l.profit';
        } else {
            $profit = 'l.gift_coin';
        }
        $where = "l.type=4 ";
        $where .= $id ? " and l.room_id =" . $id : '';
        $where .= $startime ? " and l.create_time >=" . $startime : '';
        if ($page > 5) {
            $list = [];
        } else {
            $list = db('user')->alias('a')
                ->join('user_gift_log l', 'l.user_id=a.id')
                ->field("a.user_nickname,a.avatar,a.sex,a.age,a.level,sum($profit) as total_diamonds,a.id")
                ->where($where)
                ->group("a.id")
                ->order("total_diamonds desc")
                ->page($page)
                ->select();
        }
        foreach ($list as $k => $v) {
            //陪聊等级
            $talker_level = get_talker_level($v['id']);
            $list[$k]['talker_level_name'] = $talker_level['talker_level_name'];
            $list[$k]['talker_level_img'] = $talker_level['talker_level_img'];
            //陪玩等级
            $player_level = get_player_level($v['id']);
            $list[$k]['player_level_name'] = $player_level['player_level_name'];
            $list[$k]['player_level_img'] = $player_level['player_level_img'];
        }
        $result['list'] = $list;
        return_json_encode($result);
    }

    //排行榜 ---魅力榜
    public function earnings_ranking_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));     //分页数据
        $type = trim(input('param.type'));     //all总榜 weeks周榜 month 月榜
        $id = trim(input('param.id'));    //房间id --- 如果空，就是查询所有的
        if ($type == 'month') {
            $sdefaultDate = date("Y-m-01 0:0:0");
            $startime = strtotime($sdefaultDate);
        } else if ($type == 'weeks') {
            $sdefaultDate = date("Y-m-d");
            //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
            $first = 1;
            //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
            $w = date('w', strtotime($sdefaultDate));
            //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
            $startime = strtotime(date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')));
        } else {
            $startime = '';
        }
        $config = load_cache('config');
        if ($config['rank_coin_type'] == 1) {
            $profit = 'l.profit';
        } else {
            $profit = 'l.gift_coin';
        }
        $where = "l.type=4 ";
        $where .= $id ? " and l.room_id =" . $id : '';
        $where .= $startime ? " and l.create_time >=" . $startime : '';
        if ($page > 5) {
            $list = [];
        } else {
            $list = db('user')->alias('a')
                ->join('user_gift_log l', 'l.to_user_id=a.id')
                ->field("a.user_nickname,a.avatar,a.sex,a.age,a.level,sum($profit) as  total_ticket,a.id")
                ->where($where)
                ->group("a.id")
                ->order("total_ticket desc")
                ->page($page)
                ->select();
        }
        foreach ($list as $k => $v) {
            //陪聊等级
            $talker_level = get_talker_level($v['id']);
            $list[$k]['talker_level_name'] = $talker_level['talker_level_name'];
            $list[$k]['talker_level_img'] = $talker_level['talker_level_img'];
            //陪玩等级
            $player_level = get_player_level($v['id']);
            $list[$k]['player_level_name'] = $player_level['player_level_name'];
            $list[$k]['player_level_img'] = $player_level['player_level_img'];
        }

        $result['list'] = $list;
        return_json_encode($result);
    }

    //语音房间排行榜 ---房间排行
    public function room_ranking_lists()
    {
        $result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page'));     //分页数据
        $type = trim(input('param.type'));     //all总榜 weeks周榜 month 月

        if ($type == 'month') {
            $sdefaultDate = date("Y-m-01 0:0:0");
            $startime = strtotime($sdefaultDate);
            //dump($sdefaultDate);die();

        } else if ($type == 'weeks') {
            $sdefaultDate = date("Y-m-d");
            //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
            $first = 1;
            //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
            $w = date('w', strtotime($sdefaultDate));
            //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
            $startime = strtotime(date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')));
        } else {
            $startime = '';
        }
        $config = load_cache('config');
        if ($config['rank_coin_type'] == 1) {
            $profit = 'l.profit';
        } else {
            $profit = 'l.gift_coin';
        }
        $where = "l.type=2 ";
        $where .= $startime ? " and l.create_time >=" . $startime : '';
        $list = db('user')->alias('a')
            ->join('user_gift_log l', 'l.voice_user_id=a.id')
            ->join('voice v', 'v.user_id=a.id')
            ->field("a.user_nickname,a.avatar,a.sex,a.age,a.level,sum($profit) as  total_ticket,a.id,v.title,v.avatar as room_img")
            ->where($where)
            ->group("a.id")
            ->order("total_ticket desc")
            ->page($page)
            ->select();

        foreach ($list as $k => $v) {
            //陪聊等级
            $talker_level = get_talker_level($v['id']);
            $list[$k]['talker_level_name'] = $talker_level['talker_level_name'];
            $list[$k]['talker_level_img'] = $talker_level['talker_level_img'];
            //陪玩等级
            $player_level = get_player_level($v['id']);
            $list[$k]['player_level_name'] = $player_level['player_level_name'];
            $list[$k]['player_level_img'] = $player_level['player_level_img'];
        }
        $result['list'] = $list;

        return_json_encode($result);
    }

}