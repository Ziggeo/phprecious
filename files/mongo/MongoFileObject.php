<?php

require_once(dirname(__FILE__) . "/../FileObject.php");

Class MongoFileObject extends FileObject {
	
	public function getSize() {
		return $this->object()->getSize();
	}
	
	public function getFilename() {
		return $this->object()->getFilename();
	}
	
	public function getStream() {
		if (method_exists($this->object(), "getResource"))
			return $this->object()->getResource();
		return parent::getStream();
	}
	
	public function getBytes() {
		return $this->object()->getBytes();
	}
	
	public function delete() {
		return $this->database()->gridfs()->delete($this->id());
	}
	
}
