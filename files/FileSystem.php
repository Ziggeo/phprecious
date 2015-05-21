<?php

require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class FileSystem extends AbstractFileSystem {
	
	protected function getClass() {
		return File;
	}
	
	private static $fs_singleton = NULL;
	
	static function singleton() {
		if (self::$fs_singleton == NULL)
			self::$fs_singleton = new FileSystem();
		return self::$fs_singleton;
	}
	
}

Class File extends AbstractFile {
	
	protected $file_handle = NULL;
	
	protected function _open($options) {
		$mode = isset($options["mode"]) ? $options["mode"] : "a";
		$this->file_handle = fopen($this->file_name, $mode);
		if (!$this->file_handle)
			throw new FileSystemException("Could not open file");
	}
	
	public function size() {
		return filesize($this->file_name);
	}
	
	public function exists() {
		return file_exists($this->file_name);
	}
	
	public function isDir() {
		throw new FileSystemException("Unsupported Operation");
	}
	
	public function delete() {
		if (!unlink($this->file_name))
			throw new FileSystemException("Could not delete file");
	}
	
	protected function _close() {
		if (!fclose($this->file_handle))
			throw new FileSystemException("Could not close file");
		unset($this->file_handle);		
	}
	
	protected function _write($string) {
		return fwrite($this->file_handle, $string);
	}
	
}
