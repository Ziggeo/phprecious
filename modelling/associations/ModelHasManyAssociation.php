<?php

require_once(dirname(__FILE__) . "/ModelAssociation.php");

class ModelHasManyAssociation extends ModelAssociation {
	
	private $foreignKey;
	private $foreignClass;
	
	public function __construct($parentModel, $foreignKey, $foreignClass, $options = array()) {
		parent::__construct($parentModel, $options);
		$this->foreignKey = $foreignKey;
		$this->foreignClass = $foreignClass;
	}
	
	public function select($query = NULL, $sort = NULL, $limit = NULL) {
		$class = $this->foreignClass;
		$model = $this->getParentModel();
		$key = $this->foreignKey;
		if (!@$sort)
			$sort = @$this->getOption("sort");
		if (@!$query)
			$query = array();
		if(@$this->getOption("where"))
			$query = array_merge($query, @$this->getOption("where"));
		$query[$key] = $model->id();
		if (@$this->getOption("polymorphic"))
			$params[$key . "_type"] = get_class($model);
		if (@$this->getOption("role"))
			$params[$key . "_role"] = $this->getOption("role");
 		return $class::allBy($query, $sort, $limit);
	}
	
	public function all($sort = NULL) {
		return $this->select(NULL, $sort);
	}
	
	protected function delegateSelect() {
		return $this->select(NULL, @$this->getOption("sort"));
	}
	
	public function contains($obj) {
		$class = $this->foreignClass;
		$model = $this->getParentModel();
		$key = $this->foreignKey;
		if (!is_object($obj))
			$obj = $class::findById($obj);
		return ($obj->$key == $model->id());
	}
	
	public function deleteModel() {
		if (@$this->getOption("delete_cascade")) 
			foreach ($this->delegate() as $obj)
				$obj->delete();
	}

}
