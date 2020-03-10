<?php

require_once(dirname(__FILE__) . "/../../vendor/autoload.php");
require_once(dirname(__FILE__) . "/../../database/mongo/MongoDatabase.php");

$mongo = new MongoDatabase("test");

$table = $mongo->selectTable("test");

$iterator = $table->find(array(), array("limit" => 10, "sort" => array("_id" => -1)));

foreach ($iterator as $item) {
    echo $item["_id"] . "\n";
}