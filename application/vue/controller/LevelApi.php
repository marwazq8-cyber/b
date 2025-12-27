<?php

namespace app\vue\controller;

use think\Db;
use think\helper\Time;
use app\vue\model\LevelModel;
use app\vue\controller\Base;

class  LevelApi extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        $this->LevelModel = new LevelModel();
    }

    /**
     * 获取等级列表
     */
    public function level_list()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        // 1消费等级 2收益等级
        $type = intval(input('param.type')) == 1 ? 1 : 2;
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        // 获取下一等级
        $grade_level = $type == 1 ? get_grade_level($uid) : get_grade_income_level($uid);

        // 获取等级列表
        if ($type == 1) {
            $where = "type=1";
        } else {
            $where = "type=2";
        }
        $level_list = db('level_type')->where($where)->order("min_level asc")->select();

        // 获取等级说明
        $portal_category = db("portal_category_post")->alias('a')
            ->where(" a.status=1 and b.post_type=1 and b.post_status=1 and a.category_id=40")
            ->join("portal_post b", "b.id=a.post_id")
            ->field("b.id,b.post_title,b.post_content")
            ->find();
        if ($portal_category) {
            $portal_category['post_content'] = html_entity_decode($portal_category['post_content']);
        }
        $result['data'] = array(
            'list' => $level_list,
            'grade_level' => $grade_level,
            'user' => array(
                'id' => $user_info['id'],
                'avatar' => $user_info['avatar'],
                'user_nickname' => $user_info['user_nickname']
            ),
            'content' => $portal_category ? $portal_category['post_content'] : '',
        );
        return_json_encode($result);
    }

    /**
     * 获取等级列表
     */
    public function get_level()
    {
        // bogo_level_type
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['last_login_ip']);
        // 1消费等级 2收益等级
        $type = intval(input('param.type')) == 1 ? 1 : 2;


        // 获取下一等级
        $grade_level = $type == 1 ? get_grade_level($uid) : get_grade_income_level($uid);
        //系统金币单位名称
        $config = load_cache('config');
        // 获取等级类型

        $level_type = db('level_type')->where("uid=$uid")->count();

        $result['data'] = $grade_level;
        $result['data']['type'] = $type;
        $result['data']['level_type'] = $level_type;
        $result['data']['coin_name'] = $type == 1 ? $config['currency_name'] : $config['virtual_currency_earnings_name'];
        return_json_encode($result);
    }

    public function index()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        // 1消费等级 2收益等级
        $type = intval(input('param.type')) == 1 ? 1 : 2;
        // 获取当前等级
        //    $level = $type == 1 ? get_level($uid) : get_income_level($uid);
        // 获取下一等级
        $grade_level = $type == 1 ? get_grade_level($uid) : get_grade_income_level($uid);
        //系统金币单位名称
        $config = load_cache('config');

        $result['data'] = $grade_level;
        $result['data']['type'] = $type;
        $result['data']['coin_name'] = $type == 1 ? $config['currency_name'] : $config['virtual_currency_earnings_name'];
        return_json_encode($result);
    }
}

?>