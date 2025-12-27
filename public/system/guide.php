<?php


function detect_file_encoding($file_path)
{
    // 读取文件内容
    $contents = file_get_contents($file_path);

    // 检测编码格式
    $encoding = mb_detect_encoding($contents, mb_list_encodings(), true);

    return $encoding;
}

if (detect_file_encoding('index.php') == 'UTF-8') {
    echo '入口文件编码错误！';
    exit;
}

// 判断有没有 demo 这个模块
if (!IS_TEST && file_exists(APP_PATH . '/demo/')) {
    echo '请先删除demo模块';
    exit;
}

require_once DOCUMENT_ROOT . '/system/RedisPackage.php';

//加载前后台公共函数
require_once DOCUMENT_ROOT . '/system/common.php';
require_once DOCUMENT_ROOT . '/system/im_common.php';
require_once DOCUMENT_ROOT . '/system/im/BogoIM.php';