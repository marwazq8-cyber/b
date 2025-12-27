<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/8 0008
 * Time: 上午 10:55
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ConsumeController extends AdminBaseController
{

    //消费记录
    public function index()
    {
        $where = [];
        if (!input('request.page')) {
            session('consume_index', null);
        }
        if (input('request.uid') || input('request.touid') || input('request.agent_company') || input('request.guild_uid') || input('request.start_time') || input('request.end_time') || input('request.type') > 0) {
            session('consume_index', input('request.'));
            if (session('consume_index.type') == null) {
                session('consume_index.type', 0);
            }
        }
        if (session('consume_index.uid')) {
            $where['a.user_id'] = intval(session('consume_index.uid'));
        }
        if (session('consume_index.touid')) {
            $where['a.to_user_id'] = intval(session('consume_index.touid'));
        }
        if (session('consume_index.end_time') && session('consume_index.start_time')) {
            $where['a.create_time'] = ['between', [strtotime(session('consume_index.start_time')), strtotime(session('consume_index.end_time'))]];
        }
        if (session('consume_index.type') > 0) {
            $where['a.type'] = session('consume_index.type');
        } else {
            session('consume_index.type', -1);
        }
        if (session('consume_index.guild_uid')) {
            $where['a.guild_uid'] = intval(session('consume_index.guild_uid'));
        }
        if (session('consume_index.agent_company')) {
            $where['a.agent_company'] = intval(session('consume_index.agent_company'));
        }

        $user = db("user_consume_log")
            ->alias("a")
            ->field("a.agent_company,a.agent_staff,a.agent_id,a.user_id,a.id,a.to_user_id,a.coin,a.profit,a.content,a.create_time,a.type,a.table_id,a.coin_type,a.guild_uid,a.guild_earnings,a.guild_commission,a.guild_type,a.classification_id")
            ->where($where)
            ->order('create_time desc')
            ->paginate(20, false, ['query' => request()->param()]);
        $lists = $user->toArray();

        foreach ($lists['data'] as &$v) {
            $v['uname'] = db("user")->where("id=" . $v['user_id'])->value("user_nickname");
            $v['toname'] = db("user")->where("id=" . $v['to_user_id'])->value("user_nickname");
            $v['guild_name'] = "";
            $v['agent_company_name'] = "";
            if ($v['guild_uid']) {
                $v['guild_name'] = db("user")->where("id=" . $v['guild_uid'])->value("user_nickname");
            }
            if ($v['agent_company']) {
                $v['agent_company_name'] = db('agent')->where("id=" . intval($v['agent_company']))->value("login_name");
            }
        }

        if (isset($where['a.user_id']) || isset($where['a.to_user_id']) || isset($where['a.guild_uid']) || isset($where['a.agent_company']) || isset($where['a.create_time'])) {
            $total = Db::name("user_consume_log")->alias("a")->where($where)->sum('a.profit');
            $where['a.coin_type'] = 1;
            $coin = Db::name("user_consume_log")->alias("a")->where($where)->sum('a.coin');
            $where['a.coin_type'] = 2;
            $system_coin = Db::name("user_consume_log")->alias("a")->where($where)->sum('a.coin');
            $this->assign('is_show_total', 1);
        } else {
            // 给默认的空字符串
            $total = '';
            $coin = '';
            $system_coin = '';
            $this->assign('is_show_total', 0);
        }


        $config = load_cache('config');
        $currency_name = $config['currency_name'];
        $profit_name = $config['virtual_currency_earnings_name'];
        $system_currency_name = $config['system_currency_name'];

        $Consumer_classification = Consumer_classification();
        $Consumer_classification_id = [];
        foreach ($Consumer_classification as $vc) {
            $Consumer_classification_id[$vc['id']] = $vc['title'];
        }
        $this->assign('Consumer_classification', $Consumer_classification);
        $this->assign('type', $Consumer_classification_id);
        $this->assign('system_currency_name', $system_currency_name);
        $this->assign('profit_name', $profit_name);
        $this->assign('currency_name', $currency_name);
        $this->assign('total', $total);
        $this->assign('coin', $coin);
        $this->assign('system_coin', $system_coin);
        $this->assign('data', $lists['data']);
        $this->assign('request', session('consume_index'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }

    //查看本次通话的记录
    public function select_call()
    {

        $id = input('request.id');
        //获取拨打视频通话记录表
        $call = Db::name("user_consume_log")
            ->alias("a")->field("g.id")
            ->join("video_call_record_log v", "v.channel_id=a.table_id")
            ->join("user_gift_log g", "g.channel_id=v.channel_id")
            ->where("a.table_id=" . $id)
            ->select();

        $time = Db::name("video_call_record_log")->field("call_time")->where("channel_id=" . $id)->find();

        $where_id = '';
        foreach ($call as $v) {
            $where_id .= $v['id'] . ",";
        }
        $where_in = rtrim($where_id, ',');
        if ($where_in) {
            $where = "a.table_id in(" . $id . "," . $where_in . ")";
        } else {
            $where = "a.table_id =" . $id;
        }
        $where .= ' and a.type = 4';
        $user = db("user_consume_log")
            ->alias("a")
            ->join("user b", "b.id=a.user_id")
            ->join("user c", "c.id=a.to_user_id")
            ->field("a.*,b.user_nickname as uname,c.user_nickname as toname")
            ->where($where)
            ->order('a.create_time desc')
            ->select()
            ->toArray();
        //invite_profit_record
        $money = 0;
        $profit = 0;
        $coin = 0;
        foreach ($user as &$v) {
            $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
            $invite = db("invite_profit_record")
                ->alias("i")
                ->join("user c", "c.id=i.user_id")
                ->field("i.money,c.user_nickname as cname,c.id")
                ->where("i.c_id=" . $v['id'] . " and invite_user_id=" . $v['to_user_id'])
                ->find();

            $v['cname'] = $invite ? $invite['cname'] : '';
            $v['money'] = $invite ? $invite['money'] : '';
            $v['cid'] = $invite ? intval($invite['id']) : '';
            if ($v['money']) {
                $money = $money + $v['money'];
            }
            $profit = $profit + $v['profit'];
            $coin = $coin + $v['coin'];
        }

        $data['coin'] = $coin;
        $data['profit'] = $profit;
        $data['money'] = $money;
        $data['time'] = secs_to_str($time['call_time']);
        $data['user'] = $user;
        echo json_encode($data);
    }

    /*导出*/
    public function export()
    {

        $title = lang('ADMIN_CONSUME');

        $where = [];
        if (!input('request.page')) {
            session('consume_index', null);
        }
        if (input('request.uid') || input('request.touid') || input('request.start_time') || input('request.end_time') || input('request.type') > 0) {
            session('consume_index', input('request.'));
            if (session('consume_index.type') == null) {
                session('consume_index.type', 0);
            }
        }
        if (session('consume_index.uid')) {
            $where['a.user_id'] = intval(session('consume_index.uid'));
        }
        if (session('consume_index.touid')) {
            $where['a.to_user_id'] = intval(session('consume_index.touid'));
        }
        if (session('consume_index.end_time') && session('consume_index.start_time')) {
            $where['a.create_time'] = ['between', [strtotime(session('consume_index.start_time')), strtotime(session('consume_index.end_time'))]];
        }
        if (session('consume_index.type') > 0) {
            $where['a.type'] = session('consume_index.type');
        } else {
            session('consume_index.type', -1);
        }

        $user = db("user_consume_log")->alias("a")
            ->field("a.agent_company,g.login_name,a.user_id,a.id,a.to_user_id,a.coin,a.profit,a.content,a.create_time,a.type,a.table_id,b.user_nickname as uname,c.user_nickname as toname")
            ->join("user b", "b.id=a.user_id", 'left')
            ->join("user c", "c.id=a.to_user_id", 'left')
            ->join("agent g", "g.id=a.agent_company", 'left')
            ->where($where)
            ->order('a.create_time desc')
            ->select();
        $lists = $user->toArray();
        if ($lists != null) {
            $dataResult = array();
            $type = array(0 => lang('Other_consumption'), 1 => lang('Video_consumption'), 2 => lang('Private_license_consumption'), 3 => lang('Gift_consumption'), 4 => lang('One_to_one_video_consumption'), 5 => lang('Private_message_payment'), 6 => lang('ADMIN_GUARDIAN_GET_COIN'), 7 => lang('Play_order_consumption'), 22 => lang('Open_treasure_box_consumption'), 23 => lang('Backpack_gift_consumption'));
            foreach ($lists as $k => $v) {

                $dataResult[$k]['user_id'] = $v['user_id'] ? $v['user_id'] : '';
                $dataResult[$k]['uname'] = $v['uname'] ? $v['uname'] : '';
                $dataResult[$k]['toname'] = $v['toname'] ? $v['toname'] : '';
                $dataResult[$k]['to_user_id'] = $v['to_user_id'] ? $v['to_user_id'] : '';
                $dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : '0';
                $dataResult[$k]['profit'] = $v['profit'] ? $v['profit'] : '0';
                $dataResult[$k]['content'] = $v['content'] ? $v['content'] : '';
                $dataResult[$k]['type'] = $type[$v['type']] ? $type[$v['type']] : $type[0];
                $dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : '';
                $dataResult[$k]['login_name'] = $v['login_name'] ? $v['login_name'] . "(" . $v['agent_company'] . ")" : '';
            }
            $str = lang('ADMIN_CONSUME_USER') . "ID," . lang('ADMIN_CONSUME_USER') . "," . lang('ADMIN_INCOME_USER') . "," . lang('ADMIN_INCOME_USER') . "ID," . lang('ADMIN_CONSUMPTION_NUMBER') . "," . lang('ADMIN_INCOME_NUMBER') . "," . lang('ADMIN_CONSUME_CONTENT') . "," . lang('ADMIN_CONSUME_TYPE') . "," . lang('ADMIN_CONSUME_TIME') . "," . lang('agent_level1');

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error(lang('No_data'));
        }

    }

}
