<?php

require_once(dirname(__FILE__) . "/../../support/strings/ParseType.php");
require_once(dirname(__FILE__) . "/ActiveModel.php");

class DatabaseModel extends ActiveModel {
	
	private static $table = array();
	
	protected static function getDatabase() {
		global $database;
		return $database;
	}

	private static function encodeData($attrs) {
		$sch = static::classScheme();
		$result = array();
		foreach ($attrs as $key => $value) {
			$result[$key] = $value;
			$meta = @$sch[$key];
			if (isset($meta) && isset($meta["type"])) {
				if ($meta["type"] == "id" && $value != NULL)
					$result[$key] = static::getDatabase()->encodePrimaryKey($value);
				elseif ($meta["type"] == "date" && $value != NULL)
					$result[$key] = static::getDatabase()->encodeDate($value);
				elseif ($meta["type"] == "boolean" && $value != NULL && !is_bool($value))
					$result[$key] = ParseType::parseBool($value);
			}
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
			"readonly" => TRUE,
			"index" => TRUE
		);
		$attrs["updated"] = array(
			"readonly" => TRUE,
			"index" => TRUE
		);
		return $attrs;
	}
	
	protected function beforeUpdate() {
		parent::beforeUpdate();
		if ($this->hasChanged())
			$this->setAttr("updated", self::table()->getDatabase()->encodeDate(), TRUE);
	}
	
	protected function beforeCreate() {
		parent::beforeCreate();
		$this->setAttr("created", self::table()->getDatabase()->encodeDate(), TRUE);  
		$this->setAttr("updated", self::table()->getDatabase()->encodeDate(), TRUE);  
	}	
	
	public static function count($query) {
		return self::table()->count($query);
	}

	protected function incAttr($key, $value) {
		return self::table()->incrementCell($this->id(), $key, $value);
	}

	protected function createModel() {
		$table = self::table();
		$attrs = self::encodeData($this->filterPersistentAttrs($this->attrs()));
        $success = $table->insert($attrs);
		if ($success) return @$attrs[self::idKey()];
		return FALSE;		
	}
	
	protected function updateModel() {
		return self::table()->updateRow($this->id(), self::encodeData($this->filterPersistentAttrs($this->attrsChanged())));		
	}
	
	protected function deleteModel() {
		return self::table()->removeRow($this->id());
	}

	protected static function findRowById($id) {
		return self::table()->findRow($id);
	}

	protected static function findRowBy($query) {
		return self::table()->findOne(self::encodeData($query));
	}

	protected static function allRows($options = NULL) {
		return self::table()->find(array(), $options);
	}

	protected static function allRowsBy($query, $options = NULL) {
		return self::table()->find(self::encodeData($query), $options);
	}
	
	public static function ensureIndices() {
		foreach (static::classIndices() as $index) 
			self::table()->ensureIndex($index);
	}
}
