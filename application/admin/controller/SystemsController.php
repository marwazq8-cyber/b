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

use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;
use think\Db;

class SystemsController extends AdminBaseController
{

    /**
     *  系统设置
     * @adminMenu(
     *     'code'   => '系统唯一标识',
     *     'titlt' => '系统名称',
     *     'group_id'=> 系统分类,
     *     'val'=> 设置系统的值,
     *     'type'  => 系统的属性 0 input 1文本域 2图片地址 3多选 4单选 5时间 ,
     *     'sort'   => '排序',
     *     'value_scope' => '值的范围',
     *     'title_scope'  => '对应value_scope的中文解释'
     *     'desc'  => '描述'
     * )
     */
    public function index()
    {

        //$where = "group_id != '公会管理' and group_id != 'UCLOUD配置' and group_id != '邀请设置'";

        $where = "group_id != '公会管理' and group_id != 'UCLOUD配置'";
        if (IS_AGENT != 1) {
            $where .= " and group_id != '渠道设置'";
        }
        if (IS_GUILD != 1) {
            $where .= " and group_id != '公会配置'";
        }
        if (defined('OPEN_AUTO_SEE_HI_PLUGS') && OPEN_AUTO_SEE_HI_PLUGS == 0) {
            $where .= " and group_id != '自动打招呼插件设置'";
            //$where = " group_id != '自动打招呼插件设置'";

        }

        //系统设置分类
        $type = Db::name('config')->distinct("group_id")->field('group_id')
            ->where($where)->select();

        // 1 充值的虚拟币名称 2收益名称
        $coin = Db::name('config')->where("code='currency_name'")->field("val")->find();

        $earnings = Db::name('config')->where("code='virtual_currency_earnings_name'")->field("val")->find();

        $virtual_coin = array('coin' => $coin['val'], 'earnings' => $earnings['val']);

        $config = Db::name('config')->order('sort desc')->select()->toArray();
        foreach ($config as $k => &$v) {

            if ($v['status'] == 0) {
                unset($config[$k]);
                continue;
            }

            if ($v['type'] == 4) {
                $keys = explode(",", $v['value_scope']);
                $val = explode(",", $v['title_scope']);
                $value = array_combine($keys, $val);
                $v['type_val'] = $value;
            } else if ($v['type'] == 3) {
                $keys = explode(",", $v['value_scope']);
                $val = explode(",", $v['title_scope']);
                $check = explode(",", $v['val']);
                $value = array_combine($keys, $val);
                $v['checkbox_check'] = $check;
                $v['checkbox_val'] = $value;

            } else if ($v['type'] == 6) {   //列表
                $list = explode(",", $v['val']);
                $v['list'] = $list;

            } else if ($v['type'] == 8) {   //下拉列表
                $val = explode(",", $v['title_scope']);
                $keys = explode(",", $v['value_scope']);
                $list = array_combine($keys, $val);
                $v['list'] = $list;
            }
        }

        $this->assign('virtual_coin', $virtual_coin);
        $this->assign('config', $config);
        $this->assign('type', $type);
        $this->assign('debug', IS_DEBUG);
        $this->assign('admin_id', session('ADMIN_ID'));
        return $this->fetch();
    }

    /**
     * 网站信息设置提交
     * @adminMenu(
     *     'name'   => '网站信息设置提交',
     *     'parent' => 'site',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '网站信息设置提交',
     *     'param'  => ''
     * )
     */
    public function upd_post()
    {
        if(IS_TEST && get_client_ip() != '222.132.157.163'){
            $this->error('The demonstration version prohibits this operation');
        }

        $nam = input('post.');

        foreach ($nam as $k => $v) {
            if (is_array($v)) {
                $nam_val = '';
                foreach ($v as $value) {

                    if ($value >= '0') {
                        $nam_val .= $value . ',';
                    }
                }
                $arr = rtrim($nam_val, ',');
                Db::name('config')->where("code='$k'")->setField('val', $arr);
            } else {
                if ($k == 'talker_audio_coin' && $v == 0) {
                    $v = 1;
                }
                if ($k == 'talker_video_coin' && $v == 0) {
                    $v = 1;
                }
                Db::name('config')->where("code='$k'")->setField('val', $v);
            }
        }
        $file = DOCUMENT_ROOT . "/system/cache/config.auto_cache.php";
        require_once $file;
        $class = "config_auto_cache";
        $obj = new $class;
        $obj->clear_all();
        $this->success(lang('EDIT_SUCCESS'), url("systems/index"));

    }

