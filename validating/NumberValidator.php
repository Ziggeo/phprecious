<?php

class NumberValidator extends Validator {
	
	private $error_string;
	private $min_value;
	private $max_value;
	
	public function __construct($min_value = NULL, $max_value = NULL, $error_string = NULL) {
		$this->min_value = $min_value;
		$this->max_value = $max_value;
		if ($error_string == NULL)
			$this->error_string = _(self::STR_REQUIRED_FIELD);
		else
			$this->error_string = $error_string;
	}
	
	public function validate($value, $context = NULL) {
		if (isset($value) && is_numeric($value) && (!isset($this->min_value) || $this->min_value <= $value) && (!isset($this->max_value) || $this->max_value >= $value))
			return NULL;
		return $this->error_string;
	}
	
}
