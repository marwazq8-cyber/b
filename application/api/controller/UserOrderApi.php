<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-29
 * Time: 09:22
 */

namespace app\api\controller;

use app\common\Enum;
use think\Request;
use think\Db;
use think\helper\Time;
use app\api\model\PlaywithModel;
use app\api\model\UserModel;
use app\api\model\SkillsInfo;
use app\api\model\SkillsOrder;

class UserOrderApi extends Base
{
    public $PlaywithModel;
    public $UserModel;
    public $SkillsInfo;
    public $SkillsOrder;

    protected function _initialize()
    {
        parent::_initialize();

        header('Access-Control-Allow-Origin:*');
        $this->PlaywithModel = new PlaywithModel();
        $this->UserModel = new UserModel();
        $this->SkillsInfo = new SkillsInfo();
        $this->SkillsOrder = new SkillsOrder();
    }

    //普通用户订单
    public function get_order_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token);
        //$order_id = intval(input('order_id'));//订单ID
        $page = intval(input('page'));
        $where = 's.uid = ' . $uid;
        $res = $this->SkillsOrder->get_user_order_list($where, $page);
        $result['data'] = $res;
        return_json_encode($result);
    }

    //取消订单
    public function request_cancel_order()
    {
        $result = array('code' => 1, 'msg' => lang('Cancellation_succeeded'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        $refuse_reason = trim(input('param.refuse_reason'));//取消原因
        //$refuse_id = intval(input('param.refuse_id'));
        $order_id = intval(input('param.order_id'));//订单ID

        $order_info = $this->SkillsOrder->get_order_info(['id' => $order_id, 'uid' => $uid, 'status' => 1]);
        if (!$order_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Order_information_does_not_exist');
            return_json_encode($result);
        }
        //1待接单2已接单-待服务3服务中-待确认4完成服务-商户 5确认-待评价，6结束-评级完成 7已拒接 8取消 9退款 10同意 11不同意
        $data = [
            'status' => 8,
            'refuse_reason' => $refuse_reason,
            //'refuse_id'=>$refuse_id,
            'edit_time' => NOW_TIME,
        ];
        $where = 'id = ' . $order_id . ' and uid =' . $uid;
        $res = $this->SkillsOrder->up_order($where, $data);
        if (!$res) {
            $result['code'] = 0;
        }
        //返还金币
        $coin = $order_info['total_coin'];
        db('user')
            ->where('id=' . $order_info['uid'])
            ->inc('coin', $coin)
            ->update();
        //增加消费记录
        upd_user_coin_log($user_info['id'], $coin, $coin, 9, 1, 1, $user_info['last_login_ip'], $user_info['id']);
        // 修改记录
        db('user_consume_log')->where("type=7 and status=0 and table_id=" . $order_id)->update(['status' => 2]);

        player_order_msg($order_id, 8);
        //一对一订单消息
        $broadMsg = [];
        $broadMsg['type'] = Enum::ONE_ORDERS;
        $broadMsg['info']['order_id'] = $order_info['id'];
        $broadMsg['info']['status'] = 8;
        $broadMsg['info']['title'] = lang('cancellation_order');
        $broadMsg['info']['content'] = lang('Cancelled_accompanying_order');
        $broadMsg['info']['skills_id'] = $order_info['skills_id'];
        $broadMsg['info']['uid'] = $order_info['uid'];
        $broadMsg['info']['player_uid'] = $order_info['touid'];
        send_order_msg($uid, $order_info['touid'], $broadMsg);
        return_json_encode($result);

    }

    //确认订单
    public function reuqest_confirm_order()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        //1待接单2已接单-待服务3服务中-待确认4完成服务-商户 5确认-待评价，6结束-评价完成 7拒绝 8取消 9退款 10同意 11不同意
        $order_id = intval(input('param.order_id'));//订单ID
        $order_where = "id = $order_id and uid = $uid and (status = 4 or status = 11)";
        $order_info = $this->SkillsOrder->get_order_info($order_where);
        if (!$order_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Order_information_does_not_exist');
            return_json_encode($result);
        }

        $data = [
            'status' => 5,
            'edit_time' => NOW_TIME,
            //'skills_order_num'=>NOW_TIME,
        ];
        $where = 'id = ' . $order_id . ' and uid =' . $uid;
        $res = $this->SkillsOrder->up_order($where, $data);
        if ($res) {
            //增加服务数量
            Db::name('skills_order')->where($where)->setInc('skills_order_num');
            //给陪玩师增加收益 总收益
            //$total_coin = $order_info['total_coin'];
            //可提现收益
            $total_income = $order_info['total_income'];
            //$coin = $total_coin-$total_income;

            $user_earnings = db('user')
                ->where('id=' . $order_info['touid'])
                ->inc('income', $total_income)
                ->inc('income_total', $total_income)
                ->inc('income_player_total', $total_income)
                //->inc('coin', $coin)
                ->update();

            if ($user_earnings) {
                //提现比收益转换
                $config = load_cache('config');
                $integral = number_format(($total_income / $config['integral_withdrawal']), 2);
                $consume_id = upd_user_coin_log($order_info['touid'], $total_income, $integral, 6, 2, 1, $user_info['last_login_ip'], $user_info['id']);
                player_order_msg($order_id, 5);
                // 修改记录
                $user_consume_log = db('user_consume_log')->where("type=7 and status=0 and table_id=" . $order_id . " and to_user_id=" . $order_info['touid'])->find();
                if ($user_consume_log) {
                    db('user_consume_log')->where("id=" . $user_consume_log['id'])->update(['status' => 1]);
                    if ($user_consume_log['guild_earnings'] && $user_consume_log['guild_uid']) {
                        // 用户收益  -- 公户收益
                        $UserModel = new UserModel();
                        $to_user_info = db('user')->where("id=" . $order_info['touid'])->find();
                        $UserModel->add_user_earnings($user_consume_log['guild_uid'], $user_consume_log['guild_earnings'], $to_user_info, 14);
                        $guild_info = db('guild')->where('user_id = ' . $user_consume_log['guild_uid'])->find();
                        //工会收益记录
                        add_guild_income($order_info['touid'], 2, $order_info['id'], $order_info['total_coin'], $total_income, $consume_id, $user_consume_log['guild_earnings'], $guild_info['id'], $guild_info['guild_commission']);
                    }
                }

                //邀请收益记录
                request_invite_record($order_info['touid'], 3, $total_income, $consume_id);
            }
            //一对一订单消息
            $broadMsg = [];
            $broadMsg['type'] = Enum::ONE_ORDERS;
            $broadMsg['info']['order_id'] = $order_info['id'];
            $broadMsg['info']['status'] = 5;
            $broadMsg['info']['title'] = lang('End_order');
            $broadMsg['info']['content'] = lang('End_order_confirmed_by_user');
            $broadMsg['info']['skills_id'] = $order_info['skills_id'];
            $broadMsg['info']['uid'] = $order_info['uid'];
            $broadMsg['info']['player_uid'] = $order_info['touid'];
            send_order_msg($uid, $order_info['touid'], $broadMsg);
        } else {
            $result['code'] = 0;
        }
        return_json_encode($result);
    }

    //申请退款
    public function request_refund()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        //1待接单2已接单-待服务3服务中-待确认4完成服务-商户 5确认-待评价，6结束-评价完成 7拒绝 8取消 9退款 10同意 11不同意
        $order_id = intval(input('param.order_id'));//订单ID
        $refund_info = trim(input('param.refund_info'));//退款原因
        $where['id'] = $order_id;
        $where['uid'] = $uid;
        $where['status'] = ['in', [2, 3, 4]];
        $order_info = $this->SkillsOrder->get_order_info($where);
        if (!$order_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Order_information_error');
            return_json_encode($result);
        }
        if (!$refund_info) {
            $result['code'] = 0;
            $result['msg'] = lang('Refund_reason_cannot_be_blank');
            return_json_encode($result);
        }

        $data = [
            'status' => 9,
            'refuse_reason' => $refund_info,
            'edit_time' => NOW_TIME,
        ];
        //$where = 'id = '.$order_id.' and uid ='.$uid;
        $res = $this->SkillsOrder->up_order($where, $data);
        if ($res) {
            //退款记录
            $data_refund = [
                'uid' => $uid,
                'touid' => $order_info['touid'],
                'order_id' => $order_id,
                'status' => 1,
                'refund_info' => $refund_info,
                'addtime' => NOW_TIME,
            ];
            Db::name('skills_order_refund')->insertGetId($data_refund);
            player_order_msg($order_id, 9);
            //一对一订单消息
            $broadMsg = [];
            $broadMsg['type'] = Enum::ONE_ORDERS;
            $broadMsg['info']['order_id'] = $order_info['id'];
            $broadMsg['info']['status'] = 9;
            $broadMsg['info']['title'] = lang('Request_order_refund');
            $broadMsg['info']['content'] = $refund_info . lang('User_initiated_refund_request');
            $broadMsg['info']['skills_id'] = $order_info['skills_id'];
            $broadMsg['info']['uid'] = $order_info['uid'];
            $broadMsg['info']['player_uid'] = $order_info['touid'];
            send_order_msg($uid, $order_info['touid'], $broadMsg);
            $result['msg'] = lang('Successfully_applied_for_refund');
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Failed_to_apply_for_refund');
        }
        return_json_encode($result);
    }

    //评价标签
    public function get_comment_label()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        check_login_token($uid, $token, ['last_login_ip']);
        $result['data'] = Db::name('skills_comment_label')->order('orderno')->select();
        return_json_encode($result);
    }

    //评价
    public function request_add_comment()
    {
        $result = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $order_id = intval(input('order_id'));//订单号
        //$c_id = intval(input('c_id'));//标签ID
        $is_recommend = intval(input('is_recommend'));//是否推荐
        $content = trim(input('content'));//评价内容
        $label_name = trim(input('label_name'));//评价标签名称 逗号拼接
        check_login_token($uid, $token, ['last_login_ip']);
        //1待接单2已接单-待服务3服务中-待确认4完成服务-商户 5确认-待评价，6结束-评价完成 7拒绝 8取消 9退款 10同意 11不同意
        $where = 's.id = ' . $order_id . ' and s.uid = ' . $uid;
        $order_info = $this->SkillsOrder->get_info($where);
        if (!$order_info) {
            $result['msg'] = lang('Order_information_acquisition_failed');
            return_json_encode($result);
        }

        if (!$label_name) {
            $result['msg'] = lang('Please_select_evaluation_label');
            return_json_encode($result);
        }
        /*if(!$content){
            $result['msg'] = '请填写评价内容！';
            return_json_encode($result);
        }*/
        //评价标签
        //$comment_label = Db::name('skills_comment_label')->find($c_id);
        $data = [
            'uid' => $uid,
            'touid' => $order_info['touid'],
            'name' => $label_name,
            'order_id' => $order_id,
            'content' => $content,
            'is_recommend' => $is_recommend,
            'skills_id' => $order_info['skills_id'],
            'addtime' => NOW_TIME,
        ];
        $res = Db::name('skills_comment')->insertGetId($data);
        if ($res) {
            $result['code'] = 1;
            $result['msg'] = lang('Successful_evaluation');
            $data = [
                'status' => 6,
                'edit_time' => NOW_TIME,
            ];
            $where_up = 'id = ' . $order_id . ' and uid =' . $uid;
            $this->SkillsOrder->up_order($where_up, $data);
            //增加推荐数量
            if ($is_recommend == 1) {
                Db::name('skills_order')->alias('s')->where($where)->setInc('recommend_num');
            }
            //添加统计评价标签
            $label = explode(',', $label_name);
            foreach ($label as $val) {
                $label_info = Db::name('skills_info_label')
                    ->where(['label_name' => $val, 'skills_id' => $order_info['skills_id']])
                    ->find();
                if ($label_info) {
                    Db::name('skills_info_label')
                        ->where(['id' => $label_info['id']])
                        ->inc('num', 1)
                        ->update();
                } else {
                    $info = [
                        'label_name' => $val,
                        'num' => 1,
                        'skills_id' => $order_info['skills_id'],
                    ];
                    Db::name('skills_info_label')->insertGetId($info);
                }
            }
        }
        return_json_encode($result);
    }

    //删除评价
    public function del_add_comment()
    {
        $result = array('code' => 1, 'msg' => lang('DELETE_SUCCESS'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $cid = intval(input('cid'));//评价ID

        check_login_token($uid, $token, ['last_login_ip']);
        $comment = Db::name('skills_comment')->where(['uid' => $uid, 'id' => $cid])->find();
        if (!$comment) {
            $result['code'] = 0;
            $result['msg'] = lang('Evaluation_information_does_not_exist');
            return_json_encode($result);
        }
        //删除评价
        $res = Db::name('skills_comment')->where(['uid' => $uid, 'id' => $cid])->delete();
        if (!$res) {
            $result['code'] = '0';
            $result['msg'] = lang('DELETE_FAILED');
        }
        //去除标签
        if ($comment['name']) {
            $label = explode(',', $comment['name']);
            //$where = 'label_name in ('.$comment['name'].')';
            $list = Db::name('skills_info_label')->where('label_name', 'in', $label)->where('skills_id = ' . $comment['skills_id'])->select();
            $update_id = [];
            $del_id = [];
            foreach ($list as $v) {
                if ($v['num'] > 1) {
                    array_push($update_id, $v['id']);
                } else {
                    array_push($del_id, $v['id']);
                }
            }
            if ($update_id) {
                Db::name('skills_info_label')->where('id', 'in', $update_id)->where('skills_id = ' . $comment['skills_id'])->setDec('num');
            }
            if ($del_id) {
                Db::name('skills_info_label')->where('id', 'in', $del_id)->where('skills_id = ' . $comment['skills_id'])->delete();
            }
            /*dump($update_id);
            dump($del_id);
            dump($list);
            die();*/
        }

        return_json_encode($result);
    }

    //订单详情-下单
    public function get_order_info()
    {
        $result = array('code' => 0, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $order_id = intval(input('order_id'));//订单ID
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        $where = 's.id = ' . $order_id . ' and s.uid = ' . $uid;
        $order_info = $this->SkillsOrder->get_info($where);
        if (!$order_info) {
            $result['msg'] = lang('Order_information_acquisition_failed');
            return_json_encode($result);
        }

        $result['code'] = 1;
        $result['data'] = $order_info;
        return_json_encode($result);
    }

}
