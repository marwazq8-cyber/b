<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/30 0030
 * Time: 上午 9:20
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class FeedbackController extends AdminBaseController
{
    //意见反馈
    public function index(){
        $where = [];

        if (!input('request.page')) {
            $index['status']='-1';
            session('feedback_index', $index);
        }
        if (input('request.id') || input('request.status') >= '0' || input('request.start_time') || input('request.end_time')) {
            session('feedback_index', input('request.'));
        }
        if (session('feedback_index.id')) {
            $where['u.id'] = session('feedback_index.id');
        }
        if (session('feedback_index.status') >='0') {
            $where['r.status'] = session('feedback_index.status');
        }
        if (session('feedback_index.end_time') && session('feedback_index.start_time')) {
            $where['r.addtime'] = ['between', [strtotime(session('feedback_index.start_time')), strtotime(session('feedback_index.end_time'))]];
        }
        $list = db('feedback')
            ->alias('r')
            ->join("user u", "u.id=r.uid")
            ->field('u.user_nickname,r.*')
            ->where($where)
            ->order("r.addtime DESC")
            ->paginate(20, false, ['query' => request()->param()]);
        $data = $list->toArray();
        foreach($data['data'] as $key=>$val){
            $img_url = '';
            if($val['img']){
                $img = explode(',',$val['img']);
                $img_url = $img[0];
            }
            $data['data'][$key]['img'] = $img_url;
        }
        //dump($data);
        $page = $list->render();

        $this->assign('request', session('feedback_index'));
        $this->assign('data', $data['data']);
        $this->assign('page', $page);
        return $this->fetch();
    }
    //处理意见反馈
    public function add(){
        $id=input('request.id');
        $list = db('feedback')->where("id=".$id)->setInc("status",1);
        return $list? "1" : '2';
    }
    //删除意见反馈
    public function del(){
        $id=input('request.id');
        $list = db('feedback')->where("id=".$id)->delete();
        return $list? "1" : '2';
    }
    //查看图片
    public function select_img(){
        $id=input('request.id');
        $list = db('feedback')->field("img")->where("id=".$id)->find();
        $name['title']= lang('ADMIN_FEEDBACK_LIST');
        $name['id']=$id;
        $name['start']=0;
        $img = explode(',',$list['img']);
        $imgs = [];
        foreach ($img as $k=>$v){
            $imgs[$k]['alt'] = lang('ADMIN_IMG');
            $imgs[$k]['src'] = $v;
            $imgs[$k]['thumb'] = $v;
        }
        /*for($i=0;$i<4;$i++){
            if($img[$i]){
                $imgs[$i]['alt']=lang("图片");
                $imgs[$i]['src']=$img[$i];
                $imgs[$i]['thumb']=$img[$i];
            }
        }*/
        $name['data']=$imgs;
        echo json_encode($name);exit;
    }
}
