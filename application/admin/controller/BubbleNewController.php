<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-12-14
     * Time: 11:24
     * Name: 打泡泡
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use app\admin\model\AdminMenuModel;

    class BubbleNewController extends AdminBaseController
    {
        /*
         * 奖励礼物包
         * */
        public function gift_bag_list(){
            if (!input('request.page')) {
                $data['gift_id']=0;
                $data['type']=0;
                $data['continuous_id']=0;
                $data['pool_id']=0;
                session('bubble_gift', $data);
            }
            if (input('request.type') || input('request.gift_id') || input('request.continuous_id') || input('request.pool_id')) {
                $data['type']=input('request.type') ? input('request.type') :0;
                $data['gift_id']=input('request.gift_id') ? input('request.gift_id') :0;
                $data['continuous_id']=input('request.continuous_id') ? input('request.continuous_id') :0;
                $data['pool_id']=input('request.pool_id') ? input('request.pool_id') :0;
                session('bubble_gift', $data);
            }
            $type=session('bubble_gift.type') >0 ? session('bubble_gift.type') :'';
            $gift_id=session('bubble_gift.gift_id') >0 ? session('bubble_gift.gift_id') :'';
            $continuous_id=session('bubble_gift.continuous_id') >0 ? session('bubble_gift.continuous_id') :'';
            $pool_id=session('bubble_gift.pool_id') >0 ? session('bubble_gift.pool_id') :'';

            $gift = Db::name("gift")->order("orderno desc")->select();

            $bubble_type = Db::name("bubble_type")->where("type=1")->order("orderno desc")->select();

            $where="e.id >0";
            $where.= $gift_id ? " and e.gift_id=". $gift_id:'';
            $where.= $continuous_id ? " and e.continuous_id=". $continuous_id:'';
            $where.= $pool_id ? " and e.pool_id=". $pool_id:'';
            $where.= $type ? " and e.type=". $type:'';

            $list = Db::name('bubble_gift_bag')
                ->alias("e")
                ->join('bubble_type t', 't.id = e.continuous_id')
                ->join('bubble_pool p', 'p.id = e.pool_id')
                ->field("e.*,t.sum as tsum,p.name as pname")
                ->where($where)
                //->order("e.sort desc")
                ->paginate(10, false, ['query' => request()->param()]);
            $data = $list->toArray();
            // dump($data);die();
            foreach ($data['data'] as $k=>$val){
                $gift = Db::name("bubble_gift_list")
                    ->alias('b')
                    ->join('gift g','b.gift_id = g.id')
                    ->where('b.gift_bag_id = '.$val['id'] )
                    ->field('sum(g.coin*b.sum) as coin')
                    ->find();
                $data['data'][$k]['coin'] = $gift['coin'];
            }
            $page = $list->render();
            $sum = Db::name("bubble_gift_list")
                ->alias('l')
                ->join('gift g','l.gift_id = g.id')
                ->join('bubble_gift_bag e','l.gift_bag_id = e.id')
                ->field('sum(g.coin*l.sum) as coin')
                ->where($where)
                ->find();
            $count = Db::name('bubble_gift_bag')
                ->alias("e")
                ->join('bubble_type t', 't.id = e.continuous_id')
                ->field("e.*,t.sum as tsum")
                ->where($where)
                ->count();
            if($sum &&  $sum['coin']>0){
                $sum['average'] = $sum['coin']/$count;
            }else{
                $sum['average'] = 0;
            }
            $pool = Db::name("bubble_pool")->select();
            $this->assign('pool', $pool);
            $this->assign('statistical', $sum);
            $this->assign('list', $data['data']);
            $this->assign('page', $page);
            $this->assign('gift', $gift);
            $this->assign('bubble_type', $bubble_type);
            $this->assign('request', session('bubble_gift'));
            return $this->fetch();
        }

        public function add(){
            $bubble_type = Db::name("bubble_type")->where("status=1 and type=1")->order("orderno desc")->select();
            $pool = Db::name("bubble_pool")->select();
            $this->assign('bubble_type',$bubble_type);
            $this->assign('pool',$pool);
            $this->assign('list',[]);
            return $this->fetch();
        }

        public function addPost()
        {
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            $type = $data['type'];
            $sum_id = $data['continuous_id'];
            $pool_id = $data['pool_id'];

            $data['create_time'] = NOW_TIME;

            $result = Db::name("bubble_gift_bag")->insert($data);

            if ($result) {
                //重置礼物包缓存
                $list = db('bubble_gift_bag')
                    ->where(['type'=>$type,'continuous_id'=>$sum_id,'pool_id'=>$pool_id])
                    ->where('odds > 0')
                    ->select();
                //dump($list);die();
                if (count($list) > 0) {
                    $arr = [];
                    foreach($list as $val){
                        $arr[$val['id']] = $val['odds'];
                        //$arr[] = $arr;
                    }
                    $redis_name = $type."_".$sum_id."_".$pool_id;
                    redis_hSet("bubble_gift_bag_list",$redis_name,json_encode($arr));
                }
                $this->success(lang('EDIT_SUCCESS'), url('bubbleNew/gift_bag_list'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function editPost()
        {
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            $type = $data['type'];
            $sum_id = $data['continuous_id'];
            $pool_id = $data['pool_id'];

            $data['create_time'] = NOW_TIME;

            $result = Db::name("bubble_gift_bag")->where('id = '.$id)->update($data);

            if ($result) {
                //重置礼物包缓存
                $list = db('bubble_gift_bag')
                    ->where(['type'=>$type,'continuous_id'=>$sum_id,'pool_id'=>$pool_id])
                    ->where('odds > 0')
                    ->select();
                //dump($list);die();
                if (count($list) > 0) {
                    $arr = [];
                    foreach($list as $val){
                        $arr[$val['id']] = $val['odds'];
                        //$arr[] = $arr;
                    }
                    $redis_name = $type."_".$sum_id."_".$pool_id;
                    redis_hSet("bubble_gift_bag_list",$redis_name,json_encode($arr));
                }
                $this->success(lang('EDIT_SUCCESS'), url('bubbleNew/gift_bag_list'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function edit(){
            $id = input('id');
            $list = Db::name("bubble_gift_bag")->find($id);
            $bubble_type = Db::name("bubble_type")->where("status=1 and type=1")->order("orderno desc")->select();
            $pool = Db::name("bubble_pool")->select();
            $this->assign('pool',$pool);
            $this->assign('bubble_type',$bubble_type);
            $this->assign('list',$list);
            return $this->fetch();
        }

        /*
         * 添加礼物
         * */
        public function gift_list(){
            $id = input('id');
            $list = Db::name("bubble_gift_list")
                ->alias('b')
                ->join('gift g','b.gift_id = g.id')
                ->where("gift_bag_id = ".$id)
                ->order("create_time desc")
                ->field('b.*,g.name as gift_name,g.coin')
                ->paginate(10, false, ['query' => request()->param()]);
            $this->assign('list',$list);
            $this->assign('page',$list->render());
            $this->assign('gift_bag_id',$id);
            return $this->fetch();

        }

        public function add_gift(){
            $id = input('gift_bag_id');
            $gift = Db::name("gift")->order("orderno desc")->select();
            $this->assign('gift',$gift);
            $this->assign('gift_bag_id',$id);
            $this->assign('list',[]);
            return $this->fetch();

        }

        public function addGiftPost(){
            $param = $this->request->param();
            $gift_bag_id = $param['gift_bag_id'];
            $data = $param['post'];

            $data['gift_bag_id'] = $gift_bag_id;
            $data['create_time'] = NOW_TIME;

            $result = Db::name("bubble_gift_list")->insert($data);

            if ($result) {

                $this->success(lang('EDIT_SUCCESS'), url('bubbleNew/gift_list',array('id'=>$gift_bag_id)));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function edit_gift(){
            $gift_bag_id = input('gift_bag_id');
            $id = input('id');
            $gift = Db::name("gift")->order("orderno desc")->select();
            $list = Db::name("bubble_gift_list")->find($id);
            $this->assign('gift',$gift);
            $this->assign('gift_bag_id',$gift_bag_id);
            $this->assign('list',$list);
            return $this->fetch();

        }

        public function editGiftPost(){
            $param = $this->request->param();
            $gift_bag_id = $param['gift_bag_id'];
            $id = $param['id'];
            $data = $param['post'];

            $data['gift_bag_id'] = $gift_bag_id;
            $data['create_time'] = NOW_TIME;

            $result = Db::name("bubble_gift_list")->where(['id'=>$id])->update($data);

            if ($result) {

                $this->success(lang('EDIT_SUCCESS'), url('bubbleNew/gift_list',array('id'=>$gift_bag_id)));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }


        public function del(){
            $id = input('id');
            $res = Db::name('playing_bubble_list')->where('id = '.$id)->delete();
            if($res){
                //删除礼物
                Db::name("bubble_gift_list")->where(['gift_bag_id'=>$id])->delete();
            }
            return $res?1:0;
        }

        public function gift_del(){
            $id = input('id');
            $res = Db::name('bubble_gift_list')->where('id = '.$id)->delete();
            return $res?1:0;
        }

        /*
         * 添加额外礼物
         * */
        public function extra_list(){
            //$id = input('id');
            $list = Db::name("bubble_extra_gift")
                ->alias('b')
                ->join('gift g','b.gift_id = g.id')
                ->order("addtime desc")
                ->field('b.*,g.name as gift_name,g.coin')
                ->paginate(10, false, ['query' => request()->param()]);
            $this->assign('data',$list);
            $this->assign('page',$list->render());
            return $this->fetch();

        }

        public function add_extra_list(){
            $gift = Db::name("gift")->order("orderno desc")->select();
            $this->assign('gift',$gift);
            $this->assign('list',[]);
            return $this->fetch();

        }

        public function addExtraListPost(){
            $param = $this->request->param();
            $data = $param['post'];

            $data['addtime'] = NOW_TIME;

            $result = Db::name("bubble_extra_gift")->insert($data);

            if ($result) {

                $this->success(lang('EDIT_SUCCESS'), url('bubbleNew/extra_list'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function edit_extra_list(){
            $id = input('id');
            $gift = Db::name("gift")->order("orderno desc")->select();
            $list = Db::name("bubble_extra_gift")->find($id);
            $this->assign('gift',$gift);
            $this->assign('list',$list);
            return $this->fetch();

        }

        public function editExtraListPost(){
            $param = $this->request->param();
            $gift_bag_id = $param['gift_bag_id'];
            $id = $param['id'];
            $data = $param['post'];
            $data['addtime'] = NOW_TIME;

            $result = Db::name("bubble_extra_gift")->where(['id'=>$id])->update($data);

            if ($result) {

                $this->success(lang('EDIT_SUCCESS'), url('bubbleNew/extra_list',array('id'=>$gift_bag_id)));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function extra_del(){
            $id = input('id');
            $res = Db::name('bubble_extra_gift')->where('id = '.$id)->delete();
            return $res?1:0;
        }

        //奖池
        public function pool_index()
        {
            $car = Db::name("bubble_pool")->where('type = 1')->paginate(10);
            $list = $car->toArray();
            foreach($list['data'] as $k=>$v){
                $pool = Db::name("bubble_pool")->where('type = 1')->find($v['next_pool']);
                $list['data'][$k]['next_pool'] = '';
                if($pool){
                    $list['data'][$k]['next_pool'] = $pool['name'];
                }
            }
            //dump($list);
            $this->assign('data', $list['data']);
            $this->assign('page', $car->render());
            return $this->fetch();
        }

        public function pool_add()
        {
            $param = $this->request->param();
            $whitelist = [];
            if (isset($param['id'])) {
                $whitelist = Db::name("bubble_pool")->where('type = 1')->find($param['id']);

            }else{
                $check = '';
                $checkcount = 0;
            }
            $list = Db::name("bubble_pool")->where('type = 1')->select();

            $this->assign('list', $list);
            $this->assign('data', $whitelist);
            return $this->fetch();
        }

        public function poolAddPost()
        {
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];

            $data['addtime'] = time();

            if ($id) {
                $result = Db::name("bubble_pool")->where('type = 1')->where("id=$id")->update($data);
            } else {
                $result = Db::name("bubble_pool")->insert($data);
            }
            if ($result) {
                //奖池
                $pool = Db::name('bubble_pool')->where('type = 1')->order('orderno')->select();
                // 获取abcd奖池
                redis_hSet("user_bubble_pool_list",'bubble_pool', json_encode($pool));
                $this->success(lang('EDIT_SUCCESS'), url('Bubble_new/pool_index'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function upd()
        {
            $param = request()->param();
            $data = '';
            foreach ($param['listorders'] as $k => $v) {
                $status = Db::name("bubble_pool")->where("id=$k")->update(array('orderno' => $v));
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

        public function pool_del(){
            $param = request()->param();
            $result = Db::name("bubble_pool")->where("id=" . $param['id'])->delete();
            if($result){
                Db::name("bubble_gift_bag")->where("pool_id=" . $param['id'])->delete();
            }
            return $result ? '1' : '0';
            exit;
        }

        //指定用户的列表
        public function named_user(){
            $uid=input('request.uid');
            $where="is_named_user =1";
            $where.= $uid ? " and id =".$uid: '';

            $list = Db::name('user')->field("*")->where($where)->order("last_login_time desc")->paginate(10, false, ['query' => request()->param()]);
            $data = $list->toArray();
            $page = $list->render();

            $this->assign('list', $data['data']);
            $this->assign('page', $page);
            return $this->fetch();
        }
        //解除指定用户
        public function upd_named_user(){
            $param = request()->param();
            $type = $param['type'] ? $param['type'] : 0;
            $uid = $param['uid'];
            $data=array(
                'is_named_user' =>$type
            );
            $results = Db::name('user')->where("id=".$uid)->update($data);
            return $results ? '1' : '0';exit;
        }

        // 连续打泡泡的次数列表
        public function continuous_number(){

            $list = Db::name("bubble_type")->where("type=1")->order("orderno desc")->select();

            $this->assign('list', $list);
            return $this->fetch();
        }
        // 增加连续打泡泡的次数列表
        public function add_continuous_number(){
            $id = input('param.id');
            if($id){
                $list = Db::name("bubble_type")->where("type=1")->where("id=".$id)->order("orderno desc")->find();
            }else{
                $list['type'] =  0;
                $list['status'] =  1;
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
        // 提交连续打泡泡次数
        public function add_continuous_number_post(){
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            if(!$data['sum']){
                $this->error(lang('Please_enter_number_hits'));
            }

            $data['orderno']=  $data['orderno'] ?  $data['orderno'] : 0;
            if ($id) {
                $result = Db::name("bubble_type")->where("id=$id")->update($data);
            } else {
                $result = Db::name("bubble_type")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('bubble/continuous_number'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        /*
         * 奖励礼物包
         * */
        public function pool_user(){
            if (!input('request.page')) {
                $data['uid']=0;
                $data['keyword']=0;
                session('bubble_gift', $data);
            }
            if (input('request.uid') || input('request.keyword')) {
                $data['uid']=input('request.uid') ? input('request.uid') :0;
                $data['keyword']=input('request.keyword') ? input('request.keyword') :0;
                session('bubble_gift', $data);
            }
            $uid=session('bubble_gift.uid') >0 ? session('bubble_gift.uid') :'';
            $user_nickname=session('bubble_gift.keyword') >0 ? session('bubble_gift.keyword') :'';
            $where = [];
            if($uid){
                $where['g.uid'] = $uid;
            }
            if($user_nickname){
                $where['u.user_nickname'] = ['like','%'.$user_nickname.'%'];
            }
            $id = input('id');
            $list = db('user_game_consumption')
                ->alias('g')
                ->join('user u','u.id=g.uid')
                ->where('g.pool_id = '.$id)
                ->where($where)
                ->field('g.*,u.user_nickname')
                ->paginate(10, false, ['query' => request()->param()]);
            $arr = $list->toArray();
            foreach ($arr['data'] as $k=>$v){
                switch ($v['pool_id']){
                    case 1:
                        $arr['data'][$k]['income'] = $v['pool_coin1'];
                        $arr['data'][$k]['coin'] = $v['pool_money1'];
                        break;
                    case 2:
                        $arr['data'][$k]['income'] = $v['pool_coin2'];
                        $arr['data'][$k]['coin'] = $v['pool_money2'];
                        break;
                    case 5:
                        $arr['data'][$k]['income'] = $v['pool_coin3'];
                        $arr['data'][$k]['coin'] = $v['pool_money3'];
                        break;
                    case 6:
                        $arr['data'][$k]['income'] = $v['pool_coin4'];
                        $arr['data'][$k]['coin'] = $v['pool_money4'];
                        break;
                    default:
                        $arr['data'][$k]['income'] = $v['pool_coin5'];
                        $arr['data'][$k]['coin'] = $v['pool_money5'];
                        break;
                }
            }
            $this->assign('list', $arr['data']);
            $this->assign('id', $id);
            $this->assign('page', $list->render());
            $this->assign('request', session('bubble_gift'));
            return $this->fetch();
        }
    }
