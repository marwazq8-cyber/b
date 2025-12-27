<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2021-01-08
 * Time: 14:39
 */

namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class AdminPass extends Command
{
    protected function configure()
    {
        $this->setName('resetpass')->setDescription('山东布谷鸟网络科技[重置后台管理员密码]');
    }

    protected function execute(Input $input, Output $output)
    {
        $arr = range('a', 'z');
        shuffle($arr);
        $str = implode('', $arr);
        $pass = substr($str, 0, 6);
        $login = substr($str, 9, 5);
        $result = "###" . md5(md5('OvJIeCuO1AhIof5foR' . $pass));

        if (!db('user')->where('id = 1')->find()) {
            db('user')->insert(['id' => 1, 'user_type' => 1, 'user_login' => $login, 'user_pass' => $result]);
        } else {
            db('user')->where('id = 1')->update(['user_login' => $login, 'user_pass' => $result]);
        }

        $output->writeln("重置成功,用户名：" . $login . " 密码：" . $pass);
    }
}
