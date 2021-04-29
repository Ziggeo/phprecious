<?php

require_once(dirname(__FILE__) . "/../DatabaseTable.php");
require_once(dirname(__FILE__) . "/../../support/data/ArrayUtils.php");

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

class DynamoDBDatabaseTable extends DatabaseTable {

	private $primary_key_attributes = array();
	private $indexes = array();
	private $unparsed_config = array();

	const VALUE_IDENTIFIERS = array(
		"set" => ":",
		"remove" => "#"
	);

	public function __construct($database, $tablename, $config) {
		parent::__construct($database, $tablename, FALSE);
		foreach ($config["KeySchema"] as $value) {
			$this->primary_key_attributes[] = $value["AttributeName"];
		}
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
		foreach ($indexes as $in => $keys) {
			$this->indexes[$in] = array_map(function ($attr) {
				return $attr["AttributeName"];
			}, $keys);
		}
		$this->unparsed_config = $config;
	}

	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("database");
			else
				$PERFMON->leave("database");
		}
	}

	public function primaryKey() {
		if (count($this->primary_key_attributes) === 1)
			return $this->primary_key_attributes[0];
		return $this->primary_key_attributes;
	}

	private function existsInPrimaryKey($attr) {
		return in_array($attr, $this->primary_key_attributes);
	}

	private function existsInIndex($attr, $index) {
		if (!@$index["name"] || !@$this->indexes[$index["name"]])
			return FALSE;
		return in_array($attr, $this->indexes[$index["name"]]);
	}

	public function insert(&$row) {
		try {
			$marshaler = new Marshaler();
			$params = array(
				'TableName' => $this->getTablename(),
				'Item' => $marshaler->marshalItem($row)
			);

			return $this->getDatabase()->getDatabase()->putItem($params);
		} catch (DynamoDbException $exception) {
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->insert($row);
			} else
				throw $exception;
		}
	}


	public function read($values, $options = NULL) {
		try {
			$marshaler = new Marshaler();
			$key = $marshaler->marshalJson($values);

			$params = array(
				'TableName' => $this->getTablename(),
				'Key' => $key
			);

			$result = $this->getDatabase()->getDatabase()->getItem($params);
			return $result["Item"];
		} catch (DynamoDbException $exception) {
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->read($values, $options);
			} else
				throw $exception;
		}
	}

	public function count($query = array()) {
		try {
			$marshaler = new Marshaler();
			$params = array();
			//KEY EXPRESSION TO FILTER EXPRESSION WITH SCAN
			if (count($query)) {
				$query["index"] = (@$query["index"]) ? $query["index"] : array();
				if (isset($query["query"]["start_query_from"]))
					unset($query["query"]["start_query_from"]);
				$params = $this->parseFindAndScan($query["query"], array(), $query["index"]);
				$params["ExpressionAttributeValues"] = $marshaler->marshalJson(json_encode($params["ExpressionAttributeValues"]));
			}
			$params = $this->cleanScanParams($params);
			$params = array_merge(array(
				"TableName" => $this->getTablename(),
				"Select" => "COUNT"
			), $params);

			$result = $this->getDatabase()->getDatabase()->scan($params);
			return $result["Count"];
		} catch (DynamoDbException $exception) {
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->count($query);
			} else
				throw $exception;
		}
	}

	public function scan($query = array(), $options = NULL) {
		try {
			$marshaler = new Marshaler();
			$query["query"] = (@$query["query"]) ? $query["query"] : array();
			$query["fields"] = (@$query["fields"]) ? $query["fields"] : array();
			$query["index"] = (@$query["index"]) ? $query["index"] : array();
			if (@$query["query"]["start_query_from"]) {
				$options["start_query_from"] = $query["query"]["start_query_from"];
				unset($query["query"]["start_query_from"]);
			}
			$params = $this->parseFindAndScan($query["query"], $query["fields"], $query["index"], $options);
			if (isset($params["ExpressionAttributeValues"]))
				$params["ExpressionAttributeValues"] = $marshaler->marshalJson(json_encode($params["ExpressionAttributeValues"]));
			if (isset($params["ExclusiveStartKey"]))
				$params["ExclusiveStartKey"] = $marshaler->marshalJson(json_encode($params["ExclusiveStartKey"]));
			$params = $this->cleanScanParams($params);
			$params = array_merge(array(
				'TableName' => $this->getTablename()
			), $params);

			$results = [];
			while (TRUE) {
				$params = array_merge(array(
					'TableName' => $this->getTablename()
				), $params);
				$result = $this->getDatabase()->getDatabase()->scan($params);
				if (@$result["Items"])
					$results = array_merge($results, $result["Items"]);
				if (!isset($result["LastEvaluatedKey"]) || (is_numeric($options["limit"]) && count($results) >= $options["limit"]))
					break;
				if (isset($result["LastEvaluatedKey"]))
					$params["ExclusiveStartKey"] = $result["LastEvaluatedKey"];
				else
					break;
			}

			return new ArrayIterator($results);
		} catch (DynamoDbException $exception) {
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->scan($query, $options);
			} else
				throw $exception;
		}
	}

	public function find($query, $options = NULL) {
		try {
			$marshaler = new Marshaler();
			$query["fields"] = (@$query["fields"]) ? $query["fields"] : array();
			$query["index"] = (@$query["index"]) ? $query["index"] : array();
			if (@$query["query"]["start_query_from"]) {
				$options["start_query_from"] = $query["query"]["start_query_from"];
				unset($query["query"]["start_query_from"]);
			}
			$params = $this->parseFindAndScan($query["query"], $query["fields"], $query["index"], $options);
			if (!@$params["KeyConditionExpression"])
				return $this->scan($query, $options);
			$params["ExpressionAttributeValues"] = $marshaler->marshalJson(json_encode($params["ExpressionAttributeValues"]));
			if (isset($params["ExclusiveStartKey"]))
				$params["ExclusiveStartKey"] = $marshaler->marshalJson(json_encode($params["ExclusiveStartKey"]));
			$results = [];
			while (TRUE) {
				$params = array_merge(array(
					'TableName' => $this->getTablename()
				), $params);
				$result = $this->getDatabase()->getDatabase()->query($params);
				if (@$result["Items"])
					$results = array_merge($results, $result["Items"]);
				if (!isset($result["LastEvaluatedKey"]) || (is_numeric($options["limit"]) && count($results) >= $options["limit"]))
					break;
				if (isset($result["LastEvaluatedKey"]))
					$params["ExclusiveStartKey"] = $result["LastEvaluatedKey"];
				else
					break;
			}

			return new ArrayIterator($results);
		} catch (DynamoDbException $exception) {
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->find($query, $options);
			} else
				throw $exception;
		}
	}

	public function update($query, $update) {
		// TODO: Implement update() method.
	}

	public function updateOne($id, $update) {
		try {
			$marshaler = new Marshaler();
			$params = $this->parseUpdate($update);
			$key = $marshaler->marshalJson(json_encode($id));
			$params = array_merge(array(
				'TableName' => $this->getTablename(),
				'Key' => $key,
				'ReturnValues' => 'UPDATED_NEW'
			), $params);

			$result = $this->getDatabase()->getDatabase()->updateItem($params);
			return $result['Attributes'];
		} catch (DynamoDbException $exception) {
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->updateOne($id, $update);
			} else
				throw $exception;
		}
	}

	public function remove($query) {
		// TODO: Implement remove() method.
	}

	public function removeOne($query) {
		try {
			$marshaler = new Marshaler();
			$key = $marshaler->marshalJson(json_encode($query));

			$params = array(
				'TableName' => $this->getTablename(),
				'Key' => $key
			);

			$result = $this->getDatabase()->getDatabase()->deleteItem($params);
			return $result["Item"];
		} catch (DynamoDbException $exception) {
			APP()->logger(NULL, NULL, $exception->getMessage());
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->removeOne($query);
			} else
				throw $exception;
		}
	}

	public function deleteTable() {
		$params = [
			'TableName' => $this->getTablename()
		];

		try {
			return $this->getDatabase()->getDatabase()->deleteTable($params);
		} catch (DynamoDbException $e) {
			echo "Unable to delete table:\n";
			echo $e->getMessage() . "\n";
		}
	}

	public function incrementCell($id, $attr, $value) {
		try {
			$marshaler = new Marshaler();
			$key = $marshaler->marshalJson(json_encode($id));
			$updater = $this->parseIncrement($attr, $value);
			$eav = $marshaler->marshalJson($updater["value"]);

			$params = array(
				'TableName' => $this->getTablename(),
				'Key' => $key,
				'UpdateExpression' => $updater["expression"],
				'ExpressionAttributeValues' => $eav,
				'ReturnValues' => 'UPDATED_NEW'
			);

			$result = $this->getDatabase()->getDatabase()->updateItem($params);
			return $result['Attributes'];
		} catch (DynamoDbException $exception) {
			if ($exception->getAwsErrorCode() === "ResourceNotFoundException" && @$this->unparsed_config) {
				$this->getDatabase()->createTable($this->getTablename(), $this->unparsed_config);
				return $this->incrementCell($id, $attr, $value);
			} else 
				throw $exception;
		}
	}

	private function parseIncrement($attr, $value) {
		$value_key = ":" . preg_replace("/[^A-Za-z0-9]/", '', $attr);
		$expression = "set $attr = $attr + " . $value_key;
		$parsed = array(
			$value_key => $value
		);
		return array(
			"expression" => $expression,
			"value" => json_encode($parsed)
		);
	}

	/**
	 * $update_data["update"]["set"] to update values
	 * $update_data["update"]["remove"] to remove values
	 * $update_data["update"]["add"] to add values to lists
	 * $update_data["update"]["delete"] to delete values to lists
	 *
	 * @param $update_data
	 * @return array
	 */
	private function parseUpdate($update_data) {
		$expression = "";
		$values = array();
		$names = array();
		if (!isset($update_data["update"])) {
			$update_data = array(
				"update" =>
					array(
						"set" => $update_data
					)
			);
		};
		$last_action_key = ArrayUtils::arrayKeyLast($update_data["update"]);
		foreach ($update_data["update"] as $action => $data) {
			$parsed_action = $this->parseUpdateAction($action, $data);
			$expression .= $parsed_action["expression"];
			$values = array_merge($values, $parsed_action["values"]);
			$names = array_merge($names, $parsed_action["names"]);
			if ($last_action_key <> $action)
				$expression .= " ";
		}
		$marshaler = new Marshaler();
		$result = array(
			"UpdateExpression" => $expression,
			"ExpressionAttributeValues" => $marshaler->marshalJson(json_encode($values)),
			"ExpressionAttributeNames" => $names

		);
		//Update Condition
		return $result;
	}

	/**
	 * DynamoDB supports different actions when updating records. With this function we parse input data
	 * into a DynamoDB valid update expression and values
	 *
	 * @param $action
	 * @param $update_values
	 * @return array
	 */
	private function parseUpdateAction($action, $update_values) {
		$expression = "$action ";
		$eav = array();
		$ean = array();
		$value_identifier = self::VALUE_IDENTIFIERS[$action];
		$last_key = ArrayUtils::arrayKeyLast($update_values);
		foreach ($update_values as $key => $update_value) {
			if (is_string($key)) {
				$attribute_name = "#" . preg_replace("/[^A-Za-z0-9]/", '', $key) . rand();
				$ean[$attribute_name] = $key;
				$update_value_key = "$value_identifier" . preg_replace("/[^A-Za-z0-9]/", '', $key);
				$expression .= $attribute_name . " = " . $update_value_key;
				$eav[$update_value_key] = $update_value;
			} else { //this will happen for remove and delete actions
				$attribute_name = "#" . preg_replace("/[^A-Za-z0-9]/", '', $update_value) . rand();
				$ean[$attribute_name] = $update_value;
				$expression .= $attribute_name;
			}
			if ($last_key <> $key)
				$expression .= ", ";
		}

		return array(
			"expression" => $expression,
			"values" => $eav,
			"names" => $ean
		);
	}

	private function parseFindAndScan($query, $fields = array(), $index = array(), $options = NULL) {
		$kce = array();
		$fe = array();
		$pe = array();
		$eav = array();
		$ean = array();
		foreach ($query as $key => $item) {
			$item_string = "";
			$attribute_name = "#" . preg_replace("/[^A-Za-z0-9]/", '', $key) . rand(0, 10000); //We replace every key because of reserved words
			$ean[$attribute_name] = $key;
			if (in_array($key, $fields)) {
				$pe[] = $attribute_name;
				ArrayUtils::removeByValue($fields, $key);
			}
			if (!is_array($item)) {
				$item_string .= $this->parseSimpleFindValue($key, $item, $attribute_name, $eav);
			} else {
				foreach ($item as $operator => $values) {
					$item_string .= $this->parseArrayFindValue($key, $values, $attribute_name, $eav, $operator);
				}
			}

			if ($this->existsInPrimaryKey($key) || $this->existsInIndex($key, $index)) {
				$kce[] = $item_string;
			} else {
				$fe[] = $item_string;
			}

		}

		//fields that weren't part of the query condition
		foreach ($fields as $field) {
			$field_name = [];
			$parts = explode(".", $field);
			foreach ($parts as $attr) {
				$output_array = array();
				if (!in_array($attr, $ean)) {
					preg_match("/\[\d{1,}\]/", $attr, $output_array);
					$attr = preg_replace("/\[\d{1,}\]/", "", $attr);
					$attribute_name = "#" . preg_replace("/[^A-Za-z0-9]/", "", $attr) . rand(0, 10000); //We replace every key because of reserved words
					$ean[$attribute_name] = $attr;
				} else {
					$attribute_name = array_search($attr, $ean);
				}
				if (count($output_array) > 0)
					$attribute_name .= $output_array[0];
				$field_name[] = $attribute_name;
			}
			$pe[] = implode(".", $field_name);
		}

		$parsed = array();
		if (@$eav)
			$parsed["ExpressionAttributeValues"] = $eav;
		if (@$ean)
			$parsed["ExpressionAttributeNames"] = $ean;
		if (count($kce))
			$parsed["KeyConditionExpression"] = implode(" and ", $kce);
		if (count($pe)) //ProjectionExpression
			$parsed["ProjectionExpression"] = implode(", ", $pe);
		if (count($fe))//FilterExpression
			$parsed["FilterExpression"] = implode(" and ", $fe);
		if (count($index) && @$index["name"]) { //Index
			$parsed["IndexName"] = $index["name"];
			if (isset($index["sort"])) {
				$parsed["ScanIndexForward"] = @$index["sort"];
			}
		}
		if (@$options["limit"])
			$parsed["Limit"] = $options["limit"];

		if (@$options["start_query_from"])
			$parsed["ExclusiveStartKey"] = $options["start_query_from"];

		return $parsed;
	}

	private function parseSimpleFindValue($key, $item, $attribute, &$eav, $operator = "=") {
		$value_key = ":" . preg_replace("/[^A-Za-z0-9]/", '', $key);
		$eav[$value_key] = $item;
		return $attribute . " $operator " . $value_key;
	}

	private function parseArrayFindValue($key, $item, $attribute, &$eav, $operator) {
		//key element should be the operator
		$kce = "";
		if (!is_array($item)) {
			$kce .= $this->parseSimpleFindValue($key, $item, $attribute, $eav, $operator);
		} else {
			$last_key = ArrayUtils::arrayKeyLast($item);
			foreach ($item as $idV => $value) {
				$value_key = ":" . preg_replace("/[^A-Za-z0-9]/", '', $key) . $idV;
				$kce .= $value_key;
				$eav[$value_key] = $value;
				if ($last_key <> $idV)
					$kce .= " and ";
			}
		}

		return $kce;
	}

	/**
	 * Scan function doesn't use KeyConditionExpression so we should add it to FilterExpression.
	 *
	 * @param $params
	 * @return mixed
	 */
	private function cleanScanParams($params) {
		$expressions = array();
		if (@$params["KeyConditionExpression"]) {
			$expressions[] = $params["KeyConditionExpression"];
			unset($params["KeyConditionExpression"]);
		}
		if (@$params["FilterExpression"])
			$expressions[] = $params["FilterExpression"];
		if (count($expressions))
			$params["FilterExpression"] = implode(" and ", $expressions);
		return $params;
	}


}