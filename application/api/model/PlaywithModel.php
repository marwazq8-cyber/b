<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-19
 * Time: 11:51
 */

namespace app\api\model;

use think\Model;
use think\Db;
use think\helper\Time;
use VideoCallRedis;

class PlaywithModel extends Model
{
    //游戏列表
    public function get_game_list($where = '')
    {
        $type_list = Db::name('play_game_type')
            ->order('orderno')
            ->field('id,type_name')
            ->select();
        if ($type_list) {
            foreach ($type_list as &$val) {
                $val['game_list'] = Db::name('play_game')
                    ->where('type_id = ' . $val['id'])
                    ->where($where)
                    ->order('orderno')
                    ->field('id,name,img')
                    ->select();
            }
        }

        return $type_list;
    }

    //id 获取后台设置接单信息
    public function get_game_type($id)
    {
        $res = Db::name('play_game_order_info')
            ->where('game_id = ' . $id)
            ->order('orderno')
            ->field('id,name,val,type')
            ->select();
        if ($res) {
            foreach ($res as &$val) {
                $type = explode(',', $val['val']);
                $val['val'] = [];
                foreach ($type as $v) {
                    $arr = ['name' => $v];
                    array_push($val['val'], $arr);
                }

            }
        }
        return $res;
    }

    //添加陪玩信息
    public function add_skills($data)
    {
        return db('skills_info')->insertGetId($data);
    }

    //加单信息记录
    public function add_skills_log($data)
    {
        db('skills_info_log')->insertGetId($data);
    }

    //陪玩接单信息详情
    public function get_skills_info($where)
    {
        return db('skills_info')->where($where)->find();
    }

    //删除
    public function del_game_order($where)
    {
        return db('skills_info')->where($where)->delete();
    }

    //接单信息
    public function get_skills_info_all($id, $uid)
    {
        $where = 's.id = ' . $id . ' and u.is_player = 1';
        $info = db('skills_info')
            ->alias('s')
            ->join('user u', 'u.id=s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            ->field('s.*,u.user_nickname,u.sex,u.age,u.avatar,u.is_online,u.city,g.name as game_name')
            ->find();
        if ($info) {
            //是否关注
            $info['other'] = json_decode($info['other']);
            $info['is_attention'] = get_attention($uid, $info['uid']);
            //认证图片
            $auth_player = db('auth_player')
                ->where(['uid' => $info['uid'], 'game_id' => $info['game_id']])
                ->find();
            $info['player_img'] = [];
            if ($auth_player) {
                $info['player_img'] = db('auth_player_img')
                    ->where('pid = ' . $auth_player['id'])
                    ->select();
            }

            //评价标签
            $info['comment_label'] = db('skills_info_label')
                ->where('skills_id = ' . $info['id'])
                ->field('label_name as name,num')
                ->order('num desc')
                ->select();

            $info['comment'] = db('skills_comment')
                ->alias('c')
                ->join('user u', 'u.id = c.uid')
                ->where('c.skills_id = ' . $info['id'])
                ->field('u.user_nickname,u.avatar,c.*')
                ->find();

            if ($info['comment']) {
                $info['comment']['addtime'] = date('Y-m-d', $info['comment']['addtime']);
                $info['comment']['label'] = explode(',', $info['comment']['name']);
                $avatar_frame = get_user_dress_up($info['comment']['uid'], 3);
                $info['comment']['avatar_frame'] = '';
                if ($avatar_frame) {
                    $info['comment']['avatar_frame'] = $avatar_frame['icon'];
                }
                $noble = get_noble_level($info['comment']['uid']);
                $info['comment']['noble_img'] = $noble['noble_img'];
                $info['comment']['user_name_colors'] = $noble['colors'];
            }

            $info['comment_count'] = db('skills_comment')
                ->where('skills_id = ' . $info['id'])
                ->count();
            //推荐数
            $info['recommend_count'] = db('skills_comment')
                ->where('skills_id = ' . $info['id'])
                ->where('is_recommend = 1')
                ->count();
            $map['status'] = ['in', [5, 6, 11]];
            $info['skills_order_num'] = db('skills_order')
                ->where(['skills_id' => $id])
                ->where($map)
                ->count();
            $noble = get_noble_level($info['uid']);
            $info['noble_img'] = $noble['noble_img'];
            $info['user_name_colors'] = $noble['colors'];
        }

        return $info;
    }

    public function get_skills_comment($id, $page = 1, $key = '')
    {
        //最新评价
        $where = [];
        if ($key) {
            $where['c.name'] = ['like', '%' . $key . '%'];
        }
        $list = db('skills_comment')
            ->alias('c')
            ->join('user u', 'u.id=c.uid')
            ->where('c.skills_id = ' . $id)
            ->where($where)
            ->field('c.*,u.user_nickname,u.avatar')
            ->order('c.addtime desc')
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            $v['addtime'] = date('Y-m-d', $v['addtime']);
            if (empty($v['name'])) {
                $v['label'] = [];
            } else {
                $v['label'] = explode(',', $v['name']);
            }
            $avatar_frame = get_user_dress_up($v['uid'], 3);
            $v['avatar_frame'] = '';
            if ($avatar_frame) {
                $v['avatar_frame'] = $avatar_frame['icon'];
            }
            $noble = get_noble_level($v['uid']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
        }
        return $list;
    }

    //评价标签
    public function get_skills_comment_label($id)
    {
        //评价标签
        $info['comment_label'] = db('skills_info_label')
            ->where('skills_id = ' . $id)
            ->field('label_name as name,num')
            ->order('num desc')
            ->select();
        //推荐数
        $info['recommend_count'] = db('skills_comment')
            ->where('skills_id = ' . $id)
            ->where('is_recommend = 1')
            ->count();
        $info['recommend_not_count'] = db('skills_comment')
            ->where('skills_id = ' . $id)
            ->where('is_recommend = 0')
            ->count();
        $map['skills_id'] = $id;
        $map['status'] = ['in', [5, 6, 11]];
        $info['skills_order_num'] = db('skills_order')
            ->where($map)
            ->count();
        return $info;
    }

    public function selOrderType()
    {
        return db('game_order_type')->order('orderno')->field('name')->select();
    }

}
