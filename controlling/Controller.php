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
		           call_user_func_array(array($this, "before_filter_" . $action), $args));
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
	
	function json_data($filter = NULL) {
		$raw = json_decode(Requests::getVar("data") ? Requests::getVar("data") : file_get_contents('php://input'), true);
		if (!is_array($filter))
			return $raw;
		$result = array();
		foreach ($filter as $key)
			if (@isset($raw[$key]))
				$result[$key] = $raw[$key];
		return $result;
	}

	function header_http_status($status, $string = NULL) {
		if ($string == NULL)
			header("HTTP/1.1 " . HttpHeader::formatStatusCode($status, TRUE));
		else
			header("HTTP/1.1 " . $status . " " . $string);
	}	


	const RETURN_ENCODING_DEFAULT = 0;
	const RETURN_ENCODING_TEXTAREA = 1;
	const RETURN_ENCODING_POSTMESSAGE = 2;
	
	public $return_encoding = ApiController::RETURN_ENCODING_DEFAULT;
	
	function return_status($status = HttpHeader::HTTP_STATUS_OK, $data = NULL) {
		$success = $status == HttpHeader::HTTP_STATUS_OK || $status == HttpHeader::HTTP_STATUS_CREATED;
		if ($this->return_encoding == ApiController::RETURN_ENCODING_DEFAULT) {
			$this->header_http_status($status);
		    header('Content-Type: application/json');
			print json_encode($data);	
		} elseif ($this->return_encoding == ApiController::RETURN_ENCODING_TEXTAREA) {
			?><textarea data-type="application/json">{"success": <?= $success ? "true" : "false" ?>, "data": <?= json_encode($data) ?>}</textarea><?			
		} elseif ($this->return_encoding == ApiController::RETURN_ENCODING_POSTMESSAGE) {
			$this->header_http_status($status);
			print "<!DOCTYPE html><script>parent.postMessage(JSON.stringify(" . json_encode($data) . "), '*');</script>";
		}
		return $success;
	}
	
	function return_forbidden($data = array()) {
		return $this->return_status("403", $data);
	}
	
	function return_not_found($data = array()) {
		return $this->return_status("404", $data);
	}

	function return_error($data = array()) {
		return $this->return_status("500", $data);
	}
	
	function return_success($data = array()) {
		return $this->return_status("200", $data);
	}
	
	function return_success_created($data = array()) {
		return $this->return_status("201", $data);
	}
	
}
