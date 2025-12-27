<?php

namespace app\vue\controller;

use think\Db;
use app\vue\model\VisitorsModel;


class VisitorsApi extends Base
{
    protected $VisitorsModel;

    protected function _initialize()
    {
        parent::_initialize();

        $this->VisitorsModel = new VisitorsModel();
    }

    // 获取我的访客
    public function index()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 1我访客的 2访客我的
        $type = intval(input('param.type'));
        // 分页
        $page = intval(input('param.page')) <= 0 ? 1 : intval(input('param.page'));
        // 检查用户
        $user_info = check_login_token($uid, $token);
        // 查询条件
        if ($type == 1) {
            $where = "l.uid=" . $uid;
            // 查询
            $list = $this->VisitorsModel->get_user_visitors($where, $page);
        } else {
            $where = "l.touid=" . $uid;
            // 查询
            $list = $this->VisitorsModel->get_touser_visitors($where, $page);
        }
        if (count($list) > 0) {
            foreach ($list as &$v) {
                $v['addtime'] = date('Y-m-d H:i', $v['addtime']);
            }
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

}