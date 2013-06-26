<?php

class LengthValidator extends Validator {
	
	private $error_string;
	private $min_length;
	private $max_length;
	
	public function __construct($min_length = NULL, $max_length = NULL, $error_string = NULL) {
		$this->min_length = $min_length;
		$this->max_length = $max_length;
		//parent::__construct();
		if ($error_string == NULL)
			$this->error_string = _(self::STR_REQUIRED_FIELD);
		else
			$this->error_string = $error_string;
	}
	
	// Returns an error string in case of an error; otherwise returns NULL
	public function validate($value, $context = NULL) {
		if (isset($this->max_length) && isset($value) && is_string($value) && strlen(trim($value)) > $this->max_length)
			return $this->error_string;
		if (isset($this->min_length) && (!isset($value) || !is_string($value) || strlen(trim($value)) < $this->min_length))
			return $this->error_string;
		return NULL;
	}
	
}
