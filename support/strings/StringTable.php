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
			$current = @$current[$key];
		if (!isset($current))
			throw new Exception(_("Please provide text for ") . "'" . $ident . "'"); 
		return $current;
	}
	
	public function set($ident, $value) {
		$idents = explode(".", $ident);
		$current = &$this->string_table;
		foreach ($idents as $key) {
			if (!@$current[$key])
				$current[$key] = array();
			$current = &$current[$key];
		}
		$current = $value;		
	}
	
	public function exists($ident) {
		return isset($this->string_table[$ident]);
	}
	
}
