<?php

class private_letter_gift_auto_cache
{
    private $key = "private_letter_gift:list";

    public function load($param, $is_real = true)
    {
        $gift_list = $GLOBALS['redis']->get($this->key);
        if ($gift_list === false) {
            $gift_list = db('gift')->where('status = 1 and gift_type_id > 1')->order("orderno asc")->select();
            $GLOBALS['redis']->set($this->key, json_encode($gift_list), 60, true);
        }

        if (!is_array($gift_list)) {
            $gift_list = json_decode($gift_list, true);
        }
        return $gift_list;
    }

    public function rm($param)
    {
        $GLOBALS['redis']->del('del',$this->key);
    }

    public function clear_all()
    {
        $GLOBALS['redis']->del('del',$this->key);
    }
}

?>