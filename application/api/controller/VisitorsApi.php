<?php
/**
 * Created by PhpStorm.
 * User: YTH
 * Date: 2022/1/6
 * Time: 9:26 上午
 * Name:
 */


namespace app\api\controller;

use think\Db;
use app\vue\model\VisitorsModel;
use think\Model;
use think\Request;


class VisitorsApi extends Base
{
    protected $VisitorsModel;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->VisitorsModel = new VisitorsModel();
    }

    // 获取我的访客
    public function get_visitors_list()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 1我访客的 2访客我的
        $type = intval(input('param.type'));
        // 分页
        $page = intval(input('param.page')) <= 0 ? 1 : intval(input('param.page'));
        // 检查用户
        //$user_info = check_login_token($uid, $token);
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