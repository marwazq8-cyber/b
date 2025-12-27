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
use app\admin\model\AdminMenuModel;

class IndexController extends AdminBaseController
{
    private $lang_list= array();
    private  $lang_val = array();
    public function _initialize()
    {

        $adminSettings = cmf_get_option('admin_settings');
        if (empty($adminSettings['admin_password']) || $this->request->path() == $adminSettings['admin_password']) {
            $adminId = cmf_get_current_admin_id();
            if (empty($adminId)) {
                session("__LOGIN_BY_CMF_ADMIN_PW__", 1);//设置后台登录加密码
            }
        }
        $this->lang_list[] = array(
            'id'=>'zh-cn',
            'name'=>lang('中文'),
        );
        $this->lang_list[] = array(
            'id'=>'en',
            'name'=>lang('英文'),
        );

        foreach ($this->lang_list as $v){
            $this->lang_val[$v['id']]=$v['name'];
        }
        parent::_initialize();
    }

    /**
     * 后台首页
     */
    public function index()
    {
        //REGISTER TODAY
        //echo strtoupper('register today').'<br/>';
        //exit;
        $adminMenuModel = new AdminMenuModel();
        $menus = $adminMenuModel->menuTree();
        //  var_dump(APP_DEBUG);exit;
        $this->assign("menus", $menus);

        $admin = Db::name("user")->where('id', cmf_get_current_admin_id())->find();
        $lang = cookie('think_var') ? cookie('think_var') : 'zh-cn';

        //权限检查
        $is_home_page=0;
        if (cmf_get_current_admin_id() == 1) {
            //如果是超级管理员 直接通过
            $is_home_page= 1;
        }else{
            $roleId =intval(session('ADMIN_GROUPS_ID'));
            $authAccess = Db::name("authAccess")->where(["role_id" => $roleId, 'type' => 'admin_url','rule_name'=>'admin/main/index'])->find();

            if($authAccess){
                $is_home_page= 1;
            }
        }
        // 获取当前账户是否有首页权限
        if ($is_home_page == 1){
            $url = "Main/index";
        }else{
            $url = "Main/not_index";
        }
        $this->assign('home_url', $url);

        $this->assign('lang', $this->lang_val[$lang]);
        $this->assign('lang_list', $this->lang_list);
        $this->assign('admin', $admin);
        $this->assign('debug', APP_DEBUG);
        return $this->fetch();
    }
    /**
     * 更改语言包
     * */
    public function lang_save(){
        $root = array('code'=> 1,'msg'=>lang('操作成功'));
        $lang_val = input('param.lang_val');
        cookie('think_var',$lang_val);
        echo json_encode($root);
    }
    //未读的消息 认证
    public function message_index()
    {
        //视频审核统计
        $data['user_video'] = db("user_video")->where("type=0")->count();
        //私照审核统计
        $data['user_pictures'] = db("user_pictures")->where("status=0")->count();
        //封面图审核统计
        $data['user_img'] = db("user_img")->where("status=0")->count();
        //信息认证审核统计
        $data['auth_record'] = db("auth_form_record")->where("status=0")->count();

        echo json_encode($data);
        exit;
    }

}

