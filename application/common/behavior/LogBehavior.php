<?php

namespace app\common\behavior;

use app\common\model\OperationLogModel;
use think\Request;
use think\Response;

class LogBehavior
{
    public function run(&$param)
    {
        // 记录日志
        // 判断只有后台管理模块下的操作才记录日志
        if (Request::instance()->module() == 'admin' || Request::instance()->module() == 'user') {

            // 判断 URL 中包含 /user/public/avatar/id 不记录日志
            if (strpos(Request::instance()->url(), '/user/public/avatar/id') !== false) {
                return;
            }

            // 记录日志
            $this->logOperation();
        }
        //$this->logOperation(); /user/public/avatar/id
    }

    private function logOperation()
    {
        $request = Request::instance();

        // 获取用户信息（假设用户已登录）
        // 获取请求信息
        $action = $request->action();
        $requestMethod = $request->method();
        $requestUrl = $request->url();
        $requestData = json_encode($request->param());
        $request_head = $request->header();

        $description = '';

        // 获取响应信息
        //$responseStatus = $response->getCode();
        //$responseData = $response->getContent();

        // 获取IP地址
        $ip = $request->ip();

        // 记录日志
        OperationLogModel::create([
            'action' => $action,
            'description' => $description,
            'request_method' => $requestMethod,
            'request_url' => $requestUrl,
            'request_head' => json_encode($request_head),
            'request_data' => $requestData,
            //'response_status' => $responseStatus,
            //'response_data' => $responseData,
            'ip' => $ip,
            'create_time' => time()
        ]);
    }
}