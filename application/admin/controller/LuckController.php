<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class LuckController extends AdminBaseController
{
    /**
     * 靓号列表
     */
    public function index()
    {
        $luck = Db::name("user_luck_list")->select()->toarray();
        foreach ($luck as &$v) {
           if($v['uid']){
                $user=Db::name("user")->where("id=".$v['uid'])->field("user_nickname")->find();
                $v['user_nickname'] = $user['user_nickname'];
           }else{
                $v['user_nickname']='';
           }
        }
        $config = load_cache('config');
        $currency_name = $config['currency_name'];

        $this->assign('currency_name', $currency_name);
        $this->assign('luck', $luck);
        return $this->fetch();
    }

    /**
     * 靓号添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $list = Db::name("user_luck_list")->where("id=$id")->find();
        } else {
            $list['status'] = 0;
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $type = strlen($param['post']['nubmer']);
        if($type <= 0){
             $this->error(lang('Please_enter_good_number'));
        }
        if(!$param['post']['coin']){
             $this->error(lang('Please_enter_single_price'));
        }
        $data['type']  = $type;
        $data['addtime'] = time();
        $user_luck_list = Db::name("user_luck_list")->where("type='".$data['nubmer']."'")->find();
        if($user_luck_list){
            $this->error(lang('luck_already_exists'));
        }
        if ($id) {
            $result = Db::name("user_luck_list")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_luck_list")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('luck/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $luck = Db::name("user_luck_list")->where("id=" . $param['id'])->find();
        if($luck['status'] == 1 && $luck['uid']){
             Db::name("user")->where("id=".$luck['uid'])->update(array('luck' => '','luck_end_time'=>0));
        }
        $result = Db::name("user_luck_list")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //修改排序
    public function upd()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("gift")->where("id=$k")->update(array('orderno' => $v));
            if ($status) {
                $data = $status;
            }
        }

        if ($data) {
            $this->success(lang('Sorting_succeeded'));
        } else {
            $this->error(lang('Sorting_error'));
        }
    }

    //收回
    public function back(){
        $param = request()->param();
        $luck = Db::name("user_luck_list")->where("id=" . $param['id'])->find();
        if(!$luck){
            return 0;exit;
        }
        if(!$luck['uid'] && $luck['status'] == 0){
           return 1;exit;
        }
        $data=array(
                'status'=>0,
                'uid'=>''
        );

        if($luck['status'] == 1 && $luck['uid']){
            $user = Db::name("user")->where("id=".$luck['uid'])->update(array('luck' => '','luck_end_time'=>0));
           //  var_dump($luck['uid']);exit;
            if(!$user){
               return 0;exit;
            }
        }
        $status = Db::name("user_luck_list")->where("id=".$param['id'])->update($data);
        if($status){
             return 1;exit;
        }
        return 0;exit;
    }
    //卖出
    public function luck_sell(){
        $param = request()->param();
        $luck = Db::name("user_luck_list")->where("id=" . $param['id'])->find();
        if($luck['status'] == 1 && $luck['uid']){
            Db::name("user")->where("id=".$param['uid'])->update(array('luck' => '','luck_end_time'=>0));
        }
        $data=array(
            'luck' => $luck['nubmer'],
            'luck_end_time' => NOW_TIME + 30*365*24*60*60
        );
        $sell=Db::name("user")->where("id=".$param['uid'])->update($data);
        if($sell){
            Db::name("user_luck_list")->where("id=". $param['id'])->update(array('status' => '1','uid'=>$param['uid']));
             return 1;exit;
        }else{
            return 0;exit;
        }
       
    }
}
