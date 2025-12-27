<?php


/*
 * Redis
 *
 * */
//写入缓存
function redis_set($key, $value, $time = 60)
{
    return $GLOBALS['redis']->set($key, $value, $time);
}

//获取缓存
function redis_get($key)
{
    return $GLOBALS['redis']->get($key);
}

//删除缓存
function redis_rm($key)
{
    return $GLOBALS['redis']->del($key);
}

//判断缓存是否存在
function redis_has($key)
{
    return $GLOBALS['redis']->has($key);
}

//缓存加锁 --- 如果存在返回false 成功返回true
function redis_lock($key = 'redis_lock', $value = true)
{
    return $GLOBALS['redis']->set_lock($key, $value);
}

function redis_locksleep($key)
{
    do {  //针对问题1，使用循环
        $isLock = redis_lock($key);
        if ($isLock) {
            continue;
        } else {
            usleep(5000);
        }
    } while (!$isLock);
}


//缓存解锁
function redis_unlock($key = 'redis_lock')
{
    return $GLOBALS['redis']->del('del', $key);
}

//判断缓存锁是否存在
function redis_is_lock($key = 'redis_lock')
{
    return $GLOBALS['redis']->has($key);
}

//缓存锁睡眠
function redis_lock_sleep($key = 'redis_lock')
{
    do {  //针对问题1，使用循环
        $isLock = redis_is_lock($key);
        if ($isLock) {
            usleep(5000);
        } else {
            continue;
        }
    } while ($isLock);
}

function redis_hMGet($key, $hashKeys)
{
    return $GLOBALS['redis']->hMGet($key, $hashKeys);
}

function redis_zAdd($key, $score1, $value1)
{
    return $GLOBALS['redis']->zAdd($key, $score1, $value1);
}

function redis_hIncrBy($key, $hashKey, $value)
{
    return $GLOBALS['redis']->hIncrBy($key, $hashKey, $value);
}

function redis_zCount($key, $start, $end)
{
    return $GLOBALS['redis']->zCount($key, $start, $end);
}

function redis_zRem($key, $member1)
{
    return $GLOBALS['redis']->zRem($key, $member1);
}

function redis_zRange($key, $start, $end, $withscores = null)
{
    return $GLOBALS['redis']->zRange($key, $start, $end, $withscores = null);
}

function redis_zRevRange($key, $start, $end, $withscores = null)
{
    return $GLOBALS['redis']->zRevRange($key, $start, $end, $withscores = null);
}

function redis_hMSet($key, $fieldArr)
{
    return $GLOBALS['redis']->hMSet($key, $fieldArr);
}

function redis_srandmember($key, $num)
{
    return $GLOBALS['redis']->srandmember($key, $num);
}

function redis_zScore($key, $member)
{
    return $GLOBALS['redis']->zScore($key, $member);
}

function redis_sAdd($key, $val)
{
    return $GLOBALS['redis']->sAdd($key, $val);
}

function redis_smembers($key)
{
    return $GLOBALS['redis']->smembers($key);
}

function redis_Scard($key)
{
    return $GLOBALS['redis']->Scard($key);
}

function redis_sRem($key, $val)
{
    return $GLOBALS['redis']->srem($key, $val);
}

function redis_Zincrby($key, $number, $value1)
{
    return $GLOBALS['redis']->Zincrby($key, $number, $value1);
}
