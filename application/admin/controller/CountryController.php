<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/16 0016
 * Time: 下午 17:55
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class CountryController extends AdminBaseController
{
    //国家列表
    public function index()
    {

        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and $this->request->param('status') < 1 and !$this->request->param('name')) {
            session("Country_index", null);
            $data['status'] = '0';
            session("Country_index", $data);
        } else if (empty($p)) {
            $data['status'] = $this->request->param('status') ? $this->request->param('status') : '0';
            $data['name'] = $this->request->param('name') ? $this->request->param('name') : '';
            session("Country_index", $data);
        }

        $status = intval(session("Country_index.status"));
        $name = session("Country_index.name");

        $where = 'id > 0';
        $where .= $status ? " and status=" . $status : '';
        $where .= $name ? " and name like '%" . $name . "%'" : '';

        $users = Db::name('country')->where($where)
            ->field("*")
            ->order("sort DESC")
            ->paginate(20, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();

        $this->assign("page", $page);
        $this->assign("data", $name['data']);
        $this->assign("request", session("Country_index"));
        return $this->fetch();
    }

    /**
     * 编辑或添加
     */
    public function country_add()
    {
        $id = input('param.id');
        if ($id) {
            $country = Db::name("country")->where("id=$id")->find();
        } else {
            $country['status'] = 1;
            $country['num_code'] = 0;
            $country['img'] = '';
        }
        $file = file_get_contents(DOCUMENT_ROOT . "/countries.json");

        $this->assign('list', json_decode($file, true));
        $this->assign('data', $country);
        return $this->fetch();
    }

    /**
     * 保存
     */
    public function addCountryPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];

        $file = file_get_contents(DOCUMENT_ROOT . "/countries.json");
        $countries = json_decode($file, true);
        foreach ($countries as $v) {
            if (intval($data['num_code']) == $v['num_code']) {
                $data['alpha_2_code'] = $v['alpha_2_code'];
                $data['en_short_name'] = $v['en_short_name'];
            }
        }
        if (intval($data['num_code']) == 0) {
            $this->error(lang('EDIT_FAILED'));
        }
        if ($id) {
            $result = Db::name("country")->where("id=$id")->update($data);
        } else {
            $result = Db::name("country")->insertGetId($data);
        }

        if ($result) {

            cache('country_list', db('country')->where('status', '=', 1)->order('sort desc')->select());

            $this->success(lang('EDIT_SUCCESS'), url('Country/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
}