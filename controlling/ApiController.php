<?php

require_once(dirname(__FILE__) . "/Controller.php");

Class ApiController extends Controller {
	
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
	
	public $return_encoding = ApiController::RETURN_ENCODING_DEFAULT;
	
	function return_status($status = HttpHeader::HTTP_STATUS_OK, $data = NULL) {
		if ($this->return_encoding == ApiController::RETURN_ENCODING_DEFAULT) {
			$this->header_http_status($status);
		    header('Content-Type: application/json');
			print json_encode($data);	
		} elseif ($this->return_encoding == ApiController::RETURN_ENCODING_TEXTAREA) {
			?><textarea data-type="application/json">{"success": <?= $status == NULL ? "true" : "false" ?>, "data": <?= json_encode($data) ?>}</textarea><?			
		}
		return $status == HttpHeader::HTTP_STATUS_OK;
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
