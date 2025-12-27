<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-06-01
 * Time: 10:25
 */

namespace app\api\controller;

use think\Request;
use think\Db;
use think\helper\Time;
use app\api\model\PlaywithModel;
use app\api\model\UserModel;
use app\api\model\SkillsInfo;
use app\api\model\SkillsOrder;

class NobleVueApi extends Base
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

    public function get_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $id = intval(input('param.id'));
        $token = trim(input('param.token'));
        $lang = trim(input('param.lang')) ? trim(input('param.lang')) : 'zh-cn';
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time']);

        $database_lang = DATABASE_LANG;

        $list = Db::name('noble')->where('status = 1')->order('orderno')->select();
        foreach ($list as &$vl){
            $whitelist_langs = json_decode($vl['lang_name'],true);
            $vl['chat_bg'] = Db::name('dress_up')->where('id',$vl['chat_bg_id'])->value('img_bg');
            foreach ($database_lang as $v){
                if($v==$lang){
                    if (isset($whitelist_langs[$lang])){
                        $vl['name'] =$whitelist_langs[$lang];
                        break;
                    }
                }
            }
        }
        if (!$id) {
            $info = Db::name('noble')->where('status = 1')->order('orderno')->find();
        } else {
            $info = Db::name('noble')->where('status = 1')->find($id);
        }
        $privilege = [];
        if ($info) {
            $info_langs = json_decode($info['lang_name'],true);
            $info['name'] = $info_langs[$lang] ? $info_langs[$lang] : $info['name'];
            $privilege_id = $info['privilege_id'];
            $privilege_id = explode(',', $privilege_id);
            //dump($privilege_id);
            //$where['id'] = ['in',$privilege_id];
            //$privilege = Db::name('noble_privilege')->where($where)->select();
            $privilege = Db::name('noble_privilege')->order("orderno asc")->select();

            foreach ($privilege as &$val) {
                $whitelist_lang = json_decode($val['lang_name'],true);
                 foreach ($database_lang as $v){
                    if($v==$lang){
                        if (isset($whitelist_lang[$lang])){
                            $val['name'] =$whitelist_lang[$lang];
                            break;
                        }
                    }
                 }
                if (!in_array($val['id'], $privilege_id)) {
                    $val['privilege_img'] = $val['no_img'];
                }
            }
        }
        $data = [];
        /*$date = date("Y-m-d");
        // 本月第一天
        $first = date('Y-m-01 0:0:0', strtotime($date));
        // 本月最后一天
        $last = date('Y-m-d 23:59:59', strtotime("$first +1 month -1 day"));
        $first_time = strtotime($first);
        $last_time = strtotime($last);
        $where_coin['uid'] = $uid;
        $where_coin['status'] = 1;
        $where_coin['addtime'] = ['BETWEEN',[$first_time,$last_time]];
        $coin = Db::name('user_charge_log')->where($where_coin)->sum('coin');*/
        $noble_id = 0;
        if ($user_info['noble_end_time'] > NOW_TIME) {
            $noble_id = $user_info['nobility_level'];
        }
        $config = load_cache('config');
        $difference = $user_info['noble_end_time'] - time();
        $days = floor($difference / (60 * 60 * 24));
        $hours = floor(($difference % (60 * 60 * 24)) / (60 * 60));
        $minutes = floor(($difference % (60 * 60)) / 60);
        $seconds = $difference % 60;

        $data['noble_list'] = $list;
        $data['noble_info'] = $info;
        $data['privilege'] = $privilege;
        $data['currency_name'] = $config['currency_name'];
        $data['noble_end_time'] = date("Y-m-d H:i:s", $user_info['noble_end_time']);
        $data['noble_end_time_days'] = $days;
        $data['noble_id'] = $noble_id;
        $result['data'] = $data;
        return_json_encode($result);
    }

    //购买
    public function buy_noble()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $id = intval(input('param.id'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['nobility_level', 'noble_end_time', 'last_login_ip']);
        $info = Db::name('noble')->find($id);
        if (!$info) {
            $result['code'] = 0;
            $result['msg'] = lang('Aristocratic_information_error');
            return_json_encode($result);
        }
        //查询续费还是开通
        if ($id == $user_info['nobility_level'] && $user_info['noble_end_time'] > NOW_TIME) {
            $buy_type = 2;
            $coin = $info['renew_coin'];
            $time = $user_info['noble_end_time'] + $info['noble_time'] * 86400;
            $msg = lang('renew');
            //返回金币
            $return_coin = $info['return_coin'];

        } else {
            $buy_type = 1;
            $coin = $info['coin'];
            $time = NOW_TIME + $info['noble_time'] * 86400;
            $msg = lang('purchase');
            $return_coin = 0;
        }

        //贵族是否低于当前贵族
//            if($user_info['nobility_level']>$id && $user_info['noble_end_time']>NOW_TIME){
//                $result['code'] = 0;
//                $result['msg'] = '贵族等级低于您当前等级';
//                return_json_encode($result);
//            }
        if ($user_info['coin'] < $coin) {
            $result['code'] = 10017;
            $result['msg'] = lang('Insufficient_Balance');
            return_json_encode($result);
        }
        //扣费
        $res = db('user')->where(['id' => $uid])->dec('coin', $coin)->update();
        if ($res) {
            //添加贵族 添加购买记录
            db('user')
                ->where(['id' => $uid])
                ->update(['nobility_level' => $id, 'noble_end_time' => $time]);
            upd_user_coin_log($uid, $coin, $coin, 12, 1, 2, $user_info['last_login_ip'], $uid);
            // 钻石变更记录
            save_coin_log($uid, '-' . $coin, 1, 10, $info['name']);

            if ($return_coin > 0) {
                //返金币
                db('user')->where(['id' => $uid])->inc('coin', $return_coin)->update();
                upd_user_coin_log($uid, $return_coin, $return_coin, 13, 2, 1, $user_info['last_login_ip'], $uid);
                // 钻石变更记录
                save_coin_log($uid, $return_coin, 1, 10, $info['name']);
            }
            $this->save_user_dress_up($uid,$info);
            $data = [
                'uid' => $uid,
                'noble_id' => $id,
                'buy_type' => $buy_type,
                'addtime' => NOW_TIME,
                'endtime' => $time,
            ];
            $table_id = db('user_noble_log')->insertGetId($data);
            //消费记录
            add_charging_log($uid, 1, 6, $coin, $table_id, '1');
            $result['msg'] = $msg . lang('SUCCESS');
        } else {
            $result['code'] = 0;
            $result['msg'] = $msg . lang('FAILED');
        }

        return_json_encode($result);

    }
    // 增加装饰
    public function save_user_dress_up($uid,$info){
        $days = 0;
        if (intval($info['noble_time']) > 0) {
            $days = $info['noble_time']*24*60*60;
        }
        if($days){
            if($info['chat_bg_id']){
                $dress_up_id_array = explode(",", $info['chat_bg_id']);
                foreach ($dress_up_id_array as $v){
                    $this->dress_up_save($uid,5,$v,$days);
                }
            }
            if($info['chat_bubble_id']){
                $dress_up_id_array = explode(",", $info['chat_bubble_id']);
                foreach ($dress_up_id_array as $v){
                    $this->dress_up_save($uid,4,$v,$days);
                }
            }
            if($info['avatar_frame_id']){
                $dress_up_id_array = explode(",", $info['avatar_frame_id']);
                foreach ($dress_up_id_array as $v){
                    $this->dress_up_save($uid,3,$v,$days);
                }
            }
            if($info['home_page_id']){
                $dress_up_id_array = explode(",", $info['home_page_id']);
                foreach ($dress_up_id_array as $v){
                    $this->dress_up_save($uid,2,$v,$days);
                }
            }
            if($info['medal_id']){
                $dress_up_id_array = explode(",", $info['medal_id']);
                foreach ($dress_up_id_array as $v){
                    $this->dress_up_save($uid,1,$v,$days);
                }
            }
            if($info['car_id']){
                $dress_up_id_array = explode(",", $info['car_id']);
                foreach ($dress_up_id_array as $v){
                    $this->dress_up_save($uid,7,$v,$days);
                }
            }
        }
    }
    // 封装装扮
    public function dress_up_save($uid,$type,$dress_id,$days){
        $time = NOW_TIME;
        $endtime = $days + $time;
        $dress_up = db('dress_up') ->where(['id'=>intval($dress_id)])->find();
        if($dress_up){
            if($type == 5){
                $dress_up['icon'] = $dress_up['img_bg'] ? $dress_up['img_bg'] : $dress_up['icon'];
            }
            //是否购买过
            $info = db('user_dress_up') ->where(['uid'=>$uid,'dress_id'=>intval($dress_id)])->find();
            // 关闭正在使用的
            db('user_dress_up')->alias('u')
                ->join('dress_up d','d.id=u.dress_id')
                ->where('u.uid',$uid)
                ->where("d.type",$type)
                ->update(['status'=>0]);
            if($info){
                $end_time = $info['endtime'] > $time ? $info['endtime'] + $days : $endtime;
                db('user_dress_up')->where(['uid'=>$uid,'dress_id'=>intval($dress_id)])
                    ->update(['endtime' => $end_time,'status' =>1,'dress_up_name'=>$dress_up['name'],'dress_up_icon'=>$dress_up['icon'],'dress_up_type'=>$type]);
            }else{
                $data = [
                    'uid'=>$uid,
                    'dress_id' => intval($dress_id),
                    'status' => 1,
                    'addtime' => $time,
                    'endtime' => $endtime,
                    'dress_up_name' => $dress_up['name'],
                    'dress_up_icon'=> $dress_up['icon'],
                    'dress_up_type'=> $type
                ];
                db('user_dress_up')->insertGetId($data);
            }
        }
    }
    public function test()
    {
        $data = [];
        $date = date("Y-m-d");
        // 本月第一天
        $first = date('Y-m-01 0:0:0', strtotime($date));
        // 本月最后一天
        $last = date('Y-m-d 23:59:59', strtotime("$first +1 month -1 day"));
        //$first_time = strtotime($first);
        //$last_time = strtotime($last);
        //上月第一天
        $top_one = date('Y-m-d ', strtotime("$first -1 month "));
        $top_end = date('Y-m-d 23:59:59', strtotime("$top_one +1 month -1 day"));
        echo $top_end;
    }
}
