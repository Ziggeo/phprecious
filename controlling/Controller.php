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
			self::log(Logger::INFO_3, "Dispatch action '{$action}' on controller '{$class}'");
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
			HttpHeader::setHeader("HTTP/1.1 " . HttpHeader::formatStatusCode($status, TRUE));
		else
			HttpHeader::setHeader("HTTP/1.1 " . $status . " " . $string);
	}	

    // The status and data of the response to the last request.
    private $last_status;
    private $last_data;

    /**
     * `get_last_status` returns the HTTP status code the controller
     * returned when processing its last request. It is useful for
     * testing the result controllers/routing.
     *
     * @return Http Status Code.
     */
    function get_last_status() {
        return $this->last_status;
    }

    /**
     * `get_last_data` returns the data the controller returned when
     * processing its last request. Controller, and controllers
     * extending Controller, are predominant users of this method.
     * Again, this is helpful for testing.
     *
     * @return JSON type data from last request.
     */
    function get_last_data() {
        return $this->last_data;
    }

	const RETURN_ENCODING_DEFAULT = 0;
	const RETURN_ENCODING_TEXTAREA = 1;
	const RETURN_ENCODING_POSTMESSAGE = 2;

    /**
     * Return a status without doing any formatting/json printing. This setting
     * is used heavily with controllers serving Static pages. The controllers
     * should still return a status code, but should not render their result in
     * JSON format.
     */
    const RETURN_ENCODING_STATUS_ONLY = 3;
	
	public $return_encoding = self::RETURN_ENCODING_DEFAULT;
	
	public $wrap_status = FALSE;

    /**
     * `return_status` sets a status using the header method and json encodes
     * and prints any data passed as an argument. It also saves copies of the
     * status and json for local access when testing.
     *
     * @param HttpHeader::Status $status The status code to return.
     * @param mixed $data The data to json encode.
     *
     * @return Boolean indicating successful status codes.
     */
	function return_status($status = HttpHeader::HTTP_STATUS_OK, $data = NULL) {
		$success = $status == HttpHeader::HTTP_STATUS_OK || $status == HttpHeader::HTTP_STATUS_CREATED;
		if ($this->wrap_status) {
			$data = array(
				"status" => $status,
				"responseText" => $data
			);
			$status = HttpHeader::HTTP_STATUS_OK;
		}
		if ($this->return_encoding == self::RETURN_ENCODING_DEFAULT) {
			$this->header_http_status($status);
		    HttpHeader::setHeader('Content-Type: application/json');
            /* Adding no-sniff and no-cache headers */
            if (in_array(Requests::getMethod(), array('POST', 'PUT', 'DELETE', 'UPDATE', 'PATCH'))) {
                HttpHeader::setHeader('Cache-Control: no-cache,no-store,must-revalidate');
                HttpHeader::setHeader('Pragma: no-cache');
            }
            HttpHeader::setHeader('X-Content-Type-Options: nosniff');
            $this->printData(json_encode($data));
		} elseif ($this->return_encoding == self::RETURN_ENCODING_TEXTAREA) {
			?><textarea data-type="application/json">{"success": <?= $success ? "true" : "false" ?>, "data": <?= json_encode($data) ?>}</textarea><?			
		} elseif ($this->return_encoding == self::RETURN_ENCODING_POSTMESSAGE) {
			$this->header_http_status($status);
			$this->printData("<!DOCTYPE html><script>parent.postMessage(JSON.stringify(" . json_encode($data) . "), '*');</script>");
        } elseif ($this->return_encoding == self::RETURN_ENCODING_STATUS_ONLY) {
            $this->header_http_status($status);
        }
        $this->last_status = $status;
        $this->last_data = $data;

		return $success;
	}

    protected function noCacheHeader() {
        HttpHeader::setHeader( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
        HttpHeader::setHeader( 'Cache-Control: no-store, no-cache, must-revalidate' );
        HttpHeader::setHeader( 'Pragma: no-cache' );
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

	function printData($s) {
        if (function_exists("custom_controller_print_data_function"))
            custom_controller_print_data_function($s);
        else
            print($s);
    }
	
}
