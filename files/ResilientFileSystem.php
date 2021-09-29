<?php

require_once(dirname(__FILE__) . "/FileSystemException.php");
require_once(dirname(__FILE__) . "/AbstractFileSystem.php");

Class ResilientFileSystem extends AbstractFileSystem {
	
	protected $file_system = null;
	protected $options = null;
	
	protected function getClass() {
		return "ResilientFile";
	}
	
	function __construct($file_system, $options = array()) {
		// parent::__construct();
		$this->file_system = $file_system;
		$this->options = array_merge($options, array(
			"repeat_count" => 100,
			"wait_time" => 100 
		));
	}
	
	public function fileSystem() {
		return $this->file_system;
	}
	
	public function options() {
		return $this->options;
	}

}

Class ResilientFile extends AbstractFile {
	
	protected $file = null;
	
	function __construct($file_system, $file_name) {
		parent::__construct($file_system, $file_name);
		$this->file = $file_system->fileSystem()->getFile($file_name);
	}
	
	protected function resilient_execute($method, $args = array()) {
		$opts = $this->file_system->options();
		$repeat_count = $opts["repeat_count"];
		$wait_time = $opts["wait_time"];
		$result = NULL;
		while ($repeat_count > 0) {
			try {
				return call_user_func_array(array($this->file, $method), $args);
			} catch (FileSystemException $e) {
				$repeat_count--;
				usleep($wait_time);
			}
		}
		return $result;
	}
	
	public function size() {
		return $this->resilient_execute("size");
	}
	
	public function exists() {
		return $this->resilient_execute("exists");
	}

	public function isDir() {
		return $this->resilient_execute("isDir");
	}

	public function delete() {
		$this->resilient_execute("delete");
	}

	public function getFileClass() {
		return get_class($this->file);
	}

	public function writeStream() {
		return $this->resilient_execute("writeStream");
	}

	public function readStream() {
		return $this->resilient_execute("readStream");
	}

	public function readSeekableStream() {
		return $this->resilient_execute("readSeekableStream");
	}

	public function getChunk($chunk_size = 8192, $seekable = NULL) {
		return $this->resilient_execute("getChunk", array($chunk_size, $seekable));
	}

	public function toLocalFile($dest) {
		return $this->resilient_execute("toLocalFile", array($dest));
	}

	public function s3path() {
		return $this->resilient_execute("s3path");
	}

	public function tmpMaterialize() {
		return $this->resilient_execute("tmpMaterialize");
	}
}
