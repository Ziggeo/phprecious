<?php

require_once(dirname(__FILE__) . "/../../support/files/FileUtils.php");
require_once(dirname(__FILE__) . "/../../support/web/ContentType.php");


Class FileModel extends DatabaseModel {
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["removed"] = array(
			"type" => "boolean",
			"default" => FALSE
		);
		$attrs["prefix_type"] = array(
			"type" => "string",
			"default" => "default"
		);
		$attrs["identifier"] = array(
			"type" => "string",
			"index" => TRUE,
			"default" => function ($instance) {
				return Tokens::generate(self::optionsOf("identifier_length"));
			},
		);
		$attrs["file_type"] = array("type" => "string");
		$attrs["file_size"] = array("type" => "integer");
		$attrs["original_file_name"] = array("type" => "string");
		$attrs["file_name"] = array("type" => "string");
		return $attrs;
	}	
	
	protected static function initializeOptions() {
		$opts = parent::initializeOptions();
		$opts["directory"] = "";
		$opts["identifier_length"] = 16;
		$opts["split_identifier"] = 2;
		$opts["keep_files"] = TRUE;
		$opts["prefixes"] = array(
			"default" => "/default",
			"removed" => "/removed",
			"unref" => "/unref",
		);
		return $opts;
	}
	
	public function getPrefix($pfx_type = NULL) {
		$pfx = @self::optionsOf("prefixes");
		if (@$pfx) 
			return $pfx[@$pfx_type ? $pfx_type : $this->prefix_type];
		return "";
	}
	
	public function getIdentifierName() {
		$split_identifier = @self::optionsOf("split_identifier");
		if (!@$split_identifier)
			return $this->identifier;		
		return join("/", str_split($this->identifier, $split_identifier));
	}
	
	public function getFileName($prefix = NULL) {
		return self::optionsOf("directory") . $this->getPrefix($prefix) . "/" . $this->getIdentifierName();
	}
	
	public function getFileDirectory($prefix = NULL) {
		$name = $this->getFileName($prefix);
		$pos = strrpos($name, "/");
		return substr($name, 0, $pos);
	}
	
	public static function findFileBy($query) {
		$query["removed"] = FALSE;
		return self::findBy($query);
	}
	
	public static function allFiles($sort = NULL, $limit = NULL, $skip = NULL) {
		return self::allFilesBy(array(), $sort, $limit, $skip);
	}
	
	public static function allFilesBy($query, $sort = NULL, $limit = NULL, $skip = NULL) {
		$query["removed"] = FALSE;
		return self::allBy($query, $sort, $limit, $skip);
	}

	public static function findRemovedFileBy($query) {
		$query["removed"] = TRUE;
		return self::findBy($query);
	}
	
	public static function allRemovedFiles($sort = NULL, $limit = NULL, $skip = NULL) {
		return self::allRemovedFilesBy(array(), $sort, $limit, $skip);
	}
	
	public static function allRemovedFilesBy($query, $sort = NULL, $limit = NULL, $skip = NULL) {
		$query["removed"] = TRUE;
		return self::allBy($query, $sort, $limit, $skip);
	}
	
	public function extension() {
		return FileUtils::extensionOf($this->original_file_name);
	}
	
	public function contentType() {
		return ContentType::byFileName($this->original_file_name);
	}
	
	private function log_ident() {
		return $this->id() . "/" . $this->original_file_name . "/" . $this->identifier;
	}
	
	public function httpReadFile() {
		static::log("Reading file " . $this->log_ident() . "", Logger::INFO_2);
		header('Content-type: ' . $this->contentType());
		ob_clean();
		flush();
	    readfile($this->getFileName());
	}
	
	public static function createByUpload($FILE, $options = array()) {
		if (!@$FILE || $FILE["error"] > 0)
			return NULL;
		static::log("Create file by upload " . $original_file_name . "", Logger::INFO);
		$original_file_name = $file_upload["name"];
		$file_size = $file_upload["size"];
		$tmp_file = $file_upload["tmp_name"];
		if (!file_exists($tmp_file)) {
			static::log("Error: tmp file does not exist.", Logger::WARN);
			return NULL;
		}
		$file_name = @$options["file_name"] ? $options["file_name"] : $original_file_name;
		$file_type = @$options["file_type"] ? $options["file_type"] : FileUtils::extensionOf($original_file_name);
		$class = get_called_class();
		$instance = new $class(array(
			"file_type" => $file_type,
			"file_size" => $file_size,
			"original_file_name" => $original_file_name,
			"file_name" => $file_name
		));
		if (file_exists($instance->getFileName())) {
			static::log("Error: identifier already exists.", Logger::WARN);
			return NULL;
		}
		if (!mkdir($instance->getDirectoryPath(), 0777, TRUE)) {
			static::log("Error: cannot create directory.", Logger::WARN);
			return NULL;
		}
		if (!$instance->save())
			return NULL;
		if (!move_uploaded_file($tmp_file, $instance->getFileName())) {
			static::log("Error: cannot move file.", Logger::WARN);
			$instance->delete();
			return NULL;
		}
		return $instance;
	}
	
	public static function createByFile($filename, $options = array(), $move = TRUE) {
		static::log("Create file by " . $filename . "", Logger::INFO);
		if (!file_exists($filename)) {
			static::log("Error: file does not exist.", Logger::WARN);
			return NULL;
		}
		$original_file_name = basename($filename);
		$file_size = filesize($filename);
		$file_name = @$options["file_name"] ? $options["file_name"] : $original_file_name;
		$file_type = @$options["file_type"] ? $options["file_type"] : FileUtils::extensionOf($original_file_name);
		$class = get_called_class();
		$instance = new $class(array(
			"file_type" => $file_type,
			"file_size" => $file_size,
			"original_file_name" => $original_file_name,
			"file_name" => $file_name
		));
		if (file_exists($instance->getFileName())) {
			static::log("Error: identifier already exists.", Logger::WARN);
			return NULL;
		}
		if (!mkdir($instance->getDirectoryPath(), 0777, TRUE)) {
			static::log("Error: cannot create directory.", Logger::WARN);
			return NULL;
		}
		if (!$instance->save())
			return NULL;
		if ($move) {
			if (!rename($filename, $instance->getFileName())) {
				$instance->delete();
				return NULL;
			}
		} else {
			if (!copy($filename, $instance->getFileName())) {
				static::log("Error: cannot move file.", Logger::WARN);
				$instance->delete();
				return NULL;
			}
		}
		return $instance;
	}

	protected function afterDelete() {
		@unlink($this->getFileName());
	}
	
	public function remove() {
		static::log("Remove file " . $this->log_ident(), Logger::INFO);
		if ($this->removed || !@self::optionsOf("keep_files"))
			return $this->delete();
		if (!@self::optionsOf("prefixes"))
			return FALSE;
		$pfx = self::optionsOf("prefixes");
		$removedpfx = @$pfx["removed"];
		if (!@$removedpfx)
			return FALSE;
		if (!mkdir($this->getDirectoryPath($removedpfx), 0777, TRUE)) {
			static::log("Error: cannot create directory.", Logger::WARN);
			return FALSE;
		}
		if (!rename($this->getFileName(), $this->getFileName($removedpfx))) {
			static::log("Error: cannot move file.", Logger::WARN);
			return FALSE;
		}
		return $this->update(array("removed" => TRUE, "prefix_type" => "removed"));		
	}
		
	public static function cleanRemovedFiles() {
		$skip = 0;
		$limit = 100;
		$files = self::allRemovedFiles(array("created" => 1), $limit, $skip);
		while (count($files) > 0) {
			$skip += $limit;
			foreach ($files as $file)
				if ($file->remove())
					$skip--;
			$files = self::allRemovedFiles(array("created" => 1), $limit, $skip);
		}
	}
	
	// Deletes all files in the unreferenced folder. If it does not exist, removeUnreferencedFiles is called.
	public static function cleanUnreferencedFiles() {
		if (!@self::optionsOf("prefixes"))
			return self::removeUnreferencedFiles();
		$pfx = self::optionsOf("prefixes");
		$unrefpfx = @$pfx["unref"];
		if (!@$unrefpfx)
			return self::removeUnreferencedFiles();
		FileUtils::delete_tree(self::optionsOf("directory") . $unrefpfx, TRUE);
	}
	
	// Identifies unreferenced files. If unref prefix is available, they are moved. Otherwise, they are deleted.
	public static function removeUnreferencedFiles() {
		if (!@self::optionsOf("prefixes"))
			return self::removeUnreferencedFilesRec(self::optionsOf("directory"), "");
		$pfx = self::optionsOf("prefixes");
		if (@$pfx["default"])
			return self::removeUnreferencedFilesRec(self::optionsOf("directory") . $pfx["default"], "");
		if (@$pfx["removed"])
			return self::removeUnreferencedFilesRec(self::optionsOf("directory") . $pfx["removed"], "");
	}
	
	private static function removeUnreferencedFiles($base, $sub) {
		if ($sub != "" && is_file($base . "/" . $sub)) {
			$ident = str_replace("/", "", $sub);
			if (@self::findBy(array("identifier" => $ident)))
				return;
			$pfx = self::optionsOf("prefixes");
			if (@$pfx && @$pfx["unref"]) {
				$move_base = self::optionsOf("directory") . $pfx["unref"];
				@mkdir(FileUtils::pathOf($move_base . "/" . $sub));
				@rename($base . "/" . $sub, $move_base . "/" . $sub);
			} else {
				@unlink($base . "/" . $sub);
			}
		} else {
			if (@$handle = opendir($sub == "" ? $base : ($base . "/" . $sub))) {
			    while (false !== ($entry = readdir($handle)))
			        if ($entry != "." && $entry != "..")
						self::removeUnreferencedFiles($base, $sub == "" ? $entry : ($sub . "/" . $entry));
			    closedir($handle);
			}
		}
	}
	
}