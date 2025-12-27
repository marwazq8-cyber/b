<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-08-31
     * Time: 14:58
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use think\Request;
    use app\admin\model\PlayGameModel;

    class ChatRecordController extends AdminBaseController
    {
        //聊天记录
        public function index(){
            /**搜索条件**/
            $p = $this->request->param('page');
            if (empty($p) and !$this->request->param('uid') and  !$this->request->param('receive_uid') and !$this->request->param('keyword') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
                session("chat_record", null);
                $data['start_time'] = '';
                $data['uid'] = '';
                $data['receive_uid'] = '';
                $data['keyword'] = '';
                $data['end_time'] = '';
                session("chat_record", $data);
            } else if (empty($p)) {

                $data['uid'] = $this->request->param('uid')  ? $this->request->param('uid') :'';
                $data['receive_uid'] = $this->request->param('receive_uid') ? $this->request->param('receive_uid') :'';
                $data['keyword'] = $this->request->param('keyword') ?$this->request->param('keyword') :'';
                $data['start_time'] = $this->request->param('start_time') ?$this->request->param('start_time') :'';
                $data['end_time'] = $this->request->param('end_time') ?$this->request->param('end_time') :'';
                session("chat_record", $data);
            }

            $uid = session("chat_record.uid");

            $receive_uid = session("chat_record.receive_uid");

            $keyword = session("chat_record.keyword");
            $start_time = session("chat_record.start_time");
            $end_time = session("chat_record.end_time");

            $where= 'a.id >0';

            if ($uid) {
                $where .= " and a.uid=".$uid;
            }
            if ($receive_uid) {
                $where .= " and a.receive_uid=".$receive_uid;
            }
            if ($keyword) {
                $where .= " and a.information like '%".$keyword."%'";
            }
            if ($start_time) {
                $where .= " and a.create_time >=".strtotime($start_time);
            }
            if ($end_time) {
                $where .= " and a.create_time <=".strtotime($end_time);
            }

            $users = Db::name('chat_record')->alias("a")
                ->where($where)
                ->field("u.user_nickname uname,e.user_nickname as tname,a.*")
                ->join("user e", "e.id=a.receive_uid")
                ->join("user u", "u.id=a.uid")
                ->order("a.create_time DESC")
                ->paginate(20, false, ['query' => request()->param()]);

            // 获取分页显示
            $page = $users->render();
            $name = $users->toArray();

            $this->assign("page", $page);
            $this->assign("list", $name['data']);
            $this->assign("request", session("chat_record"));
            return $this->fetch();
        }
    }
