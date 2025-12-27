<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-18
 * Time: 11:16
 */

namespace app\api\model;

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

    public function get_list($uid, $where, $page, $order = '')
    {
        if (!$order) {
            $order = 'b.id desc,b.heart desc';
        }
        $field = 'b.*,u.user_nickname,u.avatar,u.sex,u.age,u.is_online,u.is_player,u.is_talker';
        $list = db('bzone')
            ->field($field)
            ->alias('b')
            ->join('user u', 'u.id = b.uid', 'left')
            ->where($where)
            ->order($order)
            ->page($page, 10)
            ->select();
        if ($list) {
            $id = array_column($list, 'id');
            $to_user_id = array_column($list, 'uid');
            $img_list = db('bzone_images')->where('zone_id', 'in', $id)->select();
            $count_list = Db::name('bzone_like')
                ->where('zone_id', 'in', $id)
                ->field('zone_id,count(id) as num')
                ->group('zone_id')
                ->select();
            $bzone_like = Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => ['in', $id]])->select();
            $user_attention = db('user_attention')->where("uid=$uid")->where('attention_uid', 'in', $to_user_id)->find();
            //评论数
            $reply_list = db('bzone_reply')
                ->where('zone_id', 'in', $id)
                ->field('zone_id,count(id) as num')
                ->group('zone_id')
                ->select();
            /*dump($id);
            dump($reply_list);
            die();*/
            foreach ($list as &$val) {
                $val['img_list'] = [];
                if ($img_list) {
                    foreach ($img_list as $k1 => $v1) {
                        if ($v1['zone_id'] == $val['id']) {
                            $val['img_list'][] = $v1;
                            unset($img_list[$k1]);
                        }
                    }
                }
                //点赞
                $val['like_count'] = 0;
                if ($count_list) {
                    foreach ($count_list as $k2 => $v2) {
                        if ($v2['zone_id'] == $val['id']) {
                            $val['like_count'] = $v2['num'];
                            unset($count_list[$k2]);
                        }
                    }
                }
                $val['is_like'] = 0;
                if ($bzone_like) {
                    foreach ($bzone_like as $k3 => $v3) {
                        if ($v3['zone_id'] == $val['id']) {
                            $val['is_like'] = 1;
                            unset($bzone_like[$k3]);
                        }
                    }
                }
                //是否关注
                $val['is_attention'] = 0;
                if ($user_attention) {
                    foreach ($user_attention as $k4 => $v4) {
                        if ($v4['attention_uid'] == $val['uid']) {
                            $val['is_attention'] = 1;
                            unset($user_attention[$k4]);
                        }
                    }
                }
                //评论数
                $val['comment_count'] = 0;
                if ($reply_list) {
                    foreach ($reply_list as $k5 => $v5) {
                        if ($v5['zone_id'] == $val['id']) {
                            $val['comment_count'] = $v5['num'];
                            unset($user_attention[$k5]);
                        }
                    }
                }
                $val['distance'] = '';
                //收到的礼物价值
                $val['gift_total'] = $val['heart'];
                //用户等级
                //$val['level_info'] = get_grade_level($val['uid']);
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
                $val['user_name_colors'] = $noble['colors'];
                //时间
                $val['publish_time'] = date('m-d H:i:s', $val['publish_time']);
                $avatar_frame = get_user_dress_up($val['uid'], 3);
                $val['avatar_frame'] = '';
                if ($avatar_frame) {
                    $val['avatar_frame'] = $avatar_frame['icon'];
                }
            }
        }
        return $list;
    }

    public function get_nearby_list($uid, $lat, $lng, $where, $page)
    {
        //经纬度计算距离
        $latitude1 = $lat; //A点纬度
        $longitude1 = $lng; //A点经度
        $field_distance = "6378.137 * 2 * ASIN(SQRT(POW(SIN(($latitude1 * PI() / 180 - b.lat * PI() / 180) / 2), 2) + COS($latitude1 * PI() / 180) * COS(b.lat * PI() / 180) * POW(SIN(($longitude1 * PI() / 180 - b.lng * PI() / 180) / 2),2))) as distance";

        $field = 'b.*,u.user_nickname,u.avatar,u.sex,u.age,u.is_online,u.is_player,u.is_talker,' . $field_distance;
        //$where['b.lng'] = ['','0'];
        //$where['distance'] = ['<','100'];
        $config = load_cache('config');
        $bzone_distance = $config['bzone_distance'];
        $list = db('bzone')
            ->field($field)
            ->alias('b')
            ->join('user u', 'u.id = b.uid', 'left')
            ->where($where)
            ->where("b.lng > 0 and 6378.137 * 2 * ASIN(SQRT(POW(SIN(($latitude1 * PI() / 180 - b.lat * PI() / 180) / 2), 2) + COS($latitude1 * PI() / 180) * COS(b.lat * PI() / 180) * POW(SIN(($longitude1 * PI() / 180 - b.lng * PI() / 180) / 2),2))) <= $bzone_distance")
            ->order('distance asc')
            ->page($page, 10)
            ->select();
        if ($list) {
            $id = array_column($list, 'id');
            $to_user_id = array_column($list, 'uid');
            $img_list = db('bzone_images')->where('zone_id', 'in', $id)->select();
            $count_list = Db::name('bzone_like')
                ->where('zone_id', 'in', $id)
                ->field('zone_id,count(id) as num')
                ->group('zone_id')
                ->select();
            $bzone_like = Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => ['in', $id]])->select();
            $user_attention = db('user_attention')->where("uid=$uid")->where('attention_uid', 'in', $to_user_id)->find();
            //评论数
            $reply_list = db('bzone_reply')
                ->where('zone_id', 'in', $id)
                ->field('zone_id,count(id) as num')
                ->group('zone_id')
                ->select();
            foreach ($list as &$val) {
                $val['img_list'] = [];
                if ($img_list) {
                    foreach ($img_list as $k1 => $v1) {
                        if ($v1['zone_id'] == $val['id']) {
                            $val['img_list'][] = $v1;
                            unset($img_list[$k1]);
                        }
                    }
                }
                //点赞
                $val['like_count'] = 0;
                if ($count_list) {
                    foreach ($count_list as $k2 => $v2) {
                        if ($v2['zone_id'] == $val['id']) {
                            $val['like_count'] = $v2['num'];
                            unset($count_list[$k2]);
                        }
                    }
                }
                $val['is_like'] = 0;
                if ($bzone_like) {
                    foreach ($bzone_like as $k3 => $v3) {
                        if ($v3['zone_id'] == $val['id']) {
                            $val['is_like'] = 1;
                            unset($bzone_like[$k3]);
                        }
                    }
                }
                //是否关注
                $val['is_attention'] = 0;
                if ($user_attention) {
                    foreach ($user_attention as $k4 => $v4) {
                        if ($v4['attention_uid'] == $val['uid']) {
                            $val['is_attention'] = 1;
                            unset($user_attention[$k4]);
                        }
                    }
                }
                //评论数
                $val['comment_count'] = 0;
                if ($reply_list) {
                    foreach ($reply_list as $k5 => $v5) {
                        if ($v5['zone_id'] == $val['id']) {
                            $val['comment_count'] = $v5['num'];
                            unset($user_attention[$k5]);
                        }
                    }
                }
                $val['distance'] = round($val['distance'], 2);
                //收到的礼物价值
                $val['gift_total'] = $val['heart'];
                //用户等级
                //$val['level_info'] = get_grade_level($val['uid']);
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
                $val['user_name_colors'] = $noble['colors'];
                //时间
                $val['publish_time'] = date('m-d H:i:s', $val['publish_time']);
                $avatar_frame = get_user_dress_up($val['uid'], 3);
                $val['avatar_frame'] = '';
                if ($avatar_frame) {
                    $val['avatar_frame'] = $avatar_frame['icon'];
                }
            }
        }
        return $list;
    }

    //详情
    public function get_bzone_info($id, $uid)
    {
        $where = 'b.id = ' . $id;
        $field = 'b.*,u.user_nickname,u.avatar,u.sex,u.age,u.is_online,u.is_player,u.is_talker';
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
            //评论数
            $list['comment_count'] = db('bzone_reply')
                ->where(['zone_id' => $list['id']])
                ->count();
            $list['is_like'] = 0;
            $like = Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => $list['id']])->find();
            if ($like) {
                $list['is_like'] = 1;
            }
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
            $list['user_name_colors'] = $noble['colors'];
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
                $v['user_name_colors'] = $noble['colors'];
            }

        }

        return $list;
    }

    /*
     * 获取动态
     * */
    public function selFind($id)
    {
        return db('bzone')->find($id);
    }

    public function getLikeList($id, $page)
    {
        $list = db('bzone_like')
            ->alias('b')
            ->join('user u', 'u.id=b.uid')
            ->field('b.*,u.user_nickname,u.avatar,u.sex,u.age,u.luck')
            ->where('b.zone_id = ' . $id)
            ->order('b.addtime desc')
            ->page($page)
            ->select();
        if ($list) {
            foreach ($list as &$v) {
                //头饰
                $dress_up = get_user_dress_up($v['uid'], 3);
                if ($dress_up) {
                    $v['avatar_frame'] = $dress_up['icon'];
                } else {
                    $v['avatar_frame'] = '';
                }
                $v['addtime'] = $this->format_time($v['addtime']);
                $noble = get_noble_level($v['uid']);
                $v['noble_img'] = $noble['noble_img'];

            }
            //清除未读
            //db('bzone_like')->where('to_user_id = '.$uid)->update(['read_status'=>1]);
        }

        return $list;
    }

    /*
     * 获赞数量
     * $uid 被赞用户ID
     * $type 1总赞 2未读数量
     * */
    public function getLikeCount($uid, $type)
    {
        if ($type == 1) {
            $count = db('bzone_like')->where('to_user_id = ' . $uid)->count();
        } else {
            $count = db('bzone_like')->where('read_status = 0 and to_user_id = ' . $uid)->count();
        }
        return $count;
    }

    function format_time($time)
    {
        $day_time = strtotime(date('Y-m-d', NOW_TIME));
        $yesterday = $day_time - 86400;
        $tow_day = $day_time - (86400 * 2);
        $three_day = $day_time - (86400 * 3);
        $seven_day = $day_time - (86400 * 7);
        if ($time > $day_time) {
            $text = date('H:i', $time);
        } else if ($time > $yesterday) {
            $text = lang('yesterday') . date('H:i', $time);
        } else if ($time > $tow_day) {
            $text = lang('before_yesterday') . date('H:i', $time);
        } elseif ($time > $three_day) {
            $text = lang('three_days_ago') . date('H:i', $time);
        } else {
            $text = date('Y-m-d H:i', $time);
        }
        return $text;
    }

}
