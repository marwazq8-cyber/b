<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use Qcloud\Cos\Client;
use QCloud\COSSTS\Sts;
use think\Db;
use think\Request;
use app\admin\model\PlayGameModel;
use Qiniu\Auth;

class QiniuController extends AdminBaseController
{
    public function index()
    {
        $logs = Db::name('qiniu_upload_log')
            ->order("create_time DESC")
            ->paginate(20, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $logs->render();

        $this->assign("page", $page);
        $this->assign("list", $logs);
        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    public function get_qiniu_upload_token()
    {
        header('Access-Control-Allow-Origin:*');
        $result = array('code' => 1, 'msg' => '');

        // $this->checkLoginToken(['image_label']);

        $qiniu_config = get_qiniu_config();

        require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $qiniu_config['accessKey'];
        $secretKey = $qiniu_config['secretKey'];
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $result['bucket'] = $qiniu_config['bucket'];
        $result['domain'] = $qiniu_config['domain'];
        $result['token'] = $auth->uploadToken($result['bucket']);
        echo json_encode($result);
        //return_json_encode($result);
    }

    public function add_log()
    {
        $url = input('url');
        $data = [
            'url'         => $url,
            'create_time' => NOW_TIME,
        ];
        $res = db('qiniu_upload_log')->insert($data);
        return $res ? 1 : 0;
    }

    public function cloud()
    {
        $ten_info = db('upload_set')->where('type = 1')->find();
        $data['region'] = $ten_info['region'];
        $data['bucket_url'] = $ten_info['url'];
        $data['bucket'] = $ten_info['bucket'];
        $data['accelerate_domain_name'] = $ten_info['accelerate_domain_name'];
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function get_qcloud_update_token()
    {
        header('Access-Control-Allow-Origin:*');
        $result = array('code' => 1, 'msg' => '');

        // $this->checkLoginToken(['image_label']);

        $ten_info = db('upload_set')->where('type = 1')->find();
        //dump($ten_info);die();
        if ($ten_info) {
            require_once DOCUMENT_ROOT . '/system/qcloud_upload_sdk/qcloud-cos-sts-php-sdk/src/Sts.php';

            $SecretId = $ten_info['secret_id'];
            $SecretKey = $ten_info['secret_key'];
            $region = $ten_info['region'];
            $bucket_url = $ten_info['url'];
            $bucket = $ten_info['bucket'];
            $allowPrefix = '*';
            //$protocol = $ten_info['protocol'];
            //$url = $protocol.'://'.$bucket_url;
            //dump($ten_info);die();
            $sts = new Sts();
            //$sts = new Sts();
            $config = array(
                'url'             => 'https://sts.tencentcloudapi.com/',
                'domain'          => 'sts.tencentcloudapi.com', // 域名，非必须，默认为 sts.tencentcloudapi.com
                'proxy'           => '',
                'secretId'        => $SecretId, // 固定密钥
                'secretKey'       => $SecretKey, // 固定密钥
                'bucket'          => $bucket, // 换成你的 bucket
                'region'          => $region, // 换成 bucket 所在园区
                'durationSeconds' => 1800, // 密钥有效期
                'allowPrefix'     => $allowPrefix, // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
                // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
                'allowActions'    => array(
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
            $tempKeys = $sts->getTempKeys($config);
            //dump($tempKeys);die();
            if (isset($tempKeys['credentials'])) {
                $result['sessionToken'] = $tempKeys['credentials']['sessionToken'];
                $result['tmpSecretId'] = $tempKeys['credentials']['tmpSecretId'];
                $result['tmpSecretKey'] = $tempKeys['credentials']['tmpSecretKey'];
                $result['bucket'] = $bucket;
                $result['domain'] = $bucket_url;
                $result['region'] = $region;
                $result['expiredTime'] = $tempKeys['expiredTime'];
                $result['startTime'] = $tempKeys['startTime'];
                $result['requestId'] = $tempKeys['requestId'];
                $result['allowPrefix'] = $allowPrefix;
                $result['credentials'] = $tempKeys['credentials'];
                //echo date('Y-m-d H:i:s',$tempKeys['expiredTime']);die();
            }
            //dump($tempKeys);die();
            //$result['data'] = $tempKeys;
            //$result['data']['bucket'] = $bucket;
            //$result['data']['protocol'] = $protocol;
            //$result['data']['domain'] = $url;
        } else {
            $result['code'] = 0;
            $result['msg'] = lang('Tencent_cloud_upload_is_not_configured');
        }
        echo json_encode($result);
        //return_json_encode($result);
    }

    function qcloud_upload($arrInfo)
    {

        input();
        require_once DOCUMENT_ROOT . '/system/qcloud_upload_sdk/cos-php-sdk-v5/vendor/autoload.php';
        require_once DOCUMENT_ROOT . '/system/qcloud_upload_sdk/cos-php-sdk-v5/src/Qcloud/Cos/Common.php';
        $ten_info = db('upload_set')->where('type = 1')->find();
        if ($ten_info) {
            $SecretId = $ten_info['secret_id'];
            $SecretKey = $ten_info['secret_key'];
            $region = $ten_info['region'];
            $bucket_url = $ten_info['url'];
            $bucket = $ten_info['bucket'];
            //$protocol = $ten_info['protocol'];
            //$url = $protocol.'://'.$bucket_url;
        } else {
            return false;
            exit;
        }
        /*$SecretId = 'AKIDeBYmphrsBNUVL08q11pRuCNODqLfMkV5';
        $SecretKey = 'YzFKJ7jAOVZUDk5FF6lIeqrhS2Qgv1zq';
        $region = 'ap-beijing';
        $bucket = 'cesi-1256486109';*/
        $data = array(
            'region'      => $region,
            'credentials' => array(
                'secretId'  => $SecretId,
                'secretKey' => $SecretKey
            )
        );
        $cosClient = new Client($data);

        //echo DOCUMENT_ROOT;die();
        $key = $arrInfo['file_name'];
        //$Body = $arrInfo['file_path'];
        $filePath = "upload/" . $arrInfo['file_path'];
        try {
            $result = $cosClient->putObject(array(
                'Bucket' => $bucket,
                'Key'    => $key,
                'Body'   => fopen($filePath, 'rb')));
            //$rt = $cosClient->getObject(['Bucket'=>$bucket, 'Key'=>$key]);
            return $bucket_url . '/' . $key;
            //return $result;
        } catch (\Exception $e) {
            echo "$e\n";
        }
        //die();
    }
}
