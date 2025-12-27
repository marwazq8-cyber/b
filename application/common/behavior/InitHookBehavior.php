<?php

namespace app\common\behavior;

use system\RedisPackage;

class InitHookBehavior
{
    public function run(&$param)
    {
        // 初始化redis链接
        $GLOBALS['redis'] = new RedisPackage();

        // \think\Db::listen(function ($sql, $time, $explain) {
        //     // sql 为执行的SQL语句
        //     // time 为本次SQL执行的时间
        //     // explain 为可能存在的SQL分析，仅在查询SQL时有效
        //     if (IS_DEBUG) {
        //         // 如果开启了debug模式
        //         // 获取请求信息
        //         $info = \think\Request::instance();
        //         // 写入日志
        //         \think\Log::write('SQL: ' . $sql . ' [ RunTime:' . $time . 's ]');
        //     }
        // });
    }

}