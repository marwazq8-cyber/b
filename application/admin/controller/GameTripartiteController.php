<?php

namespace app\admin\controller;

use app\admin\model\Games;
use app\admin\model\PubMsgs;
use app\admin\model\TripartiteGameLogModel;
use app\admin\model\TripartiteGameModel;
use cmf\controller\AdminBaseController;
use think\Db;

//游戏
class GameTripartiteController extends AdminBaseController
{
    private $game_type_list = [];
   public function __construct()
    {
        parent::__construct();
        $this->game_type_list[] = array(
            'label' => 'kingdoms',
            'value' => 'kingdoms'
        );
        $this->game_type_list[] = array(
            'label' => 'lucky77',
            'value' => 'lucky77'
        );
        $this->game_type_list[] = array(
            'label' => 'lucky99',
            'value' => 'lucky99'
        );
        $this->game_type_list[] = array(
            'label' => 'fruitLoops',
            'value' => 'fruitLoops'
        );
        $this->game_type_list[] = array(
            'label' => 'greedy',
            'value' => 'greedy'
        );
        $this->game_type_list[] = array(
            'label' => 'whackAMole',
            'value' => 'whackAMole'
        );
        $this->game_type_list[] = array(
            'label' => 'fruitMachine',
            'value' => 'fruitMachine'
        );
        $this->game_type_list[] = array(
            'label' => 'dragonTigerBattle',
            'value' => 'dragonTigerBattle'
        );
    }
    /**
    * 处理java_json中数据
     * */
    private function get_java_json($type,$java_json){
        if($java_json){
            $java_json = json_decode($java_json,true);
        }else{
            $TripartiteGame = new TripartiteGameModel();
            switch ($type){
                case 'kingdoms':
                case 'kingdoms2':
                    // 扎金花 title和desc 是语言包
                $java_json = $TripartiteGame->get_kingdoms_java_json();
                    break;
                case 'lucky77':
                    $java_json = $TripartiteGame->get_lucky77_java_json();
                    break;
                case 'lucky99':
                    $java_json = $TripartiteGame->get_lucky99_java_json();
                    break;
                case 'fruitLoops':
                    $java_json = $TripartiteGame->get_fruitLoops_java_json();
                    break;
                case 'greedy':
                case 'greedyBtec':
                $java_json = $TripartiteGame->get_greedy_java_json();
                    break;
                case 'fruitMachine':
                    $java_json = $TripartiteGame->get_fruitMachine_java_json();
                    break;
                case 'whackAMole':
                    // 打狗游戏
                    $java_json = $TripartiteGame->get_whackAMole_java_json();
                    break;
                case 'dragonTigerBattle':
                  // 龙虎斗游戏
                  $java_json = $TripartiteGame->get_dragonTigerBattle_java_json();
                  break;
                default:
                    $java_json = [];
                    break;
            }
        }
        return $java_json;
    }
    /**
     * 获取三方游戏
     */
    public function index() {
        $page = 1;
        $limit = 20;
        $where ="id > 0";
        $TripartiteGame = new TripartiteGameModel();
        $list = $TripartiteGame -> get_list($where,$page,$limit);

        $this->assign('list', $list['data']);
        return $this->fetch();
    }
    /**
    * 编辑信息
     * */
    public function add(){
        $id = input('param.id');

        if ($id) {
            $list = db("tripartite_game")->where("id=$id")->find();
            $is_field_name = 1; // 是否把field_name当作标题语言包
            // 处理java_json中数据
            $list['java_json'] = $this->get_java_json($list['type'],$list['java_json']);

            switch ($list['type']){
                case 'greedy':
                case 'greedyBtec':
                case 'fruitMachine':
                    foreach ($list['java_json'] as &$vs){
                        if ($vs['field_name'] == 'optionType') {
                            foreach ($vs['val'] as &$va){
                                $va['title'] = lang($va['field_name']);
                            }
                        }
                    }
                    break;
                case 'whackAMole':
                    $is_field_name = 0;
                    break;
                case 'dragonTigerBattle':
                    foreach ($list['java_json'] as &$vs){
                        if ($vs['field_name'] == 'optionType') {
                            foreach ($vs['val'] as &$va){
                                $va['title'] = lang($va['title']);
                            }
                        }
                    }
                    $is_field_name = 0;
                    break;
                default:
                    break;
            }

            foreach ($list['java_json'] as &$vs){
                $vs['title'] = lang($vs['title']);
                $vs['desc'] = lang($vs['desc']);
                if ($vs['field_name'] == 'optionType') {
                    if ($is_field_name == 1){
                        foreach ($vs['val'] as &$va){
                            $va['title'] = lang($va['field_name']);
                        }
                    }
                }
                if ($vs['type'] == 'array'){
                    foreach ($vs['val'] as &$val){
                        if ($val['type'] == 'array'){
                            foreach ($val['val'] as &$vall){
                                $vall['title'] = lang($vall['title']);
                                $vall['desc'] = lang($vall['desc']);
                            }
                        }elseif($val['type'] == 'select'){
                            foreach ($val['list'] as &$vall){
                                if(isset($vall['title'])){
                                    $vall['title'] = lang($vall['title']);
                                    $vall['desc'] = lang($vall['desc']);
                                }
                            }
                            if(isset($val['title'])){
                                $val['title'] = lang($val['title']);
                                $val['desc'] = lang($val['desc']);
                            }
                        }else{
                            $val['title'] = lang($val['title']);
                            $val['desc'] = lang($val['desc']);
                        }
                    }
                }
            }
        }
        $this->assign('list', $list);
        return $this->fetch();
    }
    /**
    * 编辑游戏
     * */
    public function addPost(){
        $params = $this->request->param();
        $id= intval($params['id']);
        $param = $params['post'];
        $TripartiteGame = new TripartiteGameModel();
        $list = $TripartiteGame->sel_find("id=".$id);
        if ($list){
            // 处理java_json中数据
            $list['java_json'] = $this->get_java_json($list['type'],$list['java_json']);

            foreach ($list['java_json'] as &$vs){

                if ($vs['type'] == 'array') {
                    $field_name = $vs['field_name'];
                    foreach ($vs['val'] as &$val){
                        $field_name1 = $field_name."_".$val['field_name'];
                        if ($val['type'] == 'array'){
                            foreach ($val['val'] as &$vall){
                                $field_name2 = $field_name1."_".$vall['field_name'];
                                if (isset($param[$field_name2])){
                                    $vall['val'] = $param[$field_name2];
                                }

                            }
                        }else{

                            if (isset($param[$field_name1])){
                                $val['val'] = $param[$field_name1];
                            }
                        }
                    }
                }else{
                    if (isset($param[$vs['field_name']])){
                        $vs['val'] = $param[$vs['field_name']];
                    }
                }
            }

            $java_json = $list['java_json'] ;
            $data = array(
                'title'=> $param['title'],
                'icon'=> $param['icon'],
                'domain_name'=> $param['domain_name'],
                'java_domain_name'=> $param['java_domain_name'],
                'java_json'=> json_encode($java_json),
                'sort'=>  intval($param['sort']),
                'status'=>  intval($param['status']),
                'create_time'=> NOW_TIME,
                'bg_img'=> $param['bg_img'],
            );
            if (empty($data['title'])) {
                $this->error(lang('ADMIN_GAME_NAME').lang('Modification_failed'));
            }
            if (empty($data['icon'])) {
                $this->error(lang('ADMIN_GAME_ICON').lang('Modification_failed'));
            }
            $game = $list;
            if ($list['game_name'] == 'kingdoms' || $list['game_name'] == 'kingdoms2') {
                foreach ($java_json as $v){
                    if ($v['field_name'] == 'betOption') {
                        // 处理金额 -- 4个盘的金额数
                        $betOption=explode(",",$v['val']);
                        if (count($betOption) != 4) {
                            $this->error(lang('counter_value_desc').lang('Modification_failed'));
                        }
                    }
                }
            }elseif ($list['game_name'] == 'whackAMole'){
                // 需要获取钻石余额 --- 游戏单价--有礼物的才使用
                $data['unit_price'] = intval($param['optionType_gameCoinValue']);
            }

            // 更新数据
            $list_save = $TripartiteGame->save_update("id=".$id,$data);

            if ($list_save) {
                // 更新 三方游戏配置
                $game = $TripartiteGame->sel_find("id=".$id);
                $result_atatus = $this->save_tripartite_game($game);
                if ($result_atatus && $result_atatus['code'] != 200){
                    //错误提示
                    $this->error($result_atatus['msg']);
                }
               //成功提示
                $this->success(lang('Modified_successfully'), url('GameTripartite/index'));
            } else {
                //错误提示
                $this->error(lang('Modification_failed'));
            }
        }else{
            $this->error(lang('Modification_failed'));
        }
    }

