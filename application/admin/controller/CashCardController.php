<?php
namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class CashCardController extends AdminBaseController
{
    // 银行名称
    public function index()
    {
      
        $list =  db('cash_card_name')->order("sort DESC")->select();
      
        $this->assign('list', $list);
        // 渲染模板输出
        return $this->fetch();
    }
    // 显示添加的银行名称
    public function add()
    {
         $id = input('param.id');
        if($id){
            $list =  db('cash_card_name')->where("id=".$id)->find();
        }else{
            $list['status'] = 1;
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    //添加银行名称
    public function add_post()
    {

        $param = $this->request->param();
        $id=$param['id'];
        $post=$param['post'];
        if($id){
            $result = db("cash_card_name")->where("id=".$id)->update($post);
        }else{
            $result = db("cash_card_name")->insert($post);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'),url("cash_card/index"));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除银行名称
    public function del()
    {
        $id = input('param.id');
        $status=db('cash_card_name')->delete($id);
        echo $status ? 1 : 0 ;exit;
    }

}