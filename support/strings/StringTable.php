<?php

Class StringTable {
	
	private $string_table;
	
	public function __construct($string_table) {
		$this->string_table = $string_table;
	}
	
	public function get($ident) {
		$idents = explode(".", $ident);
		$current = $this->string_table;
		foreach ($idents as $key) 
			$current = @$current[$key];
		if (@!$current)
			$current = _("Please provide text for ") . "'" . $ident . "'"; 
		return $current;
	}
	
}
