<?php

class RegexValidator extends Validator {
	
	private $error_string;
	private $regex;
	
	public function __construct($regex, $error_string = NULL) {
		$this->regex = $regex;
		//parent::__construct();
		if ($error_string == NULL)
			$this->error_string = _(self::STR_REQUIRED_FIELD);
		else
			$this->error_string = $error_string;
	}
	
	// Returns an error string in case of an error; otherwise returns NULL
	public function validate($value, $context = NULL) {
		if (isset($value) && is_string($value) && preg_match($this->regex, $value))
			return NULL;
		return $this->error_string;
	}
	
}
