<?php

final class logger {

    private $filename;
    
    private $dir;

    public function __construct($filename,$dir) {
	$this->filename = $filename;
	$this->dir = $dir;
    }

    public function write($message) {
	$file = $dir . $this->filename;

	$handle = fopen($file, 'a+');

	fwrite($handle, date('Y-m-d G:i:s') . ' - ' . $message . "\n");

	fclose($handle);
    }

}

?>
