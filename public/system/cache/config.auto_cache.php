<?php

class config_auto_cache{
	private $key = "config:list";
	public function load($param,$is_real=true)
	{
		$m_config = $GLOBALS['redis']->get($this->key);
		if($m_config === false)
		{
			$m_config = array();
            $list = db('config') -> select();
			foreach($list as $item){
				$m_config[$item['code']] = $item['val'];
			}
			$GLOBALS['redis']->set($this->key,json_encode($m_config),20);
		}

		if(!is_array($m_config)){
            $m_config = json_decode($m_config,true);
        }
		return $m_config;
	}
	
	public function rm($param)
	{
		$GLOBALS['cache']->rm($this->key);
	}
	
	public function clear_all()
	{
        $GLOBALS['redis']->del('del', $this->key);
	}
}
?>