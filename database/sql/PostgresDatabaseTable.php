<?php

require_once(dirname(__FILE__) . "/../DatabaseTable.php");

class PostgresDatabaseTable extends DatabaseTable {
	
	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("database");
			else
				$PERFMON->leave("database");
		}
	}

	private $conn;
	
	private function getConn() {
		if (!$this->conn) {
			static::perfmon(true);
			$this->conn = $this->getDatabase();
			static::perfmon(false);
		}
		return $this->conn;
	}
	
	public function primaryKey() {
		return "id";
	}
	
	private function updateOptions($options) {
		return $options;
	}
	
	public function insert(&$row, $options = array("safe" => TRUE, /*"fsync" => TRUE*/)) {
		//TODO
	}
	
	public function find($values = array(), $options = array()) {
		static::perfmon(true);
		$result = $this->findQuery($values, $options);
		static::perfmon(false);
		return $result;
	}
	
	public function count($values = array(), $options = array()) {
		static::perfmon(true);
		$result = $this->findQueryCount($values, $options);
		static::perfmon(false);
		return $result;
	}
	
	public function findOne($values = array(), $options = array()) {
		static::perfmon(true);
		$result = $this->findQueryOne($values, $options);
		static::perfmon(false);
		return $result;
	}
	
	public function update($query, $update, $options = array("safe" => TRUE)) { // "multiple" => false
		//TODO
	}
	
	public function incrementCell($id, $key, $value) {
		//TODO
	}
	
	public function updateOne($query, $update, $options = array("safe" => TRUE)) {
		//TODO
	}
	
	public function remove($query, $options = array("safe" => TRUE)) { // "justOne" => true
		//TODO
	}
	
	public function removeOne($query, $options = array("safe" => TRUE)) {
		//TODO
	}
	
	public function ensureIndex($keys) {
		//TODO
	}


	private function findQuery($whereParams = array(), $options = array()) {
		$query = $this->prepareQueryFind($whereParams, $options);
		$query->execute();
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}

	private function findQueryOne($whereParams = array(), $options = array()) {
		$query = $this->prepareQueryFind($whereParams, $options);
		$query->execute();
		return $query->fetch(PDO::FETCH_ASSOC);
	}


	private function findQueryCount($whereParams = array(), $options = array()) {
		$options["count"] = true;
		$query = $this->prepareQueryFind($whereParams, $options);
		$query->execute();
		return $query->fetchColumn();
	}

	private function prepareQueryFind($whereParams = array(), $options = array()) {
		$conn = $this->getConn()->getDatabase();
		$tablename = $this->getTablename();
		if (!empty($options["count"]) && $options["count"]) {
			$queryString = "SELECT COUNT (*) from $tablename";
		} else {
			$queryString = "SELECT * from $tablename";
		}
		if (!empty($whereParams)) {
			$queryString .= " WHERE";
			$i = 1;
			foreach ($whereParams as $whereParam => $value) {
				$queryString .= " $whereParam = :$whereParam";
				if ($i < count($whereParams))
					$queryString .= " AND";
				$i++;
			}
		}

		$query = $conn->prepare($queryString);

		foreach ($whereParams as $whereParam => $value) {
			$query->bindParam(":$whereParam", $value);
		}

		return $query;
	}
		
	
}
