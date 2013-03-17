<?php

require_once(dirname(__FILE__) . "/../DatabaseTable.php");

class MongoDatabaseTable extends DatabaseTable {
	
	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("database");
			else
				$PERFMON->leave("database");
		}
	}

	private $collection;
	
	private function getCollection() {
		if (!$this->collection) {
			static::perfmon(true);
			$this->collection = $this->getDatabase()->getDatabase()->selectCollection($this->getTablename());
			static::perfmon(false);
		}
		return $this->collection;
	}
	
	public function primaryKey() {
		return "_id";
	}
	
	public function insert(&$row, $options = array("safe" => TRUE, /*"fsync" => TRUE*/)) {
		static::perfmon(true);
		//TODO: Why do I have to create a new mongo id?
        $row[$this->primaryKey()] = new MongoId();
		//unset($row["_id"]);
		$success = $this->getCollection()->insert($row, $options);
        if (@$options["safe"] or @$options["fsync"])
        	$success = $success["ok"];
		static::perfmon(false);
		return $success;
	}
	
	public function find($values, $options = NULL) {
		static::perfmon(true);
		$result = $this->getCollection()->find($values);
		if (@$options) {
			if (@$options["sort"])
				$result = $result->sort($options["sort"]);
			if (@$options["limit"])
				$result = $result->limit($options["limit"]);
		}
		static::perfmon(false);
		return $result;
	}
	
	public function findOne($values) {
		static::perfmon(true);
		$result = $this->getCollection()->findOne($values);
		static::perfmon(false);
		return $result;
	}
	
	public function update($query, $update, $options = array("safe" => TRUE)) { // "multiple" => false
		if(count($update) == 0)
			return false;
		static::perfmon(true);
		$success = $this->getCollection()->update($query, array('$set' => $update), $options);
        if (@$options["safe"] or @$options["fsync"])
        	$success = $success["ok"];
		static::perfmon(false);
		return $success;
	}
	
	public function updateOne($query, $update, $options = array("safe" => TRUE)) {
		$options["multiple"] = FALSE;
		return $this->update($query, $update, $options);
	}
	
	public function remove($query, $options = array("safe" => TRUE)) { // "justOne" => true
		static::perfmon(true);
		$success = $this->getCollection()->remove($query, $options);
        if (@$options["safe"] or @$options["fsync"])
        	$success = $success["ok"];
		static::perfmon(false);
		return $success;
	}
	
	public function removeOne($query, $options = array("safe" => TRUE)) {
		$options["justOne"] = TRUE;
		return $this->remove($query, $options);
	}
	
	public function ensureIndex($keys) {
		$arr = array();
		foreach($keys as $key)
			$arr[$key] = 1;
		return $this->getCollection()->ensureIndex($arr);
	}
		
	
}
