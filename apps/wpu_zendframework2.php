<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class wpu_zendframework2 {
    
    protected $api;
    
    protected $config;
    
    protected $input;
    
    protected $license;
    
    private $skelhome;

    public function __construct(Registry $registry,xmlapi $api) {
        $this->config = $registry->get('config');
	$this->input = $registry->get('input');
	$this->license = $registry->get('license');
	$this->api = $api;
	$this->skelhome = $this->config['skelhome'];
    }
    
    public function process(){
        system("cp -Rpfv {$this->skelhome}/zf-skel/.[a-z]* {$this->input['data']['homedir']}/ ");
	system("cp -Rpfv {$this->skelhome}/zf-skel/* {$this->input['data']['homedir']}/ ");
	
	$this->setPermissions();
    }
    
    private function setPermissions(){
	system("chown -R {$this->input['data']['user']}:{$this->input['data']['user']} {$this->input['data']['homedir']}/* ");
    }
}
?>
