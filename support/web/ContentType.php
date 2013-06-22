<?php

require_once(dirname(__FILE__) . "/../files/FileUtils.php");

Class ContentType {
	
	public static function byFileName($filename) {
		$ext = FileUtils::extensionOf($filename);
		if (!@$ext)
			return "application/octet-stream";
		if ($ext == "jpg" || $ext == "jpeg")
			return "image/jpeg";
		elseif ($ext == "png")
			return "image/png";
		return "application/" . $ext;
	}
			
}
