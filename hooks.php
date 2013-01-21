#!/usr/bin/php -q
<?php
require 'xmlapi.php';
require 'utils.php';
require 'inc/registry.php';

$switches = (count($argv) > 1) ? $argv : array();

// Do whatever hook action is desired
if (in_array('--describe', $switches)) {
    echo json_encode(describe());
    exit;
} elseif (in_array('--create', $switches)) {
    list($status, $msg) = create();
    echo "$status $msg";
    exit;
} else {
    echo '0 wpu/hooks.php needs a valid switch';
    exit(1);
}

/**
 * 
 * 
 */
function create() {
    $registry = new Registry();
    $input = get_passed_data();
    $registry->set('input', $input);
    $config = parseConfig('wpu.conf');
    $registry->set("config", $config);
    $license = getLicenseData($config['license']);
    $registry->set("license", $license);
    
    $error = false;
    $debug = false;
    if($config['debug']){
	require_once 'inc/logger.php';
	$debug = new logger($config['debug_log'], $config['log_path'].'/');
	$registry->set("debug", $debug);
    }
    // Base Results 
    $results = array('status'=> "0",'msg' => 'General Failure');
    // Check License
    if($debug){
	$debug->write("License ".$license['status']);
    }
    if ($license['status'] == 'success' && $license['islicensed'] == 'true') {
	// Setup CPanel API
	$xmlapi = new xmlapi('127.0.0.1');
	$xmlapi->set_user($config['apiuser']);
	if($config['apipasswd'] != ''){
	    $xmlapi->set_password($config['apipasswd']);
	} else {
	    $hash = ($config['apihash'] != '')?$config['apihash']:file_get_contents('/root/.accesshash');
	    $xmlapi->set_hash($hash);
	}
	if($debug){
	    $debug->write("Init CPanel Api");
	}
	// Get HomeDir
	$input['data']['homedir'] = getHomeDir($input['data']['domain'], $input['data']['user']);
	// WP = Wordpress  Zf = Zend Framework  OC = OpenCart
	$action = false;
	if ($config['useprefix'] == 1) {
	    // Prefix Base
	    foreach($config['prefix'] AS $k=>$v){
		$action = (strpos($input['data']['plan'], $v) !== false)?$k:false;
		if($action)
		    break;
	    }
	} 
	if ($config['useplans'] == 1) {
	    // Plan Based
	    foreach ($config['plans'] AS $k=>$v){
		$action = ($input['data']['plan'] == $k)?$v:false;
		if($action)
		    break;
	    }
	}
	if($action){	    
	    // We have a hit
	    if(strpos($action,'skeleton')){
		$config['skeleton_number'] = (int)str_replace("skeleton", "", $action);
		$registry->set('skeleton_number', $config['skeleton_number']);
		if($debug){
		    $debug->write("Skeleton Number: ".$config['skeleton_number']);
		}
		$action = 'skeleton';
	    }
	    $action = 'wpu_'.$action;
	    if($debug){
		$debug->write("Action: ".$action);
	    }
	    include_once 'apps/'.$action.'.php';
	    
	    $class = new $action($registry,$xmlapi);
	    $results = $class->process();
	    
	}
    } else {
	// License Failed
	if($debug){
	    $debug->write("License Failure");
	}
	$results['msg'] = "License Failure";
    }
    
    if($debug){
	$debug->write("Return Status:".$results['status']."~".$results['msg']);
	$debug->write("Finished");
    }
    return array($results['status'], $results['msg']);
}

function describe() {
    $wpu_create = array(
	'category' => 'Whostmgr',
	'event' => 'Accounts::Create',
	'stage' => 'post',
	'hook' => '/etc/wpu/hooks.php --create',
	'exectype' => 'script',
    );

    return array($wpu_create);
}
?>
