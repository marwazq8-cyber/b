<?php

namespace app\voice\controller;

use app\api\controller\Base;

class SystemApi extends Base
{
    /**
     * 心跳接口
     * */
    public function heartbeat()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = input('id');
        $voice = db('voice')->where('user_id = ' . $id)->field('heat,online_count,charm_status')->find();
        if ($voice) {
            $voice['heat'] = round($voice['heat']);
            $voice['online_count'] = voice_userlist_sum($id);;
        } else {
            $voice['heat'] = 0;
            $voice['online_count'] = 0;
            $voice['charm_status'] = 1;
        }
        //查看用户是否在麦上
        //$wheat_logs = db('voice_even_wheat_log')->where("user_id=$uid and voice_id=" . $id . " and (status=1 or status =0)")->find();
        $result['data'] = $voice;
        return_json_encode($result);
    }
}