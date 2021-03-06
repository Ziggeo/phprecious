<?php

require_once(dirname(__FILE__) . "/../logging/Logger.php");
require_once(dirname(__FILE__) . "/../support/web/Requests.php");

class Router {

    public $controller_path = "";
    public $fullpath_base = "";
    public $functions = array();
    public $post_functions = array();
    private $perfmon = NULL;
    private $logger = NULL;
    private $relative_paths = FALSE;
    private $paths = array();
    private $virtual_paths = array();

    function __construct($fullpath_base = "", $controller_path = "", $options = array()) {
        $this->fullpath_base = $fullpath_base;
        $this->controller_path = $controller_path;
        if (isset($options["perfmon"]))
            $this->perfmon = $options["perfmon"];
        if (isset($options["logger"]))
            $this->logger = $options["logger"];
        if (isset($options["functions"]))
            $this->functions = $options["functions"];
        if (isset($options["post_functions"]))
            $this->post_functions = $options["post_functions"];
        $this->relative_paths = isset($options["relative_paths"]) ? TRUE : FALSE;
    }

    protected function perfmon($enter) {
        global $PERFMON;
        $pf = @$this->perfmon ? $this->perfmon : $PERFMON;
        if (@$pf) {
            if ($enter)
                $pf->enter("router");
            else
                $pf->leave("router");
        }
    }

    protected function log($level, $s) {
        global $LOGGER;
        $lg = @$this->logger ? $this->logger : $LOGGER;
        if (@$lg)
            $lg->message("router", $level, $s);
    }

    private $routes;
    private $metaRoutes;
    private $currentController;

    /**
     * The controller responsible for handling the last Http request.
     * Necessary for accessing the status_code and data the controller
     * stores from its last Http request when testing.
     */
    private $lastControllerInstance;

    private $currentAction;

    public function addRoute($method, $uri, $controller_action, $options = array()) {
        $entry = array(
            "method" => $method,
            "uri" => $uri,
            "controller_action" => $controller_action,
            "conditions" => array(),
            "post_conditions" => array(),
            "arguments" => array(),
            "sitemap" => FALSE
        );
        if (isset($options["force_redirect"]))
            $entry["force_redirect"] = $options["force_redirect"];
        if (isset($options["redirect_code"]))
            $entry["redirect_code"] = $options["redirect_code"];
        if (isset($options["forward_redirect"]))
            $entry["forward_redirect"] = $options["forward_redirect"];
        if (isset($options["fullpath_base"]))
            $entry["fullpath_base"] = $options["fullpath_base"];
        if (isset($options["conditions"]))
            $entry["conditions"] = $options["conditions"];
        if (isset($options["post_conditions"]))
            $entry["post_conditions"] = $options["post_conditions"];
        if (isset($options["path"]))
            $entry["path"] = $options["path"];
        if (isset($options["arguments"]))
            $entry["arguments"] = $options["arguments"];
        if (isset($options["sitemap"]))
            $entry["sitemap"] = $options["sitemap"];
        if (isset($options["preargs"]))
            $entry["preargs"] = $options["preargs"];
		if (isset($options["log_route"]))
			$entry["log_route"] = $options["log_route"];
        $this->routes[] = $entry;
        if (isset($options["path"]))
            $this->paths[$options["path"]] = $entry;
    }

    public function getSitemap() {
        $result = array();
        foreach ($this->routes as $entry) {
            if (@$entry["sitemap"])
                $result[] = $this->fullpath($entry);
        }
        return $result;
    }

    public function formatSitemap() {
        $s = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<urlset' . "\n" .
            '        xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n" .
            '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n" .
            '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
        $urls = $this->getSitemap();
        foreach ($urls as $url) {
            $url = preg_replace("/[^A-Za-z0-9\/:.-]/", '', $url);
            $s .= "  <url>\n" .
                "    <loc>" . $url . "</loc>\n" .
                "  </url>\n";
        }
        $s .= "</urlset>\n";
        return $s;
    }

    public function addMetaRoute($key, $controller_action) {
        $this->metaRoutes[$key] = $controller_action;
    }

    public function dispatchMetaRoute($key) {
        $this->dispatchControllerAction($this->metaRoutes[$key]);
    }

