<?php

require_once(dirname(__FILE__) . "/ModelAssociation.php");


// Beware: Uses $in - query
class ModelHasManyThroughArrayAssociation extends ModelAssociation {
	
	private $foreignKeys;
	private $foreignClass;
	
	public function __construct($parentModel, $foreignKeys, $foreignClass, $options = array()) {
		parent::__construct($parentModel, $options);
		$this->foreignKeys = $foreignKeys;
		$this->foreignClass = $foreignClass;
	}
	
	public function all($sort = NULL) {
		return $this->select(NULL, $sort);
	}
	
	protected function delegateSelect() {
		return $this->select(NULL, @$this->getOption("sort"));
	}
	
	public function deleteModel() {
		if (@$this->getOption("delete_cascade")) 
			foreach ($this->delegate() as $obj)
				$obj->delete();
	}

	public function select($query = NULL, $sort = NULL, $limit = NULL) {
		$class = $this->foreignClass;
		$model = $this->getParentModel();
		$attrs = $this->foreignKeys;
		if (@!$query)
			$query = array();
		if(@$this->getOption("where"))
			$query = array_merge($query, @$this->getOption("where"));
		$query[$class::idKey()] = array('$in' => $model->$attrs);
		if (!@$sort)
			$sort = @$this->getOption("sort");
 		return $class::allBy($query, $sort, $limit);
	}
	
	public function add($item, $update = FALSE) {
		$class = $this->foreignClass;
		$model = $this->getParentModel();
		$attrs = $this->foreignKeys;
		if (!in_array($item->id(), $model->$attrs)) {
			$model->$attrs = array_merge($model->$attrs, array($item->id()));
			if ($update)
				$model->update();
		}
	}
	
	public function remove($item, $update = FALSE) {
		$class = $this->foreignClass;
		$model = $this->getParentModel();
		$attrs = $this->foreignKeys;
		if (in_array($item->id(), $model->$attrs)) {
			$model->$attrs = array_filter($model->$attrs, function ($it) use ($item) {
				return $it != $item->id();
			});
			if ($update)
				$model->update();
		}
	}

}