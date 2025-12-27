<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Request;
use app\admin\model\AuthModel;

class ComparedController extends AdminBaseController
{
    //
    public function index()
    {
        return $this->fetch();
    }

    public function add(){

    }

    public function addPost(){
        //$file1 = $this->request->param('file1');
        $file1 = $_FILES['file1'];
        $file2 = $_FILES['file2'];
        $file1_content = file_get_contents($file1['tmp_name']);
        $file2_content = file_get_contents($file2['tmp_name']);
        $arr1 = explode('ENGINE=InnoDB AUTO_INCREMENT',$file1_content);
        $arr2 = explode('ENGINE=InnoDB AUTO_INCREMENT',$file2_content);
        $arr3 = array_intersect($arr1,$arr2);
        $arr4 = array_diff($arr1,$arr3);
        foreach ($arr4 as $k=>$v){
            $a = strpos($v,'DEFAULT CHARSET');
            dump($a);
            if($a>0){
               //unset($arr4[$k]);
                //dump($v);
            }
        }
        dump($arr4);
    }

    public function get_info(){
        $url = input('url');
        $json = file_get_contents($url);
        echo json_encode($json);
    }
}