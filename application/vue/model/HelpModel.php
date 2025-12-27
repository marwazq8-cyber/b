<?php

namespace app\vue\model;

use think\Model;
use think\Db;

class HelpModel extends Model
{
    // 获取我访客的用户
    public function get_help_type()
    {

        $list = db('portal_category')->alias('c')
            ->join('portal_category p', 'c.parent_id=p.id')
            ->field('c.id,c.name')
            ->where('p.name="使用帮助" and c.status=1')
            ->order('c.list_order asc')
            ->select();

        foreach ($list as &$v) {
            $portals = Db::name("portal_post")->alias('b')
                ->where("a.status=1 and a.category_id=" . $v['id'])
                ->join("portal_category_post a", "b.id=a.post_id ")
                ->field('b.id,b.post_title')
                ->order('a.list_order asc')
                ->select();

            $v['list'] = $portals;
        }

        return $list;
    }

    // 获取我访客的用户
    public function get_help_center($id)
    {
        $portal = Db::name("portal_post")
            ->where("id=" . $id . " and post_type=1 and post_status=1")
            ->find();
        $post_content = $portal ? htmlspecialchars_decode($portal['post_content']) : lang('No_data');
        return $post_content;
    }

    // 获取我的版本号
    public function get_about_me($uid)
    {

        $list = Db::name("device_info")->where("uid=" . $uid)->find();
        return $list;
    }

}
