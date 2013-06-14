<?php

require_once(dirname(__FILE__) . "/app/App.php");
	
$app = new App();

function APP() {
	global $app;
	return $app;
}

$app->initialize();
$app->run();	
