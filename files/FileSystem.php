<?php

require_once(dirname(__FILE__) . "/FileSystemException.php");
require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class FileSystem extends AbstractFileSystem {
	
	protected function getClass() {
		return "File";
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
	
	public function readStream() {
		if (@$this->read_handle)
			return $this->read_handle;
		$handle = fopen($this->file_name, "r");
		if ($handle === FALSE)
			throw new FileSystemException("Could not open file");
		$this->read_handle = $handle;
		return $handle;
	}
	
	public function writeStream() {
		$handle = fopen($this->file_name, "w");
		if ($handle === FALSE)
			throw new FileSystemException("Could not open file");
		return $handle;
	}
	
	public function toLocalFile($file) {
		if (!copy($this->file_name, $file))
			throw new FileSystemException("Could not save to local file");
	}
	
	public function fromLocalFile($file) {
		if (!copy($file, $this->file_name))
			throw new FileSystemException("Could not load from local file");
	}	
	
	public function materialize() {
		return new FileMaterializedFile($this);
	}

	public function getChunk($chunk_size = 8192, $seekable = NULL) {
		if (!@$this->read_handle)
			$this->readStream();
		$handle = $this->read_handle;
		$accumulator = "";
		while (strlen($accumulator) < $chunk_size && !feof($handle)) {
			$accumulator .= fread($handle, $chunk_size);
		}
		return $accumulator;
	}
}
