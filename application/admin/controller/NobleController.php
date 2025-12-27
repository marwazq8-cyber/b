<?php
    /**
     * Created by PhpStorm.
     * User: Administrator
     * Date: 2019/12/18 0020
     * Time: 上午 16:13
     */
    namespace app\admin\controller;

    use cmf\controller\AdminBaseController;
    use think\Db;
    use app\admin\model\AdminMenuModel;

    class NobleController extends AdminBaseController
    {
        //贵族特权
        public function index()
        {
            $config = load_cache('config');
            $lang = cookie('think_var') ? cookie('think_var') : 'zh-cn';
            $car = Db::name("noble")->order("orderno asc")->paginate(10);

            $list = $car->toArray();
            foreach ($list['data'] as &$v){
                $whitelist_lang = json_decode($v['lang_name'],true);
                if (isset($whitelist_lang[$lang])){
                    $v['name']=$whitelist_lang[$lang];
                }
            }
            $this->assign('data', $list['data']);
            $this->assign('page', $car->render());
            $this->assign('currency_name', $config['currency_name']);
            return $this->fetch();
        }

        public function add()
        {
            $param = $this->request->param();
            $whitelist = [];
            if (isset($param['id'])) {
                $whitelist = Db::name("noble")->find($param['id']);
                $check = $whitelist['privilege_id'];
                $checkcount = count(explode(',',$whitelist['privilege_id']));
                $other = $whitelist['chat_bg_id'].','.$whitelist['chat_bubble_id'].','.$whitelist['avatar_frame_id'].','.$whitelist['home_page_id'].','.$whitelist['medal_id'].','.$whitelist['car_id'];
                $checkcount_other = count(explode(',',$other));
            }else{
                $check = '';
                $checkcount = 0;
                $whitelist['r_id'] = 0;
                $other = '';
                $checkcount_other = 0;
                $whitelist['lang_name'] = '';
            }
            $charge_rule = Db::name('user_charge_rule')->order('coin')->select();
            $privilege = Db::name("noble_privilege")->where(['status'=>1])->select();
            $dress = Db::name("dress_up")->select();
            $chat_bg = [];
            $chat_bubble = [];
            $avatar_frame = [];
            $home_page = [];
            $medal = [];
            $car = [];
            if($dress){
                foreach ($dress as $v){
                    switch ($v['type']){
                        case 1:
                            $medal[] = $v;
                            break;
                        case 2:
                            $home_page[] = $v;
                            break;
                        case 3:
                            $avatar_frame[] = $v;
                            break;
                        case 4:
                            $chat_bubble[] = $v;
                            break;
                        case 5:
                            $chat_bg[] = $v;
                            break;
                        case 7:
                            $car[] = $v;
                            break;
                    }
                }
            }
            /*$chat_bg = Db::name("dress_up")->where(['type'=>5])->select();
            $chat_bubble = Db::name("dress_up")->where(['type'=>4])->select();
            $avatar_frame = Db::name("dress_up")->where(['type'=>3])->select();
            $home_page = Db::name("dress_up")->where(['type'=>2])->select();
            $medal = Db::name("dress_up")->where(['type'=>1])->select();*/

            $database_lang = DATABASE_LANG;
            $lang_name = [];
            if (!$whitelist['lang_name']){
                foreach ($database_lang as $v){
                    $lang_name[]=array(
                        'name'=>$v,
                        'value'=>''
                    );
                }
            }else{
                $whitelist_lang = json_decode($whitelist['lang_name'],true);
                foreach ($database_lang as $v){
                    if (isset($whitelist_lang[$v])){
                        $lang_name[]=array('name'=>$v,'value'=>$whitelist_lang[$v]);
                    }else{
                        $lang_name[]=array('name'=>$v,'value'=>'');
                    }
                }
            }
            $whitelist['lang_name'] = $lang_name;


            $this->assign('data', $whitelist);
            $this->assign('privilege', $privilege);
            $this->assign('checkcount', $checkcount);
            $this->assign('check', $check);
            $this->assign('charge_rule', $charge_rule);
            $this->assign('chat_bg', $chat_bg);
            $this->assign('chat_bubble', $chat_bubble);
            $this->assign('avatar_frame', $avatar_frame);
            $this->assign('home_page', $home_page);
            $this->assign('medal', $medal);
            $this->assign('car', $car);
            $this->assign('other', $other);
            $this->assign('checkcount_other', $checkcount_other);
            return $this->fetch();
        }

        public function addPost()
        {
            $param = $this->request->param();
            $id = $param['id'];
            $data = $param['post'];
            //$data['img'] = $param['post']['img'];
            $lang_name = $param['lang_name'];
            $data['name'] = $lang_name['zh-cn'];
            $data['lang_name'] = json_encode($lang_name);
            $data['addtime'] = time();
            if(!isset($param['privilege_id'])){
                $this->error(lang('Please_select_least_one_privilege'));
            }
            $data['privilege_id'] = '';
            $data['chat_bg_id'] = '';
            $data['chat_bubble_id'] = '';
            $data['avatar_frame_id'] = '';
            $data['home_page_id'] = '';
            $data['medal_id'] = '';
            $data['car_id'] = '';
            if(isset($param['privilege_id'])){
                $data['privilege_id'] = implode(',',$param['privilege_id']);
            }
            if(isset($param['chat_bg_id'])){
                $data['chat_bg_id'] = implode(',',$param['chat_bg_id']);
            }
            if(isset($param['chat_bubble_id'])){
                $data['chat_bubble_id'] = implode(',',$param['chat_bubble_id']);
            }
            if(isset($param['avatar_frame_id'])){
                $data['avatar_frame_id'] = implode(',',$param['avatar_frame_id']);
            }

            if(isset($param['home_page_id'])){
                $data['home_page_id'] = implode(',',$param['home_page_id']);
            }
            if(isset($param['medal_id'])){
                $data['medal_id'] = implode(',',$param['medal_id']);
            }
            if(isset($param['car_id'])){
                $data['car_id'] = implode(',',$param['car_id']);
            }

            //$data['car_status'] = 1;
            if ($id) {
                $result = Db::name("noble")->where("id=$id")->update($data);
            } else {
                $result = Db::name("noble")->insert($data);
            }
            if ($result) {
                $noble_list = Db::name("noble")->where("status=1")->select();
                $dress_up_where = "";
                if($noble_list){
                    // chat_bg_id聊天背景,chat_bubble_id聊天气泡,avatar_frame_id头像框,home_page_id主页特效,medal_id勋章,car_id进场动画
                    foreach ($noble_list as $v){
                        if($v['chat_bg_id']){
                            $dress_up_id_array = explode(",", $v['chat_bg_id']);
                            $dress_up_where = $this->sel_dress_up($dress_up_where,$dress_up_id_array);
                        }
                        if($v['chat_bubble_id']){
                            $dress_up_id_array = explode(",", $v['chat_bubble_id']);
                            $dress_up_where = $this->sel_dress_up($dress_up_where,$dress_up_id_array);
                        }
                        if($v['avatar_frame_id']){
                            $dress_up_id_array = explode(",", $v['avatar_frame_id']);
                            $dress_up_where = $this->sel_dress_up($dress_up_where,$dress_up_id_array);
                        }
                        if($v['home_page_id']){
                            $dress_up_id_array = explode(",", $v['home_page_id']);
                            $dress_up_where = $this->sel_dress_up($dress_up_where,$dress_up_id_array);
                        }
                        if($v['medal_id']){
                            $dress_up_id_array = explode(",", $v['medal_id']);

                            $dress_up_where = $this->sel_dress_up($dress_up_where,$dress_up_id_array);

                        }
                        if($v['car_id']){

                            $dress_up_id_array = explode(",", $v['car_id']);
                            $dress_up_where = $this->sel_dress_up($dress_up_where,$dress_up_id_array);

                        }
                    }

                }
                Db::name("dress_up")->where("is_pay=0")->update(['is_pay'=>1]);

                if($dress_up_where){
                    $dress_up_where = trim($dress_up_where,",");
                    // 重置装饰未禁止购买
                    Db::name("dress_up")->whereIn("id",$dress_up_where)->where("is_pay", 1)->update(['is_pay'=>0]);

                }
                $this->success(lang('EDIT_SUCCESS'), url('noble/index'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }
        public function sel_dress_up($dress_up_where,$dress_up_id_array){
            foreach ($dress_up_id_array as $vv){
                if (!strpos($dress_up_where, ",".$vv.",")) {
                    $dress_up_where .= $dress_up_where ? $vv."," : ",".$vv.",";
                }
            }
            return $dress_up_where;
        }
        public function upd()
        {
            $param = request()->param();
            $data = '';
            foreach ($param['listorders'] as $k => $v) {
                $status = Db::name("noble")->where("id=$k")->update(array('orderno' => $v));
                if ($status) {
                    $data = $status;
                }
            }
            if ($data) {
                $this->success(lang('Sorting_succeeded'));
            } else {
                $this->error(lang('Sorting_error'));
            }
        }

        public function del()
        {
            $param = request()->param();

            $result = Db::name("noble")->where("id=" . $param['id'])->delete();


            return $result ? '1' : '0';
            exit;
        }

        public function privilege()
        {
            $car = Db::name("noble_privilege")->order("orderno asc")->paginate(10);
            $lang = cookie('think_var') ? cookie('think_var') : 'zh-cn';
            $list = $car->toArray();
            foreach ($list['data'] as &$v){
                $whitelist_lang = json_decode($v['lang_name'],true);
                if (isset($whitelist_lang[$lang])){
                    $v['name']=$whitelist_lang[$lang];
                }
            }

            $this->assign('data', $list['data']);
            $this->assign('page', $car->render());
            return $this->fetch();

        }

        public function privilege_add()
        {
            $param = $this->request->param();
            $whitelist = [];
            if (isset($param['id'])) {
                $whitelist = Db::name("noble_privilege")->find($param['id']);
            }else{
                $whitelist['privilege_img'] = null;
                $whitelist['status'] = 1;
                $whitelist['lang_name'] = '';
            }
            $database_lang = DATABASE_LANG;
            $lang_name = [];
            if (!$whitelist['lang_name']){
                foreach ($database_lang as $v){
                    $lang_name[]=array(
                        'name'=>$v,
                        'value'=>''
                    );
                }
            }else{
                $whitelist_lang = json_decode($whitelist['lang_name'],true);
                foreach ($database_lang as $v){
                    if (isset($whitelist_lang[$v])){
                        $lang_name[]=array('name'=>$v,'value'=>$whitelist_lang[$v]);
                    }else{
                        $lang_name[]=array('name'=>$v,'value'=>'');
                    }
                }
            }
            $whitelist['lang_name'] = $lang_name;

            $this->assign('data', $whitelist);
            return $this->fetch();
        }

        public function privilege_addPost()
        {
            $param = $this->request->param();
            // print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$data['img'] = $param['post']['img'];
            $lang_name = $param['lang_name'];
            $data['name'] = $lang_name['zh-cn'];
            $data['lang_name'] = json_encode($lang_name);
            $data['addtime'] = time();
            //$data['car_status'] = 1;
            if ($id) {
                $result = Db::name("noble_privilege")->where("id=$id")->update($data);
            } else {
                $result = Db::name("noble_privilege")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('noble/privilege'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function privilege_upd()
        {
            $param = request()->param();
            $data = '';
            foreach ($param['listorders'] as $k => $v) {
                $status = Db::name("noble_privilege")->where("id=$k")->update(array('orderno' => $v));
                if ($status) {
                    $data = $status;
                }
            }
            if ($data) {
                $this->success(lang('Sorting_succeeded'));
            } else {
                $this->error(lang('Sorting_error'));
            }
        }

        public function privilege_del()
        {
            $param = request()->param();

            $result = Db::name("noble_privilege")->where("id=" . $param['id'])->delete();


            return $result ? '1' : '0';
            exit;
        }

        //手动添加贵族记录
        public function add_noble_log(){
            $res = db('noble_log')
                ->alias('nl')
                ->join('user u','u.id=nl.uid')
                ->join('noble n','nl.nid=n.id')
                ->field('nl.*,u.user_nickname,n.name,n.coin')
                ->paginate(10);
            $page = $res->render();
            $this->assign('list',$res);
            $this->assign('page',$page);
            return $this->fetch();
        }

        public function add_log(){
            $list = db('noble')->where(['status'=>1])->select();
            $this->assign('list',$list);
            return $this->fetch();
        }

        public function add_log_post(){
            $param = $this->request->param();

            $data = $param['post'];
            $data['addtime'] = time();
            //$data['car_status'] = 1;

            $uid = $data['uid'];
            $nid = $data['nid'];

            //查询是否开启其他贵族
            $user = Db::name("user")->field('nobility_level,noble_end_time')->find($uid);
            if($user['noble_end_time']>NOW_TIME && $user['nobility_level']>$nid){
                $this->error(lang('Nobles_are_lower_than_current_level'));
                exit;
            }
            $result = Db::name("noble_log")->insert($data);
            if($result){
                //直接覆盖贵族
                $info = Db::name('noble')->find($nid);
                if($info['id']==$user['nobility_level'] && $user['noble_end_time'] > NOW_TIME){
                    $time = $user['noble_end_time'] + $info['noble_time']*86400;
                }else{
                    $time = NOW_TIME + $info['noble_time']*86400;
                }
                db('user')
                    ->where(['id'=>$uid])
                    ->update(['nobility_level'=>$nid,'noble_end_time'=>$time]);
                //$this->add_headdress($uid,$nid);
                $this->save_user_dress_up($uid,$info);
                $this->success(lang('EDIT_SUCCESS'), url('noble/add_noble_log'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function aperture(){
            $car = Db::name("noble_aperture")
                ->alias('a')
                ->join('noble n','a.nid=n.id')
                ->field('a.*,n.name as noble_name')
                ->paginate(10);

            $this->assign('data', $car);
            $this->assign('page', $car->render());
            return $this->fetch();
        }

        public function aperture_add(){
            $param = $this->request->param();
            $id = input('id');
            if($id){
                $list = db('noble_aperture')->find($id);
            }else{
                $list = [
                    'aperture_svga'=>'',
                    'nid'=>0
                ];
            }
            $noble_list = db('noble')->where(['status'=>1])->select();
            $this->assign('data',$list);
            $this->assign('noble',$noble_list);
            return $this->fetch();
        }

        public function aperture_addPost(){
            $param = $this->request->param();
            // print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$data['img'] = $param['post']['img'];
            $data['create_time'] = time();
            //$data['car_status'] = 1;
            if ($id) {
                $result = Db::name("noble_aperture")->where("id=$id")->update($data);
            } else {
                $result = Db::name("noble_aperture")->insert($data);
            }
            if ($result) {
                $this->success(lang('EDIT_SUCCESS'), url('noble/aperture'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }

        public function aperture_del()
        {
            $param = request()->param();

            $result = Db::name("noble_aperture")->where("id=" . $param['id'])->delete();


            return $result ? '1' : '0';
            exit;
        }

        /*
     * 增加头饰
     * uid 用户ID
     * nid 贵族ID
     * */
        private function add_headdress($uid,$nid){
            //查询贵族头饰
            $headdress = db('headdress')
                ->alias('h')
                ->join('noble n','n.id=h.nid')
                ->where(['h.status'=>1,'h.nid'=>$nid])
                ->field('h.id,n.noble_time')
                ->find();
            if($headdress){
                //增加头饰
                $user_headdress = db('user_headdress')->where(['uid'=>$uid,'hid'=>$headdress['id']])->find();
                if($user_headdress){
                    if($user_headdress['endtime'] >= NOW_TIME){
                        $dress_time = $user_headdress['endtime'] + $headdress['noble_time'] * 86400;
                        $duration = $headdress['noble_time'] + $user_headdress['duration'];
                    }else{
                        $dress_time = NOW_TIME + $headdress['noble_time'] * 86400;
                        $duration = $headdress['noble_time'];
                    }
                    $data = [
                        'duration'=>$duration,
                        'endtime'=>$dress_time,
                        'addtime'=> NOW_TIME,
                        'status'=>0
                    ];
                    db('user_headdress')->where(['id'=>$user_headdress['id']])->update($data);
                    //$table_id = $user_headdress['id'];
                }else{
                    $data = [
                        'hid'=>$headdress['id'],
                        'duration'=>$headdress['noble_time'],
                        'endtime'=> NOW_TIME + $headdress['noble_time']*86400,
                        'uid'=>$uid,
                        'addtime'=>NOW_TIME,
                        'status'=>0
                    ];
                    db('user_headdress')->insertGetId($data);
                }
            }

        }
        // 增加装饰
        public function save_user_dress_up($uid,$info){
            $days = 0;
            if (intval($info['noble_time']) > 0) {
                $days = $info['noble_time']*24*60*60;
            }
            if($days){
                if($info['chat_bg_id']){
                    $dress_up_id_array = explode(",", $info['chat_bg_id']);
                    foreach ($dress_up_id_array as $v){
                        $this->dress_up_save($uid,5,$v,$days);
                    }
                }
                if($info['chat_bubble_id']){
                    $dress_up_id_array = explode(",", $info['chat_bubble_id']);
                    foreach ($dress_up_id_array as $v){
                        $this->dress_up_save($uid,4,$v,$days);
                    }
                }
                if($info['avatar_frame_id']){
                    $dress_up_id_array = explode(",", $info['avatar_frame_id']);
                    foreach ($dress_up_id_array as $v){
                        $this->dress_up_save($uid,3,$v,$days);
                    }
                }
                if($info['home_page_id']){
                    $dress_up_id_array = explode(",", $info['home_page_id']);
                    foreach ($dress_up_id_array as $v){
                        $this->dress_up_save($uid,2,$v,$days);
                    }
                }
                if($info['medal_id']){
                    $dress_up_id_array = explode(",", $info['medal_id']);
                    foreach ($dress_up_id_array as $v){
                        $this->dress_up_save($uid,1,$v,$days);
                    }
                }
                if($info['car_id']){
                    $dress_up_id_array = explode(",", $info['car_id']);
                    foreach ($dress_up_id_array as $v){
                        $this->dress_up_save($uid,7,$v,$days);
                    }
                }
            }
        }
        // 封装装扮
        public function dress_up_save($uid,$type,$dress_id,$days){
            $time = NOW_TIME;
            $endtime = $days + $time;
            $dress_up = db('dress_up') ->where(['id'=>intval($dress_id)])->find();
            if($dress_up){
                if($type == 5){
                    $dress_up['icon'] = $dress_up['img_bg'] ? $dress_up['img_bg'] : $dress_up['icon'];
                }
                //是否购买过
                $info = db('user_dress_up') ->where(['uid'=>$uid,'dress_id'=>intval($dress_id)])->find();
                // 关闭正在使用的
                db('user_dress_up')->alias('u')
                    ->join('dress_up d','d.id=u.dress_id')
                    ->where('u.uid',$uid)
                    ->where("d.type",$type)
                    ->update(['status'=>0]);
                if($info){
                    $end_time = $info['endtime'] > $time ? $info['endtime'] + $days : $endtime;
                    db('user_dress_up')->where(['uid'=>$uid,'dress_id'=>intval($dress_id)])
                        ->update(['endtime' => $end_time,'status' =>1,'dress_up_name'=>$dress_up['name'],'dress_up_icon'=>$dress_up['icon'],'dress_up_type'=>$type]);
                }else{
                    $data = [
                        'uid'=>$uid,
                        'dress_id' => intval($dress_id),
                        'status' => 1,
                        'addtime' => $time,
                        'endtime' => $endtime,
                        'dress_up_name' => $dress_up['name'],
                        'dress_up_icon'=> $dress_up['icon'],
                        'dress_up_type'=> $type
                    ];
                    db('user_dress_up')->insertGetId($data);
                }
            }
        }
    }
