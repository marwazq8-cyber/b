<?php

namespace app\api\controller;
require_once DOCUMENT_ROOT . '/system/im_common.php';
use think\helper\Time;
use UserOnlineStateRedis;

class MusicApi
{
    
	public function index(){
    	$result = array('code' => 1, 'msg' => '');
        $page = intval(input('param.page')) ? intval(input('param.page')) :0;
        $config = load_cache('config');
        if($config['is_music_type'] ==1){//网易云音乐接口列表
              //网易云音乐
            $url = "https://v1.itooi.cn/netease/songList/hot";
            $params = array();
            $params['cat'] = lang('ALL');
            $params['pageSize'] =10;
            $params['page'] = $page;

            $req = $this->curl_https($url,$params,['application/x-www-form-urlencoded']);

            $req = json_decode($req,1);
          
            $list = array();
              
            if($req['code']==200){
             $data = $req['data'];

              foreach ( $data as $k => $v ){
                    $song = array();
                    $song['id'] = $v['id'];  //音乐id
                    $song['title'] = $v['name'];   //音乐名称
                    $song['user_name'] = $v['creator']['nickname'];  //歌手
                    $song['img'] = $v['creator']['avatarUrl'];   //图片
                    $song['url'] ="https://v1.itooi.cn/netease/url?id=".$v['id']."&quality=flac"; //音乐下载播放地址
                    $list[] = $song;
                }
            }
        }else{    //本地服务器音乐列表
            $where="status=1";
            //查询语音房间音乐列表
            $list = db('music')
            ->where($where)
            ->order("is_recommended desc,sort desc")
            ->page($page)
            ->select();
        }
       

        $result['list']=$list;
        return_json_encode($result);
	}
    //搜索音乐
    public function search(){
        $result = array('code' => 1, 'msg' => '');
        $name = input('param.name');

        $config = load_cache('config');
        if($config['is_music_type'] ==1){//网易云音乐接口列表
            $url = "https://v1.itooi.cn/netease/search";
            $params = array();
            $params['keyword'] = $name;
            $params['pageSize'] = 30;
            $params['type'] = 'song';
            $params['page'] = 0;
            $req = $this->curl_https($url,$params,['application/x-www-form-urlencoded']);

            $req = json_decode($req,1);
          
            $list = array();
              
            if($req['code']==200){
             $data = $req['data']['songs'];
              foreach ( $data as $k => $v ){
                    $song = array();
                    $song['id'] = $v['id'];                    //音乐id
                    $song['title'] = $v['name'];               //音乐名称
                    $song['user_name'] =$v['ar'][0]['name'];   //歌手
                    $song['img'] =  $v['al']['picUrl'];      //图片
                    $song['url'] ="https://v1.itooi.cn/netease/url?id=".$v['id']."&quality=flac"; //音乐下载播放地址
                    $list[] = $song;
                }
            }
        }else{   //本地服务器
            $where='status=1';
            $where.=$name ? ' and (title like "%'.$name.'%" or user_name like "%'.$name.'%")':'';
            //查询语音房间音乐列表
            $list = db('music')
            ->where($where)
            ->order("is_recommended desc,sort desc")
            ->select();
        }
    
        $result['list']=$list;
        return_json_encode($result);
    }
  

  /**
     * https 方法
     */
    public function curl_https($url, $data=array(), $header=array(), $timeout=30){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);

        if($error=curl_error($ch)){
            die($error);
        }
        curl_close($ch);

        return $response;

    }

}
?>