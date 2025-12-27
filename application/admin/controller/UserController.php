<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Config;
/**
 * Class UserController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '管理组',
 *     'action' => 'default',
 *     'parent' => 'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   => '',
 *     'remark' => '管理组'
 * )
 */
class UserController extends AdminBaseController {

	/**
	 * 管理员列表
	 * @adminMenu(
	 *     'name'   => '管理员',
	 *     'parent' => 'default',
	 *     'display'=> true,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员管理',
	 *     'param'  => ''
	 * )
	 */
	public function index() {
		$where = ["user_type" => 1];
		/**搜索条件**/
		$user_login = $this->request->param('user_login');
		$user_email = trim($this->request->param('user_email'));

		if ($user_login) {
			$where['user_login'] = ['like', "%$user_login%"];
		}

		if ($user_email) {
			$where['user_email'] = ['like', "%$user_email%"];
		}
		$users = Db::name('user')
			->where($where)
			->order("id DESC")
			->paginate(10, false, ['query' => request()->param()]);
		// 获取分页显示
		$page = $users->render();

		$rolesSrc = Db::name('role')->select();
		$roles = [];
		foreach ($rolesSrc as $r) {
			$roleId = $r['id'];
			$roles["$roleId"] = $r;
		}
		$this->assign("page", $page);
		$this->assign("roles", $roles);
		$this->assign("users", $users);
		return $this->fetch();
	}

	/**
	 * 管理员添加
	 * @adminMenu(
	 *     'name'   => '管理员添加',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员添加',
	 *     'param'  => ''
	 * )
	 */
	public function add() {
		$roles = Db::name('role')->where(['status' => 1])->order("id DESC")->select();
		$this->assign("roles", $roles);
		return $this->fetch();
	}

