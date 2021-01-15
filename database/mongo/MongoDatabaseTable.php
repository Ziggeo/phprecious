<?php

require_once(dirname(__FILE__) . "/../DatabaseTable.php");
require_once(dirname(__FILE__) . "/ResilientMongoIterator.php");
require_once(dirname(__FILE__) . "/../../support/sys/MemoryManager.php");

class MongoDatabaseTable extends DatabaseTable {

	const MEMORY_LIMIT_THRESHOLD = 2; //In MB

    private static $GC_BASELINE = -1;

	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("database");
			else
				$PERFMON->leave("database");
		}
		/*
		 * Putting this here because it's used by most of the query functions.
		 *
		 */
        self::$GC_BASELINE = MemoryManager::gc_collect_threshold_baseline(self::MEMORY_LIMIT_THRESHOLD * 1024 * 1024, self::$GC_BASELINE);
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
	
	private function updateOptions($options) {
		if (isset($options["safe"]) && class_exists("\MongoDB\Client")) {
			$options["w"] = $options["safe"];
			unset($options["safe"]);
		}
		return $options;
	}
	
	public function insert(&$row, $options = array("safe" => TRUE, /*"fsync" => TRUE*/), $resilience = 5) {
        $options = $this->updateOptions($options);
        $options = $this->sanitizeOptions($options);
        static::perfmon(true);
        if ($this->primaryKey() === "_id")
            unset($row["_id"]);
        else
            $row[$this->primaryKey()] = new MongoDB\BSON\ObjectID();
        $success = NULL;
        while (TRUE) {
            $resilience--;
            try {
                $success = $this->getCollection()->insertOne($row, $options);
                break;
            } catch (MongoDB\Driver\Exception\RuntimeException $e) {
                // duplicate key _id_
                if (strpos($e->getMessage(), "E11000") === FALSE || $resilience < 0)
                    throw $e;
            }
        }
        if ((isset($options["safe"]) && $options["safe"]) || (isset($options["fsync"]) && $options["fsync"]) || (isset($options["w"]) && $options["w"]) || $success->isAcknowledged()) {
            if ($this->primaryKey() === "_id")
                $row["_id"] = $success->getInsertedId();
            $success = $success->isAcknowledged();
        }
		static::perfmon(false);
		return $success;
	}
	
	public function find($values, $options = NULL) {
		static::perfmon(true);
        $options = $this->sanitizeOptions($options);
        // to make it "easy" we currently do only support queries where the _id is part of the sort.
        $resilientIteratorSupported = isset($options["sort"]) && isset($options["sort"]["_id"]);
        if ($resilientIteratorSupported) {
            $skip = isset($options["skip"]) ? $options["skip"] : NULL;
            $limit = isset($options["limit"]) ? $options["limit"] : NULL;
            $sort = isset($options["sort"]) ? $options["sort"] : NULL;
            $hint = isset($options["hint"]) ? $options["hint"] : NULL;
            $result = new ResilientMongoIterator($this->getCollection(), $values, $skip, $limit, $sort, $hint);
        } else {
            $result = $this->getCollection()->find($values, $options);
            if ($result)
                $result = new IteratorIterator($result);
        }
		static::perfmon(false);
		return $result;
	}
	
	public function count($values, $options = array()) {
		static::perfmon(true);
		$result = $this->getCollection()->count($values, $options);
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
		$options = $this->updateOptions($options);
        $options = $this->sanitizeOptions($options);
			if(count($update) == 0)
			return false;
		static::perfmon(true);
		$action = (isset($options["multiple"]) && $options["multiple"]) ? "updateMany" : "updateOne";
		$success = $this->getCollection()->$action($query, array('$set' => $update), $options);
        if ((isset($options["safe"]) && $options["safe"]) || (isset($options["fsync"]) && $options["fsync"]) || (isset($options["w"]) && $options["w"]) || $success->isAcknowledged())
        	$success = $success->isAcknowledged();
		static::perfmon(false);
		return $success;
	}
	
	public function incrementCell($id, $key, $value) {
		static::perfmon(true);
		$success = $this->getCollection()->updateOne(array("_id" => $id), array('$inc' => array($key => $value)));
		static::perfmon(false);
		return $success->isAcknowledged() ? $success->isAcknowledged() : $success;
	}
	
	public function updateOne($query, $update, $options = array("safe" => TRUE)) {
		$options["multiple"] = FALSE;
		return $this->update($query, $update, $options);
	}
	
	public function remove($query, $options = array("safe" => TRUE)) { // "justOne" => true
		$options = $this->updateOptions($options);
        $options = $this->sanitizeOptions($options);
		static::perfmon(true);
		$action = (isset($options["justOne"]) && $options["justOne"]) ? "deleteOne" : "deleteMany";
		$success = $this->getCollection()->$action($query, $options);
        if ((isset($options["safe"]) && $options["safe"]) || (isset($options["fsync"]) && $options["fsync"]) || (isset($options["w"]) && $options["w"]) || $success->isAcknowledged())
        	$success = $success->isAcknowledged();
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

    public function sanitizeOptions($options) {
        if (isset($options["skip"]))
            $options["skip"] = intval($options["skip"]);
        if (isset($options["limit"]))
            $options["limit"] = intval($options["limit"]);
        return $options;
    }
    
}