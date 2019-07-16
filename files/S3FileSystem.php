<?php

require_once(dirname(__FILE__) . "/FileSystemException.php");
require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class S3FileSystem extends AbstractFileSystem {

	private $s3;
	private $bucket;

	protected function getClass() {
		return "S3File";
	}

	function __construct($opts) {
		try {
			if (((!@$opts["key"] || !@$opts["secret"]) && (!@$opts["profile"])) || !@$opts["region"])
				throw new Exception("Key, Secret and Region must be present to configure an AWS instance");
			$conf = array(
				"region" => $opts["region"],
				"version" => "2006-03-01"
			);
			//Using credentials when set in the config.
			if ($opts["key"] <> "" && $opts["secret"] <> "")
				$conf["credentials"] = new Aws\Credentials\Credentials($opts["key"], $opts["secret"]);
			//Allowing the use of profiles.
			if (($opts["key"] === "" || $opts["secret"] === "") && $opts["profile"] <> "")
				$conf["profile"] = $opts["profile"];
			if ($opts["signature"] !== "v2")
				$conf["signature"] = $opts["signature"];
			$this->s3 = new Aws\S3\S3Client($conf);
			$this->bucket = $opts["bucket"];
		} catch (Exception $e) {
			throw new FileSystemException($e->getMessage());
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

	public function filename() {
		return ltrim($this->file_name, "/"); //For the new version the string shouldn"t start with "/"
	}

	private function s3() {
		return $this->file_system->s3();
	}

	private function bucket() {
		return $this->file_system->bucket();
	}

	public function s3path() {
		return "s3://" . $this->bucket() . "/" . $this->filename();
	}

	public function waitUntilExists($options = array("wait_time" => 1000, "repeat_count" => 3)) {
		$this->s3()->waitUntil("ObjectExists", array(
			"Bucket" => $this->bucket(),
			"Key" => $this->filename(),
			"waiter.interval" => ceil($options["wait_time"] / 1000),
			"waiter.max_attempts" => $options["repeat_count"]
		));
	}

	public function size() {
		$meta = $this->s3()->headObject(array(
			"Bucket" => $this->bucket(),
			"Key" => $this->filename()
		));
		return intval($meta["ContentLength"]);
	}

	public function exists() {
		try {
			$meta = $this->s3()->headObject(array(
				"Bucket" => $this->bucket(),
				"Key" => $this->filename()
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
				"Key" => $this->filename()
			));
		} catch (Exception $e) {
			throw new FileSystemException("Could not delete file");
		}
	}

	public function readStream($options = array()) {
		$range = @$options["range"];
		$block_size = $options["block_size"];
		$file_size = $this->size();
		$remaining = @$range ? $range["bytes"] : $file_size;

		if (@$options["head_only"])
			return $remaining;
		set_time_limit(0);

		$transferred = 0;
		$remaining = @$range ? $range["bytes"] : $file_size;
		while (($remaining > 0) && !connection_aborted()) {
			$read_size = min($remaining, $block_size);
			if (@$range) {
				$start = $range["start"];
				$end = $range["end"];
			} else {
				$start = $transferred;
				$end = ($transferred + $block_size - 1) > $file_size ? $file_size : $transferred + $block_size - 1;
			}
			$data = $this->s3()->getObject(array(
				"Bucket" => $this->bucket(),
				"Key" => $this->filename(),
				"Range" => "bytes=$start-$end"
			));
			$returned_size = strlen($data["Body"]);
			if ($returned_size > $read_size)
				throw new FileStreamerException("Read returned more data than requested.");
			print $data["Body"];
			$transferred += $returned_size;
			$remaining -= $returned_size;
		}
		return TRUE;
	}

	public function readFile() {
		$file = $this->s3()->getObject(array(
			'Bucket' => $this->bucket(),
			'Key' => $this->filename()
		));

		return $file["Body"];
	}

	public function writeStream() {
		$handle = fopen($this->s3path(), "w");
		if ($handle === FALSE)
			throw new FileSystemException("Could not open file");
		return $handle;
	}

	public function toLocalFile($file) {
		try {
			$this->s3()->getObject(array(
				"Bucket" => $this->bucket(),
				"Key" => $this->filename(),
				"SaveAs" => $file
			));
		} catch (Exception $e) {
			throw new FileSystemException($e->getMessage());
		}
	}

	public function fromLocalFile($file) {
		try {
			$this->s3()->putObject(array(
				"Bucket" => $this->bucket(),
				"Key" => $this->filename(),
				"SourceFile" => $file
			));
		} catch (Exception $e) {
			throw new FileSystemException($e->getMessage());
		}
	}

}
