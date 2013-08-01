<?php

require_once(dirname(__FILE__) . "/DatabaseTable.php");

abstract class Database {
	
	public abstract function selectTable($name);
	
	public function encode($type, $value) {
		return $value;
	}
	
	public function decode($type, $value) {
		return $value;
	}
	
}

