<?php
/**
 * Redis缓存驱动，适合单机部署、有前端代理实现高可用的场景，性能最好
 * 有需要在业务层实现读写分离、或者使用RedisCluster的需求，请使用Redisd驱动
 */

namespace system;

use think\Cache;

class RedisPackage
{
    protected static $handler = null;
    public static $prefix = '';

    public function __construct($options = [])
    {
        //判断是否有扩展(如果你的apache没reids扩展就会抛出这个异常)
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }

        self::$prefix = config('cache.prefix');
        //判断是否长连接
        self::$handler = Cache::store('redis')->handler();
    }

    /**
     * 写入缓存
     * @param string $key    键名
     * @param string $value  键值
     * @param int    $exprie 过期时间 0:永不过期
     * @return bool
     */
    public static function set($key, $value, $exprie = 0)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        if ($exprie == 0) {
            $set = self::$handler->set(self::$prefix . $key, $value);
        } else {
            $set = self::$handler->setex(self::$prefix . $key, $exprie, $value);
        }
        return $set;
    }

    /**
     * 读取缓存
     * @param string $key 键值
     * @return mixed
     */
    public static function get($key)
    {
        $fun = is_array($key) ? 'Mget' : 'get';
        return self::$handler->{$fun}(self::$prefix . $key);
    }

    /**
     * 删除缓存
     * @param string $key 键值
     * @return mixed
     */
    public static function del($key)
    {
        $fun = 'del';
        return self::$handler->{$fun}(self::$prefix . $key);
    }

    /**
     * 获取值长度
     * @param string $key
     * @return int
     */
    public static function lLen($key)
    {
        return self::$handler->lLen(self::$prefix . $key);
    }

    /**
     * 将一个或多个值插入到列表头部
     * @param $key
     * @param $value
     * @return int
     */
    public static function LPush($key, $value)
    {
        return self::$handler->lPush(self::$prefix . $key, $value);
    }

    /**
     * 将一个或多个值插入到列表尾部
     * @param $key
     * @param $value
     * @return int
     */
    public static function RPush($key, $value)
    {
        return self::$handler->rPush(self::$prefix . $key, $value);
    }

    /**
     * 移出并获取列表的第一个元素
     * @param string $key
     * @return string
     */
    public static function lPop($key)
    {
        return self::$handler->lPop(self::$prefix . $key);
    }


    public static function set_lock($key, $value = null, $exp = 10)
    {
        return self::$handler->set(self::$prefix . $key, $value, array('nx', 'ex' => $exp));
    }

    public static function hGet($key1, $key2)
    {

        return self::$handler->hGet(self::$prefix . $key1, $key2);
    }

    public static function hSet($key1, $key2, $val)
    {

        self::$handler->hSet(self::$prefix . $key1, $key2, $val);
    }

    public static function hDel($key1, $key2)
    {

        self::$handler->hDel(self::$prefix . $key1, $key2);
    }

    public static function hLen($key1)
    {

        return self::$handler->hLen(self::$prefix . $key1);
    }

    public static function hVals($key1)
    {

        return self::$handler->hVals(self::$prefix . $key1);
    }

    // 自增
    public static function incr($key1, $val = 1)
    {
        return self::$handler->incr(self::$prefix . $key1, $val);
    }

    // 获取所有的键名
    public static function hKeys($key1)
    {
        return self::$handler->hKeys(self::$prefix . $key1);
    }

    public static function zAdd($key, $score1, $value1)
    {
        return self::$handler->zAdd(self::$prefix . $key, $score1, $value1);
    }

    public static function zRevRange($key, $start, $stop, $WITHSCORES)
    {
        return self::$handler->zRevRange(self::$prefix . $key, $start, $stop, $WITHSCORES);
    }

    public static function zRange($key, $start, $end, $withscores)
    {
        return self::$handler->zRange(self::$prefix . $key, $start, $end, $withscores);
    }

    public static function zCount($key, $start, $end)
    {
        return self::$handler->zCount(self::$prefix . $key, $start, $end);
    }

    public static function sAdd($key, $value1)
    {
        return self::$handler->sAdd(self::$prefix . $key, $value1);
    }

    /**
     * 添加所有当前时间用户信息
     */
    public function smembers($key)
    {
        return self::$handler->SMEMBERS(self::$prefix . $key);
    }

    /**
     * 获取数量
     */
    public function Scard($key)
    {
        return self::$handler->SCARD(self::$prefix . $key);
    }

    /**
     * 移除集合中
     */
    public function srem($key, $value)
    {
        return self::$handler->SREM(self::$prefix . $key, $value);
    }

    public static function Zincrby($key, $number, $field)
    {
        return self::$handler->Zincrby(self::$prefix . $key, $number, $field);
    }

}
