<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 上午 11:02
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class TreeGameController extends AdminBaseController
{
    /**
     *  浇树礼物配置列表
     */
    public function index()
    {
        if (!input('request.page')) {
            $data['gift_id']=0;
            $data['status']= 0;
            session('tree_game', $data);
        }
        if (input('request.status') || input('request.gift_id')) {
            $data['status']=input('request.status') ? input('request.status') : 0;
            $data['gift_id']=input('request.gift_id') ? input('request.gift_id') :0;
            session('tree_game', $data);
        }
        $status=session('tree_game.status') >0 ? session('tree_game.status') :'';
        $gift_id=session('tree_game.gift_id') >0 ? session('tree_game.gift_id') :'';

        $where="t.id >0";
        $where.= $gift_id ? " and t.gift_id=". $gift_id:'';
        $where.= $status ? " and t.status=". $status:'';

        $list = Db::name('game_tree_gift')->alias("t")
            ->join('gift f', 'f.id = t.gift_id and f.is_delete=0','left')
            ->field("t.*,f.name as gift_name,f.coin as gift_coin")
            ->where($where)
            ->order("t.sort desc")
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();
        $page = $list->render();
        $count_rate = Db::name('game_tree_gift')->alias("t")
            ->where("status=1")
            ->sum("rate*count");
        foreach ($data['data'] as &$v){
            $v['rate_val'] = $count_rate > 0 && $v['rate']*$v['count'] > 0 ?  round(($v['rate']*$v['count']) / $count_rate,4) * 100 . "%" : 0;
        }
        $average = Db::name('game_tree_gift')->alias("t")
            ->join('gift f', 'f.id = t.gift_id and f.is_delete=0')
            ->where("t.status=1") ->field("sum(t.rate) as rate,sum(f.coin*t.rate*t.count) as coin")->find();
        // 获取 game_tree_coin

        $game_tree_coin = Db::name('game_tree_coin')->order("coin asc")->find();
        $tree_coin = 0;
        if ($game_tree_coin && $game_tree_coin['sum'] > 0 && $game_tree_coin['coin'] > 0){
            $tree_coin = round($game_tree_coin['coin'] / $game_tree_coin['sum']);
        }
        $average_val = 0;
        $gift_coin = 0;
        if ($average && $average['rate'] > 0 && $average['coin'] > 0) {
            $average_val = round($average['coin'] / $average['rate'],2);
            $gift_coin = $average['coin'];
        }
        $config = load_cache('config');
        $currency_name = ' '.$config['currency_name'];
        // 每次消费价格
        $tree = array(
            'tree_coin'=>$tree_coin.$currency_name,
            'tree_count_coin'=>$tree_coin * $average['rate'].$currency_name,
            'average_val'=>$average_val.$currency_name,
            'gift_coin'=>$gift_coin.$currency_name,
        );

        $gift = Db::name("gift")->where("is_delete=0")->order("orderno desc")->select();
        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('gift', $gift);
        $this->assign('data', $tree);
        $this->assign('request', session('tree_game'));
        return $this->fetch();
    }
    /**
     *  编辑礼物
     */
    public function add_rate()
    {
        $id = intval(input('param.id'));
        if ($id) {
            $list = Db::name("game_tree_gift")->where("id=$id")->find();
        } else {
            $list['gift_id'] = 0;
            $list['is_system_push']=1;
            $list['is_male_screen']=1;
            $list['is_all_notify']=1;
            $list['status']=1;
        }
        $gift = Db::name("gift")->where("is_delete=0")->select();
        $this->assign('list', $list);
        $this->assign('gift', $gift);
        return $this->fetch();
    }
    /**
     * 删除礼物
     */
    public function del_rate(){
        $id = intval(input('param.id'));
        $result = Db::name("game_tree_gift")->where("id=$id")->delete();
        if ($result) {
            $redis_name = 1;
            $keys_gift_list = "user_game_tree_gift_list";
            $keys_gift = "user_game_tree_gift";
            $keys_rate = "user_game_tree_rate";
            redis_hSet($keys_gift_list,$redis_name,json_encode([]));
            redis_hSet($keys_gift,$redis_name,json_encode([]));
            redis_hSet($keys_rate,$redis_name,json_encode([]));
        }
        return $result ? '1' : '0';exit;
    }
    /**
    * 提交编辑的礼物
     */
    public function addRatePost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['create_time'] = time();
        if ($id) {
            $result = Db::name("game_tree_gift")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_tree_gift")->insert($data);
        }
        if ($result) {
            $redis_name = 1;
            $keys_gift_list = "user_game_tree_gift_list";
            $keys_gift = "user_game_tree_gift";
            $keys_rate = "user_game_tree_rate";
            redis_hSet($keys_gift_list,$redis_name,json_encode([]));
            redis_hSet($keys_gift,$redis_name,json_encode([]));
            redis_hSet($keys_rate,$redis_name,json_encode([]));
            $this->success(lang('EDIT_SUCCESS'), url('tree_game/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    /**
    * 价格配置
     */
    public function tree_coin(){

        $list = Db::name("game_tree_coin")->order("sort desc")->select();
        $this->assign('list', $list);
        return $this->fetch();
    }
    /***
    * 获取价格配置信息
    */
    public function add_tree_coin(){
        $id = input('param.id');
        if($id){
            $list = Db::name("game_tree_coin")->where("id=".$id)->find();
        }else{
            $list['sum'] =  1;
            $list['sort'] =  10;
        }
        $this->assign('list', $list);
        return $this->fetch();
    }
    /**
    * 保存价格配置信息
     */
    public function add_tree_coin_post(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['sort']=  $data['sort'] ?  $data['sort'] : 0;
        $data['create_time'] = time();
        if ($id) {
            $result = Db::name("game_tree_coin")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_tree_coin")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('tree_game/tree_coin'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    // 清空奖池
    public function tree_game_type_next(){

        $redis_name = 1;
        $keys_gift_list = "user_game_tree_gift_list";
        $keys_gift = "user_game_tree_gift";
        $keys_rate = "user_game_tree_rate";
        redis_hSet($keys_gift_list,$redis_name,json_encode([]));
        redis_hSet($keys_gift,$redis_name,json_encode([]));
        redis_hSet($keys_rate,$redis_name,json_encode([]));
        redis_unlock_nx("treeBox_gift_one"); // 清除缓存

        // 存入操作记录 --redis
        $add = array(
            'create_time' => time()
        );
        $insert[] = $add;
        redis_hSet('tree_game_type_next',$redis_name,json_encode($insert)); // 记录操作日志
        echo 1;
    }
    /**
    * 奖励记录
     */
    public function tree_log(){
        if (!input('request.page')) {
            $data['uid']='';
            $data['gift_id']= '';
            $data['coin_id']= '';
            $data['only']= '';
            $data['voice_id']= '';
            $data['cycles'] = '';
            $data['date_type']= '';
            session('tree_log', $data);
        }
        if (input('request.uid') || input('request.date_type')  || input('request.gift_id') || input('request.cycles') || input('request.coin_id') || input('request.only')|| input('request.voice_id') || input('request.start_time') || input('request.end_time')) {
            $data['date_type']=input('request.date_type') ? input('request.date_type') : '';
            $data['uid']=input('request.uid') ? input('request.uid') : '';
            $data['cycles']=input('request.cycles') ? input('request.cycles') : '';
            $data['gift_id']=input('request.gift_id') ? input('request.gift_id') : '';
            $data['coin_id']=input('request.coin_id') ? input('request.coin_id') : '';
            $data['only']=input('request.only') ? input('request.only') : '';
            $data['voice_id']=input('request.voice_id') ? input('request.voice_id') : '';
            $data['start_time']=input('request.start_time') ? input('request.start_time') :'';
            $data['end_time']=input('request.end_time') ? input('request.end_time') :'';
            session('tree_log', $data);
        }
        if(session('tree_log.date_type')){
            //今日、昨日、本周、上周 处理
            $search_time = select_date_type(session('tree_log.date_type'));
            session('tree_log.start_time', date('Y-m-d',$search_time['start_time']));
            session('tree_log.end_time', date('Y-m-d',$search_time['end_time']));
        }

        $coin_id=session('tree_log.coin_id') ? intval(session('tree_log.coin_id')) :'';
        $gift_id=session('tree_log.gift_id') ? intval(session('tree_log.gift_id')) :'';
        $uid=session('tree_log.uid') ? intval(session('tree_log.uid')) :'';
        $only=session('tree_log.only') ? session('tree_log.only') :'';
        $voice_id=session('tree_log.voice_id') ? intval(session('tree_log.voice_id')) :'';
        $starttime=session('tree_log.start_time') > 0 ? session('tree_log.start_time') :'';
        $endtime=session('tree_log.end_time') > 0 ? session('tree_log.end_time') :'';
        $cycles=session('tree_log.cycles') ? intval(session('tree_log.cycles')) :'';

        $where="l.id >0";

        $where.= $coin_id ? " and l.coin_id=". $coin_id:'';
        $where.= $gift_id ? " and l.gift_id=". $gift_id:'';
        $where.= $uid ? " and l.uid=". $uid:'';
        $where.= $voice_id ? " and l.voice_id=". $voice_id:'';
        $where.= $only ? " and instr(l.only,'".$only."') > 0 ":'';
        $where.= $starttime ? " and l.create_time >=". strtotime($starttime):'';
        $where.= $endtime ? " and l.create_time <". strtotime($endtime):'';
        $where.= $cycles ? " and l.cycles=". $cycles:'';

        $list = db('game_tree_log')->alias('l')
            ->where($where)->order('l.create_time desc')
            ->paginate(10, false, ['query' => request()->param()]);

        // 统计总消费金额
        $Statistics = db('game_tree_log')->alias('l')->field("sum(l.coin_sum * l.coin_sum_money) as coin_money,sum(l.gift_coin * l.sum) as gift_money")->where($where)->find();
        $Statistics_gift = db('game_tree_log')
            ->alias('l')
            ->field("sum(l.sum) as sum,l.gift_name")
            ->where($where)
            ->group("l.gift_id")
            ->select();

        $data = $list->toArray();
        foreach ($data['data'] as &$v) {
            $user = Db::name('user')->where('id = '.$v['voice_user_id'])->field('id,user_nickname')->find();
            $v['voice_user_id'] = $user['id'];
            $v['aname'] = $user['user_nickname'];

            if ($v['pool_json']){
                $v['pool_json'] = json_decode($v['pool_json'],true);
            }
        }

         $gift = Db::name('game_tree_gift')->alias("t")
            ->join('gift f', 'f.id = t.gift_id and f.is_delete=0')
            ->field("f.name,f.id")
            ->order("f.orderno desc")
            ->select();


        $page = $list->render();
        $this->assign('gift', $gift);
        $this->assign('list', $data['data']);
        $this->assign('Statistics_gift', $Statistics_gift);
        $this->assign('statistics', $Statistics);
        $this->assign('page', $page);
        $this->assign('request', session('tree_log'));
        return $this->fetch();
    }

}
