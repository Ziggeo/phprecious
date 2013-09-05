<?php


require_once(dirname(__FILE__) . "/../Database.php");
require_once(dirname(__FILE__) . "/MongoDatabaseTable.php");

class MongoDatabase extends Database {

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
    
    public function __construct($dbname) {
    	$this->dbname = strtolower($dbname);
    }
	
	private function getConnection() {
        if (!$this->connection) {
        	static::perfmon(true);
        	$this->connection = new Mongo();
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
        return new MongoDatabaseTable($this, $name);
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

