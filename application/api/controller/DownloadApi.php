<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/14 0014
 * Time: 下午 14:47
 */
namespace app\api\controller;
use app\api\controller\Base;
use think\Controller;
use think\Db;
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技语聊系统海外版商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class DownloadApi extends Controller {
    //分享下载页面
    public function index(){

//        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
//            $download=db("config")->where("code='ios_download_url'")->find();
//        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
//            $download=db("config")->where("code='android_download_url'")->find();
//        }else{
//            $download=db("config")->where("code='android_download_url'")->find();
//        }
//        $this->assign('download',$download['val']);

        $config = load_cache('config');
        if (preg_match('/iPad|iPhone|iPod/', $_SERVER['HTTP_USER_AGENT'])) {
            $url = $config['ios_download_url'];
        } else{
            $url = $config['android_download_url'];
        }
        $this->assign('download_url',$url);
        $this->assign('share_bg',$config['share_bg']);
        $this->assign('download_log',$config['system_log']);
        $this->assign('system_name',$config['system_name']);
        return $this->fetch();
    }
    //直播间分享下载页面
    public function voice_duwnload(){
    	$uid = intval(input('param.invite_code'));
    	$id = intval(input('param.id'));
    	$config = load_cache('config');

    	$user = Db::name('user')->where(['id' => $uid])->find();

    	$user_info['name']=$user['user_nickname'];
    	$user_info['avatar']= $user['avatar'];
        $user_info['uid']=$user['luck'] ? $user['luck'] :$user['id'];
    	$user_info['id']=$id;

        if (preg_match('/iPad|iPhone|iPod/', $_SERVER['HTTP_USER_AGENT'])) {
            $url = $config['ios_download_url'];
        } else{
            $url = $config['android_download_url'];
        }
        $this->assign('download_url',$url);
        $this->assign('download_log',$config['system_log']);
	    $this->assign('user_info',$user_info);

        $this->assign('system_name',$config['system_name']);
        return $this->fetch();
    }
    // 音乐分享下载页面
    public function music_duwnload(){
        $id = intval(input('param.id')); // 发布音乐的id
        $config = load_cache('config');

        $user = db('voice_bank')->alias('v')
            ->join('user u', 'v.uid=u.id')
            ->where("v.id=".$id." and v.status=1")
            ->field('v.*,u.avatar')
            ->find();

        $user_info['title']=$user ? $user['title'] : '';
        $user_info['avatar']=$user ? $user['img'] ? $user['img'] : $user['avatar'] : '';
        $user_info['url']=$user ? $user['path'] : '';
        $user_info['id']=$id;


        $this->assign('user_info',$user_info);

        $this->assign('download_log',$config['system_log']);
        $this->assign('system_name',$config['system_name']);
        $this->assign('openinstall_key',$config['openinstall_key']);
        return $this->fetch();
    }

    public function pc_index(){
        $config = load_cache('config');

        //版本控制
        $android_dow = db('version_log')->where('type = 2 and is_release = 1')->order('create_time desc')->find();
        if($android_dow){
            $android_url = $android_dow['url'];

        }else{
            $android_url = $config['android_download_url'];
        }

        $ios_dow = db('version_log')->where('type = 1 and is_release = 1')->order('create_time desc')->find();
        if($ios_dow){
            //下载地址
            $ios_url = $ios_dow['url'];

        }else{
            //下载地址
            $ios_url = $config['ios_download_url'];
        }
        $this->assign('ios_url',$ios_url);
        $this->assign('android_url',$android_url);
        $this->assign('download_code',$config['download_code']);
        $this->assign('system_log',$config['system_log']);
        $this->assign('system_name',$config['system_name']);
        $this->assign('download_bg_url',$config['download_bg_url']);
        $this->assign('download_text',$config['download_text']);

        return $this->fetch();
    }

    public function phone_index(){
        $config = load_cache('config');
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if(strpos($agent, 'iphone')) {
            $platform = 1;
        }else {
            $platform = 2;
        }
        //版本控制
        $android_dow = db('version_log')->where('type = 2 and is_release = 1')->order('create_time desc')->find();
        if($android_dow){
            $android_url = $android_dow['url'];

        }else{
            $android_url = $config['android_download_url'];
        }

        $ios_dow = db('version_log')->where('type = 1 and is_release = 1')->order('create_time desc')->find();
        if($ios_dow){
            //下载地址
            $ios_url = $ios_dow['url'];

        }else{
            //下载地址
            $ios_url = $config['ios_download_url'];
        }
        $this->assign('ios_url',$ios_url);
        $this->assign('android_url',$android_url);
        $this->assign('download_code',$config['download_code']);
        $this->assign('platform',$platform);
        $this->assign('system_log',$config['system_log']);
        $this->assign('system_name',$config['system_name']);
        $this->assign('download_bg_url',$config['download_phone_bg_url']);
        $this->assign('download_text',$config['download_text']);
        return $this->fetch();
    }
}
