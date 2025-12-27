<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Request;
use app\admin\model\PlayGameModel;
use QCloud\COSSTS\Sts;

class UploadController extends AdminBaseController
{
    public function index(){
        $logs = Db::name('qiniu_upload_log')
            ->order("create_time DESC")
            ->paginate(20, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $logs->render();

        $this->assign("page", $page);
        $this->assign("list", $logs);
        return $this->fetch();
    }

    public function add(){
        return $this->fetch();
    }
    public function get_upload_token()
    {
        header('Access-Control-Allow-Origin:*');
        $result = array('code' => 1, 'msg' => '');
        require_once DOCUMENT_ROOT . '/system/qcloud_upload_sdk/qcloud-cos-sts-php-sdk/src/Sts.php';
        //new Sts();
        $sts = new Sts();
        $secretId = '';
        $secretKey = '';
        $bucket = '';
        $region = '';
        $time = 1800;
        $config = array(
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com', // 域名，非必须，默认为 sts.tencentcloudapi.com
            'proxy' => '',
            'secretId' => $secretId, // 固定密钥
            'secretKey' => $secretKey, // 固定密钥
            'bucket' => $bucket, // 换成你的 bucket
            'region' => $region, // 换成 bucket 所在园区
            'durationSeconds' => $time, // 密钥有效期
            'allowPrefix' => '*', // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => array (
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload'
            )
        );

        // 获取临时密钥，计算签名
        $tempKeys = $sts->getTempKeys($config);
        echo json_encode($tempKeys);

    }

    public function add_log(){
        $url = input('url');
        $data = [
            'url'=>$url,
            'create_time'=>NOW_TIME,
        ];
        $res = db('qiniu_upload_log')->insert($data);
        return $res?1:0;
    }

    /*
     * 配置设置*/
    public function setting(){
        $type = input('type');
        $ten_info = db('upload_set')->where('type = '.$type)->find();
        if($ten_info){
            $SecretId = $ten_info['secret_id'];
            $SecretKey = $ten_info['secret_key'];
            $region = $ten_info['region'];
            $bucket_url = $ten_info['url'];
            $bucket = $ten_info['bucket'];
            $protocol = $ten_info['protocol'];
            $url = $protocol.'://'.$bucket_url;
        }else{
            $ten_info = array(
                'secret_id' => '',
                'secret_key' => '',
                'region' => '',
                'url' => '',
                'bucket' => '',
                'protocol' => '',
                'accelerate_domain_name'=> '',
            );
        }
        $this->assign('list',$ten_info);
        return $this->fetch();
    }

    public function addPost(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $type = $param['type'];

        if ($id) {
            $result = Db::name("upload_set")->where("id=$id")->update($data);
        } else {
            $result = Db::name("upload_set")->insert($data);
        }
        if ($result) {
            $this->success(lang('EDIT_SUCCESS'), url('upload/setting',array('type'=>$type)));
        } else {
            $this->error(lang('EDIT_FAILED'));
        }
    }
}
