<?php

class Tokens {
	
	public static function generate($len = 32) {
		return static::random_hex($len);
	}
		
	public static function random_bytes($len) {
		if (function_exists("openssl_random_pseudo_bytes"))
			return openssl_random_pseudo_bytes($len, $cstrong);
		$bytes = "";
	    for ($i = 0; $i < $len; $i++)
	    	$bytes .= chr(mt_rand(0,255));
		return $bytes;
	}
	
	public static function random_hex($len) {
		return bin2hex(static::random_bytes(floor($len / 2)));
	}
	
}