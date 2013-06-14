<?php

class UploadedFile extends BaseModel {

	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["session_id"] = array("type" => "id", "index" => TRUE);
		$attrs["file_name"] = array("type" => "string");
		$attrs["file_size"] = array("type" => "integer");
		$attrs["file_ident"] = array("type" => "string");
		return $attrs;
	}

	protected function initializeAssocs() {
		parent::initializeAssocs();
		$this->assocs["session"] = new ModelHasOneAssociation($this, "session_id", "Session", array("cached" => TRUE));
	}
	
	public static function createByUpload($file_upload, $session_id) {
		if (!@$file_upload || $file_upload["error"] > 0)
			return NULL;
		$file_name = $file_upload["name"];
		$file_size = $file_upload["size"];
		$tmp_file = $file_upload["tmp_name"];
		if (!file_exists($tmp_file))
			return NULL;
		$file = new UploadedFile(array(
			"session_id" => $session_id,
			"file_name" => $file_name,
			"file_size" => $file_size
		));
		if (!$file->save())
			return NULL;
		if (!$file->update(array("file_ident" => $file->id())))
			return NULL;
		if (!move_uploaded_file($tmp_file, $file->getIdentPath())) {
			$file->delete();
			return NULL;
		}
		return $file;
	}
	
	public function getIdentPath() {
		return APP()->resolvePath("{data}/" . $this->file_ident);
	}
	
	public function getContentType() {
		return ContentType::byFileName($this->file_name);
	}
	 					
	protected function afterDelete() {
		parent::afterDelete();
		@unlink($this->getIdentPath());
	}
	

}