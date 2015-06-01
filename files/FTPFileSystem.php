<?php

require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class FTPFileSystem extends AbstractFileSystem {
	
	private $ftp;
	
	protected function getClass() {
		return FTPFile;
	}
	
	function __construct($options) {
		// parent::__construct();
		$ssl = @$options["ssl"] ? $options["ssl"] : FALSE;
		$connect = $ssl ? ftp_ssl_connect : ftp_connect;
		$host = $options["host"];
		$port = @$options["port"] ? $options["port"] : 21;
		$timeout = @$options["timeout"] ? $options["timeout"] : 90;
		$this->ftp = $connect($host, $port, $timeout);
		if ($this->ftp === FALSE)
			throw new FileSystemException("Could not connect to FTP");
		if (@$options["username"] && @$options["password"]) {
			$result = ftp_login($this->ftp, $options["username"], $options["password"]);
			if ($result === FALSE)
				throw new FileSystemException("Could not sign into FTP - wrong username / password?");
		}
		ftp_pasv($this->ftp, TRUE);
	}
	
	function __destruct() {
		ftp_close($this->ftp);
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
		if (!ftp_delete($this->ftp(), $this->file_name))
			throw new FileSystemException("Could not delete file");
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
	
	protected function readStream() {
		$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
		stream_set_write_buffer($sockets[0], 0);
		stream_set_timeout($sockets[1], 0);
		$ret = ftp_nb_fget($this->ftp(), $sockets[0], $this->file_name, $this->get_ftp_mode($this->file_name));
		while ($ret == FTP_MOREDATA)
			$ret = ftp_nb_continue($this->ftp());
		return $sockets[1];
	}

	/*
	protected function writeStream() {
		$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
		stream_set_write_buffer($sockets[0], 0);
		stream_set_timeout($sockets[1], 0);
		ftp_nb_fput($this->ftp(), $this->file_name, $sockets[1], $this->get_ftp_mode($this->file_name));
		return $sockets[0];
	}
	*/
			
	public function toLocalFile($file) {
		if (!ftp_get($this->ftp(), $file, $this->file_name, $this->get_ftp_mode($this->file_name)))
			throw new FileSystemException("Could not save to local file");
	}
	
	public function fromLocalFile($file) {
		if (!ftp_put($this->ftp(), $this->file_name, $file, $this->get_ftp_mode($this->file_name)))
			throw new FileSystemException("Could not load from local file");
	}	

}
