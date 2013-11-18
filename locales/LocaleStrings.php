<?php

Abstract Class LocaleStrings {
	
	private $default;
	
	function __construct($default = "") {
		$this->default = $default;
	}
	
	function defaultFor($key) {
		return sprintf($this->default, $key);
	}
	
	function defaultText() {
		return $this->default;
	}
	
	abstract function find($key);

	function get($key) {
		$value = $this->find($key);
		return isset($value) ? $value : $this->defaultFor($key);
	}
	
}


Class LocaleStringTable extends LocaleStrings {
	
	private $table;
	private $context;
	
	function __construct($default = "", $context = NULL) {
		parent::__construct($default);
		$this->table = array();
		$this->context = $context;		
	}
	
	function find($key) {
		if (!@$key)
			return $this;
		$arr = explode(".", $key, 2);
		$base = @$this->table[$arr[0]];
		if (!@$base)
			return NULL;
		if (count($arr) > 1)
			return $base->get($arr[1]);
		return is_callable($base) ? call_user_func($base, $this->context) : $base;
	}
	
	function set($key, $value) {
		if (!@$key)
			return;
		$arr = explode(".", $key, 2);
		if (count($arr) == 1)
			$this->table[$arr[0]] = $value;
		else {
			if (!@$this->table[$arr[0]])
				$this->table[$arr[0]] = new LocaleStringTable($this->defaultText(), $this->context);
			$this->table[$arr[0]]->set($arr[1], $value);
		}
	}

	function register($data, $prefix = "") {
		foreach ($data as $key => $value)
			$this->set(@$prefix ? $prefix . "." . $key : $key, $value);
	}
	
	function enumerate() {
		return $this->table;
	}
	
}


Class LocaleStringGroup extends LocaleStrings {
	
	private $locale_strings;
	
	function __construct($locale_strings, $default = "") {
		if (!@$default)
			foreach ($locale_strings as $l)
				$default = $default || $l->defaultText();
		parent::__construct($default);
		$this->locale_strings = $locale_strings;
	}
	
	function find($key) {
		for ($i = 0; $i < count($this->locale_strings); ++$i) {
			$result = $this->locale_strings[$i]->find($key);
			if (isset($result))
				return $result;
		}			
		return NULL;
	}	
	
}
