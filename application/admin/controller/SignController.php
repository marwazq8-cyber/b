<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-30
     * Time: 10:38
     * 签到管理
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use think\Request;
    use app\admin\model\PlayGameModel;

    class SignController extends AdminBaseController
    {
        //签到天数
        public function index(){
            $p = $this->request->param('page');
            $page = 10;
            $data = Db::name('sign_in')
                ->order('sort')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            $this->assign("data", session("level_index"));
            return $this->fetch();
        }

        public function add(){
            $id = input('param.id');
            if ($id) {
                $name = Db::name("sign_in")->where("id=$id")->find();
            }else{
                $name['box_id']=0;
            }
            $config = load_cache('config');
            $this->assign('currency_name', $config['currency_name']);
            $this->assign('list', $name);
            return $this->fetch();
        }

        public function addPost(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            $data['addtime'] = time();
            if ($id) {
                $result = Db::name("sign_in")->where("id=$id")->update($data);
            } else {
                $sum = Db::name("sign_in")->where("id > 0")->count();
                if($sum >= 7) {
                    $this->error(lang('Sign_in_for_7_days_at_most'));
                }else{
                    $result = Db::name("sign_in")->insert($data);
                }

            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('sign/index'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function del(){
            $param = $this->request->param();
            $id = $param['id'];
            $type = $param['type'];

            $result = Db::name("sign_in")->where(['id'=>$id])->delete();

            return $result?1:0;
        }
    }
