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
	
	public function size() {
		return filesize($this->file_name);
	}
	
	public function exists() {
		return file_exists($this->file_name);
	}
	
	public function delete() {
		if (!unlink($this->file_name))
			throw new FileSystemException("Could not delete file");
	}
	
	protected function readStream() {
		return fopen($this->file_name, "r");
	}
	
	protected function writeStream() {
		return fopen($this->file_name, "w");
	}
	
	public function toLocalFile($file) {
		copy($this->file_name, $file);
	}
	
	public function fromLocalFile($file) {
		copy($file, $this->file_name);
	}	
	
	public function materialize() {
		return new FileMaterializedFile($this);
	}

}
