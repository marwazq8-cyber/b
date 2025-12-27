<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20 0020
 * Time: 上午 10:38
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class SmsController extends AdminBaseController
{
    //短信发送管理
    public function index()
    {
        /**搜索条件**/
        $p = $this->request->param('page');

        /**搜索条件**/
        $account = $this->request->param('account');

        $where='';
        if ($account) {
            $where.="account like '%".$account."%'";
        }

        $users = Db::name('verification_code')
            ->order("id DESC")->where($where)
            ->paginate(15, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();
        foreach ($name['data'] as $k=>$v){

            if(IS_MOBILE == 0){
                $name['data'][$k]['account'] = substr( $v['account'], 0, 3).'****'.substr( $v['account'], strlen($v['account']) - 4,4);
            }elseif(session('ADMIN_GROUPS_ID')==6){
                $name['data'][$k]['account'] = substr( $v['account'], 0, 5).'****'.substr( $v['account'], 9);
            }
            $name['data'][$k]['account'] ="(".$name['data'][$k]['phone_area_code'].")".$name['data'][$k]['account'];
        }

        $this->assign("page", $page);
        $this->assign("users", $name['data']);
        return $this->fetch();
    }

    public function config(){
        $list = Db::name('cloud_sms_config')
            ->order("id DESC")
            ->paginate(15, false, ['query' => request()->param()]);
        $page = $list->render();
        $sum = Db::name('cloud_sms_config')->count();
        $is_delete = $sum > 1 ? 1 : 0;
        $this->assign("is_delete", $is_delete);
        $this->assign("page", $page);
        $this->assign("list", $list);
        return $this->fetch();
    }

    public function add_config(){
        $id = intval(input('id'));
        if($id){
            $list = Db::name('cloud_sms_config')->find($id);
        }else{
            $list['status'] = 1;
            $list['type'] = 0;
            $list['val'] = '';
        }
        $type  = $this->sms_type();
        $this->assign("list", $list);
        $this->assign("type", $type);
        return $this->fetch();
    }

    public function addPost(){
        $param = $this->request->param();
        $data = $param['post'];
        $id = $param['id'];
        $data['addtime'] = NOW_TIME;
        $type  = $this->sms_type();
        $data['name'] = $type[$data['val']];
        if ($id) {
            $result = Db::name("cloud_sms_config")->where("id=$id")->update($data);
        } else {
            $result = Db::name("cloud_sms_config")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('sms/config'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    public function del(){
        $id = intval(input('id'));
        if($id){
            $res = Db::name("cloud_sms_config")->where('id = '.$id)->delete();
        }else{
            $res = false;
        }
        echo $res?1:0;
    }

    public function sms_type(){
        $type = array(
            'aliyun'=>lang("阿里云"),
            'yuntongxun'=>lang("容联云通讯"),
            'huyi'=>lang("互亿无限"),
            'baidu'=>lang("百度云"),
            'qcloud'=>lang("腾讯云 SMS"),
            'huawei'=>lang("华为云 SMS"),
            'yunxin'=>lang("网易云信"),
            'qiniu'=>lang("七牛云"),
            'yunzhixun'=>lang("云之讯"),
            'huaxin'=>lang("华信短信平台"),
            'yunpian'=>lang("云片"),
            'submail'=>'Submail',
            'luosimao'=>lang("螺丝帽"),
            'juhe'=>lang("聚合数据"),
            'sendcloud'=>'SendCloud',
            'chuanglan'=>'253云通讯（创蓝）',
            'rongcloud'=>lang("融云"),
            'tianyiwuxian'=>lang("天毅无线"),
            'twilio'=>'twilio',
            'tiniyo'=>'tiniyo',
            'avatardata'=>lang("阿凡达数据"),
            'kingtto'=>lang("凯信通"),
            'ucloud'=>'ucloud',
            'smsbao'=>lang("短信宝"),
            'moduyun'=>lang("摩杜云"),
            'vonage'=>'vonage',
            'engage'=>'engage',
            );
        return $type;
    }
}
