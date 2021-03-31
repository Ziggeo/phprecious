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
	private $uri;
    
    public function __construct($dbname, $uri = "mongodb://localhost:27017") {
    	$this->dbname = strtolower($dbname);
		$this->uri = $uri;
    }
	
	private function getConnection() {
        if (!$this->connection) {
        	static::perfmon(true);
        	$this->connection = class_exists("\MongoDB\Client") ? new MongoDB\Client($this->uri, [], [
                'typeMap' => [
                    'array' => 'array',
                    'document' => 'array',
                    'root' => 'array',
                ],
            ] ) : new MongoClient($this->uri);
        	static::perfmon(false);
		}
		return $this->connection;
	}
	
	public function getDatabase() {
        if (!$this->database) {
        	static::perfmon(true);
        	$this->database = $this->getConnection()->selectDatabase($this->dbname);
        	static::perfmon(false);
		}
		return $this->database;
	}

    public function selectTable($name, $definition = NULL) {
        return new MongoDatabaseTable($this, $name);
    }

    public function encode($type, $value, $attrs = array()) {
        if (is_array($value) && in_array($type, array("id", "date", "datetime"))) {
            return array_map(function($val) use($type, $attrs){
                return $this->encode($type, $val, $attrs);
            }, $value);
        }
        if ($type == "id") {
			try {
				return $value == NULL ? NULL : new MongoDB\BSON\ObjectID($value);
			} catch (InvalidArgumentException $e) {
				if (@$attrs["weakly_encoded"] && is_string($value))
					return $value;
				else
					throw $e;
			}
		}
        if ($type == "date" || $type == "datetime")
            return $value == NULL ? NULL : new MongoDB\BSON\UTCDatetime($value * 1000);
		//Added to prevent storing empty arrays when empty objects are needed
		if ($type === "object" && $value === array() && @$attrs["force_object"])
			return (object) $value;
        return $value;
    }

    public function decode($type, $value, $attrs = array()) {
        if ($type == "id")
            return $value == NULL ? NULL : $value . "";
        //Workaround to keep backwards compatibility
        if ($type == "date" || $type == "datetime") {
            if ($value == NULL)
                return NULL;
            if (is_a($value, "MongoDB\BSON\UTCDatetime")) {
                $serialized = $value->jsonSerialize();
                $time_value = $serialized['$date']['$numberLong'];
                if (preg_match('/^\d{10}$/', $time_value)) {
                    $value = $time_value;
                } else {
                    $value = $value->toDateTime()->getTimestamp();
                }
                return $value;
            }
            if (is_numeric($value))
                return preg_match('/^\d{10}$/', $value) ? $value : $value / 1000;
        }
        return $value;
    }
}

