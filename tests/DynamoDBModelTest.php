<?php

require_once(dirname(__FILE__) . "/../database/dynamodb/DynamoDBDatabase.php");
require_once(dirname(__FILE__) . "/../database/dynamodb/DynamoDBDatabaseTable.php");
require_once(dirname(__FILE__) . "/../modelling/models/DatabaseModel.php");

$test_config = json_decode(file_get_contents(dirname(__FILE__) . "/test_data/dynamodb-test-config.json"), TRUE);
$database = NULL;


class DynamoDBModelTest extends PHPUnit\Framework\TestCase {

	public function testTableCreateCrudAndTableDelete() {
		global $test_config;
		if (!@$test_config)
			throw new Exception(
				"Please add the dynamodb-test-config.json file under the ./tests folder with the following format: " .
				"{
			        \"dynamodb.test_db_config\": {
					\"endpoint\": \"TESTURL\",
					\"region\": \"TESTREGION\",
					\"version\": \"latest\"
				  }
				}"
			);
		$database = new DynamoDBDatabase($test_config["dynamodb.test_db_config"]);
		$database->getDatabase();
		try {
			$database->deleteTable("TestTable");
		} catch (Exception $exception) {
			//Table was already deleted
		}
		$test_table = json_decode(file_get_contents('./test_data/test-table.json'), TRUE);
		$table_config = $test_table["table"];
		$table = $database->createTable("TestTable", $table_config);
		$data = $test_table["data"];
		$first_key = ArrayUtils::arrayKeyFirst($data);
		$first_key_expression = array(
			"year" => $data[$first_key]["year"],
			"title" => $data[$first_key]["title"]
		);

		//Test INSERT
		foreach ($data as $datum) {
			$table->insert($datum);
		}

		//Test READ and DECODE
		$item = $table->read(json_encode($first_key_expression));
		$decoded = $database->decodeItem($item);
		$this->assertEquals($data[$first_key]["title"], $decoded["title"]);

		//Test UPDATE
		$update_data = array(
			"update" => array(
				"set" => array("info.rating" => 0.0, "info.rank" => 9999, "testcount" => 1),
				"remove" => array("info.running_time_secs")
			)
		);
		$updated = $table->updateOne(
			$first_key_expression,
			$update_data
		);
		$this->assertNotNull($updated["testcount"]);
		$item = $table->read(json_encode($first_key_expression));
		$decoded = $database->decodeItem($item);
		$this->assertFalse(isset($decoded["info"]["running_time_secs"]));
		$this->assertEquals($first_key_expression["year"], $decoded["year"]);

		//Test INCREMENTS
		$incremented = $table->incrementCell($first_key_expression, "testcount", 1);
		$incremented = $database->decodeItem($incremented);
		$this->assertEquals(2, $incremented["testcount"]);
		$decremented = $table->incrementCell($first_key_expression, "testcount", -1);
		$decremented = $database->decodeItem($decremented);
		$this->assertEquals(1, $decremented["testcount"]);
		//Test FIND
		$items = $table->find(
			array(
				"query" => array(
					"year" => 1995,
					"title" => array("between" => array("A", "T"))
				),
				"fields" => array(
					"year", "title", "info.rating", "info.actors[0]", "info.rank"
				)
			)
		);
		$this->assertEquals(7, iterator_count($items)); //7 items fulfill this query params. Manually counted in the test data.
		$decoded_movie = NULL;
		foreach ($items as $movie) {
			$decoded_movie = $database->decodeItem($movie);
			break;
		}
		$this->assertEquals(1, count($decoded_movie["info"]["actors"]));
		$this->assertEquals(3, count($decoded_movie["info"]));
		$this->assertFalse(isset($decoded_movie["info"]["plot"])); //Exists in DB but shouldn't be present in query

		//Test COUNT
		$this->assertEquals(count($data), $table->count());
		$this->assertEquals(7, $table->count(array(
				"year" => 1995,
				"title" => array("between" => array("A", "T"))
			)
		));

		//Test SCAN
		$scanned = $table->scan(array(
				"query" => array(
					"year" => 1995,
					"title" => array("between" => array("A", "T"))
				)
			)
		);
		$this->assertEquals(7, iterator_count($scanned));
		$decoded_movie = NULL;
		foreach ($scanned as $movie) {
			$decoded_movie = $database->decodeItem($movie);
			break;
		}
		$this->assertEquals(3, count($decoded_movie["info"]["actors"]));
		$this->assertEquals(9, count($decoded_movie["info"]));
		$this->assertTrue(isset($decoded_movie["info"]["plot"])); //Exists in DB but shouldn't be present in query

		$table->deleteTable();
	}
}

