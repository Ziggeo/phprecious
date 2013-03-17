<?php


abstract class LoggerConsumer {
	
	protected function messageApplies($logger, $logMessage) {
		return TRUE;
	}
	
	protected abstract function processMessage($logger, $logMessage);
	
	public function process($logger, $logMessage) {
		if ($this->messageApplies($logger, $logMessage))
			$this->processMessage($logger, $logMessage);
	}
	
}
