<?php

namespace app\vue\controller;

use think\Controller;
use think\Db;
use think\config;

class Base extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();

        // 允许所有来源访问
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers: *');#允许的header名称
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');#允许的请求方法

    }

}