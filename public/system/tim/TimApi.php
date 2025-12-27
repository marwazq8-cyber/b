<?php

require_once(ROOT_PATH . 'public/system/tim/TimRestApi.php');
/**
 * sdkappid 是app的sdkappid
 * identifier 是用户帐号
 * private_pem_path 为私钥在本地位置
 * server_name 是服务类型
 * command 是具体命令
 */

function createTimAPI()
{

    $m_config = load_cache("config");//参数
    $sdkappid = $m_config['tencent_sdkappid'];
    $identifier = $m_config['tencent_identifier'];
    $tencent_sha_key = $m_config['tencent_sha_key'];
    $im_yun_url = $m_config['im_yun_url'];
    $ret = load_cache("usersign", array("id" => $identifier));

    //echo $sdkappid.' '.$identifier.' '.$tencent_sha_key;
    //  dump($ret);exit;
    if ($ret['status'] == 1) {

        $api = createRestAPI();
        $api->init($sdkappid, $identifier, $tencent_sha_key, $im_yun_url);
        $api->set_user_sig($ret['usersign']);
        return $api;

    } else {
        return $ret;
    }
}