    public function dispatchRoute() {
        $this->perfmon(true);
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
        $this->log(Logger::INFO_3, "Dispatch Route: " . $method . " " . $uri);
        if ($method === "DESTROY")
            $method = "DELETE";
        $uri = trim($uri, '/');
        $controller_action = $this->metaRoutes["404"];
        $args = array();
        $arguments = array();
        $post_conditions = array();
		$log_route = TRUE; //By default we're logging the route
        foreach ($this->routes as $route) {
            if ((($route["method"] == "*") || ($route["method"] == $method)) &&
                (preg_match("/^" . $route["uri"] . "$/", $uri, $matches))) {
                $conditions = $route["conditions"];
                $success = true;
                while ($success && $condition = array_shift($conditions))
                    $success = isset($this->functions[$condition]) ? $this->functions[$condition]() : $condition();
                if ($success) {
                    if (isset($route["force_redirect"]) && (isset($this->functions[$route["force_redirect"]]) ? $this->functions[$route["force_redirect"]]() : $route["force_redirect"]())) {
                        $redir = (isset($route["fullpath_base"]) ? $route["fullpath_base"] : $this->fullpath_base) . "/" . $uri;
                        $this->redirect($redir);
                        $this->log(Logger::INFO_2, "Redirect to: " . $redir);
                        $this->perfmon(false);
                        return;
                    }
                    if (isset($route["forward_redirect"])) {
                        $redir = $route["forward_redirect"];
                        $this->redirect($redir, @$route["redirect_code"] ? $route["redirect_code"] : 301);
                        $this->log(Logger::INFO_2, "Redirect to: " . $redir);
                        return;
                    }
					if (isset($route["log_route"]))
						$log_route = $route["log_route"];
                    $controller_action = $route["controller_action"];
                    $arguments = $route["arguments"];
                    array_shift($matches);
                    $args = $matches;
                    if (@$route["preargs"])
                        $args = array_merge($route["preargs"], $args);
                    if (@$route["post_conditions"])
                        $post_conditions = $route["post_conditions"];
                    break;
                }
            }
        }
        $this->perfmon(false);
        $this->dispatchControllerAction($controller_action, $args, $arguments, $log_route, $post_conditions);
    }

    public function dispatchControllerAction($controller_action, $args = array(), $arguments = array(), $log_route = TRUE, $post_conditions = array()) {
        $this->perfmon(true);
        if ($log_route) //Used to prevent route logging on demand.
        	$this->log(Logger::INFO_2, "Dispatch Action: " . $controller_action);
        krsort($arguments);
        foreach ($arguments as $argkey=>$data) {
            $item = @$data["remove"] ? ArrayUtils::removeByIndex($args, $argkey) : $args[$argkey];
            if (@$data["write"])
                $data["write"]($item);
        }
        @list($controller_file, $action_function) = explode("#", $controller_action);
        $this->currentController = $controller_file;
        $this->currentAction = $action_function;

        $cls = $controller_file . "Controller";
        if (!class_exists($cls))
            include_once($this->controller_path . "/" . $cls . ".php");
        $i = strrchr($cls, "/");
        $clsname = $i ? substr($i, 1) : $cls;
        $this->perfmon(false);
        $controller = new $clsname();
        $success = true;
        while ($success && $condition = array_shift($post_conditions))
            $success = isset($this->post_functions[$condition]) ? $this->post_functions[$condition]($controller) : $condition();
        if (!$success) {
            $this->dispatchMetaRoute("404");
            return;
        }
        $this->lastControllerInstance = $controller;
        $controller->dispatch($action_function, $args);
    }

    public function path($path) {
        $this->perfmon(true);
        $route = $path;
        if (is_string($path)) {
            if (isset($this->virtual_paths[$path]))
                return $this->virtual_paths[$path]();
            $route = $this->paths[$path];
        }
        $uri = ($this->relative_paths ? "" : "/") . $route["uri"];
        $uri = str_replace('\/', "/", $uri);
        $uri = str_replace('\.', ".", $uri);
        $args = func_get_args();
        array_shift($args);
        $arguments = $route["arguments"];
        ksort($arguments);
        foreach ($arguments as $argkey=>$data) {
            if (@$data["read"])
                ArrayUtils::insert($args, $argkey, $data["read"]());
        }
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
        $this->perfmon(false);
        return Requests::buildPath($uri, $params);
    }

    public function fullpath($path) {
        $subpath = call_user_func_array(array($this, "path"), func_get_args());
        return $this->fullpath_base . $subpath;
    }

    public function redirect($uri, $statusCode = NULL) {
        if (@$statusCode !== NULL)
            HttpHeader::setHeader("Location: " . $uri, true, $statusCode);
        else
            HttpHeader::setHeader("Location: " . $uri);
    }

    public function getCurrentController() {
        return $this->currentController;
    }

    /**
     * `getLastControllerInstance` is a simple getter.
     * It is useful in testing for finding the last controller
     * handling an HTTP request, because that controller stores
     * information useful for testing.
     *
     * @return The last controller to which a call was dispatched.
     */
    public function getLastControllerInstance() {
        return $this->lastControllerInstance;
    }

    public function getCurrentAction() {
        return $this->currentAction;
    }

    public function addVirtualPath($path, $function) {
        $this->virtual_paths[$path] = $function;
    }

}
