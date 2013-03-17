<?php

require_once(dirname(__FILE__) . "/DatabaseTable.php");

abstract class Database {
	
	public abstract function selectTable($name);
	
	public function encodeDate($date = NULL) {
		return $date;
	}
	
	public function decodeDate($date = NULL) {
		return $date;
	}

	public function encodePrimaryKey($id) {
		return $id;
	}
	
	public function decodePrimaryKey($id) {
		return $id;
	}

}

