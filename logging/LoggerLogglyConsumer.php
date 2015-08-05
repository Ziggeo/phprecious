<?php


require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerLogglyConsumer extends LoggerConsumer {
	
	private $url;	
	
	public function __construct($api_key, $name) {
		$this->url = 'https://logs-01.loggly.com/inputs/' . $api_key . '/tag/' . $name;
	}
	
	protected function processMessage($logger, $logMessage) {
		try {
			$session = curl_init();
			curl_setopt($session, CURLOPT_URL, $this->url);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_POSTFIELDS, $logMessage->toJSON());
			curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-type' => 'text/plain'));
			$result = curl_exec($session);
			curl_close($session);
		} catch (Exception $e) {
			// Fail silently
		}
	}
	
}
