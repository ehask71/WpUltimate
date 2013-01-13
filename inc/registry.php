<?php

final class Registry {

    private $data = array();

    public function get($key) {
	return (isset($this->data[$key]) ? $this->data[$key] : NULL);
    }

    public function set($key, $value) {
	$this->data[$key] = $value;
    }

    public function has($key) {
	return isset($this->data[$key]);
    }
    
    public function getPassedData($key){
	return (isset($this->data['arg'][$key]))? $this->data['arg'][$key] : NULL;
    }
    
    public function setPassedData($data){
	foreach ($data AS $k=>$v){
	    $this->data['arg'][$k] = $v;
	}
    }
}