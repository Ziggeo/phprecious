<?php

/* A completely unoptimized, unindexed database in memory. Mostly for unit testing modelling */

require_once(dirname(__FILE__) . "/../Database.php");
require_once(dirname(__FILE__) . "/MemoryDatabaseTable.php");

class MemoryDatabase extends Database {
	
	private $tables = array();

    public function __construct() {
    }
	
    public function selectTable($name) {
    	if (!isset($this->tables[$name]))
			$this->tables[$name] = new MemoryDatabaseTable($this, $name);
		return $this->tables[$name];
    }
	
}