    /**
    * 获取三方游戏轮数记录
     */
    public function tripartit_game_log(){


        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('room_id') and !$this->request->param('game_type') and !$this->request->param('game_order_id')  and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['room_id'] = '';
            $data['game_type'] = '';
            $data['game_order_id'] = '';
            $data['end_time'] = '';
            $data['start_time'] = '';
            session("tripartit_game_log", $data);
        } else if (empty($p)) {

            $data['room_id'] = $this->request->param('room_id') .'';
            $data['game_type'] = $this->request->param('game_type').'';
            $data['game_order_id'] = $this->request->param('game_order_id') .'';
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("tripartit_game_log", $data);
        }

        $room_id = intval(session("tripartit_game_log.room_id"));
        $start_time = session("tripartit_game_log.start_time") ? strtotime(session("tripartit_game_log.start_time")) : '';
        $end_time = session("tripartit_game_log.end_time") ? strtotime(session("tripartit_game_log.end_time") . " 23:59:59") : '';
        $game_type = session("tripartit_game_log.game_type");
        $game_order_id = session("tripartit_game_log.game_order_id");

        $where ="l.id > 0";
        $where .= $room_id ? " and l.room_id=".$room_id : '';
        $where .= $game_type ? " and l.game_type ='".$game_type."'" : '';
        $where .= $game_order_id ? " and l.game_order_id ='".$game_order_id."'" : '';
        $where .= $start_time ? " and l.create_time >=".$start_time : "";
        $where .= $end_time ? " and l.create_time <=".$end_time : "";

