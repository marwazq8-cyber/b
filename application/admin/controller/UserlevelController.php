<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-21
     * Time: 10:16
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use think\Request;
    use app\admin\model\PlayGameModel;

    class UserlevelController extends AdminBaseController
    {
        //陪玩师等级
        public function player_level(){
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('name') and !$this->request->param('colors') ) {
                session("level_index", null);
                //$data['type'] = '1';
                //session("level_index", $data);

            } else if (empty($p)) {
                $data['name'] = $this->request->param('name');
                $data['colors'] = $this->request->param('colors');
                //$data['type'] = $this->request->param('type');
                session("level_index", $data);
            }

            $level_name = session("level_index.name");
            $colors = session("level_index.colors");
            //$type = session("level_index.type");
            $where = '';
            //$where = "type=".$type;
            $where .=$level_name ? " name=".trim($level_name) : '';
            $where .= $colors ? " and colors like '%".trim($colors)."%'" :'';
            $page = 10;
            $data = Db::name('player_level')
                ->where($where)
                ->order('sort')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            $this->assign("data", session("level_index"));
            return $this->fetch();
        }

        public function add_player_level(){
            $id = input('param.id');
            if ($id) {
                $name = Db::name("player_level")->where("id=$id")->find();
            }else{
                $name['chat_icon']='';
                $name['level_icon']='';
                $name['type']=1;
            }
            $this->assign('level', $name);
            return $this->fetch();
        }

        public function add_player_post(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            $data['addtime'] = time();
            if ($id) {
                $result = Db::name("player_level")->where("id=$id")->update($data);
            } else {
                $result = Db::name("player_level")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('userlevel/player_level'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        //陪聊师等级
        public function talker_level(){
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('name') and !$this->request->param('colors') ) {
                session("level_index", null);
                //$data['type'] = '1';
                //session("level_index", $data);

            } else if (empty($p)) {
                $data['name'] = $this->request->param('name');
                $data['colors'] = $this->request->param('colors');
                //$data['type'] = $this->request->param('type');
                session("level_index", $data);
            }

            $level_name = session("level_index.name");
            $colors = session("level_index.colors");
            //$type = session("level_index.type");
            $where = '';
            //$where = "type=".$type;
            $where .=$level_name ? " name=".trim($level_name) : '';
            $where .= $colors ? " and colors like '%".trim($colors)."%'" :'';
            $page = 10;
            $data = Db::name('talker_level')
                ->where($where)
                ->order('sort')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            $this->assign("data", session("level_index"));
            return $this->fetch();
        }

        public function add_talker_level(){
            $id = input('param.id');
            if ($id) {
                $name = Db::name("talker_level")->where("id=$id")->find();
            }else{
                $name['chat_icon']='';
                $name['level_icon']='';
                $name['type']=1;
            }
            $this->assign('level', $name);
            return $this->fetch();
        }

        public function add_talker_post(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            $data['addtime'] = time();
            if ($id) {
                $result = Db::name("talker_level")->where("id=$id")->update($data);
            } else {
                $result = Db::name("talker_level")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('userlevel/talker_level'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        //密友等级
        public function friend_level(){
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('name') and !$this->request->param('colors') ) {
                session("level_index", null);
                //$data['type'] = '1';
                //session("level_index", $data);

            } else if (empty($p)) {
                $data['name'] = $this->request->param('name');
                $data['colors'] = $this->request->param('colors');
                //$data['type'] = $this->request->param('type');
                session("level_index", $data);
            }

            $level_name = session("level_index.name");
            $colors = session("level_index.colors");
            //$type = session("level_index.type");
            $where = '';
            //$where = "type=".$type;
            $where .=$level_name ? " name=".trim($level_name) : '';
            $where .= $colors ? " and colors like '%".trim($colors)."%'" :'';
            $page = 10;
            $data = Db::name('friendship_level')
                ->where($where)
                ->order('sort')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            $this->assign("data", session("level_index"));
            return $this->fetch();
        }

        public function add_friend_level(){
            $id = input('param.id');
            if ($id) {
                $name = Db::name("friendship_level")->where("id=$id")->find();
            }else{
                $name['is_text']=1;
                $name['is_audio']=1;
                $name['is_video']=1;
                $name['is_info']=1;
            }
            $this->assign('level', $name);
            return $this->fetch();
        }

        public function add_friend_post(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            $data['addtime'] = time();
            if ($id) {
                $result = Db::name("friendship_level")->where("id=$id")->update($data);
            } else {
                $result = Db::name("friendship_level")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('userlevel/friend_level'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function del(){
            $param = $this->request->param();
            $id = $param['id'];
            $type = $param['type'];
            if($type==1){
                $result = Db::name("player_level")->where("id=" . $id)->delete();
            }else if($type==2){
                $result = Db::name("talker_level")->where("id=" . $id)->delete();
            }else if($type==3){
                $result = Db::name("friendship_level")->where("id=" . $id)->delete();
            }

            return $result ? '1' : '0';
            exit;
        }
    }
