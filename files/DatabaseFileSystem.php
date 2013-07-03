<?php

require_once(dirname(__FILE__) . "/FileSystem.php");

Class DatabaseFileSystem extends FileSystem {
	
	private $database;
	
	function __construct($database) {
		$this->database = $database;
	}
	
	public function database() {
		return $this->database;
	}
	
}
