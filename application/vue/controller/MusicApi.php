<?php

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\MusicModel;

class MusicApi extends Base
{
     public function __construct(){
        // 允许所有来源访问
         parent::__construct();
        $this->MusicModel = new MusicModel();
    }

	// 获取音乐分类
	public function index(){

		$result = array('code' => 1, 'msg' => '');
		// 获取音乐分类列表
        $music = $this->MusicModel ->get_music_type_list("status=1");
       
        $result['data']=$music;

        return_json_encode($result);
	}
	// 获取分类下的音乐详情
	public function classify(){

		$result = array('code' => 1, 'msg' => '');

		$uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 分类id
        $music_type_id = intval(input('param.type_id'));

        $page = intval(input('param.page')) ? intval(input('param.page')) : 1;

        $where="t.id=".$music_type_id." and m.status=1";
        // 获取分类下的音乐详情
       	$music = $this->MusicModel ->get_music_classify_list($where,$page,$uid);

        $result['data']=$music;

        return_json_encode($result);
	}
    // 获取搜索
    public function music_search(){
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $search = trim(input('param.search'));
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1;

        $where= $search ? "title like '%".$search."%' or user_name like '%".$search."%'" :'';

        $music = $this->MusicModel ->get_music_search($where,$uid,$page);

        $result['data']=$music;
        return_json_encode($result);

    }
    // 用户下载
    public function music_download(){

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $url_md5 = trim(input('param.url_md5'));

        $where = "m.url_md5 ='".$url_md5."'";

        $music = $this->MusicModel ->get_music_download_one($where,$uid);

        if(!$music){
            $result = array('code' => 0, 'msg' => lang('Music_does_not_exist'));
            return_json_encode($result);
        }
        if(intval($music['dtype']) > 0 && $music['status'] == 1){
            $result = array('code' => 0, 'msg' => lang('User_downloaded'));
            return_json_encode($result);
        }
        if($music['status'] != 1){

            $where = "music_id=".$music['id']." and uid=".$uid;

            $this->MusicModel ->del_music_download($where);
        }

        $data=array(
            'uid' => $uid,
            'music_id' =>$music['id'],
            'status' =>1,
            'url_md5' =>$url_md5,
            'addtime'=>NOW_TIME
        );

        $status=$this->MusicModel ->add_music_download($data);
        if(!$status){

            $result = array('code' => 0, 'msg' => lang('Download_failed'));

            return_json_encode($result);
        }
        $result['data']['download_id'] = $status;
        return_json_encode($result);
    }

    // 获取伴奏库
    public function get_music_hot(){

        $result = array('code' => 1, 'msg' => '','data'=>[]);

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $search = trim(input('param.search'));

        $where1 = $search ? "m.user_name like '%".$search."%'" : '';
        $where2 = $search ? "m.title like '%".$search."%'" : '';
        // 排序 is_recommended推荐 sort后台排序  download下载次数
        $order="m.is_recommended desc,m.sort desc,m.download desc";
        $group="m.user_name";
        $limit=8;
        // 获取8个音乐歌手
        $data['music_name'] = $this->MusicModel ->get_music_hot($where1,$order,$group,$limit);
        // 获取热门列表
        $data['music_list'] = $this->MusicModel ->get_music_hot_list($where2,$order,15,$uid);

        $result['data'] =$data;
        
        return_json_encode($result);
    }
    // 获取歌手
    public function music_singer(){

        $result = array('code' => 1, 'msg' => '','data'=>[]);

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page')) > 0 ? intval(input('param.page')) : 1;
        // 排序 is_recommended推荐 sort后台排序  download下载次数
        $order="m.is_recommended desc,m.sort desc,m.download desc";
        $group="m.user_name";
        // 获取音乐歌手
        $music_list = $this->MusicModel ->get_music_singer('',$order,$group,$page);

        $result['data'] =$music_list;
        
        return_json_encode($result);
    }
    // 获取热门歌区
    public function music_song(){

        $result = array('code' => 1, 'msg' => '','data'=>[]);

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 歌手名称
        $name = trim(input('param.name'));
        $page = intval(input('param.page')) > 0 ? intval(input('param.page')) : 1;
        // 排序 is_recommended推荐 sort后台排序  download下载次数
        
        $where = $name ? "m.user_name ='".$name."'" : '';
        $order="m.is_recommended desc,m.sort desc,m.download desc";
        // 获取音乐歌手
        $music_list = $this->MusicModel ->get_music_song($where,$order,$page,$uid);

        $result['data'] =$music_list;
        
        return_json_encode($result);
    }
}
?>