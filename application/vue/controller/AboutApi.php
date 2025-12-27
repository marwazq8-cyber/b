<?php

namespace app\vue\controller;

use think\Db;
use app\vue\model\HelpModel;

class  AboutApi extends Base
{

    protected function _initialize()
    {
        parent::_initialize();

        $this->HelpModel = new HelpModel();
    }

    //关于我们
    public function index()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));

        $config = load_cache('config');
        // 查询版本号
        $list = $this->HelpModel->get_about_me($uid);

        $data['system_log'] = $config['system_log'];
        $data['system_name'] = $config['system_name'];
        $data['version'] = $list ? $list['app_version'] : '';
        $data['copyright'] = 'Copyright©2018-2019';
        $data['web_url'] = 'http://www.baidu.com';
        $data['tel'] = '400-0000-0000';
        $result['data'] = $data;

        return_json_encode($result);
    }

}
