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
		return $class::findBy($params);
	}
	
	public function deleteModel() {
		if (@$this->getOption("delete_cascade")) {
		 	$obj = $this->delegate();
			if (@$obj)
				$obj->delete();
		}
	}

}
