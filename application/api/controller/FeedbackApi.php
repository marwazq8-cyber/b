<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/29 0029
 * Time: 下午 15:36
 */

namespace app\api\controller;

use app\api\controller\Base;

class FeedbackApi extends Base
{
    /*app*/
    //提价表单
    public function app_buy()
    {
        $result = array('code' => 0, 'msg' => lang('Upload_failed'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $content = trim(input('param.centent'));
        $tel = trim(input('param.tel'));

        $img = request()->file(); //获取图
        if (count($img) > 4) {
            $result['msg'] = lang('Maximum_4_pictures');
            return_json_encode($result);
        }

        $user_info = check_login_token($uid, $token);

        if (strlen($content) < 10) {
            $result['msg'] = lang('Fill_in_10_word_description');
            return_json_encode($result);
        }

        if (strlen($tel) < 8) {
            $result['msg'] = lang('Incorrect_mobile_number');
            return_json_encode($result);
        }

        if (count($img) > 0) {

            foreach ($img as $k => $v) {
                $uploads = oss_upload($v); //单图片上传
                if ($uploads) {
                    $name[$k] = $uploads;
                }
            }
        }

        $name['content'] = $content;
        $name['tel'] = $tel;
        $name['uid'] = $uid;
        $name['addtime'] = time();

        //添加记录
        $result = db('feedback')->insert($name);
        //   var_dump(db()->getlastsql());exit;
        if ($result) {
            $result['code'] = 1;
            $result['msg'] = lang('Thank_you_for_your_comments');
        }

        return_json_encode($result);

    }

    /*h5*/
    public function index()
    {
        $result = array('code' => 1, 'msg' => lang('Submit_failed_retry'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        //    $uid='100163';
        //   $token='ff290e2b28cd3921fc569674126f7ee6';

        if ($uid == 0 || empty($token)) {
            $result['code'] = 0;
            $result['msg'] = lang('Parameter_transfer_error');
            return_json_encode($result);
        }

        $user_info = check_token($uid, $token);

        if (!$user_info) {
            $result['code'] = 10001;
            $result['msg'] = lang('login_timeout');
            return_json_encode($result);
        }
        $data['uid'] = $uid;
        $data['token'] = $token;
        session('Feedback', $data);
        return $this->fetch();
    }

    //提交表单
    public function buy()
    {
        $uid = session('Feedback.uid');
        $content = input('param.centent');
        $tel = input('param.tel');
        $data = array('status' => 0, 'msg' => lang('Upload_failed'));
        if (strlen($content) < 10) {
            $data['msg'] = lang('Fill_in_10_word_description');
            echo json_encode($data);
            exit;
        }
        if (strlen($tel) != 11) {
            $data['msg'] = lang('Incorrect_mobile_number');
            echo json_encode($data);
            exit;
        }
        $name['content'] = $content;
        $name['tel'] = $tel;
        $img = request()->file(); //获取私照上传的文件
        if ($img['img']) {
            $audio_path = $this->feedback_img($img['img']);
            if (count($audio_path) > 0) {
                for ($i = 0; $i < count($audio_path); $i++) {
                    $name['img' . $i] = $audio_path[$i];
                }
            }
        }

        $name['uid'] = $uid;
        $name['addtime'] = time();
        //添加邀请记录
        $result = db('feedback')->insert($name);
        if ($result) {
            $data['status'] = 1;
            $data['msg'] = lang('Thank_you_for_your_comments');
        }

        echo json_encode($data);
        exit;

    }

    private function feedback_img($img)
    {
        if (count($img) > 0) {
            $audio_path = [];
            foreach ($img as $k => $v) {
                $audio_path[] = oss_upload($v); //单图片上传
            }
        }
        return $audio_path;
    }
}
