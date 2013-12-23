<?php

abstract class DatabaseTable {
	
	private $database;
	private $tablename;
	
    public function __construct($database, $tablename) {
    	$this->database = $database;
    	$this->tablename = strtolower($tablename);
    }
	
	public function getDatabase() {
		return $this->database;
	}
	
	public function getTablename() {
		return $this->tablename;
	}
	
	public abstract function primaryKey();

	public abstract function insert(&$row);
	
	public abstract function find($values, $options = NULL);
	
	public function count($values) {
		return count($this->find($values));
	}
	
	public function findSort($values, $sort) {
		return $this->find($values, array("sort" => $sort));
	}
	
	public function findGroupBy($values, $group, $sort = NULL) {
		$result = @$sort ? $this->findSort($values, $sort) : $this->find($values);
		$arr = array();
		foreach ($result as $entry) {
			if (@!$arr[$entry[$group]])
				$arr[$entry[$group]] = array();
			$arr[$entry[$group]][] = $entry;
		}
		return $arr;
	}
	
	public function findOne($values) {
		$arr = $this->find($values);
		return (count($arr) > 0) ? $arr[0] : NULL;
	}
	
	public function findRow($id) {
		return $this->findOne(array($this->primaryKey() => $this->database->encode("id", $id)));
	}
	
	public abstract function update($query, $update);
	
	public abstract function updateOne($query, $update);
	
	public function updateRow($id, $update) {
		return $this->updateOne(array($this->primaryKey() => $this->database->encode("id", $id)), $update);
	}
	
	public function incrementCell($id, $key, $value) {
		$row = $this->findRow($id);
		if (!@$row)
			return FALSE;
		$new_value = @$row[$key] ? $row[$key] + $value : $value;
		return $this->updateRow($id, array($key => $new_value));
	}
	
	public abstract function remove($query);
	
	public abstract function removeOne($query);
	
	public function removeRow($id) {
		return $this->removeOne(array($this->primaryKey() => $this->database->encode("id", $id)));
	}
	
	public function ensureIndex($keys) {}

}

