<?php

require_once(dirname(__FILE__) . "/Controller.php");

Class ApiController extends Controller {
	
	const RETURN_ENCODING_DEFAULT = 0;
	const RETURN_ENCODING_TEXTAREA = 1;
	
	public $return_encoding = ApiController::RETURN_ENCODING_DEFAULT;
	
	function jsonData($filter = NULL) {
		$raw = json_decode(Requests::getVar("data") ? Requests::getVar("data") : file_get_contents('php://input'), true);
		if (!is_array($filter))
			return $raw;
		$result = array();
		foreach ($filter as $key)
			if (@isset($raw[$key]))
				$result[$key] = $raw[$key];
		return $result;
	}
	
	function return_state ($state, $result) {
		if ($this->return_encoding == ApiController::RETURN_ENCODING_DEFAULT) {
			if ($state != NULL)
				header('HTTP/1.1 ' . $state);
		    header('Content-Type: application/json');
			print json_encode($result);	
		} elseif ($this->return_encoding == ApiController::RETURN_ENCODING_TEXTAREA) {
?><textarea data-type="application/json">
{"success": <?= $state == NULL ? "true" : "false" ?>, "data": <?= json_encode($result) ?>}
</textarea><?			
		}
		return $state == NULL;
	}

	function return_forbidden($result = array()) {
		return $this->return_state("403 Forbidden", $result);
	}
	
	function return_not_found($result = array()) {
		return $this->return_state("404 Not Found", $result);
	}

	function return_error($result = array()) {
		return $this->return_state("500 Internal Server Error", $result);
	}
	
	function return_success($result = array()) {
		return $this->return_state("200 OK", $result);
	}
	
	function return_success_created($result = array()) {
		return $this->return_state("201 Created", $result);
	}

}
