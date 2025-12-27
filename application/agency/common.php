<?php


// 语言包设置 -- 修改语言包后必须重新生成缓存文件
function apiLang($name)
{
    return lang($name);
}

function strim($str)
{
    return $str ? quotes(htmlspecialchars(trim($str))) : '';
}

function quotes($content)
{
    if (is_array($content)) {
        foreach ($content as $key => $value) {
            $content[$key] = addslashes($value);
        }
    } else {
        $content = addslashes($content);
    }
    return $content;
}

/**
 * 获取token
 * @param $str
 * @return string
 */
function get_token($str)
{
    empty($str) && $str = random();
    $token = md5($str . time() . 'bogo');
    return $token;
}

function get_d($number = 1)
{
    return 60 * 60 * 24 * $number;
}

/**
 * 总后台token存储到redis的前缀
 * @return string
 */
function get_platform_agency_token_prefix()
{
    return 'platform_agency_token_';
}

/**
 * @description 获取缓存
 * @param $key
 * @return mixed
 */
function redis_get_token($token)
{
    $re = redis_get($token);
    if (is_string($re)) {
        return json_decode($re, true);
    } else {
        $root = array('code' => 401, 'message' => '', 'data' => array());
        // 用户未登陆,请先登陆.
        return_json_encode($root);
    }
}