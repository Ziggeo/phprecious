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


Abstract Class AbstractMaterializedFile {
	
	public abstract function filename();

	public function release() {}
	
	function __destruct() {
		$this->release();
	} 
	
	public function materialize() {
		return $this;
	}

}


Class FileMaterializedFile extends AbstractMaterializedFile {
	
	protected $file;
	
	function __construct($file) {
		$this->file = $file;
	}
	
	public function filename() {
		return $this->file->filename();
	}
	
}


Class TemporaryMaterializedFile extends AbstractMaterializedFile {

	protected $filename;

	function __construct($file) {
		$this->filename = tempnam(sys_get_temp_dir(), "");
		$idx = strrpos($file->filename(), ".");
		if ($idx !== FALSE)
			$this->filename .= substr($file->filename(), $idx);
		$file->toLocalFile($this->filename);
	}

	public function filename() {
		return $this->filename;
	}
	
	public function release() {
		unlink($this->filename);
	}

}



Class AbstractFile {
	
	protected $file_system = null;
	protected $file_name = null;
	
	function __construct($file_system, $file_name) {
		$this->file_system = $file_system;
		$this->file_name = $file_name;
	}
	
	public function size() {
		throw new FileSystemException("Unsupported Operation");
	}
	
	public function filename() {
		return $this->file_name;
	}
	
	public function waitUntilExists($options = array("wait_time" => 1000, "repeat_count" => 3)) {
		$attempts = $options["repeat_count"];
		while ($attempts > 0) {
			if ($this->exists())
				return;
			$attempts--;
			usleep($options["wait_time"]);
		}
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
	
	protected function writeStream() {
		throw new FileSystemException("Unsupported Operation");
	} 

	protected function readStream() {
		throw new FileSystemException("Unsupported Operation");
	}
	
	public function putContents($data) {
		$stream = $this->writeStream();
		fwrite($stream, $data);
		fclose($stream);
	}
	
	public function getContents() {
		$stream = $this->readStream();
		$data = fread($stream, $this->size());
		fclose($stream);
		return $data;
	}
	
	public function toLocalFile($file) {
		$input = $this->readStream();
		$output = fopen($file, "w");
		stream_copy_to_stream($input, $output);
		fclose($output);
	}
	
	public function fromLocalFile($file) {
		$input = fopen($file, "r");
		$output = $this->writeStream();
		stream_copy_to_stream($input, $output);
		fclose($output);
	}	
	
	public function materialize() {
		return new TemporaryMaterializedFile($this);
	}

}
