<?php


require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerSlackConsumer extends LoggerConsumer {
	
	private $slackChannel;
	private $slackUsername;
	private $slackWebhookUri;
	
	public function __construct($slackWebhook, $slackChannel, $slackUsername) {
		$this->slackChannel = $slackChannel;
		$this->slackUsername = $slackUsername;
		$this->slackWebhookUri = $slackWebhook;
	}
		
	protected function processMessage($logger, $logMessage) {
		$ch = curl_init($this->slackWebhookUri);
		$data = json_encode([
			"channel" => APP()->config("slack.channel"),
			"text" => $logMessage->formatOneLine(),
			"username" => APP()->config("slack.username"),
		]);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, "payload=" . $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}

