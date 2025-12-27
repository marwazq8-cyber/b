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

use think\Db;
use cmf\controller\AdminBaseController;
use app\admin\model\SlideItemModel;
use app\portal\model\PortalPostModel;
class SlideItemController extends AdminBaseController
{
    /**
     * 幻灯片页面列表
     * @adminMenu(
     *     'name'   => '幻灯片页面列表',
     *     'parent' => 'admin/Slide/index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $id      = $this->request->param('slide_id');
        $slideId = !empty($id) ? $id : 1;
        $result  = Db::name('slideItem')->where(['slide_id' => $slideId])->select()->toArray();

        $this->assign('slide_id', $id);
        $this->assign('result', $result);
        return $this->fetch();
    }

    /**
     * 幻灯片页面添加
     * @adminMenu(
     *     'name'   => '幻灯片页面添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $slideId = $this->request->param('slide_id');
        $this->assign('slide_id', $slideId);
        return $this->fetch();
    }

    /**
     * 幻灯片页面添加提交
     * @adminMenu(
     *     'name'   => '幻灯片页面添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        $data = $this->request->param();
        if($data['post']['type'] ==1){    //邀请链接mapi/public/index.php
            $invite_friends ='http://' . $_SERVER['HTTP_HOST'] . '/api/invite_api/index/uid/'; //邀请好友 用户uid
            $data['post']['url']=$invite_friends;
            $data['post']['is_auth_info']=1;
        }elseif($data['post']['type'] ==2){
            $article['post_title']=$data['post_title'];
            $article['post_content']=$data['post_content'];
            $article['post_keywords']=$data['post_keywords'];
            $article['post_status']=1;
            $portal_category  = Db::name('portal_category')->where("id=16")->find();
            if(!$portal_category){
                 $this->error(lang('Rotation_chart_article_classification_parameter_error'));
            }
            $article['categories']=$portal_category['id'];//轮播图分类id
            
            if (empty($article['post_title'])) {
                $this->error(lang('Please_enter_title'));
            }
            if (empty($article['post_content'])) {
                $this->error(lang('Please_enter_content'));
            }

            $portalPostModel = new PortalPostModel();

            $portalPostModel->adminAddArticle($article, $article['categories']);

            $hookParam          = [
                'is_add'  => true,
                'article' => $article
            ];
            hook('portal_admin_after_save_article', $hookParam);

            $invite_friends ='http://'.$_SERVER['HTTP_HOST'].'/api/novice_guide_api/content/id/'.$portalPostModel->id; //文章列表 id
            $data['post']['url']=$invite_friends;
             $data['post']['article_id']=$portalPostModel->id;
        }

        Db::name('slideItem')->insert($data['post']);
        $this->success(lang('ADD_SUCCESS'), url("slideItem/index", ['slide_id' => $data['post']['slide_id']]));
    }

    /**
     * 幻灯片页面编辑
     * @adminMenu(
     *     'name'   => '幻灯片页面编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id     = $this->request->param('id');
        $result = Db::name('slideItem')->where(['id' => $id])->find();
        if($result['article_id']){
            $article=Db::name('portal_post')->where(['id' => $result['article_id']])->find();
            $article['post_content']=htmlspecialchars_decode($article['post_content']);
            $this->assign('article', $article);
        }
        $this->assign('result', $result);
        $this->assign('slide_id', $result['slide_id']);
        return $this->fetch();
    }

    /**
     * 幻灯片页面编辑
     * @adminMenu(
     *     'name'   => '幻灯片页面编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面编辑提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $data = $this->request->param();
        
        $data['post']['image'] =cmf_asset_relative_url($data['post']['image']);

        $slideItem  = Db::name('slideItem')->where("id=".$data['post']['id'])->find();
 
         if($data['post']['type'] ==1){    //邀请链接
            if($slideItem['type'] !=$data['post']['type']){
                $invite_friends ='http://' . $_SERVER['HTTP_HOST'] . '/api/invite_api/index'; //邀请好友 用户uid
                $data['post']['url']=$invite_friends;
                $data['post']['is_auth_info']=1;
            }
           
        }elseif($data['post']['type'] ==2){
            $article['post_title']=$data['post_title'];
            $article['post_content']=$data['post_content'];
            $article['post_keywords']=$data['post_keywords'];
            $article['id']=$data['article_id'];
            $article['post_status']=1;
            $portal_category  = Db::name('portal_category')->where("id=16")->find();
            if(!$portal_category){
                 $this->error(lang('Rotation_chart_article_classification_parameter_error'));
            }
            $article['categories']=strval($portal_category['id']);//轮播图分类id
            
            if (empty($article['post_title'])) {
                $this->error(lang('Please_enter_title'));
            }
            if (empty($article['post_content'])) {
                $this->error(lang('Please_enter_content'));
            }

            $portalPostModel = new PortalPostModel();

            if($slideItem['type'] !=$data['post']['type']){   //添加
                
                 $portalPostModel->adminAddArticle($article, $article['categories']);

                $invite_friends ='http://'.$_SERVER['HTTP_HOST'].'/api/novice_guide_api/content/id/'.$portalPostModel->id; //文章列表 id
                $data['post']['url']=$invite_friends;
                $data['post']['article_id']=$portalPostModel->id;
              
             }else{            //修改

                $portalPostModel->adminEditArticle($article, $article['categories']);
                $invite_friends = 'http://'.$_SERVER['HTTP_HOST'].'/api/novice_guide_api/content/id/'.$article['id']; //文章列表 id
              //  var_dump($article['id']);exit;
                $data['post']['url']=$invite_friends;
                $data['post']['article_id']=$article['id'];
                
             }

            $hookParam          = [
                    'is_add'  => true,
                    'article' => $article
                ];
            hook('portal_admin_after_save_article', $hookParam);

        }
      
        Db::name('slideItem')->update($data['post']);

        $this->success(lang('EDIT_SUCCESS'), url("SlideItem/index", ['slide_id' => $data['post']['slide_id']]));

    }

    /**
     * 幻灯片页面删除
     * @adminMenu(
     *     'name'   => '幻灯片页面删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id     = $this->request->param('id', 0, 'intval');

        $slideItem = Db::name('slideItem')->find($id);

        $result = Db::name('slideItem')->delete($id);
        if ($result) {
            //删除图片。
//            if (file_exists("./upload/".$slideItem['image'])){
//                @unlink("./upload/".$slideItem['image']);
//            }
            $this->success(lang('DELETE_SUCCESS'), url("SlideItem/index",["slide_id"=>$slideItem['slide_id']]));
        } else {
            $this->error(lang('DELETE_FAILED'));
        }

    }

    /**
     * 幻灯片页面隐藏
     * @adminMenu(
     *     'name'   => '幻灯片页面隐藏',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面隐藏',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $rst = Db::name('slideItem')->where(['id' => $id])->update(['status' => 0]);
            if ($rst) {
                $this->success(lang('Operation_successful'));
            } else {
                $this->error(lang('operation_failed'));
            }
        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    /**
     * 幻灯片页面显示
     * @adminMenu(
     *     'name'   => '幻灯片页面显示',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面显示',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $result = Db::name('slideItem')->where(['id' => $id])->update(['status' => 1]);
            if ($result) {
                $this->success(lang('Enabled_successfully'));
            } else {
                $this->error(lang('Enable_failed'));
            }
        } else {
            $this->error(lang('Data_transfer_in_failed'));
        }
    }

    /**
     * 幻灯片页面排序
     * @adminMenu(
     *     'name'   => '幻灯片页面排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面排序',
     *     'param'  => ''
     * )
     */
    public function listOrder()
    {
        $slideItemModel = new  SlideItemModel();
        parent::listOrders($slideItemModel);
        $this->success(lang('Sorting_succeeded'));
    }
}