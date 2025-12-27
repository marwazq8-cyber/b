<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-06-02
 * Time: 14:30
 */

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\UserModel;

//use app\api\model\LoginModel;
use app\vue\model\VoiceModel;
use app\vue\model\BzoneModel;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class UserVueApi extends Base
{
    private $UserModel;
    //private $LoginModel;
    private $VoiceModel;
    private $BzoneModel;

    protected function _initialize()
    {
        parent::_initialize();

        $this->UserModel = new UserModel();
        //$this->LoginModel = new LoginModel();
        $this->VoiceModel = new VoiceModel();
        $this->BzoneModel = new BzoneModel();
    }

    // 获取幸运礼物记录表 更改奖池前:405650; 变更奖池数量(头奖): -100;更改后奖池余额:405550
    public function lucky_gift_log()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $page = intval(input('param.page'));
        check_login_token($uid, $token);
        $result['code'] = 1;
        $where = 'l.uid = ' . $uid . ' and l.status > 0 ';
        $list = db('gift_lucky_log')
            ->alias('l')
            ->join('user u', 'u.id=l.host_id')
            ->field('l.*,u.user_nickname')
            ->order('l.addtime desc')
            ->where($where)
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //关注/粉丝列表 vue
    public function get_attention_vue()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));

        $page = intval(input('param.page'));

        $type = intval(input('param.type', 1));
        if ($type == 1) {
            $where = "a.uid=" . $uid;
            // 获取关注列表
            $attention = $this->UserModel->focus_fans_list($where, $page, "a.attention_uid=u.id");
            foreach ($attention as &$v) {
                // 是否关注对方
                $focus = $this->UserModel->is_focus_user($v['id'], $uid);
                $v['focus'] = $focus ? 1 : 0;
                // 本用户是否在语音房间
            }
        } else {
            $where = "a.attention_uid=" . $uid;
            // 获取关注列表
            $attention = $this->UserModel->focus_fans_list($where, $page, "a.uid=u.id");
            foreach ($attention as &$v) {
                // 是否关注对方
                $focus = $this->UserModel->is_focus_user($uid, $v['id']);
                $v['focus'] = $focus ? 1 : 0;
                // 本用户是否在语音房间
            }
        }
        $result['data'] = $attention;

        return_json_encode($result);
    }

    //关注和取消 vue
    public function click_attention_vue()
    {
        $result = array('code' => 1, 'msg' => lang('Focus_on_success'));

        $uid = input('param.uid');
        $token = input('param.token');
        $id = input('param.id');

        $user_info = check_login_token($uid, $token);

        $attention = db('user_attention')->where("uid=$uid and attention_uid=$id")->find();
        if ($attention) {
            $result['msg'] = lang('Unsubscribe_successfully');
            $atte = db('user_attention')->where("uid=$uid and attention_uid=$id")->delete();
            if (!$atte) {
                $result['code'] = 0;
                $result['msg'] = lang('Failed_to_cancel_following');
            }

            $result['follow'] = 0;
        } else {
            $data = array(
                'uid' => $uid,
                'attention_uid' => $id,
                'addtime' => NOW_TIME
            );
            $atte = db('user_attention')->insert($data);
            if (!$atte) {
                $result['code'] = 0;
                $result['msg'] = lang('Failed_to_follow');
            }

            $result['follow'] = 1;
        }
        return_json_encode($result);

    }

    //帮助
    public function get_help()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = input('id', 1);
        $list = db('portal_category_post')
            ->alias('c')
            ->join('portal_post p', 'p.id=c.post_id')
            ->where('c.category_id = ' . $id . ' and c.status = 1 and p.delete_time=0')
            ->field('p.id,p.post_title')
            ->select();
        $type_list = db('portal_category')->where('parent_id = 19 and delete_time=0')->select();
        foreach ($type_list as &$v) {
            $more = json_decode($v['more']);
            $v['img'] = $more;
        }
        $result['data']['list'] = $list;
        $result['data']['type_list'] = $type_list;
        return_json_encode($result);
    }

    //帮助文章详情
    public function get_help_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = input('id');
        $list = db('portal_post')
            ->where('id = ' . $id)
            ->field('*')
            ->find();
        //$type_list = db('portal_category')->where('parent_id = 19')->select();
        //$result['data']['list'] = $list;
        //html_entity_decode()
        $list['post_content'] = html_entity_decode($list['post_content']);
        $result['data']['info'] = $list;
        return_json_encode($result);
    }

    //邀请信息
    public function get_invite_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        //$id = input('param.id');

        $user_info = check_login_token($uid, $token);
        $config = load_cache('config');
        $exchange = 1; // $config['invitation_exchange']
        //$time = strtotime(date('Y-m-d',NOW_TIME));
        //$start_day = $time - 86400;
        $config = load_cache('config');
        $time_num = $config['invite_income_expired_time'];
        if ($time_num > 0) {
            $time = strtotime(date('Y-m-d', NOW_TIME));
            $start_day = $time - (86400 * $time_num);
            $record_where = 'create_time > ' . $start_day . ' and create_time < ' . NOW_TIME;
        } else {
            $record_where = '';
        }

        $income = db('invite_profit_record')
            ->where($record_where)
            ->where('user_id = ' . $uid . ' and status=0')
            ->sum('income');

        $user_num = db('invite_profit_record')
            ->alias('i')
            ->join('user u', 'u.id=i.user_id')
            ->where(['i.status' => 1])
            ->group('i.user_id')
            ->field('sum(total_coin) as coinall')
            ->count();
        $user_coin = db('invite_profit_record')
            ->alias('i')
            ->join('user u', 'u.id=i.user_id')
            ->where(['i.status' => 1])
            ->sum('i.income');
        $coin_tow = db('invite_redbag')->where('type = 1')->sum('coin');
        $coin_three = db('invite_redbag')->where('type = 2')->sum('coin');
        //轮播广告
        $banner = db('invite_receive_log')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->field('i.*,u.user_nickname')
            ->order('i.addtime desc')
            ->limit(5)
            ->select();

        if ($banner) {
            $banner_msg = '';
            foreach ($banner as $val) {
                $income_to = $val['income'];
                $msg = lang('congratulations') . $val['user_nickname'] . lang('Get_rewards') . $income_to;
                $banner_msg .= $msg . ' ';
            }
        } else {
            $banner_msg = lang('Congratulations_to_user_for_winning_reward');
        }
        $server = 'http://' . $_SERVER['HTTP_HOST'] . '/api/download_api/index?invite_code=' . $uid;
        $invite_income_ratio = $config['invite_income_ratio'] > 0 ? (100 * $config['invite_income_ratio']) : 0;
        $invite_income_ratio_female = $config['invite_income_ratio_female'] > 0 ? (100 * $config['invite_income_ratio_female']) : 0;
        $data['invite_info'] = [
            'start_time' => '2020.08.01',//开始时间
            'end_time' => '2020.12.30',//结束时间
            'invite_income_ratio' => $invite_income_ratio,
            'invite_income_ratio_female' => $invite_income_ratio_female,
            'coin_one' => $invite_income_ratio_female . '%',
            'coin_tow' => $coin_tow > 0 && $exchange > 0 ? round($coin_tow / $exchange, 2) : 0,
            'coin_three' => $coin_three > 0 && $exchange > 0 ? round($coin_three / $exchange, 2) : 0,
            'income' => $income > 0 && $exchange > 0 ? round($income / $exchange, 2) : 0,
            'user_num' => $user_num,
            'user_coin' => $user_coin > 0 && $exchange > 0 ? round($user_coin / $exchange, 2) : 0,
            'banner_msg' => $banner_msg,
            'server' => $server,
            'open_login_qq' => $config['open_login_qq'],
            'open_login_facebook' => $config['open_login_facebook'],
            'open_login_wx' => $config['open_login_wx'],
            'registration_period_tycoon_rewards' => $config['registration_period_tycoon_rewards'],
            'registration_period_goddess_rewards' => $config['registration_period_goddess_rewards'],
            'accumulated_income_tycoon_awards' => $config['accumulated_income_tycoon_awards'],
            'accumulated_earnings_goddess_award' => $config['accumulated_earnings_goddess_award'],
        ];
        $list = db('invite_profit_record')
            ->alias('i')
            ->join('user u', 'u.id=i.user_id')
            ->where(['i.status' => 1])
            ->group('i.user_id')
            ->field('i.id,sum(i.income) as total_income,u.user_nickname,u.avatar')
            ->limit(5)
            ->select();

        foreach ($list as &$v) {
            $v['total_income'] = $v['total_income'] > 0 ? round($v['total_income'] / $exchange, 2) : 0;
        }

        // 获取页面背景图片
        $picture_resources = db('picture_resources')->where("identifier='invitation_background'")->find();
        // 获取页面标题图片
        $invitation_title_map = db('picture_resources')->where("identifier='invitation_title_map'")->find();

        $data['invitation_background'] = $picture_resources ? $picture_resources['img'] : '';
        $data['invitation_title_img'] = $invitation_title_map ? $invitation_title_map['img'] : '';
        $data['rank_list'] = $list;
        $data['user_info'] = $user_info;
        $data['exchange'] = $exchange;
        $result['data'] = $data;
        return_json_encode($result);
    }

    //邀请红包
    public function get_invite_bag()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $type = input('param.type');

        $user_info = check_login_token($uid, $token);
        $list = db('invite_redbag')->where('type = ' . $type)->select();

        $config = load_cache('config');
        if ($type == 1) {
            $coin = intval($config['accumulated_income_tycoon_awards']); // accumulated_income_tycoon_awards=500
            $day = intval($config['registration_period_tycoon_rewards']); // registration_period_tycoon_rewards = 7
            $where = ['i.user_id' => $uid, 'type' => 2];
        } else {
            $coin = intval($config['accumulated_earnings_goddess_award']); // accumulated_earnings_goddess_award =1000
            $day = intval($config['registration_period_goddess_rewards']); // registration_period_goddess_rewards = 10
            $where = ['i.user_id' => $uid, 'type' => 3];
        }

        $day_time = NOW_TIME - $day * 60 * 60 * 24;
        $profit_record = db('invite_profit_record')->alias("i")
            ->join("user u", "u.id = i.invite_user_id")
            ->where($where)
            ->where('u.create_time >=' . $day_time)
            ->group('i.invite_user_id')
            ->field('sum(i.total_coin) as coinall')
            ->select();

        $exchange = 1; // $config['invitation_exchange']
        $num = 0;
        foreach ($profit_record as $v) {
            if ($v['coinall'] >= $coin) {
                $num++;
            }
        }
        //是否够条件领取 是否领取过
        foreach ($list as &$val) {
            //是否领取过
            $receive = db('invite_receive_log')
                ->where(['uid' => $uid, 'other_id' => $val['id']])
                ->find();
            $val['is_receive'] = 0;//是否领取过
            //$val['is_can'] = 0;//是否可以领取
            if ($receive) {
                $val['is_receive'] = 1;
            }
            if ($val['num'] - $num < 0) {
                $val['num'] = 0;
            } else {
                $val['num'] = $val['num'] - $num;
            }
            $val['coin'] = round($val['coin'] / $exchange, 2);

        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //领取红包奖励
    public function request_invite_redbag()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = input('param.id');//红包ID
        $uid = input('param.uid');
        $token = input('param.token');
        check_login_token($uid, $token);
        $invite_redbag = db('invite_redbag')->find($id);
        $receive = db('invite_receive_log')
            ->where(['uid' => $uid, 'other_id' => $id])
            ->find();
        if ($receive) {
            $result = array('code' => 0, 'msg' => lang('have_received_red_envelope'));
            return_json_encode($result);
        }
        $config = load_cache('config');
        //是否达到领取条件
        if ($invite_redbag['type'] == 1) {
            $coin = intval($config['accumulated_income_tycoon_awards']); // accumulated_income_tycoon_awards=500
            $day = intval($config['registration_period_tycoon_rewards']); // registration_period_tycoon_rewards = 7
            $where = ['i.user_id' => $uid, 'type' => 2];
        } else {
            $coin = intval($config['accumulated_earnings_goddess_award']); // accumulated_earnings_goddess_award =1000
            $day = intval($config['registration_period_goddess_rewards']); // registration_period_goddess_rewards = 10
            $where = ['i.user_id' => $uid, 'type' => 3];
        }
        $day_time = NOW_TIME - $day * 60 * 60 * 24;
        $profit_record = db('invite_profit_record')->alias("i")
            ->join("user u", "u.id = i.invite_user_id")
            ->where($where)
            ->where('u.create_time >=' . $day_time)
            ->group('i.invite_user_id')
            ->field('sum(i.total_coin) as coinall')
            ->select();

        $num = 0;
        foreach ($profit_record as $v) {
            if ($v['coinall'] >= $coin) {
                $num++;
            }
        }
        if ($invite_redbag['num'] > $num) {
            $result['code'] = 0;
            $result['msg'] = lang('Not_meeting_conditions_for_receiving');
            return_json_encode($result);
        }
        // 启动事务
        db()->startTrans();
        try {
            $data = [
                'uid' => $uid,
                'other_id' => $id,
                'type' => 1,
                'income' => $invite_redbag['coin'],
                'addtime' => NOW_TIME,
            ];
            db('invite_receive_log')->insertGetId($data);
            //添加币
            db('user')->where(['id' => $uid])->inc('coin', $invite_redbag['coin'])->update();
            save_coin_log($uid, $invite_redbag['coin'], 1, 21);
            $result['msg'] = lang('Reward_received_successfully');
            // 提交事务
            db()->commit();
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e->getMessage();
        }
        return_json_encode($result);

    }

    //领取奖励
    public function request_invite_record()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        check_login_token($uid, $token);
        $config = load_cache('config');
        $time_num = $config['invite_income_expired_time'];
        if ($time_num > 0) {
            $time = strtotime(date('Y-m-d', NOW_TIME));
            $start_day = $time - (86400 * $time_num);
            $record_where = 'create_time > ' . $start_day . ' and create_time < ' . NOW_TIME;
        } else {
            $record_where = '';
        }
        $list = db('invite_profit_record')
            ->where(['user_id' => $uid, 'status' => 0])
            ->where($record_where)
            ->select();
        if ($list) {
            $sum = 0;
            foreach ($list as $val) {
                $sum += $val['income'];
                //修改状态
                db('invite_profit_record')->where(['id' => $val['id']])->update(['status' => 1]);
            }
            // 启动事务
            db()->startTrans();
            try {
                $data = [
                    'uid' => $uid,
                    'other_id' => 1,
                    'type' => 1,
                    'income' => $sum,
                    'addtime' => NOW_TIME,
                ];
                db('invite_receive_log')->insertGetId($data);
                //添加友币
                db('user')->where(['id' => $uid])->inc('coin', $sum)->update();
                save_coin_log($uid, $sum, 1, 21);
                $result['msg'] = lang('Reward_received_successfully');
                // 提交事务
                db()->commit();
            } catch (\Exception $e) {
                $result['code'] = 0;
                $result['msg'] = $e->getMessage();
            }
            return_json_encode($result);
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('There_are_no_rewards_to_claim');
            return_json_encode($result);
        }
    }

    public function request_my_invite()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        //check_login_token($uid, $token);

        /*$list = db('invite_profit_record')
            ->alias('i')
            ->join('user u','u.id=i.invite_user_id')
            ->where(['i.status'=>1,'i.user_id'=>$uid])
            ->group('i.user_id')
            ->field('i.id,sum(i.income) as total_income,u.user_nickname,u.avatar')
            //->limit(5)
            ->select();*/

        $list = db('invite_record')
            ->alias('r')
            ->join('user u', 'r.invite_user_id = u.id')
            ->where(['r.user_id' => $uid])
            ->field('r.id,u.user_nickname,u.avatar,r.user_id,r.invite_user_id')
            ->select();
        foreach ($list as &$v) {

            $v['total_income'] = db('invite_profit_record')->where('status = 1 and user_id = ' . $v['user_id'] . ' and invite_user_id = ' . $v['invite_user_id'])->sum('income');
        }

        $result['data'] = $list;
        return_json_encode($result);
    }

    public function get_user_level()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['income_talker_total', 'income_player_total']);
        $player_level = get_player_level($uid);
        $get_talker_level = get_talker_level($uid);

        $player_type = 0;
        //陪玩师
        if ($user_info['is_player'] == 1) {
            $player_type = 1;
            $up = $player_level['level_up'];
            $level = Db::name('player_level')
                ->where('level_up > ' . $up)
                ->order('level_up')
                ->find();
            if ($level) {
                $player_level['top_up'] = $level['level_up'] - $user_info['income_player_total'];
                $player_level['top_name'] = $level['name'];
                $income = $user_info['income_player_total'];
                $player_level['top_progress'] = ($income / $level['level_up']) * 100;
                if ($player_level['top_progress'] == 0) {
                    $player_level['top_progress'] = 1;
                }
            } else {
                $player_level['top_up'] = 999999;
                $player_level['top_name'] = 9999;
                $player_level['top_progress'] = 1;
            }

        } else {
            $player_level['top_up'] = 0;
            $player_level['top_name'] = 0;
            $player_level['top_progress'] = 1;
        }
        $talker_type = 0;
        // 主播
        if ($user_info['is_talker'] == 1) {
            $talker_type = 1;
            $up = $get_talker_level['level_up'];
            $level = Db::name('talker_level')
                ->where('level_up > ' . $up)
                ->order('level_up')
                ->find();
            if ($level) {
                $get_talker_level['top_up'] = $level['level_up'] - $user_info['income_player_total'];
                $income = $user_info['income_talker_total'];
                $get_talker_level['top_progress'] = ($income / $level['level_up']) * 100;
                if ($get_talker_level['top_progress'] == 0) {
                    $get_talker_level['top_progress'] = 1;
                }
                $get_talker_level['top_name'] = $level['name'];
            } else {
                $get_talker_level['top_up'] = 999999;
                //$income = $user_info['income_talker_total']-$up;
                $get_talker_level['top_progress'] = 1;
                $get_talker_level['top_name'] = 9999;
            }

        } else {
            $get_talker_level['top_up'] = 0;
            $get_talker_level['top_name'] = 0;
            $get_talker_level['top_progress'] = 1;
        }
        // 财富
        $grade_type = 1;
        $grade_level = get_grade_level($uid);
        $get_grade_level['level_name'] = $grade_level['level_name'];
        $get_grade_level['top_up'] = $grade_level['spread'];
        $get_grade_level['top_name'] = $grade_level['down_name'];
        $get_grade_level['top_progress'] = $grade_level['progress'];

        // 收益
        $grade_income_type = 0;
        $grade_income_level = get_grade_income_level($uid);
        $get_grade_income_level['level_name'] = $grade_income_level['level_name'];
        $get_grade_income_level['top_up'] = $grade_income_level['spread'];
        $get_grade_income_level['top_name'] = $grade_income_level['down_name'];
        $get_grade_income_level['top_progress'] = $grade_income_level['progress'];

        $result['data']['user_info'] = $user_info;
        $result['data']['player_level'] = $player_level;
        $result['data']['talker_level'] = $get_talker_level;
        $result['data']['grade_level'] = $get_grade_level;
        $result['data']['income_level'] = $get_grade_income_level;
        $result['data']['type'] = 1;
        $result['data']['level_type'] = array(
            'grade_type' => $grade_type,
            'grade_income_type' => $grade_income_type,
            'talker_type' => $talker_type,
            'player_type' => $player_type
        );

        return_json_encode($result);
    }

    // 等级财富和魅力
    public function level_information()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        check_login_token($uid, $token);


        return_json_encode($result);
    }

    // 财富等级
    public function wealth_level()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token);
        $user_info['level'] = get_level($uid);
        $where = "user_id=" . $uid . " and type != 9";   //9砸蛋中奖的礼物进入背包
        //获取消费记录
        $total = db('user_consume_log')->where($where)->sum("coin");
        $level_up = db('level')->where("level_up >" . $total . " and type=1")->order("level_up asc")->find();

        if ($level_up) {
            $user_level['top_up'] = $level_up['level_up'] - $total;
            $user_level['top_name'] = $level_up['levelname'] ? $level_up['levelname'] : $level_up['level_name'];
            $user_level['top_progress'] = ($total / $level_up['level_up']) * 100;
            if ($user_level['top_progress'] == 0) {
                $user_level['top_progress'] = 1;
            }
        } else {
            $user_level['top_up'] = 999999;
            $user_level['top_name'] = 9999;
            $user_level['top_progress'] = 1;
        }
        $result['data']['user_info'] = $user_info;
        $result['data']['level'] = $user_level;
        $result['data']['total'] = $total;

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
        //   var_dump(db()->getlastsql());exit;
        if ($res) {
            $result['code'] = 1;
            $result['msg'] = lang('Thank_you_for_your_comments');
        }
        return_json_encode($result);
    }

    //工会信息
    public function request_guild_info()
    {
        $result = array('code' => 1, 'msg' => lang('Submitted_successfully'));
        $uid = input('param.uid');
        $token = input('param.token');
        $user_info = check_login_token($uid, $token, ['is_named_user']);

        $guild_join = db('guild_join')
            ->where('user_id = ' . $uid . ' and status = 1')
            ->find();
        $guild = [];
        if ($guild_join) {
            $guild = db('guild')->find($guild_join['guild_id']);
            //工会人数
            $guild['user_count'] = db('guild_join')
                ->where('guild_id = ' . $guild_join['guild_id'] . ' and status = 1')
                ->count();
            $guild['addtime'] = date('Y-m-d H:i:s', $guild_join['create_time']);

            $guild['quit_guild'] = 0;
            $guild_join_quit = db('guild_join_quit')->where("guild_join_id=" . $guild_join['id'] . " and status=0")->find();
            if ($guild_join_quit) {
                // 已申请退出中
                $guild['quit_guild'] = 1;
            }

        }
        $result['data'] = $guild;
        return_json_encode($result);
    }

    // 申请退出公会
    public function quit_guild_join()
    {
        $result = array('code' => 0, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $is_force = intval(input('param.is_force')); // 是否强制退会0否1 是

        check_login_token($uid, $token);
        //查询是否加入了公会
        $join_record = db('guild_join')->where('user_id=' . $uid . ' and (status =1 or status = 0)')->find();
        if (!$join_record) {
            $result['msg'] = lang('No_guild_membership');
            return_json_encode($result);
        }
        if ($join_record['status'] == 0) {
            // 删除公会列表
            db('guild_join')->where('id=' . $join_record['id'])->delete();
            $result['code'] = 1;
            $result['msg'] = '退会成功';
        } else {
            // 申请退出
            $guild_join_quit = db('guild_join_quit')->where('user_id=' . $uid . ' and guild_join_id=' . $join_record['id'])->find();
            if ($guild_join_quit) {
                if ($guild_join_quit['status'] == 0) {
                    $result['msg'] = lang('Applying_to_quit_guild');
                    return_json_encode($result);
                } else if ($guild_join_quit['status'] == 1) {
                    // 已同意的 --- 删除公会列表
                    db('guild_join')->where('id=' . $join_record['id'])->delete();
                    // 清除公会直播间
                    db('voice')->where('user_id=' . $uid . " and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid' => '']);
                    db('user')->where('id=' . $uid)->update(['guild_id' => '']);
                } else {
                    $result['msg'] = lang('Failed_to_apply_to_quit_guild');
                    return_json_encode($result);
                }
            }
            $explain = '';
            if ($is_force == 1) {
                // 删除公会列表 -- 强制退会
                db('guild_join')->where('id=' . $join_record['id'])->delete();
                // 清除公会直播间
                db('voice')->where('user_id=' . $uid . " and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid' => '']);
                db('user')->where('id=' . $uid)->update(['guild_id' => '']);
                $result['msg'] = lang('Successfully_quit_meeting');
                $explain = lang('Forced_withdrawal_from_guild');
            } else {
                $result['msg'] = lang('Membership_withdrawal_application_succeeded');
            }
            $status = $is_force == 1 ? 3 : 0;
            $guild_quit = array(
                'user_id' => $uid,
                'guild_id' => $join_record['guild_id'],
                'guild_join_id' => $join_record['id'],
                'guild_join_status' => $join_record['status'],
                'guild_join_time' => $join_record['create_time'],
                'status' => $status,
                'create_time' => NOW_TIME,
                'explain' => $explain
            );
            if ($is_force == 1) {
                $guild_quit['end_time'] = NOW_TIME;
            }
            db('guild_join_quit')->insert($guild_quit);
            $result['code'] = 1;
        }
        return_json_encode($result);
    }

    /*
     * 收礼物记录*/
    public function get_gift_log()
    {
        $result = array('code' => 1, 'msg' => lang('Submitted_successfully'));
        $uid = input('param.uid');
        $token = input('param.token');
        $page = input('param.page');
        $type = input('param.type');
        //$user_info = check_login_token($uid, $token,['is_named_user']);
        $where = 'l.to_user_id = ' . $uid . ' and gift_type = ' . $type;
        $list = db('user_gift_log')
            ->alias('l')
            ->join('gift g', 'g.id=l.gift_id')
            ->join('user u', 'u.id=l.user_id')
            ->field('l.*,g.img,u.user_nickname')
            ->order('create_time desc')
            ->where($where)
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            $v['addtime'] = date('Y-m-d H:i:s', $v['create_time']);
            //$v['profit'] = intval($v['profit']);
        }
        $result['data'] = $list;
        return_json_encode($result);
    }
}