	/**
	 * 管理员添加提交
	 * @adminMenu(
	 *     'name'   => '管理员添加提交',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员添加提交',
	 *     'param'  => ''
	 * )
	 */
	public function addPost() {
		if ($this->request->isPost()) {
			if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
				$role_ids = $_POST['role_id'];
				unset($_POST['role_id']);
				$result = $this->validate($this->request->param(), 'UserValidate');

				if ($result !== true) {
					$this->error($result);
				} else {
					$_POST['user_pass'] = cmf_password($_POST['user_pass']);
                    $_POST['user_type'] = 1;
					$result = DB::name('user')->insertGetId($_POST);
					if ($result !== false) {
						//$role_user_model=M("RoleUser");
						foreach ($role_ids as $role_id) {
							if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
								$this->error(lang('Do_not_create_super_management'));
							}
							Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
						}
						$this->success(lang('ADD_SUCCESS'), url("user/index"));
					} else {
						$this->error(lang('ADD_FAILED'));
					}
				}
			} else {
				$this->error(lang('Please_specify_role_for_user'));
			}

		}
	}

	/**
	 * 管理员编辑
	 * @adminMenu(
	 *     'name'   => '管理员编辑',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员编辑',
	 *     'param'  => ''
	 * )
	 */
	public function edit() {
		$id = $this->request->param('id', 0, 'intval');
		$roles = DB::name('role')->where(['status' => 1])->order("id DESC")->select();
		$this->assign("roles", $roles);
		$role_ids = DB::name('RoleUser')->where(["user_id" => $id])->column("role_id");
		$this->assign("role_ids", $role_ids);

		$user = DB::name('user')->where(["id" => $id])->find();
		$this->assign($user);
		return $this->fetch();
	}

	/**
	 * 管理员编辑提交
	 * @adminMenu(
	 *     'name'   => '管理员编辑提交',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员编辑提交',
	 *     'param'  => ''
	 * )
	 */
	public function editPost() {
		if ($this->request->isPost()) {
			if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
				if (empty($_POST['user_pass'])) {
					unset($_POST['user_pass']);
				} else {
					$_POST['user_pass'] = cmf_password($_POST['user_pass']);
				}
				$role_ids = $this->request->param('role_id/a');
				unset($_POST['role_id']);
				$result = $this->validate($this->request->param(), 'UserValidate.edit');

				if ($result !== true) {
					// 验证失败 输出错误信息
					$this->error($result);
				} else {
					$result = DB::name('user')->update($_POST);
					if ($result !== false) {
						$uid = $this->request->param('id', 0, 'intval');
						DB::name("RoleUser")->where(["user_id" => $uid])->delete();
						foreach ($role_ids as $role_id) {
							if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
								$this->error(lang('Do_not_create_super_management'));
							}
							DB::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
						}
						$this->success(lang('EDIT_SUCCESS'));
					} else {
						$this->error(lang('EDIT_FAILED'));
					}
				}
			} else {
				$this->error(lang('Please_specify_role_for_user'));
			}

		}
	}

	/**
	 * 管理员个人信息修改
	 * @adminMenu(
	 *     'name'   => '个人信息',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> true,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员个人信息修改',
	 *     'param'  => ''
	 * )
	 */
	public function userinfo() {
		$id = cmf_get_current_admin_id();
		$user = Db::name('user')->where(["id" => $id])->find();
		$this->assign($user);
		return $this->fetch();
	}

	/**
	 * 管理员个人信息修改提交
	 * @adminMenu(
	 *     'name'   => '管理员个人信息修改提交',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员个人信息修改提交',
	 *     'param'  => ''
	 * )
	 */
	public function userInfoPost() {
		if ($this->request->isPost()) {

			$data = $this->request->post();
			$data['birthday'] = strtotime($data['birthday']);
			$data['id'] = cmf_get_current_admin_id();
			$create_result = Db::name('user')->update($data);
			if ($create_result !== false) {
				$this->success(lang('EDIT_SUCCESS'));
			} else {
				$this->error(lang('EDIT_FAILED'));
			}
		}
	}

	/**
	 * 管理员删除
	 * @adminMenu(
	 *     'name'   => '管理员删除',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '管理员删除',
	 *     'param'  => ''
	 * )
	 */
	public function delete() {
		$id = $this->request->param('id', 0, 'intval');
		if ($id == 1) {
			$this->error(lang('Top_Administrator_cannot_delete'));
		}

		if (Db::name('user')->delete($id) !== false) {
			Db::name("RoleUser")->where(["user_id" => $id])->delete();
			$this->success(lang('DELETE_SUCCESS'));
		} else {
			$this->error(lang('DELETE_FAILED'));
		}
	}

	/**
	 * 停用管理员
	 * @adminMenu(
	 *     'name'   => '停用管理员',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '停用管理员',
	 *     'param'  => ''
	 * )
	 */
	public function ban() {
		$id = $this->request->param('id', 0, 'intval');
		if (!empty($id)) {
			$result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '0');
			if ($result !== false) {
				$this->success(lang('Administrator_deactivated_successfully'), url("user/index"));
			} else {
				$this->error(lang('Administrator_deactivation_failed'));
			}
		} else {
			$this->error(lang('Data_transfer_in_failed'));
		}
	}

	/**
	 * 启用管理员
	 * @adminMenu(
	 *     'name'   => '启用管理员',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '启用管理员',
	 *     'param'  => ''
	 * )
	 */
	public function cancelBan() {
		$id = $this->request->param('id', 0, 'intval');
		if (!empty($id)) {
			$result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '1');
			if ($result !== false) {
				$this->success(lang('Enabled_successfully'), url("user/index"));
			} else {
				$this->error(lang('Enable_failed'));
			}
		} else {
			$this->error(lang('Data_transfer_in_failed'));
		}
	}

	//陪聊
    public function talker(){

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('name') and !$this->request->param('id') and !$this->request->param('reference') ) {
            session("level_index", null);
            $data['reference'] = '0';
            session("level_index", $data);

        } else if (empty($p)) {
            $data['name'] = $this->request->param('name');
            $data['id'] = $this->request->param('id');
            $data['reference'] = $this->request->param('reference');
            session("level_index", $data);
        }

        $level_name = session("level_index.name");
        $id = session("level_index.id");
        $reference = session("level_index.reference");
        //$type = session("level_index.type");
        $where = '';
        $where = "id > 0 ";
        $where .=$id ? " and id = ".intval($id) : '';
        $where .= $level_name ? " and user_nickname like '%".trim($level_name)."%'" :'';
        if($reference==2){
            $where .= " and reference = 0";
        }else{
            $where .= $reference ? " and reference =".$reference:'';
        }

        $page = 10;
        $data = Db::name('user')
            ->where(['is_talker'=>1])
            ->where($where)
            ->order('create_time desc')
            ->paginate($page, false, ['query' => request()->param()]);
        $list = $data->toArray();
        $list_arr = [];
        foreach($list['data'] as $v){
            $v['count_user'] = db('video_charging_record')
                ->where('to_user_id = '.$v['id'])
                ->group('user_id')
                ->count();
            $talker = db('auth_talker')
                ->alias('t')
                ->join('auth_talker_label a','a.id=t.type')
                ->where('t.uid = '.$v['id'])
                ->field('t.*,a.label_name')
                ->find();
            if($talker){
                $v['type_id'] = $talker['type'];
                $v['label_name'] = $talker['label_name'];
            }else{
                $v['type_id'] = '';
                $v['label_name'] = '';
            }

            if (IS_MOBILE == 0) {
                $v['mobile'] = substr( $v['mobile'], 0, 3).'****'.substr( $v['mobile'], 7,4);
            }
            $list_arr[] = $v;

        }
        $label_type = db('auth_talker_label')->select();
        //dump($label_type);die();
        //付费用户数量
        $config = load_cache('config');
        //$data = [];
        $this->assign('page', $data->render());
        $this->assign('list', $list_arr);
        $this->assign("data", session("level_index"));

        $label = Db::name('skills_recommend_label')->select();
        $game = Db::name('play_game')->select();
        $this->assign("label", $label);
        $this->assign("game", $game);
        $this->assign("config", $config);
        $this->assign("label_type", $label_type);

        return $this->fetch();

    }

    /*
     * 修改认证标签*/
    public function talkerSetLabel(){
        $array = ['code'=>1,'msg'=>lang('Modified_successfully')];
        $id = input('id');
        $type = input('type');
        $res = db('auth_talker')->where('uid = '.$id)->update(['type'=>$type]);
        if($res){
            echo json_encode($array);exit;
        }else{
            $array['code'] = 0;
            $array['msg'] = lang('Modification_failed');
            echo json_encode($array);exit;
        }
    }
    //音遇
    public function audio(){

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('name') and !$this->request->param('id') and !$this->request->param('reference') ) {
            session("level_index", null);
            $data['reference'] = '0';
            session("level_index", $data);

        } else if (empty($p)) {
            $data['name'] = $this->request->param('name');
            $data['id'] = $this->request->param('id');
            $data['reference'] = $this->request->param('reference');
            session("level_index", $data);
        }

        $level_name = session("level_index.name");
        $id = session("level_index.id");
        $reference = session("level_index.reference");
        //$type = session("level_index.type");
        $where = '';
        $where = "id > 0 ";
        $where .=$id ? " and id=".intval($id) : '';
        $where .= $level_name ? " and user_nickname like '%".trim($level_name)."%'" :'';
        if($reference==2){
            $where .= " and reference = 0";
        }else{
            $where .= $reference ? " and reference =".$reference:'';
        }

        $page = 10;
        $data = Db::name('user')
            ->where(['is_talker'=>1])
            ->where($where)
            //->where('audio_file != "" and audio_time != 0')
            ->order('create_time desc')
            ->paginate($page, false, ['query' => request()->param()]);

        /*$data = Db::name('skills_info')
            ->alias('s')
            ->join('user u','u.id=s.uid')
            ->join('play_game g','g.id=s.game_id')
            ->where($where)
            ->field('s.*,u.user_nickname,g.name')
            ->order('create_time desc')
            ->paginate($page, false, ['query' => request()->param()]);*/
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

    public function audio_edit(){
	    $id = input('id');
        $info = Db::name('user')
            ->where(['is_talker'=>1,'id'=>$id])
            ->find();
        $this->assign('data',$info);
        return $this->fetch();

    }

    public function audio_edit_post(){
        $id = input('id');
        $audio_file = input('param.audio_file');
        $audio_time = intval(input('param.audio_time'));
        $info = Db::name('user')
            ->where(['is_talker'=>1,'id'=>$id])
            ->update(['audio_time'=>$audio_time,'audio_file'=>$audio_file]);
        $this->success(lang('Operation_successful'));

    }

    public function audio_del(){
        $id = input('id');
        $res = Db::name('user')
            ->where(['is_talker'=>1,'id'=>$id])
            ->update(['audio_time'=>0,'audio_file'=>0]);
        return 1;
    }

    public function start_recommend(){
        $param = $this->request->param();
        $type = $param['type'];
        $id = $param['id'];
        if($type==1){
            $recommend_label = $param['recommend_label'];
            $data = [
                'recommend_label'=>$recommend_label,
                //'recommend_time'=>NOW_TIME,
                'reference'=>1,
            ];
            $res = Db::name('user')->where(['id'=>$id])->update($data);

            /*$user_reference = Db::name('user_reference')->where(['uid'=>$id])->find();
            if($user_reference){
                Db::name('user_reference')->where(['uid'=>$id])->update(['recommend_label'=>$recommend_label]);
            }else{
                $recommend = [
                    'recommend_label'=>$recommend_label
                ];
                Db::name('user_reference')->insert($recommend);
            }*/
        }else if($type==2){
            $data = [
                'recommend_label'=>0,
                //'recommend_time'=>0,
                'reference'=>0,
            ];
            $res = Db::name('user')->where(['id'=>$id])->update($data);
        }

        return $res?1:0;
    }




	/*
	* 清除测试数据
	*/
	public function clear_data(){
		$prefix=Config::get('database.prefix');
		if(!APP_DEBUG){
			$this->error('未开启错误记录！');exit;
		}
		Db::name('user')->where("id !=1")->delete();
		Db::name("agent")-> execute("truncate table  ".$prefix."agent");

		Db::name('agent_order_log')->execute("truncate table  ".$prefix."agent_order_log");
		Db::name('agent_statistical')->execute("truncate table  ".$prefix."agent_statistical");
		Db::name('agent_withdrawal')->execute("truncate table  ".$prefix."agent_withdrawal");
		Db::name('app_analyze')->execute("truncate table  ".$prefix."app_analyze");
		Db::name('asset')->execute("truncate table  ".$prefix."asset");
		Db::name('auth_form_record')->execute("truncate table  ".$prefix."auth_form_record");
		Db::name('auto_talking_skill')->execute("truncate table  ".$prefix."auto_talking_skill");
		Db::name('bzone')->execute("truncate table  ".$prefix."bzone");
		Db::name('bzone_images')->execute("truncate table  ".$prefix."bzone_images");
		Db::name('bzone_like')->execute("truncate table  ".$prefix."bzone_like");
		Db::name('bzone_reply')->execute("truncate table  ".$prefix."bzone_reply");
		Db::name('comment')->execute("truncate table  ".$prefix."comment");
		Db::name('device_info')->execute("truncate table  ".$prefix."device_info");
		Db::name('equipment_closures')->execute("truncate table  ".$prefix."equipment_closures");
		Db::name('feedback')->execute("truncate table  ".$prefix."feedback");
		Db::name('guardian_user')->execute("truncate table  ".$prefix."guardian_user");
		Db::name('guardian_user_log')->execute("truncate table  ".$prefix."guardian_user_log");
		Db::name('guild')->execute("truncate table  ".$prefix."guild");
		Db::name('guild_join')->execute("truncate table  ".$prefix."guild_join");
		Db::name('guild_log')->execute("truncate table  ".$prefix."guild_log");
		Db::name('guild_withdrawal_log')->execute("truncate table  ".$prefix."guild_withdrawal_log");
		Db::name('invited_record_log')->execute("truncate table  ".$prefix."invited_record_log");
		Db::name('invite_cash_record')->execute("truncate table  ".$prefix."invite_cash_record");
		Db::name('invite_code')->execute("truncate table  ".$prefix."invite_code");
		Db::name('invite_profit_record')->execute("truncate table  ".$prefix."invite_profit_record");
		Db::name('invite_recharge_deduction_record')->execute("truncate table  ".$prefix."invite_recharge_deduction_record");
		Db::name('invite_record')->execute("truncate table  ".$prefix."invite_record");
		Db::name('invite_reg_deduction_record')->execute("truncate table  ".$prefix."invite_reg_deduction_record");
		Db::name('ip_reg_log')->execute("truncate table  ".$prefix."ip_reg_log");
		Db::name('join_in')->execute("truncate table  ".$prefix."join_in");
		Db::name('live')->execute("truncate table  ".$prefix."live");
		Db::name('live_gift')->execute("truncate table  ".$prefix."live_gift");
		Db::name('live_pk')->execute("truncate table  ".$prefix."live_pk");
		Db::name('mb_user')->execute("truncate table  ".$prefix."mb_user");
		Db::name('monitor')->execute("truncate table  ".$prefix."monitor");
		Db::name('music')->execute("truncate table  ".$prefix."music");
		Db::name('music_log')->execute("truncate table  ".$prefix."music_log");
		Db::name('online_record')->execute("truncate table  ".$prefix."online_record");
		Db::name('pay_notify_log')->execute("truncate table  ".$prefix."pay_notify_log");
		Db::name('recharge_log')->execute("truncate table  ".$prefix."recharge_log");
		Db::name('reward_coin_log')->execute("truncate table  ".$prefix."reward_coin_log");
		Db::name('role_user')->execute("truncate table  ".$prefix."role_user");
		Db::name('search_log')->execute("truncate table  ".$prefix."search_log");
		Db::name('third_party_user')->execute("truncate table  ".$prefix."third_party_user");
		Db::name('user_action_log')->execute("truncate table  ".$prefix."user_action_log");
		Db::name('user_alipay')->execute("truncate table  ".$prefix."user_alipay");
		Db::name('user_attention')->execute("truncate table  ".$prefix."user_attention");
		Db::name('user_auth_video')->execute("truncate table  ".$prefix."user_auth_video");
		Db::name('user_bag')->execute("truncate table  ".$prefix."user_bag");
		Db::name('user_black')->execute("truncate table  ".$prefix."user_black");
		Db::name('user_cash_account')->execute("truncate table  ".$prefix."user_cash_account");
		Db::name('user_cash_record')->execute("truncate table  ".$prefix."user_cash_record");
		Db::name('user_charge_log')->execute("truncate table  ".$prefix."user_charge_log");
		Db::name('user_consume_log')->execute("truncate table  ".$prefix."user_consume_log");
		Db::name('user_eggs_log')->execute("truncate table  ".$prefix."user_eggs_log");
		Db::name('user_evaluate_record')->execute("truncate table  ".$prefix."user_evaluate_record");
		Db::name('user_fabulous_record')->execute("truncate table  ".$prefix."user_fabulous_record");
		Db::name('user_favorite')->execute("truncate table  ".$prefix."user_favorite");
		Db::name('user_gift_log')->execute("truncate table  ".$prefix."user_gift_log");
		Db::name('user_identity')->execute("truncate table  ".$prefix."user_identity");
		Db::name('user_img')->execute("truncate table  ".$prefix."user_img");
		Db::name('user_message_log')->execute("truncate table  ".$prefix."user_message_log");
		Db::name('user_photo_buy')->execute("truncate table  ".$prefix."user_photo_buy");
		Db::name('user_pictures')->execute("truncate table  ".$prefix."user_pictures");
		Db::name('user_private_chat_log')->execute("truncate table  ".$prefix."user_private_chat_log");
		Db::name('user_reference')->execute("truncate table  ".$prefix."user_reference");
		Db::name('user_report')->execute("truncate table  ".$prefix."user_report");
		Db::name('user_score_log')->execute("truncate table  ".$prefix."user_score_log");
		Db::name('user_teacher')->execute("truncate table  ".$prefix."user_teacher");
		Db::name('user_token')->execute("truncate table  ".$prefix."user_token");
		Db::name('user_turntable')->execute("truncate table  ".$prefix."user_turntable");
		Db::name('user_video')->execute("truncate table  ".$prefix."user_video");
		Db::name('user_video_attention')->execute("truncate table  ".$prefix."user_video_attention");
		Db::name('user_video_buy')->execute("truncate table  ".$prefix."user_video_buy");
		Db::name('verification_code')->execute("truncate table  ".$prefix."verification_code");
		Db::name('video_call_record')->execute("truncate table  ".$prefix."video_call_record");
		Db::name('video_call_record_log')->execute("truncate table  ".$prefix."video_call_record_log");
		Db::name('video_call_subscribe')->execute("truncate table  ".$prefix."video_call_subscribe");
		Db::name('video_charging_record')->execute("truncate table  ".$prefix."video_charging_record");
		Db::name('video_live_list')->execute("truncate table  ".$prefix."video_live_list");

		Db::name('agent_information')->execute("truncate table  ".$prefix."agent_information");
		Db::name('magic_wand_log')->execute("truncate table  ".$prefix."magic_wand_log");
		Db::name('user_exchange_log')->execute("truncate table  ".$prefix."user_exchange_log");
		Db::name('user_luck_list')->execute("truncate table  ".$prefix."user_luck_list");
		Db::name('user_report_img')->execute("truncate table  ".$prefix."user_report_img");
		Db::name('voice_gift_reset')->execute("truncate table  ".$prefix."voice_gift_reset");


		Db::name('voice')->execute("truncate table  ".$prefix."voice");
		Db::name('voice_administrator')->execute("truncate table  ".$prefix."voice_administrator");
		Db::name('voice_even_wheat_log')->execute("truncate table  ".$prefix."voice_even_wheat_log");
		Db::name('voice_release')->execute("truncate table  ".$prefix."voice_release");

		Db::name('bubble_day_log')->execute("truncate table  ".$prefix."bubble_day_log");
		Db::name('playing_bubble_log')->execute("truncate table  ".$prefix."playing_bubble_log");
		Db::name('user_eggs_log')->execute("truncate table  ".$prefix."user_eggs_log");

		$this->success(lang("清除测试数据成功！"), url("index/index"));

	}

    public function receive_gift_log(){
	    $id = $this->request->param('id');
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('name') and !$this->request->param('uid') and !$this->request->param('game_id') ) {
            session("level_index", null);
            $data['game_id'] = '0';
            session("level_index", $data);

        } else if (empty($p)) {
            $data['name'] = $this->request->param('name');
            $data['uid'] = $this->request->param('uid');
            $data['game_id'] = $this->request->param('game_id');
            session("level_index", $data);
        }

        $name = session("level_index.name");
        $uid = session("level_index.uid");
        $game_id = session("level_index.game_id");
        //$type = session("level_index.type");
        $map = [];
        if($uid){
            $map['l.user_id'] = $uid;
        }
        if($name){
            $map['u.user_nickname'] = ['like','%'.$name.'%'];
        }
        if($game_id){
            $map['l.gift_id'] = $game_id;
        }
        //dump($map);
        $page = 10;
        if($id){
            $map['l.to_user_id'] = $id;
            $data = Db::name('user_gift_log')
                ->alias('l')
                ->join('gift g','g.id=l.gift_id')
                ->join('user u','u.id=l.user_id')
                ->where($map)
                ->field('l.*,u.user_nickname')
                ->order('create_time desc')
                ->paginate($page, false, ['query' => request()->param()]);
        }
        //$data = [];
        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign("data", session("level_index"));

        $label = Db::name('skills_recommend_label')->select();
        $game = Db::name('gift')->select();
        $this->assign("label", $label);
        $this->assign("game", $game);
        $this->assign("id", $id);

        return $this->fetch();
    }

    public function occupation(){
        $list = Db::name("user_occupation")->order('orderno')->select();
        $this->assign('list', $list);

        return $this->fetch();
    }

    public function occupation_add()
    {
        $id = input('param.id');
        if ($id) {
            $gift = Db::name("user_occupation")->where("id=$id")->find();
        } else {

            $gift['status'] = 1;
        }
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    public function addOccupationPost()
    {
        $param = $this->request->param();
        //  print_r($param);exit;
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("user_occupation")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_occupation")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('user/occupation'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del_occupation()
    {
        $param = request()->param();
        $result = Db::name("user_occupation")->where("id=" . $param['id'])->delete();

        return $result ? '1' : '0';
        exit;
    }

    //修改排序
    public function upd_occupation()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_occupation")->where("id=$k")->update(array('orderno' => $v));
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

    public function update_login_name(){
        $id = cmf_get_current_admin_id();
        $user = Db::name('user')->where(["id" => $id])->find();
        $this->assign($user);
        return $this->fetch();
    }

    public function update_login(){
        if ($this->request->isPost()) {

            $data = $this->request->param();

            if (empty($data['password'])) {
                $this->error(lang('PASSWORD_REQUIRED'));
            }

            $userId = cmf_get_current_admin_id();

            $admin = Db::name('user')->where(["id" => $userId])->find();

            $password    = $data['password'];
            $user_login  = $data['user_login'];

            if (cmf_compare_password($password, $admin['user_pass'])) {
                if ($user_login != $admin['user_login']) {

                    Db::name('user')->where('id', $userId)->update(['user_login' => $user_login]);
                    $this->success(lang('EDIT_SUCCESS'));

                } else {
                    $this->error(lang('login_account_same_original_account'));
                }

            } else {
                $this->error(lang('Password error'));
            }
        }
    }
    // 后台登录日志
    public function login_log(){

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('name') and !$this->request->param('type') and !$this->request->param('uid') and !$this->request->param('ip') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            $data['type'] = 0;
            session("login_log", null);
        } else if (empty($p)) {
            $data['name'] = $this->request->param('name');
            $data['uid'] = $this->request->param('uid');
            $data['ip'] = $this->request->param('ip');
            $data['type'] = intval($this->request->param('type'));
            $data['start_time'] = $this->request->param('start_time') ?$this->request->param('start_time') :'';
            $data['end_time'] = $this->request->param('end_time') ?$this->request->param('end_time') :'';
            session("login_log", $data);
        }

        $name = session("login_log.name");
        $id = intval(session("login_log.uid"));
        $ip = session("login_log.ip");
        $type = intval(session("login_log.type"));
        $start_time = session("login_log.start_time");
        $end_time = session("login_log.end_time");

        $where = "id > 0 ";
        $where .=$id ? " and uid=".intval($id) : '';
        $where .=$type ? " and type=".$type : '';
        $where .= $name ? " and user_login like '%".trim($name)."%'" :'';
        $where .= $ip ? " and ip like '%".trim($ip)."%'" :'';
        if ($start_time) {
            $where .= " and addtime >=".strtotime($start_time);
        }
        if ($end_time) {
            $where .= " and addtime <=".strtotime($end_time);
        }

        $page = 10;
        $data = Db::name('login_log')->where($where)->order('addtime desc') ->paginate($page, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign("data", session("login_log"));
        return $this->fetch();
    }
}
