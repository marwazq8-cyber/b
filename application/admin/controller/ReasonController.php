<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-29
     * Time: 10:26
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use app\admin\model\AdminMenuModel;

    class ReasonController extends AdminBaseController
    {
        public function index(){
            $list = Db::name('refuse')->order('type,orderno')->select();
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function add(){
            $id = input('param.id');
            if ($id) {
                $list = Db::name("refuse")->where("id=$id")->find();
            }else{

                $list['type']=1;
            }
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function addPost(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];

            $data['addtime']= time();
            if ($id) {
                $result = Db::name("refuse")->where("id=$id")->update($data);
            } else {
                $result = Db::name("refuse")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('Reason/index'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function del(){
            $param = $this->request->param();
            $id = $param['id'];
            $type = $param['type'];
            if($type==3){
                $result = Db::name("refuse")->where(['id'=>$id])->delete();
            }
            return $result?1:0;
        }
    }
