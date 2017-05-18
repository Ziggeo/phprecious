<?php


require_once(dirname(__FILE__) . "/../Database.php");
require_once(dirname(__FILE__) . "/SqlDatabaseTable.php");

class RedshiftDatabase extends Database {

	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("database");
			else
				$PERFMON->leave("database");
		}
	}

    private $connection;
    private $database;
    private $dbname;
	private $uri;
    
    public function __construct($dbname, $uri = "jdbc:postgres://localhost:5432") {
    	$this->dbname = strtolower($dbname);
		$this->uri = $uri;
    }
	
	private function getConnection() {
        if (!$this->connection) {
        	static::perfmon(true);
        	$this->connection = class_exists("MongoClient") ? new MongoClient($this->uri) : new Mongo($this->uri);
        	static::perfmon(false);
		}
		return $this->connection;
	}
	
	public function getDatabase() {
        if (!$this->database) {
        	static::perfmon(true);
        	$this->database = $this->getConnection()->selectDB($this->dbname);
        	static::perfmon(false);
		}
		return $this->database;
	}

    public function selectTable($name) {
        return new SqlDatabaseTable($this, $name);
    }
	
	public function encode($type, $value) {
		if ($type == "id")
			return $value == NULL ? NULL : new MongoId($value);
		if ($type == "date")
			return $value == NULL ? NULL : new MongoDate($value);
		return $value;
	}
	
	public function decode($type, $value) {
		if ($type == "id")
			return $value == NULL ? NULL : $value . "";
		if ($type == "date")
			return $value == NULL ? NULL : TimeSupport::microtime_to_seconds($value);
		return $value;
	}

}
