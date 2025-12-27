<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;

class StorageController extends AdminBaseController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 文件存储
     * @adminMenu(
     *     'name'   => '文件存储',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文件存储',
     *     'param'  => ''
     * )
     */
    public function index()
    {
       /* $storage = cmf_get_option('storage');

        if (empty($storage)) {
            $storage['type'] = 'Local';
            $storage['storages'] = ['Local' => ['name' => lang('local')]];
        } else {
            if (empty($storage['type'])) {
                $storage['type'] = 'Local';
            }

            if (empty($storage['storages']['Local'])) {
                $storage['storages']['Tencent'] = ['name' => '腾讯云'];
                $storage['storages']['Local'] = ['name' => lang('local')];
            }
        }*/

        $storage = [];
        $storage['storages']['Tencent'] = ['name' => 'Tencent Cloud COS'];
        //dump($storage);die();

        $this->assign($storage);
        return $this->fetch();
    }

    /**
     * 文件存储
     * @adminMenu(
     *     'name'   => '文件存储设置提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文件存储设置提交',
     *     'param'  => ''
     * )
     */
    public function settingPost()
    {

        $post = $this->request->post();

        $storage = cmf_get_option('storage');
        //dump($storage);die();
        $storage['type'] = $post['type'];
        //{"storages":{"Qiniu":{"name":"\u4e03\u725b\u4e91\u5b58\u50a8","driver":"\\plugins\\qiniu\\lib\\Qiniu"}},"type":"Qiniu"}
        cmf_set_option('storage', $storage);
        $this->success(lang('Set_successfully'), '');

    }


}