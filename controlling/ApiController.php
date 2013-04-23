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

	function return_forbidden($result = array()) {
		if ($this->return_encoding == ApiController::RETURN_ENCODING_DEFAULT) {
			header('HTTP/1.1 403 Forbidden');
		    header('Content-Type: application/json');
		} elseif ($this->return_encoding == ApiController::RETURN_ENCODING_TEXTAREA) {
?><textarea data-type="application/json">
{"success": false, "data": <?= json_encode($result) ?>}
</textarea><?			
		}
	    return FALSE;
	}
	
	function return_error($result = array()) {
		if ($this->return_encoding == ApiController::RETURN_ENCODING_DEFAULT) {
			header('HTTP/1.1 500 Internal Server Error');
		    header('Content-Type: application/json');
			print json_encode($result);	
		} elseif ($this->return_encoding == ApiController::RETURN_ENCODING_TEXTAREA) {
?><textarea data-type="application/json">
{"success": false, "data": <?= json_encode($result) ?>}
</textarea><?			
		}
	    return FALSE;
	}
	
	function return_success($result = array()) {
		if ($this->return_encoding == ApiController::RETURN_ENCODING_DEFAULT) {
			header('Content-Type: application/json');
		    print json_encode($result);	
		} elseif ($this->return_encoding == ApiController::RETURN_ENCODING_TEXTAREA) {
?><textarea data-type="application/json">
{"success": true, "data": <?= json_encode($result) ?>}
</textarea><?			
		}
	    return TRUE;
	}
	
}
