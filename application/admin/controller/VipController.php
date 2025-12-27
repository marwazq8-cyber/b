<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/17
 * Time: 01:31
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;
use think\Db;

class VipController extends AdminBaseController
{
    public function vip_list(){
        $list = db('vip')->alias("v")
            ->field("v.*")
            ->select();

        $this->assign('list',$list);
        return $this->fetch();
    }
    public function vip_list_add(){
        $id = input('param.id');
        $headwear_list = Db::name("dress_up")->where("type=3 and is_vip=1")->select();
        $approach_list = Db::name("dress_up")->where("type=7 and is_vip=1")->select();
        $bubble_list = Db::name("dress_up")->where("type=4 and is_vip=1")->select();
        if ($id) {
            $name = Db::name("vip")->where("id=$id")->find();
        }else{
            $name= array(
                'status' =>1,
                'icon' =>'',
                'interval_icon' =>'',
                'identity_url' =>'',
                'identity_app' =>'',
                'headwear_id' => 0,
                'headwear_web' => '',
                'approach_id' => 0,
                'approach_web' => '',
                'sound_wave_url' =>'',
                'sound_wave_app' =>'',
                'bubble_id' => 0,
                'bubble_web' => '',
                'room_card_url' =>'',
                'room_card_app' =>'',
                'is_nickname' => 1,
                'sign_in_coin' => 0,
                'is_rank' => 1,
                'is_visitors' => 1,
                'is_private_chat' => 0,
                'is_stealth' => 1,
                'is_ban_attention' => 1,
                'is_kick' => 1,
                'days' => '',
                'shop_coin' => '',
                'level_acceleration' => '',
                'maximum_fans' => '',
                'maximum_attention' => '',
                'exclusive_nickname' => '',
                'colors' => '',
            );
        }
        $this->assign('data', $name);
        $this->assign('headwear_list', $headwear_list);
        $this->assign('approach_list', $approach_list);
        $this->assign('bubble_list', $bubble_list);
        return $this->fetch();
    }
    public function add_post_vip_list(){

        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
//        if($data['sound_wave_url']){
//            $sound_wave_url = substr($data['sound_wave_app'],-4);
//            if ($sound_wave_url != 'webp') {
//                $this->error(lang('format_must_be_webp'));
//            }
//        }
        $data['create_time'] = time();
        if($id){
            $result = Db::name("vip")->where("id=$id")->update($data);
        }else{
            $result = Db::name("vip")->insert($data);
        }
        if($result){
            redis_hDelOne('vip_level_list', 1);
            $vip_level_user = redis_hkeys("vip_level_user");
            foreach ($vip_level_user as $v){
                redis_hDelOne('vip_level_user', $v);
            }
            $this->success(lang('EDIT_SUCCESS'),url('vip/vip_list'));
        }else{
            $this->error(lang('EDIT_FAILED'));
        }
    }
    public function vip_list_del(){
        $id = input('param.id');
        $result = Db::name('vip') -> delete($id);
        redis_hDelOne('vip_level_list', 1);
        return $result ? '1' : '0';
        exit;
    }
    public function index(){
        $list = db('vip_rule') -> select();

        $this->assign('list',$list);
        return $this->fetch();
    }

    public function add(){
        $id = input('param.id');
        if ($id) {
            $name = Db::name("vip_rule")->where("id=$id")->find();

        }else{
            $name['status']= 1;
        }
        $this->assign('data', $name);
        return $this->fetch();

    }

    public function add_post(){

        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['create_time'] = time();
        $data['discounts'] = $data['month']*$data['normal_money'] - $data['money'] ;
        if($id){
            $result = Db::name("vip_rule")->where("id=$id")->update($data);
        }else{
            $result = Db::name("vip_rule")->insert($data);
        }
        if($result){
            $this->success(lang('EDIT_SUCCESS'),url('vip/index'));
        }else{
            $this->error(lang('EDIT_FAILED'));
        }
    }

    public function del(){
        $id = input('param.id');
        $result = Db::name('vip_rule') -> delete($id);

        return $result ? '1' : '0';
        exit;
    }







    public function rule(){
        $list = db('vip_rule_details')->order("sort desc") -> select();

        $this->assign('list',$list);
        return $this->fetch();
    }
    public function addrule(){
        $id = input('param.id');
        if ($id) {
            $name = Db::name("vip_rule_details")->where("id=$id")->find();

        }else{
            $name['img']='';
            $name['type']=1;
            $name['status']=1;
        }
        $this->assign('data', $name);
        return $this->fetch();

    }

    public function add_post_rule(){

        $param = $this->request->param();
        $id = $param['id'];
        $data = $param;
        if($data['img'] ==''){
            $this->error(lang('Please_upload_the_icon'));
        }
        if($data['center'] ==''){
            $this->error(lang('Please_enter_details_VIP_rules'));
        }
        $data['addtime'] = time();
        if($id){
            $result = Db::name("vip_rule_details")->where("id=$id")->update($data);
        }else{
            $result = Db::name("vip_rule_details")->insert($data);
        }
        if($result){
            $this->success(lang('EDIT_SUCCESS'),url('vip/rule'));
        }else{
            $this->error(lang('EDIT_FAILED'));
        }
    }

    public function delrule(){
        $id = input('param.id');
        $result = Db::name('vip_rule_details') -> delete($id);

        return $result ? '1' : '0';
        exit;
    }

}