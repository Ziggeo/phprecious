<?php

require_once(dirname(__FILE__) . "/../support/web/ContentType.php");

Class FileObject {
	
	private $database;
	private $object;
	private $id;
	
	function __construct($database, $id, $object) {
		$this->database = $database;
		$this->object = $object;
		$this->id = $id;
	}
	
	protected function database() {
		return $this->database;
	}
	
	protected function object() {
		return $this->object;
	}
	
	public function id() {
		return $this->id;
	}
	
	public function getSize() {}
	
	public function getFilename() {}
	
	public function getStream() {
		return fopen('data://text/plain;base64,' . base64_encode($this->getBytes()), 'r');
	}
	
	public function getBytes() {
		return stream_get_contents($this->getStream());
	}
	
	public function contentType() {
		return ContentType::byFileName($this->getFilename());
	}
	
	public function echoStream() {
		$stream = $this->getStream();
		while (!feof($stream))
		    echo fread($stream, 8192);
	}

	public function httpStreamFile() {
		header('Content-type: ' . $this->contentType());
		ob_clean();
		flush();
		$this->echoStream();
	}
	
	public function delete() {}
	
}