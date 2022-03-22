<?php

require_once(dirname(__FILE__) . "/../strings/StringUtils.php");

class FileUtils {
	
	public static function enumerate($folder, $expand = FALSE) {
		$result = array();
		if (@$handle = opendir($folder)) {
		    while (false !== ($entry = readdir($handle)))
		        if ($entry != "." && $entry != "..") 
					$result[] = $expand ? $folder . "/" . $entry : $entry;
		    closedir($handle);
		}
		return $result;
	}

	public static function enumerate_dirs($folder, $expand = FALSE) {
		$result = array();
		if (@$handle = opendir($folder)) {
		    while (false !== ($entry = readdir($handle)))
		        if ($entry != "." && $entry != ".." && is_dir($folder . "/" . $entry)) 
					$result[] = $expand ? $folder . "/" . $entry : $entry;
		    closedir($handle);
		}
		return $result;
	}
	
	public static function enumerate_files($folder, $expand = FALSE) {
		$result = array();
		if (@$handle = opendir($folder)) {
		    while (false !== ($entry = readdir($handle)))
		        if ($entry != "." && $entry != ".." && !is_dir($folder . "/" . $entry)) 
					$result[] = $expand ? $folder . "/" . $entry : $entry;
		    closedir($handle);
		}
		return $result;
	}

	public static function extensionOf($filename) {
		$ext_pos = strrpos($filename, ".");
		return $ext_pos === FALSE ? NULL : strtolower(substr($filename, $ext_pos + 1));
	}
	
	public static function pathOf($filename) {
		$slash = strrpos($filename, "/");
		return $slash === FALSE ? NULL : substr($filename, 0, $slash);
	}

	public static function remove_empty_directory_chain($path, $root = "") {
		if ($root == $path)
			return TRUE;
		if ($root != "") {
			$root .= "/";
			if (!StringUtils::startsWith($path, $root))
				return FALSE;
			$path = substr($path, strlen($root));
		}
		while ($path != "") {
			if (!self::is_empty_directory($root . $path))
				return TRUE;
			if (!@rmdir($root . $path))
				return FALSE;
			$slash = strrpos($path, "/");
			$path = $slash === FALSE ? "" : substr($path, 0, $slash);
		}
		return TRUE;		
	}
	
	public static function delete_tree($base, $files_only = FALSE) {
		if (is_file($base))
			@unlink($base);
		elseif (is_dir($base) && @$handle = opendir($base)) {
		    while (false !== ($entry = readdir($handle)))
		        if ($entry != "." && $entry != "..")
		        	self::delete_tree($base . "/" . $entry, $files_only);
		    closedir($handle);
			if (!$files_only)
				rmdir($base);
		}
	}
	
	public static function safeFileName($filename) {
		return str_replace(" ", "", str_replace("/", "", str_replace("..", "", $filename)));
	}
	
	public static function move_uploaded_file($tmp_name, $target, $class = "FileModel") {
		return forward_static_call(array($class, "move_uploaded_file"), $tmp_name, $target) || forward_static_call(array($class, "rename"), $tmp_name, $target);
	}
	
}