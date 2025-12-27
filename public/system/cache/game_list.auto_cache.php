<?php

class game_list_auto_cache
{
    private $key = "game_list:list";

    public function load($param, $is_real = true)
    {
        $game_list = $GLOBALS['redis']->get($this->key);
        if ($game_list === false) {
            $game_list =db('game_list')->where('status=1')->order("sort desc")->select();
            $GLOBALS['redis']->set($this->key, json_encode($game_list), 60, true);
        }

        if (!is_array($game_list)) {
            $game_list = json_decode($game_list, true);
        }
        return $game_list;
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