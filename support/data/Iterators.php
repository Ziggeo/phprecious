<?php

Class MappedIterator implements Iterator {
	
	private $iterator;
	private $map;
	
	function __construct($iterator, $map) {
		$this->iterator = $iterator;
		$this->map = $map;
	}
	
	#[\ReturnTypeWillChange]
	public function current() {
		$f = $this->map;
		return $f($this->iterator->current());	
	}
	
	#[\ReturnTypeWillChange]
	public function key() {
		return $this->iterator->key();
	}
	
	#[\ReturnTypeWillChange]
	public function next() {
		$this->iterator->next();
	}
	
	#[\ReturnTypeWillChange]
	public function rewind() {
		$this->iterator->rewind();
	}
	
	#[\ReturnTypeWillChange]
	public function valid() {
		return $this->iterator->valid();
	}

}
