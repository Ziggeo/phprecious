<?php

require_once(dirname(__FILE__) . "/ModelAssociation.php");

class ModelHasManyViaAssociation extends ModelAssociation {
	
	private $foreignKey;
	private $foreignClass;
	
	public function __construct($parentModel, $intermediateKey, $intermediateClass, $foreignKey, $foreignClass, $options = array()) {
		parent::__construct($parentModel, $options);
		$this->intermediateKey = $intermediateKey;
		$this->intermediateClass = $intermediateClass;
		$this->foreignKey = $foreignKey;
		$this->foreignClass = $foreignClass;
	}
	
	private function prepare_query($query = NULL) {
		$class = $this->intermediateClass;
		$model = $this->getParentModel();
		$key = $this->intermediateKey;
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
	
	private function map_single($result) {
		$class = $this->foreignClass;
		$key = $this->foreignKey;
		return $class::findById($result->$key);
	}
	
	private function map_result($result) {
		$self = $this;
		return array_map(function ($intermediate) use ($self) {
			return $self->map_single($intermediate);
		}, $result);
	}
	
	public function select($query = NULL, $sort = NULL, $limit = NULL, $skip = NULL) {
		$class = $this->intermediateClass;
		if (!@$sort)
			$sort = @$this->getOption("sort");
 		return $this->map_result($class::allBy($this->prepare_query($query), $sort, $limit, $skip));
	}
	
	public function count($query = NULL) {
		$class = $this->intermediateClass;
 		return $class::count($this->prepare_query($query));
	}
	
	public function all($sort = NULL) {
		return $this->select(NULL, $sort);
	}
	
	public function findOne($query) {
		$class = $this->foreignClass;
 		return $this->map_single($class::findBy($this->prepare_query($query)));
	}
	
	protected function delegateSelect() {
		return $this->select(NULL, @$this->getOption("sort"));
	}
	
	public function contains($obj) {
		if (!is_object($obj))
			$obj = $class::findById($obj);
		if (!@$obj)
			return FALSE;
		$class = $this->foreignClass;
		$key = $this->foreignKey;
		$intermediate_class = $this->intermediateClass;
		$intermediate_key = $this->intermediateKey;
		$query = array();
		$query[$foreign_key] = $obj->id();
		$int = $intermediate_class::findBy($query);
		if (!@$int)
			return FALSE;
		$model = $this->getParentModel();
		return ($int->$intermediate_key == $model->id());
	}
	
}
