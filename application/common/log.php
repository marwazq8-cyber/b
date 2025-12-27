<?php


// 创建日志实例
use app\common\log\HourlyRotatingFileHandler;
use Monolog\Logger;

// 打印日志
function bogokjLogPrint($fileName, $logContent, $dir = 'printLog')
{

    if (is_array($logContent)) {
        $logContent = json_encode($logContent);
    }

    // 打印到runtime/log/目录下，根据日期生成日志文件
    $logPath = RUNTIME_PATH . "$dir/" . date('Y-m-d') . '-log/';

    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }
    $logFile = $logPath . $fileName . '-' . date('H') . '.log';
    $logContent = date('Y-m-d H:i:s') . '-log' . "\n" . $logContent . "\n";

    // 添加一个换行
    $logContent .= "\n";
    // 获取URL请求参数和设别参数添加进日志
    $logContent .= "[REQUEST]:" . request()->controller() . '/' . request()->action() . "\n";
    $logContent .= "[URL_PARAM]:" . json_encode(request()->param()) . "\n";
    $logContent .= "[CONTENT]:" . json_encode(request()->getContent()) . "\n";
    $logContent .= "[DEVICE_INFO]:" . json_encode(request()->header()) . "\n";

    // 打印内容用分割线分割并带上日期区分
    $logContent = "----------------------------------------\n" . $logContent;

    file_put_contents($logFile, $logContent, FILE_APPEND);

}

/**
 * 获取日志实例
 * */
function createRotatingLogger($name, string $fileName = '')
{
    // 创建日志实例
    $log = new Logger($name);

    // 指定日志存储路径和文件名
    $logPath = RUNTIME_PATH . '/logger/';

    if (empty($fileName)) {
        $logFile = $logPath . 'app.log';
    } else {
        $logFile = $logPath . $fileName . '.log';
    }

    $handler = new HourlyRotatingFileHandler($logFile);

    // 添加处理器，将日志按小时分割
    $log->pushHandler($handler);
    return $log;
}
