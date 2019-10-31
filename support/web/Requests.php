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
	
}
