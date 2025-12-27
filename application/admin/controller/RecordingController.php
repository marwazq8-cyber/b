<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class RecordingController extends AdminBaseController
{
    /**
     * 录音列表
     */
    public function index()
    {
        if (!input('request.page')) {
            $data['gift_id']=0;
            $data['status']='-1';
            session('Recording', $data);
        }
        if (input('request.uid') || input('request.status') >= '0') {
            $data['uid']=input('request.uid') ? input('request.uid') :0;
            $data['status']=input('request.status') >= '0' ? input('request.status') : '-1';

            session('Recording', $data);
        }
       
        $uid=session('Recording.uid') >0 ? session('Recording.uid') :'';
        $status=session('Recording.status') >= '0' ? session('Recording.status') :'-1';

        $where="v.id >0";
        $where.= $uid ? " and v.uid=". $uid:'';
        $where.= $status != '-1' ? " and v.status=". $status:'';
      
        $list = Db::name('voice_bank')->alias("v")
                    ->join('user u', 'u.id = v.uid')
                    ->field("v.*,u.user_nickname")
                    ->where($where)
                    ->order("v.addtime desc")
                    ->paginate(10, false, ['query' => request()->param()]);
       
        $data = $list->toArray();

        $page = $list->render();

        $this->assign('list', $data['data']);
        $this->assign('page', $page);

        $this->assign('request', session('Recording'));
        return $this->fetch();
    }

    /**
     * 修改和删除录音
     */
    public function update_index()
    {
        $id = input('param.id');
        $status = input('param.status');
        
        $list = '';
        if(!$id){
            echo json_encode(0);exit;
        }
        if ($status != 2) {
            $list = Db::name("voice_bank")->where("id=$id")->update(array('status'=>$status));
        }else{
            $list = Db::name("voice_bank")->where("id=$id")->delete();
        }
        $data =$list ? 1 : 0;
         echo json_encode($data);exit;
    }

  

}
