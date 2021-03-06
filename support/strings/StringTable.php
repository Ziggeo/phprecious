<?php

Class StringTable {
	
	private $string_table;
	
	function __construct($string_table = array()) {
		$this->string_table = $string_table;
	}
	
	public function get($ident) {
		$idents = explode(".", $ident);
		$current = $this->string_table;
		foreach ($idents as $key) 
			$current = isset($current) && isset($current[$key]) ? $current[$key] : NULL;
		if (!isset($current))
			return NULL; 
		return $current;
	}
	
	public function set($ident, $value) {
		$idents = explode(".", $ident);
		$current = &$this->string_table;
		foreach ($idents as $key) {
			if (!isset($current[$key]))
				$current[$key] = array();
			$current = &$current[$key];
		}
		$current = $value;		
	}
	
	public function exists($ident) {
		return $this->get($ident) != NULL;
	}
	
	public function table() {
		return $this->string_table;
	}
	
	public function setAll($arr) {
		foreach ($arr as $ident=>$value)
			$this->set($ident, $value);
	}
	
	public function setFromJSON($json) {
		$this->setAll(json_decode($json, TRUE));
	}
	
	public function setFromFile($filename) {
		$this->setFromJSON(file_get_contents($filename));
	}
	
}
