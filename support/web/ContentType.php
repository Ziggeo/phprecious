<?php

Class ContentType {
	
	public static function byFileName($filename) {
		$ext_pos = strrpos($filename, ".");
		if ($ext_pos === FALSE)
			return "application/octet-stream";
		else {
			$ext = strtolower(substr($filename, $ext_pos + 1));
			if ($ext == "jpg" || $ext == "jpeg")
				return "image/jpeg";
			elseif ($ext == "png")
				return "image/png";
			return "application/" . $ext;
		}
	}
			
}
