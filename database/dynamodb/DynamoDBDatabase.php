<?php


require_once(dirname(__FILE__) . "/../Database.php");
require_once(dirname(__FILE__) . "/DynamoDBDatabaseTable.php");
require_once(dirname(__FILE__) . "/../../support/data/ArrayUtils.php");

use Aws\DynamoDb\Exception\DynamoDbException;

class DynamoDBDatabase extends Database {

	const TYPES = array(
		"S" => "string",
		"N" => "number",
		"M" => "array",
		"L" => "list"
	);
	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("database");
			else
				$PERFMON->leave("database");
		}
	}

	private $database;
	private $config;

	public function __construct($config = array()) {
		$this->config = $config;
	}

	public function getDatabase() {
		if (!$this->database) {
			static::perfmon(TRUE);
			$config = $this->config;
			if (empty($config["endpoint"]))
				throw new InvalidArgumentException("Endpoint param is mandatory");
			if (empty($config["region"]))
				throw new InvalidArgumentException("Region param is mandatory");
			if (empty($config["version"]))
				throw new InvalidArgumentException("Region param is mandatory");
			$sdk = new Aws\Sdk($this->config);
			$this->database = $sdk->createDynamoDb();
			static::perfmon(FALSE);
		}
		return $this->database;
	}

	public function selectTable($name, $key = array()) {
		return new DynamoDBDatabaseTable($this, $name, $key);
	}

	public function encode($type, $value, $attrs = array()) {

		return $value;
	}
	public function decode($type, $value, $attrs = array()) {
		if (!@self::TYPES[$type]) {
			echo $type;
			throw new Exception("Type not supported");
		}
		switch ($type) {
			case "S":
				$value = strval($value);
				break;
			case "N":
				$value = (strpos($value, ".")) ? (float) $value : (int) $value;
				break;
			case "M":
				$value = $this->decodeItem($value);
				break;
			case "L":
				$value = $this->decodeItem($value);
				break;
		}
		return $value;
	}


	public function createTable($name, $config) {
		if (!@$config["KeySchema"])
			throw new InvalidArgumentException("KeyScheme attribute is mandatory");
		if (!@$config["AttributeDefinitions"])
			throw new InvalidArgumentException("AttributeDefinitions attribute is mandatory");
		if (!@$config["ProvisionedThroughput"])
			throw new InvalidArgumentException("ProvisionedThroughput attribute is mandatory");
		$config["TableName"] = $name;
		try {
			$this->getDatabase()->createTable($config);
		} catch (DynamoDbException $e) {
			echo "Unable to create table:\n";
			echo $e->getMessage() . "\n";
		}
		return $this->selectTable($name, $config["KeySchema"]);
	}

	public function decodeItem($item) {
		$decoded = array();
		foreach ($item as $id_i => $value) {
			$decoded[$id_i] = $this->decode(ArrayUtils::arrayKeyFirst($value), ArrayUtils::firstElement($value));
		}

		return $decoded;
	}

	public function deleteTable($tablename) {
		$params = [
			'TableName' => $tablename
		];

		try {
			return $this->getDatabase()->deleteTable($params);
		} catch (DynamoDbException $e) {
			echo "Unable to delete table:\n";
			echo $e->getMessage() . "\n";
		}
	}
}

