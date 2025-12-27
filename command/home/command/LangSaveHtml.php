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

class LangSaveHtml extends Command
{
    protected function configure()
    {
        $this->setName('langHtml')
            ->addArgument('name', Argument::OPTIONAL, "键名")
            ->setDescription('山东布谷鸟网络科技[提取html语言包]');
    }
    protected function traverse($path){
        // 使用 scandir() 函数获取指定目录下的所有文件和子目录
        $files = [];
        $currentDir = opendir($path);
        //opendir()返回一个目录句柄,失败返回false
        while(($file = readdir($currentDir)) !== false) {
            //readdir()返回打开目录句柄中的一个条目
            $subDir = $path . DIRECTORY_SEPARATOR . $file;
            //构建子目录路径
            if($file == '.' || $file == '..') {
                continue;
            } else if(is_dir($subDir)) {
                //如果是目录,进行递归
                $files_list = $this->traverse($subDir);
                foreach ($files_list as $vs){
                    $files[] = $vs;
                }
            } else {
                //如果是文件,调用clasbackFun方法(参数：文件路径，文件名)
                $files[] = $subDir;
            }
        }
        return $files;
    }
    protected function execute(Input $input, Output $output)
    {
        try {
            //
            // 获取语言包信息
           /* $lang_dir = DOCUMENT_ROOT."/application/admin/lang/extract.php";
            $lang_files = require_once $lang_dir;
            $dir = DOCUMENT_ROOT."/public/themes/admin_simpleboot3/admin";

            $file_list = $this->traverse($dir);

            foreach($file_list as $files) {
                $content  = file_get_contents($files);
                $output->writeln($files);
                // 正则匹配引号开头第一个是中文的字符串 提取
                preg_match_all('/"([一-龥].*?)"/', $content, $matches);
                $output->writeln(json_encode($matches[1]));
                if (count($matches) && count($matches[1])){
                    foreach ($matches[1] as $v){
                        $output->writeln("files--》key:".$v);
                        $lang_files[$v]=$v;
                    }
                }else{
                    preg_match_all("/'([一-龥].*?)'/", $content, $matches2);
                    $output->writeln(json_encode($matches2[1]));
                    if (count($matches2) && count($matches2[1])){
                        foreach ($matches2[1] as $vv){
                            $lang_files[$vv]=$vv;
                        }
                    }
                }
                $output->writeln("files========结束");
            }
            chmod($lang_dir, 0777);
            // 保存时存入文件夹
            $lang= '<?php
                return '.var_export($lang_files,true).';';
            if (!file_exists($lang_dir) ) {
                mkdir ($lang_dir, 0744);
            }
            file_put_contents($lang_dir,$lang);

            $lang_dir = DOCUMENT_ROOT."/application/user/lang/extract.php";
            $lang_files = require_once $lang_dir;
            $dir = DOCUMENT_ROOT."/public/themes/admin_simpleboot3/user";

            $file_list = $this->traverse($dir);

            foreach($file_list as $files) {
                $content  = file_get_contents($files);
                $output->writeln($files);
                // 正则匹配引号开头第一个是中文的字符串 提取
                preg_match_all('/"([一-龥].*?)"/', $content, $matches);
                $output->writeln(json_encode($matches[1]));
                if (count($matches) && count($matches[1])){
                    foreach ($matches[1] as $v){
                        $output->writeln("files--》key:".$v);
                        $lang_files[$v]=$v;
                    }
                }else{
                    preg_match_all("/'([一-龥].*?)'/", $content, $matches2);
                    $output->writeln(json_encode($matches2[1]));
                    if (count($matches2) && count($matches2[1])){
                        foreach ($matches2[1] as $vv){
                            $lang_files[$vv]=$vv;
                        }
                    }
                }
                $output->writeln("files========结束");
            }
            chmod($lang_dir, 0777);
            // 保存时存入文件夹
            $lang= '<?php
                return '.var_export($lang_files,true).';';
            if (!file_exists($lang_dir) ) {
                mkdir ($lang_dir, 0744);
            }
            file_put_contents($lang_dir,$lang);*/
          /*  $lang_dir = DOCUMENT_ROOT."/application/admin/lang/extract.php";
            $lang_files = require_once $lang_dir;
            $dir = DOCUMENT_ROOT."/public/themes/admin_simpleboot3/admin";

            $file_list = $this->traverse($dir);

            foreach($file_list as $files) {
                $content  = file_get_contents($files);
                $output->writeln($files);
                // 正则匹配引号开头第一个是中文的字符串 提取
                preg_match_all("/'([一-龥].*?)'/", $content, $matches);
                $output->writeln(json_encode($matches[1]));
                if (count($matches) && count($matches[1])){
                    foreach ($matches[1] as $v){
                        $output->writeln("files--》key:".$v);
                        $lang_files[$v]=$v;
                    }
                }else{
                    preg_match_all("/'([一-龥].*?)'/", $content, $matches2);
                    $output->writeln(json_encode($matches2[1]));
                    if (count($matches2) && count($matches2[1])){
                        foreach ($matches2[1] as $vv){
                            $lang_files[$vv]=$vv;
                        }
                    }
                }
                $output->writeln("files========结束");
            }
            chmod($lang_dir, 0777);
            // 保存时存入文件夹
            $lang= '<?php
                return '.var_export($lang_files,true).';';
            if (!file_exists($lang_dir) ) {
                mkdir ($lang_dir, 0744);
            }
            file_put_contents($lang_dir,$lang);

            $lang_dir = DOCUMENT_ROOT."/application/user/lang/extract.php";
            $lang_files = require_once $lang_dir;
            $dir = DOCUMENT_ROOT."/public/themes/admin_simpleboot3/user";

            $file_list = $this->traverse($dir);

            foreach($file_list as $files) {
                $content  = file_get_contents($files);
                $output->writeln($files);
                // 正则匹配引号开头第一个是中文的字符串 提取
                preg_match_all("/'([一-龥].*?)'/", $content, $matches);
                $output->writeln(json_encode($matches[1]));
                if (count($matches) && count($matches[1])){
                    foreach ($matches[1] as $v){
                        $output->writeln("files--》key:".$v);
                        $lang_files[$v]=$v;
                    }
                }else{
                    preg_match_all("/'([一-龥].*?)'/", $content, $matches2);
                    $output->writeln(json_encode($matches2[1]));
                    if (count($matches2) && count($matches2[1])){
                        foreach ($matches2[1] as $vv){
                            $lang_files[$vv]=$vv;
                        }
                    }
                }
                $output->writeln("files========结束");
            }
            chmod($lang_dir, 0777);
            // 保存时存入文件夹
            $lang= '<?php
                return '.var_export($lang_files,true).';';
            if (!file_exists($lang_dir) ) {
                mkdir ($lang_dir, 0744);
            }
            file_put_contents($lang_dir,$lang);*/
            $lang_dir = DOCUMENT_ROOT."/application/admin/lang/extract.php";
            $lang_files = require_once $lang_dir;
            $dir = DOCUMENT_ROOT."/public/themes/admin_simpleboot3/admin";

            $file_list = $this->traverse($dir);

            foreach($file_list as $files) {
                $content  = file_get_contents($files);
                $output->writeln($files);
                // 正则匹配引号开头第一个是中文的字符串 提取
                preg_match_all('/> ([一-龥].*?) </', $content, $matches);
                $output->writeln(json_encode($matches[1]));
                if (count($matches) && count($matches[1])){
                    foreach ($matches[1] as $v){
                        $output->writeln("files--》key:".$v);
                        $lang_files[$v]=$v;
                    }
                }
                $output->writeln("files========结束");
            }
            chmod($lang_dir, 0777);
            // 保存时存入文件夹
            $lang= '<?php
                return '.var_export($lang_files,true).';';
            if (!file_exists($lang_dir) ) {
                mkdir ($lang_dir, 0744);
            }
            file_put_contents($lang_dir,$lang);

            $lang_dir = DOCUMENT_ROOT."/application/user/lang/extract.php";
            $lang_files = require_once $lang_dir;
            $dir = DOCUMENT_ROOT."/public/themes/admin_simpleboot3/user";

            $file_list = $this->traverse($dir);

            foreach($file_list as $files) {
                $content  = file_get_contents($files);
                $output->writeln($files);
                // 正则匹配引号开头第一个是中文的字符串 提取
                preg_match_all('/> ([一-龥].*?) </', $content, $matches);
                $output->writeln(json_encode($matches[1]));
                if (count($matches) && count($matches[1])){
                    foreach ($matches[1] as $v){
                        $output->writeln("files--》key:".$v);
                        $lang_files[$v]=$v;
                    }
                }
                $output->writeln("files========结束");
            }
            chmod($lang_dir, 0777);
            // 保存时存入文件夹
            $lang= '<?php
                return '.var_export($lang_files,true).';';
            if (!file_exists($lang_dir) ) {
                mkdir ($lang_dir, 0744);
            }
            file_put_contents($lang_dir,$lang);
            $output->writeln("提取语言包成功");
        } catch(\think\exception\ErrorException $e) {

            $output->writeln("错误发生，" . $e->getMessage().";行数：".$e->getLine());

        } catch (BindParamException $e) {
            $output->writeln("提取语言包失败，" . $e->getMessage());
        } catch (PDOException $e) {
            $output->writeln("提取语言包成功，" . $e->getMessage());
        }

    }
}
