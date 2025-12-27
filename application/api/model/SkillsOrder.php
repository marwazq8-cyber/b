<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-25
 * Time: 15:30
 */

namespace app\api\model;

use think\Model;
use think\Cache;

class SkillsOrder extends Model
{
    //添加订单
    public function add_order($data)
    {
        return $this->insertGetId($data);
    }

    //订单列表
    public function get_order_list($where, $page)
    {
        return $this->where($where)->page($page)->select();
    }

    //修改订单状态
    public function up_order($where, $data)
    {
        return $this->where($where)->update($data);
    }

    public function get_order_info($where)
    {
        return $this->where($where)->find();
    }

    //我的订单 用户
    public function get_user_order_list($where, $page)
    {
        //进行中
        $list_map['s.status'] = ['in', [1, 2, 3, 4, 9]];
        $list = $this->alias('s')
            ->join('user u', 'u.id =s.touid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            ->where($list_map)
            //->where('s.status < 5 and s.status = 9')
            ->order('s.addtime desc')
            ->field('s.*,u.user_nickname,u.avatar,u.is_online,g.name as game_name')
            ->page($page)
            ->select();
        foreach ($list as &$val) {
            $time_left = $val['ordertime'] - NOW_TIME;
            $val['time_left'] = $time_left;
            //剩余接单时间
            $val['last_time'] = 0;
            $addtime = $val['addtime'];
            if ((NOW_TIME - $addtime) < (15 * 60)) {
                $val['last_time'] = (15 * 60) - (NOW_TIME - $addtime);
            }
            $val['status'] = $val['status'] == 19 ? 10 : $val['status'];
            $time = strtotime(date('Y-m-d', NOW_TIME));
            $addtime = strtotime(date('Y-m-d', $val['ordertime']));
            if ($time == $addtime) {
                $val['ordertime'] = lang('today') . date('H:i', $val['ordertime']);
            } else {
                $val['ordertime'] = date('m-d H:i', $val['ordertime']);
            }
        }
        //已完成
        $list_end_map['s.status'] = ['not in', [1, 2, 3, 4, 9]];
        $list_end = $this->alias('s')
            ->join('user u', 'u.id =s.touid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            ->where($list_end_map)
            //->where('s.status > 4 and s.status != 9')
            ->order('s.addtime desc')
            ->field('s.*,u.user_nickname,u.avatar,u.is_online,g.name as game_name')
            ->page($page)
            ->select();
        foreach ($list_end as &$val) {
            $time_left = $val['ordertime'] - NOW_TIME;
            $val['time_left'] = $time_left;
            //剩余接单时间
            $val['last_time'] = 0;
            $addtime = $val['addtime'];
            if ((NOW_TIME - $addtime) < (15 * 60)) {
                $val['last_time'] = (15 * 60) - (NOW_TIME - $addtime);
            }
            $val['status'] = $val['status'] == 19 ? 10 : $val['status'];
            $time = strtotime(date('Y-m-d', NOW_TIME));
            $addtime = strtotime(date('Y-m-d', $val['ordertime']));
            if ($time == $addtime) {
                $val['ordertime'] = lang('today') . date('H:i', $val['ordertime']);
            } else {
                $val['ordertime'] = date('m-d H:i', $val['ordertime']);
            }
        }
        $data = [
            'list' => $list,
            'list_end' => $list_end,
        ];
        return $data;
    }

    //订单详情 接单
    public function get_info($where)
    {
        $info = $this->alias('s')
            ->join('user u', 'u.id =s.touid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            //->order('s.skills_order_num desc,s.praise_rate desc')
            ->field('s.*,u.user_nickname,u.avatar,u.is_online,g.name as game_name,g.img')
            ->find();
        if ($info) {
            $info['refund_addtime'] = 0;
            $info['refund_edit_time'] = 0;
            $info['refund_info'] = '';
            $info['denial_reason'] = '';
            $info['status'] = $info['status'] == 19 ? 10 : $info['status'];
            //退款
            $refund = db('skills_order_refund')
                ->where('order_id =' . $info['id'])
                ->find();
            //$info['refund_status'] = 0;

            if ($refund) {
                //$info['refund_status'] = $refund['status'];
                $info['refund_addtime'] = $refund['addtime'];
                $info['refund_edit_time'] = $refund['edit_time'];
                $info['refund_info'] = $refund['refund_info'];
                $info['denial_reason'] = $refund['denial_reason'];
            }
            $time_left = 0;
            //$addtime = strtotime(date('Y-m-d',$info['ordertime']));
            //if($info['status']==2){
            $time_left = $info['ordertime'] - NOW_TIME;;

            $info['time_left'] = $time_left;
            //剩余接单时间
            $info['last_time'] = 0;
            $addtime = $info['addtime'];
            if ((NOW_TIME - $addtime) < (15 * 60)) {
                $info['last_time'] = (15 * 60) - (NOW_TIME - $addtime);
            }
        }
        return $info;
    }

    //订单详情 下单
    public function get_info_player($where)
    {
        $info = $this->alias('s')
            ->join('user u', 'u.id =s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            //->order('s.skills_order_num desc,s.praise_rate desc')
            ->field('s.*,u.user_nickname,u.avatar,u.is_online,g.name as game_name,g.img')
            ->find();
        if ($info) {
            //退款
            $refund = db('skills_order_refund')
                ->where('order_id =' . $info['id'])
                ->find();
            //$info['refund_status'] = 0;
            $info['refund_addtime'] = 0;
            $info['refund_edit_time'] = 0;
            $info['refund_info'] = '';
            $info['denial_reason'] = '';
            if ($refund) {
                //$info['refund_status'] = $refund['status'];
                $info['refund_addtime'] = $refund['addtime'];
                $info['refund_edit_time'] = $refund['edit_time'];
                $info['refund_info'] = $refund['refund_info'];
                $info['denial_reason'] = $refund['denial_reason'];
            }

            $time_left = $info['ordertime'] - NOW_TIME;

            $info['time_left'] = $time_left;
            //剩余接单时间
            $info['last_time'] = 0;
            $addtime = $info['addtime'];
            if ((NOW_TIME - $addtime) < (15 * 60)) {
                $info['last_time'] = (15 * 60) - (NOW_TIME - $addtime);
            }
        }
        return $info;
    }

    //我的订单 陪玩
    public function get_player_order_list($where, $page)
    {
        //进行中
        $list_map['s.status'] = ['in', [1, 2, 3, 9]];
        $list = $this->alias('s')
            ->join('user u', 'u.id =s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            ->where($list_map)
            //->where('s.status < 4')
            ->order('s.addtime desc')
            ->field('s.*,u.user_nickname,u.avatar,u.is_online,g.name as game_name')
            ->page($page)
            ->select();
        foreach ($list as &$val) {
            //$time_left = 0;
            $time_left = $val['ordertime'] - NOW_TIME;
            $val['time_left'] = $time_left;
            //剩余接单时间
            $val['last_time'] = 0;
            $addtime = $val['addtime'];
            if ((NOW_TIME - $addtime) < (15 * 60)) {
                $val['last_time'] = (15 * 60) - (NOW_TIME - $addtime);
            }
            $time = strtotime(date('Y-m-d', NOW_TIME));
            $addtime = strtotime(date('Y-m-d', $val['ordertime']));
            if ($time == $addtime) {
                $val['ordertime'] = lang('today') . date('H:i', $val['ordertime']);
            } else {
                $val['ordertime'] = date('m-d H:i', $val['ordertime']);
            }
        }
        //已完成
        $list_end_map['s.status'] = ['not in', [1, 2, 3, 9]];
        $list_end = $this->alias('s')
            ->join('user u', 'u.id =s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($list_end_map)
            ->where($where)
            ->where('s.status > 3')
            ->order('s.addtime desc')
            ->field('s.*,u.user_nickname,u.avatar,u.is_online,g.name as game_name')
            ->page($page)
            ->select();
        foreach ($list_end as &$val) {
            $time_left = $val['ordertime'] - NOW_TIME;
            $val['time_left'] = $time_left;
            //剩余接单时间
            $val['last_time'] = 0;
            $addtime = $val['addtime'];
            if ((NOW_TIME - $addtime) < (15 * 60)) {
                $val['last_time'] = (15 * 60) - (NOW_TIME - $addtime);
            }
            $time = strtotime(date('Y-m-d', NOW_TIME));
            $addtime = strtotime(date('Y-m-d', $val['ordertime']));
            if ($time == $addtime) {
                $val['ordertime'] = lang('today') . date('H:i', $val['ordertime']);
            } else {
                $val['ordertime'] = date('m-d H:i', $val['ordertime']);
            }

        }
        $data = [
            'list' => $list,
            'list_end' => $list_end,
        ];
        return $data;
    }

    //订单详情 下单 进行中
}
