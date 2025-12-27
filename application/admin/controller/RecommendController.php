<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-07-11
     * Time: 16:27
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use app\admin\model\AdminMenuModel;

    class RecommendController extends AdminBaseController
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
                $result = Db::name("user_reference")->where("id=$id")->update($data);
            } else {
                $result = Db::name("user_reference")->insert($data);
            }
            if ($result) {
                if($data['type']==2){
                    $this->success(lang('EDIT_SUCCESS'), url('recommend/index_takills'));
                }else{
                    $this->success(lang('EDIT_SUCCESS'), url('recommend/index_player'));
                }

            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function index_takills(){
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('uid') and !$this->request->param('name') and !$this->request->param('game_id') and !$this->request->param('label_id') ) {
                session("level_index", null);
                $data['game_id'] = '0';
                $data['label_id'] = '0';
                session("level_index", $data);

            } else if (empty($p)) {
                $data['name'] = $this->request->param('name');
                $data['uid'] = $this->request->param('uid');
                $data['label_id'] = $this->request->param('label_id');
                //$data['game_id'] = $this->request->param('game_id');
                session("level_index", $data);
            }

            $name = session("level_index.name");
            $uid = session("level_index.uid");
            $label_id = session("level_index.label_id");
            //$game_id = session("level_index.game_id");
            //$type = session("level_index.type");
            $where = '';
            $where = "r.type = 2 ";
            $where .=$uid ? " and r.uid=".intval($uid) : '';
            $where .= $name ? " and u.user_nickname like '%".trim($name)."%'" :'';
            //$where .= $game_id ? " and s.game_id =".$game_id:'';
            $where .= $label_id ? " and r.recommend_label =".$label_id:'';

            $list = [];
            $page = 10;
            $data = Db::name("user_reference")
                ->alias('r')
                ->join('user u','u.id=r.uid')
                ->join('skills_recommend_label s','s.id=r.recommend_label')
                ->field('r.*,u.user_nickname,s.label_name')
                ->where($where)
                ->order('r.orderno')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = [];
            $this->assign('page', $data->render());
            $label = Db::name('skills_recommend_label')->select();
            $this->assign('list', $data);
            $this->assign('game', $list);
            $this->assign('label', $label);
            $this->assign("data", session("level_index"));
            return $this->fetch();
        }

        public function add_takills(){
            $id = input('param.id');
            if ($id) {
                $level = Db::name("user_reference")->where("id=$id")->find();
                $list = Db::name('user')
                    ->where(['is_talker'=>1])
                    ->select();
            }else{
                $level['uid']=1;
                $level['recommend_label']=1;
                $reference = Db::name("user_reference")->select();
                $reference_id = [];
                if($reference){
                    foreach($reference as $v){
                        array_push($reference_id,$v['uid']);
                    }
                }
                $map['id'] = ['not in',$reference_id];
                $list = Db::name('user')
                    ->where(['is_talker'=>1])
                    ->where($map)
                    ->select();
            }
            $label = Db::name('skills_recommend_label')->select();
            $this->assign('level', $level);
            $this->assign('list', $list);
            $this->assign('label', $label);
            //$this->assign('$list', $list);

            return $this->fetch();
        }


        public function index_player(){
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('uid') and !$this->request->param('name') and !$this->request->param('game_id') and !$this->request->param('label_id') ) {
                session("level_index", null);
                $data['game_id'] = '0';
                $data['label_id'] = '0';
                session("level_index", $data);

            } else if (empty($p)) {
                $data['name'] = $this->request->param('name');
                $data['uid'] = $this->request->param('uid');
                $data['label_id'] = $this->request->param('label_id');
                //$data['game_id'] = $this->request->param('game_id');
                session("level_index", $data);
            }

            $name = session("level_index.name");
            $uid = session("level_index.uid");
            $label_id = session("level_index.label_id");
            $game_id = session("level_index.game_id");
            //$type = session("level_index.type");
            $where = '';
            $where = "r.type = 1 and i.status = 1";
            $where .=$uid ? " and u.id=".intval($uid) : '';
            $where .= $name ? " and u.user_nickname like '%".trim($name)."%'" :'';
            $where .= $game_id ? " and g.game_id =".$game_id:'';
            $where .= $label_id ? " and r.recommend_label =".$label_id:'';

            $list = [];
            $page = 10;
            $data = Db::name("user_reference")
                ->alias('r')
                ->join('skills_info i','i.id = r.uid')
                ->join('user u','u.id=i.uid')
                ->join('play_game g','g.id=i.game_id')
                ->join('skills_recommend_label s','s.id=r.recommend_label')
                ->field('r.*,u.user_nickname,s.label_name,g.name as gname')
                ->where($where)
                ->order('r.orderno')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = [];
            $this->assign('page', $data->render());
            $label = Db::name('skills_recommend_label')->select();
            $this->assign('list', $data);
            $this->assign('game', $list);
            $this->assign('label', $label);
            $this->assign("data", session("level_index"));
            return $this->fetch();
        }

        public function add_player(){
            $id = input('param.id');
            $where = '';
            if ($id) {
                $level = Db::name("user_reference")->where("id=$id")->find();
                $list = Db::name('skills_info')
                    ->alias('s')
                    ->join('user u','u.id=s.uid')
                    ->join('play_game g','g.id=s.game_id')
                    ->where($where)
                    ->where('s.status = 1')
                    ->field('s.*,u.user_nickname,g.name')
                    ->order('create_time desc')
                    ->select();
            }else{
                $level['uid']=1;
                $level['recommend_label']=1;
                $reference = Db::name("user_reference")->select();
                $reference_id = [];
                if($reference){
                    foreach($reference as $v){
                        array_push($reference_id,$v['uid']);
                    }
                }
                $map['s.id'] = ['not in',$reference_id];
                $list = Db::name('skills_info')
                    ->alias('s')
                    ->join('user u','u.id=s.uid')
                    ->join('play_game g','g.id=s.game_id')
                    ->where($map)
                    ->where('s.status = 1')
                    ->field('s.*,u.user_nickname,g.name')
                    ->order('create_time desc')
                    ->select();
            }
            $label = Db::name('skills_recommend_label')->select();
            $this->assign('level', $level);
            $this->assign('list', $list);
            $this->assign('label', $label);
            //$this->assign('$list', $list);

            return $this->fetch();
        }

        public function del(){
            $param = $this->request->param();
            $id = $param['id'];
            $type = $param['type'];
            if($type==4){
                $result = Db::name("user_reference")->where(['id'=>$id])->delete();
            }else{
                $result = '';
            }
            return $result?1:0;
        }
    }
