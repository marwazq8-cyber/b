<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-20
     * Time: 10:47
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use think\Request;
    use app\admin\model\AuthModel;

    class AuthController extends AdminBaseController
    {
        //主播认证 陪聊师
        public function auth_talker_list()
        {
            $where = [];
            $request = input('request.');

            if (!empty($request['uid'])) {
                $where['t.uid'] = intval($request['uid']);
            }

            if (isset($request['status']) && intval($request['status']) != -1) {
                $where['t.status'] = intval($request['status']);
            } else {
                $request['status'] = '-1';
            }

            if (input('request.end_time') > 0 && input('request.start_time')) {
                $where['addtime'] = ['between', [strtotime(input('request.start_time')), strtotime(input('request.end_time'))]];
            }

            $authModel = new AuthModel();
            $data = $authModel->get_auth_talker($where);
            $list = $data->toArray();
            foreach ($list['data'] as &$v){
                $auth_talker_img = db('auth_talker_img')->field("id,img")->where("aid=".$v['id'])->select()->toArray();
                $v['platform_auth_img'] = count($auth_talker_img) ? $auth_talker_img : array();
            }
            //$list = [];
            $this->assign('page', $data->render());
            $this->assign('list', $list['data']);
            $this->assign('request', $request);
            return $this->fetch();
        }

        public function get_talker_img(){
            $id = input('id');
            $authModel = new AuthModel();
            $where = ['aid'=>$id];
            $data = $authModel->get_talker_img($where);
            $result = ['code'=>1,'msg'=>''];
            $result['data'] = $data;
            echo json_encode($result);
        }

        //陪玩师认证
        public function auth_player_list()
        {
            $where = [];
            $request = input('request.');

            if (!empty($request['uid'])) {
                $where['p.uid'] = intval($request['uid']);
            }

            if (isset($request['status']) && intval($request['status']) != -1) {
                $where['p.status'] = intval($request['status']);
            } else {
                $request['status'] = '-1';
            }
            if (input('request.end_time') > 0 && input('request.start_time')) {
                $where['p.addtime'] = ['between', [strtotime(input('request.start_time')), strtotime(input('request.end_time'))]];
            }

            $authModel = new AuthModel();
            $data = $authModel->get_auth_playerv($where);
            $list = $data->toArray();
            foreach ($list['data'] as &$v){
                $auth_player_img = db('auth_player_img')->field("id,img")->where("pid=".$v['id'])->select()->toArray();
                $v['platform_auth_img'] = count($auth_player_img) ? $auth_player_img : array();
            }
            //$list = [];
            $this->assign('page', $data->render());
            $this->assign('list', $list['data']);
            $this->assign('request', $request);
            return $this->fetch();
        }
        public function SavePlayerall()
        {
            $request = request()->param();
            if (empty($request['id'])) {
                return $this->redirect('/admin/public/index.php/admin/auth/auth_player_list');
            } else {
                $id = $request['id'];
                $type = $request['type'];
                foreach ($id as $key => $val) {
                    $auth_info = db("auth_player")->where('id', '=', $val)->where('status', '=', 0)->find();
                    if(!$auth_info){
                        $this->error(lang('operation_failed'));
                    }else{
                        $res = Db::name('auth_player') ->where('id', '=', $val)  ->update(['status'=>$type]);
                        if($res){
                            $url = 'bogo://auth?type=1&id='.$auth_info['uid'];
                            if($type==1){
                                Db::name('user')->where('id = '.$auth_info['uid'])->update(['is_player'=>1]);
                                push_msg_user(9, $auth_info['uid'], 1, '',$url);
                            }else{
                                push_msg_user(10, $auth_info['uid'], 1, '',$url);
                            }
                        }else{
                            $this->error(lang('operation_failed'));
                        }
                    }

                }
                if ($res) {
                    $this->success(lang('Operation_successful'));
                } else {
                    $this->success(lang('operation_failed'));
                }
            }
        }
        public function SaveTalkerall()
        {
            $request = request()->param();
            if (empty($request['id'])) {
                return $this->redirect('/admin/public/index.php/admin/auth/auth_talker_list');
            } else {
                $id = $request['id'];
                $type = $request['type'];
                foreach ($id as $key => $val) {
                    $auth_info = db("auth_talker")->where('id', '=', $val)->where('status', '=', 0)->find();
                    if(!$auth_info){
                        $this->error(lang('operation_failed'));
                    }else{
                        $res = Db::name('auth_talker') ->where('id', '=', $val)  ->update(['status'=>$type]);
                        if($res){
                            $url = 'bogo://auth?type=2&id='.$auth_info['uid'];

                            if($type==1){
                                Db::name('user')->where('id = '.$auth_info['uid'])->update(['is_talker'=>1]);
                                push_msg_user(7, $auth_info['uid'], 1, '',$url);
                            }else{
                                push_msg_user(8, $auth_info['uid'], 1, '',$url);
                            }
                        }else{
                            $this->error(lang('operation_failed'));
                        }
                    }
                }
                if ($res) {
                    $this->success(lang('Operation_successful'));
                } else {
                    $this->success(lang('operation_failed'));
                }
            }
        }
        public function SavePlatformall()
        {
            $request = request()->param();
            if (empty($request['id'])) {
                return $this->redirect('/admin/public/index.php/admin/auth/platform_auth');
            } else {
                $id = $request['id'];
                $type = $request['type'];
                foreach ($id as $key => $val) {
                    $auth_info = db("platform_auth")->where('id', '=', $val)->where('status', '=', 0)->find();
                    if(!$auth_info){
                        $this->error(lang('operation_failed'));
                    }else{
                        $res = Db::name('platform_auth') ->where('id', '=', $val)  ->update(['status'=>$type]);
                        if($res){
                            $url = 'bogo://auth?type=3&id='.$auth_info['user_id'];
                            if($type==1){
                                Db::name('user')->where('id = '.$auth_info['user_id'])->update(['is_talker'=>1]);
                                push_msg_user(11, $auth_info['user_id'], 1, '',$url);
                            }else{
                                push_msg_user(12, $auth_info['user_id'], 1, '',$url);
                            }
                        }else{
                            $this->error(lang('operation_failed'));
                        }
                    }
                }
                if ($res) {
                    $this->success(lang('Operation_successful'));
                } else {
                    $this->success(lang('operation_failed'));
                }

            }
        }
        public function get_player_img(){
            $id = input('id');
            $authModel = new AuthModel();
            $where = ['pid'=>$id];
            $data = $authModel->get_player_img($where);
            $result = ['code'=>1,'msg'=>''];
            $result['data'] = $data;
            echo json_encode($result);
        }

        public function status_auth_player(){
            $id = input('param.id', 0, 'intval');
            $uid = input('param.uid', 0, 'intval');
            $type = input('param.type', 0, 'intval');
            $center = input('param.center');
            $authModel = new AuthModel();

            $res = $authModel->player_upd($id,$uid,$type,$center);
            if($res){
                $url = 'bogo://auth?type=1&id='.$uid;
                if($type==1){
                    push_msg_user(9, $uid, 1, $center,$url);
                    $this->success(lang('Operation_successful'));
                }else{
                    push_msg_user(10, $uid, 1, $center,$url);
                    //拒绝删除认证信息
                    $where = ['id'=>$id,'uid'=>$uid];
                    Db::name('auth_player')->where($where)->delete();
                    $this->success(lang('Operation_successful'));
                }
            }else{
                $this->success(lang('operation_failed'));
            }

        }

        public function status_auth_talker(){
            $id = input('param.id', 0, 'intval');
            $uid = input('param.uid', 0, 'intval');
            $type = input('param.type', 0, 'intval');
            $center = input('param.center');
            $authModel = new AuthModel();

            $res = $authModel->talker_upd($id,$uid,$type,$center);
            if($res){
                $url = 'bogo://auth?type=2&id='.$uid;
                if($type==1){
                    push_msg_user(7, $uid, 1, $center,$url);
                    $this->success(lang('Operation_successful'));
                }else{
                    push_msg_user(8, $uid, 1, $center,$url);
                    //拒绝删除认证信息
                    $where = ['id'=>$id,'uid'=>$uid];
                    Db::name('auth_talker')->where($where)->delete();
                    $this->success(lang('Operation_successful'));
                }
            }else{
                $this->success(lang('operation_failed'));
            }

        }

        public function del(){
            $id = input('param.id', 0, 'intval');
            //$uid = input('param.uid', 0, 'intval');
            $type = input('param.type', 0, 'intval');
            $authModel = new AuthModel();
            if($type==1){
                $res = $authModel->talker_del($id);
            }else if($type==2){
                $res = $authModel->player_del($id);
            }
            if($res){
                echo 1;
                exit;
            }else{
                echo 0;
                exit;
            }
        }

        //官方认证审核
        public function platform_auth(){
            $where = [];
            $request = input('request.');

            if (!empty($request['uid'])) {
                $where['t.user_id'] = intval($request['uid']);
            }

            if (isset($request['status']) && intval($request['status']) != -1) {
                $where['t.status'] = intval($request['status']);
            } else {
                $request['status'] = '-1';
            }

            if (input('request.end_time') > 0 && input('request.start_time')) {
                $where['addtime'] = ['between', [strtotime(input('request.start_time')), strtotime(input('request.end_time'))]];
            }
            $page= 10;
            $data = db('platform_auth')
                ->alias('t')
                ->join('user u','u.id = t.user_id')
                // ->join('platform_auth_type p','p.id = t.user_id')
                ->where($where)
                ->order('id desc')
                ->field('t.*,u.user_nickname')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = $authModel->get_auth_talker($where);
            //$list = [];
            $list = $data->toArray();
            foreach ($list['data'] as &$v){
                $platform_auth_img = db('platform_auth_img')->field("id,img")->where("pid=".$v['id'])->select()->toArray();
                $v['platform_auth_img'] = count($platform_auth_img) ? $platform_auth_img : array();
            }

            $this->assign('page', $data->render());
            $this->assign('list', $list['data']);
            $this->assign('request', $request);
            return $this->fetch();
        }

        public function get_platform_img(){
            $id = input('id');
            //$authModel = new AuthModel();
            $where = ['pid'=>$id];
            //$data = $authModel->get_player_img($where);
            $data = db('platform_auth_img')->where($where)->select();
            $result = ['code'=>1,'msg'=>''];
            $result['data'] = $data;
            echo json_encode($result);
        }

        public function status_auth_platform(){
            $id = input('param.id', 0, 'intval');
            $uid = input('param.uid', 0, 'intval');
            $type = input('param.type', 0, 'intval');
            $center = input('param.center');
            $res = db('platform_auth')->where(['id'=>$id])->update(['status'=>$type]);
            if($res){
                $url = 'bogo://auth?type=3&id='.$uid;
                if($type==1){
                    push_msg_user(11, $uid, 1, $center,$url);
                    $this->success(lang('Operation_successful'));
                }else{
                    db('platform_auth')->where(['id'=>$id])->delete();
                    push_msg_user(12, $uid, 1, $center,$url);
                    $this->success(lang('Operation_successful'));
                }
            }else{
                $this->success(lang('operation_failed'));
            }

        }

        public function del_platform(){
            $id = input('param.id', 0);
            //$uid = input('param.uid', 0, 'intval');
            //$type = input('param.type', 0, 'intval');
            $res = db('platform_auth')->where(['id'=>$id])->delete();

            if($res){
                echo 1;
                exit;
            }else{
                echo 0;
                exit;
            }
        }

        //官方认证类型
        public function platform_auth_type(){
            $page = 10;
            $list = db('platform_auth_type')
                ->order('orderno')
                ->paginate($page, false, ['query' => request()->param()]);
            //$playGameModel = new PlayGameModel();
            //$data = $playGameModel->get_type_list();
            //$list = [];
            $this->assign('page', $list->render());
            $this->assign('list', $list);
            return $this->fetch();
        }

        public function add_type()
        {
            //$param = $this->request->param();
            //  print_r($param);exit;
            $id = input('id');
            $data = [];
            if($id){
                $where = 'id = '.$id;
                $data = db('platform_auth_type')->where($where)->find();
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
            //$playGameModel = new PlayGameModel();
            if ($id) {
                $where = 'id = '.$id;
                $result = db('platform_auth_type')->where($where)->update($data);
            } else {
                $result = db('platform_auth_type')->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('auth/platform_auth_type'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function del_type(){
            $id = input('param.id', 0);
            //$uid = input('param.uid', 0, 'intval');
            //$type = input('param.type', 0, 'intval');
            $authModel = new AuthModel();
            $res = db('platform_auth_type')->where(['id'=>$id])->delete();

            if($res){
                echo 1;
                exit;
            }else{
                echo 0;
                exit;
            }
        }
    }
