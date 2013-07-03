<?php

require_once(dirname(__FILE__) . "/../DatabaseFileSystem.php");
require_once(dirname(__FILE__) . "/MongoFileObject.php");

Class MongoFileSystem extends DatabaseFileSystem {
	
	private $gridsf = null;
	
	public function gridfs() {
		if (!@$this->gridfs)
			$this->gridfs = $this->database()->getGridFS();
		return $this->gridfs;
	}
	
	public function getFile($id) {
		if (!@$id)
			return NULL;
		$object = $this->gridfs()->get($id);
		return @$object ? new MongoFileObject($this, $id, $object) : NULL;
	}
	
	public function putFile($filename) {
		$id = $this->gridfs()->storeFile($filename, array(), array("safe" => true));
		return $this->getFile($id);
	}

	public function putFileByNamedUpload($name) {
		$id = $this->gridfs()->storeUpload($name, array());
		return $this->getFile($id);
	}
		

}
