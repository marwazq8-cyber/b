<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-19
     * Time: 10:10
     */

    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use think\Request;
    use app\admin\model\PlayGameModel;

    class PlayGameController extends AdminBaseController
    {

        public function play_type()
        {
            $playGameModel = new PlayGameModel();
            $data = $playGameModel->get_type_list();
            //$list = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            return $this->fetch();
        }

        public function add_type()
        {
            //$param = $this->request->param();
            //  print_r($param);exit;
            $id = input('id');
            $data = [];
            if($id){
                $playGameModel = new PlayGameModel();
                $where = 'id = '.$id;
                $data = $playGameModel->get_type_find($where);
            }
            $this->assign('list',$data);
            return $this->fetch();
        }

        public function addTypePost()
        {
            $param = $this->request->param();
            //  print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$data['img'] = $param['post']['img'];
            $data['create_time'] = time();
            $playGameModel = new PlayGameModel();
            if ($id) {
                $where = 'id = '.$id;
                $result = $playGameModel->update_type($where,$data);
            } else {
                $result = $playGameModel->add_type($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('PlayGame/play_type'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function play_game()
        {
            $playGameModel = new PlayGameModel();
            $data = $playGameModel->get_game_list();
            //$list = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            return $this->fetch();
        }

        public function add_game()
        {
            //$param = $this->request->param();
            //  print_r($param);exit;
            $id = input('id');
            $data = [
                'type_id'=>1,
                'img'=>'',
                'demo_img'=>'',
                'bg_img'=>'',
                'is_banner'=>1,
            ];
            $playGameModel = new PlayGameModel();
            if($id){
                $where = 'id = '.$id;
                $data = $playGameModel->get_game_find($where);
            }
            $type = $playGameModel->get_type_list(100);
            $this->assign('data',$data);
            $this->assign('type',$type);
            return $this->fetch();
        }

        public function addGamePost()
        {
            $param = $this->request->param();
            //  print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$data['img'] = $param['post']['img'];
            $data['create_time'] = time();
            $playGameModel = new PlayGameModel();
            if ($id) {
                $where = 'id = '.$id;
                $result = $playGameModel->update_game($where,$data);
            } else {
                $result = $playGameModel->add_game($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('PlayGame/play_game'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        //修改排序
        public function upd()
        {
            $param = request()->param();
            $data = '';
            $playGameModel = new PlayGameModel();
            foreach ($param['listorders'] as $k => $v) {
                $where = 'id = '.$k;
                $status = $playGameModel->update_game($where,array('orderno' => $v));
                //$status = Db::name("gift")->where("id=$k")->update(array('orderno' => $v));
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

        //游戏接单信息
        public function order_info(){
            $game_id = input('game_id');//游戏ID
            $playGameModel = new PlayGameModel();
            $data = $playGameModel->get_game_find(['id'=>$game_id]);
            $list = $playGameModel->get_game_order(['game_id'=>$game_id]);
            //$list = [];
            $this->assign('page', $list->render());
            $this->assign('data', $data);
            $this->assign('list', $list);
            return $this->fetch();
        }

        //添加接单信息
        public function add_order_info(){
            $game_id = input('game_id');//游戏ID
            $id = input('id');
            $playGameModel = new PlayGameModel();
            $data = $playGameModel->get_game_find(['id'=>$game_id]);
            $list = [
                'type'=>1,
            ];
            if($id){
               $list =  $playGameModel->get_game_order_find(['id'=>$id]);
            }
            //dump($list);
            $this->assign('data', $data);
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function add_order_info_run(){
            $param = $this->request->param();
            //  print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            $game_id = input('game_id');//游戏ID
            //$data['img'] = $param['post']['img'];
            $data['create_time'] = time();
            $data['game_id'] = $game_id;
            $playGameModel = new PlayGameModel();
            if ($id) {
                $where = 'id = '.$id;
                $result = $playGameModel->update_game_order($where,$data);
            } else {
                $result = $playGameModel->add_game_order($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('PlayGame/order_info',array('game_id'=>$game_id)));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function del(){
            $id = input('id');
            $type = input('type');
            $playGameModel = new PlayGameModel();
            $where = 'id = '.$id;
            if($type==1){
                $res = $playGameModel->del_type($where);
            }else if($type==2){
                $res = $playGameModel->del_game($where);
            }else if($type==3){
                $res = $playGameModel->del_game_order($where);
            }else if($type==4){
                $res = Db::name("skills_price")->where($where)->delete();
            }else if($type==5){
                $res = Db::name("skills_search_price")->where($where)->delete();
            }else if($type==6){
                $res = $playGameModel->del_game_order_type($where);
            }
            return $res ? '1' : '0';
            exit;

        }

        //接单价格
        public function price(){
            $page = 10;
            $list = Db::name("skills_price")
                ->paginate($page, false, ['query' => request()->param()]);
            $this->assign('list', $list);
            $this->assign('page', $list->render());
            return $this->fetch();
        }

        public function addPrice(){

            $id = input('id');

            $list = [
                'type'=>1,
            ];
            if($id){
                $list =  db('skills_price')->where(['id'=>$id])->find();
            }
            //dump($list);
            //$this->assign('data', $data);
            $this->assign('gift', $list);
            return $this->fetch();
        }

        public function addPriceRun(){
            $param = $this->request->param();
            //  print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$game_id = input('game_id');//游戏ID
            //$data['img'] = $param['post']['img'];
            $data['addtime'] = time();
            //$data['game_id'] = $game_id;
            $playGameModel = new PlayGameModel();
            if ($id) {
                $where = 'id = '.$id;
                $result = db('skills_price')->where($where)->update($data);
            } else {
                $result = db('skills_price')->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('PlayGame/price'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        //搜索价格
        public function search_price(){
            $page = 10;
            $list = Db::name("skills_search_price")
                ->paginate($page, false, ['query' => request()->param()]);
            $this->assign('list', $list);
            $this->assign('page', $list->render());
            return $this->fetch();
        }

        public function addSearchPrice(){

            $id = input('id');

            $list = [
                'type'=>1,
            ];
            if($id){
                $list =  db('skills_search_price')->where(['id'=>$id])->find();
            }
            //dump($list);
            //$this->assign('data', $data);
            $this->assign('gift', $list);
            return $this->fetch();
        }

        public function addSearchPriceRun(){
            $param = $this->request->param();
            //  print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$game_id = input('game_id');//游戏ID
            //$data['img'] = $param['post']['img'];
            $data['addtime'] = time();
            //$data['game_id'] = $game_id;
            $playGameModel = new PlayGameModel();
            if ($id) {
                $where = 'id = '.$id;
                $result = db('skills_search_price')->where($where)->update($data);
            } else {
                $result = db('skills_search_price')->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('PlayGame/search_price'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function order_type(){
            $playGameModel = new PlayGameModel();
            $data = $playGameModel->get_order_type_list();
            //$list = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            return $this->fetch();
        }

        public function add_order_type()
        {
            //$param = $this->request->param();
            //  print_r($param);exit;
            $id = input('id');
            $data = [];
            if($id){
                $playGameModel = new PlayGameModel();
                $where = 'id = '.$id;
                $data = $playGameModel->get_order_type_find($where);
            }
            $this->assign('list',$data);
            return $this->fetch();
        }

        public function addOrderTypePost()
        {
            $param = $this->request->param();
            //  print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$data['img'] = $param['post']['img'];
            $data['create_time'] = time();
            $playGameModel = new PlayGameModel();
            if ($id) {
                $where = 'id = '.$id;
                $result = $playGameModel->update_order_type($where,$data);
            } else {
                $result = $playGameModel->add_order_type($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('PlayGame/order_type'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }
    }
