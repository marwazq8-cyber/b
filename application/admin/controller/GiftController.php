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
use gift_type_auto_cache;

class GiftController extends AdminBaseController
{
    /**
     * 幸运礼物列表
     * */
    public function gift_lucky()
    {
        $where = "l.id > 0";
        $gift = Db::name("gift_lucky")->alias("l")
            ->field("l.*,g.name,g.coin as gift_coin,g.img as gift_img")
            ->join("gift g", "g.id=l.gift_id and g.is_delete=0")
            ->where($where)
            ->order('l.create_time desc')
            ->select();
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    /**
     * 编辑幸运礼物
     * */
    public function gift_lucky_add()
    {
        $id = input('param.id');
        $gift = Db::name("gift")->where("status=1 and coin_type=1 and is_luck=1 and is_delete=0")->order("id desc")->select();
        if ($id) {
            $gift_lucky = Db::name("gift_lucky")->where("id=$id")->find();
        } else {
            $gift_lucky['status'] = 1;
            $gift_lucky['gift_id'] = 0;
        }
        $this->assign('gift', $gift);
        $this->assign('gift_lucky', $gift_lucky);
        return $this->fetch();
    }

    /**
     * 保存幸运礼物
     * */
    public function gift_lucky_post()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['create_time'] = time();
        if (intval($data['lucky_platform']) > 100) {
            $this->error(lang('请输入0-100的数'));
        }
        if (intval($data['lucky_host']) > 100) {
            $this->error(lang('请输入0-100的数'));
        }
        if (intval($data['lucky_rate']) > 100) {
            $this->error(lang('请输入0-100的数'));
        }
        if (intval($data['lucky_guild']) > 100) {
            $this->error(lang('请输入0-100的数'));
        }
        if (intval($data['lucky_platform']) + intval($data['lucky_host']) + intval($data['lucky_guild']) >= 100) {
            $this->error(lang('平台收益加主播收益不能超出100'));
        }
        // 处理出奖倍数概率不能是小数问题
        if ($data['lucky_multiple_rate']) {
            $lucky_multiple_rate = explode(",", $data['lucky_multiple_rate']);
            foreach ($lucky_multiple_rate as $v) {
                if (intval($v) <= 0) {
                    $this->error(lang('出奖倍数概率必须是正整数'));
                }
            }
        }

        if ($id) {
            $gift_lucky_one = Db::name("gift_lucky")->where("id =".$id)->find();
            $is_gift_lucky = Db::name("gift_lucky")->where("gift_id=" . $data['gift_id'] . " and id !=" . $id)->find();
            if ($is_gift_lucky) {
                $this->error(lang('current_gift_already_exists_prize_pool'));
            }
            $result = Db::name("gift_lucky")->where("id=$id")->update($data);
            if($result){
                if ($data['gift_id'] != $gift_lucky_one['gift_id']){
                    // 如果换幸运礼物-关闭旧的幸运礼物
                    redis_hDelOne("lucky_reward_gift",$gift_lucky_one['gift_id']);
                }
            }
        } else {
            $is_gift_lucky = Db::name("gift_lucky")->where("gift_id=" . $data['gift_id'])->find();
            if ($is_gift_lucky) {
                $this->error(lang('current_gift_already_exists_prize_pool'));
            }
            $result = Db::name("gift_lucky")->insertGetId($data);
            $id = $result;
        }
        if ($result) {
            // 删除礼物列表缓存
            require_once DOCUMENT_ROOT . "/system/cache/gift_type.auto_cache.php";
            $obj = new gift_type_auto_cache;
            $obj->rm(6);

            $gift_lucky = Db::name("gift_lucky")->where("id=$id")->find();
            redis_locksleep_nx('lucky_reward_lock:' . $gift_lucky['gift_id'], true); // 加锁
            if ($gift_lucky['status'] != 1) {
                // 关闭幸运礼物
                redis_hDelOne("lucky_reward_gift", $gift_lucky['gift_id']);
            } else {
                $gift_lucky['lucky_multiple_array'] = $this->get_lucky_multiple($gift_lucky);
                redis_hSet('lucky_reward_gift', $gift_lucky['gift_id'], json_encode($gift_lucky));
            }
            redis_unlock_nx('lucky_reward_lock:' . $gift_lucky['gift_id']);// 解锁
            $this->success(lang('EDIT_SUCCESS'), url('gift/gift_lucky'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    /**
     * 更新--同步 奖池数据
     * */
    public function upd_lucky_Jackpot()
    {
        $param = $this->request->param();
        $gift_id = intval($param['gift_id']);
        $lucky_reward_pools_val = redis_hGet("lucky_reward_gift", $gift_id);
        if (!$lucky_reward_pools_val || $lucky_reward_pools_val == null) {
            // 幸运礼物不存在
            $this->error(lang('operation_failed'));
        } else {
            $lucky_reward_pools = json_decode($lucky_reward_pools_val, true);
            unset($lucky_reward_pools['lucky_multiple_array']);
            db('gift_lucky')->where('id=' . $lucky_reward_pools['id'])->update($lucky_reward_pools);
            $this->success(lang('Operation_successful'), url('gift/gift_lucky'));
        }
    }

    /**
     * 获取倍数概率
     */
    public function get_lucky_multiple($Jackpot)
    {
        // 获取中奖倍数金额
        $lucky_multiple = explode(",", $Jackpot['lucky_multiple']);
        // 获取倍数概率
        $lucky_multiple_rate = explode(",", $Jackpot['lucky_multiple_rate']);
        $lucky_multiple_array = [];
        foreach ($lucky_multiple as $k => $v) {
            if (isset($lucky_multiple_rate[$k]) && $lucky_multiple_rate[$k] > 0) {
                $lucky_multiple_array[$v] = $lucky_multiple_rate[$k];
            }
        }
        return $lucky_multiple_array;
    }

    /**
     * 中奖记录
     * */
    public function gift_lucky_log()
    {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('gift_id') and !$this->request->param('uid') and !$this->request->param('jackpot_time') and !$this->request->param('host_id') && !isset($_REQUEST['status']) and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("gift_lucky_log", null);
            $data['gift_id'] = '0';
            $data['status'] = '-1';
            session("gift_lucky_log", $data);

        } else if (empty($p)) {
            $data['host_id'] = $this->request->param('host_id');
            $data['gift_id'] = $this->request->param('gift_id');
            $data['uid'] = $this->request->param('uid');
            $data['status'] = $this->request->param('status');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['jackpot_time'] = $this->request->param('jackpot_time');
            session("gift_lucky_log", $data);
        }

        $host_id = intval(session("gift_lucky_log.host_id"));
        $uid = intval(session("gift_lucky_log.uid"));
        $gift_id = intval(session("gift_lucky_log.gift_id"));
        $status = session("gift_lucky_log.status");
        $start_time = strtotime(session("gift_lucky_log.start_time"));
        $end_time = strtotime(session("gift_lucky_log.end_time"));
        $jackpot_time = session("gift_lucky_log.jackpot_time");

        $where = "id > 0";
        $where .= $host_id ? " and host_id=" . $host_id : "";
        $where .= $uid ? " and uid=" . $uid : "";
        $where .= $gift_id ? " and gift_id=" . $gift_id : "";
        $where .= $status != '-1' ? " and status=" . intval($status) : "";
        $where .= $jackpot_time ? " and  jackpot_time =" . $jackpot_time : "";
        $where .= $start_time ? " and addtime >=" . $start_time : "";
        $where .= $end_time ? " and addtime <=" . $end_time : "";

        $data = Db::name('gift_lucky_log')
            ->where($where)
            ->order('addtime desc')
            ->paginate(10);

        $count = Db::name('gift_lucky_log')
            ->field("sum(winning) as winning,sum(coin) as coin,sum(platform_coin) as platform_coin,sum(ticket) as ticket,sum(guild_coin) as guild_coin")
            ->where($where)
            ->find();


        $gift = Db::name("gift")->where("status=1 and coin_type=1 and is_luck=1 and is_delete=0")->order("id desc")->select();

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign('count', $count);
        $this->assign("data", session("gift_lucky_log"));
        $this->assign("gift", $gift);
        return $this->fetch();
    }

    /**
     * 清空礼物缓存
     */
    public function clear_cache($id = 0)
    {
        // 20230508版本
        $file = DOCUMENT_ROOT . "/system/cache/gift_type.auto_cache.php";
        require_once $file;
        $class = "gift_type_auto_cache";
        // 清空缓存
        $obj = new $class;
        $obj->rm($id);
        // 私信礼物
        $file = DOCUMENT_ROOT . "/system/cache/private_letter_gift.auto_cache.php";
        require_once $file;
        $class = "private_letter_gift_auto_cache";
        // 清空缓存
        $obj = new $class;
        $obj->rm($id);
        //老版本
        $file = DOCUMENT_ROOT . "/system/cache/gift.auto_cache.php";
        require_once $file;
        $class = "gift_auto_cache";
        // 清空缓存
        $obj = new $class;
        $obj->rm();

    }

    /**
     * 礼物列表
     */
    public function index()
    {
        if (($this->request->param('gift_type_id') >= 0 && $this->request->param('gift_type_id') != null) || ($this->request->param('is_gifs') >= 0 && $this->request->param('is_gifs') != null) || ($this->request->param('is_music') >= 0 && $this->request->param('is_music') != null) || ($this->request->param('is_cp') >= 0 && $this->request->param('is_cp') != null) and ($this->request->param('is_star') >= 0 or $this->request->param('is_star') != null)) {
            $data['gift_type_id'] = $this->request->param('gift_type_id') . '';
            $data['is_gifs'] = $this->request->param('is_gifs') . '';
            $data['is_music'] = $this->request->param('is_music') . '';
            $data['is_cp'] = $this->request->param('is_cp') . '';
            $data['is_star'] = $this->request->param('is_star') . '';

            session("admin_Gift", $data);
        } else {
            $data['gift_type_id'] = 0;
            $data['is_gifs'] = -1;
            $data['is_music'] = -1;
            $data['is_cp'] = -1;
            $data['is_star'] = -1;
            session("admin_Gift", $data);
        }

        $gift_type_id = intval(session("admin_Gift.gift_type_id"));

        $is_gifs = intval(session("admin_Gift.is_gifs"));
        $is_music = intval(session("admin_Gift.is_music"));
        $is_cp = intval(session("admin_Gift.is_cp"));
        $is_star = intval(session("admin_Gift.is_star"));

        $where = "g.is_delete=0";
        $where .= $gift_type_id ? " and g.gift_type_id=" . $gift_type_id : "";
        $where .= $is_gifs >= 0 ? " and g.is_gifs=" . intval($is_gifs) : "";
        $where .= $is_music >= 0 ? " and g.is_music=" . intval($is_music) : "";
        $where .= $is_cp >= 0 ? " and g.is_cp=" . intval($is_cp) : "";
        $where .= $is_star >= 0 ? " and g.is_star=" . intval($is_star) : "";

        $gift = Db::name("gift")->alias("g")
            ->field("g.*,t.title as gift_type_title")
            ->join("gift_type t", "t.id=g.gift_type_id", "left")
            ->where($where)
            ->order('g.orderno')
            ->select();
        $gift_type = Db::name("gift_type")->where("status=1")->order("sort desc")->select();

        $this->assign('data', session("admin_Gift"));
        $this->assign('gift_type', $gift_type);
        $this->assign('gift', $gift);
        $config = load_cache('config');
        $this->assign('system_currency_name', $config['system_currency_name']);
        $this->assign('currency_name', $config['currency_name']);
        return $this->fetch();
    }

    /**
     * 礼物添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $gift = Db::name("gift")->where("id=$id")->find();
        } else {
            $gift['coin_type'] = 1;
            $gift['type'] = 1;
            $gift['is_all_notify'] = 1;
            $gift['img'] = null;
            $gift['svga'] = null;
            $gift['status'] = 1;
            $gift['gift_animation_id'] = 0;
            $gift['gift_type_id'] = 1;
            $gift['is_gifs'] = 0;
            $gift['is_music'] = 0;
            $gift['is_cp'] = 0;
            $gift['is_star'] = 0;
            $gift['is_luck'] = 0;
        }
        $gift_type = Db::name("gift_type")->where("status=1")->order("sort desc")->select();

        $config = load_cache('config');
        $this->assign('system_currency_name', $config['system_currency_name']);
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('gift', $gift);
        $this->assign('gift_type', $gift_type);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        //  print_r($param);exit;
        $id = $param['id'];
        $data = $param['post'];
        $data['img'] = $param['post']['img'];
        $data['addtime'] = time();
        if ($data['gift_type_id'] != 1) {
            $data['vip_id'] = 0;
        }
        if ($id) {
            $result = Db::name("gift")->where("id=$id")->update($data);
        } else {
            $result = Db::name("gift")->insert($data);
        }
        if ($result) {
            $this->clear_cache($id);
            $this->success(lang('EDIT_SUCCESS'), url('gift/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        //   $result = Db::name("gift")->where("id=" . $param['id'])->delete();
        $result = Db::name("gift")->where("id=" . $param['id'])->update(array('status' => 0,'is_delete'=>1));
        $playing_bubble_list = Db::name("playing_bubble_list")->where("gift_id=" . $param['id'])->find();
        if ($playing_bubble_list) {
            Db::name("playing_bubble_list")->where("gift_id=" . $param['id'])->delete();
            redis_hDelOne("user_voice_gift", 1);
            redis_hDelOne("user_voice_gift", 2);
        }
        $game_tree_gift = Db::name("game_tree_gift")->where("gift_id=" . $param['id'])->find();
        if ($game_tree_gift) {
            Db::name("game_tree_gift")->where("gift_id=" . $param['id'])->update(['status' => 0]);
            redis_hDelOne("user_game_tree_gift_list", 1);
        }
        // 关闭幸运礼物
        redis_hDelOne("lucky_reward_gift", $param['id']);
        $this->clear_cache($param['id']);
        return $result ? '1' : '0';
    }

    //修改排序
    public function upd()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("gift")->where("id=$k")->update(array('orderno' => $v));
            if ($status) {
                $data = $status;
            }
        }

        if ($data) {
            $this->clear_cache();
            $this->success(lang('Sorting_succeeded'));
        } else {
            $this->error(lang('Sorting_error'));
        }
    }

    /**
     *更新排序
     */
    private function listSort($model)
    {
        if (!is_object($model)) {
            return false;
        }
        $pk = $model->getPk(); //获取主键
        $ids = $_POST['listorders'];
        foreach ($ids as $key => $r) {
            $data['sort'] = $r;
            $model->where(array($pk => $key))->save($data);
        }
        return true;
    }


    //礼物数量寓意列表
    public function moral()
    {
        $gift = Db::name("gift_sum")->order("sort desc")->select();
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    //显示寓意增加列表
    public function add_moral()
    {
        $id = input('param.id');
        if ($id) {
            $gift = Db::name("gift_sum")->where("id=$id")->find();
            $this->assign('gift', $gift);
        }
        return $this->fetch();
    }

    //显示寓意增加列表
    public function addPost_moral()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];

        if ($id) {
            $result = Db::name("gift_sum")->where("id=$id")->update($data);
        } else {
            $result = Db::name("gift_sum")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('gift/moral'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //修改排序
    public function upd_moral()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("gift_sum")->where("id=$k")->update(array('sort' => $v));
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

    //删除
    public function del_moral()
    {
        $param = request()->param();
        $result = Db::name("gift_sum")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    /*
     * 查看背包
     * */
    public function gift_bag()
    {
        $where = [];
        $request = input('request.');
        if (!input('request.page')) {
            session('admin_index', null);
        }
        if (input('request.uid') || input('request.giftid') || input('request.start_time') || input('request.end_time')) {
            session('admin_index', input('request.'));
        }

        if (session('admin_index.uid')) {
            $where['u.id'] = session('admin_index.uid');
        }
        if (session('admin_index.giftid') && session('admin_index.giftid') != '-1') {
            $where['b.giftid'] = intval(session('admin_index.giftid'));
        } else {
            session('admin_index.giftid', -1);
        }

        if (session('admin_index.end_time') && session('admin_index.start_time')) {
            $where['create_time'] = ['between', [strtotime(session('admin_index.start_time')), strtotime(session('admin_index.end_time'))]];
        }

        $list = db('user_bag')
            ->alias('b')
            ->join('gift g', 'g.id=b.giftid')
            ->join('user u', 'u.id=b.uid')
            ->field('u.user_nickname,g.name,b.*')
            ->where($where)
            ->where('giftnum>0')
            ->order('id desc')
            ->paginate(20, false, ['query' => request()->param()]);
        $gift_list = db('gift')->select();
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('request', session('admin_index'));
        $this->assign('page', $page);
        $config = load_cache('config');
        $this->assign('system_currency_name', $config['system_currency_name']);
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('gift_list', $gift_list);
        // 渲染模板输出
        return $this->fetch();
    }

    public function gift_bag_del()
    {
        $param = request()->param();
        $user_bag = Db::name("user_bag")->where("id=" . $param['id'])->find();

        $result = Db::name("user_bag")->where("id=" . $param['id'])->delete();
        if ($user_bag && $result) {
            $insert_log = array(
                'uid'         => $user_bag['uid'],
                'bag_id'      => $param['id'],
                'giftid'      => $user_bag['giftid'],
                'giftnum'     => 0,
                'type'        => 2,
                'old_giftnum' => $user_bag['giftnum'],
                'operator'    => session('ADMIN_ID'),
                'create_time' => time()
            );
            Db::name("user_bag_log")->insertGetId($insert_log);
        }
        return $result ? '1' : '0';
        exit;
    }

    public function gift_bag_add()
    {
        $id = input('param.id');
        if ($id) {
            $gift = Db::name("user_bag")->where("id=$id")->find();
        } else {
            $gift['giftid'] = 1;
        }
        $gift_list = db('gift')->where("is_delete=0")->select();
        $this->assign('gift', $gift);
        $this->assign('gift_list', $gift_list);
        return $this->fetch();
    }

    public function addBagPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];

        if ($id) {
            $info = Db::name("user_bag")->where("id = $id")->find();
            $insert_log = array(
                'uid'         => $info['uid'],
                'bag_id'      => $id,
                'giftid'      => $info['giftid'],
                'giftnum'     => $data['giftnum'],
                'type'        => 1,
                'old_giftnum' => $info['giftnum'],
                'operator'    => session('ADMIN_ID'),
                'create_time' => time()
            );
            $result = Db::name("user_bag")->where("id=$id")->update($data);
        } else {
            //是否该礼物
            $uid = $data['uid'];
            $giftid = $data['giftid'];
            $giftnum = $data['giftnum'];
            $info = Db::name("user_bag")->where("uid = $uid and giftid = $giftid")->find();
            $insert_log = array(
                'uid'         => $uid,
                'giftid'      => $giftid,
                'giftnum'     => $giftnum,
                'type'        => 3,
                'operator'    => session('ADMIN_ID'),
                'create_time' => time()
            );
            if ($info) {
                $insert_log['bag_id'] = $info['id'];
                $insert_log['old_giftnum'] = $info['giftnum'];
                $insert_log['giftnum'] = $giftnum + $info['giftnum'];
                $result = Db::name("user_bag")->where("uid = $uid and giftid = $giftid")->setInc('giftnum', $giftnum);
            } else {
                $result = Db::name("user_bag")->insertGetId($data);
                $insert_log['bag_id'] = $result;
                $insert_log['old_giftnum'] = 0;
            }
        }
        if ($result) {
            Db::name("user_bag_log")->insertGetId($insert_log);
            $this->success(lang('EDIT_SUCCESS'), url('gift/gift_bag'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //礼物动画列表
    public function gift_animation()
    {
        $gift = Db::name("gift_animation")->order("sort desc")->select();
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    //动画增加列表
    public function add_gift_animation()
    {
        $id = input('param.id');
        if ($id) {
            $gift = Db::name("gift_animation")->where("id=$id")->find();
        } else {
            $gift['status'] = 1;
            $gift['img'] = '';
        }
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    //动画增加列表
    public function addPost_gift_animation()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];

        if ($id) {
            $result = Db::name("gift_animation")->where("id=$id")->update($data);
        } else {
            $result = Db::name("gift_animation")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('gift/gift_animation'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //礼物类型列表
    public function gift_type()
    {
        $gift = Db::name("gift_type")->order("sort desc")->select();
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    //礼物类型增加列表
    public function add_gift_type()
    {
        $id = intval(input('param.id'));
        $gift = Db::name("gift_type")->where("id=$id")->find();
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    //礼物类型增加列表
    public function addPost_gift_type()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['create_time'] = time();
        if ($id) {
            $result = Db::name("gift_type")->where("id=$id")->update($data);
        } else {
            $result = Db::name("gift_type")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('gift/gift_type'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

}
