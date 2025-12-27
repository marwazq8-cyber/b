<?php

namespace app\api\model;

use think\Model;
use think\Db;
use VideoCallRedis;

class VideoCallModel extends Model
{
    /*
    * 查询是否有自己的通话记录
    */
    public function video_call_record($uid)
    {

        $call_user_where = 'call_be_user_id=' . $uid . ' or user_id=' . $uid . ' or anchor_id=' . $uid;

        $self_video_call_record = db("video_call_record")->where($call_user_where)->select();

        return $self_video_call_record;
    }

    /*
    * 添加拨打记录
    */
    public function add_video_call_record($data)
    {

        $status = db("video_call_record")->insert($data);

        return $status;
    }

    /*
    *  通过通话号查询通话记录
    */
    public function sel_video_call_record_one($channel_id, $id = '')
    {

        $where = $id == '' ? 'channel_id=' . $channel_id : 'id =' . $id;

        $call_record = db('video_call_record')->where($where)->find();

        return $call_record;
    }

    /*
    * 获取真正通话的信息
    */
    public function video_call_record_one($where)
    {

        $video_call_record = db("video_call_record")->where($where)->find();

        return $video_call_record;
    }

    /*
    * 修改通话状态
    */
    public function upd_video_call_record($id, $data)
    {

        $call_record = db('video_call_record')->where('id =' . $id)->update($data);

        return $call_record;
    }

    /*拒绝接听电话删除通话记录*/
    public function del_video_call_record($id)
    {

        $video_call = db('video_call_record')->where('id =' . $id)->delete();

        return $video_call;
    }

    /*添加通话记录*/
    public function add_video_call_record_log($data)
    {

        $video_call = db('video_call_record_log')->insert($data);

        return $video_call;
    }

    /* 查询最后一条扣费记录 */
    public function sel_video_charging_record_one($where)
    {

        $video_charging_record = db('video_charging_record')->where($where)->order('create_time desc')->find();

        return $video_charging_record;
    }

    /* 增加通话扣费记录 */
    public function add_video_charging_record($data)
    {

        $video_charging_record = db('video_charging_record')->insert($data);

        return $video_charging_record;
    }

}