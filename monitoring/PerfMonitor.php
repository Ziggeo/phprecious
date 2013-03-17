<?php

require_once(dirname(__FILE__) . "/../logging/Logger.php");

class PerfMonitor {
	
	protected static function log($level, $s) {
		global $LOGGER;
		if (@$LOGGER)
			$LOGGER->message("framework.perfmonitor", $level, $s);
	}

	private $sections = array();
	
	private function touchSection($section) {
		if (!@$this->sections[$section]) {
			$this->sections[$section] = array();
			$this->sections[$section]["time"] = 0.0;
			$this->sections[$section]["stack"] = array(0);
		}
	}
	
	public function enter($section) {
		$this->touchSection($section);
		$i = count($this->sections[$section]["stack"]) - 1;
		$a = $this->sections[$section]["stack"][$i];
		$this->sections[$section]["stack"][$i] = $a + 1; 
		if ($a == 0)
			$this->sections[$section]["timer"] = microtime(TRUE);
	}
	
	public function leave($section) {
		$this->touchSection($section);
		$i = count($this->sections[$section]["stack"]) - 1;
		$a = $this->sections[$section]["stack"][$i];
		$this->sections[$section]["stack"][$i] = $a - 1; 
		if ($a == 1)
			$this->sections[$section]["time"] = $this->sections[$section]["time"] + microtime(TRUE) - $this->sections[$section]["timer"];
	}
	
	public function leaveCall($section) {
		$this->touchSection($section);
		$i = count($this->sections[$section]["stack"]) - 1;
		$a = $this->sections[$section]["stack"][$i];
		array_push($this->sections[$section]["stack"], 0);
		if ($a > 0)
			$this->sections[$section]["time"] = $this->sections[$section]["time"] + microtime(TRUE) - $this->sections[$section]["timer"];
	}
	
	public function enterReturn($section) {
		$this->touchSection($section);
		$a = array_pop($this->sections[$section]["stack"]); 
		if ($a > 0)
			$this->sections[$section]["time"] = $this->sections[$section]["time"] + microtime(TRUE) - $this->sections[$section]["timer"];
		$i = count($this->sections[$section]["stack"]) - 1;
		$b = $this->sections[$section]["stack"][$i];
		if ($b > 0)
			$this->sections[$section]["timer"] = microtime(TRUE);
	}
	
	public function digest($annotations = array()) {
		$s = "";
		foreach ($annotations as $key=>$value)
			if (@$value)
				$s .= $key . "=" . $value . "; ";
		foreach ($this->sections as $key=>$section)
			$s .= $key . "=" . number_format($section["time"], 4) . "; ";
		return $s;
	}
	
	public function read($section) {
		return $this->sections[$section]["time"];
	}
	
	public function logMsg($annotations = array()) {
		static::log(LOGGER::INFO, "Performance Digest: " . $this->digest($annotations));
	}
	
	public function logMsgDB($db, $tablename, $annotations = array()) {
		$data = array();
		$data["created"] = $db->encodeDate();
		foreach ($annotations as $key=>$value)
			if (@$value)
				$data[$key] = $value;
		foreach ($this->sections as $key=>$section)
			$data[$key]=$section["time"];
		$db->selectTable($tablename)->insert($data);
	}
	
	
	
}
