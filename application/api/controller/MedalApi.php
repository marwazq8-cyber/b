<?php

namespace app\api\controller;

use think\Db;

class MedalApi extends Base
{

    /**
     * 勋章
     */
    public function index()
    {
        $uid = input('param.uid');
        $token = input('param.token');
        if (empty($uid) || empty($token)) {
            echo lang('Parameter_transfer_error');
            exit;
        }
        $user_info = Db::name("user")->where("id=$uid")->find();
        $medal = Db::name("medal")->order("sort desc")->select();
        $user_info['is_medal'] = $user_info['medal_id'] && $user_info['medal_end_time'] >= NOW_TIME ? 1 : 0;
        if ($user_info['medal_end_time']) {
            $user_info['medal_end_time'] = date("Y.m.d", $user_info['medal_end_time']);
        }

        $config = load_cache('config');

        $this->assign('currency_name', $config['currency_name']);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        $this->assign('user_info', $user_info);
        $this->assign('medal', $medal);

        return $this->fetch();
    }

    /*
    *   购买勋章
    */
    public function buy_medal()
    {
        $root = array('code' => 0, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $id = input('param.id');
        $user_info = Db::name("user")->where("id=$uid and token='" . $token . "'")->find();
        $is_medal = $user_info['medal_id'] && $user_info['medal_end_time'] >= NOW_TIME ? 1 : 0;

        if (!$user_info) {
            $root['msg'] = lang('login_timeout');
            echo json_encode($root);
            exit;
        }
        $medal = Db::name("medal")->where("id=" . $id)->find();
        if (!$medal) {
            $root['msg'] = lang('Parameter_transfer_error');
            echo json_encode($root);
            exit;
        }
        $time = NOW_TIME + 24 * 60 * 60 * $medal['time'];   //购买
        if ($is_medal && $user_info['medal_id'] == $id) {   //续费
            $time = $user_info['medal_end_time'] + 24 * 60 * 60 * $medal['time'];
        }
        // 启动事务
        db()->startTrans();
        try {

            $charging_coin_res = db('user')->where(['id' => $uid])->Dec('coin', $medal['coin'])->update(array('medal_end_time' => $time, 'medal_id' => $id));

            if ($charging_coin_res) {

                //增加总消费记录
                $root['code'] = 1;
                $root['msg'] = lang('Purchase_succeeded');
                add_charging_log($uid, 1, 8, $medal['coin'], $id, 0, lang('purchase') . $medal['name'] . '消费');
            } else {
                $root['msg'] = lang('Insufficient_Balance');
            }
            // 提交事务
            db()->commit();
        } catch (\Exception $e) {
            $root['msg'] = lang('Insufficient_Balance');
            // 回滚事务
            db()->rollback();
        }

        echo json_encode($root);
        exit;

    }


}