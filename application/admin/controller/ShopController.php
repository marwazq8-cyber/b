<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ShopController extends AdminBaseController
{
	// 商城
	public function index(){
		$where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        
        if (empty($p) and !$this->request->param('type') and !$this->request->param('status')) {
            session("Shop_index", null);
            $data['type'] = 1;
            $data['status'] = -1;
            session("Shop_index", $data);

        } else if (empty($p)) {

            $data['type'] = $this->request->param('type');
            $data['status'] = $this->request->param('status');
            session("Shop_index", $data);
        }

        $type = session("Shop_index.type");
        $status = session("Shop_index.status");
       
        $where = 'id > 0';
        $where .=$type  ? " and type=".$type : '';
        $where .=$status != -1 ? " and status=".$status : '';

        $list = Db::name('shop')->where($where)->order("sort DESC")->paginate(10, false, ['query' => request()->param()]);
        // 获取分页显示
        $page = $list->render();
        $name = $list->toArray();

        $this->assign("page", $page);
        $this->assign("list", $name['data']);
        $this->assign("data", session("Shop_index"));
        return $this->fetch();
	}
    // 添加商城
    public function add(){
        $id = input('param.id');
        if ($id) {
            $name = Db::name("shop")->where("id=$id")->find();
        }else{
            $name['img']='';
            $name['svga']='';
            $name['type']=1;
            $name['status'] = 1;
            $name['is_vip'] = 0;
        }
        $this->assign('data', $name);
        return $this->fetch();
    }
    // 提交添加商城
    public function add_post(){
        $param = $this->request->param();
 
        $id = $param['id'];
        $data = $param['post'];

        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("shop")->where("id=$id")->update($data);
        } else {
            $result = Db::name("shop")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('shop/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    // 删除商城
    public function del(){
        
        $id = input('param.id');

        $result = Db::name('shop') -> delete($id);

        return $result ? '1' : '0';
        exit;
    }
    // 商城价格
    public function price(){
        $where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        
        if (empty($p) and !$this->request->param('type') and !$this->request->param('status') and !$this->request->param('shop_id')) {
            session("Shop_index", null);
            $data['type'] = 1;
            $data['status'] = -1;
            session("Shop_index", $data);

        } else if (empty($p)) {

            $data['type'] = $this->request->param('type');
            $data['status'] = $this->request->param('status');
            $data['shop_id'] = $this->request->param('shop_id');
            session("Shop_index", $data);
        }

        $type = session("Shop_index.type");
        $status = session("Shop_index.status");
        $shop_id = session("Shop_index.shop_id");
       
        $where = 's.id > 0';
        $where .=$type  ? " and s.type=".$type : '';
        $where .=$status != -1 ? " and p.status=".$status : '';
        $where .=$shop_id ? " and p.shop_id=".$shop_id : '';

        $list = Db::name('shop_price')->alias("p")
            ->where($where)
            ->field("p.*,s.name,s.type")
            ->join("shop s", "s.id=p.shop_id")
            ->order("p.sort DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $list->render();
        $name = $list->toArray();

        $this->assign("page", $page);
        $this->assign("list", $name['data']);
        $this->assign("data", session("Shop_index"));
        return $this->fetch();
    }
    // 添加商城价格
    public function add_price(){
        $id = input('param.id');
        $type = input('param.type') ? input('param.type') : 1;
        $shop = Db::name("shop")->where("type=".$type." and status=1")->order("sort desc")->select();
        if ($id) {
            $name = Db::name("shop_price")->where("id=$id")->find();
        }else{

            $name['shop_id']='';
            $name['status'] = 1;
        }
        $this->assign('shop', $shop);
        $this->assign('data', $name);
        return $this->fetch();
    }
    // 提交添加商城
    public function add_price_post(){
        $param = $this->request->param();
 
        $id = $param['id'];
        $data = $param['post'];

        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("shop_price")->where("id=$id")->update($data);
        } else {
            $result = Db::name("shop_price")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('shop/price'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    // 删除商城
    public function del_price(){
        
        $id = input('param.id');

        $result = Db::name('shop_price') -> delete($id);

        return $result ? '1' : '0';
        exit;
    }
    // 商城购买记录
    public function buy_log(){
        $where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        
        if (empty($p) and !$this->request->param('type') and !$this->request->param('uid') and !$this->request->param('status') and !$this->request->param('shop_id') and !$this->request->param('is_use') and !$this->request->param('addtime') and !$this->request->param('endtime')) {
            session("Shop_index", null);
            $data['type'] = 1;
            $data['status'] = -1;

             $data['is_use'] = -1;
            session("Shop_index", $data);

        } else if (empty($p)) {

            $data['type'] = $this->request->param('type');
            $data['status'] = $this->request->param('status');
            $data['shop_id'] = $this->request->param('shop_id');
            $data['uid'] = $this->request->param('uid');
            $data['is_use'] = $this->request->param('is_use');
            $data['addtime'] = $this->request->param('addtime');
            $data['endtime'] = $this->request->param('endtime');
            session("Shop_index", $data);
        }

        $type = session("Shop_index.type");
        $status = session("Shop_index.status");
        $shop_id = session("Shop_index.shop_id");
        $uid = session("Shop_index.uid");
        $is_use = session("Shop_index.is_use");
        $addtime = session("Shop_index.addtime");
        $endtime = session("Shop_index.endtime");
       
        $where = 's.id > 0';
        $where .=$type  ? " and s.type=".$type : '';
        $where .=$status != -1 ? " and p.status=".$status : '';
        $where .=$shop_id ? " and p.shop_id=".$shop_id : '';
        $where .=$uid ? " and p.uid=".$uid : '';
        $where .=$is_use  != -1 ? " and p.is_use=".$is_use : '';
        $where .=$addtime ? " and p.addtime >=".strtotime($addtime) : '';
        $where .=$endtime ? " and p.addtime <=".strtotime($endtime)  : '';

        $list = Db::name('shop_user')->alias("p")
            ->where($where)
            ->field("p.*,s.name,u.user_nickname")
            ->join("shop s", "s.id=p.shop_id")
            ->join("user u", "u.id=p.uid")
            ->order("p.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $list->render();
        $name = $list->toArray();

        $this->assign("page", $page);
        $this->assign("list", $name['data']);
        $this->assign("data", session("Shop_index"));
        return $this->fetch();
    }
    // 结束用户购买的商品
    public function del_buy_log(){

        $id = input('param.id');

        $data=array('status'=>2);

        $result = Db::name('shop_user')->where("id=".$id) -> update($data);

        return $result ? '1' : '0';
        exit;
    }

}
?>