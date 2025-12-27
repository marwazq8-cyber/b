<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminIndexController extends AdminBaseController
{
    // 机器人列表
    public function robot(){
        $where = [];
        if (!input('request.page')) {
            $data = array();
            session('admin_robot', $data);
        }
        if (input('request.uid') || input('request.keyword') || input('request.user_status') || input('request.sex')) {
            session('admin_robot', input('request.'));
        }
        $where['is_robot'] = 1;
        if (session('admin_robot.uid')) {
            $where['id'] = session('admin_robot.uid');
        }
        if (session('admin_index.user_status') >= '0') {
            $where['user_status'] = intval(session('admin_robot.user_status')) == '0' ? '0' : ['<>', 0];
        } else {
            session('admin_robot.user_status', -1);
        }
        if (session('admin_robot.sex') >= '0') {
            $where['sex'] = intval(session('admin_robot.sex'));
        } else {
            session('admin_robot.sex', -1);
        }
        $keywordComplex = [];
        if (session('admin_robot.keyword')) {
            $keyword = session('admin_robot.keyword');

            $keywordComplex['user_login|user_nickname|user_email|mobile'] = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');
        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("id DESC")->paginate(20, false, ['query' => request()->param()]);
        $lists = $list->toArray();
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $lists['data']);
        $this->assign('request', session('admin_robot'));
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }
    //编辑机器人列表
    public function edit_robot()
    {
        $id = input('param.id', 0, 'intval');
        if($id){
            $user_info = db('user')->find($id);
        }else{
            $user_info = array(
                'user_nickname' => '',
                'sex' => 1,
                'avatar' => '',
                'user_status' => 1,
                'id' => '',
            );
        }
        $this->assign('data', $user_info);
        // 渲染模板输出
        return $this->fetch();
    }

    //编辑信息保存
    public function edit_post_robot()
    {
        $id = input('param.id', 0, 'intval');
        $user_nickname = input('param.user_nickname');
        $avatar = input('param.avatar');
        $sex = input('param.sex');
        if (empty($user_nickname)) {
            $this->error(lang('Nickname_cannot_be_empty'));
            exit;
        }
        $data = array(
            'user_nickname'    => $user_nickname,
            'avatar'           => $avatar,
            'sex'              => $sex,
            'mobile' => 'robot',
            'user_status' => 1,
            'is_robot'=> 1
        );
        if($id){
            db('user')->where('id', '=', $id)->update($data);
        }else{
            $id = db('user')->insertGetId($data);
        }
        if ($id) {
            // 用户im下线
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $api = createTimAPI();
            $ret = $api->account_import($id, $data['user_nickname'], $data['avatar']);
            if ($ret['ActionStatus'] != 'OK'){
                bogokjLogPrint("edit_post_robot", "id=".$id."; im登录失败=".json_encode($ret));
            }
        }

        $this->success(lang('Operation_successful'));
    }
    //本站用户列表
    public function index()
    {
        $where = [];
        $request = input('request.');
        if (!input('request.page')) {
            $data = array(
                'is_exchange' => '-1',
                'country_code' => '0'
            );
            session('admin_index', $data);
        }
        if (input('request.uid') || input('request.user_type') || input('request.country_code') || input('request.is_exchange') >= '0' || input('request.reference') || input('request.device_uuid') || input('request.is_auth') || input('request.user_status') || input('request.sex') || input('request.order') || input('request.keyword') || input('request.start_time') || input('request.end_time') || input('request.start_time2') || input('request.end_time2') || input('request.is_online')) {

            session('admin_index', input('request.'));
        }
        $where['is_robot'] = 0;
        if (session('admin_index.is_exchange') >= '0') {
            $where['is_exchange'] = intval(session('admin_index.is_exchange'));
        }
        if (session('admin_index.uid')) {
            $where['id|luck'] = session('admin_index.uid');
        }
        if (session('admin_index.user_type') > 0) {
            $where['user_type'] = session('admin_index.user_type');
        } else {
            session('admin_index.user_type', -1);
        }
        if (session('admin_index.reference') && session('admin_index.reference') != '-1') {
            $where['reference'] = intval(session('admin_index.reference'));
        } else {
            session('admin_index.reference', -1);
        }

        if (session('admin_index.is_auth') >= '0') {
            $where['is_auth'] = intval(session('admin_index.is_auth'));
        } else {
            session('admin_index.is_auth', -1);
        }

        if (session('admin_index.user_status') >= '0') {
            $where['user_status'] = intval(session('admin_index.user_status')) == '0' ? '0' : ['<>', 0];
        } else {
            session('admin_index.user_status', -1);
        }
         if (session('admin_index.country_code')) {
             $where['country_code'] = intval(session('admin_index.country_code'));
         } else {
             session('admin_index.country_code', 0);
         }

        if (session('admin_index.sex') >= '0') {
            $where['sex'] = intval(session('admin_index.sex'));
        } else {
            session('admin_index.sex', -1);
        }
        if (session('admin_index.end_time') && session('admin_index.start_time')) {
            $where['create_time'] = ['between', [strtotime(session('admin_index.start_time')), strtotime(session('admin_index.end_time'))]];
        }
        if (session('admin_index.end_time2') && session('admin_index.start_time2')) {
            $where['last_login_time'] = ['between', [strtotime(session('admin_index.start_time2')), strtotime(session('admin_index.end_time2'))]];
        }
        if (session('admin_index.is_online') >= '0') {
            $where['is_online'] = intval(session('admin_index.is_online'));
        } else {
            session('admin_index.is_online', -1);
        }
        if (session('admin_index.order') && intval(session('admin_index.order')) != -1) {
            if (session('admin_index.order') == 1) {
                $order = 'income_total';
            } elseif (session('admin_index.order') == 2) {
                $order = 'coin';
            } else {
                $order = 'level';
            }

        } else {
            $order = 'create_time';
            session('admin_index.order', -1);
        }

        $keywordComplex = [];
        if (session('admin_index.keyword')) {
            $keyword = session('admin_index.keyword');

            $keywordComplex['user_login|user_nickname|user_email|mobile'] = ['like', "%$keyword%"];
        }
        $keyworduuid = [];

        if (session('admin_index.device_uuid')) {
            $device_uuid = session('admin_index.device_uuid');
            $keyworduuid['device_uuid'] = ['like', "%$device_uuid%"];
        }

        $usersQuery = Db::name('user');

        $list = $usersQuery->whereOr($keywordComplex)->whereOr($keyworduuid)->where($where)->order("$order DESC")->paginate(20, false, ['query' => request()->param()]);
        $lists = $list->toArray();
    //    $noble = db('noble')->where(['status'=>1])->select();

        // var_dump($lists);
        foreach ($lists['data'] as &$v) {
            if ($v['vip_end_time'] <= time()) {
                $v['vip_end_time'] = '无';
            } else {
                $v['vip_end_time'] = date('Y-m-d H:i', $v['vip_end_time']);
            }
            // 贵族
            if ($v['noble_end_time'] <= time()) {
                $v['noble_end_time'] = '';
                $v['noble_name'] = '';
            } else {
                $v['noble_end_time'] = date('Y-m-d H:i', $v['noble_end_time']);
                $noble_ley="noble_name_user_".$v['id'];
                $noble_name = redis_Get($noble_ley);
                if(!$noble_name){
                    $noble = db('noble')->field('name')->find($v['nobility_level']);
                    $noble_name =$noble ?$noble['name'] : '';
                    redis_set($noble_ley,$noble_name,60);
                }
                $v['noble_name'] = $noble_name;
            }

            if (isset($v['country_code']) && $v['country_code'] != 0) {
                $country = get_country_one(intval($v['country_code'])); // 获取国家
                if ($country) {
                    $v['country_name'] = $country['name'];
                    $v['country_flag_img_url'] = $country['img'];
                }
            }
            $uid = $v['id'];
            // 房间名片 --vip特权
            $vip_name = get_user_vip_authority($uid, 'title');
            $v['vip_name'] = $vip_name;

            $find = Db::name("user_reference")->where("uid=$uid")->find();
            if ($find) {
                $v['reference'] = '1';
            } else {
                $v['reference'] = '0';
            }
            $user = Db::name("invite_record")->alias("a")->join("user u", "u.id=a.user_id")
                ->field('u.user_nickname,a.user_id')->where("a.invite_user_id=$uid")->find();
            $attention = Db::name("user_attention")->where("uid=$uid")->count();
            $fans = Db::name("user_attention")->where("attention_uid=$uid")->count();

            //    $invite_coin = Db::name("invite_profit_record")->where("user_id=$uid")->sum("money");
            $cash_record = Db::name("invite_cash_record")->where('uid=' . $uid . ' and status !=2')->sum("coin");
            $v['invite_withdrawal'] = $cash_record;
            $where_money['r.status'] = 1;
            $where_money['r.user_id'] = $uid;
            /* $v['income'] = db('user_cash_record')
                 ->alias('r')
                 ->join("user u", "u.id=r.user_id")
                 ->join("user_cash_account c", "c.id=r.paysid")
                 ->where($where_money)->sum("r.income");*/
            $v['money'] = db('user_cash_record')
                ->alias('r')
                ->join("user u", "u.id=r.user_id")
                ->join("user_cash_account c", "c.id=r.paysid")
                ->where($where_money)->sum("r.money");


            $v['invite_user_name'] = $user && $user['user_nickname'] ? $user['user_nickname'] : '';//$user['user_nickname'] ? $user['user_nickname'] : '';
            $v['invite_user_id'] = $user && $user['user_id'] ? $user['user_id'] : '';//$user['user_id'] ? $user['user_id'] : '';
            $v['attention'] = $attention ? $attention : '0';
            $v['fans'] = $fans ? $fans : '0';
            //     $v['invite_coin'] = $invite_coin ? $invite_coin : '0';

            //付费概率
            //总单
            $total_order = db('user_charge_log')->where('uid=' . $v['id'])->count();
            //成功单
            $success_order = db('user_charge_log')->where('uid=' . $v['id'] . ' and status=1')->count();
            $v['recharge_probability'] = 0;
            if ($success_order != 0) {
                $v['recharge_probability'] = ($success_order / $total_order * 100);
            }

            $v['device_info'] = db("device_info")->where("uid={$v['id']}")->find();

            //是否禁封设备号
            $device = db('equipment_closures')->where('device_uuid', $v['device_uuid'])->find();
            $v['is_device'] = $device ? 2 : 1;
            if (IS_MOBILE == 0) {
                $v['mobile'] = substr($v['mobile'], 0, 3) . '****' . substr($v['mobile'], strlen($v['mobile']) - 4, 4);
            } elseif (session('ADMIN_GROUPS_ID') == 6) {
                $v['mobile'] = substr($v['mobile'], 0, 5) . '****' . substr($v['mobile'], 9);
            }

        }

        // 获取分页显示
        $page = $list->render();

        $file = file_get_contents(DOCUMENT_ROOT . "/countries.json");
        $countries = json_decode($file, true);

        $this->assign('countries', $countries);
        $this->assign('list', $lists['data']);
   //     $this->assign('noble', $noble);
        $this->assign('request', session('admin_index'));
        $this->assign('page', $page);
        $config = load_cache('config');
        $this->assign('system_currency_name', $config['system_currency_name']);
        $this->assign('currency_name', $config['currency_name']);
        // 渲染模板输出
        return $this->fetch();
    }
    // 取消贵族
    public function clear_noble(){
        $root=array('status'=>1,'msg'=>lang('Operation_succeeded'));
        $id = input('param.id', 0, 'intval');
        if ($id) {
            db("user")->where('id', '=', $id)->update(['noble_end_time'=>'']);
            redis_rm("noble_name_user_".$id);
            $data = array(
                'uid'=>$id,
                'admin_id'=> cmf_get_current_admin_id(),
                'time'=>NOW_TIME
            );
            redis_RPush("admin_clear_noble",json_encode($data));
        } else {
            $root=array('status'=>0,'msg'=>lang('operation_failed'));
        }
        echo json_encode($root);
    }
    /**
     * 本站用户拉黑
     * @adminMenu(
     *     'name'   => '本站用户拉黑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户拉黑',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            if ($result) {
                $user['id'] = $id;
                $message = Db::name("user_message")->where("id=2")->find();

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                im_shut_up($id, 4294967295);
                $this->success(lang('Operation_successful'));

            } else {
                $this->error(lang('Failed_to_blackmail_member_does_not_exist'));
            }
        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    /**
     * 本站用户启用
     * @adminMenu(
     *     'name'   => '本站用户启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户启用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');

        if ($id) {
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            $user['id'] = $id;
            require_once DOCUMENT_ROOT . '/system/im_common.php';
            im_shut_up($id, time());

            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    /*
    *拉黑和开启用户
    */
    public function ban_type()
    {
        $id = input('param.id', 0, 'intval');
        $day = input('param.day', 0, 'intval');
        $hours = input('param.hours', 0, 'intval');
        $minutes = input('param.minutes', 0, 'intval');
        $seconds = input('param.seconds', 0, 'intval');
        if ($id) {
            $user['id'] = $id;
            require_once DOCUMENT_ROOT . '/system/im_common.php';
            $time = 0;
            $time += $day == 0 ? 0 : 60 * 60 * 24 * $day;
            $time += $hours == 0 ? 0 : 60 * 60 * $hours;
            $time += $minutes == 0 ? 0 : 60 * $minutes;
            $time += $seconds == 0 ? 0 : $seconds;


            $type = $time == 0 ? 1 : 0;

            $end_time = $time == 0 ? 0 : NOW_TIME + $time;

            $data['shielding_time'] = $end_time;
            $data['user_status'] = $type;

            db('user')->where('id =' . $id . " and user_type=2")->update($data);

            im_shut_up($id, $time);

            echo json_encode(['code' => 1, 'msg' => lang('Operation_successful')]);
        } else {
            echo json_encode(['code' => 0, 'msg' => lang('operation_failed')]);
        }
    }

    //推荐用户
    public function reference()
    {
        $id = input('param.id', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        if ($id) {
            $user['id'] = $id;
            if ($type == '1') {
                $data = array(
                    'uid'     => $id,
                    'addtime' => time(),
                );
                Db::name("user_reference")->insert($data);
                db('user')->where('id', '=', $id)->setField('reference', 1);
                //$stat="已成为推荐用户";
            } else {
                Db::name("user_reference")->where("uid=$id")->delete();
                db('user')->where('id', '=', $id)->setField('reference', 0);
                //$stat="已取消推荐";
            }
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    //账户管理
    public function account()
    {
        exit;
        $id = input('param.id', 0, 'intval');
        $coin = intval(input('param.coin'));
        $type = intval(input('param.type'));
        $ctype = intval(input('param.ctype'));
        $user = db('user')->where('id', '=', $id)->find();
        $data = array(
            'uid'       => $id,
            'coin'      => abs($coin),
            'coin_type' => $type,
            'addtime'   => time(),
        );
        if ($type == 1) {
            $coin_val = 'coin';
        } else {
            $coin_val = 'friend_coin';
        }

        if ($ctype == 1) {
            db('user')->where('id', '=', $id)->setInc($coin_val, $coin);
            $data['user_type'] = 1;
            $data['type'] = 1;
        } else {
            db('user')->where('id', '=', $id . ' and ' . $coin_val . ' > ' . $coin)->setDec($coin_val, $coin);
            $data['user_type'] = 1;
            $data['type'] = 2;
        }
        $data['operator'] = cmf_get_current_admin_id();
        db('recharge_log')->insert($data);
        echo json_encode(['code' => 1]);
        exit;
    }

    //渠道绑定设置
    public function linkSet()
    {
        $id = input('param.id', 0, 'intval');
        $link_id = input('param.link_id', 0, 'intval');

        $user = db('user')->where('id', '=', $id)->find();

        $invite_record = db('invite_record')->where("invite_user_id='$id'")->find();
        if ($invite_record) {
            echo json_encode(['code' => 0, 'msg' => lang('user_invited_in_cannot_bound')]);
            exit;
        }
        if ($link_id) {
            $agent = db('agent')->where("id='" . $link_id . "'")->find();
            if (!$agent) {
                echo json_encode(['code' => 0, 'msg' => lang('model data Not Found')]);
                exit;
            }
            if ($user['link_id'] == $link_id) {
                echo json_encode(['code' => 1, 'msg' => lang('Operation_successful')]);
                exit;
            }
        } else {
            $link_id = 0;
        }


        $res = db('user')->where('id', '=', $id)->update(array('link_id' => $link_id));
        if ($res) {
            db('agent_register')->where('uid', '=', $id)->update(array('status' => 2, 'updtime' => time()));
            if ($link_id) {
                $data = array(
                    'uid'      => $id,
                    'agent_id' => $link_id,
                    'code'     => $agent['channel'],
                    'status'   => 1,
                    'addtime'  => time()
                );
                db('agent_register')->insert($data);
            }
            echo json_encode(['code' => 1, 'msg' => lang('Operation_successful')]);
            exit;
        }

        echo json_encode(['code' => 0, 'msg' => lang('operation_failed')]);
        exit;
    }

    //VIP时间设置
    public function vipSet()
    {
        $id = input('param.id', 0, 'intval');

        $vip_end_time = input('param.vip_end_time') ? strtotime(input('param.vip_end_time')) : 0;

        $user = db('user')->where('id', '=', $id)->find();
        $vip_log = array('user_id' => $id, 'admin_id' => $_SESSION['think']['ADMIN_ID'], 'vip_time' => $vip_end_time, 'ctime' => time());
        if ($vip_end_time == 0 && $user['vip_end_time'] == 0) {
            echo json_encode(['code' => 1, 'msg' => lang('Operation_successful')]);
            exit;
        }

        if (empty($vip_end_time) || $vip_end_time <= 0) {
            $data = array(
                'vip_end_time' => 0,
            );
            $type = 0;
        } else {
            $time = ($user['vip_end_time'] < time()) ? time() : $user['vip_end_time'];
            $data = array(
                'vip_end_time' => abs($vip_end_time),
            );
            $type = 1;
        }
        $res = db('user')->where('id', '=', $id)->update($data);

        if ($res) {
            if ($type == 1) {
                // 给用户添加vip商城特权
                add_vip_shop($id, abs($vip_end_time));
            } else {
                // 关闭vip特权

                $where_shop = "u.status=1 and s.is_vip=1 and u.uid=" . $id;
                $list = Db::name("shop")->alias('s')
                    ->join('shop_user u', 's.id=u.shop_id')
                    ->field('u.id')
                    ->group("s.id")
                    ->order('u.endtime desc')
                    ->where($where_shop)
                    ->select();
                foreach ($list as $v) {
                    db("shop_user")->where("id=" . $v['id'])->update(array('status' => 2));
                }

            }

            echo json_encode(['code' => 1, 'msg' => lang('Operation_successful')]);
            $vip_log['state'] = 1;
        } else {
            echo json_encode(['code' => 0, 'msg' => lang('operation_failed')]);
            $vip_log['state'] = 0;
        }
        db('user_vip_set_log')->insert($vip_log);
        exit;
    }

    public function userVipSetLog()
    {
        $pageWhere = ['query' => request()->param()];
        $where = [];

        $list = Db::name("user_vip_set_log")
            ->alias('c')
            ->join('user u', 'c.user_id=u.id', 'LEFT')
            ->join('user a', 'c.admin_id=a.id', 'LEFT')
            ->field('c.*,u.user_nickname as uname,a.user_nickname as aname')
            ->order('c.ctime desc')
            ->where($where)
            ->paginate(20, false, $pageWhere);

        // $list = Db::name("user_vip_set_log") ->select();
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch();


    }

    //排序
    public function upd()
    {

        $param = request()->param();
        foreach ($param['listorders'] as $k => $v) {

            Db::name("user")->where("id=$k")->update(array('sort' => $v));
        }

        $this->success(lang('Sorting_succeeded'));

    }

    //编辑用户信息
    public function edit()
    {

        $id = input('param.id', 0, 'intval');
        $user_info = db('user')->find($id);
        if (!$user_info) {
            $this->error(lang('user_does_not_exist'));
            exit;
        }

        $user = Db::name("invite_record")->where("invite_user_id=$id")->find();
        if ($user) {
            $user_info['invite_id'] = $user['user_id'];
        } else {
            $user_info['invite_id'] = lang('ADMIN_WITHOUT');
            $user_info['custom_video_charging_coin'] = '0';
        }
        if (IS_MOBILE == 0) {
            $user_info['mobile'] = substr($user_info['mobile'], 0, 3) . '****' . substr($user_info['mobile'], 7, 4);
        } elseif (session('ADMIN_GROUPS_ID') == 6) {
            $user_info['mobile'] = substr($user_info['mobile'], 0, 5) . '****' . substr($user_info['mobile'], 9);
        }

        $fee = Db::name("host_fee")->order("sort asc")->select();

        $file = file_get_contents(DOCUMENT_ROOT . "/countries.json");
        $countries = json_decode($file, true);
        $this->assign('countries', $countries);
        $this->assign('fee', $fee);
        $this->assign('data', $user_info);

        // 渲染模板输出
        return $this->fetch();
    }

    //编辑信息保存
    public function edit_post()
    {

        $id = input('param.id', 0, 'intval');
        $user_nickname = input('param.user_nickname');
        $avatar = input('param.avatar');
        $sex = input('param.sex');
        $invite_id = input('param.invite_id');
        $custom_video_charging_coin = input('param.custom_video_charging_coin');

        $is_online = input('param.is_online');
        $is_auth = input('param.is_auth');
        $is_robot = intval(input('param.is_robot'));
        $audio_file = input('param.audio_file');
        $audio_time = intval(input('param.audio_time'));
        $mobile_area_code = input('param.mobile_area_code');
        $mobile = input('param.mobile');
        $country_code = input('param.country_code');

        $user_type = intval(input('param.user_type')) < 4 && intval(input('param.user_type')) > 0 ? intval(input('param.user_type')) : 2;

        if (empty($user_nickname)) {
            $this->error(lang('Nickname_cannot_be_empty'));
            exit;
        }
        if ($invite_id) {
            $user = db('invite_code')->alias('i')->field("i.*")->join('user u', 'i.user_id = u.id')->where("u.id='$invite_id'")->find();
            if ($user) {
                $invite = Db::name("invite_record")->where("invite_user_id='$id'")->find();
                $data = array(
                    'user_id'        => $invite_id,
                    'invite_user_id' => $id,
                    'invite_code'    => $user['invite_code'],
                    'create_time'    => time(),
                );
                if ($invite) {
                    db('invite_record')->where('invite_user_id', '=', $id)->update($data);
                } else {
                    db('invite_record')->insert($data);
                }
            }
        }

        $data = array(
            'user_nickname'    => $user_nickname,
            'avatar'           => $avatar,
            'sex'              => $sex,
            'is_online'        => $is_online,
            'is_auth'          => $is_auth,
            'audio_file'       => $audio_file,
            'audio_time'       => $audio_time,
            'mobile_area_code' => $mobile_area_code,
            'is_robot'=> $is_robot,
            'country_code'=>$country_code
        );
        if($user_type){
            $data['user_type'] = $user_type;
        }
        if (strpos($mobile, '****') === false) {
            $data['mobile'] = $mobile;
        }
        if ($custom_video_charging_coin) {
            $data['custom_video_charging_coin'] = $custom_video_charging_coin;
        }
        db('user')->where('id', '=', $id)->update($data);
        if ($user_type == 3) {
            // 注销账户
            close_delete_voice($id);
        }
        $this->success(lang('Operation_successful'));
    }

    public function invitation()
    {
        $where = [];
        $uid = input('id');
        $sex = input('sex');

        if (empty($sex)) {
            $where['i.user_id'] = $uid;
        } else if ($sex == 0) {
            $where['i.user_id'] = $uid;
        } else if ($sex == 1) {
            $where['i.user_id'] = $uid;
            $where['u.sex'] = $sex;
        } else if ($sex == 2) {
            $where['i.user_id'] = $uid;
            $where['u.sex'] = $sex;
        }

        $invite = db('invite_record')
            ->alias('i')
            ->join('user u', 'i.invite_user_id = u.id')
            ->where($where)
            ->field('u.id,u.sex,u.user_nickname,i.user_id')
            ->select();
        $cc = [];
        foreach ($invite as $val) {
            $count = db('invite_profit_record')->where(['invite_user_id' => $val['id'], 'user_id' => $val['user_id']])->sum('money');
            $val['count'] = $count;
            $cc[] = $val;
        }

        $numpeo = db('invite_record')->where('user_id', $uid)->count();
        $mnum = db('invite_record')
            ->alias('i')
            ->join('user u', 'i.invite_user_id = u.id')
            ->where(["i.user_id" => $uid, 'sex' => 1])
            ->count();
        $wnum = db('invite_record')
            ->alias('i')
            ->join('user u', 'i.invite_user_id = u.id')
            ->where(["i.user_id" => $uid, 'sex' => 2])
            ->count();
        $nummoney = db('invite_profit_record')->where(['user_id' => $uid])->sum('money');

        $data = [
            'mnum'     => $mnum,
            'wnum'     => $wnum,
            'numpeo'   => $numpeo,
            'nummoney' => $nummoney,
            'uid'      => $uid,
        ];
        $this->assign($data);
        $this->assign('invite', $cc);
        // 渲染模板输出
        return $this->fetch();
    }

    /*导出*/
    public function export()
    {

        $where = [];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        if (isset($request['reference']) && $request['reference'] != '-1') {
            $where['reference'] = intval($request['reference']);
        }
        if (isset($request['is_auth']) && $request['is_auth'] != '-1') {
            $where['is_auth'] = intval($request['is_auth']);
            if (intval($request['is_auth']) == '1') {
                $title = lang('ADMIN_ANCHOR_LIST');
            } else {
                $title = lang('ADMIN_USER_LIST');
            }
        } else {
            $title = lang('Member_list');
        }

        if (isset($request['user_status']) && intval($request['user_status']) >= '0') {
            $where['user_status'] = intval($request['user_status']) == '0' ? '0' : ['<>', 0];
        }

        if (isset($request['is_online']) && intval($request['is_online']) >= 0) {
            $where['is_online'] = intval($request['is_online']);
        }

        if (isset($request['sex']) && intval($request['sex']) != -1) {
            $where['sex'] = intval($request['sex']);
        }

        if (isset($request['order']) && intval($request['order']) != -1) {
            $order = 'income_total';
        } else {
            $order = 'create_time';

        }
        if ($request['end_time'] && $request['start_time']) {
            $where['create_time'] = ['between', [strtotime($request['start_time']), strtotime($request['end_time'])]];
        }
        if ($request['end_time2'] && $request['start_time2']) {
            $where['last_login_time'] = ['between', [strtotime($request['start_time2']), strtotime($request['end_time2'])]];
        }

        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['user_login|user_nickname|user_email|mobile'] = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');

        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("$order DESC")->select();
        $lists = $list->toArray();
        if ($lists != null) {
            // print_r($lists);exit;
            foreach ($lists as $k => $v) {
                $uid = $v['id'];
                $find = Db::name("user_reference")->where("uid=$uid")->find();
                if ($find) {
                    $v['reference'] = '1';
                } else {
                    $v['reference'] = '0';
                }
                $user = Db::name("invite_profit_record")->alias("a")->join("user u", "u.id=a.user_id")
                    ->field('u.user_nickname,a.user_id')->where("a.invite_user_id=$uid")->find();

                $dataResult[$k]['id'] = $uid ? $uid : lang('No_data');
                $dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : lang('No_data');
                $dataResult[$k]['sex'] = $v['sex'] == '1' ? lang('MALE') : lang('FEMALE');
                $dataResult[$k]['level'] = $v['level'];
                $dataResult[$k]['mobile'] = $v['mobile'] && IS_MOBILE == 1 ? $v['mobile'] : lang('No_information');
                $dataResult[$k]['is_auth'] = $v['is_auth'] == 1 ? lang('YES') : lang('NO');
                $dataResult[$k]['is_player'] = $v['is_player'] == 1 ? lang('YES') : lang('NO');
                $dataResult[$k]['is_talker'] = $v['is_talker'] == 1 ? lang('YES') : lang('NO');

                $dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : '0';
                $dataResult[$k]['income'] = $v['income'] ? $v['income'] : '0';
                $dataResult[$k]['income_total'] = $v['income_total'] ? $v['income_total'] : '0';
                $dataResult[$k]['invite_user_name'] = $user['user_nickname'] ? $user['user_nickname'] : lang('ADMIN_WITHOUT');
                $dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : lang('No_information');

            }

            $str = "ID," . lang('USER') . "," . lang('GENDER') . "," . lang('Grade') . "," . lang('ADMIN_PHONE_NUMBER') . "," . lang('Is_it_certified') . "," . lang('Accompany_certification') . ",陪聊认证,余额,收益,累计收益,邀请人," . lang('REGISTRATION_TIME');

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error(lang('No_data'));
        }
    }

    //添加用户
    public function add_user()
    {
        $file = file_get_contents(DOCUMENT_ROOT . "/countries.json");
        $countries = json_decode($file, true);
        $this->assign('countries', $countries);
        return $this->fetch();
    }

    //查看用户关注和粉丝
    public function attention()
    {
        $id = input('request.id');
        $type = input('request.type');
        $root = array('status' => 0, 'msg' => lang('No_data'), 'data' => []);
        if ($type == '2') {
            $where = "a.uid=$id";
            $attention = Db::name("user_attention")->alias("a")->field("a.*,u.user_nickname")->join('user u', 'a.attention_uid = u.id')->where($where)->select();
        } else {

            $attention = Db::name("user_attention")->alias("a")->field("a.*,u.user_nickname")->join('user u', 'a.uid = u.id')->where("a.attention_uid=" . $id)->select();
            //  var_dump(Db::name("user_attention")->getLastSql());exit;
        }

        $html = '';

        if ($attention) {
            foreach ($attention as $v) {
                $sid = $type == '2' ? $v['attention_uid'] : $v['uid'];
                $html .= '<tr><td>' . $sid . '</td><td style="width:150px;overflow:hidden;">' . $v['user_nickname'] . '</td><td>' . date("Y-m-d", $v['addtime']) . '</td></tr>';
            }
            $root['status'] = '1';
            $root['data'] = $html;
        }
        if ($html == '') {
            $root['status'] = '0';
        }
        echo json_encode($root);
    }

    public function addUserPost()
    {
        $user_nickname = input('param.user_nickname');
        $avatar = input('param.avatar');
        $sex = intval(input('param.sex')) == 1 ? 1 : 2;
        $user_login = input('param.user_login');
        $is_online = input('param.is_online');
        $is_auth = input('param.is_auth');
        $is_robot = intval(input('param.is_robot'));
        $audio_file = input('param.audio_file');
        $audio_time = intval(input('param.audio_time'));
        $mobile_area_code = input('param.mobile_area_code');
        $mobile = input('param.mobile');
        $num_code = input('param.num_code');

        $login = db('user')->where('user_login', $user_login)->find();

        if (!empty($login)) {
            $this->error(lang('User_name_already_exists'));
        }
        if (empty($user_nickname)) {
            $this->error(lang('Nickname_cannot_be_empty'));
        }
        $nickname = db('user')->where('user_nickname', $user_nickname)->find();
        if (!empty($nickname)) {
            $this->error(lang('Nickname_already_exists'));
        }
        if (empty($avatar)) {
            $this->error(lang('Avatar_cannot_be_empty'));
        }

        $data = array(
            'user_login'    => $user_login,
            'user_nickname'    => $user_nickname,
            'avatar'           => $avatar,
            'sex'              => $sex,
            'is_online'        => $is_online,
            'is_auth'          => $is_auth,
            'audio_file'       => $audio_file,
            'audio_time'       => $audio_time,
            'user_type'        => 2,
            'mobile_area_code' => $mobile_area_code,
            'mobile' => $mobile,
            'create_time'   => time(),
            'is_robot'=> $is_robot,
            'country_code'=>$num_code
        );
        $res = db('user')->insertGetId($data);
        if ($res) {
            $this->success(lang('ADD_SUCCESS'));
        } else {
            $this->error(lang('ADD_FAILED'));
        }
    }


    //禁用头像
    public function edit_img()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $user['id'] = $id;
            $config = load_cache('config');

            $res = db("user")->where('id', '=', $id)->update(array("avatar" => $config['user_avatar']));

            if ($res) {
                $this->success(lang('Operation_successful'));
            } else {
                $this->error(lang('operation_failed'));
            }

        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    //封禁设备
    public function add_closures()
    {
        $request = request()->param();
        if (!isset($request['device_uuid'])) {
            $this->success(lang('No_equipment_number'));
        }
        $device = db('equipment_closures')->where('device_uuid', $request['device_uuid'])->select();
        $is_im_login = 1; // im下线
        if (count($device) > 0) {
            $is_im_login = 0;
            //  $this->success('该设备已封禁！');
            $res = db('equipment_closures')->where('device_uuid', $request['device_uuid'])->delete();
        } else {
            if ($request['device_uuid'] == '00000000-0000-0000-0000-000000000000') {
                $this->error(lang('Unauthorized_access_to_device_ID'));
            }
            $data = [
                'uid'         => $request['uid'],
                'device_uuid' => $request['device_uuid'],
                'addtime'     => time(),
            ];
            $res = db('equipment_closures')->insert($data);
        }

        if ($res) {
            if($is_im_login == 1){
                // 用户im下线
                require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
                $api = createTimAPI();
                $api->kick($request['uid']. '');
            }
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    //取消主播认证
    public function cancel_auth()
    {
        $id = input('param.id', 0, 'intval');
        $root['status'] = 1;
        $root['msg'] = '';
        if ($id) {
            //改变认证状态
            $res = db("user")->where('id', '=', $id)->update(array("is_auth" => 0));
            //删除认证资料
            db('auth_form_record')->where('user_id', '=', $id)->delete();
            if (!$res) {
                $root['status'] = 0;
                $root['msg'] = lang('operation_failed');
            }
        } else {
            $root['status'] = 0;
            $root['msg'] = lang('Data_transfer_in_failed');
        }

        echo json_encode($root);
        exit;
    }

    public function set_exchange()
    {
        $id = input('param.id', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        $root['status'] = 1;
        $root['msg'] = '';
        if ($id) {
            if ($type == 1) {
                //开启好友兑换
                $res = db("user")->where('id', '=', $id)->update(array("is_exchange" => 1));
            } else {
                $res = db("user")->where('id', '=', $id)->update(array("is_exchange" => 0));
            }
            if (!$res) {
                $root['status'] = 0;
                $root['msg'] = lang('operation_failed');
            }
        } else {
            $root['status'] = 0;
            $root['msg'] = lang('Data_transfer_in_failed');
        }

        echo json_encode($root);
        exit;
    }

    public function set_test()
    {
        $id = input('param.id', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        $root['status'] = 1;
        $root['msg'] = '';
        if ($id) {
            if ($type == 1) {
                //改变认证状态
                $res = db("user")->where('id', '=', $id)->update(array("is_test" => 1));
            } else {
                $res = db("user")->where('id', '=', $id)->update(array("is_test" => 0));
            }
            if (!$res) {
                $root['status'] = 0;
                $root['msg'] = lang('operation_failed');
            }
        } else {
            $root['status'] = 0;
            $root['msg'] = lang('Data_transfer_in_failed');
        }

        echo json_encode($root);
        exit;
    }

    /*
     * 账户清0*/
    public function clear_coin()
    {
        $id = input('param.id', 0, 'intval');
        $root['status'] = 1;
        $root['msg'] = '';
        if ($id) {
            //清除
            $data = array("coin" => 0, "friend_coin" => 0, "income" => 0, "income_total" => 0, "invitation_coin" => 0);
            $res = db("user")->where('id', '=', $id)->update($data);
            // 清除背包礼物
            $data = array("giftnum" => 0);
            db("user_bag")->where('uid', '=', $id)->update($data);
            echo 1;
            exit;
        } else {
            echo 0;
            exit;
        }

    }

    //封禁IP
    public function add_closures_ip()
    {
        $request = request()->param();
        if (!isset($request['ip'])) {
            $this->success(lang('No_IP_address'));
        }
        //封禁ip
        $close_ip = db('close_ip')->where('ip', $request['ip'])->select();
        if (count($close_ip) > 0) {
            $res = true;
        } else {
            $data = [
                'ip'      => $request['ip'],
                'addtime' => time(),
            ];
            $res = db('close_ip')->insert($data);
        }
        if ($res) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    // 取消禁封的ip
    public function delete_close_ip()
    {
        $request = request()->param();
        $id = $request['id'];
        $close_ip = Db::name('close_ip')->where('id=' . $id)->delete();
        echo $close_ip ? 1 : 0;
        exit;
    }

    // 获取禁封ip列表
    public function close_ip()
    {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('ip')) {
            $data['uid'] = '';
            $data['ip'] = '';
            session("close_ip", $data);
        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['ip'] = $this->request->param('ip');
            session("close_ip", $data);
        }

        $id = intval(session("close_ip.uid"));
        $ip = session("close_ip.ip");

        $where = "c.id > 0 ";
        $where .= $id ? " and u.id=" . intval($id) : '';
        $where .= $ip ? " and c.ip like '%" . trim($ip) . "%'" : '';

        $page = 10;
        $data = Db::name('close_ip')->alias("c")
            ->join("user u", "u.last_login_ip = c.ip", 'left')
            ->field("c.*")
            ->where($where)
            ->group("c.ip")
            ->order('c.addtime desc')
            ->paginate($page, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign("data", session("close_ip"));
        return $this->fetch();
    }

    private function get_type_log()
    {
        // 1充值 2聊天 3视频通话 4送礼物 5语音通话 6签到 7任务 8VIP 9装扮 10贵族 11退出公会(无) 12无 13无 14无 15兑换 16浇树 17宝箱 18三方游戏 19无 20退出家族(无) 21邀请领取奖励 100后台手动操作
        $type_val = array();

        $type_val[] = array(
            'id'    => '1',
            'title' => lang('Recharge'),
        );

        $type_val[] = array(
            'id'    => '2',
            'title' => lang('聊天'),
        );
        $type_val[] = array(
            'id'    => '3',
            'title' => lang('视频通话'),
        );
        $type_val[] = array(
            'id'    => '4',
            'title' => lang('送礼物'),
        );
        $type_val[] = array(
            'id'    => '5',
            'title' => lang('语音通话'),
        );
        $type_val[] = array(
            'id'    => '6',
            'title' => lang('签到'),
        );
        $type_val[] = array(
            'id'    => '7',
            'title' => lang('任务'),
        );
        $type_val[] = array(
            'id'    => '8',
            'title' => 'VIP',
        );
        $type_val[] = array(
            'id'    => '9',
            'title' => lang('装扮'),
        );
        $type_val[] = array(
            'id'    => '10',
            'title' => lang('贵族'),
        );
        $type_val[] = array(
            'id'    => '15',
            'title' => lang('兑换'),
        );
        $type_val[] = array(
            'id'    => '16',
            'title' => lang('浇树'),
        );
        $type_val[] = array(
            'id'    => '17',
            'title' => lang('宝箱'),
        );
        $type_val[] = array(
            'id'    => '18',
            'title' => lang('三方游戏'),
        );
        $type_val[] = array(
            'id'    => '21',
            'title' => lang('邀请领取奖励'),
        );
        $type_val[] = array(
            'id'    => '22',
            'title' => lang('用户注册时赠送'),
        );
        $type_val[] = array(
            'id'    => '100',
            'title' => lang('后台操作'),
        );
        $type_one = [];
        foreach ($type_val as $v) {
            $type_one[$v['id']] = $v['title'];
        }

        return array(
            'type_one'  => $type_one,
            'type_list' => $type_val
        );
    }

    // 用户消费记录
    public function consumption_records()
    {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('coin_type') and !$this->request->param('type') and !$this->request->param('classification') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['uid'] = '';
            $data['type'] = '0';
            $data['classification'] = '';
            $data['start_time'] = '';
            $data['end_time'] = '';
            $data['coin_type'] = '0';
            session("consumption_records", $data);
        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['type'] = $this->request->param('type');
            $data['classification'] = $this->request->param('classification');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['coin_type'] = $this->request->param('coin_type');
            session("consumption_records", $data);
        }

        $id = intval(session("consumption_records.uid"));
        $type = intval(session("consumption_records.type"));
        $coin_type = intval(session("consumption_records.coin_type"));
        $classification = intval(session("consumption_records.classification"));
        $end_time = session('consumption_records.end_time');
        $start_time = session('consumption_records.start_time');
        $where = "l.id > 0 ";
        $where .= $id ? " and l.uid=" . $id : '';
        $where .= $type ? " and l.type=" . $type : '';
        $where .= $coin_type ? " and l.coin_type=" . $coin_type : '';
        if ($classification > 0) {
            $where .= $classification == 1 ? " and l.coin > 0" : ' and l.coin < 0';
        }
        if ($end_time && $start_time) {
            $where .= " and l.create_time >=" . strtotime($start_time) . " and l.create_time <=" . strtotime($end_time);
        }

        $page = 15;
        $data = Db::name('user_coin_log')->alias("l")
            ->join("user u", "u.id = l.uid")
            ->field("l.*,u.user_nickname")
            ->where($where)
            ->order('l.create_time desc,l.id desc')
            ->paginate($page, false, ['query' => request()->param()]);

        $type_list = $this->get_type_log();
        $type_one = $type_list['type_one'];
        $list = $data->toArray();
        foreach ($list['data'] as &$v) {
            $v['type_name'] = isset($type_one[$v['type']]) ? $type_one[$v['type']] : $v['type'];
        }
        $this->assign('page', $data->render());
        $this->assign('list', $list['data']);
        $this->assign('type_list', $type_list['type_list']);
        $this->assign("data", session("consumption_records"));
        $config = load_cache('config');
        $this->assign('system_currency_name', $config['system_currency_name']);
        $this->assign('currency_name', $config['currency_name']);
        return $this->fetch();
    }

    // 用户收益记录
    public function revenue_records()
    {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('type') and !$this->request->param('classification') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['uid'] = '';
            $data['type'] = '0';
            $data['classification'] = '';
            $data['start_time'] = '';
            $data['end_time'] = '';
            session("consumption_records", $data);
        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['type'] = $this->request->param('type');
            $data['classification'] = $this->request->param('classification');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("consumption_records", $data);
        }

        $id = intval(session("consumption_records.uid"));
        $type = intval(session("consumption_records.type"));
        $classification = intval(session("consumption_records.classification"));
        $end_time = session('consumption_records.end_time');
        $start_time = session('consumption_records.start_time');
        $where = "l.id > 0 ";
        $where .= $id ? " and l.uid=" . $id : '';
        $where .= $type ? " and l.type=" . $type : '';
        if ($classification > 0) {
            $where .= $classification == 1 ? " and l.income > 0" : ' and l.income < 0';
        }
        if ($end_time && $start_time) {
            $where .= " and l.create_time >=" . strtotime($start_time) . " and l.create_time <=" . strtotime($end_time);
        }

        $page = 15;
        $data = Db::name('user_income_log')->alias("l")
            ->join("user u", "u.id = l.uid")
            ->field("l.*,u.user_nickname")
            ->where($where)
            ->order('l.create_time desc,l.id desc')
            ->paginate($page, false, ['query' => request()->param()]);

        $type_list = $this->get_type_log();
        $type_one = $type_list['type_one'];
        $list = $data->toArray();
        foreach ($list['data'] as &$v) {
            $v['type_name'] = isset($type_one[$v['type']]) ? $type_one[$v['type']] : $v['type'];
        }
        $this->assign('page', $data->render());
        $this->assign('list', $list['data']);
        $this->assign('type_list', $type_list['type_list']);
        $this->assign("data", session("consumption_records"));
        return $this->fetch();
    }
}
