<?php

require_once(dirname(__FILE__) . "/../database/dynamodb/DynamoDBDatabase.php");
require_once(dirname(__FILE__) . "/../database/dynamodb/DynamoDBDatabaseTable.php");
require_once(dirname(__FILE__) . "/../modelling/models/DatabaseModel.php");

$database = NULL;


class DynamoDBModelTest extends PHPUnit\Framework\TestCase {


	public function testTableCreateCrudAndTableDelete() {
		$test_config_path = realpath(__DIR__ . "/test_data/dynamodb-test-config.json");
		if ($test_config_path === FALSE) {
			$test_config_path = __DIR__ . "/test_data/dynamodb-test-config.json";
		}
		$test_config = file_exists($test_config_path) ? json_decode(file_get_contents($test_config_path), TRUE) : NULL;
		$env_endpoint = getenv("DYNAMODB_ENDPOINT");
		$env_region = getenv("DYNAMODB_REGION");
		$env_version = getenv("DYNAMODB_VERSION");
		if ($env_endpoint || $env_region || $env_version) {
			if (!is_array($test_config)) {
				$test_config = array();
			}
			if (!isset($test_config["dynamodb.test_db_config"])) {
				$test_config["dynamodb.test_db_config"] = array();
			}
			if ($env_endpoint) {
				$test_config["dynamodb.test_db_config"]["endpoint"] = $env_endpoint;
			}
			if ($env_region) {
				$test_config["dynamodb.test_db_config"]["region"] = $env_region;
			}
			if ($env_version) {
				$test_config["dynamodb.test_db_config"]["version"] = $env_version;
			}
		}
		if (!is_array($test_config) || !isset($test_config["dynamodb.test_db_config"])) {
			$this->markTestSkipped("Missing DynamoDB test config at {$test_config_path}.");
		}
		$database = new DynamoDBDatabase($test_config["dynamodb.test_db_config"]);
		$attempts = 5;

		while ($attempts > 0) {
			try {
				$database->getDatabase();
				break;
			} catch (Exception $exception) {
				$attempts--;
				if ($attempts === 0) {
					$this->markTestSkipped("DynamoDB not available: " . $exception->getMessage());
				}
				usleep(500000);
			}
		}

		try {
			$database->deleteTable("TestTable");
		} catch (Exception $exception) {
			//Table was already deleted
		}
		$test_table = json_decode(file_get_contents(dirname(__FILE__) . "/test_data/test-table.json"), TRUE);

		$table_config = $test_table["table"];
		$table = $database->createTable("TestTable", $table_config);
		$data = $test_table["data"];
		$first_key = ArrayUtils::arrayKeyFirst($data);
		$first_key_expression = array(
			"_id" => $data[$first_key]["_id"],
			"created" => $data[$first_key]["created"]
		);

		//Test INSERT
		foreach ($data as $datum) {
			$table->insert($datum);
		}

		//Test READ and DECODE
		$item = $table->read(json_encode($first_key_expression));
		$decoded = $database->decodeItem($item);
		$this->assertEquals($data[$first_key]["_id"], $decoded["_id"]);
		$this->assertTrue(isset($decoded["index"]));
		$data_date = $database->decode("date", $data[$first_key]["created"]);
		$decoded_date = $database->decode("date", $decoded["created"]);
		$this->assertEquals($data_date, $decoded_date);
		$data_date = $database->decode("date", $data[$first_key]["created"]);
		$decoded_date = $database->decode("date", $decoded["created"]);
		$this->assertEquals($data_date, $decoded_date);
		$this->assertEquals($database->encode("date", $data_date), $decoded["created"]);
		//Date Object
		$decoded_date = $database->decode("date", array("S" => $decoded["created"]));
		$this->assertEquals($data_date, $decoded_date);
		$decode_test = $database->decode("date", array("S" => 1635869771));
		$this->assertEquals($decode_test, 1635869771);
		//TODO TEST WITH ARRAYS
		//Test UPDATE
		$update_data = array(
			"update" => array(
				"set" => array("status" => 3, "failure_count" => 0, "not_ready_count" => 0),
				"remove" => array("index")
			)
		);
		$updated = $table->updateOne(
			$first_key_expression,
			$update_data
		);
		$updated = $database->decodeItem($updated);
		$this->assertEquals(0, $updated["failure_count"]);
		$this->assertFalse(isset($updated["index"]));
		$item = $table->read(json_encode($first_key_expression));
		$decoded = $database->decodeItem($item);
		$this->assertEquals($first_key_expression["_id"], $decoded["_id"]);

		//Test INCREMENTS
		$incremented = $table->incrementCell($first_key_expression, "not_ready_count", 1);
		$incremented = $database->decodeItem($incremented);
		$this->assertEquals(1, $incremented["not_ready_count"]);
		$decremented = $table->incrementCell($first_key_expression, "not_ready_count", -1);
		$decremented = $database->decodeItem($decremented);
		$this->assertEquals(0, $decremented["not_ready_count"]);
		//Test FIND with Application Global index
		$items = $table->find(
			array(
				"query" => array(
					"application_id" => "5dd3fc9e3a72e17d1c0ee918",
					"created" => array(">" => "2013-12-31T23:59:59.99Z")
				),
				"index" => array(
					"name" => "ApplicationIndex"
				),
				"fields" => array(
					"owner_id", "owner_id_type", "status", "_id"
				)
			)
		);
		$this->assertEquals(19, iterator_count($items)); //19 items fulfill this query params. Manually counted in the test data.
		$decoded_item = NULL;
		foreach ($items as $item) {
			$decoded_item = $database->decodeItem($item);
			break;
		}
		$this->assertEquals(3, $decoded_item["status"]);
		$this->assertEquals("ApiVideoStream", $decoded_item["owner_id_type"]);
		$this->assertFalse(isset($decoded_item["created"])); //Exists in DB but shouldn't be present in query

		//Test COUNT
		$this->assertEquals(count($data), $table->count());
		$this->assertEquals(19, $table->count(array(
				"query" => array(
					"application_id" => "5dd3fc9e3a72e17d1c0ee918",
					"created" => array(">" => "2013-12-31T23:59:59 +03:00")
				),
				"index" => array(
					"name" => "ApplicationIndex"
				),
				"fields" => array(
					"owner_id", "owner_id_type", "status", "_id"
				)
			)
		));

		//Test SCAN
		$scanned = $table->scan(array(
				"query" => array(
					"status" => 3
				)
			)
		);
		$this->assertEquals(124, iterator_count($scanned));
		$decoded_item = NULL;
		foreach ($scanned as $item) {
			$decoded_item = $database->decodeItem($item);
			$this->assertEquals(3, $decoded_item["status"]);
			if (isset($decoded_item["test_object_property"])) {
				$this->assertEquals(2, count($decoded_item["test_object_property"]));
			}
		}
		//Test FIND with Owner Global index
		$items = $table->find(
			array(
				"query" => array(
					"owner_id" => "5dd585a2c4c441891b1a98d3"
				),
				"index" => array(
					"name" => "OwnerIndex"
				)
			)
		);
		$this->assertEquals(23, iterator_count($items)); //19 items fulfill this query params. Manually counted in the test data.
		$table->deleteTable();
	}
}

