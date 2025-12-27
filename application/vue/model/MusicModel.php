<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class MusicModel extends Model
{
    /* 获取音乐分类列表 */
    public function get_music_type_list($where)
    {

        $music = Db::name('music_type')->field("id,name")->where($where)->order("sort desc")->select();

        return $music;
    }

    /* 获取分类下的音乐详情 */
    public function get_music_classify_list($where, $page, $uid)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.*,t.name,d.id as dtype,d.status as status")
            ->join("music_type t", "t.id=m.music_type")
            ->join("music_download d", "m.id=d.music_id and d.uid=" . $uid . " and d.status=1", 'left')
            ->order("m.sort DESC")
            ->page($page)
            ->select();

        return $music;
    }

    /* 获取搜索 */
    public function get_music_search($where, $uid, $page)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.id,m.user_name,m.title,d.id as dtype")
            ->join("music_download d", "m.id=d.music_id and d.uid=" . $uid . " and d.status=1", 'left')
            ->order("m.sort DESC")
            ->page($page)
            ->select();

        return $music;
    }

    /* 用户单个下载记录 */
    public function get_music_download_one($where, $uid)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.id,m.user_name,m.title,d.id as dtype,d.status")
            ->join("music_download d", "m.id=d.music_id and d.uid=" . $uid, 'left')
            ->find();

        return $music;
    }

    /* 用户单个下载记录 */
    public function add_music_download($data)
    {

        $music = Db::name('music_download')->insertGetId($data);

        return $music;
    }

    // 删除下载的音乐
    public function del_music_download($where)
    {

        $music = Db::name('music_download')->where($where)->delete();

        return $music;
    }

    /* 获取音乐伴奏列表 */
    public function get_music_hot($where, $order, $group, $limit)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.img,m.user_name,m.id")
            ->order($order)
            ->group($group)
            ->limit(0, $limit)
            ->select();

        return $music;
    }

    /* 获取音乐伴奏列表 */
    public function get_music_hot_list($where, $order, $limit, $uid)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->join("music_download d", "m.id=d.music_id and d.uid=" . $uid . " and d.status=1", 'left')
            ->field("m.id,m.title,m.img,m.user_name,m.music_time,m.music_size,m.url,d.id as dtype")
            ->order($order)
            ->limit(0, $limit)
            ->select();

        return $music;
    }

    /* 获取歌手列表 */
    public function get_music_singer($where, $order, $group, $page)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.img,m.user_name,m.id")
            ->order($order)
            ->group($group)
            ->page($page)
            ->select();

        return $music;
    }

    /* 获取音乐伴奏列表 */
    public function get_music_song($where, $order, $page, $uid)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->join("music_download d", "m.id=d.music_id and d.uid=" . $uid . " and d.status=1", 'left')
            ->field("m.id,m.title,m.img,m.user_name,m.music_time,m.music_size,m.url,d.id as dtype")
            ->order($order)
            ->page($page)
            ->select();

        return $music;
    }

}