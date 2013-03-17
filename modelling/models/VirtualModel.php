<?php

require_once(dirname(__FILE__) . "/ActiveModel.php");

class VirtualModel extends ActiveModel {
	
	private static $database = array();
	
	private static function &database() {
    	$class = get_called_class();
		if (!@self::$database[$class]) 
			self::$database[$class] = array(
				"primary" => 0,
				"table" => array()
			);
		return self::$database[$class];
	}
	
	public static function &table() {
		$db = self::database();
		return $db["table"];
	}
	
	protected static function idKey() {
		return "id";
	} 	
	
	protected static function findRowById($id) {
		$tab = self::table();
		return @$tab[$id];
	}

	// Note: options NOT implemented
	protected static function allRows($options = NULL) {
		return self::table();
	}

	protected static function findRowBy($query) {
		if ($query[self::idKey()])
			return self::findRowById($query[self::idKey()]);
		foreach (self::table() as $row) {
			$match = TRUE;
			foreach ($query as $key=>$value)
				if ($row[$key] != $value) {
					$match = FALSE;
					break;
				}
			if ($match)
				return $row;
		}
		return NULL;
	}

	// Note: options NOT implemented
	protected static function allRowsBy($query, $options = NULL) {
		if (@$query[self::idKey()]) {
			$row = self::findRowById($query[self::idKey()]);
			if ($row)
				return array($row);
			else
				return NULL;
		}
		$result = array();
		foreach (self::table() as $row) {
			$match = TRUE;
			foreach ($query as $key=>$value)
				if ($row[$key] != $value) {
					$match = FALSE;
					break;
				}
			if ($match)
				$result[] = $row;
		}
		return $result;
	}

	protected function createModel() {
		$db = &self::database();
		$tab = &$db["table"];
		$id = $db["primary"];
		$attrs = $this->attrs();
		if (isset($attrs[self::idKey()])) {
			$id = $attrs[self::idKey()];
			if (@$tab[$id])
				return FALSE;
		}
		$attrs = $this->filterPersistentAttrs($attrs);
		$attrs[self::idKey()] = $id;
		$tab[$id] = $attrs;
		if ($id >= $db["primary"])
			$db["primary"] = $id + 1;
		return $id;
	}
	
	protected function updateModel() {
		$tab = &self::table();
		$id = $this->id();
		if ($tab[$id]) {
			$update = $this->filterPersistentAttrs($this->attrsChanged());
			foreach ($update as $key=>$value)		
				$tab[$id][$key] = $value;
			return TRUE;
		}
		return FALSE;
	}
	
	protected function deleteModel() {
		$tab = &self::table();
		if ($tab[$this->id()]) {
			unset($tab[$this->id()]);
			return TRUE;
		}
		return FALSE;
	}
	
	public static function populate($rows) {
		foreach ($rows as $key=>$value) {
			$class = get_called_class();
			$obj = new $class($value);
			$obj->setAttr(static::idKey(), $key);
			$obj->create();
		}
	}

}
