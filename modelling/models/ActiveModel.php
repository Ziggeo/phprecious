<?php

require_once(dirname(__FILE__) . "/../../logging/Logger.php");
require_once(dirname(__FILE__) . "/Model.php");

abstract class ActiveModel extends Model {
	
	protected static function log($level, $s) {
		global $LOGGER;
		if (@$LOGGER)
			$LOGGER->message("framework.model", $level, $s);
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
			"readonly" => TRUE
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
			static::log("Created model '" . get_called_class() . "' with id {$this->id()}.", Logger::INFO_2);
			$this->afterCreate();
			return TRUE;
		}
		else
			static::log("Failed to create model '" . get_called_class() . "'.", Logger::WARN);
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
			static::log("Updated model '" . get_called_class() . "' with id {$this->id()}.", Logger::INFO_2);
			$this->saved = TRUE;
			$this->newModel = FALSE;
			$this->resetChanged();
			$this->afterUpdate();
		}
		else
			static::log("Failed to update model '" . get_called_class() . "' with id {$this->id()}.", Logger::WARN);
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
	
	protected function afterDelete() {
	}
	
	public function delete() {
		if (!$this->saved || $this->deleted)
			return FALSE;
		$success = $this->deleteModel();
		if ($success) {
			static::log("Deleted model '" . get_called_class() . "' with id {$this->id()}.", Logger::INFO_2);
			$this->deleted = TRUE;
			foreach ($this->assocs() as $assoc)
				$assoc->deleteModel();
			$this->afterDelete();
		}
		else
			static::log("Failed to delete model '{" . get_called_class() . "' with id {$this->id()}.", Logger::WARN);
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

	public static function materializeObject($attrs) {
		if (@$attrs) {
			$class = get_called_class();
			$obj = new $class();
			foreach ($attrs as $key=>$value) 
				$obj->setAttr($key, $value);
			$obj->saved = TRUE;
			$obj->newModel = FALSE;
			return $obj;
		}
		return FALSE;
	}
	
	public static function all($sort = NULL, $limit = NULL, $skip = NULL) {
		$options = array();
		if (@$sort)
			$options["sort"] = $sort;
		if (@$limit)
			$options["limit"] = $limit;
		if (@$skip)
			$options["skip"] = $skip;
		$result = static::allRows($options);
		return self::materializeObjects($result);
	}
	
	protected static function allRows($options = NULL) {
		return NULL;
	}

	public static function allBy($query, $sort = NULL, $limit = NULL, $skip = NULL) {
		$options = array();
		if (@$sort)
			$options["sort"] = $sort;
		if (@$limit)
			$options["limit"] = $limit;
		if (@$skip)
			$options["skip"] = $skip;
		$result = static::allRowsBy($query, $options);
		return self::materializeObjects($result);
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
	
}
