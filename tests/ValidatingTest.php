<?php

function _($s) { return $s; }

require_once(dirname(__FILE__) . "/../validating/Validator.php");
require_once(dirname(__FILE__) . "/../validating/LengthValidator.php");
require_once(dirname(__FILE__) . "/../validating/NumberValidator.php");

class ValidatingTest extends PHPUnit_Framework_TestCase {
	
	public function testLengthValidator() {
		$val = new LengthValidator(5);
		$this->assertTrue($val->validate("abcde") == NULL);
		$this->assertTrue($val->validate("abcdef") == NULL);
		$this->assertFalse($val->validate("abcd") == NULL);
		$this->assertFalse($val->validate("") == NULL);
		$this->assertFalse($val->validate(NULL) == NULL);
	}
	
	public function testNumberValidator() {
		$val = new NumberValidator(2, 8);
		$this->assertTrue($val->validate(2) == NULL);
		$this->assertTrue($val->validate("5") == NULL);
		$this->assertTrue($val->validate(8) == NULL);
		$this->assertFalse($val->validate(1) == NULL);
		$this->assertFalse($val->validate(9) == NULL);
		$this->assertFalse($val->validate(-1) == NULL);
		$this->assertFalse($val->validate(0) == NULL);
		$this->assertFalse($val->validate("10") == NULL);
		$this->assertFalse($val->validate(NULL) == NULL);
		$this->assertFalse($val->validate("abc") == NULL);
	}
}

