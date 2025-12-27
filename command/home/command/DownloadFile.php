<?php

namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class DownloadFile extends Command
{
    protected function configure()
    {
        $this->setName('downloadFile')
            ->setDescription('山东布谷鸟网络科技[下载礼物列表]');
    }

    protected function execute(Input $input, Output $output)
    {
        // 需要下载的文件
        $files = [];

        $giftList = db('gift')->select();

        foreach ($giftList as $gift) {
            $files[] = $gift['img'];
            $files[] = $gift['svga'];
        }

        $base_directory = './resource/gift/'; // 指定保存的目录

        if (!is_dir($base_directory)) {
            mkdir($base_directory, 0777, true); // 如果目录不存在，则创建目录
        }

        foreach ($files as $file_url) {

            $extension = pathinfo($file_url, PATHINFO_EXTENSION); // 获取文件扩展名

            $directory = '';

            if ($extension == 'svga') {
                $directory = $base_directory . $extension . '/'; // 根据扩展名创建目录
            } else if ($extension == 'png' || $extension == 'jpg' || $extension == 'jpeg') {
                $directory = $base_directory . 'thumb/'; // 根据扩展名创建目录
            }

            if (!is_dir($directory)) {
                mkdir($directory, 0777, true); // 如果目录不存在，则创建目录
            }

            $path = $directory . basename($file_url); // 保持原文件名
            if (file_exists($path)) {
                break;
            }

            $this->downloadFile($file_url, $path);
            $output->writeln("Downloaded: " . $file_url);
        }

        if (file_exists('./resource/gift')) {
            file_put_contents('./resource/gift/gift.json', json_encode($giftList, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");
        }

    }

    private function downloadFile($url, $saveTo)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 200 means that the file exists, 404 indicates that it does not.
        if ($retcode != 200) {
            echo("File does not exist");
            return;
        }

        curl_close($ch);

        $fp = fopen($saveTo, 'wb');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);

        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, [$this, 'progress']);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $output = curl_exec($ch);

        if ($output === false) {
            echo "Error Number:" . curl_errno($ch);
            echo "Error String:" . curl_error($ch);
        }

        curl_close($ch);
        fclose($fp);
    }

    private function progress($resource, $download_size, $downloaded, $upload_size, $uploaded)
    {
        if ($download_size > 0)
            echo intval($downloaded / $download_size * 100) . "%\n";
        else
            echo "Done\n";
    }
}