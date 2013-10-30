<?php

require_once(dirname(__FILE__) . "/../../logging/Logger.php");
require_once(dirname(__FILE__) . "/Model.php");

abstract class ActiveModel extends Model {
	
	protected static function log($level, $s) {
		global $LOGGER;
		if (@$LOGGER)
			$LOGGER->message("model", $level, $s);
	}
	
	public static function idKey() {
		return "id";
	} 
	
	public function id() {
		return $this->getAttr(static::idKey());
	}
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs[static::idKey()] = array(
			"readonly" => TRUE,
			"type" => "id"
		);
		return $attrs;
	} 

	private $saved = FALSE;
	private $newModel = TRUE;
	private $deleted = FALSE;
	
	public function isSaved() {
		return $this->saved;
	}
	
	protected function beforeSave() {
	}
	
	protected function afterSave() {
	}
	
	protected function beforeUpdate() {
		$this->beforeSave();
	}
	
	protected function beforeCreate() {
		$this->beforeSave();
	}
	
	protected function afterUpdate() {
		$this->afterSave();
	}
	
	protected function afterCreate() {
		$this->afterSave();
	}

	// Save either creates or updates the model
	public function save() {
		if ($this->saved)
			return $this->update();
		else
			return $this->create(); 
	}
	
	public function create() {
		if ($this->saved || $this->deleted || !$this->isValid())
			return FALSE;
		$this->beforeCreate();
		$id = $this->createModel();
		if (isset($id)) {
			$this->saved = TRUE;
			$this->setAttr(static::idKey(), $id);
			$this->resetChanged();
			static::log(Logger::INFO_2, "Created model '" . get_called_class() . "' with id {$this->id()}.");
			$this->afterCreate();
			return TRUE;
		}
		else
			static::log(Logger::WARN, "Failed to create model '" . get_called_class() . "'.");
		return FALSE;
	}
	
	protected abstract function createModel();

	public function update($attrs = array(), $force = FALSE) {
		if (!$this->saved || $this->deleted)
			return FALSE;
		foreach ($attrs as $key => $value)
			$this->$key = $value;
		if (!$force && !$this->isValid())
			return FALSE;
		$this->beforeUpdate();
		$success = !$this->hasChanged() || $this->updateModel();
		if ($success) {
			static::log(Logger::INFO_2, "Updated model '" . get_called_class() . "' with id {$this->id()}.");
			$this->saved = TRUE;
			$this->newModel = FALSE;
			$this->resetChanged();
			$this->afterUpdate();
		}
		else
			static::log(Logger::WARN, "Failed to update model '" . get_called_class() . "' with id {$this->id()}.");
		return $success;
	}
	
	public function updatables($attrs = array(), $save = FALSE) {
		$sch = $this->scheme();
		foreach ($attrs as $key=>$value) {
			if (@$sch[$key] && @$sch[$key]["updatable"])
				$this->$key = $value;
		}
		return !$save || $this->save(); 
	}
	
	protected abstract function updateModel();
	
	protected function beforeDelete() {
	}

	protected function afterDelete() {
	}
	
	public function delete() {
		if (!$this->saved || $this->deleted)
			return FALSE;
		$this->beforeDelete();
		$success = $this->deleteModel();
		if ($success) {
			static::log(Logger::INFO_2, "Deleted model '" . get_called_class() . "' with id {$this->id()}.");
			$this->deleted = TRUE;
			foreach ($this->assocs() as $assoc)
				$assoc->deleteModel();
			$this->afterDelete();
		}
		else
			static::log(Logger::WARN, "Failed to delete model '{" . get_called_class() . "' with id {$this->id()}.");
		return $success;
	}
		
	protected abstract function deleteModel();
	
	public static function findById($id) {
		return self::materializeObject(static::findRowById($id));
	}
	
	protected static function findRowById($id) {
		return NULL;
	}
	
	public static function findBy($query) {
		return self::materializeObject(static::findRowBy($query));
	}
	
	protected static function findRowBy($query) {
		return NULL;
	}
	
	public function reload() {
		$attrs = static::findRowById($this->id());
		foreach ($attrs as $key=>$value)
			$this->setAttr($key, $value);
		$this->saved = TRUE;
		$this->newModel = FALSE;
		$this->resetChanged();
	}
	
	protected static function materializeClass($attrs) {
		return get_called_class();
	} 

	public static function materializeObject($attrs) {
		if (@$attrs) {
			$class = static::materializeClass($attrs);
			$obj = new $class();
			foreach ($attrs as $key=>$value) 
				$obj->setAttr($key, $value);
			$obj->saved = TRUE;
			$obj->newModel = FALSE;
			return $obj;
		}
		return FALSE;
	}
	
	public static function all($sort = NULL, $limit = NULL, $skip = NULL, $iterator = FALSE) {
		$options = array();
		if (@$sort)
			$options["sort"] = $sort;
		if (@$limit)
			$options["limit"] = $limit;
		if (@$skip)
			$options["skip"] = $skip;
		$result = static::allRows($options);
		$cls = get_called_class();
		if (is_array($result)) {
			$result = new ArrayObject($result);
			$result = $result->getIterator();
		}
		$iter = new MappedIterator($result, function($row) use ($cls) {
			return $cls::materializeObject($row);
		}); 
		return $iterator ? $iter : iterator_to_array($iter, FALSE);
	}
	
	protected static function allRows($options = NULL) {
		return NULL;
	}

	public static function allBy($query, $sort = NULL, $limit = NULL, $skip = NULL, $iterator = FALSE) {
		$options = array();
		if (@$sort)
			$options["sort"] = $sort;
		if (@$limit)
			$options["limit"] = $limit;
		if (@$skip)
			$options["skip"] = $skip;
		$result = static::allRowsBy($query, $options);
		if (is_array($result)) {
			$result = new ArrayObject($result);
			$result = $result->getIterator();
		}
		$cls = get_called_class();
		$iter = new MappedIterator($result, function($row) use ($cls) {
			return $cls::materializeObject($row);
		}); 
		return $iterator ? $iter : iterator_to_array($iter, FALSE);
	}

	protected static function allRowsBy($query, $options = NULL) {
		return NULL;
	}
	
	public static function count($query) {
		return count(self::all($query));
	}
	
	public static function materializeObjects($cursor) {
        $objects = array();
        foreach ($cursor as $object)
            $objects[] = self::materializeObject($object);
        return $objects;
	}
	
	public function asRecord($tags = array("read"), $options = array()) {
		$result = array();
		$sch = $this->scheme();
		foreach ($sch as $key=>$meta) {
			$key_tags = @$meta["tags"] ? $meta["tags"] : array();
			if (ArrayUtils::subset($tags, $key_tags)) {
				if ($key == static::idKey())
					$result["id"] = $this->$key;
				else
					$result[$key] = $this->$key;
			}
		}
		return $result;
	}
	
	public function validate() {
		parent::validate();
		$sch = $this->scheme();
		foreach ($sch as $key=>$meta) {
			if (isset($meta["unique"])) {
				$conf = $meta["unique"];
				$value = @$this->attrs[$key];
				if ((!@$conf["ignore_if_null"] || isset($value)) &&
				    (isset($this->attrsChanged[$key]) || !$this->isSaved())) {
					$query = array();
					if (@$conf["query"])
						foreach($conf["query"] as $item)
							$query[$item] = $this->attrs[$item];
					while (TRUE) {
						$query[$key] = $value;
						$object = self::findBy($query);
						if (@$object && $object->id() != $this->id()) {
							if (@$conf["iterate_default"]) {
								$value = $meta["default"]();
								$this->$key = $value;
								continue;
							}								
							$this->errors[$key] = @$conf["error_message"] ? $conf["error_message"] : "Key " . $key . " not unique";
						}
						break;
					}
				}
			}
		}
	}

	public function isInvalidated() {
		return FALSE;
	}
	
	public static function invalidateAll($simulate = FALSE) {
		self::log(Logger::INFO, get_called_class() . ": Removing invalid models...");
		foreach (self::all(NULL, NULL, NULL, TRUE) as $instance)
			if ($instance->isInvalidated()) {
				self::log(Logger::INFO_2, get_called_class() . ": Remove Instance {$instance->id()}.");
				if (!$simulate)
					$instance->delete();
			}		
	} 

}
