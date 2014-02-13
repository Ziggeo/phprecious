<?php

abstract class ModelAssociation {
	
	private $parentModel;
	private $options;
	private $cache = NULL;
	private $cached = FALSE;
	
	public function getParentModel() {
		return $this->parentModel;
	}
	
	public function getOption($key) {
		return isset($this->options[$key]) ? $this->options[$key] : NULL;
	}
	
	public function __construct($parentModel, $options = array()) {
		$this->parentModel = $parentModel;
		$this->options = $options;
	}
	
	public function delegate() {
		if (@$this->options["cached"] && (func_num_args() == 0)) {
			if (!$this->cached) {
				$this->cache = $this->delegateSelect();
				$this->cached = TRUE;
			}
			return $this->cache;
		}
		else
			return call_user_func_array(array($this, "delegateSelect"), func_get_args());
	}
	
	public function invalidateCache() {
		$this->cached = FALSE;
		$this->cache = NULL;
	}
	
	protected abstract function delegateSelect();
	
	public function createModel() {
	}
	
	public function deleteModel() {
	}
	
	public function validate() {
		if (isset($this->options["validate"])) {
			$value = $this->delegate();
			$validators = $this->options["validate"];
			if (!is_array($validators))
				$validators = array($validators);
			foreach ($validators as $validator) {
				$result = $validator->validate($value, $this->parentModel);
				if ($result != NULL && is_string($result))
					return $result;
			}
		}
	}

}

class ModelAssociationException extends Exception {}
