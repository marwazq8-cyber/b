<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2021-02-18
 * Time: 11:24
 * Name: 代充
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class GameBoxController extends AdminBaseController
{
    /*
     * 奖品礼物列表*/
    public function index()
    {
        if (!input('request.page')) {
            $data['gift_id']=0;
            $data['type']=0;
            $data['continuous_id']=0;
            $data['pool_id']=0;
            $data['box_type']=0;
            session('bubble_gift', $data);
        }
        if (input('request.type') || input('request.gift_id') || input('request.continuous_id') || input('request.pool_id') || input('request.box_type')) {
            $data['type']=input('request.type') ? input('request.type') :0;
            $data['gift_id']=input('request.gift_id') ? input('request.gift_id') :0;
            $data['continuous_id']=input('request.continuous_id') ? input('request.continuous_id') :0;
            $data['pool_id']=input('request.pool_id') ? input('request.pool_id') :0;
            $data['box_type']=input('request.box_type') ? input('request.box_type') :0;
            session('bubble_gift', $data);
        }
        $type=session('bubble_gift.type') >0 ? session('bubble_gift.type') :'';
        $gift_id=session('bubble_gift.gift_id') >0 ? session('bubble_gift.gift_id') :'';
        $continuous_id=session('bubble_gift.continuous_id') >0 ? session('bubble_gift.continuous_id') :'';
        $pool_id=session('bubble_gift.pool_id') >0 ? session('bubble_gift.pool_id') :'';
        $box_type=session('bubble_gift.box_type') >0 ? session('bubble_gift.box_type') :'';

        $gift = Db::name("gift")->order("orderno desc")->select();

        $bubble_type = Db::name("game_box_type")->where("type=1")->order("orderno desc")->select();

        $where="e.id >0";
        $where.= $gift_id ? " and e.gift_id=". $gift_id:'';
        $where.= $continuous_id ? " and e.continuous_id=". $continuous_id:'';
        $where.= $type ? " and e.type=". $type:'';
        $where.= $pool_id ? " and e.pool_id=". $pool_id:'';
        $where.= $box_type ? " and e.box_id=". $box_type:'';

        $list = Db::name('playing_bubble_list')->alias("e")
            //->join('bubble_type t', 't.id = e.continuous_id')
            ->join('gift f', 'f.id = e.gift_id and f.is_delete=0')
            ->join('bubble_pool p', 'p.id = e.pool_id')
            ->join('game_box_list b', 'b.id = e.box_id')
            ->field("e.*,f.name as gift,f.coin,p.name as pname,b.name as bname")
            ->where($where)
            ->order("e.box_id,e.pool_id,e.sort desc")
            ->paginate(10, false, ['query' => request()->param()]);
        $data = $list->toArray();
        $page = $list->render();
        $sum = Db::name('playing_bubble_list')->alias("e")
            ->join('gift f', 'f.id = e.gift_id and f.is_delete=0')
            ->field("sum(e.odds*f.coin) as count,sum(e.odds) as odds")
            ->where($where)
            ->find();

        $sum['average']  =   $sum['count'] > 0 && $sum['odds'] > 0 ? round($sum['count']/$sum['odds']) : 0 ;
        $pool = Db::name('bubble_pool')->where('type = 2')->order('orderno')->select();
        $game_box_type = Db::name('game_box_list')->where('status = 1')->order("orderno")->select();

        $this->assign('statistical', $sum);
        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('gift', $gift);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('pool', $pool);
        $this->assign('game_box_type', $game_box_type);
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
        $game_box_type = Db::name('game_box_list')->where('status = 1')->order("orderno")->select();
        $gift = Db::name("gift")->where("is_delete=0")->order("orderno desc")->select();
        if ($id) {
            $list = Db::name("playing_bubble_list")->where("id=$id")->find();
        }else{
            $list['type']=1;
            $list['is_system_push']=1;
            $list['is_male_screen']=1;
            $list['is_all_notify']=1;
            $list['continuous_id']=0;
            $list['is_rank']=1;
            $list['pool_id']=1;
            $list['box_type']=1;
            $list['box_id']=1;

            $list['gift_id']=count($gift) >0 ? $gift[0]['id']:0;
        }
        $pool = Db::name('bubble_pool')->where('type = 2')->order('orderno')->select();
        $this->assign('list', $list);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('gift', $gift);
        $this->assign('pool', $pool);
        $this->assign('game_box_type', $game_box_type);
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
        //$data['colors']=$data['colors'] ? $data['colors'] :'#000000';
        $data['sum']=intval($data['sum']) ? intval($data['sum']) :1;
        $data['sort']=intval($data['sort']) ? intval($data['sort']) :0;

        if ($id) {
            $result = Db::name("playing_bubble_list")->where("id=$id")->update($data);
        } else {
            $result = Db::name("playing_bubble_list")->insert($data);
        }
        if ($result) {
            //$where =  "i.type=". $data['type']." and i.continuous_id=".$data['continuous_id'];

            /*$list = db('playing_bubble_list')->alias('i')
                            ->join('gift g', 'g.id = i.gift_id')
                            ->field('i.*,g.img,g.coin,g.name')
                            ->where($where)
                            ->order("sort desc")
                            ->select();*/
            $redis_name = $data['type']."_".'box'."_".$data['pool_id'].'_'.$data['box_id'];
            redis_hSet("user_game_box_list",$redis_name,json_encode(array()));
            redis_hSet("user_game_box_list_spare",$redis_name,json_encode(array()));

            $user_pool = db('bubble_user_pool')->where('box_id = '.$data['box_id'].' and pool_id = '.$data['pool_id'])->select();
            /*$bubble_list = db('playing_bubble_list')->where('continuous_id = '.$data['continuous_id'].' and pool_id = '.$data['pool_id'])->select();*/
            $bubble_list = db('playing_bubble_list')
                ->alias('i')
                ->join('gift g', 'g.id = i.gift_id and g.is_delete=0')
                ->field('i.*,g.img,g.coin,g.name')
                ->where('box_id = '.$data['box_id'].' and i.pool_id = '.$data['pool_id'])
                ->select();
            //dump($user_pool);die();
            if($user_pool){
                foreach($user_pool as $val){
                    $redis_name_uid = $data['type']."_".'box'."_".$data['pool_id'].'_'.$data['box_id']."_".$val['uid'];
                    redis_hSet("user_game_box_list",$redis_name_uid,json_encode(array()));
                    redis_hSet("user_game_box_list_spare",$redis_name_uid,json_encode(array()));
                    $user_pool_data = [
                        'pool'=>json_encode($bubble_list,true)
                    ];
                    db('bubble_user_pool')
                        ->where('id = '.$val['id'])
                        ->update($user_pool_data);
                }

            }
            $this->success(lang('EDIT_SUCCESS'), url('game_box/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $bubble_one = Db::name("playing_bubble_list")->where("id=" . $param['id'])->find();
        if(!$bubble_one){
            return '0';exit;
        }
        $result = Db::name("playing_bubble_list")->where("id=" . $bubble_one['id'])->delete();

        if ($result) {

            $data = $bubble_one;
            $redis_name = $data['type']."_".'box'."_".$data['pool_id'].'_'.$data['box_id'];
            redis_hSet("user_game_box_list",$redis_name,json_encode(array()));
            redis_hSet("user_game_box_list_spare",$redis_name,json_encode(array()));

            $user_pool = db('bubble_user_pool')->where('box_id = '.$data['box_id'].' and pool_id = '.$data['pool_id'])->select();
            /*$bubble_list = db('playing_bubble_list')->where('continuous_id = '.$data['continuous_id'].' and pool_id = '.$data['pool_id'])->select();*/
            $bubble_list = db('playing_bubble_list')
                ->alias('i')
                ->join('gift g', 'g.id = i.gift_id and g.is_delete=0')
                ->field('i.*,g.img,g.coin,g.name')
                ->where('box_id = '.$data['box_id'].' and i.pool_id = '.$data['pool_id'])
                ->select();
            //dump($user_pool);die();
            if($user_pool){
                foreach($user_pool as $val){
                    $redis_name_uid = $data['type']."_".'box'."_".$data['pool_id'].'_'.$data['box_id']."_".$val['uid'];
                    redis_hSet("user_game_box_list",$redis_name_uid,json_encode(array()));
                    redis_hSet("user_game_box_list_spare",$redis_name_uid,json_encode(array()));
                    $user_pool_data = [
                        'pool'=>json_encode($bubble_list,true)
                    ];
                    db('bubble_user_pool')
                        ->where('id = '.$val['id'])
                        ->update($user_pool_data);
                }

            }

        }
        return $result ? '1' : '0';
        exit;
    }

    //礼物记录
    public function eggs_log(){
        if (!input('request.page')) {
            $data['type']=0;
            $data['gift_id']=0;
            $data['box_id']=0;
            $data['uid']='';
            $data['date_type']= '';
            session('bubble_eggs_log', $data);
        }
        if (input('request.box_id') || input('request.date_type') || input('request.only') || input('request.pool_id') || input('request.voice_id') || input('request.gift_id') || input('request.uid') || input('request.cycles') || input('request.start_time') || input('request.end_time')) {
            $data['box_id']=input('request.box_id') ? input('request.box_id') :0;
            $data['date_type']=input('request.date_type') ? input('request.date_type') : '';
            $data['voice_id']=input('request.voice_id') ? input('request.voice_id') : '';
            $data['only']=input('request.only') ? input('request.only') : '';
            $data['pool_id']=input('request.pool_id') ? input('request.pool_id') : '';
            $data['gift_id']=input('request.gift_id') ? input('request.gift_id') :0;
            $data['uid']=input('request.uid') ? input('request.uid') :'';
            $data['cycles']=input('request.cycles') ? input('request.cycles') :'';
            $data['start_time']=input('request.start_time') ? input('request.start_time') :'';
            $data['end_time']=input('request.end_time') ? input('request.end_time') :'';
            session('bubble_eggs_log', $data);
        }
        if(session('bubble_eggs_log.date_type')){
            //今日、昨日、本周、上周 处理
            $search_time = select_date_type(session('bubble_eggs_log.date_type'));
            session('bubble_eggs_log.start_time', date('Y-m-d',$search_time['start_time']));
            session('bubble_eggs_log.end_time', date('Y-m-d',$search_time['end_time']));
        }

        $box_id=session('bubble_eggs_log.box_id') >0 ? session('bubble_eggs_log.box_id') :'';
        $voice_id=session('bubble_eggs_log.voice_id') >0 ? session('bubble_eggs_log.voice_id') :'';
        $only=session('bubble_eggs_log.only') >0 ? session('bubble_eggs_log.only') :'';
        $pool_id=session('bubble_eggs_log.pool_id') >0 ? session('bubble_eggs_log.pool_id') :'';
        $gift_id=session('bubble_eggs_log.gift_id') >0 ? session('bubble_eggs_log.gift_id') :'';
        $uid=session('bubble_eggs_log.uid') > 0 ? session('bubble_eggs_log.uid') :'';
        $cycles=session('bubble_eggs_log.cycles') > 0 ? session('bubble_eggs_log.cycles') :'';
        $starttime=session('bubble_eggs_log.start_time') > 0 ? session('bubble_eggs_log.start_time') :'';
        $endtime=session('bubble_eggs_log.end_time') > 0 ? session('bubble_eggs_log.end_time') :'';

        $where="e.id >0";

        $where .= $box_id ? " and e.box_id=" . intval($box_id) : '';
        $where .= $only ? " and e.only='" . $only . "'" : '';
        $where .= $voice_id ? " and e.voice_id=" . intval($voice_id) : '';
        $where .= $pool_id ? " and e.pool_id=" . intval($pool_id) : '';
        $where .= $gift_id ? " and e.gift_id=" . intval($gift_id) : '';
        $where .= $uid ? " and e.uid=" . intval($uid) : '';
        $where .= $cycles ? " and e.cycles=" . intval($cycles) : '';
        $where .= $starttime ? " and e.addtime >=" . strtotime($starttime) : '';
        $where .= $endtime ? " and e.addtime <" . strtotime($endtime) : '';

        //e.expend
        $list = Db::name('game_box_log')->alias("e")
            ->field("e.voice_id,e.uid,e.type,e.sum,e.voice_user_id,e.cycles,e.addtime,e.gift_id,e.only,e.box_name,e.pool_id,e.voice_id,e.box_id,e.pool_json,e.is_grand_prix")
            ->where($where)
            ->order("e.addtime desc")
            ->paginate(20, false, ['query' => request()->param()]);

        $data = $list->toArray();

        $count = Db::name('game_box_log')
            ->alias("e")
            ->join("gift g","g.id=e.gift_id")
            ->field("sum(e.sum) as sum,g.name")
            ->where($where)
            ->group("e.gift_id")
            ->select();


        foreach ($data['data'] as &$v) {
            $v['gift'] = Db::name("gift")->where("id=".$v['gift_id'])->value("name");
            $v['user_nickname'] = Db::name("user")->where("id=".$v['uid'])->value("user_nickname");
            $user = Db::name('voice')
                ->alias('a')
                ->where('a.id = '.$v['voice_id'])
                ->join('user u','u.id=a.user_id')
                ->field('u.id,u.user_nickname')->find();
            $v['voice_user_id'] = $user['id'];
            $v['aname'] = $user['user_nickname'];

            if ($v['pool_json']){
                $pool_json = json_decode($v['pool_json'],true);
                $pool_json_val = $pool_json['voice_game_box_new_coin']-$pool_json['trigger_bonus_amount'];
                $pool_json['pool_difference'] = $pool_json_val > 0 ? $pool_json_val : '';
                $v['pool_json'] = $pool_json;
            }
            //$v['aname'] = Db::name("user")->where("id=".$v['voice_user_id'])->value("user_nickname");
        }
        $page = $list->render();

        $gift = Db::name("gift")->where("is_delete=0")->order("orderno desc")->select();
        $game_box = Db::name("game_box_list")->order("orderno desc")->select();

        $Statistics = db('game_box_log')->alias('e')->field("sum(e.coin_sum * e.coin_sum_money) as coin_money,sum(e.coin * e.sum) as gift_money")->where($where)->find();

        $this->assign('Statistics', $Statistics);
        $this->assign('count', $count->toArray());
        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('gift', $gift);
        $this->assign('game_box', $game_box);
        $this->assign('request', session('bubble_eggs_log'));
        return $this->fetch();
    }
    //魔法棒规则
    public function magic_wand(){
        $magic_wand = Db::name("magic_wand")->where("type=1")->order("sort desc")->select();
        $this->assign('list', $magic_wand);
        return $this->fetch();
    }
    //添加和修改魔法棒规则
    public function add_magic_wand(){
        $id = input('param.id');
        if ($id) {
            $list = Db::name("magic_wand")->where("id=$id")->find();
        }else{
            $list['id']='';
        }
        $this->assign('list', $list);
        return $this->fetch();
    }
    //提交
    public function add_post_magic_wand(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];

        $data['sort']=intval($data['sort']) ? intval($data['sort']) :0;

        if ($id) {
            $result = Db::name("magic_wand")->where("id=$id")->update($data);
        } else {
            $result = Db::name("magic_wand")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('game_box/magic_wand'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    //删除
    public function del_magic_wand()
    {
        $param = request()->param();
        $result = Db::name("magic_wand")->where("id=" . $param['id'])->delete();
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

    //清除缓存
    public function clear_both_voice_gift(){
        redis_hDelOne("user_voice_gift",1);
        redis_hDelOne("user_voice_gift",2);
        return '1' ;exit;
    }

    //  奖励礼物记录表
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

        $gift = Db::name("gift")->where("is_delete=0")->order("orderno desc")->select();

        $where="e.id >0";
        $where.= $gift_id ? " and e.gift_id=". $gift_id:'';
        $where.= $uid ? " and e.uid=". $uid:'';
        $list = Db::name('bubble_bonus_log')->alias("e")
            ->join('playing_bubble_list l', 'l.id = e.bubble_id')
            ->join('gift f', 'f.id = l.gift_id and f.is_delete=0')
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
    // 添加 奖励礼物记录表
    public function add_bubble_bonus(){
        $id = input('param.id');
        $bubble_list = Db::name('playing_bubble_list')->alias("l")
            ->join('gift f', 'f.id = l.gift_id and f.is_delete=0')
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
    // 修改 奖励礼物记录表
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
            $this->success(lang('EDIT_SUCCESS'), url('game_box/bubble_bonus'));
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
        if (!input('request.page')) {
            $data['status']= '-1';
            $data['box_type']=0;
            session('continuous_number', $data);
        }
        if ( input('request.status') >= '0' || input('request.box_type')) {

            $data['status']=input('request.status') >= '0' ? input('request.status') : '-1';
            $data['box_type']=input('request.box_type') ? input('request.box_type') :0;
            session('continuous_number', $data);
        }

        $status=session('continuous_number.status') >= '0' ? session('continuous_number.status') :'';
        $box_type=session('continuous_number.box_type') >0 ? session('continuous_number.box_type') :'';

        $where="t.id >0";

        $where.= $status >= '0' ? " and t.status=". $status:'';
        $where.= $box_type ? " and t.type=". $box_type:'';

        $list = Db::name("game_box_type")
            ->alias('t')
            ->join('game_box_list b','b.id = t.type')
            ->field('b.name,t.*')
            ->where($where)
            ->order("t.orderno desc")
            ->select();
        $game_box_list=Db::name("game_box_list")->order("orderno")->select();
        $this->assign('game_box_list', $game_box_list);
        $this->assign('list', $list);
        $this->assign('request', session('continuous_number'));

        return $this->fetch();
    }
    // 增加连续打的次数列表
    public function add_continuous_number(){
        $id = input('param.id');
        if($id){
            $list = Db::name("game_box_type")->where("id=".$id)->order("orderno desc")->find();
        }else{
            $list['type'] =  0;
            $list['status'] =  1;
        }
        $game_box_type = db('game_box_list')->where('status = 1')->order('orderno')->select();
        $this->assign('list', $list);
        $this->assign('game_box_type', $game_box_type);
        return $this->fetch();
    }
    // 提交连续次数
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
    // 删除
    public function game_box_type_sum_del(){
        $param = request()->param();

        $result = Db::name("game_box_type")->where("id=" . $param['id'])->delete();

        return $result ? '1' : '0';
        exit;
    }
    //奖池
    public function pool_index()
    {
        $car = Db::name("bubble_pool")->where('type = 2')->paginate(10);
        $list = $car->toArray();
        foreach($list['data'] as $k=>$v){
            $pool = Db::name("bubble_pool")->find($v['next_pool']);
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
            $whitelist = Db::name("bubble_pool")->where('type = 2')->find($param['id']);

        }else{
            $whitelist['pool_type'] = 0;
            $whitelist['next_pool'] = 0;
        }
        $list = Db::name("bubble_pool")->where('type = 2')->select();

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
            $result = Db::name("bubble_pool")->where("id=$id")->update($data);
        } else {
            $result = Db::name("bubble_pool")->insert($data);
        }
        if ($result) {
            //奖池
            $pool = Db::name('bubble_pool')->order('orderno')->select();
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
            $bubble_one = Db::name("playing_bubble_list")->where("pool_id=" . $param['id'])->find();
            Db::name("playing_bubble_list")->where("pool_id=" . $param['id'])->delete();

            redis_hSet("user_bubble_list",$bubble_one['type']."_".$bubble_one['continuous_id']."_".$bubble_one['pool_id'],json_encode(array()));
            redis_hSet("user_bubble_list_spare",$bubble_one['type']."_".$bubble_one['continuous_id']."_".$bubble_one['pool_id'],json_encode(array()));
            $list = db('user_game_consumption')->where('pool_id = '.$param['id'])->select();
            if($list){
                foreach ($list as $v){
                    //清除redis
                    $type = 1;
                    $pool_id=$v['pool_id'];
                    $box_id=$v['box_id'];
                    $uid=$v['uid'];
                    $redis_name = $type."_".'box'."_".$pool_id.'_'.$box_id;
                    $redis_name_uid = $type."_".'box'."_".$pool_id.'_'.$box_id."_".$uid;
                    // 获取中奖的礼物
                    redis_hSet("user_game_box_list",$redis_name,json_encode(array()));
                    redis_hSet("user_game_box_list_spare",$redis_name,json_encode(array()));
                    redis_hSet("user_game_box_list",$redis_name_uid,json_encode(array()));
                    redis_hSet("user_game_box_list_spare",$redis_name_uid,json_encode(array()));
                }
            }
            //删除个人奖池记录
            db('user_game_consumption')->where('pool_id = '.$param['id'])->delete();
            db('bubble_user_pool')->where('pool_id = '.$param['id'])->delete();
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
            ->join('game_box_list t','t.id=g.box_id')
            ->where('g.pool_id = '.$id)
            ->where($where)
            ->field('g.*,u.user_nickname,t.name as t_name')
            ->paginate(10, false, ['query' => request()->param()]);
        $arr = $list->toArray();
        foreach ($arr['data'] as $k=>$v){
            $info = db('bubble_pool_log')->where(['uid'=>$v['uid'],'pool_id'=>$v['pool_id']])->find();
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
        $bubble_pool = Db::name("bubble_pool")->where("type = 2 and pool_type = 2")->order("orderno desc")->select();
        $game_box_type = Db::name('game_box_list')->select();

        $where="b.id >0";
        $where.= $uid ? " and b.uid=". $uid:'';
        $where.= $continuous_id ? " and b.box_id=". $continuous_id:'';
        $where.= $pool_id ? " and b.pool_id=". $pool_id:'';

        $list = db('bubble_user_pool')
            ->alias('b')
            ->join('user u','u.id=b.uid')
            ->join('bubble_pool p','p.id=b.pool_id')
            ->join('game_box_list t','t.id=b.box_id')
            ->where($where)
            ->field('b.*,u.user_nickname,p.name as pname,t.name as tname')//,t.sum as continuous
            ->paginate(10, false, ['query' => request()->param()]);
        /*->find();*/
        //dump($list);
        $this->assign('pool', $bubble_pool);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('page', $list->render());
        $this->assign('list', $list);
        $this->assign('game_box_type', $game_box_type);
        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    public function user_pool_gift(){

        $bubble_type = Db::name("bubble_type")->where("type=1")->order("orderno desc")->select();
        $bubble_pool = Db::name("bubble_pool")->where("type = 2 and pool_type = 2")->order("orderno desc")->select();
        $uid = input('uid');
        $continuous_id = input('continuous_id');
        $pool_id = input('pool_id');

        $where="b.id >0";
        $where.= $uid ? " and b.uid=". $uid:'';
        $where.= $continuous_id ? " and b.continuous_id=". $continuous_id:'';
        $where.= $pool_id ? " and b.pool_id=". $pool_id:'';

        $info = db('bubble_user_pool')
            ->alias('b')
            ->where($where)
            ->find();

        $list = json_decode($info['pool'],true);
        foreach($list as &$v){
            $pool = Db::name("bubble_pool")->find($v['pool_id']);
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
        $car = Db::name("bubble_make_up")
            ->alias('b')
            ->join('user u','u.id=b.uid')
            ->join('gift g','g.id=b.gift_id and g.is_delete=0')
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
            $whitelist = Db::name("bubble_make_up")->where('type = 2')->find($param['id']);

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
            $result = Db::name("bubble_make_up")->where("id=$id")->update($data);
        } else {
            $result = Db::name("bubble_make_up")->insert($data);
        }
        if ($result) {
            $list = Db::name("bubble_make_up")
                ->where(['uid'=>$data['uid']])
                ->where(['status'=>0])
                ->select();
            redis_hSet("user_bubble_make_up",$data['uid'],json_encode($list));
            $this->success(lang('EDIT_SUCCESS'), url('game_box/make_up'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }


    public function make_del(){
        $param = request()->param();
        $result = Db::name("bubble_make_up")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    /*
     * 宝箱类型
     * */
    public function game_box_type(){
        $list = Db::name('game_box_list')
            ->order("orderno desc")
            ->paginate(10, false, ['query' => request()->param()]);
        $data = $list->toArray();
        $page = $list->render();

        $this->assign('list', $data['data']);
        $this->assign('page', $page);

        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    public function add_game_box_type()
    {
        $id = input('param.id');

        if ($id) {
            $list = Db::name("game_box_list")->where("id=$id")->find();
        }else{
            $list['img']=1;
            $list['open_img']=1;
        }
        $this->assign('gift', $list);
        return $this->fetch();
    }

    public function add_game_box_type_post()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = NOW_TIME;

        if ($id) {
            $result = Db::name("game_box_list")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_box_list")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('game_box/game_box_type'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    public function game_box_type_del(){
        $param = request()->param();
        $bubble_one = Db::name("game_box_list")->where("id=" . $param['id'])->find();
        if(!$bubble_one){
            return '0';exit;
        }
        $result = Db::name("game_box_list")->where("id=" . $bubble_one['id'])->delete();

        return $result ? '1' : '0';
        exit;
    }

    public function game_box_type_upd(){
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("game_box_list")->where("id=$k")->update(array('orderno' => $v));
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

    /*
     * 奖池统计记录*/
    public function bubble_pool_log(){
        if (!input('request.page')) {

            $data['uid']='';
            $data['pool_id']=0;
            $data['box_type']=0;
            session('bubble_gift', $data);
        }
        if ( input('request.pool_id') || input('request.box_type') || input('request.uid')) {

            $data['pool_id']=input('request.pool_id') ? input('request.pool_id') :0;
            $data['box_type']=input('request.box_type') ? input('request.box_type') :0;
            $data['uid']=input('request.uid') ? input('request.uid') :0;
            session('bubble_gift', $data);
        }

        $pool_id=session('bubble_gift.pool_id') >0 ? session('bubble_gift.pool_id') :'';
        $box_type=session('bubble_gift.box_type') >0 ? session('bubble_gift.box_type') :'';
        $uid=session('bubble_gift.uid') >0 ? session('bubble_gift.uid') :'';
        $gift = Db::name("gift")->where("is_delete=0")->order("orderno desc")->select();

        $bubble_type = Db::name("bubble_type")->where("type=1")->order("orderno desc")->select();

        $where="l.id >0";

        $where.= $pool_id ? " and l.pool_id=". $pool_id:'';
        $where.= $box_type ? " and l.box_id=". $box_type:'';
        $where.= $uid ? " and l.uid=". $uid:'';

        $list = db('bubble_user_pool_log')
            ->alias('l')
            ->join('bubble_pool p','p.id=l.pool_id')
            ->join('game_box_list g','g.id=l.box_id')
            ->join('user u','u.id=l.uid')
            ->where($where)
            ->field('l.*,p.name as p_name,g.name as g_name,u.user_nickname')
            ->order('l.addtime desc')
            ->paginate(10, false, ['query' => request()->param()]);
        //$luck = db('user_luck_list')->where('nubmer = '.$user_id)->find();
        $data = $list->toArray();
        $page = $list->render();
        $pool = Db::name('bubble_pool')->where('type = 2')->order('orderno')->select();
        $game_box_type = Db::name('game_box_type')->where('status = 1')->order("orderno")->select();

        //$this->assign('statistical', $sum);
        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('gift', $gift);
        $this->assign('bubble_type', $bubble_type);
        $this->assign('pool', $pool);
        $this->assign('game_box_type', $game_box_type);
        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    //统计
    public function statistics(){
        session('page_log',input('request.page') ? input('request.page') : 1);
        $where = [];
        $request = input('request.');
        if (!input('request.page')) {
            session('admin_index', null);
        }
        if (input('request.uid') || input('request.pool_id') || input('request.box_type') ||  input('request.start_time') || input('request.end_time')) {
            session('admin_index', input('request.'));
        }

        if (session('admin_index.uid')) {
            $where['l.uid'] = session('admin_index.uid');
        }
        if (session('admin_index.pool_id') && session('admin_index.pool_id') != '-1') {
            $where['l.pool_id'] = intval(session('admin_index.pool_id'));
        }else{
            session('admin_index.pool_id',-1);
        }
        if (session('admin_index.box_type') && session('admin_index.box_type') != '-1') {
            $where['l.box_id'] = intval(session('admin_index.box_type'));
        }else{
            session('admin_index.box_type',-1);
        }


        if (session('admin_index.end_time') && session('admin_index.start_time')) {
            $where['l.addtime'] = ['between', [strtotime(session('admin_index.start_time')), strtotime(session('admin_index.end_time'))]];
        }
        //$where = 'addtime';
        //
        if(GAME_BOX_TYPE==1){
            $list = db('game_box_log')
                ->alias('l')
                ->join('game_box_list b','b.id=l.box_id')
                //->join('bubble_pool p','p.id=l.pool_id')
                ->where($where)
                ->field('sum(l.coin * l.sum) as coin_count,sum(l.sum*b.money) as money')
                ->find();
        }else{
            $list = db('game_box_log')
                ->alias('l')
                ->join('game_box_list b','b.id=l.box_id')
                ->join('bubble_pool p','p.id=l.pool_id')
                ->where($where)
                ->field('sum(l.coin * l.sum) as coin_count,sum(l.sum*b.money) as money')
                ->find();
        }

        //echo db('game_box_log')->getLastSql();die();
        //dump($list);die();
        $pool = Db::name('bubble_pool')->where('type = 2')->order('orderno')->select();
        $game_box_type = Db::name('game_box_list')->where('status = 1')->order("orderno")->select();

        //$this->assign('statistical', $sum);
        //$page = $list->render();
        $this->assign('list', $list);
        //$this->assign('page', $page);
        //$this->assign('gift', $gift);
        //$this->assign('bubble_type', $bubble_type);
        $this->assign('pool', $pool);
        $this->assign('game_box_type', $game_box_type);
        $this->assign('request', session('admin_index'));
        $this->assign('GAME_BOX_TYPE', GAME_BOX_TYPE);
        return $this->fetch();
    }

    /**
     * 礼物列表
     */
    public function index_rate(){
        $request = [];

        $request['type_id'] = input('type_id');
        $where = 'r.id > 0';
        if(intval($request['type_id']) > 0){
            $where .= ' and r.type_id = '.intval($request['type_id']);
        }
        $result = Db::name("game_box_gift_list_rate")
            ->alias('r')
            ->join('game_box_list t','t.id = r.type_id')
            ->join('gift g','g.id = r.gift_id and g.is_delete=0')
            ->where($where)
            ->order('r.type_id')
            ->field('r.*,t.name as type_name,g.name as gift_name,g.coin as gift_coin')
            ->paginate(10, false, ['query' => request()->param()]);





        $count_rate = Db::name("game_box_gift_list_rate")
            ->alias('r')
            ->join('game_box_list t','t.id = r.type_id')
            ->join('gift g','g.id = r.gift_id and g.is_delete=0')
            ->where($where)
            ->group("r.type_id")
            ->field('r.type_id,sum(r.rate) as rate')
            ->select();

        $list = array();
        if ($result) {
            $winning_gift_key = 'winning_gift_key_id';
            $listval = $result->toarray();
            foreach ($listval['data'] as &$v){
                $v['rate_val'] = 0;
                foreach ($count_rate as $vv){
                    if ($vv['type_id'] == $v['type_id'] && $vv['rate'] > 0 && $v['rate'] > 0) {
                        $v['rate_val'] = round($v['rate'] / $vv['rate'],4) * 100 . "%";
                    }
                }
                //    $surplus = redis_hGet($winning_gift_key,$v['id']);
                $surplus = redis_islock_nx($winning_gift_key."_".$v['id']);
                if ($v['rate'] - $surplus >= 0) {
                    $v['surplus'] = $v['rate'] - $surplus;
                }else{
                    redis_hSet($winning_gift_key,$v['id'],0); //剩余总数量
                    $v['surplus'] = $v['rate'];
                }

            }
            $list = $listval['data'];
        }
        $box = Db::name("game_box_list")->order('orderno')->select();
        $config = load_cache('config');
        $currency_name = ' '.$config['currency_name'];

        $box_coin = array();
        foreach ($box as $vs){
            if(intval($request['type_id'])){
                if(intval($request['type_id']) == $vs['id']){
                    $coin_array = Db::name("game_box_gift_list_rate")
                        ->alias('r')
                        ->join('game_box_list t','t.id = r.type_id')
                        ->join('gift g','g.id = r.gift_id and g.is_delete=0')
                        ->where($where.' and r.type_id = '.$vs['id'])
                        ->order('r.type_id')
                        ->field('sum(r.rate) as rate,t.name as type_name,sum(g.coin*r.rate*r.count) as gift_coin')
                        ->find();
                    $coin_array['count_coin'] = $coin_array['rate'] * $vs['money'] .$currency_name;
                    $coin_array['gift_coin'] = $coin_array['gift_coin'].$currency_name;
                    $box_coin[]= $coin_array;
                }
            }else{
                $coin_array = Db::name("game_box_gift_list_rate")
                    ->alias('r')
                    ->join('game_box_list t','t.id = r.type_id')
                    ->join('gift g','g.id = r.gift_id and g.is_delete=0')
                    ->where($where.' and r.type_id = '.$vs['id'])
                    ->order('r.type_id')
                    ->field('sum(r.rate) as rate,t.name as type_name,sum(g.coin*r.rate*r.count) as gift_coin')
                    ->find();
                $coin_array['count_coin'] = $coin_array['rate'] * $vs['money'] .$currency_name;
                $coin_array['gift_coin'] = $coin_array['gift_coin'].$currency_name;
                $box_coin[]= $coin_array;
            }
        }


        $box = Db::name("game_box_list")->order('orderno')->select();
        $this->assign('gift',$list);
        $this->assign('box',$box);
        $this->assign('request',$request);
        $this->assign('box_coin',$box_coin);
        $this->assign('page',$result->render());
        return $this->fetch();
    }

    /**
     * 礼物添加
     */
    public function add_rate()
    {
        $id = input('param.id');
        if ($id) {
            $list = Db::name("game_box_gift_list_rate")->where("id=$id")->find();
        } else {
            $list['type_id'] = 0;
            $list['gift_id'] = 0;
            $list['is_system_push']=1;
            $list['is_male_screen']=1;
            $list['is_all_notify']=1;
            $list['is_trigger_jackpot']= 2;
        }
        $gift = Db::name("gift")->where("is_delete=0")->select();
        $box = Db::name("game_box_list")->order('orderno')->select();

        $this->assign('list', $list);
        $this->assign('gift', $gift);
        $this->assign('box', $box);
        return $this->fetch();
    }

    public function addRatePost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['create_time'] = time();
        if ($data['rate'] > 10000) {
            $this->error('概率最大设置10000');
        }
        $data['surplus'] = $data['rate'];
        if ($id) {
            $result = Db::name("game_box_gift_list_rate")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_box_gift_list_rate")->insertGetId($data);
            $id = $result;
        }
        if ($result) {
            if ($data['is_trigger_jackpot'] == 1) {
                // 触发奖只能有一个
                Db::name("game_box_gift_list_rate")->where("id !=$id and type_id=".intval($data['type_id']))->update(['is_trigger_jackpot'=> 2]);
            }
            $this->success(lang('EDIT_SUCCESS'), url('GameBox/index_rate'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    // 更新奖池--开启下一轮奖励
    public function game_box_type_next(){
        $param = $this->request->param();
        $id = $param['id'];
        $redis_name = 'gameBox'."_".$id;
        $gameBox_key = "gameBox_gift_".$id;

        // 总金额 和 触发奖的金额
        $grand_prize_amount = json_decode(redis_hGet('voice_game_box_coin_sum',$redis_name),true);
        // 剩余总金额
        $voice_game_box_coin = intval(redis_hGet('voice_game_box_coin',$redis_name));

        // 存入操作记录 --redis
        $add = array(
            'box_id'=> $id,
            'create_time' => time(),
            'grand_prize_amount' => $grand_prize_amount,
            'voice_game_box_coin' =>$voice_game_box_coin
        );
        $game_box_type_next = redis_hGet('game_box_type_next',$id);
        $insert = [];
        if ($game_box_type_next){
            $insert = json_decode($game_box_type_next,true);
        }
        $insert[] = $add;
        redis_hSet('game_box_type_next',$id,json_encode($insert)); // 记录操作日志


        redis_hDelOne("user_game_box_list_spare",$redis_name);
        redis_hDelOne("user_game_box_list",$redis_name);

        redis_unlock_nx($gameBox_key);
        redis_hDelOne("voice_game_box_coin_sum",$redis_name); // 奖池总金额

        $game_box_gift_list_rate = Db::name("game_box_gift_list_rate")->where("type_id =".$id)->select();
        foreach ($game_box_gift_list_rate as $v){
            redis_hDelOne("winning_gift_key_id",$v['id']); // 删除剩余数量
            redis_unlock_nx("winning_gift_key_id_".$v['id']);// 清除剩余数量
        }

        echo 1;
    }
    //删除
    public function del_rate()
    {
        $param = request()->param();
        $result = Db::name("game_box_gift_list_rate")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }
}
