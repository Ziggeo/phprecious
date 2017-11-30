<?php

require_once(dirname(__FILE__) . "/../vendor/autoload.php");
require_once(dirname(__FILE__) . "/FileSystemException.php");
require_once(dirname(__FILE__) . "/AbstractFileSystem.php");

use phpseclib\Net\SFTP;


Class SFTPFileSystem extends AbstractFileSystem {

	private $sftp;

	protected function getClass() {
		return "SFTPFile";
	}

	function __construct($options) {
		$host = $options["host"];
		$port = $options["port"];
		$username = $options["username"];
		$password = $options["password"];
		$connection = new SFTP($host, $port);
		if (!$connection->login($username, $password)) {
			throw new Exception('Login Failed');
		}
		$this->sftp = $connection;
		if (! $this->sftp)
			throw new Exception("Could not initialize SFTP subsystem.");
	}

	function __destruct() {
		$this->fstp = null;
	}

	public function ftp() {
		return $this->sftp;
	}

}


Class SFTPFile extends AbstractFile {

	private function sftp() {
		return $this->file_system->ftp();
	}

	public function size() {
		return $this->sftp()->size($this->file_name);
	}

	public function exists() {
		return $this->size() >= 0;
	}

	public function delete() {
		$sftp = $this->sftp();
		try {
			$result = $sftp->delete($this->file_name);
		} catch (Exception $e) {
			throw $e;
		}
		return $result;
	}

	public function fromFile($file) {
		$file = $file->materialize();
		return $this->fromLocalFile($file->filename());
	}

	public function toLocalFile($file) {
		$sftp = $this->sftp();
		try {
			$result = $sftp->get($this->file_name, $file);
		} catch (Exception $e) {
			throw $e;
		}
		return $result;
	}

	public function fromLocalFile($file) {
		$sftp = $this->sftp();
		try {
			$result = $sftp->put($this->file_name, $file, SFTP::SOURCE_LOCAL_FILE);
		} catch (Exception $e) {
			throw $e;
		}
		return $result;
	}

}
