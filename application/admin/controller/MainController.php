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
use app\admin\model\Menu;

class MainController extends AdminBaseController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     *  后台欢迎页
     */
    public function not_index()
    {
        $dashboardWidgets = [];
        $widgets = cmf_get_option('admin_dashboard_widgets');

        $defaultDashboardWidgets = [
            '_SystemCmfHub' => ['name' => 'CmfHub', 'is_system' => 1],
            '_SystemMainContributors' => ['name' => 'MainContributors', 'is_system' => 1],
            '_SystemContributors' => ['name' => 'Contributors', 'is_system' => 1],
            '_SystemCustom1' => ['name' => 'Custom1', 'is_system' => 1],
            '_SystemCustom2' => ['name' => 'Custom2', 'is_system' => 1],
            '_SystemCustom3' => ['name' => 'Custom3', 'is_system' => 1],
            '_SystemCustom4' => ['name' => 'Custom4', 'is_system' => 1],
            '_SystemCustom5' => ['name' => 'Custom5', 'is_system' => 1],
        ];

        if (empty($widgets)) {
            $dashboardWidgets = $defaultDashboardWidgets;
        } else {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    $dashboardWidgets['_System' . $widget['name']] = ['name' => $widget['name'], 'is_system' => 1];
                } else {
                    $dashboardWidgets[$widget['name']] = ['name' => $widget['name'], 'is_system' => 0];
                }
            }

            foreach ($defaultDashboardWidgets as $widgetName => $widget) {
                $dashboardWidgets[$widgetName] = $widget;
            }
        }

        $dashboardWidgetPlugins = [];
        $hookResults = hook('admin_dashboard');
        if (!empty($hookResults)) {
            foreach ($hookResults as $hookResult) {
                if (isset($hookResult['width']) && isset($hookResult['view']) && isset($hookResult['plugin'])) { //验证插件返回合法性
                    $dashboardWidgetPlugins[$hookResult['plugin']] = $hookResult;
                    if (!isset($dashboardWidgets[$hookResult['plugin']])) {
                        $dashboardWidgets[$hookResult['plugin']] = ['name' => $hookResult['plugin'], 'is_system' => 0];
                    }
                }
            }
        }


        $smtpSetting = cmf_get_option('smtp_setting');

        $this->assign('dashboard_widgets', $dashboardWidgets);
        $this->assign('dashboard_widget_plugins', $dashboardWidgetPlugins);
        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);
        $this->assign('http', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']);

        return $this->fetch();
    }

    /**
     *  后台欢迎页
     */
    public function index()
    {

        $smtpSetting = cmf_get_option('smtp_setting');

        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);
        $this->assign('http', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']);

//        // 代理充值页面链接
        $this->assign('top_up_url', config('base_config.top_up_url'));

        return $this->fetch();
    }

    public function statistics()
    {
        $dashboardWidgets = [];
        $widgets = cmf_get_option('admin_dashboard_widgets');

        $defaultDashboardWidgets = [
            '_SystemCmfHub' => ['name' => 'CmfHub', 'is_system' => 1],
            '_SystemMainContributors' => ['name' => 'MainContributors', 'is_system' => 1],
            '_SystemContributors' => ['name' => 'Contributors', 'is_system' => 1],
            '_SystemCustom1' => ['name' => 'Custom1', 'is_system' => 1],
            '_SystemCustom2' => ['name' => 'Custom2', 'is_system' => 1],
            '_SystemCustom3' => ['name' => 'Custom3', 'is_system' => 1],
            '_SystemCustom4' => ['name' => 'Custom4', 'is_system' => 1],
            '_SystemCustom5' => ['name' => 'Custom5', 'is_system' => 1],
        ];

        if (empty($widgets)) {
            $dashboardWidgets = $defaultDashboardWidgets;
        } else {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    $dashboardWidgets['_System' . $widget['name']] = ['name' => $widget['name'], 'is_system' => 1];
                } else {
                    $dashboardWidgets[$widget['name']] = ['name' => $widget['name'], 'is_system' => 0];
                }
            }

            foreach ($defaultDashboardWidgets as $widgetName => $widget) {
                $dashboardWidgets[$widgetName] = $widget;
            }


        }

        $dashboardWidgetPlugins = [];

        $hookResults = hook('admin_dashboard');

        if (!empty($hookResults)) {
            foreach ($hookResults as $hookResult) {
                if (isset($hookResult['width']) && isset($hookResult['view']) && isset($hookResult['plugin'])) { //验证插件返回合法性
                    $dashboardWidgetPlugins[$hookResult['plugin']] = $hookResult;
                    if (!isset($dashboardWidgets[$hookResult['plugin']])) {
                        $dashboardWidgets[$hookResult['plugin']] = ['name' => $hookResult['plugin'], 'is_system' => 0];
                    }
                }
            }
        }


        $smtpSetting = cmf_get_option('smtp_setting');

        //Android充值
        $where['addtime'] = ['between', [strtotime(time()), strtotime(time()) + 86400]];
        db('user_charge_log')->where($where)->sum('money');
        //IOS充值

        $admin_index_list = admin_index_list();
        $this->assign('admin_index_list', $admin_index_list);
        $this->assign('dashboard_widgets', $dashboardWidgets);
        $this->assign('dashboard_widget_plugins', $dashboardWidgetPlugins);
        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);
        $this->assign('http', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']);

        // 代理充值页面链接
        $this->assign('top_up_url', config('base_config.top_up_url'));

        return $this->fetch();
    }

    public function dashboardWidget()
    {
        $dashboardWidgets = [];
        $widgets = $this->request->param('widgets/a');
        if (!empty($widgets)) {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 1]);
                } else {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 0]);
                }
            }
        }

        cmf_set_option('admin_dashboard_widgets', $dashboardWidgets, true);

        $this->success(lang('Update_succeeded'));

    }

}
