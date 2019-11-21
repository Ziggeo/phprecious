<?php


require_once(dirname(__FILE__) . "/../Database.php");
require_once(dirname(__FILE__) . "/DynamoDBDatabaseTable.php");
require_once(dirname(__FILE__) . "/../../support/data/ArrayUtils.php");

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

class DynamoDBDatabase extends Database {

	const TYPES = array(
		"S" => "string",
		"N" => "number",
		"M" => "array",
		"L" => "list",
		"BOOL" => "boolean"
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

	public function selectTable($name, $key = array(), $indexes = array()) {
		return new DynamoDBDatabaseTable($this, $name, $key, $indexes);
	}

	public function encode($type, $value, $attrs = array()) {

		return $value;
	}
	public function decode($type, $value, $attrs = array()) {
		$marshaler = new Marshaler();
		return $marshaler->unmarshalValue(array($type => $value));
	}


	public function createTable($name, $config) {
		if (!@$config["KeySchema"])
			throw new InvalidArgumentException("KeyScheme attribute is mandatory");
		if (!@$config["AttributeDefinitions"])
			throw new InvalidArgumentException("AttributeDefinitions attribute is mandatory");
		if (!@$config["ProvisionedThroughput"])
			throw new InvalidArgumentException("ProvisionedThroughput attribute is mandatory");
		$config["TableName"] = $name;
		$this->getDatabase()->createTable($config);
		$indexes = array();
		if (isset($config["GlobalSecondaryIndexes"])) {
			foreach ($config["GlobalSecondaryIndexes"] as $index) {
				$indexes[$index["IndexName"]] = $index["KeySchema"];
			}
		}
		if (isset($config["LocalSecondaryIndexes"])) {
			foreach ($config["LocalSecondaryIndexes"] as $index) {
				$indexes[$index["IndexName"]] = $index["KeySchema"];
			}
		}
		return $this->selectTable($name, $config["KeySchema"], $indexes);
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

