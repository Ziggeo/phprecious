<?php

class TimeSupport {
	
	public static function ensure_seconds($time) {
		if (is_numeric($time))
			return intval($time);
		if (is_object($time)) {
			if (get_class($time) == "TimePointObj")
				return $time->seconds();
			$time = $time . "";
		} 
		$arr = explode(" ", $time);
		if (count($arr) == 2 && is_numeric($arr[0]) && is_numeric($arr[1]))
			return intval($arr[1]);
		return strtotime($time, 0);
	}
	
	public static function microtime_diff($start, $end=NULL) { 
	    if (!$end)
	    	$end= microtime(); 
	    list($start_usec, $start_sec) = explode(" ", $start); 
	    list($end_usec, $end_sec) = explode(" ", $end); 
	    $diff_sec= intval($end_sec) - intval($start_sec); 
	    $diff_usec= floatval($end_usec) - floatval($start_usec); 
	    return floatval( $diff_sec ) + $diff_usec; 
	} 
	
	public static function microtime_to_seconds($mt = NULL) {
		if (!@$mt)
			$mt = microtime();
		$arr = explode(" ", $mt);
		if (count($arr) == 2 && is_numeric($arr[0]) && is_numeric($arr[1]))
			return intval($arr[1]);
		return $arr[0];
	}
	
	public static function seconds_to_microtime($seconds) {
		return "0.00000000 " . $seconds;
	}

	public static function is_valid_timestamp($timestamp) {
		return ((string) (int) $timestamp == $timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
	}

}



class TimePoint {
	
	public static function fromNow($seconds) {
		return new TimePointObj(time() + TimeSupport::ensure_seconds($seconds));
	}
	
	public static function beforeNow($seconds) {
		return new TimePointObj(time() - TimeSupport::ensure_seconds($seconds));
	}

	public static function get($seconds = NULL) {
		return new TimePointObj($seconds);
	}
	
	public static function ensure($tp, $default = null) {
		if (!@$tp)
			return @$default ? $default : new TimePointObj();
		if (!is_object($tp) || !($tp instanceof TimePointObj))
			return new TimePointObj($tp);
		return $tp;
	}
	
}

class TimePeriod {
	
	public static function seconds($seconds) {
		return self::get($seconds);
	}

	public static function minutes($minutes) {
		return self::seconds($minutes * 60);
	}

	public static function hours($hours) {
		return self::minutes($hours * 60);
	}
	
	public static function days($days) {
		return self::hours($days * 24);
	}

	public static function months($months) {
		return self::days($months * 30);
	}

	public static function get($seconds) {
		return new TimePeriodObj($seconds);
	}
	
	public static function ensure($tp, $default = null) {
		if (!@$tp)
			return @$default ? $default : new TimePeriodObj();
		if (!is_object($tp))
			return new TimePeriodObj($tp);
		return $tp;
	}
	
}

class AbstractTimeObj {
		
	protected $seconds;
	
	public function __construct($seconds = null) {
		$this->seconds = @$seconds ? TimeSupport::ensure_seconds($seconds) : 0;
	}
	
	public function seconds() {
		return $this->seconds;
	}
	
	public function microtime() {
		return TimeSupport::seconds_to_microtime($this->seconds());
	}
	
}

class TimePointObj extends AbstractTimeObj {
	
	public function __construct($seconds = null) {
		$this->seconds = @$seconds ? TimeSupport::ensure_seconds($seconds) : time();
	}
	
	public function increment($timeperiod) {
		return TimePoint::get($this->seconds() + TimePeriod::ensure($timeperiod)->seconds());
	}
	
	public function decrement($timeperiod) {
		return TimePoint::get($this->seconds() - TimePeriod::ensure($timeperiod)->seconds());
	}

	public function earlier($tp = null) {
		return $this->seconds() < TimePoint::ensure($tp)->seconds();
	}
		
	public function later($tp = null) {
		return $this->seconds() > TimePoint::ensure($tp)->seconds();
	}
	
	public function elapsed($tp = null) {
		return TimePeriod::seconds(TimePoint::ensure($tp)->seconds() - $this->seconds());
	}

}



class TimePeriodObj extends AbstractTimeObj {
	
	public function fromNow() {
		return TimePoint::fromNow($this->seconds);
	}
	
	public function from($timepoint) {
		return TimePoint::ensure($timepoint)->increment($this);
	}
	
}
