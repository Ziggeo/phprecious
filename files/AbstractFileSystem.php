<?php

Class AbstractFileSystem {
	
	protected function getClass() {
		return AbstractFile;
	}
	
	public function getFile($file_name) {
		$cls = $this->getClass();
		return is_string($file_name) ? new $cls($this, $file_name) : $file_name;
	}
		
}


Class FileSystemException extends Exception {}


Class AbstractFile {
	
	protected $file_system = null;
	protected $file_name = null;
	protected $opened = false;
	
	function __construct($file_system, $file_name) {
		$this->file_system = $file_system;
		$this->file_name = $file_name;
		$this->opened = false;
	}
	
	public function open($options = array()) {
		if ($this->opened)
			return;
		$this->_open($options);
		$this->opened = TRUE;
	}
	
	public function close() {
		if (!$this->opened)
			return;
		$this->_close();
		$this->opened = FALSE;
	}

	public function write($string) {
		if (!$this->opened)
			return -1;
		return $this->_write($string);
	}
	
	protected function _open($options) {
		throw new FileSystemException("Unsupported Operation");
	}
	
	public function size() {
		throw new FileSystemException("Unsupported Operation");
	}
	
	public function exists() {
		throw new FileSystemException("Unsupported Operation");
	}

	public function isDir() {
		throw new FileSystemException("Unsupported Operation");
	}

	public function delete() {
		throw new FileSystemException("Unsupported Operation");
	}
	
	protected function _close() {
		throw new FileSystemException("Unsupported Operation");
	}
	
	protected function _write($string) {
		throw new FileSystemException("Unsupported Operation");
	} 

}
