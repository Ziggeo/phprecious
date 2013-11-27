<?php

Class Assets {
	
	private $table;
	
	public function __construct($table) {
		$this->table = $table;
	}
	
	public function get() {
		$args = func_get_args();
		$current = $this->table;
		$param = @$current["param"];
		$root = @$current["root"];
		$path = @$current["path"];
		foreach ($args as $domain) {
			if (isset($current["domains"]) && isset($current["domains"][$domain])) {
				$current = $current["domains"][$domain];
				if (isset($current["root"]))
					$root = @$current["root"];
				if (isset($current["param"]))
					$param = @$current["param"];
				if (isset($current["path"]))
					$path = $path . "/" . $current["path"];
				else
					$path = $path . "/" . $domain;
			} 
			else {
				$current = NULL;
				$path = $path . "/" . $domain;
			}
		} 
		return (@$root ? $root . $path : $path) . "?" . (@$param ? $param : "");
	}
	
}
