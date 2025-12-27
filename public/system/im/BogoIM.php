<?php

namespace im;

class BogoIM
{
    private $url = "http://127.0.0.1:5001";

    public function loginIM($uid, $token)
    {
        $data = [
            'uid'          => (string)$uid,
            'token'        => $token,
            'device_flag'  => 0,
            'device_level' => 1,
        ];

        $response = $this->http("/user/token", $data);
        bogokjLogPrint('WK-IM', $response);
        return $response;
    }

    /**
     * 创建频道
     * */
    public function createChannel($channel_id, $subscribers)
    {
//        {
//            "channel_id": "xxxx", // 频道的唯一ID，如果是群聊频道，建议使用群聊ID
//            "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道（个人与个人聊天不需要创建频道，系统将自动创建）
//            "large": 0,   // 是否是超大群，0.否 1.是 （一般建议500成员以上设置为超大群，注意：超大群不会维护最近会话数据。）
//            "ban": 0, // 是否封禁此频道，0.否 1.是 （被封后 任何人都不能发消息，包括创建者）
//            "subscribers": [uid1,uid2,...], // 订阅者集合
//        }
        $data = [
            'channel_id'   => (string)$channel_id,
            'channel_type' => 2,
            'large'        => 1,
            'ban'          => 0,
            'subscribers'  => [(string)$subscribers],
        ];

        $response = $this->http("/channel", $data);
        bogokjLogPrint('WK-IM', $response);
        return $response;
    }

    /**
     * 添加频道订阅者
     * */
    public function addChannelSubscribers($channel_id, $subscribers)
    {

//        {
//            "channel_id": "xxxx", // 频道的唯一ID
//            "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
//            "reset": 0,        // // 是否重置订阅者 （0.不重置 1.重置），选择重置，则删除旧的订阅者，选择不重置则保留旧的订阅者
//            "subscribers": [uid1,uid2,...], // 订阅者集合
//            "temp_subscriber": 0 // 是否为临时频道 0.否 1.是 临时频道的订阅者将在下次重启后自动删除
//        }

        $data = [
            'channel_id'      => (string)$channel_id,
            'channel_type'    => 2,
            'reset'           => 0,
            'subscribers'     => [(string)$subscribers],
            'temp_subscriber' => 0,
        ];

        $response = $this->http("/channel/subscriber_add", $data);
        bogokjLogPrint('WK-IM', $response);
        return $response;
    }

    /**
     * 移除订阅者
     * */
    public function removeChannelSubscribers($channel_id, $subscribers)
    {

//        {
//            "channel_id": "xxxx", // 频道的唯一ID
//            "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
//            "subscribers": [uid1,uid2,...], // 订阅者集合
//        }


        $data = [
            'channel_id'   => (string)$channel_id,
            'channel_type' => 2,
            'subscribers'  => [(string)$subscribers],
        ];

        $response = $this->http("/channel/subscriber_remove", $data);
        bogokjLogPrint('WK-IM', $response);
        return $response;
    }

    private function http($path, $data)
    {
        // 初始化 cURL 会话
        $ch = curl_init();

        // 设置 URL 和 JSON 数据
        $url = $this->url . $path; // 替换为实际的 API 地址
        $jsonData = json_encode($data);

        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行 cURL 请求并获取响应
        $response = curl_exec($ch);
        // 关闭 cURL 会话
        curl_close($ch);

        $data_val = json_decode($response, true);
        return $data_val;
    }

}