<?php

class SimpleStat extends DatabaseModel {
	
	private static $periods = array(
		"year" => array(
			"select" => array(
				"year"
			),
			"increment" => array(
				"year" => 1
			)
		),
		"month" => array(
			"select" => array(
				"year",
				"mon"
			),
			"increment" => array(
				"mon" => 1
			)
		),
		"day" => array(
			"select" => array(
				"year",
				"mon",
				"mday"
			),
			"increment" => array(
				"mday" => 1
			)			
		),
	);
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["start_date"] = array("type" => "date");
		$attrs["end_date"] = array("type" => "date");
		$attrs["period"] = array("type" => "string");
		$attrs["key"] = array("type" => "string");
		$attrs["value"] = array("type" => "integer");
		return $attrs;
	}
	
	public static function getPeriod($period, $time) {
		$date = getdate($time);
		$new_date = array();
		$new_date_inc = array();
		foreach ($period["select"] as $sel) {
			$new_date[$sel] = $date[$sel];
			$new_date_inc[$sel] = $date[$sel];
			if (isset($period["increment"][$sel]))
				$new_date_inc[$sel] += $period["increment"][$sel];
		}
		return array(
			"start_date" =>	mktime(0,0,0,
				isset($new_date["mon"]) ? $new_date["mon"] : 1,
				isset($new_date["mday"]) ? $new_date["mday"] : 1,
				isset($new_date["year"]) ? $new_date["year"] : 0
			),
			"end_date" => mktime(0,0,0,
				isset($new_date_inc["mon"]) ? $new_date_inc["mon"] : 1,
				isset($new_date_inc["mday"]) ? $new_date_inc["mday"] : 1,
				isset($new_date_inc["year"]) ? $new_date_inc["year"] : 0
			),
		);
	}
	
	public static function increment($key, $value = 1, $time = NULL) {
		if (!isset($time))
			$time = microtime();
		foreach (self::$periods as $type=>$period_conf) {
			$period = self::getPeriod($period_conf, TimeSupport::microtime_to_seconds($time));
			$row = self::findBy(array(
				"period" => $type,
				"start_date" => $period["start_date"],
				"key" => $key
			));
			if ($row) {
				// TODO: improve
				$row->update(array("value" => $row->value + $value));
			} else {
				$row = new SimpleStat(array(
					"period" => $type,
					"key" => $key,
					"start_date" => $period["start_date"], 
					"end_date" => $period["end_date"],
					"value" => $value
				));
				$row->create();
			}
		}
		
	}	
	
	public static function decrement($key, $value = 1, $time = NULL) {
		self::increment($key, -$value, $time);
	}
	
	public static function getStats($type, $date) {
		$result = self::getPeriod(self::$periods[$type], $date);
		$rows = self::allBy(array(
			"period" => $type,
			"start_date" => $result["start_date"]
		));
		$ret = array();
		foreach ($rows as $row)
			$ret[$row->key] = $row->value;
		ksort($ret);
		return $ret;
	}
	
	public static function getStatsByDate($year, $month = NULL, $day = NULL) {
		if (!isset($month))
			return self::getStats("year", mktime(0,0,0,1,1,$year));
		if (!isset($day))
			return self::getStats("month", mktime(0,0,0,$month,1,$year));
		return self::getStats("day", mktime(0,0,0,$month,$day,$year));
	}
	
	
}
