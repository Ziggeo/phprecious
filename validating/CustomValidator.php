<?php

class CustomValidator extends Validator {
	
	private $func;
	
	public function __construct($func) {
		//parent::__construct();
		$this->func = $func;
	}
	
	public function validate($value, $context = NULL) {
		$func = $this->func;
		return $func($context, $value);
	}
	
}
