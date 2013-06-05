<?php

// TODO: Complete Source

Class FileModel extends DatabaseModel {
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["removed"] = array("type" => "boolean");
		$attrs["prefix_ident"] = array("type" => "string");
		$attrs["identifier"] = array("type" => "string", "validate" => array(new PresentValidator()));
		$attrs["file_type"] = array("type" => "string");
		$attrs["file_size"] = array("type" => "integer");
		$attrs["original_file_name"] = array("type" => "string");
		return $attrs;
	}	
	
	protected static function initializeOptions() {
		$opts = parent::initializeOptions();
		$opts["directory"] = "";
		$opts["identifier_length"] = 16;
		$opts["split_identifier"] = 2;
		$opts["keep_files"] = TRUE;
		$opts["prefixes"] = array(
			"active" => "active/",
			"removed" => "removed/",
			"unref" => "unref/",
		);
		return $opts;
	}
	
	public function getPrefix() {
		$pfx = self::optionsOf("prefixes");
		return $pfx[$this->prefix_ident];
	}
	
	public function getIdentifierName() {
		$split_identifier = @self::optionsOf("split_identifier");
		if (!@$split_identifier)
			return $this->identifier;		
		return join("/", str_split($this->identifier, $split_identifier));
	}
	
	public function getFileName() {
		return self::optionsOf("directory") . "/" . $this->getPrefix() . $this->getIdentifierName();
	}

}