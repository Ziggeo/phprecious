<?php

Class LocaleStrings {
	
	private $table;
	private $default;
	
	function __construct($default = "") {
		$this->table = array();
		$this->default = $default;
	}
	
	function get($key) {
		if (!@$key)
			return $this;
		$arr = explode(".", $key, 2);
		$base = $this->table[$arr[0]];
		if (!@$base)
			return sprintf($this->default, $key);
		if (count($arr) > 1)
			return $base->get($arr[1]);
		return $base;
	}
	
	function set($key, $value) {
		if (!@$key)
			return;
		$arr = explode(".", $key, 2);
		if (count($arr) == 1)
			$this->table[$arr[0]] = $value;
		else {
			if (!@$this->table[$arr[0]])
				$this->table[$arr[0]] = new LocaleStrings($this->default);
			$this->table[$arr[0]]->set($arr[1], $value);
		}
	}

	function register($data, $prefix = "") {
		foreach ($data as $key => $value)
			$this->set(@$prefix ? $prefix . "." . $key : $key, $value);
	}
	
}
