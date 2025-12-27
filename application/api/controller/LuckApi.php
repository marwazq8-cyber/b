<?php

namespace app\api\controller;

use think\Db;

class LuckApi extends Base
{
//靓号
    public function index()
    {
        $uid = input('param.uid');
        $token = input('param.token');
        //系统金币单位名称
        $config = load_cache('config');
        $coin_name = $config['currency_name'];
        if (empty($uid) || empty($token)) {
            echo lang('Parameter_transfer_error');
            exit;
        }
        $user_info = Db::name("user")->where("id=$uid and token='$token'")->field("luck")->find();
        if (empty($user_info)) {
            echo lang('login_timeout');
            exit;
        }
        $luck = Db::name("user_luck_list")->where("status=0 and uid=0")->field("type")->group("type")->select();
        foreach ($luck as &$v) {
            $v['list'] = Db::name("user_luck_list")->where("status=0 and uid=0 and type=" . $v['type'])->select();
        }

        $this->assign('coin_name', $coin_name);
        $this->assign('luck', $luck);
        $this->assign('user_nubmer', $user_info['luck']);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        return $this->fetch();
    }

    //购买靓号
    public function buy_luck()
    {
        $root = array('code' => 0, 'msg' => lang('Insufficient_Balance'));
        $uid = input('param.uid');
        $token = input('param.token');
        $id = input('param.id');
        $luck = Db::name("user_luck_list")->where("id=" . $id)->find();
        $user = Db::name("user")->where("id=" . $uid . " and token='$token'")->find();
        if (!$user) {
            $root['msg'] = lang('login_timeout');
            echo json_encode($root);
            exit;
        }
        if (!$luck || $luck['status'] == 1 || $luck['uid']) {
            $root['msg'] = lang('Operation_failed_Pretty_number_Purchased');
            echo json_encode($root);
            exit;
        }
        // 启动事务
        db()->startTrans();
        try {
            if ($user['luck'] > 0) {
                Db::name("user_luck_list")->where("uid=" . $uid)->update(array('status' => 0, 'uid' => ''));
            }
            $charging_coin_res = db('user')->where("id=$uid")->setDec('coin', intval($luck['coin']));
            if ($charging_coin_res) {
                Db::name("user_luck_list")->where("id=$id")->update(array('status' => 1, 'uid' => $uid));
                Db::name("user")->where("id=$uid")->update(array('luck' => $luck['nubmer']));
                //增加总消费记录
                add_charging_log($uid, 0, 11, $luck['coin'], $id, 0);
                $root['msg'] = lang('Operation_successful');
                $root['code'] = 1;
            } else {
                $root['msg'] = lang('Insufficient_Balance');
            }
            db()->commit();      // 提交事务
        } catch (\Exception $e) {
            $data['msg'] = lang('Insufficient_Balance');
            db()->rollback();    // 回滚事务
        }
        echo json_encode($root);
        exit;
    }

}