    public function desc_set()
    {
        $result = array('code' => 0, 'msg' => '');
        $code = input('code');
        $desc = input('desc');
        $title = trim(input('title'));
        $sort = intval(input('sort'));
        $data = [
            'desc'  => $desc,
            'title' => $title,
            'sort'  => $sort,
        ];
        $res = Db::name('config')->where("code='$code'")->update($data);
        if ($res) {
            $result['code'] = 1;
            $result['msg'] = lang('Operation_successful');
        } else {
            $result['msg'] = lang('operation_failed');
        }
        echo json_encode($result);
    }

    //添加配置信息
    public function add_sys()
    {
        //$where = "group_id != '公会管理'";
        $where = "group_id != '公会管理' and group_id != 'UCLOUD配置' and group_id != '渠道设置'";
        if (defined('OPEN_AUTO_SEE_HI_PLUGS') && OPEN_AUTO_SEE_HI_PLUGS == 0) {
            $where .= " and group_id != '自动打招呼插件设置'";
        }
        // 1 充值的虚拟币名称 2收益名称
        $coin = Db::name('config')->where("code='currency_name'")->field("val")->find();

        $earnings = Db::name('config')->where("code='virtual_currency_earnings_name'")->field("val")->find();

        $data = array('coin' => $coin['val'], 'earnings' => $earnings['val']);
        //系统设置分类
        $type = Db::name('config')->distinct("group_id")->where($where)->field('group_id')->select();
        $this->assign('data', $data);
        $this->assign('type', $type);
        return $this->fetch();
    }

    //添加配置信息
    public function add_post()
    {
        if(IS_TEST){
            $this->error('The demonstration version prohibits this operation');
        }

        $post = input('post.');
        $nam = $post['post'];
        if ($nam['type'] == '3') {
            if (is_array($post['val'])) {
                $nam['val'] = $post['val'] ? $post['val'][$nam['type']] - 1 : '';
            }

            $nam['title_scope'] = $post['title_scope'][0];
            $nam['value_scope'] = $this->jsm($post['title_scope'][0]);
        } else if ($nam['type'] == '4') {
            if (is_array($post['val'])) {
                $nam['val'] = $post['val'][$nam['type']] - 1;
            }
            $nam['title_scope'] = $post['title_scope'][1];
            $nam['value_scope'] = $this->jsm($post['title_scope'][1]);
        } else if ($nam['type'] == '6') {
            $val = '';
            if (count($post['list_val']) > 0) {
                foreach ($post['list_val'] as $v) {
                    if ($v) {
                        $val .= $v . ',';
                    }
                }
            }
            $nam_val = rtrim($val, ',');
            $nam['val'] = $nam_val;
        } else if ($nam['type'] == '7') {
            $nam['val'] = $post['val'];
        } else if ($nam['type'] == '8') {
            //list_val
            $val = '';
            if (count($post['list_val']) > 0) {
                foreach ($post['list_val'] as $v) {
                    if ($v) {
                        $val .= $v . ',';
                    }
                }
            }
            $nam_val = rtrim($val, ',');
            $nam['title_scope'] = $nam_val;

        } else {
            if (is_array($post['val'])) {
                $nam['val'] = $post['val'][$nam['type']];
            }

            $nam['title_scope'] = '';
            $nam['value_scope'] = '';
        }
        $type = Db::name('config')->insert($nam);
        if ($type) {
            $this->success(lang('EDIT_SUCCESS'), url("systems/index"));
        } else {
            $this->error(lang('EDIT_FAILED'), url("systems/index"));
        }

    }

    //字符串转换数组
    public function jsm($all)
    {
        $vas = explode(',', $all);
        $keys = array_keys($vas);
        $check = implode(",", $keys);
        return $check;
    }

    public function export_group_json()
    {
        $group_id = input('group_id');
        $list = db('config')->where('status = 1 and group_id = "' . $group_id . '"')->select();

        $json = json_encode($list, JSON_UNESCAPED_UNICODE);
        $data['code'] = 1;
        $data['href'] = $json;
        echo json_encode($data);
    }


}
