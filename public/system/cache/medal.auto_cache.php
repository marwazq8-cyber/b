<?php

class medal_auto_cache{
	private $key = "medal:list";
	public function load($param,$is_real=true)
	{
		$medal = $GLOBALS['redis']->get($this->key);
		if($medal == false){
			
            $medal = db('medal') -> select();
			$GLOBALS['redis']->set($this->key,json_encode($medal),20,true);
		}

		if(!is_array($medal)){
            $medal = json_decode($medal,true);
        }
		return $medal;
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