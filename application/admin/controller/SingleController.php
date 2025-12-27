<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class SingleController extends AdminBaseController
{
    //获取用户约单记录表
    public function single_user_log(){
       
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('status') and !$this->request->param('touid') and !$this->request->param('end_time') and !$this->request->param('start_time') and !$this->request->param('game_id') and !$this->request->param('genre_id') and !$this->request->param('single_user_id')) {
            session("single_user_log", null);
            $data['status'] = '-1';
            $data['game_id'] = '0';
            $data['genre_id'] = '0';
            $data['single_user_id'] = '0';
            session("single_user_log", $data);

        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['end_time'] = $this->request->param('end_time');
            $data['start_time'] = $this->request->param('start_time');
            $data['touid'] = $this->request->param('touid');
            $data['status'] = $this->request->param('status');
            $data['game_id'] = $this->request->param('game_id');
            $data['genre_id'] = $this->request->param('genre_id');
            $data['single_user_id'] = $this->request->param('single_user_id');

            session("single_user_log", $data);
        }

        $uid = session("single_user_log.uid");
        $end_time = session("single_user_log.end_time");
        $start_time = session("single_user_log.start_time");
        $touid = session("single_user_log.touid");
        $status = session("single_user_log.status");
        $game_id = session("single_user_log.game_id");
        $genre_id = session("single_user_log.genre_id");
        $single_user_id = session("single_user_log.single_user_id");
        //dump($uid);
        $where = "l.id > 0";
        $where.= $uid ? " and l.uid=".$uid :'';
        $where.= $touid ? " and l.touid=".$touid :'';
        $where.= $status >=0 ? " and l.status=".$status :'';
        $where.= $end_time ? " and l.addtime <=". strtotime($end_time) :'';
        $where.= $start_time ? " and l.addtime >=".strtotime($start_time) :'';
        $where.= $game_id ? " and l.game_id=".$game_id :'';
        $where.= $genre_id ? " and l.genre_id=".$genre_id :'';
        $where.= $single_user_id ? " and l.single_user_id=".$single_user_id :'';

      
        $list = Db::name('about_single_user_log')->alias("l")
            ->join("about_single_user a", "a.id=l.single_user_id")
            ->join("about_single_game g", "g.id=l.game_id")
            ->join("about_single_genre s", "s.id=l.genre_id")
            ->join("about_single_unit t", "t.id=l.unit")
            ->join("user u", "u.id=l.uid")
            ->join("user e", "e.id=l.touid")
            ->field("l.*,g.name as name,s.name as sname,t.name as tname,u.user_nickname,e.user_nickname as ename")
            ->where("1=1")
            ->where($where)
            ->order("l.addtime DESC")
            ->paginate(20, false, ['query' => request()->param()]);
           
        $lists = $list->toArray();
             // 获取分页显示
        $page = $list->render();
     
         $game = Db::name("about_single_game")->where("status=1")->order("sort desc")->select(); 
        $genre = Db::name("about_single_genre")->where("status=1")->order("sort desc")->select(); 

        $config = load_cache('config');
        //虚拟币单位
        $this->assign('list',$lists['data']);
        $this->assign('currency_name',$config['currency_name']);
        $this->assign('request', session("single_user_log"));
        $this->assign('page', $page);
        $this->assign('game', $game);
        $this->assign('genre', $genre);
        return $this -> fetch();
    }


    // 用户约单审核列表
    public function single_audit(){
        $where = 'a.id > 0';
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('uid') and !$this->request->param('status') and !$this->request->param('is_open') and !$this->request->param('end_time') and !$this->request->param('start_time') and !$this->request->param('game_id') and !$this->request->param('genre_id')) {
            session("single_audit", null);
            $data['status'] = '-1';
            $data['is_open'] = '-1';
            $data['game_id'] = '0';
            $data['genre_id'] = '0';
            session("single_audit", $data);

        } else if (empty($p)) {
            $data['uid'] = $this->request->param('uid');
            $data['end_time'] = $this->request->param('end_time');
            $data['start_time'] = $this->request->param('start_time');
            $data['is_open'] = $this->request->param('is_open');
            $data['status'] = $this->request->param('status');
            $data['game_id'] = $this->request->param('game_id');
            $data['genre_id'] = $this->request->param('genre_id');

            session("single_audit", $data);
        }

        $uid = session("single_audit.uid");
        $end_time = session("single_audit.end_time");
        $start_time = session("single_audit.start_time");
        $is_open = session("single_audit.is_open");
        $status = session("single_audit.status");
        $game_id = session("single_audit.game_id");
        $genre_id = session("single_audit.genre_id");

        $where.= $uid ? " and a.uid=".$uid :'';
        $where.= $is_open >=0 ? " and a.is_open=".$is_open :'';
        $where.= $status >=0 ? " and a.status=".$status :'';
        $where.= $end_time ? " and a.addtime <=". strtotime($end_time) :'';
        $where.= $start_time ? " and a.addtime >=".strtotime($start_time) :'';
        $where.= $game_id ? " and a.game_id=".$game_id :'';
        $where.= $genre_id ? " and g.genre_id=".$genre_id :'';

        $list = Db::name('about_single_user')->alias("a")
            ->join("about_single_game g", "g.id=a.game_id")
            ->join("about_single_genre s", "s.id=g.genre_id")
            ->join("about_single_unit t", "t.id=a.unit")
            ->join("user u", "u.id=a.uid")
            ->field("a.*,g.name as name,s.name as sname,t.name as tname,u.user_nickname")
            ->where($where)
            ->order("a.addtime DESC")
           ->paginate(20, false, ['query' => request()->param()]);
        
        $lists = $list->toArray();
             // 获取分页显示
        $page = $list->render();
        $game = Db::name("about_single_game")->where("status=1")->order("sort desc")->select(); 
        $genre = Db::name("about_single_genre")->where("status=1")->order("sort desc")->select(); 

        $config = load_cache('config');
        //虚拟币单位
        $this->assign('list',$lists['data']);
        $this->assign('currency_name',$config['currency_name']);
        $this->assign('request', session("single_audit"));
        $this->assign('page', $page);
        $this->assign('game', $game);
        $this->assign('genre', $genre);
        return $this -> fetch();
    }
    /*
    *  查看图片
    */
    public function about_single_img(){
        $id = input('param.id');
        $game = Db::name("about_single_img")->where("game_id=".$id)->order("sort desc")->select(); 
        $img=[];
        foreach ($game as $key => $v) {
            $img[$key]['alt'] = $v['img'];
            $img[$key]['pid'] = $v['id'];
            $img[$key]['src'] = $v['img'];
            $img[$key]['thumb'] = $v['img'];
        }
        $list['title']="";
        $list['id']= $id;
        $list['start']= 0;
        $list['data']= $img;

        echo json_encode($list);exit;
    }
    /**
     * 用户约单审核列表添加
     */
    public function add_single_audit()
    {
        $game = Db::name("about_single_game")->where("status=1")->order("sort desc")->select(); 
        $unit = Db::name("about_single_unit")->order("sort desc")->select(); 
        $id = input('param.id');
        if ($id) {
            $name = Db::name("about_single_user")->where("id=$id")->find(); 
        }else{
            $name['status']='0';
            $name['game_id']='1';
            $name['is_open']='0';
            $name['unit']='0';
        }

        $this->assign('list', $name);
        $this->assign('unit_list', $unit);
        $this->assign('count', count($game));
        $this->assign('game', $game);
        return $this->fetch();
    }
  
     public function add_single_audit_post()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if ($id) {
            $result = Db::name("about_single_user")->where("id=$id")->update($data);
        } else {
            $result = Db::name("about_single_user")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('single/single_audit'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    //删除用户约单审核
    public function del_single_audit()
    {
        $param = request()->param();
        $result = Db::name("about_single_user")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }



	// 获取约单导航栏列表
	public function index(){
        $list = db('about_single_type')->order("sort DESC")->select();
        $this->assign('list',$list);
        return $this -> fetch();
    }
    /**
     * 约单导航栏添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("about_single_type")->where("id=$id")->find(); 
        }else{
            $name['status']='1';
        }
        $this->assign('list', $name);
        return $this->fetch();
    }
     public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if ($id) {
            $result = Db::name("about_single_type")->where("id=$id")->update($data);
        } else {
            $result = Db::name("about_single_type")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('single/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("about_single_type")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }
    //约单类型
    public function genre(){
    	$list = Db::name('about_single_genre')->alias("g")
    	 	->join("about_single_type t", "t.id=g.type")
            ->field("g.*,t.name as tname")
            ->order("g.sort DESC")
            ->select();

        $this->assign('list',$list);
        return $this -> fetch();
    }

     /**
     * 约单类型添加
     */
    public function add_genre()
    {
        $id = input('param.id');
        $genre = Db::name('about_single_type')->where("status=1")->order("sort desc")->select();

        if ($id) {
            $name = Db::name("about_single_genre")->where("id=$id")->find(); 
        }else{
            $name['status']='1';
            $name['img']='';
             $name['type']='';
        }

        $this->assign('list', $name);
        $this->assign('genre', $genre);
        return $this->fetch();
    }
     public function add_genre_post()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if ($id) {
            $result = Db::name("about_single_genre")->where("id=$id")->update($data);
        } else {
            $result = Db::name("about_single_genre")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('single/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del_genre()
    {
        $param = request()->param();
        $result = Db::name("about_single_genre")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }
    //约单单位列表
    public function unit(){
        $list = Db::name('about_single_unit')->alias("g")
            ->join("about_single_game a", "a.id=g.single_genre_id","left")
            ->field("g.*,a.name as aname")
            ->order("g.sort DESC")
            ->select();

        $this->assign('list',$list);
         return $this->fetch();
    }
     /**约单单位列表添加 */
    public function add_unit()
    {
        $id = input('param.id');
        $genre = Db::name('about_single_game')->where("status=1 ")->order("sort desc")->select();

        if ($id) {
            $name = Db::name("about_single_unit")->where("id=$id")->find(); 
        }else{
            $name['status']='1';
            $name['single_genre_id']='0';
            
        }

        $this->assign('list', $name);
        $this->assign('genre', $genre);
        return $this->fetch();
    }
     public function add_unit_post()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if ($id) {
            $result = Db::name("about_single_unit")->where("id=$id")->update($data);
        } else {
            $result = Db::name("about_single_unit")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('single/unit'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
     //删除
    public function del_unit()
    {
        $param = request()->param();
        $result = Db::name("about_single_unit")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    // 约单功能 
    public function game(){
        $list = Db::name('about_single_game')->alias("g")
            ->join("about_single_genre t", "t.id=g.genre_id")
            ->field("g.*,t.name as tname")
            ->order("g.sort DESC")
            ->select();

        $this->assign('list',$list);
        return $this -> fetch();
    }

       /**约单功能 添加 */
    public function add_game()
    {
        $id = input('param.id');

        $genre = Db::name('about_single_genre')->where("status=1 ")->order("sort desc")->select();

        if ($id) {
            $name = Db::name("about_single_game")->where("id=$id")->find(); 
        }else{
            $name['status']='1';
            $name['genre_id']='0';
            
        }

        $this->assign('list', $name);
        $this->assign('genre', $genre);

        return $this->fetch();
    }
     public function add_game_post()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if ($id) {
            $result = Db::name("about_single_game")->where("id=$id")->update($data);
        } else {
            $result = Db::name("about_single_game")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('single/game'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
     //删除
    public function del_game()
    {
        $param = request()->param();
        $result = Db::name("about_single_game")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //约单设置申请
    public function setting(){
        $uid = request()->param('uid');
        $game_id = request()->param('game_id');
        $genre_id = request()->param('genre_id');
        $end_time = $this->request->param('end_time');
        $start_time = $this->request->param('start_time');
        $where = [];
        $data = [
            'uid'=> '',
            'game_id'=> 0,
            'genre_id'=> 0,
            'end_time'=> '',
            'start_time'=> '',
        ];
        session("single_user_log",$data);
        if($uid){
            $data['uid'] = $uid;
            session("single_user_log",$data);
            $where['su.uid'] = $uid;
        }

        if($game_id){
            $data['game_id'] = $game_id;
            session("single_user_log",$data);
            $where['su.game_id'] = $game_id;
        }

        if($genre_id){
            $data['genre_id'] = $genre_id;
            session("single_user_log",$data);
            $where['su.genre_id'] = $genre_id;
        }

        if($end_time && $start_time){
            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;
            session("single_user_log",$data);
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);
            $where['su.addtime'] = ['between',[$start_time,$end_time]];
        }

        $field = 'su.*,u.user_nickname,ge.name as genre_name,ga.name as game_name';
        $list = db('about_single_user')
            ->alias('su')
            ->join('user u','u.id=su.uid')
            ->join('about_single_genre ge','ge.id=su.genre_id')
            ->join('about_single_game ga','ga.id=su.game_id')
            ->field($field)
            ->where($where)
            ->order('addtime desc')
            ->paginate(20, false, ['query' => request()->param()]);
        $game = Db::name("about_single_game")->where("status=1")->order("sort desc")->select();
        $genre = Db::name("about_single_genre")->where("status=1")->order("sort desc")->select();

        $config = load_cache('config');
        //虚拟币单位

        $this->assign('currency_name',$config['currency_name']);
        $this->assign('request', session("single_user_log"));

        $this->assign('game', $game);
        $this->assign('genre', $genre);

        $page = $list->render();
        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    public function setting_post(){
        $id = request()->param('id');
        $type = request()->param('type');
        if($type==1){
            //通过
            $result = db('about_single_user')->where(['id'=>$id])->update(['status'=>1,'endtime'=>NOW_TIME]);
        }else{
            //拒绝
            $result = db('about_single_user')->where(['id'=>$id])->update(['status'=>2,'endtime'=>NOW_TIME]);
        }

        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('single/setting'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    public function setting_del(){
        $id = request()->param('id');
        $result = Db::name("about_single_user")->where("id=" . $id)->delete();
        return $result ? '1' : '0';
        exit;
    }

}
?>