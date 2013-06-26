<?php

Class ParseType {
	
	public static function parseBool($s, $default = FALSE) {
		if (!isset($s))
			return $default;
		if (is_bool($s))
			return $s;
		if (is_numeric($s))
			return $s % 2 == 1;
		if (is_string($s)) {
			$lower = strtolower($s);
			if ($lower == "true" || $lower == "on" || $lower == "yes" || $lower == "1")
				return TRUE;
			if ($lower == "false" || $lower == "off" || $lower == "no" || $lower == "0")
				return FALSE;
			if ($lower == "null")
				return NULL;
		}
		return $default;
	}
	
}
