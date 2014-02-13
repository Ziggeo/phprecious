<?php

require_once(dirname(__FILE__) . "/ModelAssociation.php");

class ModelBelongsToAssociation extends ModelAssociation {
	
	private $foreignKey;
	private $foreignClass;
	
	public function __construct($parentModel, $foreignKey, $foreignClass, $options = array()) {
		parent::__construct($parentModel, $options);
		$this->foreignKey = $foreignKey;
		$this->foreignClass = $foreignClass;
	}
	
	protected function delegateSelect() {
		$class = $this->foreignClass;
		$model = $this->getParentModel();
		$key = $this->foreignKey;
		$params = array($key => $model->id());
		if (@$this->getOption("polymorphic"))
			$params[$key . "_type"] = get_class($model);
		if (@$this->getOption("role"))
			$params[$key . "_role"] = $this->getOption("role");
		$result = $class::findBy($params);
		if ($result == NULL)
			$result = $this->autoCreate(FALSE);
		return $result;
	}
	
	public function deleteModel() {
		if (@$this->getOption("delete_cascade")) {
		 	$obj = $this->delegate();
			if (@$obj)
				$obj->delete();
		}
	}
	
	public function createModel() {
		$this->autoCreate(TRUE);
	}
	
	private function autoCreate($on_create = TRUE) {
		$opt = $this->getOption("auto_create");
		if (@$opt && (($on_create && (!isset($opt["on_create"]) || $opt["on_create"])) || (!$on_create && isset($opt["on_demand"]) && $opt["on_demand"]))) {
			$model = $this->getParentModel();
			if ($model->isDeleting() || $model->isDeleted())
				return NULL;
			if (isset($opts["custom"]))
				return $opts["custom"]($model);
			$class = $this->foreignClass;
			$key = $this->foreignKey;
			$params = array($key => $model->id());
			if (@$this->getOption("polymorphic"))
				$params[$key . "_type"] = get_class($model);
			if (@$this->getOption("role"))
				$params[$key . "_role"] = $this->getOption("role");
			if (isset($opts["params"]))
				$params = array_merge($params, is_callable($opts["params"]) ? $opts["params"]($model) : $opts["params"]);
			$result = new $class($params);
			$result->save();
			return $result;
		}
	}

}