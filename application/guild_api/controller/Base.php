<?php
namespace app\guild_api\controller;

use think\Controller;
use think\Db;
use think\config;

class Base extends Controller
{
    public function __construct()
    {
        // 允许所有来源访问
        header('Access-Control-Allow-Origin: *'); #允许跨域
        header('Access-Control-Allow-Credentials: false'); #是否携带cookie
        header('Access-Control-Allow-Headers: *');#允许的header名称
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');#允许的请求方法
        header('Access-Control-Max-Age: 86400');#预检测请求的缓存时间另外浏览器控制面板的Disable cache不勾选才可
    }

    public function check_token($uid,$token){
        $result = array('code' => 50008, 'msg' => lang('login_timeout'));
        $guild = db('guild')->where('id = '.$uid.' and token = "'.$token.'"')->find();

        if(!$guild){
            $guild = db('guild_admin')->where('id = '.$uid.' and token = "'.$token.'"')->find();
            if($guild){
                $guild_info = db('guild')->where('id = "'.$guild['guild_id'].'"')->find();
                $guild['name'] = $guild_info['name'];
                $guild['avatar'] = $guild_info['logo'];
                $guild['login'] = $guild_info['login'];
                $guild['user_id'] = $guild_info['user_id'];
                //$guild['login'] = $guild_info['login'];
                $guild['create_time'] = $guild_info['create_time'];
                $guild['cash_account'] = $guild_info['cash_account'];
                $guild['account_name'] = $guild_info['account_name'];
                $guild['introduce'] = $guild_info['introduce'];
                $guild['notice'] = $guild_info['notice'];
                $guild['is_admin'] = 0;
            }
        }else{
            $guild['guild_id'] = $guild['id'];
            $guild['is_admin'] = 1;
        }

        if($guild){
            return $guild;
        }else{
            return_json_encode($result);
        }
    }

    public function getMicroTime()
    {
        $micro_time = microtime(true);
        $micro_time_arr = explode('.',$micro_time);
        return implode('',$micro_time_arr);
    }

}