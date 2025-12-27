<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-25
     * Time: 09:37
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use app\admin\model\AdminMenuModel;

    class PlayerController extends AdminBaseController
    {
        public function label(){
            $list = Db::name('skills_recommend_label')->select();
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function add_label(){
            $id = input('param.id');
            if ($id) {
                $list = Db::name("skills_recommend_label")->where("id=$id")->find();
            }else{

                $list['label_img']='';
            }
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function addLabelPost(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];

            $data['addtime']= time();
            if ($id) {
                $result = Db::name("skills_recommend_label")->where("id=$id")->update($data);
            } else {
                $result = Db::name("skills_recommend_label")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('player/label'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function index(){
            /*$list = Db::name('skills_info')
                ->order('create_time desc')
                ->where($where)
                ->select();
            $this->assign('list', $list);
            return $this->fetch();*/
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('name') and !$this->request->param('colors') and !$this->request->param('game_id') ) {
                session("level_index", null);
                $data['game_id'] = '0';
                session("level_index", $data);

            } else if (empty($p)) {
                $data['name'] = $this->request->param('name');
                $data['colors'] = $this->request->param('colors');
                $data['game_id'] = $this->request->param('game_id');
                session("level_index", $data);
            }

            $level_name = session("level_index.name");
            $colors = session("level_index.colors");
            $game_id = session("level_index.game_id");
            //$type = session("level_index.type");
            $where = '';
            $where = "s.id > 0 and s.status != 4";
            $where .= $level_name ? " and s.uid=".intval($level_name) : '';
            $where .= $colors ? " and u.user_nickname like '%".trim($colors)."%'" :'';
            $where .= $game_id ? " and s.game_id =".$game_id:'';
            $page = 10;
            $data = Db::name('skills_info')
                ->alias('s')
                ->join('user u','u.id=s.uid')
                ->join('play_game g','g.id=s.game_id')
                ->where($where)
                ->field('s.*,u.user_nickname,g.name')
                ->order('create_time desc')
                ->paginate($page, false, ['query' => request()->param()]);
            $list = $data->toArray();
            $list_arr = [];
            foreach ($list['data'] as $item) {
                $list_map['status'] = ['in',[1,2,3,4,6,11]];
                $count = db('skills_order')->where($list_map)
                    ->where('skills_id = '.$item['id'])
                    ->count();
                $user_count = db('skills_order')->where($list_map)
                    ->where('skills_id = '.$item['id'])
                    ->group('uid')
                    ->count();
                $item['count_order'] = $count;
                $item['count_user'] = $user_count;
                $list_arr[] = $item;
            }

            //$data = [];
            $this->assign('page', $data->render());
            $this->assign('list', $list_arr);
            $this->assign("data", session("level_index"));

            $label = Db::name('skills_recommend_label')->select();
            $game = Db::name('play_game')->select();
            $this->assign("label", $label);
            $this->assign("game", $game);

            return $this->fetch();
        }

        public function start_recommend(){
            $param = $this->request->param();
            $type = $param['type'];
            $id = $param['id'];
            if($type==1){
                $recommend_label = $param['recommend_label'];
                $data = [
                    'recommend_label'=>$recommend_label,
                    'recommend_time'=>NOW_TIME,
                    'is_recommend'=>1,
                ];
                $res = Db::name('skills_info')->where(['id'=>$id])->update($data);
            }else if($type==2){
                $data = [
                    'recommend_label'=>0,
                    'recommend_time'=>0,
                    'is_recommend'=>0,
                ];
                $res = Db::name('skills_info')->where(['id'=>$id])->update($data);
            }

            return $res?1:0;
        }

        public function comment_label(){
            $list = Db::name('skills_comment_label')->order('orderno')->select();
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function add_comment_label(){
            $id = input('param.id');
            if ($id) {
                $list = Db::name("skills_comment_label")->where("id=$id")->find();
            }else{

                $list['label_img']='';
            }
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function addCommentPost(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];

            $data['addtime']= time();
            if ($id) {
                $result = Db::name("skills_comment_label")->where("id=$id")->update($data);
            } else {
                $result = Db::name("skills_comment_label")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('player/comment_label'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function talker_label(){
            $list = Db::name('auth_talker_label')->order('orderno')->select();
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function add_talker_label(){
            $id = input('param.id');
            if ($id) {
                $list = Db::name("auth_talker_label")->where("id=$id")->find();
            }else{

                $list['label_img']='';
            }
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function addTalkerPost(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];

            $data['addtime']= time();
            if ($id) {
                $result = Db::name("auth_talker_label")->where("id=$id")->update($data);
            } else {
                $result = Db::name("auth_talker_label")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('player/talker_label'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function del(){
            $param = $this->request->param();
            $id = $param['id'];
            $type = $param['type'];
            if($type==1){
                $result = Db::name("skills_recommend_label")->where(['id'=>$id])->delete();
            }else if($type==2){
                $result = Db::name("skills_comment_label")->where(['id'=>$id])->delete();
            }else if($type==3){
                $result = Db::name("auth_talker_label")->where(['id'=>$id])->delete();
            }else if($type==4){
                $result = Db::name("skills_info")->where(['id'=>$id])->delete();

            }
            return $result?1:0;
        }

        public function evaluation(){
            $id = input('id');
            $page = 10;
            $data = db('skills_comment')
                ->alias('c')
                ->join('user u','u.id=c.uid')
                //->join('skills_order s','s.id=c.order_id')
                ->where('c.skills_id = '.$id)
                ->field('c.*,u.avatar,u.user_nickname')
                ->order('addtime desc ')
                ->paginate($page, false, ['query' => request()->param()]);
            $this->assign('list', $data);
            $this->assign('page', $data->render());
            return $this->fetch();
        }

        public function evaluation_del(){
            $param = $this->request->param();
            $id = $param['id'];
            $type = $param['type'];

            $result = Db::name("skills_comment")->where(['id'=>$id])->delete();

            return $result?1:0;
        }



    }
    //bogo_skills_recommend_label
