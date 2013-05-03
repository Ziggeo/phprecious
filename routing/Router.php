<?php

require_once(dirname(__FILE__) . "/../logging/Logger.php");
require_once(dirname(__FILE__) . "/../support/web/Requests.php");

class Router {
	
	public $controller_path = "";
	public $fullpath_base = "";
	
	function __construct($fullpath_base = "", $controller_path = "") {
		$this->fullpath_base = $fullpath_base;
		$this->controller_path = $controller_path;
	}

	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("router");
			else
				$PERFMON->leave("router");
		}
	}

	protected static function log($level, $s) {
		global $LOGGER;
		if (@$LOGGER)
			$LOGGER->message("framework.router", $level, $s);
	}

	private $routes;
	private $metaRoutes;
	private $currentController;
	private $currentAction;
	
	public function addRoute($method, $uri, $controller_action, $options = array()) {
		$entry = array(
			"method" => $method,
			"uri" => $uri,
			"controller_action" => $controller_action,
			"direct" => FALSE,
			"conditions" => array()
		);
		if (@$options["direct"])
			$entry["direct"] = TRUE;
		if (@$options["conditions"])
			$entry["conditions"] = $options["conditions"];
		if (@$options["path"])
			$entry["path"] = $options["path"];
		$this->routes[] = $entry;
		if (@$options["path"])
			$this->paths[$options["path"]] = $entry; 
	}
	
	public function addMetaRoute($key, $controller_action) {
		$this->metaRoutes[$key] = $controller_action;
	}
	
	public function dispatchMetaRoute($key) {
		$this->dispatchControllerAction($this->metaRoutes[$key]); 		
	}
	
	public function dispatchRoute() {
		self::perfmon(true);
		if (func_num_args() == 2) {
			$method = func_get_arg(0);
			$uri = func_get_arg(1);
		}
		elseif (func_num_args() == 1) {
			$method = "GET";
			$uri = func_get_arg(0);
		}
		else {
			$method = Requests::getMethod();
			$uri = Requests::getPath();
		}
		self::log(Logger::INFO_2, "Dispatch Route: " . $method . " " . $uri);
		$uri = trim($uri, '/');
		$controller_action = $this->metaRoutes["404"];
		$args = array();
		$direct = FALSE;
		foreach ($this->routes as $route) {
			if ((($route["method"] == "*") || ($route["method"] == $method)) &&
			    (preg_match("/^" . $route["uri"] . "$/", $uri, $matches))) {
			    $conditions = $route["conditions"];
			    $success = true;
				while ($success && $condition = array_shift($conditions))
					$success = $condition();
				if ($success) {
					$controller_action = $route["controller_action"];
					$direct = $route["direct"];
					array_shift($matches);
					$args = $matches;
					break;
				}
			}
		}
		self::perfmon(false);
		$this->dispatchControllerAction($controller_action, $args, $direct);
	}
		
	public function dispatchControllerAction($controller_action, $args = array(), $direct = FALSE) {
		self::perfmon(true);
		self::log(Logger::INFO_2, "Dispatch Action: " . $controller_action);
		@list($controller_file, $action_function) = explode("#", $controller_action);
		$this->currentController = $controller_file;
		$this->currentAction = $action_function;
		if ($direct) {
			include($controller_file . ".php");
			self::perfmon(false);
			if ($action_function)
				call_user_func_array($action_function, $args);
		}
		else {
			$cls = $controller_file . "Controller";
			include($this->controller_path . "/" . $cls . ".php");
			$i = strrchr($cls, "/");
			$clsname = $i ? substr($i, 1) : $cls;
			self::perfmon(false);
			$controller = new $clsname();
			$controller->dispatch($action_function, $args);
		}
	}
	
	public function path($path) {
		self::perfmon(true);
		$route = $this->paths[$path];
		$uri = "/" . $route["uri"];
		$uri = str_replace('\/', "/", $uri);
		$args = func_get_args();
		array_shift($args);
		$in_uri_args = substr_count($uri, "(");
		while (count($args) > 0 && $in_uri_args > 0) {
			$tmp = explode("(", $uri, 2);
			$head = $tmp[0];
			$tmp2 = explode(")", $tmp[1], 2);
			$tail = $tmp2[1];
			$uri = $head . array_shift($args) . $tail;
			$in_uri_args--;
		}
		$params = count($args) > 0 ? $args[0] : array();
		if (($route["method"] != "GET") && ($route["method"] != "POST") && ($route["method"] != "*"))
			$params["_method"] = $route["method"];
		self::perfmon(false);
		return Requests::buildPath($uri, $params); 
	}
	
	public function fullpath($path) {
		$subpath = call_user_method_array("path", $this, func_get_args());
		return $this->fullpath_base . $subpath;
	}
	
	public function redirect($uri) {
        header("Location: " . $uri);		
	}
		
	public function getCurrentController() {
		return $this->currentController;
	}
	
	public function getCurrentAction() {
		return $this->currentAction;
	}
	
}