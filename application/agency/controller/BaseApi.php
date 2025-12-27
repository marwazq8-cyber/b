<?php
/**
 * 布谷科技商业系统
 * 基类
 * @author 山东布谷鸟网络科技有限公司
 * @create 2020-08-04 23:54
 */


namespace app\agency\controller;
use think\Controller;
use think\App;

class BaseApi extends Controller
{

    protected $isLogin;

    public function __construct()
    {
        // 允许所有来源访问
        header('Access-Control-Allow-Origin: *'); #允许跨域
        header('Access-Control-Allow-Credentials: false'); #是否携带cookie
        header('Access-Control-Allow-Headers: *');#允许的header名称
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');#允许的请求方法
        header('Access-Control-Max-Age: 86400');#预检测请求的缓存时间另外浏览器控制面板的Disable cache不勾选才可
        if ($_SERVER['REQUEST_METHOD'] == "OPTIONS"){    exit; }
    }
}
