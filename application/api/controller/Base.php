<?php

namespace app\api\controller;

use think\Controller;
use think\Request;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

class Base extends Controller
{
    protected $param_info;
    
    protected function _initialize()
    {
        parent::_initialize();

        $this->param_info = input('param.');
    }

    //判断传值是否是空
    public function empty_val($id)
    {
        if (empty($id)) {
            $result['code'] = "0";
            $result['msg'] = lang('Parameter_transfer_error');
            $result['data'] = $id;
            return_json_encode($result);
        }
    }

    public function get_param_info($input)
    {
        return isset($this->param_info[$input]) ? $this->param_info[$input] : 0;
    }

    protected function jsonResponse($code, $msg, $data)
    {
        // 返回 JSON 格式的响应
        return json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ]);
    }
}
