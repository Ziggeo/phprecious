<?php

Class AppConfig extends ApplicationInitializer {
	
	function __construct() {
		$this->ident = "app-config";
	}
	
	public function execute($app) {
		$app->setConfig("app.name", "FilePoster");

		$app->setConfig("database.type", "Mongo");
		$app->setConfig("database.name", $app->config("app.name"));

		$app->setConfig("logger.directory", $app->resolvePath("{logs}"));
		$app->setConfig("logger.name", $app->config("app.name") . ".log");

		$app->setConfig("server.domain", "localhost");
		$app->setConfig("server.protocol", "http");
		
		$app->setConfig("session.cookie.name", "si");
		$app->setConfig("session.cookie.domain", $app->config("server.domain"));
		$app->setConfig("session.cookie.duration_days", 7);
	}

}