<?php

require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerConsumerFilter extends LoggerConsumer {


	/*
	 * options:
	 * 	"tags" => set of tags
	 *  "antitags" => set of antitags
	 *  "level" => number - only if level is less or equal to number 
	 */
	 	
	private $options;
	private $consumer;
	
	public function __construct($consumer, $options) {
		$this->consumer = $consumer;
		$this->options = $options;
	}
	
	protected function messageApplies($logger, $logMessage) {
		if (@$this->options["level"] && $logMessage->level() > $this->options["level"])
			return FALSE;
		if (@$this->options["tags"]) {
			$found = FALSE;
			$mytags = $this->options["tags"];
			$tags = $logMessage->tags();
			foreach ($mytags as $tag) {
				if (in_array($tag, $tags)) {
					$found = TRUE;
					break;
				}
			}
			if (!$found)
				return FALSE;
		}
		if (@$this->options["antitags"]) {
			$found = FALSE;
			$mytags = $this->options["antitags"];
			$tags = $logMessage->tags();
			foreach ($mytags as $tag) {
				if (in_array($tag, $tags)) {
					$found = TRUE;
					break;
				}
			}
			if ($found)
				return FALSE;
		}
		return TRUE;
	}
	
	protected function processMessage($logger, $logMessage) {
		$this->consumer->process($logger, $logMessage);
	}
	
}
