<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\agent\controller;

use think\Db;
use app\admin\model\AdminMenuModel;

class IndexController extends BaseController
{

    /*public function _initialize()
    {

        $adminSettings = cmf_get_option('admin_settings');
        if (empty($adminSettings['admin_password']) || $this->request->path() == $adminSettings['admin_password']) {
            $adminId = cmf_get_current_admin_id();
            if (empty($adminId)) {
                session("__LOGIN_BY_CMF_ADMIN_PW__", 1);//设置后台登录加密码
            }
        }

        parent::_initialize();
    }*/

    /**
     * 后台首页调用栏目导航
     */
    public function index()
    {

        $session_admin_id = session('AGENT_ID');
        $adminMenuModel = new AdminMenuModel();
        $menus = $adminMenuModel->menuTree();

        $this->assign("menus", $menus);

        $admin = Db::name("agent")->where('id', $session_admin_id)->find();
        $this->assign('admin', $admin);
        return $this->fetch();
    }

    /*
     * 后台首页
     * */
    public function statistical()
    {
        $id = session('AGENT_ID');

        $agent = Db::name('agent')->where("id=$id")->find();
        $admin_index_list = $this->admin_index_list($agent);

        $this->assign('admin_index_list', $admin_index_list);
        $this->assign('agent', $agent);
        return $this->fetch();
    }

    /**
     *  首页统计数据
     * @param $agent
     * @return mixed
     */
    function admin_index_list($agent)
    {
        //获取当天
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        //获取昨天
        $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        //支付
        $data['charge'] = $this->admin_index_charge($beginToday, $beginYesterday, $agent);
        //注册
        $data['registered'] = $this->admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday, $agent);

        //结算
        if ($agent['agent_level'] == 1) {
            $data['settlement'] = $this->agent_settlement($agent);
        }


        return $data;
    }

    //充值统计
    function admin_index_charge($beginToday, $beginYesterday, $agent)
    {

        $where1 = "a.date_time ='" . date("Y-m-d", $beginToday) . "'";
        $where2 = "a.date_time ='" . date("Y-m-d", $beginYesterday) . "'";

        $channel = " a.agent_id='" . $agent['id']."'";
        // 获取当前渠道充值金额
        //今天
        $data['day_log'] = $this->agent_charge($where1 . " and " . $channel);
        //昨天
        $data['Yesterday_log'] = $this->agent_charge($where2 . " and " . $channel);
        //所有的
        $data['total_log'] = $this->agent_charge($channel);


        // 下级渠道邀请注册人数
        $data['day_log_channel'] = 0;
        $data['Yesterday_log_channel'] = 0;
        $data['total_log_channel'] = 0;
        if ($agent['agent_level'] < 3) {
            if($agent['agent_level'] == 1){
                $where_channel = " g.agent_company = ".$agent['id']." and a.agent_id !=".$agent['id'];
            }else{
                $where_channel = " g.agent_staff = ".$agent['id']." and a.agent_id !=".$agent['id'];
            }
            //今天
            $data['day_log_channel'] = $this->agent_charge($where1 . " and " . $where_channel);
            //昨天
            $data['Yesterday_log_channel'] = $this->agent_charge($where2 . " and " . $where_channel);
            //总数
            $data['total_log_channel'] = $this->agent_charge($where_channel);
        }
        return $data;
    }

//代理注册统计
    function admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday, $agent)
    {
        $where1 = " a.addtime >=" . $beginToday . " and a.addtime <" . $endToday;
        $where2 = " a.addtime >=" . $beginYesterday . " and a.addtime <" . $endYesterday;
        //当前渠道邀请注册人数
        $where = " a.agent_id = ".$agent['id']." and a.status = 1";
        //今天
        $data['day_agent'] = $this->agent_registered($where1 . " and " . $where);
        //昨天
        $data['Yesterday_agent'] = $this->agent_registered($where2 . " and " . $where);
        //总数
        $data['total_agent'] = $this->agent_registered($where);

        // 下级渠道邀请注册人数
        $data['day_agent_channel'] = 0;
        $data['Yesterday_agent_channel'] = 0;
        $data['total_agent_channel'] = 0;
        $data['agent_registered_than_channel'] = 0;
        if ($agent['agent_level'] < 3) {
            if($agent['agent_level'] == 1){
                $where_channel = " g.agent_company = ".$agent['id']." and a.agent_id !=".$agent['id'];
            }else{
                $where_channel = " g.agent_staff = ".$agent['id']." and a.agent_id !=".$agent['id'];
            }
            //今天
            $data['day_agent_channel'] = $this->agent_registered($where1 . " and " . $where_channel);
            //昨天
            $data['Yesterday_agent_channel'] = $this->agent_registered($where2 . " and " . $where_channel);
            //总数
            $data['total_agent_channel'] = $this->agent_registered($where_channel);
        }
        return $data;
    }

    /*获取渠道支付金额
     * */
    function agent_charge($where)
    {
        return db("agent_order_log")->alias("a")
            ->join("agent g","g.id= a.agent_id")
            ->field("sum(a.money) as money")->where($where)->find();
    }

    /*
     * 获取代理注册统计
     * */
    function agent_registered($where)
    {
        return db('agent_register')
            ->alias("a")
            ->join("agent g","g.id= a.agent_id")
            ->where($where)->count();
    }

//提现
    function agent_settlement($agent)
    {

        $agent_id = "agent_id=" . $agent['id'];
        //提现审核中
        $data['day_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($agent_id . " and status =0")->find();

        //提现成功
        $data['Yesterday_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($agent_id . " and status =1")->find();
        //拒绝提现
        $data['total_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($agent_id." and status =2")->find();
        return $data;
    }
}
