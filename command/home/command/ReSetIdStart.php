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
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\db\exception\BindParamException;
use think\exception\PDOException;

class ReSetIdStart extends Command
{
    protected function configure()
    {
        $this->setName('resetid')
            ->addArgument('id', Argument::OPTIONAL, "起始ID号")
            ->setDescription('山东布谷鸟网络科技[重置ID起始数]');
    }

    protected function execute(Input $input, Output $output)
    {
        //获取参数值
        $args = $input->getArguments();

        try {
            db()->execute("ALTER TABLE bogo_user AUTO_INCREMENT = {$args['id']}");

            $output->writeln("重置起始ID成功，从{$args['id']}开始");

        } catch (BindParamException $e) {
            $output->writeln("重置起始ID失败，" . $e->getMessage());

        } catch (PDOException $e) {
            $output->writeln("重置起始ID成功，" . $e->getMessage());

        }

    }
}
