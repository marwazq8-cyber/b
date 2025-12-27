<?php

namespace app\common\exception;

use Exception;
use Monolog\Logger;
use think\App;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;

class Http extends Handle
{

    public function render(Exception $e)
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json($e->getError(), 422);
        }

        // 请求异常
        if ($e instanceof HttpException && request()->isAjax()) {
            return response($e->getMessage(), $e->getStatusCode());
        }

        if ($e instanceof LXApiException) {
            return json(['errorCode' => $e->getCode(), 'errorMsg' => $e->getMessage(), 'data' => null]);
        }

        // api 异常
        if ($e instanceof ApiException) {
            return json(['code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => null]);
        }

        $errorData = [
            'code' => $e->getCode(),
            'msg'  => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // 获取请求设备详细信息和IP信息+请求参数
        $errorData['device'] = request()->header('user-agent');
        $errorData['ip'] = request()->ip();
        // 拼接完整的请求url
        $errorData['url'] = request()->domain() . request()->url();
        $errorData['params'] = request()->param();
        // 参数根据URL形式拼装成字符串
        $errorData['params'] = http_build_query($errorData['params']);


        if (App::$debug) {
            $errorData['trace'] = $e->getTrace();
        }

        $logDataArray = array_merge($errorData, ['trace' => $e->getTrace()]);

        bogokjLogPrint('api_error', $logDataArray);

        $pushWXContent = [
            'msgtype'  => 'markdown',
            'markdown' => [
                'content' => "### 请求异常（海外-语聊） \n  > 请求URL：{$errorData['url']} \n  > 请求参数：{$errorData['params']} \n  > 请求设备：{$errorData['device']} \n  > 请求IP：{$errorData['ip']} \n  > 错误文件：{$errorData['file']} \n  > 错误行：{$errorData['line']} \n  > 错误信息：{$errorData['msg']} \n  > 错误代码：{$errorData['code']} \n  > 请求时间：" . date('Y-m-d H:i:s') . " \n
                > 请求 Curl 代码：``` " . $this->generateCurlCommand($errorData['url'], request()->param(), $errorData['device']) . " ``` \n"

            ]
        ];

        // 把上面的push内容作为json请求体提交到
        $pushWXUrl = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=bd82af6e-7147-4741-826b-72eade8bc1ba';
        // 使用guzzlehttp提交
        $client = new \GuzzleHttp\Client();
        $client->request('POST', $pushWXUrl, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body'    => json_encode($pushWXContent)
        ]);

        // 开发者对异常的操作
        return json($errorData);
        //可以在此交由系统处理
        //return parent::render($e);
    }

    function generateCurlCommand($url, $params, $userAgent)
    {
        $curlCommand = 'curl -X GET';

        // 添加 URL
        $curlCommand .= ' "' . $url . '"';

        // 添加参数
        if (!empty($params)) {
            $paramString = http_build_query($params);
            $curlCommand .= ' -d "' . $paramString . '"';
        }

        // 添加 user-agent
        $curlCommand .= ' -H "User-Agent: ' . $userAgent . '"';

        return $curlCommand;
    }
}