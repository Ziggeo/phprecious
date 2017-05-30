<?php


require_once(dirname(__FILE__) . "/../Database.php");
require_once(dirname(__FILE__) . "/PostgresDatabaseTable.php");

class PostgresDatabase extends Database {

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
	private $user;
	private $password;
	private $host;
	private $port;
	private $dbname;

	/**
	 * PostgreDatabase constructor.
	 * @param $user
	 * @param $password
	 * @param $host
	 * @param $port
	 * @param $dbname
	 */
	public function __construct($user, $password, $host, $port, $dbname)
	{
		$this->user = $user;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
		$this->dbname = strtolower($dbname);
	}

	private function getConnection() {
        if (!$this->connection) {
        	static::perfmon(true);
        	try {
		        $this->connection = new PDO("pgsql:host=$this->host;port=$this->port;dbname=$this->dbname",$this->user, $this->password);
	        } catch (PDOException $e) {
        		die($e->getMessage());
	        }
        	static::perfmon(false);
		}
		return $this->connection;
	}
	
	public function getDatabase() {
        if (!$this->database) {
        	static::perfmon(true);
        	$this->database = $this->getConnection();
        	static::perfmon(false);
		}
		return $this->database;
	}

    public function selectTable($name) {
        return new PostgresDatabaseTable($this, $name);
    }
	
	public function encode($type, $value) {
		return $value;
	}
	
	public function decode($type, $value) {
		return $value;
	}

}

