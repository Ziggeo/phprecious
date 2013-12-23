<?php

require_once(dirname(__FILE__) . "/../database/memory/MemoryDatabase.php");
require_once(dirname(__FILE__) . "/../modelling/models/DatabaseModel.php");

$database = NULL;

Class TestModel extends DatabaseModel {
	
	protected static function getDatabase() {
		global $database;
		return $database;
	}
	
}

Class TestRemovableModel extends TestModel {
	
	protected static function initializeOptions() {
		return array(
			"exceptions" => FALSE,
			"remove_field" => "removed"
		);
	}
	
}


class DatabaseModelTest extends PHPUnit_Framework_TestCase {
	
	public function testInsertQueryRemove() {
		global $database;
		$database = new MemoryDatabase();		
		$this->assertEquals(TestModel::count(), 0);
		$object = new TestModel();
		$object->save();
		$this->assertEquals(TestModel::count(), 1);
		$this->assertTrue(TestModel::findById($object->id()) != NULL);
		$this->assertFalse(TestModel::findById(42) != NULL);
		$object->delete();
		$this->assertFalse(TestModel::findById($object->id()) != NULL);
		$this->assertEquals(TestModel::count(), 0);
		$this->assertEquals(TestRemovableModel::count(array(), TRUE), 0);
	}
	
	public function testRemovableObject() {
		global $database;
		$database = new MemoryDatabase();		
		$this->assertEquals(TestRemovableModel::count(), 0);
		$object = new TestRemovableModel();
		$object->save();
		$this->assertEquals(TestRemovableModel::count(), 1);
		$this->assertTrue(TestRemovableModel::findById($object->id()) != NULL);
		$this->assertFalse(TestRemovableModel::findById(42) != NULL);
		$object->delete();
		$this->assertFalse(TestRemovableModel::findById($object->id()) != NULL);
		$this->assertEquals(TestRemovableModel::count(), 0);
		$this->assertEquals(TestRemovableModel::count(array(), TRUE), 1);
		$this->assertTrue(TestRemovableModel::findById($object->id(), TRUE) != NULL);
		$object->delete(TRUE);
		$this->assertEquals(TestRemovableModel::count(array(), TRUE), 0);
		$this->assertFalse(TestRemovableModel::findById($object->id(), TRUE) != NULL);
	}

}

