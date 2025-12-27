<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-08-01
     * Time: 14:01
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use app\admin\model\AdminMenuModel;

    class PlayerOrderController extends AdminBaseController
    {
        //订单
        public function index(){
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('name') and !$this->request->param('uid') and !$this->request->param('game_id') && !isset($_REQUEST['status']) and !$this->request->param('start_ordertime')and !$this->request->param('end_ordertime')and !$this->request->param('start_time')and !$this->request->param('end_time') ) {
                session("level_index", null);
                $data['game_id'] = '0';
                $data['status'] = '-1';
                session("level_index", $data);

            } else if (empty($p)) {
                $data['name'] = $this->request->param('name');
                $data['uid'] = $this->request->param('uid');
                $data['game_id'] = $this->request->param('game_id');
                $data['status'] = $this->request->param('status');
                $data['start_time'] = $this->request->param('start_time');
                $data['end_time'] = $this->request->param('end_time');
                $data['start_ordertime'] = $this->request->param('start_ordertime');
                $data['end_ordertime'] = $this->request->param('end_ordertime');
                session("level_index", $data);
            }

            $name = session("level_index.name");
            $uid = session("level_index.uid");
            $game_id = session("level_index.game_id");
            $status = session("level_index.status");
            $start_time = strtotime(session("level_index.start_time"));
            $end_time = strtotime(session("level_index.end_time"));
            $start_ordertime = strtotime(session("level_index.start_ordertime"));
            $end_ordertime = strtotime(session("level_index.end_ordertime"));
            //$type = session("level_index.type");
            $map = [];
            if($uid){
                $map['s.uid'] = $uid;
            }
            if($name){
                $map['u.user_nickname'] = ['like','%'.$name.'%'];
            }
            if($game_id){
                $map['s.game_id'] = $game_id;
            }
            if($status>-1){
                $map['s.status'] = $status;
            }
            if ($start_time && $end_time) {
                $map['s.addtime'] = ['between', [$start_time, $end_time]];
            }
            if ($start_ordertime && $end_ordertime) {
                $map['s.ordertime'] = ['between', [$start_ordertime, $end_ordertime]];
            }
            //dump($map);
            $page = 10;
            $data = Db::name('skills_order')
                ->alias('s')
                ->join('skills_info i','i.id=s.skills_id')
                ->join('user u','u.id=s.uid')
                ->join('user t','t.id=s.touid')
                ->join('play_game g','g.id=s.game_id')
                ->where($map)
                ->field('s.*,u.user_nickname,g.name,t.user_nickname as toname')
                ->order('addtime desc')
                ->paginate($page, false, ['query' => request()->param()]);
            //$data = [];
            $this->assign('page', $data->render());
            $this->assign('list', $data);
            $this->assign("data", session("level_index"));

            $label = Db::name('skills_recommend_label')->select();
            $game = Db::name('play_game')->select();
            $this->assign("label", $label);
            $this->assign("game", $game);

            return $this->fetch();
        }

        public function refund(){
            $param = $this->request->param();
            $id = $param['id'];
            $order_info = db('skills_order')->find($id);
            if($order_info){
                $user_info = db('user')->field('id,last_login_ip')->find($order_info['uid']);
                //返还金币
                $coin = $order_info['total_coin'];
                db('user')
                    ->where('id='.$order_info['uid'])
                    ->inc('coin', $coin)
                    ->update();
                //增加消费记录
                upd_user_coin_log($order_info['uid'],$coin,$coin,10,1,1,$user_info['last_login_ip'],$user_info['id']);
                player_order_msg($id,19);
                //修改订单状态
                db('skills_order')->where(['id'=>$id])->update(['status'=>19]);
                return 1;
            }else{
                return 0;
            }
        }

        public function del(){
            $param = $this->request->param();
            $id = $param['id'];
            $type = $param['type'];

            $result = Db::name("skills_order")->where(['id'=>$id])->delete();

            return $result?1:0;
        }
    }
