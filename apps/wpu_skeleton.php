<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class wpu_skeleton {
    
    protected $api;
    protected $config;
    protected $input;
    protected $license;
    private $skelhome;
    private $skelnum;

    public function __construct(Registry $registry, xmlapi $api) {
	$this->config = $registry->get('config');
	$this->input = $registry->get('input');
	$this->license = $registry->get('license');
	$this->api = $api;
	$this->skelhome = $this->config['skelhome'];
	$this->skelnum = (int)$registry->get('skeleton_number');
    }

    public function process(){
	if(file_exists($this->skelhome."/skeleton".$this->skelnum."/")){
	    system("cp -Rpfv {$this->skelhome}/skeleton".$this->skelnum."/.[a-z]* {$this->input['data']['homedir']}/ ");
	    system("cp -Rpfv {$this->skelhome}/skeleton".$this->skelnum."/* {$this->input['data']['homedir']}/ ");
	
	    $this->setPermissions();
	}
    }
    
    private function setPermissions(){
	system("chown -R {$this->input['data']['user']}:{$this->input['data']['user']} {$this->input['data']['homedir']}/* ");
    }
}
?>
