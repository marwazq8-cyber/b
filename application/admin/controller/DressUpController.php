<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2020-05-30
 * Time: 10:38
 * 签到管理
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Request;
use app\admin\model\PlayGameModel;

class DressUpController extends AdminBaseController
{
    //勋章
    public function index()
    {
        $data = Db::name('dress_up')
            ->where('type', 1)
            ->where('is_pay', 1)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $data);
        return $this->fetch();
    }

    //勋章
    public function noble_index()
    {
        $data = Db::name('dress_up')
            ->where('type', 1)
            ->where('is_pay', 0)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $data);
        return $this->fetch();
    }

    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("dress_up")->where("id=$id")->find();
        } else {
            $name['box_id'] = 0;

        }
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $name);
        $this->assign('data', $name);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("dress_up")->where("id=$id")->update($data);
        } else {
            $result = Db::name("dress_up")->insert($data);
        }
        if ($result) {
            if ($data['type'] == 1) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/index'));
            } else if ($data['type'] == 2) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/home_page'));
            } else if ($data['type'] == 3) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/avatar_frame'));
            } else if ($data['type'] == 4) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/chat_bubble'));
            } else if ($data['type'] == 5) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/chat_bg'));
            }

        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //主页特效
    public function home_page()
    {

        $data = Db::name('dress_up')
            ->where('type', 2)
            ->where('is_pay', 1)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign("data", session("level_index"));
        return $this->fetch();
    }

    public function noble_home_page()
    {

        $data = Db::name('dress_up')
            ->where('type', 2)
            ->where('is_pay', 0)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign("data", session("level_index"));
        return $this->fetch();
    }

    public function home_page_add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("dress_up")->where("id=$id")->find();
        } else {
            $name['box_id'] = 0;

        }
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $name);
        $this->assign('data', $name);
        return $this->fetch();
    }

    //头像框
    public function avatar_frame()
    {

        $data = Db::name('dress_up')
            ->where('type', 3)
            ->where('is_pay', 1)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);


        // 假设$data是一个包含若干对象的数组，且这些对象都具有svga属性
        // foreach ($data  as $k=>$v) { // 注意此处使用了引用传递，但在此场景中并非必需，除非你计划在循环中修改对象本身（而非其属性）
        //     // 检查svga属性是否为空，PHP中空字符串、null、未定义属性或0等均可视为“空”
        //     // 使用empty()函数可以方便地检测这些情况
        //     // if (empty($v['svga'])) {
        //     //     // 如果svga为空，我们假设你想设置一个标记属性（例如is_svga_empty）来记录这一状态
        //     //     // 注意：这里并未直接更改svga属性，而是添加了一个新属性
        //     //     $data[$k]['svga'] = '否'; // 表示svga为空
        //     // } else {
        //     //     // 如果svga不为空，则设置标记属性为false
        //     //     $data[$k]['svga'] = '是'; // 表示svga不为空
        //     // }
        //     // var_dump($data[$k]['svga']);
        //     // $data[$k]['svga'] = '否';
        // }
        
        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        return $this->fetch();
    }

    public function noble_avatar_frame()
    {

        $data = Db::name('dress_up')
            ->where('type', 3)
            ->where('is_pay', 0)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        return $this->fetch();
    }

    public function avatar_frame_add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("dress_up")->where("id=$id")->find();
        } else {
            $name['box_id'] = 0;
        }
        // var_dump($name);
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $name);
        $this->assign('data', $name);
        return $this->fetch();
    }

    //聊气泡，背景 chat_bubble
    public function chat_bubble()
    {

        $data = Db::name('dress_up')
            ->where('type', 4)
            ->where('is_pay', 1)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        return $this->fetch();
    }

    //聊气泡，背景 chat_bubble
    public function noble_chat_bubble()
    {

        $data = Db::name('dress_up')
            ->where('type', 4)
            ->where('is_pay', 0)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        return $this->fetch();
    }

    public function chat_bubble_add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("dress_up")->where("id=$id")->find();
        } else {
            $name['box_id'] = 0;
        }
        $this->assign('list', $name);
        $this->assign('data', $name);
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        return $this->fetch();
    }

    //聊背景
    public function chat_bg()
    {

        $data = Db::name('dress_up')
            ->where('type', 5)
            ->where('is_pay', 1)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        return $this->fetch();
    }

    public function noble_chat_bg()
    {

        $data = Db::name('dress_up')
            ->where('type', 5)
            ->where('is_pay', 0)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $this->assign('list', $data);
        return $this->fetch();
    }

    public function chat_bg_add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("dress_up")->where("id=$id")->find();
        } else {
            $name['box_id'] = 0;

        }
        $this->assign('list', $name);
        $this->assign('data', $name);
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        return $this->fetch();
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("dress_up")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    /*
     * 用户装饰列表
     * */
    public function user_dress()
    {
        if (!input('request.page')) {
            $data['uid'] = 0;
            $data['type'] = 0;
            session('bubble_gift', $data);
        }
        if (input('request.type') || input('request.uid')) {
            $data['type'] = input('request.type') ? input('request.type') : 0;
            $data['uid'] = input('request.uid') ? input('request.uid') : 0;
            session('bubble_gift', $data);
        } else {
            session('bubble_gift.type', -1);
        }
        $type = session('bubble_gift.type') > 0 ? session('bubble_gift.type') : '';
        $uid = session('bubble_gift.uid') > 0 ? session('bubble_gift.uid') : '';


        $where = "ud.id >0";
        $where .= $uid ? " and ud.uid=" . $uid : '';
        $where .= $type ? " and d.type=" . $type : '';
        $list = Db::name('user_dress_up')
            ->alias('ud')
            ->join('user u', 'u.id=ud.uid')
            ->join('dress_up d', 'd.id=ud.dress_id')
            ->field('ud.*,u.user_nickname,d.name,d.type')
            ->where($where)
            ->where('ud.endtime > ' . NOW_TIME)
            ->order("ud.addtime desc")
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();
        $page = $list->render();

        //$this->assign('statistical', $sum);
        $this->assign('list', $data['data']);
        $this->assign('page', $page);
        $this->assign('time', date('Y-m-d H:i', NOW_TIME));
        //$this->assign('bubble_type', $bubble_type);
        $this->assign('request', session('bubble_gift'));
        return $this->fetch();
    }

    public function user_dress_del()
    {
        $param = request()->param();
        $result = Db::name("user_dress_up")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    public function dress_set()
    {
        $result = array('code' => 1, 'msg' => lang('ADD_SUCCESS'));
        $time = input('time');
        $uid = input('uid');
        $dress_id = intval(input('dress_id'));
        $data = [
            'uid'      => $uid,
            'dress_id' => $dress_id,
            'addtime'  => NOW_TIME,
            'endtime'  => strtotime($time),
        ];
        $dress_up = db('dress_up')->where("id = " . $dress_id)->find();
        if (!$dress_up) {
            $result['code'] = 0;
            $result['msg'] = lang('Parameter_transfer_error');
        }
        $data['dress_up_name'] = $dress_up['name'];
        $data['dress_up_icon'] = $dress_up['icon'];
        $data['dress_up_type'] = $dress_up['type'];
        // dress_up_name   dress_up_icon  dress_up_type
        $info = db('user_dress_up')->where("uid = $uid and dress_id=$dress_id")->find();
        if ($info) {
            $res = db('user_dress_up')->where("uid = $uid and dress_id=$dress_id")->update($data);
        } else {
            $data['status'] = 0;
            $res = db('user_dress_up')->insert($data);
        }
        if (!$res) {
            $result['code'] = 0;
            $result['msg'] = lang('ADD_FAILED');
        }
        echo json_encode($result);
        exit;
    }

    //7进场特效
    public function car()
    {
        $data = Db::name('dress_up')
            ->where("type", 7)
            ->where("is_pay", 1)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $data);
        $this->assign('type', 7);
        $this->assign("data", session("level_index"));
        return $this->fetch();
    }

    public function noble_car()
    {
        $data = Db::name('dress_up')
            ->where("type", 7)
            ->where("is_pay", 0)
            ->order('orderno')
            ->paginate(10, false, ['query' => request()->param()]);

        $this->assign('page', $data->render());
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $data);
        $this->assign('type', 7);
        $this->assign("data", session("level_index"));
        return $this->fetch();
    }

    public function car_add()
    {
        $id = input('param.id');
        if ($id) {
            $list = Db::name("dress_up")->where("id=$id")->find();
        } else {
            $list['box_id'] = 0;
            $list['type'] = 7;
            $list['is_pay'] = 0;
            $list['img_bg'] = '';
        }
        $config = load_cache('config');
        $this->assign('currency_name', $config['currency_name']);
        $this->assign('list', $list);
        $this->assign('data', $list);
        return $this->fetch();
    }

    public function addPostPublic()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("dress_up")->where("id=$id")->update($data);
        } else {
            $result = Db::name("dress_up")->insert($data);
        }
        if ($result) {
            if ($data['type'] == 6) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/badge'));
            } else if ($data['type'] == 7) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/car'));
            } else if ($data['type'] == 8) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/mike'));
            } else if ($data['type'] == 9) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/nickname_card'));
            } else if ($data['type'] == 10) {
                $this->success(lang('EDIT_SUCCESS'), url('dress_up/business_card'));
            }

        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //8进场 座驾
    public function entry_vehicles()
    {
        $page = 10;
        $data = Db::name('dress_up')
            ->where(['type' => 11])
            ->order('orderno')
            ->paginate($page, false, ['query' => request()->param()]);
        $config = load_cache('config');
        $currency_name = $config['currency_name'];
        $this->assign('currency_name', $currency_name);
        $this->assign('page', $data->render());
        $this->assign('list', $data);
        $this->assign('type', 7);
        $this->assign("data", session("level_index"));
        return $this->fetch();
    }

    public function entry_vehicles_add()
    {
        $id = input('param.id');
        if ($id) {
            $list = Db::name("dress_up")->where("id=$id")->find();
        } else {
            $list['box_id'] = 0;
            $list['type'] = 11;
            $list['is_vip'] = 0;
            $list['img_bg'] = '';
        }
        $config = load_cache('config');
        $currency_name = $config['currency_name'];
        $this->assign('currency_name', $currency_name);
        $this->assign('list', $list);
        return $this->fetch();
    }

}
