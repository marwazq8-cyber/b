<?php

namespace app\common;
class Enum
{
    // im 类型消息
    const CLOSE_ROOM = 27; // 关闭房间
    const LOWER_WHEAT = 61; // 麦位下麦
    const EXIT_ROOM = 6; // 退出房间消息
    const GLOBAL_GIFT = 90;//发送全局礼物消息
    const BROADCAST = 777;//发送广播消息
    const REGULAR_GIFT = 1; // 普通礼物
    const AGREE_WHEAT = 57; // 同意上麦
    const LUCKY_BAG = 205;// 福袋消息
    const ONE_ORDERS = 999;// 一对一订单消息
    const LIVE_NOTIFICATION_FANS = 222;//开直播通知粉丝
    const WHEAT_CHANGE = 745;//上下麦变动
    const SAVE_ROOM = 47;//修改房间详情信息
    const PAY_CALL_SERVICE = 778;// 支付回调通知
    const DISPATCH_MESSAGE = 122;// 派单消息
    const CHARM_VALUE = 77;//管理员重置关闭统计魅力值
    const CLOSE_VIDEO_CALL = 25;// 关闭视频通话
    const CLOSE_VOICE_CALL = 94; //关闭语音通话
    const CLOSE_VIDEO2_CALL = 96;// 关闭视频通话
    const CLOSE_LIVE = 7;// 直播结束
    const KICK_OUT_LIKE = 63;// 语音踢出

    const LUCKY_FIRST_PRIZE = 83; // 幸运头奖
}