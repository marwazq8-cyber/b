<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Request;
use app\admin\model\AuthModel;

class ClientLogController extends AdminBaseController
{
    //
    public function index()
    {
        $where = [];
        $request = input('request.');

        if (input('request.end_time') > 0 && input('request.start_time')) {
            $where['c.addtime'] = ['between', [strtotime(input('request.start_time')), strtotime(input('request.end_time'))]];
        }

        $data = db('client_log')
            ->alias('c')
            ->join('user u','u.id=c.uid')
            ->where($where)
            ->order('c.addtime')
            ->field('c.*,u.user_nickname')
            ->paginate(10, false, ['query' => request()->param()]);
        //$list = [];
        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign('request', $request);
        return $this->fetch();
    }

    public function get_info(){
        $url = input('url');
        $json = file_get_contents($url);
        echo json_encode($json);
    }
}