<?php

class ValueValidator extends Validator {
	
	const STR_ALLOWED_VALUES = "Allowed values are";
	
	private $error_string;
	private $values;
	
	
	public function __construct($values, $error_string = NULL) {
		$this->values = $values;
		//parent::__construct();
		if ($error_string == NULL)
			$this->error_string = _(self::STR_ALLOWED_VALUES) . ": " . json_encode($values);
		else
			$this->error_string = $error_string;
	}
	
	// Returns an error string in case of an error; otherwise returns NULL
	public function validate($value, $context = NULL) {
		if (!in_array($value, $this->value))
			return $this->error_string;
		return NULL;
	}
	
}
