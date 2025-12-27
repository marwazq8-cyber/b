<?php

namespace app\common\exception;

use think\Exception;

class ApiException extends \RuntimeException
{
    public $code = 0;  // 默认错误码

    public function __construct($msg = '', $code = 0, $errorCode = 0)
    {
        if (!empty($msg)) {
            $this->message = $msg;
        }
        if (!empty($code)) {
            $this->code = $code;
        }
    }
}