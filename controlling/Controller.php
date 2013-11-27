<?php

require_once(dirname(__FILE__) . "/../logging/Logger.php");

Class Controller {
	
	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("controller");
			else
				$PERFMON->leave("controller");
		}
	}

	protected static function log($level, $s) {
		global $LOGGER;
		if (@$LOGGER)
			$LOGGER->message("controller", $level, $s);
	}

	public function dispatch($action, $args = array()) {
		static::perfmon(true);
		$result = (method_exists($this, $action) && $this->before_filter($action, $args)) &&
		          (!method_exists($this, "before_filter_" . $action) ||
		           call_user_method_array("before_filter_" . $action, $this, $args));
		if ($result) {
			$class = get_called_class();
			self::log(Logger::INFO_2, "Dispatch action '{$action}' on controller '{$class}'");
			$this->call_action($action, $args);
		}
		static::perfmon(true);
		return $result;
	}
	
	protected function call_action($action, $args) {
		call_user_func_array(array($this, $action), $args);
	}
	
	protected function before_filter($action, $args = array()) {
		$assert = $this->assert_filter($action, $args);
		if (isset($assert[$action])) {
			$asserts = $assert[$action];
			if (!is_array($asserts))
				$asserts = array($asserts);
			$props = $this->filter_properties();
			foreach ($asserts as $key) {
				$prop = $props[$key];
				if (!$prop["check"]($action, $args)) {
					$prop["error"]($action, $args);
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	
	protected function filter_properties() {
		return array();
	}
	
	protected function assert_filter($action, $args = array()) {
		return array();
	}
	
	public function read_requests($arr) {
		$result = array();
		foreach ($arr as $key)
			$result[$key] = Requests::getVar($key);
		return $result;
	}	
	
}
