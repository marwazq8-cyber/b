<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2021/02/19
 * Time: 09:23
 * Name: 宝箱游戏接口
 */

namespace app\api\controller;

use think\Request;

class GameBoxApi extends Base
{

    /*
     * 榜单
     * */
    public function get_rank()
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'), 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //$voice_id = intval(input('param.voice_id'));
        $user_info = check_login_token($uid, $token, ['coin', 'income']);

        $size = 20;
        $list = db('game_box_log')
            ->alias('l')
            ->join('user u', 'u.id = l.uid')
            ->field('l.*,sum(l.sum*l.coin) as total,u.luck')
            ->group('uid')
            ->order('total desc')
            //->where('l.type = 3')
            ->page($page, $size)
            ->select();
        //等级
        foreach ($list as &$val) {
            $level = get_level($val['uid']);
            $val['level'] = $level;
        }
        $result['code'] = 1;
        $result['data'] = $list;
        return_json_encode($result);
    }

    /*
     * 中奖纪录
     * */
    public function get_log()
    {
        $result = array('code' => 0, 'msg' => lang('operation_failed'), 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['coin', 'income']);

        $size = 20;
        $list = db('game_box_log')
            ->field('*')
            ->order('addtime desc')
            ->where('uid = ' . $uid)
            ->page($page, $size)
            ->select();
        foreach ($list as &$v) {
            $v['addtime'] = date('m-d H"i', $v['addtime']);
        }
        $result['code'] = 1;
        $result['data'] = $list;
        return_json_encode($result);
    }

    /*
     * 宝箱信息接口
     * 次数、价格
     * */
    public function get_box_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //$type = intval(input('param.type',1));//1普通宝箱 2至尊宝箱
        $user_info = check_login_token($uid, $token, ['coin', 'income', 'game_box_deduction']);
        $config = load_cache('config');
        $data['user_info']['coin'] = $user_info['coin'];
        $data['user_info']['game_box_deduction'] = $user_info['game_box_deduction'];
        //宝箱列表
        $list = db('game_box_list')->where('status = 1')->order('orderno')->select();
        $data['list'] = $list;
        //规则
        $privacy_policy = db('portal_post')->find(55);
        $data['rule'] = '';
        if ($privacy_policy) {
            $data['rule'] = html_entity_decode($privacy_policy['post_content']);
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    /*
     * 开启关闭直接扣费
     * */
    public function game_box_set()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //1扣费 2其他
        $type = intval(input('param.type'));
        $user_info = check_login_token($uid, $token, ['coin', 'income', 'game_box_deduction']);
        if ($type == 1) {
            if ($user_info['game_box_deduction'] == 1) {
                $data['game_box_deduction'] = 0;
            } else {
                $data['game_box_deduction'] = 1;
            }
            db('user')->where('id = ' . $uid)->update($data);
            $result['msg'] = lang('Modified_successfully');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Modification_failed');
            $data['game_box_deduction'] = $user_info['game_box_deduction'];
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    /*
     * 宝箱信息
     * box_id 宝箱ID
     * */
    public function get_box_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $box_id = intval(input('param.box_id'));
        //普通宝箱，至尊宝箱
        $user_info = check_login_token($uid, $token, ['coin', 'income']);
        //$config = load_cache('config');
        $data['user_info']['coin'] = $user_info['coin'];
        //宝箱信息
        $box_info = db('game_box_list')->where('status = 1 and id = ' . $box_id)->find();
        $data['box_info'] = $box_info;
        //开箱次数
        $data['list'] = db('game_box_type')->where('status = 1 and type = ' . $box_id)->select();
        foreach ($data['list'] as &$v) {
            $box = db('game_box_list')->where('id = ' . $v['type'] . ' and status = 1')->find();
            $v['money'] = $box['money'] * $v['sum'];
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    //奖池
    public function get_prize_pool()
    {
        $root = array('code' => 1, 'msg' => '');
        $box_id = intval(input('param.box_id'));

        $list = db('playing_bubble_list')->alias('i')
            ->join('gift g', 'g.id = i.gift_id')
            ->field('i.*,g.img,g.coin,g.name')
            ->where("i.odds >0 ")
            ->where("i.box_id = " . $box_id)
            ->group("i.gift_id")
            ->order("sort desc")
            ->select();
        $root['list'] = $list;
        return_json_encode($root);
    }
}
