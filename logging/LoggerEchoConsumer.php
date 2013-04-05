<?php

require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerEchoConsumer extends LoggerConsumer {
	
	protected function processMessage($logger, $logMessage) {
		echo $logMessage->formatOneLine();
	}
	
	
}

