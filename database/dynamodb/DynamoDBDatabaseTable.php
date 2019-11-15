<?php

require_once(dirname(__FILE__) . "/../DatabaseTable.php");
require_once(dirname(__FILE__) . "/../../support/data/ArrayUtils.php");

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

class DynamoDBDatabaseTable extends DatabaseTable {

	private $primary_key_attributes = array();

	const VALUE_IDENTIFIERS = array(
		"set" => ":",
		"remove" => "#"
	);

	public function __construct($database, $tablename, $key) {
		parent::__construct($database, $tablename, FALSE);
		foreach ($key as $value) {
			$this->primary_key_attributes[] = $value["AttributeName"];
		}
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
		return $this->primary_key_attributes;
	}

	private function existsInPrimaryKey($attr) {
		return in_array($attr, $this->primary_key_attributes);
	}

	public function insert(&$row) {
		$marshaler = new Marshaler();
		$params = array(
			'TableName' => $this->getTablename(),
			'Item' => $marshaler->marshalJson(json_encode($row))
		);

		return $this->getDatabase()->getDatabase()->putItem($params);
	}


	public function read($values, $options = NULL) {
		$marshaler = new Marshaler();
		$key = $marshaler->marshalJson($values);

		$params = array(
			'TableName' => $this->getTablename(),
			'Key' => $key
		);

		$result = $this->getDatabase()->getDatabase()->getItem($params);
		return $result["Item"];
	}

	public function count($query = array()) {
		$marshaler = new Marshaler();
		$params = array();
		//KEY EXPRESSION TO FILTER EXPRESSION WITH SCAN
		if (count($query)) {
			$params = $this->parseFindAndScan($query);
			$params["ExpressionAttributeValues"] = $marshaler->marshalJson(json_encode($params["ExpressionAttributeValues"]));
		}
		$params = $this->cleanScanParams($params);
		$params = array_merge(array(
			"TableName" => $this->getTablename(),
			"Select" => "COUNT"
		), $params);

		$result = $this->getDatabase()->getDatabase()->scan($params);
		return $result["Count"];
	}

	public function scan($query = array(), $options = NULL) {
		$marshaler = new Marshaler();
		$query["fields"] = (@$query["fields"]) ? $query["fields"] : array();
		$params = $this->parseFindAndScan($query["query"], $query["fields"]);
		$params["ExpressionAttributeValues"] = $marshaler->marshalJson(json_encode($params["ExpressionAttributeValues"]));
		$params = $this->cleanScanParams($params);
		$params = array_merge(array(
			'TableName' => $this->getTablename()
		), $params);

		$result = $this->getDatabase()->getDatabase()->scan($params);
		if (@$result["Items"])
			$result = new ArrayIterator($result["Items"]);
		return $result;
	}

	public function find($query, $options = NULL) {
		$marshaler = new Marshaler();
		$query["fields"] = (@$query["fields"]) ? $query["fields"] : array();
		$params = $this->parseFindAndScan($query["query"], $query["fields"]);
		$params["ExpressionAttributeValues"] = $marshaler->marshalJson(json_encode($params["ExpressionAttributeValues"]));
		$params = array_merge(array(
			'TableName' => $this->getTablename()
		), $params);

		$result = $this->getDatabase()->getDatabase()->query($params);
		if (@$result["Items"])
			$result = new ArrayIterator($result["Items"]);
		return $result;
	}

	public function update($query, $update) {
		// TODO: Implement update() method.
	}

	public function updateOne($id, $update) {
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
	}

	public function remove($query) {
		// TODO: Implement remove() method.
	}

	public function removeOne($query) {
		$marshaler = new Marshaler();
		$key = $marshaler->marshalJson($query);

		$params = array(
			'TableName' => $this->getTablename(),
			'Key' => $key
		);

		$result = $this->getDatabase()->getDatabase()->deleteItem($params);
		return $result["Item"];
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
		$marshaler = new Marshaler();
		$key = $marshaler->marshalJson(json_encode($id));
		$updater = $this->parseIncrement($attr, $value);
		$eav = $marshaler->marshalJson($updater["value"]);

		$params = array(
			'TableName' => $this->getTablename(),
			'Key' => $key,
			'UpdateExpression' => $updater["expression"],
			'ExpressionAttributeValues'=> $eav,
			'ReturnValues' => 'UPDATED_NEW'
		);

		$result = $this->getDatabase()->getDatabase()->updateItem($params);
		return $result['Attributes'];
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
				$expression .= $update_value;
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

	private function parseFindAndScan($query, $fields = array()) {
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
					$item_string .= $attribute_name . " " . $operator . " ";
					$item_string .= $this->parseArrayFindValue($key, $values, $attribute_name, $eav);
				}
			}

			if ($this->existsInPrimaryKey($key)) {
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

		$parsed = array(
			"ExpressionAttributeValues" => $eav,
			"ExpressionAttributeNames" => $ean,
			"KeyConditionExpression" => implode(" and ", $kce)
		);
		if (count($pe)) {
			$parsed["ProjectionExpression"] = implode(", ", $pe);
		}
		if (count($fe)) {
			$parsed["FilterExpression"] = implode(" and ", $fe);
		}

		return $parsed;
	}

	private function parseSimpleFindValue($key, $item, $attribute, &$eav) {
		$value_key = ":" . preg_replace("/[^A-Za-z0-9]/", '', $key);
		$eav[$value_key] = $item;
		return $attribute . " = " . $value_key;
	}

	private function parseArrayFindValue($key, $item, $attribute, &$eav) {
		//key element should be the operator
		$kce = "";
		if (!is_array($item)) {
			$kce .= $this->parseSimpleFindValue($key, $item, $attribute, $eav);
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