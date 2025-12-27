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
    public function get_music_classify_list($where, $page)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.*,t.name")
            ->join("music_type t", "t.id=m.music_type")
            ->join("music_download d", "m.id=d.music_id", 'left')
            ->order("m.sort DESC")
            ->page($page)
            ->select();

        return $music;
    }

    /**/
    public function get_music_search($where, $uid, $page)
    {

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.id,m.user_name,m.title,d.id as dtype")
            ->join("music_download d", "m.id=d.music_id and d.uid=" . $uid, 'left')
            ->order("m.sort DESC")
            ->page($page)
            ->select();

        return $music;
    }

}