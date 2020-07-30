<?php

Class FileSystemException extends Exception {}

Class FileSystemFieldException extends FileSystemException {
	private $data;

	function __construct($data = array()) {
		$this->data = $data;
	}

	function getData() {
		return $this->data;
	}
}