<?php

require_once(dirname(__FILE__) . "/../files/FileUtils.php");

Class ContentType {
	
	public static function byExtension($ext, $download = FALSE) {
		if (!@$ext)
			return "application/octet-stream";
		switch($ext) {
			case 'exe': return "application/octet-stream";
			case 'zip': return "application/zip";
			case 'mp3': return "audio/mpeg";
			case 'mpg': return "video/mpeg";
			case 'avi': return "video/x-msvideo";
			case 'jpg': return "image/jpeg";
			case 'jpeg': return "image/jpeg";
			case 'png': return "image/png";
			case 'mp4': return "video/mp4";
			default: return $download ? 'application/force-download' : "application/" . $ext;
		}
	}
	
	public static function byFileName($filename, $download = FALSE) {
		return self::byExtension(FileUtils::extensionOf($filename));
	}
			
}
