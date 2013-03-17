<?php

class ConditionalValidator extends Validator {
	
	private $condition;
	private $validator;
	
	public function __construct($condition, $validator) {
		//parent::__construct();
		$this->condition = $condition;
		$this->validator = $validator;
	}
	
	public function validate($value, $context = NULL) {
		$condition = $this->condition;
		$validator = $this->validator;
		if (!$condition($context, $value))
			return NULL;
		return $validator->validate($value);
	}
	
}
