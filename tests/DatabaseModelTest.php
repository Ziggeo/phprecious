<?php

require_once(dirname(__FILE__) . "/../database/memory/MemoryDatabase.php");
require_once(dirname(__FILE__) . "/../modelling/models/DatabaseModel.php");

$database = NULL;

Class DatabaseModelTestModel extends DatabaseModel {
	
	protected static function getDatabase() {
		global $database;
		return $database;
	}
	
}

Class DatabaseModelTestRemovableModel extends DatabaseModelTestModel {
	
	protected static function initializeOptions() {
		return array(
			"exceptions" => FALSE,
			"remove_field" => "removed"
		);
	}
	
}


class DatabaseModelTest extends PHPUnit\Framework\TestCase {
	
	public function testInsertQueryRemove() {
		global $database;
		$database = new MemoryDatabase();		
		$this->assertEquals(DatabaseModelTestModel::count(), 0);
		$object = new DatabaseModelTestModel();
		$object->save();
		$this->assertEquals(DatabaseModelTestModel::count(), 1);
		$this->assertTrue(DatabaseModelTestModel::findById($object->id()) != NULL);
		$this->assertFalse(DatabaseModelTestModel::findById(42) != NULL);
		$object->delete();
		$this->assertFalse(DatabaseModelTestModel::findById($object->id()) != NULL);
		$this->assertEquals(DatabaseModelTestModel::count(), 0);
		$this->assertEquals(DatabaseModelTestRemovableModel::count(array(), TRUE), 0);
	}
	
	public function testRemovableObject() {
		global $database;
		$database = new MemoryDatabase();		
		$this->assertEquals(DatabaseModelTestRemovableModel::count(), 0);
		$object = new DatabaseModelTestRemovableModel();
		$object->save();
		$this->assertEquals(DatabaseModelTestRemovableModel::count(), 1);
		$this->assertTrue(DatabaseModelTestRemovableModel::findById($object->id()) != NULL);
		$this->assertFalse(DatabaseModelTestRemovableModel::findById(42) != NULL);
		$object->delete();
		$this->assertFalse(DatabaseModelTestRemovableModel::findById($object->id()) != NULL);
		$this->assertEquals(DatabaseModelTestRemovableModel::count(), 0);
		$this->assertEquals(DatabaseModelTestRemovableModel::count(array(), TRUE), 1);
		$this->assertTrue(DatabaseModelTestRemovableModel::findById($object->id(), TRUE) != NULL);
		$object->delete(TRUE);
		$this->assertEquals(DatabaseModelTestRemovableModel::count(array(), TRUE), 0);
		$this->assertFalse(DatabaseModelTestRemovableModel::findById($object->id(), TRUE) != NULL);
	}

}

