<?php

class visualize_auto_cache{
	private $key = "visualize:list";
	public function load($param,$is_real=true)
	{
        $visualize_list = $GLOBALS['redis']->get($this->key);
		if($visualize_list === false)
		{
			 $visualize_list =db('visualize_table')->field("id,visualize_name,color")->order("sort desc")->select();
			$GLOBALS['redis']->set($this->key,json_encode($visualize_list),20,true);
		}

		if(!is_array($visualize_list)){
            $visualize_list = json_decode($visualize_list,true);
        }
		return $visualize_list;
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