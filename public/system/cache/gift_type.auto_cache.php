<?php

class gift_type_auto_cache
{
    private $key = "gift_type:list";

    public function load($param, $is_real = true)
    {
        $id = intval($param['id']);
        $key =$this->key. ":".$id;
        $gift_list = $GLOBALS['redis']->get($key);
        if ($gift_list === false) {
            $gift_list = db('gift')->alias("g")
                ->field("g.*")
                ->where('g.status = 1 and g.gift_type_id='.$id)
                ->order("g.orderno asc")
                ->select();

            $GLOBALS['redis']->set($key, json_encode($gift_list), 60, true);
        }

        if (!is_array($gift_list)) {
            $gift_list = json_decode($gift_list, true);
        }
        return $gift_list;
    }

    public function rm($id)
    {
        if ($id) {
            $this->key = $this->key. ":".$id;
        }
        $GLOBALS['redis']->del('del', $this->key);
    }

    public function clear_all($id)
    {
        if ($id) {
            $this->key = $this->key. ":".$id;
        }
        $GLOBALS['redis']->rm($this->key);
    }
}

?>