<?php

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\GuildModel;

class GuildApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        $this->GuildModel = new GuildModel();
    }

    // 获取公会规则说明
    public function rule_specification(){
        $result = array('code' => 1, 'msg' => '');
        $list = db("portal_category_post")->alias('a')
            ->where(" a.status=1 and b.post_type=1 and b.post_status=1 and a.category_id=38")
            ->join("portal_post b", "b.id=a.post_id")
            ->field("b.id,b.post_title,b.post_content")
            ->find();
        if ($list) {
            $list['post_content'] = html_entity_decode($list['post_content']);
        }
        $result['data'] = array(
            'title' => $list ? $list['post_title'] : '',
            'content' => $list ? $list['post_content'] : '',
        );
        return_json_encode($result);
    }
    // 获取所有的公会
    public function get_guild_list(){
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $page = intval(input('param.page')) ? intval(input('param.page')) : 0;
        $search = trim(input('param.search')) ? trim(input('param.search')) : '';

        $limit = $page * 20;
        $where = "status=1";
        $where .= $search ? " and (id like '%".$search."%' or name like '%".$search."%')" : "";
        $list =$this->GuildModel ->get_guild_list($where,$limit);

        $result['data'] = $list;
        return_json_encode($result);
    }
    // 申请加入公会
    public function add_guild_join(){
        $result = array('code' => 1, 'msg' => lang('Applied_join_labor_union_pending_approval'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $guild_id = intval(input('param.guild_id'));
        $tel = trim(input('param.tel'));
        $introduction = trim(input('param.introduction'));
        $agency_id = trim(input('param.agency_id'));

        $user_info = check_login_token($uid, $token);

//        if ($user_info['is_auth'] != 1) {
//            $result['code'] = 0;
//            $result['msg'] = lang('Unable_apply_for_guild_without_certification');
//            return_json_encode($result);
//        }

        //查询公会是否存在
        $guild = db('guild')->where('id=' . $guild_id)->find();// . ' and status=1'
        if (!$guild) {
            $result['code'] = 0;
            $result['msg'] = lang('Guild_information_does_not_exist');
            return_json_encode($result);
        }

        //判断是否是公会会长
        $self_guild = db('guild')->where('user_id=' . $uid)->find();
        if ($self_guild) {
            $result['code'] = 0;
            $result['msg'] = lang('Has_been_created_cannot_apply_other_guilds');
            return_json_encode($result);
        }
//        // 判断是否退出的公会 ---  申请退出工会需要会长审核，退出后24小时之后即可加入别的工会 强制退出工会后，1个月无法加入任何工会
//        $guild_join_quit = db('guild_join_quit')->where('user_id=' . $uid . ' and status !=2 ')->order("id desc")->find();
//        if ($guild_join_quit) {
//            $time = NOW_TIME;
//            if ($guild_join_quit['status'] == 3 && $guild_join_quit['end_time'] > $time - 30*24*60*60) {
//                // 强制退出的1月内禁止加入公会
//                $result['code'] = 0;
//                $endtime = $guild_join_quit['end_time'] + 30*24*60*60;
//                $result['msg'] = lang('Forced_withdrawal_from_guild_1_month'). date('Y-m-d H:i',$endtime);
//                return_json_encode($result);
//            }else if ($guild_join_quit['status'] == 1 && $guild_join_quit['end_time'] > $time - 24*60*60) {
//                // 强制退出的1月内禁止加入公会
//                $result['code'] = 0;
//                $endtime = $guild_join_quit['end_time'] + 24*60*60;
//                $result['msg'] = lang('Forced_withdrawal_from_guild_24_hours'). date('Y-m-d H:i',$endtime);
//                return_json_encode($result);
//            }else if ($guild_join_quit['status'] == 0) {
//                $result['code'] = 0;
//                $result['msg'] = lang('Cannot_add_in_guild_exit_approval');
//                return_json_encode($result);
//            }
//        }
        //查询是否加入了公会
        $join_record = db('guild_join')->where('user_id=' . $uid . ' and status!=2')->find();
        if ($join_record) {
            $result['code'] = 0;
            $result['msg'] = lang('You_have_joined_guild');
            return_json_encode($result);
        }

        $join_data = [
            'user_id' => $uid,
            'guild_id' => $guild_id,
            'create_time' => NOW_TIME,
            'tel' => $tel,
            'introduction' => $introduction,
            'inviter_user' => $agency_id,
            'status'=> 0
        ];

        db('guild_join')->insert($join_data);
        return_json_encode($result);
    }

}
?>