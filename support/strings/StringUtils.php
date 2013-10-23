<?php

Class StringUtils {

	public static function startsWith($haystack, $needle) {
	    return !strncmp($haystack, $needle, strlen($needle));
	}

	public static function ends_with($string, $test) {
		$strlen = strlen($string);
		$testlen = strlen($test);
		if ($testlen > $strlen)
			return false;
		return substr_compare($string, $test, -$testlen) === 0;
	}
	
	public static function has_sub($string, $sub) {
		return strpos($string, $sub) !== false;
	}
	
	public static function subBefore($string, $sub) {
		$arr = explode($sub, $string, 2);
		return $arr[0];
	}		

}
