<?php

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

}
