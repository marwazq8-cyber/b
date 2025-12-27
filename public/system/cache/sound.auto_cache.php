<?php

class sound_auto_cache
{
    private $key = "sound:list";

    public function load($param, $is_real = true)
    {
        $voice_sound = $GLOBALS['redis']->get($this->key);
        if ($voice_sound === false) {
            $voice_sound = db('voice_sound')->order("sort desc")->select();
            $GLOBALS['redis']->set($this->key, json_encode($voice_sound), 60, true);
        }

        if (!is_array($voice_sound)) {
            $voice_sound = json_decode($voice_sound, true);
        }
        return $voice_sound;
    }

    public function rm($param)
    {
        $GLOBALS['cache']->rm($this->key);
    }

    public function clear_all()
    {
        $GLOBALS['cache']->rm($this->key);
    }
}

?>