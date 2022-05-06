<?php

require_once(dirname(__FILE__) . "/../../support/files/FileUtils.php");
require_once(dirname(__FILE__) . "/../../support/web/FileStreamer.php");


Class FileModel extends DatabaseModel {
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["removed"] = array(
			"type" => "boolean",
            "index" => TRUE,
			"default" => FALSE,
			"tags" => array("read")
		);
		$attrs["prefix_type"] = array(
			"type" => "string",
			"default" => "default",
			"tags" => array("read")			
		);
		$idl = self::classOptionsOf("identifier_length");
		$attrs["identifier"] = array(
			"type" => "string",
			"index" => TRUE,
			"default" => function ($instance) use ($idl) {
				return Tokens::generate($idl);
			},
			"tags" => array("read")
		);
		$attrs["file_size"] = array(
			"type" => "integer",
            "index" => TRUE,
			"tags" => array("read")
		);
		$attrs["original_file_name"] = array(
			"type" => "string",
			"tags" => array("read")
		);
		$attrs["extension"] = array(
			"type" => "string",
			"tags" => array("read")
		);
		$attrs["file_name"] = array(
			"type" => "string",
            "index" => TRUE,
			"tags" => array("read")
		);
		return $attrs;
	}	
	
	protected static function initializeOptions() {
		$opts = parent::initializeOptions();
		$opts["directory"] = "";
		$opts["identifier_length"] = 16;
		$opts["split_identifier"] = 2;
		$opts["keep_files"] = TRUE;
		$opts["retry_count"] = 1;
		$opts["retry_delay"] = 10;
		$opts["block_size"] = 8 * 1024;
		$opts["prefixes"] = array(
			"default" => "/default",
			"removed" => "/removed",
			"unref" => "/unref",
		);
		return $opts;
	}
	
	public function getPrefix($pfx_type = NULL) {
		$pfx = @$this->optionsOf("prefixes");
		if (@$pfx) 
			return $pfx[@$pfx_type ? $pfx_type : $this->prefix_type];
		return "";
	}
	
	public function getIdentifierName() {
		$split_identifier = @$this->optionsOf("split_identifier");
		if (!@$split_identifier)
			return $this->identifier;		
		return join("/", str_split($this->identifier, $split_identifier));
	}

	//Default alias for getFileName
	public function getFilePath($prefix = NULL) {
		return $this->getFileName($prefix);
	}

	public function getFileName($prefix = NULL) {
		return $this->optionsOf("directory") . $this->getPrefix($prefix) . "/" . $this->getIdentifierName() . "." . $this->extension;
	}

	public function getFileNameWithoutBase($prefix = NULL) {
		return $this->getPrefix($prefix) . "/" . $this->getIdentifierName() . "." . $this->extension;
	}
	
	public function getDirectoryPath($prefix = NULL) {
		$name = $this->getFileName($prefix);
		$pos = strrpos($name, "/");
		return substr($name, 0, $pos);
	}
	
	public static function findFileBy($query) {
		$query["removed"] = FALSE;
		return self::findBy($query);
	}
	
	public static function findFileByIdentifier($identifier) {
		return self::findFileBy(array("identifier" => $identifier));
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
	
	public function contentType() {
		return ContentType::byExtension($this->extension);
	}
	
	private function log_ident() {
		return $this->id() . "/" . $this->original_file_name . "/" . $this->identifier;
	}
	
	public function echoReadFile() {
	    readfile($this->getFileName());
	}

	public function httpReadFile($download = FALSE, $download_name = NULL, $head_only = FALSE) {
		static::log(Logger::INFO_2, "Reading file " . $this->log_ident() . "");
		return FileStreamer::streamFile($this->getFileName(), array(
			"download" => $download,
			"download_name" => @$download_name ? $download_name : $this->file_name,
			"block_size" => $this->optionsOf("block_size"),
			"head_only" => $head_only
		));
	}
	
	public static function createFolderNoFile($filename, $options = array()) {
		static::log(Logger::INFO, "Create file by " . $filename . "");
		$original_file_name = basename($filename);
		$file_name = @$options["file_name"] ? $options["file_name"] : $original_file_name;
		$extension = @$options["extension"] ? $options["extension"] : FileUtils::extensionOf($file_name);
		$class = get_called_class();
		$instance = new static(array(
			"extension" => $extension,
			"original_file_name" => $original_file_name,
			"file_name" => $file_name
		));
		if (file_exists($instance->getFileName())) {
			static::log("Error: identifier already exists.", Logger::WARN);
			return NULL;
		}
		$retry_count = self::classOptionsOf("retry_count");
		$retry_delay = self::classOptionsOf("retry_delay");
		while ($retry_count > 0) {
			if (mkdir($instance->getDirectoryPath(), 0777, TRUE))
				break;
			$retry_count--;
			if ($retry_count > 0)
				usleep(1000 * $retry_delay);
			else {
				static::log("Error: cannot create directory.", Logger::WARN);
				return NULL;
			}
		}
		$instance->file_size = 0;		
		if (!$instance->save())
			return NULL;
		return $instance;
	}

	public static function createFileObject($filename, $options = array()) {
		static::log(Logger::INFO, "Create just file object by " . $filename . "");
		$original_file_name = basename($filename);
		$file_name = @$options["file_name"] ? $options["file_name"] : $original_file_name;
		$extension = @$options["extension"] ? $options["extension"] : FileUtils::extensionOf($file_name);
		$instance = new static(array(
			"extension" => $extension,
			"original_file_name" => $original_file_name,
			"file_name" => $file_name
		));
		$instance->file_size = 0;
		if (!$instance->save())
			return NULL;
		return $instance;
	}

	public static function createByData($filename, $data, $options = array()) {
		$instance = self::createFolderNoFile($filename, $options);
		if ($instance == NULL)
			return NULL;
		$fhandle = fopen($instance->getFileName(), "wb");
		fwrite($fhandle, $data);
		fclose($fhandle);
		$instance->file_size = filesize($instance->getFileName());		
		if (!$instance->save()) {
			$instance->remove();
			return NULL;
		}
		return $instance;
	}
	public static function copy($from, $to, $context = NULL) {
		return copy($from, $to, $context);
	}

	public static function rename($from, $to, $context = NULL) {
		return rename($from, $to, $context);
	}

	public static function mkdir($directory, $permissions = 0777, $recursive = FALSE, $context = NULL) {
		return mkdir($directory, $permissions, $recursive, $context);
	}

	public static function move_uploaded_file($from, $to) {
		return move_uploaded_file($from, $to);
	}

	public function updateSize() {
		$this->update(array("file_size" => filesize($this->getFileName())));
	}
	
	public static function createByUpload($FILE, $options = array()) {
		if (!@$FILE || $FILE["error"] > 0)
			return NULL;
		$original_file_name = $FILE["name"];
		static::log(Logger::INFO, "Create file by upload " . $original_file_name . "");
		$file_size = $FILE["size"];
		$tmp_file = $FILE["tmp_name"];
		if (!file_exists($tmp_file)) {
			static::log(Logger::WARN, "Error: tmp file does not exist.");
			return NULL;
		}
		$file_name = @$options["file_name"] ? $options["file_name"] : $original_file_name;
		$extension = @$options["extension"] ? $options["extension"] : FileUtils::extensionOf($file_name);
		$class = get_called_class();
		$instance = new $class(array(
			"extension" => $extension,
			"file_size" => $file_size,
			"original_file_name" => $original_file_name,
			"file_name" => $file_name
		));
		if (file_exists($instance->getFileName())) {
			static::log(Logger::WARN, "Error: identifier already exists.");
			return NULL;
		}
		$retry_count = self::classOptionsOf("retry_count");
		$retry_delay = self::classOptionsOf("retry_delay");
		while ($retry_count > 0) {
			if (forward_static_call(array($class, "mkdir"), $instance->getDirectoryPath(), 0777, TRUE))
				break;
			$retry_count--;
			if ($retry_count > 0)
				usleep(1000 * $retry_delay);
			else {
				static::log("Error: cannot create directory.", Logger::WARN);
				return NULL;
			}
		}
		if (!$instance->save())
			return NULL;
		$retry_count = self::classOptionsOf("retry_count");
		$retry_delay = self::classOptionsOf("retry_delay");
		while ($retry_count > 0) {
			if (FileUtils::move_uploaded_file($tmp_file, $instance->getFilePath(), $class))
				break;
			$retry_count--;
			if ($retry_count > 0)
				usleep(1000 * $retry_delay);
			else {
				static::log(Logger::WARN, "Error: cannot move file.");
				$instance->delete();
				return NULL;
			}
		}
		return $instance;
	}
	
	public static function createByFile($filename, $options = array(), $move = FALSE) {
		static::log(Logger::INFO, "Create file by " . $filename . "");
		if (!file_exists($filename)) {
			static::log(Logger::WARN, "Error: file does not exist.");
			return NULL;
		}
		$original_file_name = basename($filename);
		$file_size = filesize($filename);
		$file_name = @$options["file_name"] ? $options["file_name"] : $original_file_name;
		$extension = @$options["extension"] ? $options["extension"] : FileUtils::extensionOf($file_name);
		$class = get_called_class();
		$instance = new static(array(
			"extension" => $extension,
			"file_size" => $file_size,
			"original_file_name" => $original_file_name,
			"file_name" => $file_name
		));
		if (file_exists($instance->getFileName())) {
			static::log("Error: identifier already exists.", Logger::WARN);
			return NULL;
		}
		$retry_count = self::classOptionsOf("retry_count");
		$retry_delay = self::classOptionsOf("retry_delay");
		while ($retry_count > 0) {
			if (forward_static_call(array($class, "mkdir"), $instance->getDirectoryPath(), 0777, TRUE))
				break;
			$retry_count--;
			if ($retry_count > 0)
				usleep(1000 * $retry_delay);
			else {
				static::log("Error: cannot create directory.", Logger::WARN);
				return NULL;
			}
		}
		if (!$instance->save())
			return NULL;
		$retry_count = self::classOptionsOf("retry_count");
		$retry_delay = self::classOptionsOf("retry_delay");
		while ($retry_count > 0) {
			$success = ($move && forward_static_call(array($class, "rename"), $filename, $instance->getFilePath())) || (!$move && forward_static_call(array($class, "copy"), $filename, $instance->getFilePath()));
			if ($success)
				break;
			$retry_count--;
			if ($retry_count > 0)
				usleep(1000 * $retry_delay);
			else {
				static::log("Error: cannot move file.", Logger::WARN);
				$instance->delete();
				return NULL;
			}
		}
		return $instance;
	}

	protected function afterDelete() {
		if ($this->file_exists())
			@unlink($this->getFileName());
	}
	
	public function remove() {
		static::log("Remove file " . $this->log_ident(), Logger::INFO);
		if ($this->removed || !@$this->optionsOf("keep_files"))
			return $this->delete();
		if (!@$this->optionsOf("prefixes"))
			return FALSE;
		$pfx = $this->optionsOf("prefixes");
		if (!forward_static_call(array(get_called_class(), "mkdir"),$this->getDirectoryPath("removed"), 0777, TRUE)) {
			static::log("Error: cannot create directory.", Logger::WARN);
			return FALSE;
		}
		if (!forward_static_call(array(get_called_class(), "rename"), $this->getFilePath(), $this->getFilePath("removed"))) {
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
		if (!@self::classOptionsOf("prefixes"))
			return self::removeUnreferencedFiles();
		$pfx = self::classOptionsOf("prefixes");
		$unrefpfx = @$pfx["unref"];
		if (!@$unrefpfx)
			return self::removeUnreferencedFiles();
		FileUtils::delete_tree(self::classOptionsOf("directory") . $unrefpfx, TRUE);
	}
	
	// Identifies unreferenced files. If unref prefix is available, they are moved. Otherwise, they are deleted.
	public static function removeUnreferencedFiles() {
		if (!@self::classOptionsOf("prefixes"))
			return self::removeUnreferencedFilesRec(self::classOptionsOf("directory"), "");
		$pfx = self::classOptionsOf("prefixes");
		if (@$pfx["default"])
			return self::removeUnreferencedFilesRec(self::classOptionsOf("directory") . $pfx["default"], "");
		if (@$pfx["removed"])
			return self::removeUnreferencedFilesRec(self::classOptionsOf("directory") . $pfx["removed"], "");
	}
	
	private static function removeUnreferencedFilesRec($base, $sub) {
		if ($sub != "" && is_file($base . "/" . $sub)) {
			$ident = str_replace("/", "", $sub);
			if (@self::findBy(array("identifier" => $ident)))
				return;
			$pfx = self::classOptionsOf("prefixes");
			if (@$pfx && @$pfx["unref"]) {
				$move_base = self::classOptionsOf("directory") . $pfx["unref"];
				@forward_static_call(array(get_called_class(), "mkdir"), FileUtils::pathOf($move_base . "/" . $sub));
				@forward_static_call(array(get_called_class(), "rename"), $base . "/" . $sub, $move_base . "/" . $sub);
			} else {
				@unlink($base . "/" . $sub);
			}
		} else {
			if (@$handle = opendir($sub == "" ? $base : ($base . "/" . $sub))) {
			    while (false !== ($entry = readdir($handle)))
			        if ($entry != "." && $entry != "..")
						self::removeUnreferencedFilesRec($base, $sub == "" ? $entry : ($sub . "/" . $entry));
			    closedir($handle);
			}
		}
	}
	
	public function fileExists() {
		return file_exists($this->getFileName());
	}
	
	protected function createFileSystem() {
		return FileSystem::singleton();
	}
	
	private $fileSystem = NULL;
	
	protected function fileSystem() {
		if ($this->fileSystem === NULL)
			$this->fileSystem = $this->createFileSystem();
		return $this->fileSystem;
	} 
	
	public function materialize() {
		return static::fileSystem()->getFile($this->getFileName());
	}

}