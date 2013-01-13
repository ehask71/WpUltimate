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

    public function __construct($config, $input, $license, xmlapi $api) {
	$this->config = $config;
	$this->input = $input;
	$this->license = $license;
	$this->api = $api;
	$this->skelhome = $this->config['skelhome'];
    }

    public function process(){
        system("cp -Rpfv {$this->skelhome}/skeleton/.[a-z]* {$this->input['data']['homedir']}/ ");
	system("cp -Rpfv {$this->skelhome}/skeleton/* {$this->input['data']['homedir']}/ ");
	
	$this->setPermissions();
    }
    
    private function setPermissions(){
	system("chown -R {$this->input['data']['user']}:{$this->input['data']['user']} {$this->input['data']['homedir']}/* ");
    }
}
?>