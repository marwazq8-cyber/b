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
class PictureResourcesController extends AdminBaseController
{

    /**
     * 图片资源库
     */
    public function index()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('grouping') and  !$this->request->param('identifier') and !$this->request->param('title')) {
            session("PictureResources", null);
            $data['grouping'] = '';
            $data['identifier'] = '';
            $data['title'] = '';
            session("PictureResources", $data);
        } else if (empty($p)) {

            $data['grouping'] = $this->request->param('grouping')  ? $this->request->param('grouping') :'';
            $data['identifier'] = $this->request->param('identifier') ? $this->request->param('identifier') :'';
            $data['title'] = $this->request->param('title') ?$this->request->param('title') :'';
            session("PictureResources", $data);
        }

        $grouping = session("PictureResources.grouping");
        $identifier = session("PictureResources.identifier");
        $title = session("PictureResources.title");

        $where= 'id >0';

        if ($grouping) {
            $where .= " and grouping like '%".$grouping."%'";
        }
        if ($identifier) {
            $where .= " and identifier like '%".$identifier."%'";
        }
        if ($title) {
            $where .= " and title like '%".$title."%'";
        }
        $picture_resources = Db::name('picture_resources') ->where($where)->order("sort DESC")->paginate(20, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $picture_resources->render();
        $list = $picture_resources->toArray();

        $this->assign("page", $page);
        $this->assign("list", $list['data']);
        $this->assign("request", session("PictureResources"));
        return $this->fetch();
    }
    /**
     * 编辑
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id){
            $list = Db::name('picture_resources') ->where("id=".$id)->find();
        }else{
            $list = array(
                'img'=>'',
                'identifier'=>'',
            );
        }
        $this->assign('data', $list);
        return $this->fetch();
    }

    /**
     * 编辑提交保存
     */
    public function editPost()
    {
        $data      = $this->request->param();
        $id = $data['id'];
        $post = $data['post'];
        $post['create_time'] = NOW_TIME;
        if ($id){
            $result = Db::name('picture_resources') ->where("id=".$id)->update($post);
        }else{
            $result = Db::name('picture_resources')->insert($post);
        }
        if ($result){
            $this->success(lang('EDIT_SUCCESS'), url("picture_resources/index"));
        }else{
            $this->error(lang('EDIT_FAILED'));
        }
    }


}