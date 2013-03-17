<?php

Class TimeFormat {
	
	static $TIME_FORMAT;
	static $DATE_FORMAT;
	static $DATE_TIME_FORMAT;

	public static function format_microtime_nicely($t) {
		$sec_ago = TimeSupport::microtime_diff($t);
		if ($sec_ago < 120)
			return "one minute ago";
		elseif ($sec_ago < 60 * 60)
			return floor($sec_ago / 60) . " minutes ago";
		elseif ($sec_ago < 60 * 60 * 2)
			return "one hour ago";
		elseif ($sec_ago < 60 * 60 * 24)
			return floor($sec_ago / 60 / 60) . " hours ago";
		else
			return strftime(self::$DATE_FORMAT, TimeSupport::microtime_to_seconds($t));
		
	}
	
	public static function format_microtime_date($t) {
		return strftime(self::$DATE_FORMAT, TimeSupport::microtime_to_seconds($t));
	}
	
	public static function format_microtime_date_time($t) {
		return strftime(self::$DATE_TIME_FORMAT, TimeSupport::microtime_to_seconds($t));
	}
	
	public static function format_microtime_time($t) {
		return strftime(self::$TIME_FORMAT, TimeSupport::microtime_to_seconds($t));
	}

}

TimeFormat::$TIME_FORMAT = "%I:%M %p";
TimeFormat::$DATE_FORMAT = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? "%b %#d, %Y" : "%b %e, %Y";
TimeFormat::$DATE_TIME_FORMAT = TimeFormat::$DATE_FORMAT . " at " . TimeFormat::$TIME_FORMAT;