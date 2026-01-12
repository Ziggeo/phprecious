<?php

require_once(dirname(__FILE__) . "/../support/data/ArrayUtils.php");

class ArraySupportTest extends PHPUnit\Framework\TestCase {

	
	public function testFilterArray() {
		$result = ArrayUtils::filter(array("a" => 1, "b" => 2, "c" => 3), array("a", "c", "d"));
		$this->assertEquals(count($result), 2);
		$this->assertEquals($result["a"], 1);
		$this->assertEquals($result["c"], 3);
	}
	
}

