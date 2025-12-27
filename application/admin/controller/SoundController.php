<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 上午 11:02
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class SoundController extends AdminBaseController
{
    /**
     * 音效列表
     */
    public function index()
    {
        $voice_sound = Db::name("voice_sound")->select();
        $this->assign('voice_sound', $voice_sound);
        return $this->fetch();
    }

    /**
     * 等级添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("voice_sound")->where("id=$id")->find(); 
        }else{
            $name['img']='';
            $name['audio_file']='';
        }
        $this->assign('sound', $name);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("voice_sound")->where("id=$id")->update($data);
        } else {
            $result = Db::name("voice_sound")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('sound/index'));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("voice_sound")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

  
}
