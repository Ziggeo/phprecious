<?php

require_once(dirname(__FILE__) . "/ModelAssociation.php");

class ModelHasOnePolymorphicAssociation extends ModelAssociation {
	
	private $foreignKey;
	
	public function __construct($parentModel, $foreignKey, $options = array()) {
		parent::__construct($parentModel, $options);
		$this->foreignKey = $foreignKey;
	}
	
	protected function delegateSelect() {
		$model = $this->getParentModel();
		$key = $this->foreignKey;
		$classkey = $key . "_type";
		$class = @$model->$classkey;
		
		if (func_num_args() == 1) {
			$obj = func_get_arg(0);
			if (@$obj) {
				if (!$obj->isSaved())
					throw new ModelAssociationException("Cannot associate non-existing object!");
				$model->$key = $obj->id();
				$model->$classkey = get_class($obj);
			}
			else {
				$model->$key = NULL;
				$model->$classkey = NULL;
			}
			$this->invalidateCache();
		}
		else {
			if (@$model->$key)
				return $class::findById($model->$key);
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
