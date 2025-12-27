<?php

class level_auto_cache
{
    private $key = "level:list";

    public function load($param, $is_real = true)
    {
        if (isset($param['type'])) {
            $level_list = cache($this->key . $param['type']);
        } else {
            $level_list = cache($this->key);
        }

        if ($level_list === false) {
            if (isset($param['type'])) {
                $level_list = db('level')->field("*")->where("type", '=', $param['type'])->order("level_up ASC")->select();
                cache($this->key . $param['type'], json_encode($level_list));
            } else {
                $level_list = db('level')->field("*")->where("type=1")->order("level_up ASC")->select();
                cache($this->key, json_encode($level_list));
            }
        }

        if (!is_array($level_list)) {
            $level_list = json_decode($level_list, true);
        }
        return $level_list;
    }

    public function rm($param)
    {
        if (isset($param['type'])) {
            $keys = $this->key . $param['type'];
        } else {
            $keys = $this->key;
        }

        cache($keys, null);
    }

    public function clear_all()
    {
        cache($this->key, null);
    }
}