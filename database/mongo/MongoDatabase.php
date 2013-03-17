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
	
	public function encodeDate($date = NULL) {
		if ($date)
			return new MongoDate($date);
		else
			return new MongoDate();
	}
	
	public function encodePrimaryKey($id) {
		return new MongoId($id);
	}

}

