<?php

Class ArrayUtils {
	
	private static function getValue($item, $key) {
		return "" . (is_array($item) ? $item[$key] : $item->$key);
	}
	
	public static function groupBy($arr, $keys) {
		$result = array();
		if (!is_array($keys))
			$keys = array($keys);
		foreach ($arr as $item) {
			$el = &$result;
			foreach ($keys as $key) {
				$key_value = self::getValue($item, $key);
				if (!isset($el[$key_value]))
					$el[$key_value] = array();
				$el = &$el[$key_value];
			}
			$el[] = $item;
		}
		return $result;
	}
	
	private static function groupByAggregateHelper($arr, $keys, $aggregator, &$acc) {
		if ($keys == 0)
			$acc[] = $aggregator($arr);
		else
			foreach ($arr as $item)
				self::groupByAggregateHelper($item, $keys-1, $aggregator, $acc);
		return $acc;
	}
	
	public static function groupByAggregate($arr, $keys, $aggregator) {
		$result = array();
		return self::groupByAggregateHelper(self::groupBy($arr, $keys), count($keys), $aggregator, $result);
	}

	public static function exists($arr, $cb) {
		foreach ($arr as $item)
			if ($cb($item))
				return TRUE;
		return FALSE;
	}
	
	public static function filter($arr, $cb) {
		$result = array();
		if (is_array($cb)) {
			foreach ($cb as $key)
				if (isset($arr[$key]))
					$result[$key] = @$arr[$key];
		} else {
			foreach ($arr as $key=>$value)
				if ($cb($key, $value))
					$result[$key] = $value;
		}
		return $result;
	}
	
	public static function removeNull($arr) {
		return self::filter($arr, function ($key, $value) {
			return $value != NULL;
		});
	}
	
	public static function subset($subset, $set) {
		return count(array_intersect($subset, $set)) == count($subset);		
	}
	
	public static function removeByIndex(&$input, $index) {
		$item = $input[$index];
		array_splice($input, $index, 1);
		return $item;
	}
	
	public static function insert(&$input, $index, $data) {
		array_splice($input, $index, 0, array($data));
	}
	
	public static function removeByValue(&$input, $value) {
		if(($key = array_search($value, $input)) !== false)
    		self::removeByIndex($input[$key]);
	}
	
}
