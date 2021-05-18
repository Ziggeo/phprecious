<?php

require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerStdErrorConsumer extends LoggerConsumer {
	
	protected function processMessage($logger, $logMessage) {
		fwrite(STDERR, $logMessage->formatOneLine() . PHP_EOL);
	}
	
	
}

