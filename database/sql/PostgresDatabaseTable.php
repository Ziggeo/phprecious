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


	private function findQuery($where_params = array(), $options = array()) {
		$query = $this->prepareQueryFind($where_params, $options);
		$query->execute();
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}

	private function findQueryOne($where_params = array(), $options = array()) {
		$query = $this->prepareQueryFind($where_params, $options);
		$query->execute();
		return $query->fetch(PDO::FETCH_ASSOC);
	}


	private function findQueryCount($where_params = array(), $options = array()) {
		$options["count"] = true;
		$query = $this->prepareQueryFind($where_params, $options);
		$query->execute();
		return $query->fetchColumn();
	}

	private function prepareQueryFind($where_params = array(), $options = array()) {
		$conn = $this->getConn()->getDatabase();
		$table_name = $this->getTablename();
		if (!empty($options["count"]) && $options["count"]) {
			$query_string = "SELECT COUNT (*) from $table_name";
		} else {
			$query_string = "SELECT * from $table_name";
		}
		if (!empty($where_params)) {
			$query_string .= " WHERE";
			$i = 1;
			foreach ($where_params as $where_param => $value) {
				$query_string .= " $where_param = :$where_param";
				if ($i < count($where_params))
					$query_string .= " AND";
				$i++;
			}
		}

		$query = $conn->prepare($query_string);

		foreach ($where_params as $where_param => $value) {
			$query->bindParam(":$where_param", $value);
		}

		return $query;
	}
		
	
}
