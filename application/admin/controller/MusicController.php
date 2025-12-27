<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use cmf\lib\Upload;

class MusicController extends AdminBaseController
{
    // 音乐分类
    public function music_type(){
        /**搜索条件**/
        $music = Db::name('music_type')->select();

        $this->assign("list", $music);
        return $this->fetch();
    }
    //添加音乐分类
    public function add_music_type(){
        $id=$this->request->param('id');
        if($id){
            $list = Db::name('music_type')->where("id=$id")->find();
        }else{
            $list['status']=1;
        }

        $this->assign("list", $list);
        return $this->fetch();
    }
    // 添加数据
    public function addtype_Post(){
        $param=$this->request->param();
        $data=$param['post'];

        if(empty($data['name'])){
            $this->error(lang('Please_enter_music_category_name'));
        }

        $data['addtime']=time();

        if($param['id']){
            $result=Db::name('music_type')->where("id=".$param['id'])->update($data);
        }else{
            $result=Db::name('music_type')->insert($data);
        }
        if($result){
            $this->success(lang('Operation_successful'));
        }else{
            $this->error(lang('operation_failed'));
        }

    }
    // 删除音乐分类
    public function del_music_type(){
        $id=$this->request->param("id");
        if($id){
            $result=Db::name('music_type')->where("id=".$id)->delete();
            if($result){
                echo 1;exit;
            }
        }
        echo 0;exit;
    }
    //获取音乐列表
    public function index(){
        $where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('music_type') and !$this->request->param('title') and !$this->request->param('user_name') and !$this->request->param('status') and $this->request->param('status') < '0' and !$this->request->param('is_recommended')) {
            session("admin_music", null);
            $data['status'] = '-1';
            $data['music_type'] = '0';
            $data['is_recommended'] = '-1';
            session("admin_music", $data);

        } else if (empty($p)) {
            $data['music_type'] = $this->request->param('music_type');
            $data['title'] = $this->request->param('title');
            $data['user_name'] = $this->request->param('user_name');
            $data['status'] = $this->request->param('status');
            $data['is_recommended'] = $this->request->param('is_recommended');

            session("admin_music", $data);
        }

        $music_type = session("admin_music.music_type");
        $title = session("admin_music.title");
        $user_name = session("admin_music.user_name");
        $status = session("admin_music.status");
        $is_recommended = session("admin_music.is_recommended");

        $where ='m.id>0';
        $where .=$title ?  " and m.title like '%$title%'":'';
        $where .=$music_type ?  " and m.music_type =".$music_type:'';
        $where .=$user_name ?  " and m.user_name ='".$user_name."'":'';
        $where .=$status >= '0' ?  " and m.status =".$status:'';
        $where .=$is_recommended >= '0' ?  " and m.is_recommended =".$is_recommended:'';

        $music_type_list = Db::name('music_type')->select();

        $music = Db::name('music')->alias("m")
            ->where($where)
            ->field("m.*,t.name")
            ->join("music_type t", "t.id=m.music_type")
            ->order("m.sort DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $music->render();
        $name = $music->toArray();

        $this->assign("page", $page);
        $this->assign("list", $name['data']);
        $this->assign("music_type_list", $music_type_list);
        $this->assign("data", session("admin_music") );
        return $this->fetch();
    }

    //增加音乐
    public function add(){
        $id=$this->request->param('id');

        $music_type_list = Db::name('music_type')->where("status=1")->select();

        if($id){
            $list = Db::name('music')->where("id=$id")->find();
        }else{
            $list['status']=1;
            $list['is_recommended']=1;
            $list['music_type']=0;
        }

        $this->assign("music_type_list", $music_type_list);
        $this->assign("list", $list);
        return $this->fetch();
    }
    //音乐入库
    public function addPost(){
        //dump(request()->file('file'));die();
        $param=$this->request->param();
        //dump($param);die();
        $data=$param['post'];

        if(empty($data['img'])){
            $this->error(lang('Please_upload_pictures'));
        }

//         if(empty($_FILES["file"])){
//              $data['url']=$param['file_old'];
//         }else{

//       //      vendor('getid3.getid3.getid3');
//             // 实例化类
//      //       $getID3 = new \getid3();
//             // 分析文件
//       //      var_dump($_FILES);
//     //        $ThisFileInfo = $getID3->analyze($_FILES["file"]['tmp_name']);
//         //    var_dump($ThisFileInfo);exit;
//             // 获取mp3的长度信息
//     //        $data['music_time'] = $ThisFileInfo['playtime_seconds'];
//             // 获取文件夹大小
//   //         $data['music_size'] = $_FILES["file"]['size'];
//             // 获取上传的路径地址
//             $file = request()->file('file');//$_FILES["file"]
//       //     $data['url']=oss_upload($file);

//               $uploader = new Upload();

//             $result = $uploader->upload();

//               var_dump($uploader->getError());exit;
//             if ($result === false) {
//                 $this->error($uploader->getError());
//             } else {

//                   $this->success(lang('Upload_successful'), '', $result);


//             }


//               // 获取md5
//             if( $data['url']){
//                 $data['url_md5'] = md5_file($_FILES["file"]['tmp_name']);
//             }

//         }
        if(empty($data['url'])){
            $this->error(lang('Please_upload_music'));
        }

        $url =  explode("/", $data['url']);

        $data['url_md5'] =substr($url[count($url) - 1], 0, -4);

        $data['addtime']=time();

        if($param['id']){
            $result=Db::name('music')->where("id=".$param['id'])->update($data);
        }else{
            $result=Db::name('music')->insert($data);
        }
        if($result){
            $this->success(lang('Operation_successful'));
        }else{
            $this->error(lang('operation_failed'));
        }
    }
    //删除
    public function del(){
        $id=$this->request->param("id");
        if($id){
            $result=Db::name('music')->where("id=".$id)->delete();
            if($result){
                echo 1;exit;
            }
        }
        echo 0;exit;
    }


}
?>