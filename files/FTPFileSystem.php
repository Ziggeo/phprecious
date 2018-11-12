<?php

require_once(dirname(__FILE__) . "/FileSystemException.php");
require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class FTPFileSystem extends AbstractFileSystem {

	private $ftp;

	protected function getClass() {
		return FTPFile;
	}

	function __construct($options) {
		$ftp_server = 'ftp://' . $options["host"];
		$ch = curl_init();
		if ($ch === FALSE)
			throw new FileSystemException("Could not connect to FTP");
		curl_setopt($ch, CURLOPT_URL, $ftp_server);
		curl_setopt($ch, CURLOPT_USERPWD, $options["username"] . ':' . $options["password"]);
		curl_setopt($ch, CURLOPT_PORT, $options["port"]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		if (!empty($options["passive"]) && !$options["passive"])
			curl_setopt($ch, CURLOPT_FTPPORT, "-");
		if (!empty($options["disable_epsv"]) && !$options["disable_epsv"])
			curl_setopt ( $ch , CURLOPT_FTP_USE_EPSV, 0 );

		$this->ftp = $ch;
	}

	function __destruct() {
		curl_close($this->ftp);
		$this->ftp = null;
	}

	public function ftp() {
		return $this->ftp;
	}

}


Class FTPFile extends AbstractFile {

	private function ftp() {
		return $this->file_system->ftp();
	}

	public function size() {
		return ftp_size($this->ftp(), $this->file_name);
	}

	public function exists() {
		return $this->size() >= 0;
	}

	public function delete() {
		$ftp = $this->ftp();
		$url = curl_getinfo($ftp, CURLINFO_EFFECTIVE_URL);
		curl_setopt($ftp, CURLOPT_URL, $url . "/" . $this->file_name);
		curl_setopt($ftp, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ftp, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ftp);
		$error_no = curl_errno($ftp);
		if ($error_no != 0)
			throw new FileSystemException("Could not delete file from ftp." . " - Error No: " . $error_no );
	}

	private function get_ftp_mode($file) {
		$path_parts = pathinfo($file);
		if (!isset($path_parts['extension'])) return FTP_BINARY;
		switch (strtolower($path_parts['extension'])) {
			case 'am':case 'asp':case 'bat':case 'c':case 'cfm':case 'cgi':case 'conf':
			case 'cpp':case 'css':case 'dhtml':case 'diz':case 'h':case 'hpp':case 'htm':
			case 'html':case 'in':case 'inc':case 'js':case 'm4':case 'mak':case 'nfs':
			case 'nsi':case 'pas':case 'patch':case 'php':case 'php3':case 'php4':case 'php5':
			case 'phtml':case 'pl':case 'po':case 'py':case 'qmail':case 'sh':case 'shtml':
			case 'sql':case 'tcl':case 'tpl':case 'txt':case 'vbs':case 'xml':case 'xrc':
			return FTP_ASCII;
		}
		return FTP_BINARY;
	}

	public function readStream() {
		$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
		stream_set_write_buffer($sockets[0], 0);
		stream_set_timeout($sockets[1], 0);
		$ret = ftp_nb_fget($this->ftp(), $sockets[0], $this->file_name, $this->get_ftp_mode($this->file_name));
		while ($ret == FTP_MOREDATA)
			$ret = ftp_nb_continue($this->ftp());
		return $sockets[1];
	}

	public function fromFile($file) {
		$file = $file->materialize();
		return $this->fromLocalFile($file->filename());
	}

	public function toLocalFile($file) {
		$file = fopen($file, 'w');
		$ftp = $this->ftp();
		$url = curl_getinfo($ftp, CURLINFO_EFFECTIVE_URL);
		curl_setopt($ftp, CURLOPT_URL, $url . "/" . $this->file_name);
		curl_setopt($ftp, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ftp, CURLOPT_FILE, $file); #
		curl_exec($ftp);
		fclose($file);
		$error_no = curl_errno($ftp);
		if ($error_no != 0)
			throw new FileSystemException("Could not download file from ftp." . " - Error No: " . $error_no );
	}

	public function fromLocalFile($file) {
		$ch = $this->ftp();
		$fileStream = fopen($file, "r");
		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_setopt($ch, CURLOPT_URL, $url . "/" . $this->file_name);
		curl_setopt($ch, CURLOPT_UPLOAD, 1);
		curl_setopt($ch, CURLOPT_INFILE, $fileStream);

		$output = curl_exec($ch);
		$error_no = curl_errno($ch);
		if ($error_no != 0)
			throw new FileSystemException("Could not upload file to ftp." . " - Error No: " . $error_no );
	}

}
