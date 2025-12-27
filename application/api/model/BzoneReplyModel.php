<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2021/12/29
 * Time: 11:01 上午
 * Name:
 */

namespace app\api\model;

use think\Model;
use think\Db;
use think\helper\Time;
use VideoCallRedis;

class BzoneReplyModel extends Model
{
    public function saveOne($data)
    {
        return Db::name('bzone_reply')->insert($data);
    }

    public function saveOneId($data)
    {
        return Db::name('bzone_reply')->insertGetId($data);
    }

    /*
     * 获取评论列表
     * */
    public function selList($zone_id, $page = 1)
    {
        $limit = 10;
        $list = Db::name('bzone_reply')
            ->where('zone_id = ' . $zone_id)
            ->order('id')
            ->page($page, $limit)
            ->select();
        //循环下从缓存中取出用户数据
        $temp_list = array();
        foreach ($list as $k => $v) {
            $user_info = get_user_base_info($v['uid']);
            $v['addtime'] = time_trans($v['addtime']);
            $v['body'] = emoji_decode($v['body']);
            $temp_list[$k] = $v;
            $temp_list[$k]['userInfo'] = $user_info;
        }
        return $temp_list;
    }

    /*
     * 回复评论列表
     * $zone_id 动态ID
     * $reply_id 评论ID
     * $page 分页
     * */
    public function replyList($zone_id, $reply_id, $page = 1)
    {
        $limit = 10;
        $list = Db::name('bzone_reply')
            ->where('zone_id = ' . $zone_id)
            ->where('reply_id = ' . $reply_id)
            ->order('id')
            ->page($page, $limit)
            ->select();
        //循环下从缓存中取出用户数据
        $temp_list = array();
        foreach ($list as $k => $v) {
            $user_info = get_user_base_info($v['uid']);
            $v['addtime'] = time_trans($v['addtime']);
            $v['body'] = emoji_decode($v['body']);
            $temp_list[$k] = $v;
            $temp_list[$k]['userInfo'] = $user_info;
        }
        return $temp_list;
    }

    /*
     * 评论数量
     * $zone_id 动态ID
     * */
    public function selCount($zone_id)
    {
        $count = Db::name('bzone_reply')->where('zone_id = ' . $zone_id)->count();
        return $count;
    }

    /*
     * 删除评论
     * */
    public function delReply($id, $uid)
    {
        $res = Db::name('bzone_reply')
            ->where('uid = ' . $uid . ' and id = ' . $id)
            ->delete();
        return $res;
    }
}