        $TripartiteGameLog = new TripartiteGameLogModel();
        $list = $TripartiteGameLog -> get_list($where);
        $sum = $TripartiteGameLog -> get_list_sum($where." and reward_type=0");
        $sum1 = $TripartiteGameLog ->get_list_sum($where." and reward_type=1");
        $name = $list->toArray();
        $this->assign('data', session("tripartit_game_log"));
        $this->assign('sum', $sum);
        $this->assign('sum1', $sum1);
        $this->assign('list', $name['data']);
        $this->assign('game_type_list', $this->game_type_list);
        $this->assign('page', $list->render());
        return $this->fetch();
    }
    /**
    * 三方游戏用户消费记录
     */
    public function tripartit_game_user_log() {

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('room_id') and !$this->request->param('tripartite_game_log_id') and !$this->request->param('game_type') and !$this->request->param('game_order_id')  and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['tripartite_game_log_id'] = '';
            $data['uid'] = '';
            $data['room_id'] = '';
            $data['game_type'] = '';
            $data['game_order_id'] = '';
            $data['end_time'] = '';
            $data['start_time'] = '';
            session("tripartit_game_user_log", $data);
        } else if (empty($p)) {
            $data['tripartite_game_log_id'] = $this->request->param('tripartite_game_log_id') .'';
            $data['uid'] = $this->request->param('uid') .'';
            $data['room_id'] = $this->request->param('room_id') .'';
            $data['game_type'] = $this->request->param('game_type').'';
            $data['game_order_id'] = $this->request->param('game_order_id') .'';
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("tripartit_game_user_log", $data);
        }

        $uid = intval(session("tripartit_game_user_log.uid"));
        $room_id = intval(session("tripartit_game_user_log.room_id"));
        $start_time = session("tripartit_game_user_log.start_time") ? strtotime(session("tripartit_game_user_log.start_time")) : '';
        $end_time = session("tripartit_game_user_log.end_time") ? strtotime(session("tripartit_game_user_log.end_time") . " 23:59:59") : '';
        $game_type = session("tripartit_game_user_log.game_type");
        $game_order_id = session("tripartit_game_user_log.game_order_id");
        $tripartite_game_log_id = intval(session("tripartit_game_user_log.tripartite_game_log_id"));


        $where ="l.id > 0";
        $where .= $uid ? " and l.uid=".$uid : '';
        $where .= $tripartite_game_log_id ? " and l.tripartite_game_log_id=".$tripartite_game_log_id : '';
        $where .= $room_id ? " and l.room_id=".$room_id : '';
        $where .= $game_type ? " and l.game_type ='".$game_type."'" : '';
        $where .= $game_order_id ? " and l.game_order_id ='".$game_order_id."'" : '';
        $where .= $start_time ? " and l.create_time >=".$start_time : "";
        $where .= $end_time ? " and l.create_time <=".$end_time : "";

        $TripartiteGameLog = new TripartiteGameLogModel();
        $list = $TripartiteGameLog -> get_user_list($where);
        $sum = $TripartiteGameLog ->get_user_list_sum($where." and reward_type=0");
        $sum1 = $TripartiteGameLog ->get_user_list_sum($where." and reward_type=1");
        $name = $list->toArray();


        $this->assign('data', session("tripartit_game_user_log"));
        $this->assign('sum', $sum);
        $this->assign('sum1', $sum1);
        $this->assign('list', $name['data']);
        $this->assign('game_type_list', $this->game_type_list);
        $this->assign('page', $list->render());
        return $this->fetch();

    }
    /**
     * 获取三方游戏礼物列表
     * */
    public function tripartite_game_gift() {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('gift_id') and !$this->request->param('type') and !$this->request->param('is_lucky') ) {
            $data['type'] = '';
            $data['gift_id'] = '';
            $data['is_lucky'] = '';
            session("tripartite_game_gift", $data);
        } else if (empty($p)) {
            $data['is_lucky'] = $this->request->param('is_lucky') .'';
            $data['gift_id'] = $this->request->param('gift_id') .'';
            $data['type'] = $this->request->param('type').'';
            session("tripartite_game_gift", $data);
        }

        $is_lucky = session("tripartite_game_gift.is_lucky");
        $gift_id = intval(session("tripartite_game_gift.gift_id"));
        $type = session("tripartite_game_gift.type");

        $where ="t.id > 0";
        $where .= $is_lucky >='0' ? " and t.is_lucky=".$is_lucky : '';
        $where .= $gift_id ? " and t.gift_id=".$gift_id : '';
        $where .= $type ? " and t.type ='".$type."'" : '';

        // 查询普通礼物
        $list = Db::name('tripartite_game_gift')->alias("t")
            ->join("gift g","g.id = t.gift_id")
            ->join("tripartite_game m","m.type = t.type")
            ->where($where)
            ->field("g.name as title,g.coin,t.*,m.title as game_title")
            ->order('t.sort', 'desc')
            ->paginate(10, false, ['query' => request()->param()]);

        $name = $list->toArray();
        $game_type_list =Db::name('tripartite_game')->field("title as label,type as value,unit_price")->where("is_gift=1")->select();

        $config = load_cache('config');
        $currency_name = ' '.$config['currency_name'];

        $gift_coin = array();
        foreach ($game_type_list as $v){
            $unit_price = $v['unit_price'];
            if(intval($type)){
                if(intval($type) == $v['type']){
                    // 统计每次游戏币的礼物价格
                    $count = Db::name('tripartite_game_gift')->alias("t")
                        ->join("gift g","g.id = t.gift_id")
                        ->join("tripartite_game m","m.type = t.type")
                        ->where($where)
                        ->field("sum(g.coin*t.number*t.probability) as gift_coin,sum(t.probability) as sums,m.title as type_name")
                        ->find();

                    $count['count_coin'] = $count['sums'] * $unit_price .$currency_name;
                    $count['gift_coin'] = $count['gift_coin'].$currency_name;
                    $gift_coin[]= $count;
                }
            }else{
                // 统计每次游戏币的礼物价格
                $count = Db::name('tripartite_game_gift')->alias("t")
                    ->join("gift g","g.id = t.gift_id")
                    ->join("tripartite_game m","m.type = t.type")
                    ->where($where)
                    ->field("sum(g.coin*t.number*t.probability) as gift_coin,sum(t.probability) as sums,m.title as type_name")
                    ->find();

                $count['count_coin'] = $count['sums'] * $unit_price .$currency_name;
                $count['gift_coin'] = $count['gift_coin'].$currency_name;
                $gift_coin[]= $count;
            }
        }
        $this->assign('gift_coin', $gift_coin);
        $this->assign('data', session("tripartite_game_gift"));
        $this->assign('list', $name['data']);
        $this->assign('game_type_list', $game_type_list);
        $this->assign('page', $list->render());
        return $this->fetch();
    }
    /**
     * 编辑或添加信息
     * */
    public function tripartite_game_gift_add(){
        $id = input('param.id');
        $gift = Db::name("gift")->where("status=1 and coin_type=1")->order("id desc")->select();
        $game_type_list =Db::name('tripartite_game')->field("title as label,type as value")->where("is_gift=1")->select();
        if ($id) {
            $game_gift = Db::name("tripartite_game_gift")->where("id=$id")->find();
        } else {
            $game_gift['type'] = '';
            $game_gift['gift_id'] = 0;
            $game_gift['number'] = 1;
            $game_gift['probability'] = 0;
            $game_gift['is_lucky'] = 0;
        }

        $this->assign('game_type_list', $game_type_list);
        $this->assign('gift', $gift);
        $this->assign('game_gift', $game_gift);
        return $this->fetch();
    }
    /**
     * 保存游戏礼物
     * */
    public function tripartite_game_gift_addPost(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['create_time'] = time();
        $type= $data['type'];
        $is_upd=[];
        if ($id) {
            $tripartite_game_gift = Db::name("tripartite_game_gift")->where("id=$id")->find();
            if($tripartite_game_gift['type'] != $data['type']){
                $is_upd = $tripartite_game_gift;
            }
            $result = Db::name("tripartite_game_gift")->where("id=$id")->update($data);
        } else {
            $result = Db::name("tripartite_game_gift")->insertGetId($data);
        }
        if ($result) {
            $game = Db::name("tripartite_game")->where("type='".$type."'")->find();
            $result_atatus =$this->save_tripartite_game($game);
            if ($result_atatus && $result_atatus['code'] != 200){
                //错误提示
                $this->error($result_atatus['msg']);
            }
            if($is_upd){
                $game = Db::name("tripartite_game")->where("type='".$is_upd['type']."'")->find();
                $result_atatus = $this->save_tripartite_game($game);
                if ($result_atatus && $result_atatus['code'] != 200){
                    //错误提示
                    $this->error($result_atatus['msg']);
                }
            }
            $this->success(lang('修改成功'), url('GameTripartite/tripartite_game_gift'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    /**
     * 删除游戏礼物
     * */
    public function del_game_gift(){
        $param = request()->param();
        $tripartite_game_gift = Db::name("tripartite_game_gift")->where("id=" . $param['id'])->find();
        $result = false;
        if($tripartite_game_gift){
            $result = Db::name("tripartite_game_gift")->where("id=" . $param['id'])->delete();
            if($result){
                $game = Db::name("tripartite_game")->where("type='".$tripartite_game_gift['type']."'")->find();
                $result_atatus = $this->save_tripartite_game($game);
                if ($result_atatus && $result_atatus['code'] != 200){
                    //错误提示
                    $this->error($result_atatus['msg']);
                }
            }
        }
        return $result ? '1' : '0';  exit;
    }
    /**
     * 更新三方接口数据
     * */
    private function save_tripartite_game($game){

        // 更新 三方游戏配置
        $post_data = array(
            'status' => $game['status'],
            'className' => $game['game_name'],
            'merchantId' => $game['merchant'],
        );
        // 处理java_json中数据
        $game['java_json'] = $this->get_java_json($game['type'],$game['java_json']);
        foreach ($game['java_json'] as &$vs){
            if ($vs['type'] == 'array') {
                $field_name = $vs['field_name'];
                foreach ($vs['val'] as &$val){
                    $field_name1 = $field_name."_".$val['field_name'];
                    if ($val['type'] == 'array'){
                        foreach ($val['val'] as &$vall){
                            $field_name2 = $field_name1."_".$vall['field_name'];
                            if (isset($param[$field_name2])){
                                $vall['val'] = $param[$field_name2];
                            }
                        }
                    }else{
                        if (isset($param[$field_name1])){
                            $val['val'] = $param[$field_name1];
                        }
                    }
                }
            }else{
                if (isset($param[$vs['field_name']])){
                    $vs['val'] = $param[$vs['field_name']];
                }
            }
        }
        foreach ($game['java_json'] as $v){
            switch ($game['game_name']) {
                case 'kingdoms':
                case 'kingdoms2':
                    // 扎金花 title和desc 是语言包   处理金额 -- 4个盘的金额数
                    $post_data[$v['field_name']] = $v['field_name'] == 'betOption' ? '[' . $v['val'] . ']' : $v['val'];
                    break;
                case 'lucky77':
                case 'lucky99':
                case 'fruitLoops':
                case 'greedy':
                case 'greedyBtec':
                case 'fruitMachine':
                case 'dragonTigerBattle':
                    if ($v['field_name'] == 'betOption') {
                        // 处理金额 -- 4个盘的金额数
                        $post_data[$v['field_name']] = '[' . $v['val'] . ']';
                    }elseif($v['field_name'] == 'optionType'){
                        // 处理概率和倍率数组
                        $optionType = array();
                        foreach ($v['val'] as $vv){
                            $v_type = array();
                            foreach ($vv['val'] as $vvv){
                                $v_type[$vvv['field_name']] = $vvv['val'];
                            }
                            $optionType[$vv['field_name']] = $v_type;
                        }
                        $post_data[$v['field_name']] = json_encode($optionType);
                    } else {
                        $post_data[$v['field_name']] = $v['val'];
                    }
                    break;
                case 'whackAMole':

                    if($v['field_name'] == 'optionType'){
                        $optionType = array();
                        foreach ($v['val'] as $vv){
                            if ($vv['field_name'] == 'times') {
                                // 处理金额 -- 4个盘的金额数
                                $times=explode(",",$vv['val']);
                                foreach ($times as $tiv){
                                    $optionType[$vv['field_name']][] = intval($tiv);
                                }
                            }elseif($vv['field_name'] == 'isCoinPlay' || $vv['field_name'] == 'isUpdateJackpot'){
                                $optionType[$vv['field_name']] = $vv['val'] == 1 ? true : false;
                            } else {
                                $optionType[$vv['field_name']] = $vv['val'];
                            }
                        }
                        $post_data[$v['field_name']] = $optionType;
                    }
                    break;
                default:
                    $post_data[$v['field_name']] = $v['val'];
                    break;
            }
        }

        // 需要额外增加礼物
        if($game['game_name'] == 'whackAMole'){
            // 查询普通礼物
            $gift_list = Db::name('tripartite_game_gift')->alias("t")
                ->join("gift g","g.id = t.gift_id")
                ->where("t.type='whackAMole' and t.is_lucky= 0")
                ->field("t.id as id,g.name as title,g.img as image,g.coin,t.number,t.probability")
                ->select();
            if(count($gift_list) <= 0){
                //错误提示
                $this->error(lang('Insufficient_gifts'));
            }
            $post_data['optionType']['jackpot'] = $gift_list;
            // 查询幸运礼物
            $luckyGift= Db::name('tripartite_game_gift')->alias("t")
                ->join("gift g","g.id = t.gift_id")
                ->where("t.type='whackAMole' and t.is_lucky= 1")
                ->field("t.id as id,g.name as title,g.img as image,g.coin,t.number,t.probability")
                ->select();
            $post_data['optionType']['luckyGift'] = $luckyGift;
            $post_data['optionType'] = json_encode($post_data['optionType']);
        }

        $post_result = tripartite_post($game['java_domain_name'],json_encode($post_data), ['Content-Type: application/json;charset=UTF-8']);

        bogokjLogPrint("save_game",json_encode($post_result));
        return $post_result;
    }
}