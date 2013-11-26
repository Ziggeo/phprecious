<?php

require_once(dirname(__FILE__) . "/../files/FileUtils.php");

Class Required {
	
	private static $class_paths = array();
	private static $file_paths = array();
	
	public static function add_class_path($path) {
		self::$class_paths[] = $path;
	}
	
	public static function add_class_paths($root) {
		self::add_class_path($root);
		foreach (FileUtils::enumerate_dirs($root, TRUE) as $item)
			self::add_class_paths($item);
	}	
	
	public static function add_file_path($path) {
		self::$file_paths[] = $path;
	}
	
	public static function require_file($file_name) {
		if (is_file($file_name)) 
			require_once $file_name;
		elseif (is_file(dirname(__FILE__) . "/" . $file_name))
			require_once dirname(__FILE__) . "/" . $file_name;
		else
			foreach (Required::$file_paths as $file_dir) {
				$filename = $file_dir . "/" . $file_name;
				if (is_file($filename))
					require_once $filename;
			}
	}
	
	public static function required_class($class_name) {
		foreach (Required::$class_paths as $class_dir) {
			$filename = $class_dir . "/" . $class_name . ".php";
			if (is_file($filename))
				require_once $filename;
		}
	}

}

spl_autoload_register("Required::required_class");
