<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2021-01-15
 * Time: 16:06
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class GameBoxController extends AdminBaseController
{
    /**
     * 列表
     */
    public function index()
    {
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
        $where.= $type ? " and e.type=". $type:'';
        $where.= $pool_id ? " and e.pool_id=". $pool_id:'';

        $list = Db::name('game_box_gift_list')->alias("e")
            ->join('game_box_type t', 't.id = e.continuous_id')
            ->join('gift f', 'f.id = e.gift_id')
            ->join('game_box_pool p', 'p.id = e.pool_id')
            ->field("e.*,f.name as gift,f.coin,t.sum as tsum,p.name as pname")
            ->where($where)
            ->order("e.sort desc")
            ->paginate(10, false, ['query' => request()->param()]);
        $data = $list->toArray();
        $page = $list->render();
        $sum = Db::name('game_box_gift_list')->alias("e")
            ->join('gift f', 'f.id = e.gift_id')
            ->field("sum(e.odds*f.coin) as count,sum(e.odds) as odds")
            ->where($where)
            ->find();

        $sum['average']  =   $sum['count'] > 0 && $sum['odds'] > 0 ? round($sum['count']/$sum['odds']) : 0 ;
        $pool = Db::name('game_box_pool')->where('type = 2')->order('orderno')->select();

        $this->assign('statistical', $sum);
        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('gift', $gift);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('pool', $pool);
        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $id = input('param.id');

        $bubble_type = Db::name("game_box_type")->where("status=1 and type=1")->order("orderno desc")->select();
        $gift = Db::name("gift")->order("orderno desc")->select();
        if ($id) {
            $list = Db::name("game_box_gift_list")->where("id=$id")->find();
        }else{
            $list['type']=1;
            $list['is_system_push']=1;
            $list['is_male_screen']=1;
            $list['is_all_notify']=1;
            $list['continuous_id']=0;
            $list['is_rank']=1;
            $list['pool_id']=1;

            $list['gift_id']=count($gift) >0 ? $gift[0]['id']:0;
        }
        $pool = Db::name('game_box_pool')->where('type = 2')->order('orderno')->select();
        $this->assign('list', $list);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('gift', $gift);
        $this->assign('pool', $pool);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];

        if(intval($data['room_divide_info']) > 1){
            $this->error(lang('Room_commission_error'));
        }
        $data['arrival_times'] = $data['odds'];
        $data['colors']=$data['colors'] ? $data['colors'] :'#000000';
        $data['sum']=intval($data['sum']) ? intval($data['sum']) :1;
        $data['sort']=intval($data['sort']) ? intval($data['sort']) :0;

        if ($id) {
            $result = Db::name("game_box_gift_list")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_box_gift_list")->insert($data);
        }
        if ($result) {


            redis_hSet("user_game_box_list",$data['type']."_".$data['continuous_id']."_".$data['pool_id'],json_encode(array()));
            redis_hSet("user_game_box_list_spare",$data['type']."_".$data['continuous_id']."_".$data['pool_id'],json_encode(array()));
            $user_pool = db('game_box_user_pool')->where('continuous_id = '.$data['continuous_id'].' and pool_id = '.$data['pool_id'])->select();
            /*$bubble_list = db('playing_bubble_list')->where('continuous_id = '.$data['continuous_id'].' and pool_id = '.$data['pool_id'])->select();*/
            $bubble_list = db('game_box_gift_list')
                ->alias('i')
                ->join('gift g', 'g.id = i.gift_id')
                ->field('i.*,g.img,g.coin,g.name')
                ->where('i.continuous_id = '.$data['continuous_id'].' and i.pool_id = '.$data['pool_id'])
                ->select();
            //dump($user_pool);die();
            if($user_pool){
                foreach($user_pool as $val){
                    $user_pool_data = [
                        'pool'=>json_encode($bubble_list,true)
                    ];
                    db('game_box_user_pool')
                        ->where('id = '.$val['id'])
                        ->update($user_pool_data);
                }

            }
            $this->success(lang('EDIT_SUCCESS'), url('GameBox/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $bubble_one = Db::name("game_box_gift_list")->where("id=" . $param['id'])->find();
        if(!$bubble_one){
            return '0';exit;
        }
        $result = Db::name("game_box_gift_list")->where("id=" . $bubble_one['id'])->delete();

        if ($result) {

            redis_hSet("user_game_box_list",$bubble_one['type']."_".$bubble_one['continuous_id']."_".$bubble_one['pool_id'],json_encode(array()));
            redis_hSet("user_game_box_list_spare",$bubble_one['type']."_".$bubble_one['continuous_id']."_".$bubble_one['pool_id'],json_encode(array()));

        }
        return $result ? '1' : '0';
        exit;
    }

    //礼物记录
    public function eggs_log(){
        if (!input('request.page')) {
            $data['type']=0;
            $data['gift_id']=0;
            $data['uid']='';
            session('bubble_eggs_log', $data);
        }
        if (input('request.type') || input('request.gift_id') || input('request.uid') || input('request.cycles')) {
            $data['type']=input('request.type') ? input('request.type') :0;
            $data['gift_id']=input('request.gift_id') ? input('request.gift_id') :0;
            $data['uid']=input('request.uid') ? input('request.uid') :'';
            $data['cycles']=input('request.cycles') ? input('request.cycles') :'';
            session('bubble_eggs_log', $data);
        }
        $type=session('bubble_eggs_log.type') >0 ? session('bubble_eggs_log.type') :'';
        $gift_id=session('bubble_eggs_log.gift_id') >0 ? session('bubble_eggs_log.gift_id') :'';
        $uid=session('bubble_eggs_log.uid') > 0 ? session('bubble_eggs_log.uid') :'';
        $cycles=session('bubble_eggs_log.cycles') > 0 ? session('bubble_eggs_log.cycles') :'';

        $gift = Db::name("gift")->order("orderno desc")->select();

        $where="e.id >0";
        $where.= $type ? " and e.type=". $type:'';
        $where.= $gift_id ? " and e.gift_id=". $gift_id:'';
        $where.= $uid ? " and e.uid=". $uid:'';
        $where.= $cycles ? " and e.cycles=". $cycles:'';
        $list = Db::name('game_box_log')->alias("e")
            ->field("e.uid,e.type,e.sum,e.expend,e.voice_user_id,e.cycles,e.addtime,e.gift_id,e.only")
            ->where($where)
            ->order("e.addtime desc")
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();

        foreach ($data['data'] as &$v) {
            $v['gift'] = Db::name("gift")->where("id=".$v['gift_id'])->value("name");
            $v['user_nickname'] = Db::name("user")->where("id=".$v['uid'])->value("user_nickname");
            $v['aname'] = Db::name("user")->where("id=".$v['voice_user_id'])->value("user_nickname");
        }
        $page = $list->render();

        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('gift', $gift);
        $this->assign('request', session('bubble_eggs_log'));
        return $this->fetch();
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

    //清除缓存
    public function clear_both_voice_gift(){
        redis_hDelOne("user_voice_gift",1);
        redis_hDelOne("user_voice_gift",2);
        return '1' ;exit;
    }

    //  打泡泡奖励礼物记录表
    public function bubble_bonus(){
        if (!input('request.page')) {
            $data['gift_id']=0;
            session('bubble_bonus', $data);
        }
        if (input('request.uid') || input('request.gift_id')) {
            $data['uid']=input('request.uid') ? input('request.uid') : '';
            $data['gift_id']=input('request.gift_id') ? input('request.gift_id') :0;
            session('bubble_bonus', $data);
        }
        $uid=session('bubble_bonus.uid') >0 ? session('bubble_bonus.uid') :'';
        $gift_id=session('bubble_bonus.gift_id') >0 ? session('bubble_bonus.gift_id') :'';

        $gift = Db::name("gift")->order("orderno desc")->select();

        $where="e.id >0";
        $where.= $gift_id ? " and e.gift_id=". $gift_id:'';
        $where.= $uid ? " and e.uid=". $uid:'';
        $list = Db::name('bubble_bonus_log')->alias("e")
            ->join('playing_bubble_list l', 'l.id = e.bubble_id')
            ->join('gift f', 'f.id = l.gift_id')
            ->join('user u', 'u.id = e.uid')
            ->field("e.*,f.name as gift,f.coin,u.user_nickname,l.sum,l.is_system_push,l.is_male_screen,l.is_all_notify")
            ->where($where)
            ->paginate(10, false, ['query' => request()->param()]);
        $data = $list->toArray();
        $page = $list->render();

        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('gift', $gift);
        $this->assign('request', session('bubble_bonus'));
        return $this->fetch();
    }
    // 添加 打泡泡奖励礼物记录表
    public function add_bubble_bonus(){
        $id = input('param.id');
        $bubble_list = Db::name('playing_bubble_list')->alias("l")
            ->join('gift f', 'f.id = l.gift_id')
            ->field("f.name,l.id")
            ->where("l.type=1")
            ->order("l.sort desc")
            ->select();
        if ($id) {
            $list = Db::name('bubble_bonus_log')->where("id=$id")->find();
        }else{
            $list['bubble_id']=0;
        }
        $this->assign('list', $list);

        $this->assign('bubble_list', $bubble_list);
        return $this->fetch();
    }
    // 修改 打泡泡奖励礼物记录表
    public function add_bubble_bonus_post(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if(!$data['uid']){
            $this->error(lang('Please_enter_user_ID'));
        }
        $user = Db::name("user")->where("id=".$data['uid'])->find();
        if(!$user){
            $this->error(lang('user_does_not_exist'));
        }

        $data['addtime']= NOW_TIME;
        if ($id) {
            $result = Db::name("bubble_bonus_log")->where("id=$id")->update($data);
        } else {
            $result = Db::name("bubble_bonus_log")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('bubble/bubble_bonus'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    //删除
    public function del_bubble_bonus()
    {
        $param = request()->param();
        $result = Db::name("bubble_bonus_log")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }
    // 连续次数列表
    public function continuous_number(){

        $list = Db::name("game_box_type")->where("type=1")->order("orderno desc")->select();

        $this->assign('list', $list);
        return $this->fetch();
    }
    // 增加连续打泡泡的次数列表
    public function add_continuous_number(){
        $id = input('param.id');
        if($id){
            $list = Db::name("game_box_type")->where("type=1")->where("id=".$id)->order("orderno desc")->find();
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
            $result = Db::name("game_box_type")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_box_type")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('game_box/continuous_number'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //奖池
    public function pool_index()
    {
        $car = Db::name("game_box_pool")->where('type = 2')->paginate(10);
        $list = $car->toArray();
        foreach($list['data'] as $k=>$v){
            $pool = Db::name("game_box_pool")->find($v['next_pool']);
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
            $whitelist = Db::name("game_box_pool")->where('type = 2')->find($param['id']);

        }else{
            $whitelist['pool_type'] = 0;
            $whitelist['next_pool'] = 0;
        }
        $list = Db::name("game_box_pool")->where('type = 2')->select();

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
        $data['type'] = 2;

        if ($id) {
            $result = Db::name("game_box_pool")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_box_pool")->insert($data);
        }
        if ($result) {
            //奖池
            $pool = Db::name('game_box_pool')->order('orderno')->select();
            // 获取abcd奖池
            redis_hSet("user_bubble_pool_list",'bubble_pool', json_encode($pool));
            $this->success(lang('EDIT_SUCCESS'), url('game_box/pool_index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    public function upd()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("game_box_pool")->where("id=$k")->update(array('orderno' => $v));
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
        $result = Db::name("game_box_pool")->where("id=" . $param['id'])->delete();
        if($result){
            $bubble_one = Db::name("game_box_gift_list")->where("pool_id=" . $param['id'])->find();
            Db::name("game_box_gift_list")->where("pool_id=" . $param['id'])->delete();

            redis_hSet("user_bubble_list",$bubble_one['type']."_".$bubble_one['continuous_id']."_".$bubble_one['pool_id'],json_encode(array()));
            redis_hSet("user_bubble_list_spare",$bubble_one['type']."_".$bubble_one['continuous_id']."_".$bubble_one['pool_id'],json_encode(array()));
        }
        return $result ? '1' : '0';
        exit;
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
        $where['g.type'] = 3;
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
            $info = db('game_box_pool_log')->where(['uid'=>$v['uid'],'pool_id'=>$v['pool_id']])->find();
            $arr['data'][$k]['income'] = $info['prize'];
            $arr['data'][$k]['coin'] = $info['money'];
        }
        $pool = db('bubble_pool')->find($id);
        $this->assign('list', $arr['data']);
        $this->assign('id', $id);
        $this->assign('pool', $pool);
        $this->assign('page', $list->render());
        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    /*
     * 个人奖池*/
    public function user_pool(){

        if (!input('request.page')) {
            $data['uid']=0;
            $data['continuous_id']=0;
            $data['pool_id']=0;
            session('bubble_gift', $data);
        }
        if (input('request.uid') || input('request.pool_id') || input('request.continuous_id')) {
            $data['uid']=input('request.uid') ? input('request.uid') :0;
            $data['pool_id']=input('request.pool_id') ? input('request.pool_id') :0;
            $data['continuous_id']=input('request.continuous_id') ? input('request.continuous_id') :0;
            session('bubble_gift', $data);
        }
        $uid=session('bubble_gift.uid') >0 ? session('bubble_gift.uid') :'';
        $pool_id=session('bubble_gift.pool_id') >0 ? session('bubble_gift.pool_id') :'';
        $continuous_id=session('bubble_gift.continuous_id') >0 ? session('bubble_gift.continuous_id') :'';


        $bubble_type = Db::name("game_box_type")->where("type=1")->order("orderno desc")->select();
        $bubble_pool = Db::name("game_box_pool")->where("type = 2 and pool_type = 2")->order("orderno desc")->select();

        $where="b.id >0";
        $where.= $uid ? " and b.uid=". $uid:'';
        $where.= $continuous_id ? " and b.continuous_id=". $continuous_id:'';
        $where.= $pool_id ? " and b.pool_id=". $pool_id:'';

        $list = db('game_box_user_pool')
            ->alias('b')
            ->join('user u','u.id=b.uid')
            ->join('game_box_pool p','p.id=b.pool_id')
            ->join('game_box_type t','t.id=b.continuous_id')
            ->where($where)
            ->field('b.*,u.user_nickname,p.name as pname,t.sum as continuous')
            ->paginate(10, false, ['query' => request()->param()]);
        /*->find();*/

        $this->assign('pool', $bubble_pool);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('page', $list->render());
        $this->assign('list', $list);
        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    public function user_pool_gift(){

        $bubble_type = Db::name("game_box_type")->where("type=1")->order("orderno desc")->select();
        $bubble_pool = Db::name("game_box_pool")->where("type = 2 and pool_type = 2")->order("orderno desc")->select();
        $uid = input('uid');
        $continuous_id = input('continuous_id');
        $pool_id = input('pool_id');

        $where="b.id >0";
        $where.= $uid ? " and b.uid=". $uid:'';
        $where.= $continuous_id ? " and b.continuous_id=". $continuous_id:'';
        $where.= $pool_id ? " and b.pool_id=". $pool_id:'';

        $info = db('game_box_user_pool')
            ->alias('b')
            ->where($where)
            ->find();

        $list = json_decode($info['pool'],true);
        foreach($list as &$v){
            $pool = Db::name("game_box_pool")->find($v['pool_id']);
            $continuous = Db::name("bubble_type")->find($v['continuous_id']);
            $v['pname'] = $pool['name'];
            $v['continuous'] = $continuous['sum'];
        }
        //dump($list);die();
        //$this->assign('id', $id);
        $this->assign('pool', $bubble_pool);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('page', '');
        $this->assign('list', $list);
        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    /*
     * 奖励补偿*/
    public function make_up()
    {
        $car = Db::name("game_box_make_up")
            ->alias('b')
            ->join('user u','u.id=b.uid')
            ->join('gift g','g.id=b.gift_id')
            ->field('b.*,g.name gift_name,u.user_nickname')
            ->paginate(10);
        $list = $car->toArray();

        //dump($list);
        $this->assign('data', $list['data']);
        $this->assign('page', $car->render());
        return $this->fetch();
    }

    public function add_make_up()
    {
        $param = $this->request->param();
        $whitelist = [];
        if (isset($param['id'])) {
            $whitelist = Db::name("game_box_make_up")->where('type = 2')->find($param['id']);

        }else{
            $whitelist = [
                'gift_id'=>0,
                'type'=>0,
            ];
        }
        $gift = Db::name("gift")->order("orderno desc")->select();

        $this->assign('list', $gift);
        $this->assign('data', $whitelist);
        return $this->fetch();
    }

    public function makeAddPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];

        $data['addtime'] = time();

        if ($id) {
            $result = Db::name("game_box_make_up")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_box_make_up")->insert($data);
        }
        if ($result) {
            $list = Db::name("game_box_make_up")
                ->where(['uid'=>$data['uid']])
                ->where(['status'=>0])
                ->select();
            redis_hSet("user_bubble_make_up",$data['uid'],json_encode($list));
            $this->success(lang('EDIT_SUCCESS'), url('Bubble/make_up'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }


    public function make_del(){
        $param = request()->param();
        $result = Db::name("game_box_make_up")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }
}
