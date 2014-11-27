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
	
	public static function html_encode($data) {
		if (is_array($data)) {
			$s = "";
			if (count(array_filter(array_keys($data), 'is_string')))
				foreach ($data as $key=>$value)
					$s .= "<li>" . $key . ": " . self::html_encode($value) . "</li>";
			else	
				foreach ($data as $value)
					$s .= "<li>" . self::html_encode($value) . "</li>";
			return "<ul>" . $s . "</ul>";
		} else
			return "" . $data;
	}

	public static function simpleTemplate($begin, $end, $input, $callback) {
		return preg_replace_callback('/' . $begin . "(.*?(?=" . $end . "))" . $end . '/', $callback, $input);
	}
	
	public static function jsonLookup($json, $path) {
		$tokens = explode(".", $path);
		for ($i = 0; $i < count($tokens); ++$i) {
			if (isset($json[$tokens[$i]]))
				$json = $json[$tokens[$i]];
			else
				return NULL;
		}
		return $json;
	}
	
	public static function jsonTemplate($begin, $end, $input, $json) {
		return self::simpleTemplate($begin, $end, $input, function ($var) use ($json) {
			return StringUtils::jsonLookup($json, $var[1]);
		});
	}
	

}