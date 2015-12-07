<?php

require_once(dirname(__FILE__) . "/../../logging/Logger.php");
require_once(dirname(__FILE__) . "/Model.php");

abstract class ActiveModel extends Model {
	
	protected static function log($level, $s) {
		global $LOGGER;
		if (@$LOGGER)
			$LOGGER->message(get_called_class(), $level, $s);
	}
	
	public static function idKey() {
		return "id";
	} 
	
	public function id() {
		return $this->getAttr(static::idKey());
	}
	
	protected static function initializeOptions() {
		$arr = parent::initializeOptions();
		$arr["remove_field"] = NULL;
		return $arr;
	}

	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs[static::idKey()] = array(
			"readonly" => TRUE,
			"type" => "id"
		);
		if (static::classOptionsOf("remove_field") != NULL)
			$attrs[static::classOptionsOf("remove_field")] = array(
				"type" => "boolean",
				"default" => FALSE
			);
		return $attrs;
	} 

	private $saved = FALSE;
	private $newModel = TRUE;
	private $deleted = FALSE;
	private $deleting = FALSE;
	
	public function isSaved() {
		return $this->saved;
	}
	
	public function isDeleting() {
		return $this->deleting;
	}
	
	public function isDeleted() {
		return $this->deleted;
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
		if ($this->saved) {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not create model: already created");
			return FALSE;
		}
		if ($this->deleted || $this->deleting) {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not create model: deleted");
			return FALSE;
		}
		if (!$this->isValid()) {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not create model: invalid");
			return FALSE;			
		}
		$this->beforeCreate();
		$id = $this->createModel();
		if (isset($id)) {
			$this->saved = TRUE;
			$this->setAttr(static::idKey(), $id);
			$this->resetChanged();
			static::log(Logger::INFO_2, "Created model '" . get_called_class() . "' with id {$this->id()}.");
	        foreach ($this->assocs() as $assoc)
	            $assoc->createModel();
			$this->afterCreate();
			return TRUE;
		} else {
			static::log(Logger::WARN, "Failed to create model '" . get_called_class() . "'.");
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Model creation failed: No id returned");
			return FALSE;
		}
	}
	
	protected abstract function createModel();

	public function update($attrs = array(), $force = FALSE) {
		if (!$this->saved) {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not update model: not created");
			return FALSE;
		}
		if ($this->deleted || $this->deleting) {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not update model: deleted");
			return FALSE;
		}
		foreach ($attrs as $key => $value)
			$this->$key = $value;
		if (!$force && !$this->isValid())  {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not update model: invalid");
			return FALSE;			
		}
		$this->beforeUpdate();
		$success = !$this->hasChanged() || $this->updateModel();
		if ($success) {
			static::log(Logger::INFO_2, "Updated model '" . get_called_class() . "' with id {$this->id()}.");
			$this->saved = TRUE;
			$this->newModel = FALSE;
			$this->resetChanged();
			$this->afterUpdate();
		} else {
			static::log(Logger::WARN, "Failed to update model '" . get_called_class() . "' with id {$this->id()}.");
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Model update failed");
		}
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
	
	public function delete($ignore_remove_field = FALSE) {
		if (!$ignore_remove_field && $this->optionsOf("remove_field") != NULL) {
			$arr = array();
			$arr[$this->optionsOf("remove_field")] = TRUE;
			return $this->update($arr, TRUE);
		}			
		if (!$this->saved) {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not delete model: not created");
			return FALSE;
		}
		if ($this->deleted || $this->deleting) {
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Could not delete model: already deleted");
			return FALSE;
		}
		$this->deleting = TRUE;
		$this->beforeDelete();
        foreach ($this->assocs() as $assoc)
            $assoc->deleteModel();
		$success = $this->deleteModel();
		if ($success) {
			static::log(Logger::INFO_2, "Deleted model '" . get_called_class() . "' with id {$this->id()}.");
			$this->deleted = TRUE;
			$this->afterDelete();
		} else {
			static::log(Logger::WARN, "Failed to delete model '{" . get_called_class() . "' with id {$this->id()}.");
			if ($this->optionsOf("exceptions"))
				throw new ModelException("Model deletion failed");
		}
		return $success;
	}
		
	protected abstract function deleteModel();
	
	public static function findById($id, $ignore_remove_field = FALSE) {
		$rf = static::classOptionsOf("remove_field");
		if (isset($rf) && !$ignore_remove_field) {
			$query = array();
			$query[static::idKey()] = $id;
			$query[$rf] = FALSE;
			return self::materializeObject(static::findRowBy($query));
		}		
		return self::materializeObject(static::findRowById($id));
	}
	
	protected static function findRowById($id) {
		return NULL;
	}
	
	public static function findBy($query) {
		$rf = static::classOptionsOf("remove_field");
		if ($rf != NULL && !isset($query[$rf]))
			$query[$rf] = FALSE;
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
	
	public static function all($sort = NULL, $limit = NULL, $skip = NULL, $iterator = FALSE, $ignore_remove_field = FALSE) {
		$rf = static::classOptionsOf("remove_field");
		if ($rf != NULL && !$ignore_remove_field) {
			$query = array();
			$query[$rf] = FALSE;
			return self::allBy($query, $sort, $limit, $skip, $iterator);
		}
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

	public static function allBy($query, $sort = NULL, $limit = NULL, $skip = NULL, $iterator = FALSE, $ignore_remove_field = FALSE) {
		$options = array();
		if (@$sort)
			$options["sort"] = $sort;
		if (@$limit)
			$options["limit"] = $limit;
		if (@$skip)
			$options["skip"] = $skip;
		$rf = static::classOptionsOf("remove_field");
		if ($rf != NULL && !isset($query[$rf]) && !$ignore_remove_field)
			$query[$rf] = FALSE;
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
	
	protected static function countModels($query = array()) {
		return count(self::allBy($query));
	}
	
	public static function count($query = array(), $ignore_remove_field = FALSE) {
		$rf = static::classOptionsOf("remove_field");
		if ($rf != NULL && !isset($query[$rf]) && !$ignore_remove_field)
			$query[$rf] = FALSE;
		return static::countModels($query);
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
			$key_tags = isset($meta["tags"]) ? $meta["tags"] : array();
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
				if ((!isset($conf["ignore_if_null"]) || !$conf["ignore_if_null"] || (isset($value) && @$value)) &&
				    (isset($this->attrsChanged[$key]) || !$this->isSaved())) {
					$query = array();
					if (isset($conf["query"]))
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

	public function isInvalidated($ignore_remove_field = FALSE) {
		return FALSE;
	}
	
	public static function invalidateAll($simulate = FALSE, $ignore_remove_field = FALSE, $ignore_iterator_exceptions = TRUE) {
		self::log(Logger::INFO, get_called_class() . ": Removing invalid models...");
		try {
			$it = self::all(NULL, NULL, NULL, TRUE, $ignore_remove_field);
			$it->rewind();
		} catch (Exception $e) {
			if (!$ignore_iterator_exceptions)
				throw $e;
			return;
		}
		while (TRUE) {
			$instance = NULL;
			try {
				if (!$it->valid())
					return;
				$instance = $it->current();
			} catch (Exception $e) {
				if (!$ignore_iterator_exceptions)
					throw $e;
				return;
			}
			if (!@$instance)
				return;
			if ($instance->isInvalidated($ignore_remove_field)) {
				self::log(Logger::INFO_2, get_called_class() . ": Remove Instance {$instance->id()}.");
				if (!$simulate)
					$instance->delete($ignore_remove_field);
			}		
			try {
				$it->next();
			} catch (Exception $e) {
				if (!$ignore_iterator_exceptions)
					throw $e;
				return;
			}
		}
	} 

}
