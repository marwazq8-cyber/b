<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24 0024
 * Time: 上午 10:01
 */

namespace app\api\controller;

use think\Db;
use UserOnlineStateRedis;
use \app\api\controller\Base;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class RecommendedApi extends Base
{
    //第一次进入app推荐主播，推荐为随机
    public function recommend_list()
    {
        $result = ['code' => 1, 'msg' => ''];

        $where = ['reference' => 1, 'sex' => 2];

        $user_list = db('user')
            ->where($where)
            ->field('user_nickname,avatar,id,sex')
            ->select();
        $result['list'] = $user_list;
        return_json_encode($result);
    }

    //一键关注
    public function follows()
    {
        $result = ['code' => 1, 'msg' => ''];
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $live_uid = trim(input('param.liveuid'));
        $live_uid_list = explode('&', $live_uid);

        $user_info = check_login_token($uid, $token);

        if (empty($live_uid)) {
            $result['code'] = 0;
            $result['msg'] = lang('Missing_required_parameter');
            return_json_encode($result);
        }

        $insert_data = [];
        foreach ($live_uid_list as $key => $val) {
            $insert_data[] = [
                'uid' => $uid,
                'attention_uid' => intval($val),
                'addtime' => NOW_TIME,
            ];
        }

        $res = db('user_attention')->insertAll($insert_data);
        $result['msg'] = '';
        return_json_encode($result);
    }
}