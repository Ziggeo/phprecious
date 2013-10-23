<?php

Class Cookies {
	
	private static function normalizeDomain($domain) {
		$a = split(":", $domain, 2);
		return $a[0];
	}
	
	public static function delete($name, $domain) {
		return Cookies::set($name, "", self::normalizeDomain($domain));
	}
	
	public static function get($name) {
		return @$_COOKIE[$name];
	}
	
	// if days == 0 then cookie set to expire at end of session
	public static function set($name, $value, $domain, $days = 30, $secure = false, $httponly = true) {
		$time = ($days > 0) ? time() + $days * 24 * 60 * 60  : 0;
		return setcookie($name, $value, $time, "/", self::normalizeDomain($domain), $secure, $httponly);
	}

}
