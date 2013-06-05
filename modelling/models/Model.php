<?php

/*
 * Scheme Properties
 *   "default": default value (default: undefined)
 *   "readonly": (default: false)
 *   "validate": array of validators (default: NULL)
 *   "persistent": belongs to database (default: TRUE)
 */



class Model {
	
	/*
	 * Scheme
	 */
	
	private static $scheme = array();
	
	static public function classScheme() {
		$class = get_called_class();
		if (!@self::$scheme[$class])
			self::$scheme[$class] = static::initializeScheme();
		return self::$scheme[$class];
	}
	
	public function scheme() {
		return static::classScheme();
	}
	
	public function schemeOf($key) {
		$sch = $this->scheme();
		return @$sch[$key];
	}
	
	public function schemeProp($key, $prop, $default = NULL) {
		$sch = $this->schemeOf($key);
		if (@$sch && isset($sch[$prop]))
			return $sch[$prop];
		return $default;
	}
	
	protected static function initializeScheme() {
		return array();
	}
	
	
	/*
	 * Options
	 */
	
	private static $options = array();
	
	static public function classOptions() {
		$class = get_called_class();
		if (!@self::$options[$class])
			self::$options[$class] = static::initializeOptions();
		return self::$options[$class];
	}
	
	public function options() {
		return static::classOptions();
	}
	
	public function optionsOf($key) {
		$opt = $this->options();
		return @$opt[$key];
	}

	protected static function initializeOptions() {
		return array();
	}
		
	


	/*
	 * Indices
	 */
	
	private static $indices = array();
	
	static public function classIndices() {
		$class = get_called_class();
		if (!@self::$indices[$class])
			self::$indices[$class] = static::initializeIndices();
		return self::$indices[$class];
	}

	public function indices() {
		return static::classIndices();
	}

	protected static function initializeIndices() {
		$arr = array();
		foreach (self::classScheme() as $key=>$sch) {
			if (@$sch["index"])
				$arr[] = array($key); 
		}
		return $arr;
	}
	
	
	
	/*
	 * Attributes
	 */ 

	private $attrs = array();
	private $attrsChanged = array();
	
	protected function getAttr($key) {
		return @$this->attrs[$key];
	}

	protected function setAttr($key, $value, $setChanged = FALSE) {
		if ($setChanged && @(!isset($this->attrs[$key]) || $this->attrs[$key] != $value))
			$this->attrsChanged[$key] = $value;
		$this->attrs[$key] = $value;
	}
	
	public function __get($key) {
		return $this->getAttr($key);
	}

	public function __set($key, $value) {
		if (!$this->schemeProp($key, "readonly", FALSE))
			$this->setAttr($key, $value, TRUE);
	}
	
	public function inc($key, $value = 1) {
		if (!$this->schemeProp($key, "readonly", FALSE))
			$this->incAttr($key, $value);
	}
	
	public function dec($key, $value = 1) {
		$this->inc($key, -$value);
	}

	protected function incAttr($key, $value) {
		$this->$key = $this->$key + $value;
	}
	
	protected function resetChanged() {
		$this->attrsChanged = array();
	}
	
	public function hasChanged() {
		return count($this->attrsChanged) > 0;
	}
	
	public function attrsChanged() {
		return $this->attrsChanged;
	}
	
	public function attrs($filter = NULL) {
		if ($filter == NULL)
			return $this->attrs;
		$result = array();
		foreach ($filter as $key)
			$result[$key] = @$this->attrs[$key];
		return $result;		
	}
	
	public function filterPersistentAttrs($attrs) {
		$sch = static::scheme();
		$result = array();
		foreach ($attrs as $key => $value)
			if ($this->schemeProp($key, "persistent", TRUE))
				$result[$key] = $value;
		return $result;
	}
	
	public function htmlentities($key) {
		return htmlentities($this->$key, ENT_COMPAT, "UTF-8");
	}
	
	public function htmlentitiesq($key) {
		return htmlentities($this->$key, ENT_QUOTES, "UTF-8");
	}
	
	
	/*
	 * Errors & Validations
	 */
	 
	private $errors = array();	
	
	public function errors() {
		return $this->errors;
	}
	
	protected function beforeValidate() {
	}
	
	protected function customValidate($errors) {
		return $errors;
	}

	public function validate() {
		$this->beforeValidate();
		$this->errors = array();
		$sch = $this->scheme();
		foreach ($sch as $key=>$meta) {
			if (isset($meta["validate"])) {
				$value = @$this->attrs[$key];
				$validators = $meta["validate"];
				if (!is_array($validators))
					$validators = array($validators);
				foreach ($validators as $validator) {
					$result = $validator->validate($value, $this);
					if ($result != NULL && is_string($result)) {
						$this->errors[$key] = $result;
						break;
					}
				}
			}
		}
		$this->errors = $this->customValidate($this->errors);
	}
	
	public function isValid() {
		$this->validate();
		return (count($this->errors) == 0);
	}
	
	
	
	/*
	 * Associations
	 */

	protected $assocs = array();
	
	public function assocs() {
		return $this->assocs;
	}
	
	protected function initializeAssocs() {
	} 
		
	public function __call($key, $args) {
		$obj = @$this->assocs[$key];
		if (@$obj)
			return call_user_method_array("delegate", $obj, $args);
		if (substr($key, -3) == "obj") {
			$sub = substr($key, 0, -3);
			$obj = $this->assocs[$sub];
			return $obj;
		}
		return FALSE;
	}

		
		
	/*
	 * Constructor
	 */
	 
	public function __construct($attributes = array()) {
		$sch = $this->scheme();
		// Construct scheme
		foreach ($sch as $key=>$meta)
			if(isset($meta["default"])) {
				$f = $meta["default"];
				if (is_object($f) && ($f instanceof Closure))
					$this->attrs[$key] = $f($this);
				else
					$this->attrs[$key] = $f;
			}
		// Construct attributes
		foreach ($attributes as $key=>$value) {
			if (isset($sch[$key]))
				$this->attrs[$key] = $value;
		}
		// Construct associations
		$this->initializeAssocs();
		// Finished
		$this->afterConstruction();
	}
	
	protected function afterConstruction() {
	}

	
}
