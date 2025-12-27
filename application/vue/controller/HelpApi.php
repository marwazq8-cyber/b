<?php

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\HelpModel;

class  HelpApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        $this->HelpModel = new HelpModel();
    }

    //使用帮助
    public function index()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 使用帮助类型
        $list = $this->HelpModel->get_help_type();

        $result['data'] = $list;

        return_json_encode($result);
    }

    //使用帮助
    public function center()
    {

        $result = array('code' => 1, 'msg' => '');

        $id = intval(input('param.id'));
        // 使用帮助类型
        $list = $this->HelpModel->get_help_center($id);

        $result['data'] = $list;

        return_json_encode($result);
    }
}

?>