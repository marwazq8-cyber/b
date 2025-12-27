<?php

class income_level_auto_cache{
	private $key = "income_level:list";
	public function load($param,$is_real=true)
	{
        $level_list = $GLOBALS['redis']->get($this->key);
		if($level_list === false)
		{
             $level_list = db('level')->field("level_name,chat_icon")->where("type=2")->order("sort desc") -> select();
			
			$GLOBALS['redis']->set($this->key,json_encode($level_list),20,true);
		}

		if(!is_array($level_list)){
            $level_list = json_decode($level_list,true);
        }
		return $level_list;
	}
	
	public function rm($param)
	{
		$GLOBALS['redis']->rm($this->key);
	}
	
	public function clear_all()
	{
		$GLOBALS['redis']->rm($this->key);
	}
}
?>