<?php

require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class S3FileSystem extends AbstractFileSystem {
	
	private $s3;
	private $bucket;
	
	protected function getClass() {
		return S3File;
	}
	
	function __construct($key, $secret, $bucket, $region = "") {
		// parent::__construct();
		try {
			$this->s3 = Aws\S3\S3Client::factory(array(
				"key" => $key,
				"secret" => $secret,
				"region" => $region
			));
			$this->s3->registerStreamWrapper();
			$this->bucket = $bucket;
		} catch (Exception $e) {
			throw new FileSystemException($e . "");
		}
	}
	
	public function s3() {
		return $this->s3;
	}
	
	public function bucket() {
		return $this->bucket;
	}
	
}


Class S3File extends AbstractFile {
	
	private function s3() {
		return $this->file_system->s3();
	}
	
	private function bucket() {
		return $this->file_system->bucket();
	}
	
	public function s3path() {
		return 's3://' . $this->bucket() . '/' . $this->file_name;
	}
	
	public function waitUntilExists($options = array("wait_time" => 1000, "repeat_count" => 3)) {
		$this->s3()->waitUntil('ObjectExists', array(
			'Bucket' => $this->bucket(),
			'Key' => $this->file_name,
			'waiter.interval' => ceil($options["wait_time"] / 1000),
			'waiter.max_attempts' => $options["repeat_count"]
		));
	}
	
	public function size() {
		$meta = $this->s3()->headObject(array(
			"Bucket" => $this->bucket(),
			"Key" => $this->file_name
		));
		return intval($meta["ContentLength"]);
	}
	
	public function exists() {
		try {
			$meta = $this->s3()->headObject(array(
				"Bucket" => $this->bucket(),
				"Key" => $this->file_name
			));
			return !!@$meta;
		} catch (Exception $e) {
			return FALSE;
		}
	}
	
	public function delete() {
		try {
			$meta = $this->s3()->deleteObject(array(
				"Bucket" => $this->bucket(),
				"Key" => $this->file_name
			));
		} catch (Exception $e) {
			throw new FileSystemException("Could not delete file");
		}
	}
	
	protected function readStream() {
		$handle = fopen($this->s3path(), "r");
		if ($handle === FALSE)
			throw new FileSystemException("Could not open file");
		return $handle;
	}
	
	protected function writeStream() {
		$handle = fopen($this->s3path(), "w");
		if ($handle === FALSE)
			throw new FileSystemException("Could not open file");
		return $handle;
	}
			
	public function toLocalFile($file) {
		try {
			$this->s3()->getObject(array(
				'Bucket' => $this->bucket(),
				'Key'    => $this->file_name,
				'SaveAs' => $file
			));
		} catch (Exception $e) {
			throw new FileSystemException("Could not save to local file");
		}
	}
	
	public function fromLocalFile($file) {
		try {
			 $this->s3()->putObject(array(
				'Bucket' => $this->bucket(),
				'Key'    => $this->file_name,
				'SourceFile' => $file
			));
		} catch (Exception $e) {
			throw new FileSystemException("Could not save to local file");
		}
	}	

}
