<?php

namespace app\vue\controller;
class RankingApi extends Base
{
    /**
     * 排行榜--房间榜
     */
    public function room_ranking()
    {
        //查询语音房间
        $result = array('code' => 1, 'msg' => '');
        $type = intval(input('param.type')) == 0 ? 0 : 1;     //0 日榜 1周榜
        $id = intval(input('param.room_id'));    //房主id --- 如果空，就是查询所有的
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $userinfo = check_login_token($uid, $token, ['token']);

        if ($type == 1) {
            $sdefaultDate = date("Y-m-d");
            $first = 1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
            $w = date('w', strtotime($sdefaultDate)); //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
            $startime = strtotime(date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days'))); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        } else {
            $startime = strtotime(date('Y-m-d', NOW_TIME));
        }
        $config = load_cache('config');
        $profit = $config['rank_coin_type'] == 1 ? 'l.profit' : 'l.gift_coin';
        $where = "l.type=4 ";
        $where .= $id ? " and l.room_id =" . $id : '';
        $where .= $startime ? " and l.create_time >=" . $startime : '';
        $list = db('user')->alias('a')
            ->join('user_gift_log l', 'l.user_id=a.id')
            ->field("a.user_nickname,a.avatar,a.sex,a.age,a.level,sum($profit) as total_diamonds,a.id")
            ->where($where)
            ->group("a.id")
            ->order("total_diamonds desc")
            ->limit(0, 50)
            ->select();
        $user_info = array(
            'id' => $uid,
            'user_nickname' => $userinfo['user_nickname'],
            'avatar' => $userinfo['avatar'],
            'order_num' => lang('Not_on_the_list')
        );
        $i = 1;
        foreach ($list as &$v) {
            // 财富等级
            $user_level = get_level($v['id'], 2);
            $v['level_img'] = $user_level['level_icon'];
            // 收益等级
            $income_level = get_income_level($v['id'], 2);
            $v['income_level_img'] = $income_level['level_icon'];
            //用户的排行
            if ($v['id'] == $uid) {
                $user_info['order_num'] = "NO." . $i;
            }
            $v['order_num'] = "NO." . $i;
            $i++;
        }
        $result['data'] = array(
            'list' => $list,
            'user' => $user_info
        );
        return_json_encode($result);
    }
}
