<?php
namespace app\guild_api\controller;

use Qiniu\Auth;
use think\Controller;
use think\Db;
use think\config;
class UploadApi extends Base
{
    public function get_qiniu_upload_token()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('id'));
        $token = trim(input('token'));
        $name = trim(input('name'));
        //$user_info = $this->check_token($uid,$token);
        $qiniu_config = get_qiniu_config();

        require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $qiniu_config['accessKey'];
        $secretKey = $qiniu_config['secretKey'];
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $result['data']['bucket'] = $qiniu_config['bucket'];
        $result['data']['domain'] = $qiniu_config['domain'];
        $result['data']['qiniu_token'] = $auth->uploadToken($qiniu_config['bucket']);
        $result['data']['qiniu_key'] = NOW_TIME.rand(1000,999).$name;
        //dump($_FILES);die();
        return_json_encode($result);
    }
}