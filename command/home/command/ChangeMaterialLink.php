<?php

namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class ChangeMaterialLink extends Command
{
    protected function configure()
    {
        $this->setName('replaceLink')
            ->setDescription('山东布谷鸟网络科技[替换礼物表的链接]');
    }

    protected function execute(Input $input, Output $output)
    {

        $giftList = db('gift')->select();

        $ten_info = db('upload_set')->where('type = 1')->find();

        // 开始替换礼物表素材
        foreach ($giftList as &$gift) {
            // https://voice-chat-intl-sg-1305970982.cos.ap-singapore.myqcloud.com/admin/f424f3502cc383f77f256b27a3b93349.png
            $pos = strpos($gift['img'], $ten_info['url']);

            if ($pos !== false) {
                $imgUrl = $ten_info['url'] . '/gift/thumb/' . basename($gift['img']);
                $svgaUrl = $ten_info['url'] . '/gift/svga/' . basename($gift['svga']);

                db('gift')->where('id', $gift['id'])->update(['img' => $imgUrl, 'svga' => $svgaUrl]);
            }
        }
    }

}