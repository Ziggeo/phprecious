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
            if (empty($config["region"]))
                $config["region"] = "us-east-1";
            if (empty($config["version"]))
                $config["version"] = "latest";
            $sdk = new Aws\Sdk($config);
            $this->database = $sdk->createDynamoDb();
            static::perfmon(FALSE);
        }
        return $this->database;
    }

    public function selectTable($name, $config = array()) {
        return new DynamoDBDatabaseTable($this, $name, $config);
    }

    public function encode($type, $value, $attrs = array()) {
        if ($type === "date")
            return date(DATE_ISO8601, $value);
        return $value;
    }
    public function decode($type, $value, $attrs = array()) {
        $marshaler = new Marshaler();
        if (($type === "string" || $type === "id")) {
          return $marshaler->unmarshalValue(array("S" => $value));
        } elseif ($type === "boolean") {
          return $marshaler->unmarshalValue(array("BOOL" => $value));
        } elseif ($type === "date") {
					$date = $marshaler->unmarshalValue(array("S" => $value));
					//We turn the date into a timestamp
          try {
            $date_obj = new DateTime($date);
            return $date_obj->getTimestamp();
          } catch (Exception $e) {
						return NULL;
          }
        } else {
          return $marshaler->unmarshalValue(array($type => $value));
        }
    }


    public function createTable($name, $config) {
        if (!@$config["KeySchema"])
            throw new InvalidArgumentException("KeyScheme attribute is mandatory");
        if (!@$config["AttributeDefinitions"])
            throw new InvalidArgumentException("AttributeDefinitions attribute is mandatory");
        /*if (!@$config["ProvisionedThroughput"])
            throw new InvalidArgumentException("ProvisionedThroughput attribute is mandatory");*/
        $config["TableName"] = $name;
        $this->getDatabase()->createTable($config);
        return $this->selectTable($name, $config);
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

