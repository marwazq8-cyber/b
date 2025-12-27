<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\helper\Time;

/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/2/27
 * Time: 13:40
 */
class GuildManageController extends AdminBaseController
{

    public function index()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('guild_uid') || $this->request->param('uid') || $this->request->param('name')) {
            $data['guild_uid'] = $this->request->param('guild_uid') ? $this->request->param('guild_uid') : '';
            $data['name'] = $this->request->param('name') ? $this->request->param('name') : '';
            $data['uid'] = $this->request->param('uid') ? $this->request->param('uid') : '';

            session("GuildManageIndex", $data);
        } else if (empty($p)) {
            session("GuildManageIndex", null);
        }

        $guild_uid = session("GuildManageIndex.guild_uid");        //公会长id
        $name = session("GuildManageIndex.name");     //公会昵称
        $uid = session("GuildManageIndex.uid");     //用户id

        $where = "g.id >0";
        $where .= $guild_uid ? " and g.user_id=" . $guild_uid : '';
        $where .= $name ? ' and g.name like "%' . $name . '%"' : '';
        $where .= $uid ? " and j.user_id=" . $uid : '';

        $list = db('guild')->alias('g')
            ->join("user u", "g.user_id = u.id", 'left')
            ->join("guild_join j", "j.guild_id=g.id and j.status< 2", "left")
            ->where($where)
            ->group("g.id")
            ->order('g.create_time desc')
            ->field("g.*,u.user_nickname")
            ->paginate(20, false);
        $lists = $list->toArray();
        foreach ($lists['data'] as &$v) {
            $v['number'] = db("guild_join")->where(['guild_id' => $v['id'], 'status' => 1])->count();
        }
        $this->assign('list', $lists['data']);
        $union = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/guild';
        $this->assign('data', session("GuildManageIndex"));
        $this->assign('union', $union);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    //删除公会
    public function del()
    {
        $id = input('param.id');
        $list = db('guild')->where("id=$id")->delete();
        if ($list) {
            db('guild_join')->where('guild_id=' . $id)->delete();
            db('user')->where('guild_id=' . $id)->update(['guild_id' => 0]);
        }
        echo $list ? 1 : 0;
    }

    //增加工会列表
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $list = db('guild')->where("id=$id")->find();
        } else {
            $list['status'] = 1;
            $list['type'] = 1;
            $list['logo'] = '';
            $list['rules'] = '';
            $list['user_id'] = '';
        }
        $rules = explode(",", $list['rules']);
        $guild = db('guild_rule')->select()->toArray();
        foreach ($guild as &$v) {
            $v['type'] = in_array($v['id'], $rules) ? 1 : 0;
        }

        $this->assign('rule', $guild);
        $this->assign('list', $list);
        return $this->fetch();
    }

    //增加工会
    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'] ? $param['id'] : 0;
        $data = $param['post'];
        $rule_ar = isset($param['rule']) ? $param['rule'] : '';
        $keys = "guild_manage_" . intval($data['user_id']);
        $user_keys = redis_get($keys);
        if ($user_keys) {
            $this->error(lang('Operation_is_too_fast'));
        }
        redis_set($keys, 1, 3);

        if ($param['psd']) {
            $data['psd'] = cmf_password($param['psd']);
        }
        //dump($param);
        //dump($data['psd']);die();
        $rule = '';
        if ($rule_ar) {
            $str = '';
            foreach ($rule_ar as $v) {
                $str = $v . ",";
            }
            $rule = substr($str, 0, strlen($str) - 1);
        }

        $data['rules'] = $rule;

        $data['create_time'] = time();
        $user_one = db('user')->where('id=' . intval($data['user_id']))->find();
        if(!$user_one){
            $this->error(lang('请输入用户ID'));
        }
        if ($data['user_id']) {
            if ($id) {
                $guild = db("guild")->where("id=$id")->find();
                if ($guild['user_id'] != $data['user_id']) {
                    $guild = db("guild")->where("user_id=" . intval($data['user_id']))->find();
                    if ($guild) {
                        $this->error(lang('host_ID_already_exists'));
                    }
                    $guild_join = db("guild_join")->where("user_id=" . $data['user_id'] . " and (status =1 or status = 2)")->find();
                    if ($guild_join) {
                        $this->error(lang('You_have_joined_guild'));
                    }
                }
            } else {
                $guild = db("guild")->where("user_id=" . intval($data['user_id']))->find();
                if ($guild) {
                    $this->error(lang('host_ID_already_exists'));
                }
                $guild_join = db("guild_join")->where("user_id=" . $data['user_id'] . " and (status =1 or status = 2)")->find();
                if ($guild_join) {
                    $this->error(lang('You_have_joined_guild'));
                }
            }
        }
        if ($id) {
            $result = db("guild")->where("id=$id")->update($data);
        } else {
            if (empty($param['psd']) || strlen($param['psd']) < 6) {
                $this->error(lang('Enter_password_6_digit_password'));
            }
            $result = db("guild")->insertGetId($data);
            $id = $result;
        }
        if ($result) {
            if ($data['user_id']) {
                $guild_id = $data['status'] == 1 ? $id : 0;
                db('user')->where('id=' . $data['user_id'])->update(['guild_id' => $guild_id]);
            }
            $guild_join = db('guild_join')->where('guild_id=' . $id . ' and user_id=' . $data['user_id'])->find();
            if (!$guild_join) {
                $add = array(
                    'user_id'     => $data['user_id'],
                    'guild_id'    => $id,
                    'status'      => 1,
                    'create_time' => NOW_TIME,
                    'type'        => 2
                );
                db('guild_join')->insert($add);
            }
            $this->success(lang('EDIT_SUCCESS'), url('guild_manage/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //审核
    public function auditing()
    {
        $id = input('param.id');
        $status = input('param.status');

        db('guild')->where('id=' . $id)->setField('status', $status);
        $guild = db('guild')->where('id=' . $id)->find();
        if ($guild && $guild['status'] == 1) {
            $guild_join = db('guild_join')->where('guild_id=' . $id . ' and user_id=' . $guild['user_id'])->find();
            if (!$guild_join) {
                $add = array(
                    'user_id'     => $guild['user_id'],
                    'guild_id'    => $id,
                    'status'      => 1,
                    'create_time' => NOW_TIME,
                    'type'        => 2
                );
                db('guild_join')->insert($add);
                db('user')->where('id=' . $guild['user_id'])->update(['guild_id' => $id]);
            }
        }
        $this->success(lang('Operation_successful'));
    }

    //查看公会信息
    public function select_guild_info()
    {

        $id = input('param.id');
        $guild = db('guild')->where('status=1 and id=' . $id)->find();
        $guild_join_list = db('guild_join')->where('status=1 and guild_id=' . $id)->select();

        $guild_info = $guild;
        //总人数
        $guild_info['num'] = count($guild_join_list);

        //今日总收益
        $day_time = Time::today();
        $guild_info['day_income'] = db('guild_log')->where('guild_id=' . $id . " and addtime >" . $day_time[0])->sum("guild_earnings");
        //未审核人数
        $guild_info['auditing_num'] = db('guild_join')->where('id=' . $id . ' and status=0')->count();
        //总人数
        $guild_info['num'] = db('guild_join')->where('guild_id=' . $id . ' and status=1')->count();

        $this->assign('data', $guild_info);

        return $this->fetch();
    }

    //公会用户列表
    public function join_list()
    {
        $id = input('param.id');

        $data_list = db('guild_join')->alias('g')->join('user u', 'g.user_id=u.id')->where('g.guild_id=' . $id)
            ->field('g.*,u.user_nickname,u.avatar')->order('g.create_time desc')->paginate(20, false);
        $list = [];
        foreach ($data_list as $key => $val) {
            //礼物价值
            $val['gift_coin'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and type=1 and guild_id=" . $id)->sum("host_earnings");

            //总收益
            $val['guild_earnings'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and guild_id=" . $id)->sum("guild_earnings");

            $list[] = $val;
        }
        $this->assign('list', $list);
        $this->assign('page', $data_list->render());

        return $this->fetch();
    }


    //接听率
    public function answer_rate($id)
    {
        $answer_yes = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->where('status', '=', 1)
            ->whereOr('call_be_user_id', '=', $id)
            ->count();
        $answer = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->whereOr('call_be_user_id', '=', $id)
            ->count();
        if ($answer_yes == 0 || $answer == 0) {
            return 0;
        } else {
            return round($answer_yes / $answer, 2) * 100;
        }
    }

    //公会提现
    public function withdrawal()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('id') || $this->request->param('status') || $this->request->param('start_time') || $this->request->param('end_time')) {
            $data['id'] = $this->request->param('id') ? $this->request->param('id') : '';
            $data['status'] = $this->request->param('status') || $this->request->param('status') == '0' ? $this->request->param('status') : '-1';
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') : '';

            session("guildmanage_withdrawal", $data);
        } else if (empty($p)) {
            $data['status'] = '-1';
            session("guildmanage_withdrawal", $data);
        }

        $id = session("guildmanage_withdrawal.id");
        $status = session("guildmanage_withdrawal.status");
        $start_time = session("guildmanage_withdrawal.start_time") ? strtotime(session("guildmanage_withdrawal.start_time") . " 00:00:00") : '';
        $end_time = session("guildmanage_withdrawal.end_time") ? strtotime(session("guildmanage_withdrawal.end_time") . " 23:59:59") : '';

        $where = "l.id >0";
        $where .= $id ? " and g.id=" . $id : '';
        $where .= $status != '-1' ? " and l.status=" . $status : '';
        $where .= $start_time ? " and l.create_time >=" . $start_time : '';
        $where .= $end_time ? " and l.create_time <=" . $end_time : '';
        $filed = "g.id as gid,l.id,g.name,l.income as coin,l.money as money,l.type as account_type,l.gathering_name as account_name,l.gathering_number as cash_account,l.status,l.create_time as addtime";
        $data_list = db('user_cash_record')->alias('l')
            ->join('guild g', 'g.user_id=l.user_id')
            ->where($where)
            ->field($filed)
            ->order('l.create_time desc')
            ->paginate(20, false);

        $this->assign('data', session("guildmanage_withdrawal"));
        $this->assign('list', $data_list);
        $this->assign('page', $data_list->render());
        return $this->fetch();
    }

    //操作公会提现
    public function upd_withdrawal()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status') == 1 ? 1 : 2;
        $guild = db("user_cash_record")->where("id=$id")->find();
        if (!$guild) {
            $this->error(lang('Parameter_transfer_error'), url('guild_manage/withdrawal'));
        }
        $result = db("user_cash_record")->where("id=$id")->update(array("status" => $status));
        if (!$result) {
            $this->error(lang('operation_failed'), url('guild_manage/withdrawal'));
        }
        if ($status == 2) {
            db("guild")->where("user_id=" . $guild['user_id'])->setInc("earnings", $guild['coin']);
        }
        $this->success(lang('Operation_successful'), url('guild_manage/withdrawal'));
    }

    //公会收益记录
    public function earnings_log()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('id') || $this->request->param('classification') || $this->request->param('uid') || $this->request->param('status') >= '0' || $this->request->param('hid') || $this->request->param('start_time') || $this->request->param('end_time')) {
            $data['id'] = $this->request->param('id') ? $this->request->param('id') : '';
            $data['hid'] = $this->request->param('hid') ? $this->request->param('hid') : '';
            $data['uid'] = $this->request->param('uid') ? $this->request->param('uid') : '';
            $data['classification'] = $this->request->param('classification') ? $this->request->param('classification') : '';
            $data['status'] = $this->request->param('status') >= 0 ? $this->request->param('status') : '';
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') : '';

            session("guildmanage_earnings_log", $data);
        } else if (empty($p)) {
            session("guildmanage_earnings_log", null);
        }

        $id = session("guildmanage_earnings_log.id");        //公会id
        $hid = session("guildmanage_earnings_log.hid");     //主播id
        $uid = session("guildmanage_earnings_log.uid");     //用户id
        $status = session("guildmanage_earnings_log.status");
        $classification = session("guildmanage_earnings_log.classification");
        $start_time = session("guildmanage_earnings_log.start_time") ? strtotime(session("guildmanage_earnings_log.start_time") . " 00:00:00") : '';
        $end_time = session("guildmanage_earnings_log.end_time") ? strtotime(session("guildmanage_earnings_log.end_time") . " 23:59:59") : '';

        $where = "c.guild_uid >0";
        $where .= $id ? " and c.guild_uid=" . $id : '';
        $where .= $hid ? " and c.to_user_id=" . $hid : '';
        $where .= $uid ? " and c.user_id=" . $uid : '';
        $where .= $start_time ? " and c.create_time >=" . $start_time : '';
        $where .= $end_time ? " and c.create_time <=" . $end_time : '';
        $where .= $status >= '0' ? " and c.status=" . $status : '';
        $where .= $classification ? " and c.classification=" . $classification : '';

        $data_list = db('user_consume_log')->alias('c')
            ->where($where)
            ->field('c.guild_uid,c.user_id as uid,c.coin as ucoin,c.content,c.to_user_id,c.profit,c.guild_earnings,c.create_time,c.status,c.guild_commission')
            ->order('c.create_time desc')
            ->paginate(20, false);

        // 使用 map 方法给每个 item 的 name 赋值
        $data_list = $data_list->each(function ($item) {
            $item['user_info'] = db('user')->where('id', $item['uid'])->field('user_nickname,avatar')->find();
            $item['to_user_info'] = db('user')->where('id', $item['to_user_id'])->field('user_nickname,avatar')->find();
            // 查询公会信息
            $item['guild_info'] = db('guild')->where('user_id', $item['guild_uid'])->field('id,name')->find();
            $item['guild_id'] = $item['guild_info']['id'];
            $item['gname'] = $item['guild_info']['name'];

            $item['uname'] = $item['user_info']['user_nickname'];
            $item['hname'] = $item['to_user_info']['user_nickname'];
            return $item;
        });

        if ($id || $hid || $uid || $start_time || $end_time) {
            $data_list_count = db('user_consume_log')->alias('c')
                ->join('guild g', 'g.user_id=c.guild_uid')
                ->where($where)
                ->field("sum(c.guild_earnings) as guild_earnings,sum(c.coin) as coin,sum(c.profit) as profit")
                ->find();
            $this->assign('is_show_total', 1);
        } else {
            $data_list_count = [
                'guild_earnings' => 0,
                'coin'           => 0,
                'profit'         => 0
            ];
            $this->assign('is_show_total', 0);
        }

        $this->assign('data', session("guildmanage_earnings_log"));
        $this->assign('list', $data_list);
        $this->assign('count', $data_list_count);
        $this->assign('page', $data_list->render());
        return $this->fetch();
    }

    //通过和拒绝主播加入公会
    public function auditing_join()
    {
        $id = $this->request->param('id');
        $status = intval($this->request->param('status')) == 1 ? 1 : 2;
        $list = db('guild_join')->where("id=" . $id)->update(array('status' => $status));
        if ($list) {
            if ($status == 1) {
                $info = db('guild_join')->where("id=" . $id)->find();
                $guild = db("guild")->find($info['guild_id']);
                // 添加公会直播间
                db('voice')->where('user_id=' . $info['user_id'] . " and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid' => $guild['user_id']]);
                db('user')->where('id=' . $info['user_id'])->update(['guild_id' => $info['guild_id']]);
            }
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    //工会主播列表
    public function user_list()
    {
        $id = input('id');
        $list = db("guild_join")
            ->alias('g')
            ->join('user u', 'u.id=g.user_id')
            ->where(['g.guild_id' => $id])
            ->field('g.*,u.user_nickname,u.is_player,u.is_talker,u.income_total')
            ->order("g.create_time DESC")
            ->paginate(20, false, ['query' => request()->param()]);
        $list_all = [];
        foreach ($list as $val) {
            $id = $val['guild_id'];
            //礼物收益
            $val['gift_coin'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and type=1 and guild_id=" . $id)->sum("guild_earnings");
            //通话
            $val['audio_call_coin'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and type=4 and guild_id=" . $id)->sum("guild_earnings");
            $val['video_call_coin'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and type=5 and guild_id=" . $id)->sum("guild_earnings");
            $val['call_coin'] = $val['audio_call_coin'] + $val['video_call_coin'];
            //私信
            $val['pr_coin'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and type=3 and guild_id=" . $id)->sum("guild_earnings");
            //陪玩
            $val['play_coin'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and type=2 and guild_id=" . $id)->sum("guild_earnings");
            //总收益
            $val['guild_earnings'] = db('guild_log')->where('user_id=' . $val['user_id'] . " and guild_id=" . $id)->sum("guild_earnings");
            $list_all[] = $val;
        }
        $this->assign('list', $list_all);
        $this->assign('page', $list->render());
        $this->assign('id', $id);
        return $this->fetch();
    }

    //审核
    public function status_user()
    {
        $id = input('id');
        $type = input('type');
        $info = db("guild_join")->find($id);
        if (!$info) {
            echo 0;
            exit;
        }
        if ($type == 1) {
            $guild = db("guild")->find($info['guild_id']);
            $res = db("guild_join")->where(['id' => $id])->update(['status' => 1]);
            // 添加公会直播间
            db('voice')->where('user_id=' . $info['user_id'] . " and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid' => $guild['user_id']]);
            db('user')->where('id=' . $info['user_id'])->update(['guild_id' => $info['guild_id']]);
        } else {
            $res = db("guild_join")->where(['id' => $id])->update(['status' => 2]);
        }
        echo $res ? 1 : 0;
    }

    //添加主播
    public function add_user_list()
    {
        $guild_id = input('guild_id');
        $list = [
            'status' => 1
        ];
        $this->assign('list', $list);
        $this->assign('guild_id', $guild_id);
        return $this->fetch();
    }

    public function addUserPost()
    {
        $param = $this->request->param();
        $guild_id = $param['guild_id'];
        $data = $param['post'];
        $user_id = $data['user_id'];
        //用户是否是主播
        $user_info = db('user')->find($user_id);
        if (!$user_info) {
            $this->error(lang('user_does_not_exist'));
            exit;
        }

        $guild = db("guild")->where(['id' => $guild_id])->find();

        $guild_info = db("guild_join")
            ->where(['user_id' => $user_id, 'guild_id' => $guild_id])
            ->find();
        if ($guild_info) {
            $this->error(lang("用户已加入该工会！"));
            exit;
        }
        $data['guild_id'] = $guild_id;
        $data['create_time'] = time();
        $result = db("guild_join")->insert($data);

        if ($result) {
            if ($data['status'] == 1) {
                // 添加公会直播间
                db('voice')->where('user_id=' . $user_id . " and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid' => $guild['user_id']]);
                db('user')->where('id=' . $user_id)->update(['guild_id' => $guild_id]);
            }
            $this->success(lang('EDIT_SUCCESS'), url('guild_manage/user_list', array('id' => $guild_id)));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    public function user_del()
    {
        $id = input('param.id');
        $guild_join = db('guild_join')->where("id=$id")->find();

        $list = db('guild_join')->where("id=$id")->delete();
        if ($list && $guild_join) {
            // 清除公会直播间
            db('voice')->where('user_id=' . $guild_join['user_id'] . " and (live_in = 1 or live_in =3) and status = 1")->update(['guild_uid' => '']);
            db('user')->where('id=' . $guild_join['user_id'])->update(['guild_id' => 0]);
            $guild_join_quit = db('guild_join_quit')->where('user_id=' . $guild_join['user_id'] . " and status=0")->find();
            if ($guild_join_quit) {
                $update_data = array(
                    'status'   => 1,
                    'explain'  => '',
                    'end_time' => NOW_TIME
                );
                db('guild_join_quit')->where('id=' . $guild_join['user_id'])->update($update_data);
            }
        }
        echo $list ? 1 : 0;
    }

    /*导出公会提现记录*/
    public function export_withdrawal_record()
    {

        $title = lang('Withdrawal_records_Association');
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('id') || $this->request->param('status') || $this->request->param('start_time') || $this->request->param('end_time')) {
            $data['id'] = $this->request->param('id') ? $this->request->param('id') : '';
            $data['status'] = $this->request->param('status') || $this->request->param('status') == '0' ? $this->request->param('status') : '-1';
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') : '';

            session("guildmanage_withdrawal", $data);
        } else if (empty($p)) {
            $data['status'] = '-1';
            session("guildmanage_withdrawal", $data);
        }

        $id = session("guildmanage_withdrawal.id");
        $status = session("guildmanage_withdrawal.status");
        $start_time = session("guildmanage_withdrawal.start_time") ? strtotime(session("guildmanage_withdrawal.start_time") . " 00:00:00") : '';
        $end_time = session("guildmanage_withdrawal.end_time") ? strtotime(session("guildmanage_withdrawal.end_time") . " 23:59:59") : '';

        $where = "l.id >0";
        $where .= $id ? " and g.id=" . $id : '';
        $where .= $status != '-1' ? " and l.status=" . $status : '';
        $where .= $start_time ? " and l.create_time >=" . $start_time : '';
        $where .= $end_time ? " and l.create_time <=" . $end_time : '';
        $filed = "g.id as gid,l.id,g.name,l.income as coin,l.money as money,l.type as account_type,l.gathering_name as account_name,l.gathering_number as cash_account,l.status,l.create_time as addtime";
        $data_list = db('user_cash_record')->alias('l')
            ->join('guild g', 'g.user_id=l.user_id')
            ->where($where)
            ->field($filed)
            ->order('l.create_time desc')
            ->paginate(20, false);

        $lists = $data_list->toArray();
        $dataResult = array();
        if ($lists['data'] != null) {
            $status = array(0 => lang('CHECK_LOADING'), 1 => lang('SUCCESS'), 2 => lang('FAILED'));
            foreach ($lists['data'] as $k => $v) {

                $dataResult[$k]['gid'] = $v['gid'] ? $v['gid'] : lang('No_data');
                $dataResult[$k]['name'] = $v['name'] ? $v['name'] : lang('No_data');
                $dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : lang('No_information');
                $dataResult[$k]['money'] = $v['money'] ? $v['money'] : lang('No_information');
                $dataResult[$k]['account_type'] = $v['account_type'] == 1 ? lang('ALIPAY') : lang('WECHAT');
                $dataResult[$k]['account_name'] = $v['account_name'] ? $v['account_name'] : lang('No_information');
                $dataResult[$k]['cash_account'] = $v['cash_account'] ? $v['cash_account'] : lang('No_information');
                $dataResult[$k]['status'] = $status[$v['status']] ? $status[$v['status']] : $status[0];
                $dataResult[$k]['addtime'] = $v['addtime'] ? date('Y-m-d h:i', $v['addtime']) : lang('No_information');

            }

            $str = lang('ADMIN_GUILD_NAME') . "ID," . lang('ADMIN_GUILD_NAME') . "," . lang('ADMIN_WITHDRAW_NUMBER') . "," . lang('ADMIN_WITHDRAW_COIN') . "," . lang('ADMIN_ACCOUNT_TYPE') . "," . lang('ADMIN_ACCOUNT_NAME') . "," . lang('ADMIN_ACCOUNT_USER') . "," . lang('STATUS') . "," . lang('ADMIN_CHECK_TIME');

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error(lang('No_data'));
        }


    }

    /**
     * 导出收益记录
     */
    public function export_earnings_log()
    {
        $title = lang('ADMIN_GUILD_INCOME_LOG');
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('id') || $this->request->param('uid') || $this->request->param('hid') || $this->request->param('start_time') || $this->request->param('end_time')) {
            $data['id'] = $this->request->param('id') ? $this->request->param('id') : '';
            $data['hid'] = $this->request->param('hid') ? $this->request->param('hid') : '';
            $data['uid'] = $this->request->param('uid') ? $this->request->param('uid') : '';
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') : '';
            session("guildmanage_earnings_log", $data);
        } else if (empty($p)) {
            session("guildmanage_earnings_log", null);
        }

        $id = session("guildmanage_earnings_log.id");        //公会id
        $hid = session("guildmanage_earnings_log.hid");     //主播id
        $uid = session("guildmanage_earnings_log.uid");     //用户id
        $start_time = session("guildmanage_earnings_log.start_time") ? strtotime(session("guildmanage_earnings_log.start_time") . " 00:00:00") : '';
        $end_time = session("guildmanage_earnings_log.end_time") ? strtotime(session("guildmanage_earnings_log.end_time") . " 23:59:59") : '';

        $where = "l.id >0";
        $where .= $id ? " and l.guild_id=" . $id : '';
        $where .= $hid ? " and l.user_id=" . $hid : '';
        $where .= $uid ? " and u.id=" . $uid : '';
        $where .= $start_time ? " and l.addtime >=" . $start_time : '';
        $where .= $end_time ? " and l.addtime <=" . $end_time : '';

        $data_list = db('guild_log')->alias('l')
            ->join('user_consume_log c', 'c.id=l.consume_log')
            ->join('guild g', 'g.id=l.guild_id')
            ->join('user u', 'u.id=c.user_id')
            ->join('user h', 'h.id=l.user_id')
            ->where($where)
            ->field('c.user_id as uid,c.coin as ucoin,c.content,l.*,g.name as gname,u.user_nickname as uname,h.user_nickname as hname')
            ->order('l.addtime desc')
            ->paginate(20, false);

        $lists = $data_list->toArray();
        $dataResult = array();
        if ($lists['data'] != null) {
            foreach ($lists['data'] as $k => $v) {

                $dataResult[$k]['uid'] = $v['uid'] ? $v['uid'] : lang('No_data');
                $dataResult[$k]['uname'] = $v['uname'] ? $v['uname'] : lang('No_data');
                $dataResult[$k]['user_id'] = $v['user_id'] ? $v['user_id'] : lang('No_information');
                $dataResult[$k]['hname'] = $v['hname'] ? $v['hname'] : lang('No_information');
                $dataResult[$k]['guild_id'] = $v['guild_id'] ? $v['guild_id'] : lang('No_information');
                $dataResult[$k]['gname'] = $v['gname'] ? $v['gname'] : lang('No_information');
                $dataResult[$k]['ucoin'] = $v['ucoin'] ? $v['ucoin'] : lang('No_information');
                $dataResult[$k]['host_earnings'] = $v['host_earnings'] ? $v['host_earnings'] : lang('No_information');
                $dataResult[$k]['guild_earnings'] = $v['guild_earnings'] ? $v['guild_earnings'] : lang('No_information');
                $dataResult[$k]['content'] = $v['content'] ? $v['content'] : lang('No_information');
                $dataResult[$k]['addtime'] = $v['addtime'] ? date('Y-m-d h:i', $v['addtime']) : lang('No_information');

            }

            $str = lang('ADMIN_CONSUME_USER') . "ID," . lang('ADMIN_CONSUME_USER') . "," . lang('ADMIN_ANCHOR') . "ID," . lang('ADMIN_ANCHOR') . "," . lang('ADMIN_GUILD') . "ID," . lang('ADMIN_GUILD') . "," . lang('ADMIN_COMMISSION_COIN') . "," . lang('ADMIN_ANCHOR_INCOME') . "," . lang('Guild_income') . "," . lang('ADMIN_COMMISSION_INFO') . "," . lang('TIME');

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error(lang('No_data'));
        }

    }

    /**
     * 公会下房间流水
     * */
    public function room_flow()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('guild_uid') || $this->request->param('uid') || $this->request->param('status')) {
            $data['guild_uid'] = $this->request->param('guild_uid') ? $this->request->param('guild_uid') : '';
            $data['hid'] = $this->request->param('hid') ? $this->request->param('hid') : '';
            $data['uid'] = $this->request->param('uid') ? $this->request->param('uid') : '';
            $data['status'] = $this->request->param('status') ? $this->request->param('status') : '0';
            session("guildmanage_room_flow", $data);
        } else if (empty($p)) {
            $data['guild_uid'] = intval(session("guildmanage_room_flow.guild_uid"));
            $data['status'] = '0';
            session("guildmanage_room_flow", $data);
        }

        $guild_id = intval(session("guildmanage_room_flow.guild_uid"));        //公会id
        $uid = intval(session("guildmanage_room_flow.uid"));     //主播id
        $status = intval(session("guildmanage_room_flow.status"));

        $where = 'u.guild_id = ' . $guild_id;
        $where .= $uid ? " and u.uid=" . $uid : '';
        $where .= $status ? " and v.status=" . $status : '';

        $list = db('user')->alias('u')
            ->field("v.id,v.title,v.avatar as voice_avatar,v.coin_number,v.online_number,v.status,v.guild_uid,v.create_time,v.online_count,v.endtime,v.user_id,u.user_nickname,u.id as user_id")
            ->join("voice v", "u.id = v.user_id", "left")
            ->where($where)
            ->order('u.create_time desc')
            ->paginate(20, false);

//
//        $list = db('voice')->alias('v')
//            ->field("v.id,v.title,v.avatar as voice_avatar,v.coin_number,v.online_number,v.status,v.guild_uid,v.create_time,v.online_count,v.endtime,v.user_id,u.user_nickname")
//            ->join("user u","u.id = v.user_id")
//            ->where($where)
//            ->order('v.create_time desc')
//            ->paginate(20, false);

        $list_tow = $list->toArray();

        //获取本周的起止时间
        $week_start_time = strtotime(date('Y-m-d', strtotime("this week Monday", NOW_TIME)));
        //获取上周的起止时间
        $last_week_start_time = strtotime(date('Y-m-d', strtotime("last week Monday", NOW_TIME)));
        $last_week_end_time = strtotime(date('Y-m-d', strtotime("last week Sunday", NOW_TIME))) + 24 * 3600 - 1;
        //获取本月的起止时间
        $month_start_time = strtotime(date('Y-m-01'));
        //获取今日的起止时间
        $day_start_time = strtotime(date('Y-m-d'));
        //获取昨日的起止时间
        $yesterday_start_time = strtotime(date('Y-m-d 00:00:00', strtotime('yesterday')));
        $yesterday_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('yesterday')));
        //获取上月的起止时间
        $last_month_start_time = strtotime(date('Y-m-01', strtotime('last month')));
        foreach ($list_tow['data'] as &$v) {
            if ($v['id'] == null) {
                $voice_log = db('voice_log')->field("id,title,avatar as voice_avatar,coin_number,online_number,status,guild_uid,create_time,online_count,endtime")->where('user_id = ' . $v['user_id'])->find();
                if ($voice_log) {
                    $v['id'] = $voice_log['id'];
                    $v['title'] = $voice_log['title'];
                    $v['voice_avatar'] = $voice_log['voice_avatar'];
                    $v['coin_number'] = $voice_log['coin_number'];
                    $v['online_number'] = $voice_log['online_number'];
                    $v['status'] = 2;
                    $v['guild_uid'] = $voice_log['guild_uid'];
                    $v['create_time'] = $voice_log['create_time'];
                    $v['online_count'] = $voice_log['online_count'];
                    $v['endtime'] = $voice_log['endtime'];
                }
            }
            //今日流水
            $v['day_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $day_start_time)->sum('gift_coin');
            //昨日流水
            $v['yesterday_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $yesterday_start_time . ' and create_time <=' . $yesterday_end_time)->sum('gift_coin');
            //本周流水
            $v['week_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $week_start_time)->sum('gift_coin');
            //上周流水
            $v['last_week_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $last_week_start_time . ' and create_time <=' . $last_week_end_time)->sum('gift_coin');
            // 本月流水
            $v['month_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $month_start_time)->sum('gift_coin');
            // 上月流水
            $v['last_month_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $last_month_start_time . " and create_time <" . $month_start_time)->sum('gift_coin');

        }

        $this->assign('list', $list_tow['data']);
        $this->assign('data', session("guildmanage_room_flow"));
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    /**
     * 导出房间流水
     * */
    public function room_flow_export()
    {
        $title = lang('ADMIN_VOICE_VOICE_DETAILS');
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('guild_uid') || $this->request->param('uid') || $this->request->param('status')) {
            $data['guild_uid'] = $this->request->param('guild_uid') ? $this->request->param('guild_uid') : '';
            $data['hid'] = $this->request->param('hid') ? $this->request->param('hid') : '';
            $data['uid'] = $this->request->param('uid') ? $this->request->param('uid') : '';
            $data['status'] = $this->request->param('status') ? $this->request->param('status') : '0';
            session("guildmanage_room_flow", $data);
        } else if (empty($p)) {
            $data['guild_uid'] = intval(session("guildmanage_room_flow.guild_uid"));
            $data['status'] = '0';
            session("guildmanage_room_flow", $data);
        }

        $guild_id = intval(session("guildmanage_room_flow.guild_uid"));        //公会id
        $uid = intval(session("guildmanage_room_flow.uid"));     //主播id
        $status = intval(session("guildmanage_room_flow.status"));

        $where = 'u.guild_id = ' . $guild_id;
        $where .= $uid ? " and u.uid=" . $uid : '';
        $where .= $status ? " and v.status=" . $status : '';

        $list = db('user')->alias('u')
            ->field("v.id,v.title,v.avatar as voice_avatar,v.coin_number,v.online_number,v.status,v.guild_uid,v.create_time,v.online_count,v.endtime,v.user_id,u.user_nickname,u.id as user_id")
            ->join("voice v", "u.id = v.user_id", "left")
            ->where($where)
            ->order('u.create_time desc')
            ->paginate(20, false);

        $list_tow = $list->toArray();

        //获取本周的起止时间
        $week_start_time = strtotime(date('Y-m-d', strtotime("this week Monday", NOW_TIME)));
        //获取上周的起止时间
        $last_week_start_time = strtotime(date('Y-m-d', strtotime("last week Monday", NOW_TIME)));
        $last_week_end_time = strtotime(date('Y-m-d', strtotime("last week Sunday", NOW_TIME))) + 24 * 3600 - 1;
        //获取本月的起止时间
        $month_start_time = strtotime(date('Y-m-01'));
        //获取今日的起止时间
        $day_start_time = strtotime(date('Y-m-d'));
        //获取昨日的起止时间
        $yesterday_start_time = strtotime(date('Y-m-d 00:00:00', strtotime('yesterday')));
        $yesterday_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('yesterday')));
        //获取上月的起止时间
        $last_month_start_time = strtotime(date('Y-m-01', strtotime('last month')));
        foreach ($list_tow['data'] as &$v) {
            if ($v['id'] == null) {
                $voice_log = db('voice_log')->field("id,title,avatar as voice_avatar,coin_number,online_number,status,guild_uid,create_time,online_count,endtime")->where('user_id = ' . $v['user_id'])->find();
                if ($voice_log) {
                    $v['id'] = $voice_log['id'];
                    $v['title'] = $voice_log['title'];
                    $v['voice_avatar'] = $voice_log['voice_avatar'];
                    $v['coin_number'] = $voice_log['coin_number'];
                    $v['online_number'] = $voice_log['online_number'];
                    $v['status'] = 2;
                    $v['guild_uid'] = $voice_log['guild_uid'];
                    $v['create_time'] = $voice_log['create_time'];
                    $v['online_count'] = $voice_log['online_count'];
                    $v['endtime'] = $voice_log['endtime'];
                }
            }
            //今日流水
            $v['day_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $day_start_time)->sum('gift_coin');
            //昨日流水
            $v['yesterday_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $yesterday_start_time . ' and create_time <=' . $yesterday_end_time)->sum('gift_coin');
            //本周流水
            $v['week_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $week_start_time)->sum('gift_coin');
            //上周流水
            $v['last_week_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $last_week_start_time . ' and create_time <=' . $last_week_end_time)->sum('gift_coin');
            // 本月流水
            $v['month_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $month_start_time)->sum('gift_coin');
            // 上月流水
            $v['last_month_coin'] = db('user_gift_log')->where('voice_user_id = ' . $v['user_id'] . ' and create_time >= ' . $last_month_start_time . " and create_time <" . $month_start_time)->sum('gift_coin');

        }
        $dataResult = array();
        if ($list_tow['data'] != null) {
            foreach ($list_tow['data'] as $k => $ve) {

                $dataResult[$k]['id'] = $ve['id'] ? $ve['title'] . "(" . $ve['id'] . ")" : lang('No_data');
                $dataResult[$k]['user_id'] = $ve['user_id'] ? $ve['user_nickname'] . "(" . $ve['user_id'] . ")" : lang('No_data');
                $dataResult[$k]['guild_uid'] = $ve['guild_uid'] ? $ve['guild_uid'] : lang('No_information');
                $dataResult[$k]['day_coin'] = $ve['day_coin'] ? $ve['day_coin'] : 0;
                $dataResult[$k]['yesterday_coin'] = $ve['yesterday_coin'] ? $ve['yesterday_coin'] : 0;
                $dataResult[$k]['week_coin'] = $ve['week_coin'] ? $ve['week_coin'] : 0;
                $dataResult[$k]['last_week_coin'] = $ve['last_week_coin'] ? $ve['last_week_coin'] : 0;
                $dataResult[$k]['month_coin'] = $ve['month_coin'] ? $ve['month_coin'] : 0;
                $dataResult[$k]['last_month_coin'] = $ve['last_month_coin'] ? $ve['last_month_coin'] : 0;
                $dataResult[$k]['create_time'] = $ve['create_time'] ? date('Y-m-d H:i', $ve['create_time']) : lang('No_information');

            }

            $str = lang('房间') . "ID," . lang('ADMIN_ANCHOR') . "ID," . lang('公会ID') . "," . lang('今日流水') . "," . lang('昨日流水') . "," . lang('本周流水') . "," . lang('上周流水') . "," . lang('本月流水') . "," . lang('上月流水') . "," . lang('TIME');

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error(lang('No_data'));
        }
    }
}
