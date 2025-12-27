<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kane <chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use think\config;

//引入七牛云的相关文件
use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\db;

/**
 * 附件上传控制器
 * Class Asset
 * @package app\asset\controller
 */
class AssetController extends AdminBaseController
{
    public function _initialize()
    {
        $adminId = cmf_get_current_admin_id();
        $userId = cmf_get_current_user_id();
        if (empty($adminId) && empty($userId)) {
            exit(lang('Illegal_upload'));
        }
    }

    /**
     * webuploader 上传
     */
    public function webuploader()
    {
        if ($this->request->isPost()) {

            $uploader = new Upload();

            $result = $uploader->upload();

            // var_dump($result);
            if ($result === false) {
                $this->error($uploader->getError());
            } else {
                $this->success(lang('Upload_successful'), '', $result);
            }

        } else {
            $uploadSetting = cmf_get_upload_setting();
            //dump($uploadSetting);die();
            $arrFileTypes = [
                'image' => ['title' => 'Image files', 'extensions' => $uploadSetting['file_types']['image']['extensions']],
                'video' => ['title' => 'Video files', 'extensions' => $uploadSetting['file_types']['video']['extensions']],
                'audio' => ['title' => 'Audio files', 'extensions' => $uploadSetting['file_types']['audio']['extensions']],
                'file' => ['title' => 'Custom files', 'extensions' => $uploadSetting['file_types']['file']['extensions']],
                'apk' => ['title' => 'Custom files', 'extensions' => $uploadSetting['file_types']['apk']['extensions']]
            ];

            $arrData = $this->request->param();
            if (empty($arrData["filetype"])) {
                $arrData["filetype"] = "image";
            }

            $fileType = $arrData["filetype"];

            if (array_key_exists($arrData["filetype"], $arrFileTypes)) {
                $extensions = $uploadSetting['file_types'][$arrData["filetype"]]['extensions'];
                $fileTypeUploadMaxFileSize = $uploadSetting['file_types'][$fileType]['upload_max_filesize'];
            } else {
                $this->error(lang('Upload_file_type_configuration_error'));
            }

            $this->assign('filetype', $arrData["filetype"]);
            $this->assign('extensions', $extensions);
            $this->assign('upload_max_filesize', $fileTypeUploadMaxFileSize * 1024);
            $this->assign('upload_max_filesize_mb', intval($fileTypeUploadMaxFileSize / 1024));
            $maxFiles = intval($uploadSetting['max_files']);
            $maxFiles = empty($maxFiles) ? 20 : $maxFiles;
            $chunkSize = intval($uploadSetting['chunk_size']);
            $chunkSize = empty($chunkSize) ? 512 : $chunkSize;
            $this->assign('max_files', $arrData["multi"] ? $maxFiles : 1);
            $this->assign('chunk_size', $chunkSize); //// 单位KB
            $this->assign('multi', $arrData["multi"]);
            $this->assign('app', $arrData["app"]);

            return $this->fetch(":webuploader");

        }
    }

}
