<?php

require_once(dirname(__FILE__) . "/../support/strings/ParseType.php");

class ParseTypeTest extends PHPUnit\Framework\TestCase {
	
	public function testBool() {
		$this->assertTrue(ParseType::parseBool(1));
		$this->assertTrue(ParseType::parseBool(TRUE));
		$this->assertTrue(ParseType::parseBool("on"));
		$this->assertTrue(ParseType::parseBool("yes"));
		$this->assertTrue(ParseType::parseBool("true"));
		$this->assertFalse(ParseType::parseBool(0));
		$this->assertFalse(ParseType::parseBool(FALSE));
		$this->assertFalse(ParseType::parseBool("off"));
		$this->assertFalse(ParseType::parseBool("no"));
		$this->assertFalse(ParseType::parseBool("false"));
		$this->assertFalse(ParseType::parseBool(NULL));
	}
	
}

