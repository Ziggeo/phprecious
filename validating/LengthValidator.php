<?php

class LengthValidator extends Validator {
	
	private $error_string;
	private $length;
	
	public function __construct($length, $error_string = NULL) {
		$this->length = $length;
		//parent::__construct();
		if ($error_string == NULL)
			$this->error_string = _(self::STR_REQUIRED_FIELD);
		else
			$this->error_string = $error_string;
	}
	
	// Returns an error string in case of an error; otherwise returns NULL
	public function validate($value, $context = NULL) {
		if (isset($value) && is_string($value) && strlen(trim($value)) >= $this->length)
			return NULL;
		return $this->error_string;
	}
	
}
