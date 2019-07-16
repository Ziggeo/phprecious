<?php

require_once(dirname(__FILE__) . "/FileSystemException.php");
require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class LocalFileSystem extends AbstractFileSystem {
	
	protected function getClass() {
		return "LocalFile";
	}
	
	private static $fs_singleton = NULL;
	
	static function singleton() {
		if (self::$fs_singleton == NULL)
			self::$fs_singleton = new LocalFileSystem();
		return self::$fs_singleton;
	}
	
}

Class LocalFile extends AbstractFile {
	
	public function size() {
		return filesize($this->filename());
	}
	
	public function exists() {
		return file_exists($this->filename()) && is_file($this->filename());
	}
	
	public function delete() {
		if (!unlink($this->file_name))
			throw new FileSystemException("Could not delete file");
	}
	
	public function readStream($options = array()) {
		$range = @$options["range"];
		$open_mode = isset($options["open_mode"]) ? $options["open_mode"] : "rb";
		$open_context = $options["open_context"];
		$block_size = $options["block_size"];
		$file_size = $this->size();
		$remaining = @$range ? $range["bytes"] : $file_size;

		if (@$options["head_only"])
			return $remaining;
		set_time_limit(0);
		$handle = $open_context ? fopen($this->filename(), $open_mode, FALSE, $open_context) : fopen($this->filename(), $open_mode);
		if (!$handle)
			throw new FileStreamerException("Could not open file.");

		if (@$range)
			fseek($handle, $range["start"]);

		$transferred = 0;
		$remaining = @$range ? $range["bytes"] : $file_size;

		while (!feof($handle) && ($remaining > 0) && !connection_aborted()) {
			$read_size = min($remaining, $block_size);
			$data = fread($handle, $read_size);
			$returned_size = strlen($data);
			if ($returned_size > $read_size)
				throw new FileStreamerException("Read returned more data than requested.");
			print $data;
			$transferred += $returned_size;
			$remaining -= $returned_size;
			flush();
			ob_flush();
		}

		fclose($handle);
		return TRUE;
	}
	
	public function readFile() {
		readfile($this->filename());
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

}
