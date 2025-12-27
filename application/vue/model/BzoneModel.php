<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-18
 * Time: 11:16
 */

namespace app\vue\model;

use think\Model;
use think\Db;
use think\helper\Time;
use VideoCallRedis;

class BzoneModel extends Model
{
    public function add($data)
    {
        return db('bzone')->insertGetId($data);
    }

    public function get_list($uid, $where, $page)
    {
        $field = 'b.*,u.user_nickname,u.avatar,u.sex,u.age,u.is_online';
        $list = db('bzone')
            ->field($field)
            ->alias('b')
            ->join('user u', 'u.id = b.uid', 'left')
            ->where($where)
            ->order('b.id desc')
            ->page($page)
            ->select();
        if ($list) {
            foreach ($list as &$val) {
                $val['img_list'] = db('bzone_images')
                    ->where(['zone_id' => $val['id']])
                    ->select();
                //点赞
                $val['like_count'] = Db::name('bzone_like')
                    ->where(['zone_id' => $val['id']])
                    ->count();
                $val['is_like'] = 0;
                $like = Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => $val['id']])->find();
                if ($like) {
                    $val['is_like'] = 1;
                }
                $val['distance'] = '';
                //收到的礼物价值
                $gift_total = Db::name('user_gift_log')
                    ->where(['type' => 1, 'other_id' => $val['id']])
                    ->sum('gift_total');
                $val['gift_total'] = $gift_total;
                //用户等级
                //$val['level_info'] = get_grade_level($val['uid']);
                //是否关注
                $val['is_attention'] = get_attention($uid, $val['uid']);
                //是否可以收礼物
                $user_identity = get_user_identity($val['uid']);
                $val['is_gift'] = 1;
                if ($user_identity < 2) {
                    $val['is_gift'] = 0;
                }

                //陪聊等级
                $talker_level = get_talker_level($val['uid']);
                $val['talker_level_name'] = $talker_level['talker_level_name'];
                $val['talker_level_img'] = $talker_level['talker_level_img'];
                //陪玩等级
                $player_level = get_player_level($val['uid']);
                $val['player_level_name'] = $player_level['player_level_name'];
                $val['player_level_img'] = $player_level['player_level_img'];
                //贵族等级
                $noble = get_noble_level($val['uid']);
                $val['noble_img'] = $noble['noble_img'];
                //时间
                $val['publish_time'] = date('m-d H:i:s', $val['publish_time']);
            }
        }
        return $list;
    }

    public function get_nearby_list($uid, $lat, $lng, $where, $page)
    {
        $field_distance = "(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*($lat- b.lat)/360),2)+COS(PI()*$lng/180)* COS(b.lng * PI()/180)*POW(SIN(PI()*($lng-b.lng)/360),2)))) as distance";
        $field = 'b.*,u.user_nickname,u.avatar,u.sex,u.age,u.is_online,' . $field_distance;
        $list = db('bzone')
            ->field($field)
            ->alias('b')
            ->join('user u', 'u.id = b.uid', 'left')
            ->where($where)
            ->order('b.id desc')
            ->page($page)
            ->select();
        if ($list) {
            foreach ($list as &$val) {
                $val['img_list'] = db('bzone_images')
                    ->where(['zone_id' => $val['id']])
                    ->select();
                $val['distance'] = round($val['distance'], 2);
                //点赞
                $val['like_count'] = Db::name('bzone_like')
                    ->where(['zone_id' => $val['id']])
                    ->count();
                $val['is_like'] = 0;
                $like = Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => $val['id']])->find();
                if ($like) {
                    $val['is_like'] = 1;
                }
                //收到的礼物价值
                $gift_total = Db::name('user_gift_log')
                    ->where(['type' => 1, 'other_id' => $val['id']])
                    ->sum('gift_total');
                $val['gift_total'] = $gift_total;
                //用户等级
                //$val['level_info'] = get_grade_level($val['uid']);
                //是否关注
                $val['is_attention'] = get_attention($uid, $val['uid']);
                //是否可以收礼物
                $user_identity = get_user_identity($val['uid']);
                $val['is_gift'] = 1;
                if ($user_identity < 2) {
                    $val['is_gift'] = 0;
                }
                //陪聊等级
                $talker_level = get_talker_level($val['uid']);
                $val['talker_level_name'] = $talker_level['talker_level_name'];
                $val['talker_level_img'] = $talker_level['talker_level_img'];
                //陪玩等级
                $player_level = get_player_level($val['uid']);
                $val['player_level_name'] = $player_level['player_level_name'];
                $val['player_level_img'] = $player_level['player_level_img'];
                //贵族等级
                $noble = get_noble_level($val['uid']);
                $val['noble_img'] = $noble['noble_img'];
                //时间
                $val['publish_time'] = date('m-d H:i:s', $val['publish_time']);
            }
        }
        return $list;
    }

    //详情
    public function get_bzone_info($id, $uid)
    {
        $where = 'b.id = ' . $id;
        $field = 'b.*,u.user_nickname,u.avatar,u.sex,u.age,u.is_online';
        $list = db('bzone')
            ->field($field)
            ->alias('b')
            ->join('user u', 'u.id = b.uid', 'left')
            ->where($where)
            ->order('b.id desc')
            ->find();
        if ($list) {
            $list['img_list'] = db('bzone_images')
                ->where(['zone_id' => $list['id']])
                ->select();
            //点赞
            $list['like_count'] = Db::name('bzone_like')
                ->where(['zone_id' => $list['id']])
                ->count();
            $list['distance'] = '';
            //收到的礼物价值
            $gift_total = Db::name('user_gift_log')
                ->where(['type' => 1, 'other_id' => $list['id']])
                ->sum('gift_total');
            $list['gift_total'] = $gift_total;
            //用户等级
            //$list['level_info'] = get_grade_level($list['uid']);
            //是否关注
            $list['is_attention'] = get_attention($uid, $list['uid']);
            //是否可以收礼物
            $user_identity = get_user_identity($list['uid']);
            $list['is_gift'] = 1;
            if ($user_identity < 2) {
                $list['is_gift'] = 0;
            }

            //陪聊等级
            $talker_level = get_talker_level($list['uid']);
            $list['talker_level_name'] = $talker_level['talker_level_name'];
            $list['talker_level_img'] = $talker_level['talker_level_img'];
            //陪玩等级
            $player_level = get_player_level($list['uid']);
            $list['player_level_name'] = $player_level['player_level_name'];
            $list['player_level_img'] = $player_level['player_level_img'];
            //贵族等级
            $noble = get_noble_level($list['uid']);
            $list['noble_img'] = $noble['noble_img'];
            //用户列表 3个
            $list['gift_user_num'] = Db::name('user_gift_log')
                ->where(['type' => 1, 'other_id' => $list['id']])
                ->group('user_id')
                ->count();
            $list['gift_user_list'] = Db::name('user_gift_log')
                ->alias('g')
                ->join('user u', 'u.id=g.user_id')
                ->where(['g.type' => 1, 'g.other_id' => $list['id']])
                ->field('g.user_id,u.avatar,sum(gift_coin) as total')
                ->group('user_id')
                ->order('total desc')
                ->limit(3)
                ->select();
            $list['publish_time'] = date('m-d H:i:s', $list['publish_time']);

        }
        return $list;
    }

    //打赏用户列表
    public function get_reward_list($id, $page = 1)
    {
        $list = Db::name('user_gift_log')
            ->alias('g')
            ->join('user u', 'u.id=g.user_id')
            ->where(['g.type' => 1, 'g.other_id' => $id])
            ->field('g.user_id,u.avatar,u.sex,u.age,u.user_nickname,sum(g.gift_coin) as total')
            ->group('user_id')
            ->order('total desc')
            ->page($page, 10)
            ->select();

        if ($list) {
            foreach ($list as &$v) {
                //陪聊等级
                $talker_level = get_talker_level($v['user_id']);
                $v['talker_level_name'] = $talker_level['talker_level_name'];
                $v['talker_level_img'] = $talker_level['talker_level_img'];
                //陪玩等级
                $player_level = get_player_level($v['user_id']);
                $v['player_level_name'] = $player_level['player_level_name'];
                $v['player_level_img'] = $player_level['player_level_img'];
                //贵族等级
                $noble = get_noble_level($v['user_id']);
                $v['noble_img'] = $noble['noble_img'];
            }

        }

        return $list;
    }
}
