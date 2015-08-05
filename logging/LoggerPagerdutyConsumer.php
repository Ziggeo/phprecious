<?php


require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerPagerdutyConsumer extends LoggerConsumer {
	
	private $url;
	private $service_key;
	
	public function __construct($service_key) {
		$this->url = 'https://events.pagerduty.com/generic/2010-04-15/create_event.json';
		$this->service_key = $service_key;
	}
		
	protected function processMessage($logger, $logMessage) {
		try {
			$session = curl_init();
			curl_setopt($session, CURLOPT_URL, $this->url);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode(array(
					"service_key" => $this->service_key,
					"event_type" => "trigger",
					"description" => $logMessage->text(),
					"details" => $logMessage->asRecord()
			)));
			curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-type' => 'application/json'));
			$result = curl_exec($session);
			curl_close($session);
		} catch (Exception $e) {
			// Fail silently
		}
	}
	
}
