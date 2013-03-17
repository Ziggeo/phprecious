<?php

require_once(dirname(__FILE__) . "/ModelAssociation.php");

class ModelHasOneAssociation extends ModelAssociation {
	
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
		if (func_num_args() == 1) {
			$obj = func_get_arg(0);
			if (@$obj) {
				if (!$obj->isSaved())
					throw new ModelAssociationException("Cannot associate non-existing object!");
				$model->$key = $obj->id();
			}
			else
				$model->$key = NULL;
			$this->invalidateCache();
		}
		else {
			$def = $this->getOption("default");
			if (@$model->$key) {
				$row = $class::findById($model->$key);
				if (!@$row && isset($def))
					return $class::findById($this->getOption("default"));
				return $row;
			}
			if (isset($def))
				return $class::findById($this->getOption("default"));
			return NULL;
		}
	}
	
	public function deleteModel() {
		if (@$this->getOption("delete_cascade")) {
		 	$obj = $this->delegate();
			if (@$obj)
				$obj->delete();
		}
	}

}
