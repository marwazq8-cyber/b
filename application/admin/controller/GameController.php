<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class GameController extends AdminBaseController
{
    /**
     * 游戏列表
     */
    public function index()
    {
        $where = "id > 0";
        if (IS_TREE != 1) {
            $where .= " and type != 7";
        }
        $list = Db::name('game_list')->where($where)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /*
    *  游戏统计收益
    */

    public function statistical()
    {
        $id = input('param.id');
        $data['id'] = $this->request->param('id');
        if (!$data['id']) {
            $data['id'] = session("game_statistical.id");
        }
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('type')) {
            $data['type'] = 0;
            session("game_statistical", $data);
        } else if (empty($p)) {
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['type'] = $this->request->param('type');

            session("game_statistical", $data);
        }

        $id = session("game_statistical.id");
        $start_time = session("game_statistical.start_time");
        $end_time = session("game_statistical.end_time");
        $type = session("game_statistical.type");

        $where = "game_id=" . $id;
        $where .= $start_time ? " and date >='" . $start_time . "'" : '';
        $where .= $end_time ? " and date <='" . $end_time . "'" : '';
        $where .= $type > 0 ? " and type ='" . $type . "'" : '';

        $list = db('bubble_day_log')
            ->field("sum(coin) as coin,sum(magic_sum) as magic_sum,sum(gift_sum) as gift_sum,sum(gift_coin) as gift_coin")
            ->where($where)
            ->find();

        $data = db('bubble_day_log')
            ->where($where)
            ->order("date DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        $name = $data->toArray();

        $this->assign('page', $data->render());
        $this->assign('data', $name['data']);
        $this->assign('list', $list);
        $this->assign('request', session("game_statistical"));
        return $this->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $list = Db::name("game_list")->where("id=$id")->find();
            $list['rule'] = htmlspecialchars_decode($list['rule']);
        } else {
            $list['type'] = 1;
            $list['status'] = 1;
            $list['img'] = '';
            $list['game_coin_picture'] = '';
            $list['game_title'] = '';
            $list['game_bg'] = '';
            $list['rule'] = '';
        }

        $game_type = array();

        $game_type[] = array(
            'id' => 6, 'name' => lang('Treasure_box_game') // 宝箱游戏
        );

        if (IS_TREE == 1) {
            $game_type[] = array(
                'id' => 7, 'name' => lang('Tree_watering_game') // 浇树游戏
            );
        }

        $this->assign('game_type', $game_type);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if (empty($data['name'])) {
            $this->error(lang('Please_enter_name'));
        }
        if (empty($data['img'])) {
            $this->error(lang('Please_upload_the_icon'));
        }
        $data['type'] = intval($data['type']) ? intval($data['type']) : 1;
        $data['sort'] = intval($data['sort']) ? intval($data['sort']) : 0;


        if ($data['type'] == 7) {
            // 浇树游戏
            $data['url'] = SITE_URL . VUE_URL . '/#/treeGame';
        } elseif ($data['type'] == 6) {
            // 开宝箱  -- 原生的
            $data['url'] = 'http://' . $_SERVER['HTTP_HOST'] . "/api/bubble_api/index";
        }

        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("game_list")->where("id=$id")->update($data);
        } else {
            $result = Db::name("game_list")->insert($data);
        }
        if ($result) {
            load_cache_rm('game_list', $param);
            $this->success(lang('EDIT_SUCCESS'), url('game/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("game_list")->where("id=" . $param['id'])->delete();

        load_cache_rm('game_list', $param);
        return $result ? '1' : '0';
        exit;
    }

    public function bubble_user()
    {
        $config = load_cache('config');
        $time = date("Y-m-d");
        if (!$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('uid') and !$this->request->param('type') and !$this->request->param('continuous_id')) {
            $data['start_time'] = $time . " 00:00";
            $data['end_time'] = $time . " 24:00";
            $data['type'] = 0;
            $data['continuous_id'] = 0;
            session("game_statistical", $data);
        } else if (empty($p)) {
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['uid'] = $this->request->param('uid');
            $data['type'] = $this->request->param('type');
            $data['continuous_id'] = $this->request->param('continuous_id');

            session("game_statistical", $data);
        }

        $id = session("game_statistical.uid");
        $start_time = session("game_statistical.start_time");
        $end_time = session("game_statistical.end_time");
        $type = session("game_statistical.type");
        $continuous_id = session("game_statistical.continuous_id");
        // 获取打泡泡统计
        $where = "l.id > 0";

        $where .= $start_time ? " and l.addtime >='" . strtotime($start_time) . "'" : '';
        $where .= $end_time ? " and l.addtime <='" . strtotime($end_time) . "'" : '';

        $where .= $id > 0 ? " and l.uid=" . $id : '';

        $list = Db::name("game_list")->field("name,id,type")->where("status=1")->select()->toarray();
        $data['coin'] = 0;
        $data['gift_sum'] = 0;
        $data['gift_coin'] = 0;
        $data['expend'] = 0;
        $wheretype = [];
        foreach ($list as &$v) {
            $v['coin'] = 0;
            $v['gift_sum'] = 0;
            $v['gift_coin'] = 0;
            $v['expend'] = 0;
            if ($v['type'] == 1) {
                // 统计当天的砸蛋消费
                $user_eggs_log = db('user_eggs_log')->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->field("sum(l.coin) as coin,sum(l.sum) as gift_sum,sum(l.sum*g.coin) as gift_coin")
                    ->where($where)
                    ->find();

                $v['coin'] = $user_eggs_log ? intval($user_eggs_log['coin']) : 0;
                $v['gift_sum'] = $user_eggs_log ? intval($user_eggs_log['gift_sum']) : 0;
                $v['gift_coin'] = $user_eggs_log ? intval($user_eggs_log['gift_coin']) : 0;
            } elseif ($v['type'] == 2) {
                // 统计普通用户打泡泡获得的礼物
                if ($type) {
                    $wheretype['l.type'] = $type;
                }
                if ($continuous_id) {
                    $wheretype['l.continuous_id'] = $continuous_id;
                }

                $playing_bubble_log = db('playing_bubble_log')->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->field("sum(l.expend) as expend,sum(l.sum) as gift_sum,sum(l.sum*g.coin) as gift_coin")
                    ->where($where)
                    ->where($wheretype)
                    ->find();

                $mcoin = $config['magic_wand_coin'];

                /*$magic_wand_sum = db('playing_bubble_log')
                    ->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->where($where)
                    ->where($wheretype)
                    ->sum('l.sum');*/
                $vexpend = db('user_bubble_magic_log')
                    ->alias('l')
                    ->where($where)
                    ->where($wheretype)
                    //->where(['bubble_type'=>1])
                    ->sum('l.expend');
                $magic_wand_log = $mcoin * $vexpend;
                $v['coin'] = $magic_wand_log;
                $v['expend'] = $vexpend;
                /*$magic_wand_log = $mcoin * $magic_wand_sum;
                $v['coin'] =  $playing_bubble_log['expend']*$mcoin;//intval($magic_wand_log);
                $v['expend'] = $playing_bubble_log ? intval($playing_bubble_log['expend']) : 0;*/
                $v['gift_sum'] = $playing_bubble_log ? intval($playing_bubble_log['gift_sum']) : 0;
                $v['gift_coin'] = $playing_bubble_log ? intval($playing_bubble_log['gift_coin']) : 0;

            } elseif ($v['type'] == 3) {
                // 统计普通用户打彩虹泡泡获得的礼物

                if ($type) {
                    $wheretype['l.type'] = $type;
                }
                $rainbow_bubble_log = db('rainbow_bubble_log')
                    ->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->field("sum(l.expend) as expend,sum(l.sum) as gift_sum,sum(l.sum*l.coin) as gift_coin")
                    ->where($where)
                    ->where($wheretype)
                    ->find();

                $mcoin = $config['rainbow_magic_wand_coin'];

                $vexpend = db('user_bubble_magic_log')
                    ->alias('l')
                    ->where($where)
                    ->where($wheretype)
                    ->where(['bubble_type' => 2])
                    ->sum('l.expend');
                $magic_wand_log = $mcoin * $vexpend;

                $v['coin'] = $magic_wand_log;
                $v['expend'] = $vexpend;

                //$v['coin'] =  intval($rainbow_magic_wand_log);
                //$v['expend'] = $rainbow_bubble_log ? intval($rainbow_bubble_log['expend']) : 0;
                $v['gift_sum'] = $rainbow_bubble_log ? intval($rainbow_bubble_log['gift_sum']) : 0;
                $v['gift_coin'] = $rainbow_bubble_log ? intval($rainbow_bubble_log['gift_coin']) : 0;

            } else if ($v['type'] == 4) {
                $wherelog = [];
                if ($type) {
                    $wheretype['l.type'] = $type;
                    $wherelog['sum'] = $type;
                }
                if ($continuous_id) {
                    $wheretype['l.continuous_id'] = $continuous_id;
                }
                $playing_darts_log = db('game_box_log')
                    ->alias('l')
                    ->join('gift g', 'g.id=l.gift_id')
                    ->field("sum(l.expend) as expend,sum(l.sum) as gift_sum,sum(l.sum*g.coin) as gift_coin")
                    ->where($where)
                    ->where($wheretype)
                    ->find();
                $vexpend = db('game_box_log')
                    ->alias('l')
                    ->where($where)
                    ->where($wheretype)
                    //->where(['bubble_type'=>2])
                    ->sum('l.expend');
                //$magic_wand_log = $mcoin * $vexpend;
                $darts_log_coin = db('game_box_log')
                    ->alias('l')
                    ->where($where)
                    ->where($wherelog)
                    ->sum('expend');
                $darts_log_coin = $darts_log_coin * $config['game_box_coin'];

                $v['coin'] = $darts_log_coin;
                $v['expend'] = $vexpend;

                $v['gift_sum'] = $playing_darts_log ? intval($playing_darts_log['gift_sum']) : 0;
                $v['gift_coin'] = $playing_darts_log ? intval($playing_darts_log['gift_coin']) : 0;
            }

            $data['coin'] = $data['coin'] + $v['coin'];
            $data['gift_sum'] = $data['gift_sum'] + $v['gift_sum'];
            $data['gift_coin'] = $data['gift_coin'] + $v['gift_coin'];
            $data['expend'] = $data['expend'] + $v['expend'];
        }
        $bubble_type = Db::name("bubble_type")->where("type=1")->order("orderno desc")->select();

        $this->assign('bubble_type', $bubble_type);

        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign('request', session("game_statistical"));
        return $this->fetch();
    }

}
