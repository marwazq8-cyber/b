<?php
// [ ThinkPHP ]

// تحميل Composer (إن وُجد)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// تحميل إطار ThinkPHP
require __DIR__ . '/../thinkphp/base.php';

// تشغيل التطبيق
\think\App::run()->send();
