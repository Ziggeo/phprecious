<?php

require_once(dirname(__FILE__) . "/../../support/strings/ParseType.php");
require_once(dirname(__FILE__) . "/../../support/strings/StringUtils.php");
require_once(dirname(__FILE__) . "/../../support/data/Iterators.php");
require_once(dirname(__FILE__) . "/../../support/time/TimeSupport.php");
require_once(dirname(__FILE__) . "/ActiveModel.php");

class DatabaseModel extends ActiveModel {
    
    private static $table = array();

	protected static function multiTableInheritance() {
		return NULL;
	}

    protected static function getDatabase() {
        global $database;
		if (isset($database))
			return $database;
		if (is_callable("DATABASE"))
			return DATABASE();
		return NULL;
    }

    public static function encodeData($attrs) {
        $sch = static::classScheme();
        $result = array();
        foreach ($attrs as $key => $value) {
            $result[$key] = $value;
            $meta = @$sch[$key];
            if (isset($meta) && isset($meta["type"])) {
                if (is_array($value) && count($value) == 1) {
                    $keys = array_keys($value);
                    $keys = $keys[0];
                    if (StringUtils::startsWith($keys, '$')) {
                        $result[$key] = array($keys => static::getDatabase()->encode($meta["type"], $value[$keys]));
                        continue;
                    }
                }
                $result[$key] = static::getDatabase()->encode($meta["type"], $value);
            }
        }
        return $result;
    }

    public static function decodeData($attrs) {
        $sch = static::classScheme();
        $result = array();
        foreach ($attrs as $key => $value) {
            $result[$key] = $value;
            if (!isset($sch[$key]))
                continue;
            $meta = $sch[$key];
            if (isset($meta["type"])) 
                $result[$key] = static::getDatabase()->decode($meta["type"], $value);
        }
        return $result;
    }

    protected static function materializeClass($attrs) {
    	$multi = static::multiTableInheritance();
		if ($multi !== NULL)
	        return $attrs[$multi["typeColumn"]];
		return parent::materializeClass($attrs);
    }

    public static function tableName() {
    	$multi = static::multiTableInheritance();
		if ($multi !== NULL)
			return $multi["tableName"];
        $class = get_called_class();
        $name = strtolower($class) . "s";
        return $name;
    }
    
    public static function table($force = FALSE) {
        $class = get_called_class();
        if (!isset(self::$table[$class]) || $force)
        	self::$table[$class] = static::getDatabase()->selectTable(static::tableName());
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
    	$multi = static::multiTableInheritance();
		if ($multi !== NULL) {
	        $attrs[$multi["typeColumn"]] = array(
	            "type" => "string",
	            "index" => TRUE,
	            "default" => function ($instance) {
	                return get_class($instance);
	            },
	            "validate" => array(new PresentValidator())
	        );
		}
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
    
    protected static function countModels($query = array()) {
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