<?php

require_once(dirname(__FILE__) . "/../../support/strings/ParseType.php");
require_once(dirname(__FILE__) . "/ActiveModel.php");

class DatabaseModel extends ActiveModel {
	
	private static $table = array();
	
	protected static function getDatabase() {
		global $database;
		return $database;
	}

	public static function encodeData($attrs) {
		$sch = static::classScheme();
		$result = array();
		foreach ($attrs as $key => $value) {
			$result[$key] = $value;
			$meta = @$sch[$key];
			if (isset($meta) && isset($meta["type"])) 
				$result[$key] = static::getDatabase()->encode($meta["type"], $value);
		}
		return $result;
	}

	public static function decodeData($attrs) {
		$sch = static::classScheme();
		$result = array();
		foreach ($attrs as $key => $value) {
			$result[$key] = $value;
			$meta = @$sch[$key];
			if (isset($meta) && isset($meta["type"])) 
				$result[$key] = static::getDatabase()->decode($meta["type"], $value);
		}
		return $result;
	}

    public static function tableName() {
    	$class = get_called_class();
        $name = strtolower($class) . "s";
        return $name;
    }
	
	public static function table() {
    	$class = get_called_class();
		if (!@self::$table[$class]) self::$table[$class] = static::getDatabase()->selectTable(static::tableName());
		return self::$table[$class];
	}
	
	public static function idKey() {
		return self::table()->primaryKey();
	} 	
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["created"] = array(
			//"readonly" => TRUE,
			"index" => TRUE,
			"type" => "date",
		);
		$attrs["updated"] = array(
			//"readonly" => TRUE,
			"index" => TRUE,
			"type" => "date",
		);
		return $attrs;
	}
	
	protected function beforeUpdate() {
		parent::beforeUpdate();
		if ($this->hasChanged())
			$this->setAttr("updated", time(), TRUE);
	}
	
	protected function beforeCreate() {
		parent::beforeCreate();
		$t = time();
		if (!@$this->created)
			$this->setAttr("created", $t, TRUE);  
		if (!@$this->updated)
			$this->setAttr("updated", $t, TRUE);  
	}	
	
	public static function count($query = array()) {
		return self::table()->count(self::encodeData($query));
	}

	protected function incAttr($key, $value) {
		return self::table()->incrementCell(static::getDatabase()->encode("id", $this->id()), $key, $value);
	}

	protected function createModel() {
		$table = self::table();
		$attrs = self::encodeData($this->filterPersistentAttrs($this->attrs()));
        $success = $table->insert($attrs);
		if ($success)
			return @static::getDatabase()->decode("id", $attrs[self::idKey()]);
		return FALSE;		
	}
	
	protected function updateModel() {
		return self::table()->updateRow(static::getDatabase()->encode("id", $this->id()), self::encodeData($this->filterPersistentAttrs($this->attrsChanged())));		
	}
	
	protected function deleteModel() {
		return self::table()->removeRow(static::getDatabase()->encode("id", $this->id()));
	}

	protected static function findRowById($id) {
		$result = self::table()->findRow(static::getDatabase()->encode("id", $id));
		return $result == NULL ? $result : self::decodeData($result);
	}

	protected static function findRowBy($query) {
		$result = self::table()->findOne(self::encodeData($query));
		return $result == NULL ? $result : self::decodeData($result);
	}

	protected static function allRows($options = NULL) {
		$result = self::table()->find(array(), $options);
		$cls = get_called_class();
		return new MappedIterator($result, function ($row) use ($cls) {
			return $cls::decodeData($row);
		});
	}

	protected static function allRowsBy($query, $options = NULL) {
		$result = self::table()->find(self::encodeData($query), $options);
		$cls = get_called_class();
		return new MappedIterator($result, function ($row) use ($cls) {
			return $cls::decodeData($row);
		});
	}
	
	public static function ensureIndices() {
		foreach (static::classIndices() as $index) 
			self::table()->ensureIndex($index);
	}
}