<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-25
 * Time: 09:28
 */

namespace app\api\model;

use think\Model;
use think\Cache;

class SkillsInfo extends Model
{
    public function get_new($page = 1)
    {
        $list = $this->alias('s')
            ->join('user u', 'u.id=s.uid')
            ->join('skills_recommend_label r', 'r.id = s.recommend_label')
            ->join('play_game g', 'g.id = s.game_id')
            ->where('s.is_recommend = 1 and s.status = 1')
            ->order('s.recommend_time desc')
            ->field('s.*,u.user_nickname,u.sex,u.age,u.avatar,r.label_name,r.label_img,g.name as game_name')
            ->page($page, 3)
            ->select();
        foreach ($list as &$v) {
            $noble = get_noble_level($v['uid']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
        }
        return $list;
    }

    /*
     * 用户所有技能列表*/
    public function get_player_list($where, $page = 1)
    {
        $Lat = rand(1, 99);
        $Lng = rand(1, 99);
        $distance = ",(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*($Lat- u.longitude)/360),2)+COS(PI()*$Lng/180)* COS(u.latitude * PI()/180)*POW(SIN(PI()*($Lng-u.latitude)/360),2)))) as distance";
        $list = $this->alias('s')
            ->join('user u', 'u.id=s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            ->where('s.status = 1')
            ->where('u.user_status!=0')
            ->field('s.*,u.user_nickname,u.sex,u.age,u.avatar,u.is_online,u.city,g.name as game_name,g.img,g.bg_img' . $distance)
            ->order('u.is_online desc,distance asc,s.create_time desc,s.skills_order_num desc,s.praise_rate desc')
            //->group('s.uid')
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            //推荐数
            $v['recommend_count'] = db('skills_comment')
                ->where('skills_id = ' . $v['id'])
                ->where('is_recommend = 1')
                ->count();
            //$map['status'] = ['in',[5,6,11]];
            $v['skills_order_num'] = db('skills_order')
                ->where(['skills_id' => $v['id']])
                //->where($map)
                ->count();
            $noble = get_noble_level($v['uid']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
            //勋章
            $uid_medal = get_user_dress_up($v['uid'], 1);
            $v['user_medal'] = '';
            if ($uid_medal) {
                $v['user_medal'] = $uid_medal['icon'];
            }
        }
        return $list;
    }

    public function get_group_player_list($where, $page = 1, $order = '', $user_info = '')
    {
        if ($order) {
            $order = 'u.is_online desc,' . $order;
        } else {
            $order = 'u.is_online desc,distance asc,s.create_time desc,s.skills_order_num desc,s.praise_rate desc';
        }
        if ($user_info) {
            $latitude1 = $user_info['latitude'];
            $longitude1 = $user_info['longitude'];
        } else {
            $latitude1 = rand(1, 99);
            $longitude1 = rand(1, 99);
        }

        //$distance = ",(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*($Lat- u.longitude)/360),2)+COS(PI()*$Lng/180)* COS(u.latitude * PI()/180)*POW(SIN(PI()*($Lng-u.latitude)/360),2)))) as distance";
        //$latitude2 = 'u.latitude'; //B点纬度
        //$longitude2 = 'u.longitude'; //B点经度
        $distance = ",round(6378.137 * 2 * ASIN(SQRT(POW(SIN(($latitude1 * PI() / 180 - u.latitude * PI() / 180) / 2), 2) + COS($latitude1 * PI() / 180) * COS(u.latitude * PI() / 180) * POW(SIN(($longitude1 * PI() / 180 - u.longitude * PI() / 180) / 2),2))),2) as distance";
        $list = $this->alias('s')
            ->join('user u', 'u.id=s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            ->where('s.status = 1')
            ->where('u.user_status!=0 and s.status = 1')
            ->field('s.*,u.user_nickname,u.sex,u.age,u.avatar,u.is_online,u.city,g.name as game_name,g.img,g.bg_img' . $distance)
            ->order($order)
            ->group('s.uid')
            ->page($page)
            ->select();
        foreach ($list as &$v) {
            //推荐数
            $v['recommend_count'] = db('skills_comment')
                ->where('skills_id = ' . $v['id'])
                ->where('is_recommend = 1')
                ->count();
            $map['status'] = ['in', [5, 6, 11]];
            $v['skills_order_num'] = db('skills_order')
                ->where(['skills_id' => $v['id']])
                ->where($map)
                ->count();
            $noble = get_noble_level($v['uid']);
            $v['noble_img'] = $noble['noble_img'];
            $v['user_name_colors'] = $noble['colors'];
            //勋章
            $uid_medal = get_user_dress_up($v['uid'], 1);
            $v['user_medal'] = '';
            if ($uid_medal) {
                $v['user_medal'] = $uid_medal['icon'];
            }
        }
        return $list;
    }

    public function get_list($where)
    {
        return $this->alias('s')
            //->join('user u','u.id=s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            ->order('s.skills_order_num desc,s.praise_rate desc')
            ->field('s.*,g.name as game_name')
            ->select();
    }

    //获取陪玩信息
    public function get_info($where)
    {
        $info = $this->alias('s')
            ->join('user u', 'u.id=s.uid')
            ->join('play_game g', 'g.id = s.game_id')
            ->where($where)
            //->order('s.skills_order_num desc,s.praise_rate desc')
            ->field('s.*,u.user_nickname,u.sex,u.age,u.avatar,u.is_online,u.city,g.name as game_name')
            ->find();
        $noble = get_noble_level($info['uid']);
        $info['noble_img'] = $noble['noble_img'];
        $info['user_name_colors'] = $noble['colors'];
        return $info;
    }
}
