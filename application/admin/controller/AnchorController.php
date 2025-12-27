<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class AnchorController extends AdminBaseController
{
    /**
     * 主播统计
     */
    public function index()
    {    
        $where = '';
        if(!empty($this->request->param('uid'))){
            $where['id'] = $this->request->param('uid');
        }
        $user = db('user')->where('is_auth',1)->field('id,user_nickname,income_total')->where($where)->paginate(10);
        $user_list = [];
        foreach($user as $key=>$val){
            //视频通话数量
            $videoWhere_a = 'type = 0 and status = 2 and user_id = '.$val['id'];
            $videoWhere_b = 'type = 0 and status = 3 and user_id = '.$val['id'];
            $videoWhere_c = 'type = 0 and status = 2 and call_be_user_id = '.$val['id'];
            $videoWhere_d = 'type = 0 and status = 3 and call_be_user_id = '.$val['id'];
            $videoCount = db('video_call_record_log')->where($videoWhere_a)->count();
            $videoCount += db('video_call_record_log')->where($videoWhere_b)->count();
            $videoCount += db('video_call_record_log')->where($videoWhere_c)->count();
            $videoCount += db('video_call_record_log')->where($videoWhere_d)->count();
            $val['video_count'] = $videoCount;
            //语音
            $audioWhere_a = 'type = 1 and status = 2 and user_id = '.$val['id'];
            $audioWhere_b = 'type = 1 and status = 3 and user_id = '.$val['id'];
            $audioWhere_c = 'type = 1 and status = 2 and call_be_user_id = '.$val['id'];
            $audioWhere_d = 'type = 1 and status = 3 and call_be_user_id = '.$val['id'];
            $audioCount = db('video_call_record_log')->where($audioWhere_a)->count();
            $audioCount += db('video_call_record_log')->where($audioWhere_b)->count();
            $audioCount += db('video_call_record_log')->where($audioWhere_c)->count();
            $audioCount += db('video_call_record_log')->where($audioWhere_d)->count();
            $val['audio_count'] = $audioCount;
            //私信收益
            $private_coin = db('user_private_chat_log')->where('to_user_id',$val['id'])->sum('coin');
            $val['private_coin']=$private_coin;
            //邀请人数
            $invite_count = db('invite_record')->where('user_id',$val['id'])->count();
            $val['invite_count'] = $invite_count;
            $user_list[]= $val;
        }

        $this->assign('list', $user_list);
        $this->assign('page', $user->render());
        return $this->fetch();
    }

    //主播列表
    public function anchor_list(){
        $where = '';
        if(!empty($this->request->param('uid')) && empty($this->request->param('nickname'))){
            $where['id'] = $this->request->param('uid');
        }

        if(empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['user_nickname'] =['like',['%'.$this->request->param('nickname').'%']];
        }
        if(!empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['id'] = $this->request->param('uid');
            $where['user_nickname'] =['like',['%'.$this->request->param('nickname').'%']];
        }

        $where['is_auth'] = 1;
        $user = db('user')->where($where)->paginate(10,false,['query' => request()->param()]);
        $list = [];
        foreach ($user as $key => $value) {
            //接听率
            $value['answer_rate'] = $this->answer_rate($value['id']);
            
            //邀请人ID
            $invite = db('invite_record')->where('invite_user_id',$value['id'])->find();
            $value['invite_uid'] = $invite['user_id'];
            $list[] = $value;
        }

        $this->assign('list',$list);
        $this->assign('page',$user->render());
        return $this->fetch();
    }

    //人气主播
    public function reference_list(){
        $where = '';
        if(!empty($this->request->param('uid')) && empty($this->request->param('nickname'))){
            $where['id'] = $this->request->param('uid');
        }

        if(empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['user_nickname'] =['like',['%'.$this->request->param('nickname').'%']];
        }
        if(!empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['id'] = $this->request->param('uid');
            $where['user_nickname'] =['like',['%'.$this->request->param('nickname').'%']];
        }

        $where['is_auth'] = 1;
        $where['reference'] = 1;
        $user = db('user')->where($where)->paginate(10,false,['query' => request()->param()]);
        $list = [];
        foreach ($user as $key => $value) {
            //接听率
            $value['answer_rate'] = $this->answer_rate($value['id']);
            
            //邀请人ID
            $invite = db('invite_record')->where('invite_user_id',$value['id'])->find();
            $value['invite_uid'] = $invite['user_id'];
            $list[] = $value;
        }

        $this->assign('list',$list);
        $this->assign('page',$user->render());
        return $this->fetch();
    }

    public function add_black(){
        $request = request()->param();
        $id = $request['id'];
        $userInfo = db('user')->field('user_status')->find($id);
        if($userInfo['user_status']==0){
            $res = db('user')->where(['id'=>$id])->update(['user_status'=>2]);
        }else{
            $res = db('user')->where(['id'=>$id])->update(['user_status'=>0]);
        }
        if ($res !== false) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    public function add_reference(){

        $request = request()->param();
        $id = $request['id'];
        $userInfo = db('user')->field('reference')->find($id);
        if($userInfo['reference']==0){
              $data = array(
                    'uid' => $id,
                    'addtime' => time(),
                );
                Db::name("user_reference")->insert($data);
            $res = db('user')->where(['id'=>$id])->update(['reference'=>1]);
        }else{
             Db::name("user_reference")->where("uid=$id")->delete();
            $res = db('user')->where(['id'=>$id])->update(['reference'=>0]);
        }
        if ($res !== false) {
            $this->success(lang('Operation_successful'));
        } else {
            $this->error(lang('operation_failed'));
        }
    }

    //排序
    public function reference_order(){
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user")->where("id=$k")->update(array('orderno' => $v));
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

    //接听率
    public function answer_rate($id){
        $answer_yes = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->where('status', '=', 1)
            ->whereOr('call_be_user_id', '=', $id)
            ->count();
        $answer = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->whereOr('call_be_user_id', '=', $id)
            ->count();
        if($answer_yes==0 || $answer==0){
            return 0;
        }else{
            return round($answer_yes/$answer,2)*100;
        }
    }

    //设备封禁
    public function equipment_closures(){
        $request = request()->param();
        $uid = isset($request['uid']) ? intval($request['uid']) : '';
        $device_uuid = empty($request['device_uuid']) ? '' : $request['device_uuid'];
        $where = "e.id > 0";
        $where .= $uid ? " and e.uid=".$uid : "";
        $where .= $device_uuid ? " and e.device_uuid like'%".$device_uuid."%'" : "";

        $res = db('equipment_closures')
            ->alias('e')
            ->join('user u','u.id=e.uid')
            ->field('u.user_nickname,u.sex,e.*')
            ->where($where)
            ->paginate(10);
        $list = [];
        foreach ($res as $key => $value) {
            //邀请人ID
            $invite = db('invite_record')->where('invite_user_id',$value['uid'])->find();
            $value['invite_uid'] = $invite['user_id'];
            $list[] = $value;
        }
        $data= array(
            'uid'=> $uid,
            'device_uuid' =>$device_uuid
        );
        $this->assign('data',$data);
        $this->assign('list',$list);
        $this->assign('page',$res->render());
        return $this->fetch();
    }

    public function del_closures(){
        $res = db('equipment_closures')->delete(request()->param('id'));
        if ($res) {
            $this->success(lang('DELETE_SUCCESS'));
        } else {
            $this->success(lang('DELETE_FAILED'));
        }
    }

}
