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
	
	private function prepare_query($query = NULL) {
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
			$query[$key . "_type"] = get_class($model);
		if (@$this->getOption("role"))
			$query[$key . "_role"] = $this->getOption("role");
		return $query;
	}
	
	public function select($query = NULL, $sort = NULL, $limit = NULL) {
		$class = $this->foreignClass;
		if (!@$sort)
			$sort = @$this->getOption("sort");
 		return $class::allBy($this->prepare_query($query), $sort, $limit);
	}
	
	public function all($sort = NULL) {
		return $this->select(NULL, $sort);
	}
	
	public function findOne($query) {
		$class = $this->foreignClass;
 		return $class::findBy($this->prepare_query($query));
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
