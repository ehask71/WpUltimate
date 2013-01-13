<?php

/**
 * Read the STDIN stream
 * Data passed to this script will be a JSON serialized structure
 */
function get_passed_data() {
    $raw_data;
    $stdin_fh = fopen('php://stdin', 'r');
    if (is_resource($stdin_fh)) {
	stream_set_blocking($stdin_fh, 0);
	while (($line = fgets($stdin_fh, 1024)) !== false) {
	    $raw_data .= trim($line);
	}
	fclose($stdin_fh);
    }
    if ($raw_data) {
	$input_data = json_decode($raw_data, true);
    } else {
	$input_data = array('context' => array(), 'data' => array(), 'hook' => array());
    }
    return $input_data;
}

/**
 * getHomeDir
 * 
 * @param string $dom
 * @param string $user
 * @return mixed
 */
function getHomeDir($dom, $user) {
    $home = false;
    $file = '/var/cpanel/userdata/' . $user . '/' . $dom;
    mail('ehask71@gmail.com', 'getHome', $file);
    $file_handle = fopen($file, 'r');
    while (!feof($file_handle)) {
	$line = fgets($file_handle);
	if (strstr($line, 'homedir')) {
	    $parts = explode(":", $line);
	    $home = trim($parts[1]);
	}
    }
    fclose($file_handle);
    return $home;
}

function parseConfig($filename) {
    $config = array();
    $filename = '/etc/wpu/' . $filename; 
    foreach (file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
	if (!preg_match('/^(.+?)=(.*)$/', $line, $matches)) {
	    continue;
	}
	$indices = explode('.', $matches[1]);
	$current = &$config;
	foreach ($indices as $index) {
	    $current = &$current[$index];
	}
	$current = $matches[2];
    }

    return $config;
}

function getLicenseData($lic) {
    $args = array('key' => $lic);
    $json = file_get_contents("http://api.rackspeed.net/?method=check-license&args=".  json_encode($args));
    $res = json_decode($json, 1);
    if($res['status'] != 'fail'){
	return $res;
    }
    return false;
}

function curl_get_file_contents($URL) {
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_URL, $URL);
	$contents = curl_exec($c);
	curl_close($c);
	if ($contents) return $contents;
	else return false;
}
?>
