<?php

Class Requests {
	
	public static function getVar($name, $type = "") {
	    if ($type == "GET") $value = isset($_GET[$name]) ? $_GET[$name] : NULL;
	    elseif ($type == "POST") $value = isset($_POST[$name]) ? $_POST[$name] : NULL;
		else $value = isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : NULL);
		return $value == NULL ? NULL : stripslashes(strip_tags($value));
	}
	
	public static function getVarDef($name, $def) {
		$result = static::getVar($name);
		return isset($result) && $result ? $result : $def;
	}
	
	public static function getVarCheckBox($name) {
		$result = static::getVar($name);
		return isset($result) && ($result == "on" || $result == "true");
	}

	public static function getVarDefNull($name, $def) {
		$result = static::getVar($name);
		return isset($result) ? $result : $def;
	}

	public static function getMethod() {
		$method = static::getVar("_method");
		return $method ? $method : $_SERVER['REQUEST_METHOD'];
	}
	
	public static function getPath() {
		return static::getVar("path");
	}
	
	public static function buildPath($uri, $args) {
		$s = $uri;
		$c = "?";
		foreach ($args as $key=>$value) {
			$s .= $c . htmlentities($key) . "=" . htmlentities($value);
			$c = "&";
		}
		return $s;
	}

	/**
	 * Method to support get body data or POST data according what's set.
	 * Mostly used for updated methods coming from mobile clients.
	 *
	 * @return array|mixed
	 */
	public static function getRequestData() {
		$method = $_SERVER['REQUEST_METHOD'];
		if (in_array($method, array("POST", "PUT")) && count($_POST)) {
			return $_POST;
		} elseif (in_array($method, array("POST", "PUT")) && count($_POST) === 0) {
			return json_decode(file_get_contents("php://input"), TRUE);
		} elseif ($method === "GET") {
			return $_GET;
		}
		return array();
	}

	/**
	 * `readRequests` returns the values of requests variables for the array of
	 * keys passed as an argument.
	 *
	 * @param array $arr Array of variables to return the value of.
	 * @param boolean $return_null If the variables should be set in the `requests`
	 * array even if they are null.
	 *
	 * @return array
	 */

	public static function readRequests($arr, $return_null = true) {
		$result = array();
		foreach ($arr as $key) {
			if ($return_null) {
				$result[$key] = Requests::getVar($key);
			} else {
				$val = Requests::getVar($key);

				if (!is_null($val)) {
					$result[$key] = $val;
				}
			}
		}
		return $result;
	}

	/**
	 * Returns the value of request data stored in the ways expressed by each flag.
	 *
	 * $get = GET request data
	 * $post = POST request data
	 * $post_raw = input body stream as raw string
	 * $post_raw_json = input body stream as json string
	 *
	 * Merges all of the data into the $result array.
	 *
	 *
	 * @param bool $get
	 * @param bool $post
	 * @param bool $post_raw
	 * @param bool $post_raw_json
	 * @return array
	 */
	public static function getRequestArgs($get = FALSE, $post = FALSE, $post_raw = FALSE, $post_raw_json = FALSE) {
		$result = array();
		if ($get)
			$result = array_merge($result, $_GET);
		if ($post)
			$result = array_merge($result, $_POST);
		if ($post_raw || $post_raw_json) {
			$bodyinput = file_get_contents('php://input');
			if ($post_raw && count($_FILES) == 0) {
				try {
					$parsed = array();
					parse_str($bodyinput, $parsed);
					$result = array_merge($result, $parsed);
				} catch (Exception $e) {
				}
			}
			if ($post_raw_json) {
				$decoded = json_decode($bodyinput, TRUE);
				if (@$decoded)
					$result = array_merge($result, $decoded);
			}
		}
		return $result;
	}

}
