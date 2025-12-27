<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/18
 * Time: 23:06
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class WithdrawalsManageController extends AdminBaseController {

	public function index() {

		$where = [];

		if (!input('request.page')) {
			session('withdrawals_index', null);
		}
		if (input('request.mobile') || input('request.id') || input('request.page') || input('request.name') || input('request.pay') || input('request.type') || input('request.status') >= '0' || input('request.start_time') || input('request.end_time')) {
			session('withdrawals_index', input('request.'));
		}
		if (session('withdrawals_index.mobile')) {
			$where['u.mobile'] = session('withdrawals_index.mobile');
		}
		if (session('withdrawals_index.status') >='0' && session('withdrawals_index.status') !='-1') {
			$where['r.status'] = session('withdrawals_index.status');
		}else{
			session('withdrawals_index.status',-1);
		}
		if (session('withdrawals_index.id')) {
			$where['r.user_id'] = session('withdrawals_index.id');
		}
		if (session('withdrawals_index.name')) {
			$where['u.user_nickname'] = session('withdrawals_index.name');
		}
		if (session('withdrawals_index.pay')) {
			$where['r.gathering_number'] = session('withdrawals_index.pay');
		}
		if (session('withdrawals_index.type') && session('withdrawals_index.type') !='-1') {
			$where['r.type'] = session('withdrawals_index.type');
		}else{
			session('withdrawals_index.type',-1);
		}
		if (session('withdrawals_index.end_time') && session('withdrawals_index.start_time')) {
			$where['r.create_time'] = ['between', [strtotime(session('withdrawals_index.start_time')), strtotime(session('withdrawals_index.end_time'))]];
		}
        $money = db('user_cash_record')
            ->alias('r')
            ->join("user u", "u.id=r.user_id")
            ->join("user_cash_account c", "c.id=r.paysid")
            ->where($where)->sum("r.money");
        $income = db('user_cash_record')
            ->alias('r')
            ->join("user u", "u.id=r.user_id")
            ->join("user_cash_account c", "c.id=r.paysid")
            ->where($where)->sum("r.income");

		$list = db('user_cash_record')
			->alias('r')
			->join("user u", "u.id=r.user_id")
            ->join("user_cash_account c", "c.id=r.paysid")
			->field('u.user_nickname,u.mobile,r.*,c.pay,c.wx,c.bank_card')
			->where($where)
			->order("r.create_time DESC")
			->paginate(20, false, ['query' => request()->param()]);
		//echo db() -> getLastSql();exit;
		//	var_dump($list);exit;
		$data = $list->toArray();
        foreach ($data['data'] as $k=>$v){
			if (IS_MOBILE == 0) {
				$data['data'][$k]['mobile'] = substr($v['mobile'], 0, 3).'****'.substr($v['mobile'], 7,4);
			}elseif(session('ADMIN_GROUPS_ID')==6){
                $data['data'][$k]['mobile'] = substr( $v['mobile'], 0, 5).'****'.substr( $v['mobile'], 9);
            }
			if (IS_MOBILE == 0){
				$data['data'][$k]['gathering_number'] = mb_substr($v['gathering_number'], 0, 3).'****'.mb_substr($v['gathering_number'], mb_strlen($v['gathering_number']) - 4,4);
			}
        }
		$page = $list->render();
		//dump($data);exit;
		$config = load_cache('config');
		$this->assign('request', session('withdrawals_index'));
		$this->assign('data', $data['data']);
		$this->assign('page', $page);
        $this->assign('money', $money);
        $this->assign('income', $income);
        $this->assign('alipay_fund_transfer_status', $config['alipay_fund_transfer_status']);
		return $this->fetch();
	}

	//通过审核
	public function adopt_cash() {

		$id = input('param.id');

         db('user_cash_record')->where("id=$id")->update(array('status' => 1,'updatetime'=>time()));
		$this->success(lang('Operation_successful'));
	}

	//拒绝审核
	public function refuse_cash() {

		$id = input('param.id');

		$record = db('user_cash_record')->where('id', '=', $id)->find();
		//返还提现金额
		db('user')->where('id', '=', $record['user_id'])->setInc('income', $record['income']);
		//修改提现状态
		db('user_cash_record')->where('id', '=', $id)->setField('status', 2);
		save_income_log($record['user_id'],$record['income'], 1,6);
		$this->success(lang('Operation_successful'));
	}
	public function refuse_cash_all()
	{
		$request = request()->param();
		if (empty($request['id'])) {
			return $this->redirect('/admin/public/index.php/admin/withdrawals_manage/index');
		} else {
			$id = $request['id'];
			$type = $request['type'];
			if ($type == 1) {
				foreach ($id as $key => $val) {
					$user = Db::name("user_cash_record")->where("id=$val and status=0")->update(array("status" => 1,'updatetime'=>time()));
				}
				if ($user) {
					$this->success(lang('Operation_successful'));
				} else {
					$this->success(lang('operation_failed'));
				}
			} else if ($type == 2) {
				foreach ($id as $key => $val) {
					$record = db('user_cash_record')->where("id=$val and status=0")->find();
					if($record){
						//返还提现金额
						db('user')->where('id', '=', $record['user_id'])->setInc('income', $record['income']);
						$user = Db::name("user_cash_record")->where("id=$val")->update(array("status" => 2));
					}else{
						$this->success(lang('operation_failed'));
					}
				}
				if ($user) {
					$this->success(lang('Operation_successful'));
				} else {
					$this->success(lang('operation_failed'));
				}
			}
		}
	}
	//删除
	public function del() {

		$id = input('param.id');

		db('user_cash_record')->where('id', '=', $id)->delete();
		$this->success(lang('Operation_successful'));
	}
	/* 用户绑定提现账号列表 */
	public function user_binding(){
		$where = [];
		if (!input('request.page')) {
			session('withdrawals_user_binding', null);
		}
		if (input('request.id') || input('request.mobile')) {
			session('withdrawals_user_binding', input('request.'));
		}
		if (session('withdrawals_user_binding.mobile')) {
			$where['u.mobile'] = session('withdrawals_user_binding.mobile');
		}
		if (session('withdrawals_user_binding.name')) {
			$where['u.user_nickname'] = session('withdrawals_user_binding.name');
		}
		if (session('withdrawals_user_binding.id')) {
			$where['r.uid'] = session('withdrawals_user_binding.id');
		}

		$list = db('user_cash_account')
			->alias('r')
			->join("user u", "u.id=r.uid")
			->field('u.user_nickname,u.mobile,r.*')
			->where($where)
			->order("r.addtime DESC")
			->paginate(20, false, ['query' => request()->param()]);

		$data = $list->toArray();
		$page = $list->render();
		foreach ($data['data'] as &$v) {
			$v['bank_card_name'] ='';
			if($v['bank_card_id'] > 0){

				$list = db('cash_card_name')->where("id=".$v['bank_card_id'])->find();

				$v['bank_card_name'] =$list ? $list['name']: '';
			}
		}


		$this->assign('request', session('withdrawals_user_binding'));
		$this->assign('data', $data['data']);
		$this->assign('page', $page);
		return $this->fetch();

	}
	/* 编辑绑定的账号 */
	public function binding_upd(){
		$id = input('param.id');
		$user = db('user_cash_account')
			->alias('r')
			->join("user u", "u.id=r.uid")
			->field('u.user_nickname,r.*')
			->where("r.id=".$id)
			->find();

		$list = db('cash_card_name')->where("status=1")->select();

		$this->assign('list', $list);
		$this->assign('user', $user);
		return $this->fetch();
	}
	/* 提交修改用户绑定的账号 */
	public function user_binding_post(){
		$param = $this->request->param();
        $data= $param['post'];

        $id= $param['id'];
        if(empty($data['name'])){
             $this->error(lang('Please_enter_account_name'));
        }
        if(empty($id)){
             $this->error(lang('user_does_not_exist'));
        }
         if(empty($data['bank_card'])){
             $this->error(lang('Enter_withdrawal_account_number'));
        }

      	$result = db('user_cash_account')->where("id=".$id)->update($data);

        if($result){
            $this->success(lang('Operation_successful'));
        }else{
            $this->error(lang('operation_failed'));
        }
	}
	//*导出*/
	public function export() {

		$where = [];
		if (isset($_REQUEST['mobile']) && $_REQUEST['mobile'] != '') {
			$where['u.mobile'] = $_REQUEST['mobile'];
		}

		if (isset($_REQUEST['status']) && $_REQUEST['status'] != '' && $_REQUEST['status'] != '-1') {
			$where['r.status'] = $_REQUEST['status'];
		} else {

			$_REQUEST['r.status'] = 0;
		}

		if (isset($_REQUEST['id']) && $_REQUEST['id']) {
			$where['r.user_id'] = $_REQUEST['id'];
		}
		if (isset($_REQUEST['name']) && $_REQUEST['name']) {
			$where['u.user_nickname'] = $_REQUEST['name'];
		}
		if (isset($_REQUEST['pay']) && $_REQUEST['pay']) {
			$where['r.gathering_number'] = $_REQUEST['pay'];
		}

		if ($_REQUEST['end_time'] && $_REQUEST['start_time']) {
			$where['r.create_time'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
		}

		$list = db('user_cash_record')
			->alias('r')
			->join("user u", "u.id=r.user_id")
			->field('u.user_nickname,u.mobile,r.*')
			->where($where)
			->order("r.create_time DESC")
			->select();

		if ($list != null) {

			$statuses = array('0' => lang('UNREVIEWED'), "1" => lang('AUDIT_BY'), "2" => lang('Refuse_to_withdraw'));
			foreach ($list as $k => $v) {

				$money = $v['money'];


				$dataResult[$k]['user_id'] = $v['user_id'] ? $v['user_id'] : lang('No_data');
				$dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : lang('No_data');
				$dataResult[$k]['mobile'] = $v['mobile'] && IS_MOBILE == 1 ? $v['mobile'] : lang('No_information');
				$dataResult[$k]['income'] = $v['income'] ? $v['income'] : '0';
				$dataResult[$k]['money'] = $money ? $money : '0';
				$dataResult[$k]['gathering_name'] = $v['gathering_name'] ? $v['gathering_name'] : lang('No_information');
				if (IS_MOBILE == 0 && $v['gathering_number']){
					$v['gathering_number'] = mb_substr($v['gathering_number'], 0, 3).'****'.mb_substr($v['gathering_number'], mb_strlen($v['gathering_number']) - 4,4);
				}
				$dataResult[$k]['gathering_number'] = $v['gathering_number'] ? $v['gathering_number'] : lang('No_information');
				$dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : lang('No_information');
				$dataResult[$k]['status'] = $statuses[$v['status']] ? $statuses[$v['status']] : lang('No_information');

			}

			$str = lang('USER_ID').",".lang('USER_NAME').",".lang('ADMIN_PHONE_NUMBER').",".lang('Withdrawal_quantity').",".lang('ADMIN_WITHDRAW_MONEY').",".lang('Name_of_withdrawal').",".lang('Withdrawal_payment_account_number').",".lang('SUBMIT_TIME').",".lang('STATUS');
			$title = lang('Member_withdrawal_list');

			$this->excelData($dataResult, $str, $title);
			exit();
		} else {
			$this->error(lang('No_data'));
		}

	}

	/*
	 * 打款*/
	public function fund_transfer(){
		$result = array('code'=>0,'msg'=>'');
		$id = intval(input('id'));
		$remark = trim(input('remark'));//备注
		if(!$remark){
			$remark = lang('Income_withdrawal');
		}
		$pay = db('pay_menu')->where('status = 1 and pay_name = "支付宝"')->find();
		if($pay){
			$pay_info['app_id'] = $pay['app_id'];
			$pay_info['private_key'] = $pay['private_key'];
			$pay_info['public_key'] = $pay['public_key'];
			$pay_info['pay_name'] = $pay['merchant_id'];
		}else{
			$result['code'] = 0;
			$result['msg'] = lang('Alipay_information_is_not_configured');
			echo json_encode($result);exit;
		}
		//dump($pay_info);die();
		//提现记录
		$user_cash_record = db('user_cash_record')->where("id=$id")->find();
		if(!$user_cash_record){
			$result['code'] = 0;
			$result['msg'] = lang('Withdrawal_information_acquisition_failed');
			echo json_encode($result);exit;
		}
		$name = $user_cash_record['gathering_name'];//收款账号
		$identity = $user_cash_record['gathering_number'];//收款名
		$money = number_format($user_cash_record['money'],2);//金额
		$config = load_cache('config');
		//商户订单号
		$time_order = explode (" ", microtime () );
		$time_order = $time_order [1] . ($time_order [0] * 1000);
		$time2 = explode ( ".", $time_order );
		$time_order = $time2 [0];
		$out_biz_no = $time_order.$user_cash_record['user_id'].rand(1000,9999);
		//标题
		$title = $config['system_name'].lang('Income_withdrawal');

		require_once DOCUMENT_ROOT."/system/pay_class/alipay_fund_transfer_menu.php";
		$o = new \alipay_fund_transfer();
		$res = $o->old_pay_transfer($pay_info,$out_biz_no,$money,$title,$identity,$name,$remark);
		if($res['code']==10000){
			$data = [
				'transfer_status'=>1,
				'out_biz_no'=>$out_biz_no,
				'order_id'=>$res['order_id'],
				//'pay_fund_order_id'=>$res['pay_fund_order_id'],
			];
			db('user_cash_record')->where("id=$id")->update($data);
			$result['code'] = 1;
			$result['msg'] = lang('Payment_succeeded1');
			echo json_encode($result);exit;
		}else{
			$result['code'] = 0;
			$result['msg'] = $res['sub_msg'];
			echo json_encode($result);exit;
		}
	}

	public function fund_transfer_status(){
		$result = array('code'=>0,'msg'=>'');
		$id = intval(input('id'));
		$remark = trim(input('remark'));//备注
		if(!$remark){
			$remark = lang('Income_withdrawal');
		}
		//dump($pay_info);die();
		//提现记录
		$user_cash_record = db('user_cash_record')->where("id=$id")->find();
		if(!$user_cash_record){
			$result['code'] = 0;
			$result['msg'] = lang('Withdrawal_information_acquisition_failed');
			echo json_encode($result);exit;
		}

		$data = [
			'transfer_status'=>1,
			//'pay_fund_order_id'=>$res['pay_fund_order_id'],
		];
		$res = db('user_cash_record')->where("id=$id")->update($data);
		return $res?1:0;
	}

}
?>
