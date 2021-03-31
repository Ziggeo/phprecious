<?php

require_once(dirname(__FILE__) . "/DatabaseTable.php");

abstract class Database {
	
	public abstract function selectTable($name, $definition = NULL);
	
	public function encode($type, $value) {
		return $value;
	}
	
	public function decode($type, $value) {
		return $value;
	}
	
}

