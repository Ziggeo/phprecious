<?php

Class Requests {
	
	public static function getVar($name, $type = "") {
	    if ($type == "GET") $value = @$_GET[$name];
	    elseif ($type == "POST") $value = @$_POST[$name];
		else $value = @$_GET[$name] ? @$_GET[$name] : @$_POST[$name];
	    $value = stripslashes(strip_tags($value));
		return $value;
	}
	
	public static function getVarDef($name, $def) {
		$result = static::getVar($name);
		return isset($result) && $result ? $result : $def;
	}
	
	public static function getVarCheckBox($name) {
		$result = static::getVar($name);
		return isset($result) && ($result == "on");
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
	
}
