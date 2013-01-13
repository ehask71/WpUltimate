<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class wpu_wordpress {

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

    public function process() {
	$this->moveFiles();
	$dir = $this->input['data']['homedir'] . '/public_html/';
	if (file_exists("$dir/wp-config-sample.php") && !file_exists("$dir/wp-config.php")) {
	    // Lets DO this!!!
	    $dbinfo = $this->createDatabase();
	    if ($dbinfo) {
		// Now we create the wp-config
		$wpconfig = file_get_contents("$dir/wp-config-sample.php");
		$keys = curl_get_file_contents("https://api.wordpress.org/secret-key/1.1/salt/");
		$start = strpos($wpconfig, "define('AUTH_KEY'");
		$search = substr($wpconfig, $start, 471);
		$wpconfig = str_replace($search, $keys, $wpconfig);
		$wpconfig = str_replace("define('DB_USER', 'username_here');", "define('DB_USER', '" . $dbinfo['dbuser'] . "');", $wpconfig);
		$wpconfig = str_replace("define('DB_PASSWORD', 'password_here');", "define('DB_PASSWORD', '" . $dbinfo['dbpass'] . "');", $wpconfig);
		$wpconfig = str_replace("define('DB_NAME', 'database_name_here');", "define('DB_NAME', '" . $dbinfo['db'] . "');", $wpconfig);
		$wpconfig = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', 'localhost');", $wpconfig);
		$wpconfig = str_replace("\$table_prefix  = 'wp_';", "\$table_prefix  = 'wp_';", $wpconfig);
		
		// Custom Defines
		$defines = "\n/*     Custom Defines     */\n";
		$defines .= "/*       WP-Ultimate      */\n";
		$defines .= "/* http://wp-ultimate.com */\n";
		if (is_array($config['wordpress']['defines'])) {
		    foreach ($config['wordpress']['defines'] AS $k => $v) {
			$defines .= "define('$k','$v'); \n";
		    }
		}
		if ($this->config['wordpress']['setupcron'] == 1) {
		    $defines .= "define('DISABLE_WP_CRON',true);\n";
		}
		$start = strpos($wpconfig, "define('WP_DEBUG', false);");
		$begin = substr($wpconfig, 0, $start);
		$end = substr($wpconfig, $start);
		$wpconfig = $begin . "\n" . $defines. "\n" . $end;
		
		$file = fopen("$dir/wp-config.php", "wb");
		fwrite($file, strtr($wpconfig, array("\r" => "")));
		fclose($file);

		chown("$dir/wp-config.php", $this->input['data']['user']);
		chgrp("$dir/wp-config.php", $this->input['data']['user']);

		// Includes for installer
		chdir($dir . '/wp-admin/');
		define('WP_INSTALLING', true);
		/** Load WordPress Bootstrap */
		require_once($dir . '/wp-load.php' );
		/** Load WordPress Administration Upgrade API */
		require_once($dir . '/wp-admin/includes/upgrade.php' );
		/** Load wpdb */
		require_once($dir . '/wp-includes/wp-db.php');
		$result = wp_install($this->input['data']['domain'], 'admin', $this->input['data']['contactemail'], 1, '', $this->input['data']['pass']);
		// Do some permissions
                system("mkdir ".$dir."wp-content/uploads");
		system("chown -R {$this->input['data']['user']}:{$this->input['data']['user']} {$this->input['data']['homedir']}/* ");
		
		if (!$config['suexec']) {
		    system("chmod 777 ".$dir."wp-content/uploads");
		}
	    }
	}

	if ($this->config['wordpress']['setupcron'] == 1) {
	    $this->setupCron();
	}

	return $result;
    }

    private function moveFiles() {
	$wpskel = (isset($this->config['wordpress']['skel'])) ? $this->config['wordpress']['skel'] : 'wp-skel';
	system("cp -Rpfv {$this->skelhome}/{$wpskel}/.[a-z]* {$this->input['data']['homedir']}/");
	system("cp -Rpfv {$this->skelhome}/{$wpskel}/* {$this->input['data']['homedir']}/");

	return;
    }

    private function createDatabase() {
	$dbname = 'wpu' . time();
	$virtusername = 'wpuse' . time();
	$dbname = substr($dbname, 0, 25);
	$virtusername = substr($virtusername, 0, 7);
	$privs = array('all');
	// Determine Prefixing
	$prefixing = 1;
	$config = file('/var/cpanel/cpanel.config');
	foreach ($config as $key => $value) {
	    if (stripos($value, 'database_prefix=') === 0) {
		$prefixing = substr(trim($value), -1);  // bool/int
	    }
	}
	$dbprefix = (strlen($this->input['data']['user']) > 11) ? substr($this->input['data']['user'], 0, 10) : $this->input['data']['user'];

	if ((int) $prefixing === 0) {
	    // prefixing is off
	    $db = $dbprefix . '_' . $dbname;
	    $dbuser = $dbprefix . '_' . $virtusername;
	} else {
	    // prefixing is ON, default on all cpanel systems
	    $db = $dbname;
	    $dbuser = $virtusername;
	}

	$dbpass = $this->generatePassword(10, 8);

	$args = array($db);
	$this->api->api1_query($this->input['data']['user'], 'Mysql', 'adddb', $args);
	// Create User
	$args = array($dbuser, $dbpass);
	$this->api->api1_query($this->input['data']['user'], 'Mysql', 'adduser', $args);
	// Assign User to DB
	$privs = (!isset($this->config['wordpress']['privs'])) ? array('all') : $privs; //not the best, you can change the default if you wish
	$priv_str = implode(',', $privs);
	$args = array($db, $dbuser, $priv_str);
	$this->api->api1_query($this->input['data']['user'], 'Mysql', 'adduserdb', $args);

	return array('db' => $dbprefix . '_' . $dbname, 'dbpass' => $dbpass, 'dbuser' => $dbprefix . '_' . $virtusername);
    }

    public function setupCron() {
	$command = "/usr/bin/wget -O - -q -t 1 http://{$this->input['data']['domain']}/wp-cron.php?doing_wp_cron >/dev/null 2>&1";
	$args = array(
	    'command' => $command,
	    'day' => '*',
	    'hour' => '*',
	    'minute' => '*/30',
	    'month' => '*',
	    'weekday' => '*',
	);

	//$this->api->set_debug(1);
	$this->api->api2_query($this->input['data']['user'], 'Cron', 'add_line', $args);

	return;
    }

    public function generatePassword($length = 9, $strength = 0) {
	$vowels = 'aeuy';
	$consonants = 'bdghjmnpqrstvz';
	if ($strength & 1) {
	    $consonants .= 'BDGHJLMNPQRSTVWXZ';
	}
	if ($strength & 2) {
	    $vowels .= "AEUY";
	}
	if ($strength & 4) {
	    $consonants .= '23456789';
	}
	if ($strength & 8) {
	    $consonants .= '@#$%';
	}

	$password = '';
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {
	    if ($alt == 1) {
		$password .= $consonants[(rand() % strlen($consonants))];
		$alt = 0;
	    } else {
		$password .= $vowels[(rand() % strlen($vowels))];
		$alt = 1;
	    }
	}
	return $password;
    }

}

?>
