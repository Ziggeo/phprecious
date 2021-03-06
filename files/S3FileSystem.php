<?php

require_once(dirname(__FILE__) . "/FileSystemException.php");
require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class S3FileSystem extends AbstractFileSystem {

	private $s3;
	private $bucket;
	private $region;

	protected function getClass() {
		return "S3File";
	}

	function __construct($opts) {
		try {
			if (empty($opts["region"]))
				$opts["region"] = "us-east-1"; //Defaulting to us-east-1 region
            if (!@$opts["use_implicit_credentials"]) {
                if (((!@$opts["key"] || !@$opts["secret"]) && (!@$opts["profile"])) || !@$opts["region"])
                    throw new Exception("Key, Secret and Region must be present to configure an AWS instance");
            }
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
			$this->s3 = new Aws\S3\S3MultiRegionClient($conf);
			$this->region = $this->s3->determineBucketRegion($opts["bucket"]);
			if ($this->region === "") //Might mean that the bucket doesn't exist
				throw new FileSystemException("We can't find your bucket.");
			if ($opts["region"] <> $this->region)
				throw new FileSystemFieldException(array("expected_region" => $this->region));
			$this->bucket = $opts["bucket"];
		} catch (FileSystemFieldException $e) {
			throw new FileSystemFieldException($e->getData());
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

	public function region() {
		return $this->region;
	}

	public function getPostSignedUrl($path, $expire = 60) {
		$formInputs = array("acl" => "private");
		$options = array(
			array("acl" => "private"),
			array("bucket" => $this->bucket()),
			array("eq", '$key', $path)
		);

		$expires = '+1 hours';

		$postObject = new Aws\S3\PostObjectV4(
			$this->s3(),
			$this->bucket(),
			$formInputs,
			$options,
			$expires
		);

		return $postObject;
	}

	/**
	 * Gets a signed url for any path from a bucket.
	 *
	 * Note: $expire param is not used in this version
	 *
	 *
	 * @param $path
	 * @param int $expire
	 * @return string
	 */
	public function getSignedUrl($path, $expire = 60) {
		$cmd = $this->s3()->getCommand("GetObject", [
			"Key" => $path,
			"Bucket" => $this->bucket()
		]);

		$request = $this->s3()->createPresignedRequest($cmd, "+1 hours");

		return (string)$request->getUri();
	}

	public function createFolderFromFilename($filename) {
		$pos = strrpos($filename, "/");
		$dir = substr($filename, 0, $pos);
		if (!StringUtils::ends_with($dir, "/"))
			$dir .= "/";
		$this->s3()->putObject(array(
			"Bucket" => $this->bucket(),
			'Key' => $dir,
			"ACL" => "private",
			'Body' => ""
		));
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

	private function region() {
		return $this->file_system->region();
	}

	public function s3path() {
		return "s3://" . $this->bucket() . "/" . $this->filename();
	}

	public function waitUntilExists($options = array("wait_time" => 1000, "repeat_count" => 3)) {
		$this->s3()->waitUntil("ObjectExists", array(
			"Bucket" => $this->bucket(),
			"Key" => $this->filename(),
			"waiter.interval" => ceil($options["wait_time"] / 1000),
			"waiter.max_attempts" => $options["repeat_count"],
			"@region" => $this->region()
		));
	}

	public function size() {
		$meta = $this->s3()->headObject(array(
			"Bucket" => $this->bucket(),
			"Key" => $this->filename(),
			"@region" => $this->region()
		));
		return intval($meta["ContentLength"]);
	}

	public function exists() {
		try {
			$meta = $this->s3()->headObject(array(
				"Bucket" => $this->bucket(),
				"Key" => $this->filename(),
				"@region" => $this->region()
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
				"Key" => $this->filename(),
				"@region" => $this->region()
			));
		} catch (Exception $e) {
			throw new FileSystemException("Could not delete file");
		}
	}

	public function readStream() {
		$handle = fopen($this->s3path(), "r");
		if ($handle === FALSE)
			throw new FileSystemException("Could not open file");
		return $handle;
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
				"SaveAs" => $file,
				"@region" => $this->region()
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
				"SourceFile" => $file,
				"@region" => $this->region()
			));
		} catch (Exception $e) {
			throw new FileSystemException($e->getMessage());
		}
	}

}
