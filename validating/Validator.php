<?php

abstract class Validator {
	
	const STR_REQUIRED_FIELD = "Field is required.";
	
	// Returns an error string in case of an error; otherwise returns NULL
	public abstract function validate($value, $context = NULL);
	
}
