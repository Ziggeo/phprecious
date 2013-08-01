<?php

Class MappedIterator implements Iterator {
	
	private $iterator;
	private $map;
	
	function __construct($iterator, $map) {
		$this->iterator = $iterator;
		$this->map = $map;
	}
	
	public function current() {
		$f = $this->map;
		return $f($this->iterator->current());	
	}
	
	public function key() {
		return $this->iterator->key();
	}
	
	public function next() {
		$this->iterator->next();
	}
	
	public function rewind() {
		$this->iterator->rewind();
	}
	
	public function valid() {
		return $this->iterator->valid();
	}

}
