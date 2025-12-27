<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/2
 * Time: 9:21
 */


define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

define('NOW_TIME', time());

define("SITE_URL", 'http://' . $_SERVER['HTTP_HOST']);

const VUE_URL = '/h5'; // vue地址目录

const OPEN_CUSTOM_VIDEO_CHARGE_COIN = 1;//是否开启自定义分钟扣费金额

const IP_REG_MAX_COUNT = 100; //每个IP只能注册的数量

const  OPEN_DEVICE_REG_SUM = 1;

const IS_TEST = 0; //是否是测试模式

const OPEN_INVITE = 0;//是否开启邀请模块

const OPEN_VIDEO_CHAT = 0;//是否开启视频聊

const OPEN_PAY_PAL = 0;//是否开启PayPal支付

const OPEN_SANDBOX = 1;//是否是沙盒环境

const OPEN_VOICE_CALL = 1;//开启音频通话

const OPEN_DEVICE_REG_LIMIT = 0;//开启设备注册限制

const OPEN_CUSTOM_AUTO_REPLY = 0;//是否开启自定义回复  （未完成功能，若干年后）

const OPEN_AUTO_SEE_HI_PLUGS = 0;//是否开启自动打招呼插件

const GAME_BOX_TYPE = 1;//宝箱类型 1概率 2奖池

const OPEN_ADMIN_MENU = 1;//后台菜单管理开关

const IS_DEBUG = 0;//是否开启debug

const IS_MOBILE = 1;//是否显示手机号

const MULTIPORT_ADMIN_LOGIN = 1;// 后台是否多端登录--是否检测token

const IS_TREE = 1;//是否开启浇树游戏

const IS_AGENT = 0;//是否开启渠道功能

const IS_GUILD = 1;//是否开启公会功能

const IS_OFFICIAL = 0;//是否开启商业版权和官方网站功能

const IS_PAYPALWEB = 1;//是否开启PAYPALWEB沙箱 1开启 0正式运营

const DATABASE_LANG = ['zh-cn', 'en', 'ar']; // 后台数据库语言包

const COIN_INT = 4294967295;//数据库余额存储最大值