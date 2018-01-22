<?php

require_once(dirname(__FILE__) . "/LogMessage.php");

class Logger {
	
	const ERROR = 1;
	const WARNING = 2;
	const WARN = 2;
	const INFO = 3;
	const INFO_2 = 4;
	const INFO_3 = 5;
	
	private $consumers = array();
	private $fields = array();
	
	private function process($logMessage) {
		foreach ($this->consumers as $consumer)
			$consumer->process($this, $logMessage);
	}
	
	public function message($tags, $level, $text) {
		$f = array();
		foreach ($this->fields as $key=>$valuefunc)
			$f[$key] = is_callable($valuefunc) ? $valuefunc($this, $tags, $level, $text) : $valuefunc;
		$this->process(new LogMessage($tags, $level, $text, $f));
	}
	
	public function registerConsumer($consumer) {
		$this->consumers[] = $consumer;
	}
	
	public function registerField($key, $valuefunc) {
		$this->fields[$key] = $valuefunc;
	}
	
}
