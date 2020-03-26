<?php

require_once(dirname(__FILE__) . "/../../../application/Application.php");

Class App extends Application {
	
	function __construct() {
		parent::__construct();
		$this->initializeFolders();
		$this->initializeRequired();
		$this->registerInitializers();
	}
	
	protected function initializeFolders() {
		$this->setFolder("root", dirname(__FILE__) . "/..");
		$this->setFolder("phprecious", "{root}/../..");
		$this->setFolder("logs", "{root}/logs");
		$this->setFolder("data", "{root}/data");
		$this->setFolder("views", "{root}/views");
		$this->setFolder("views-layout", "{views}/layout");
	}
	
	protected function initializeRequired() {
		$this->require_class_path("{phprecious}/support/web");
		$this->require_class_path("{phprecious}/logging");
		$this->require_class_path("{phprecious}/modelling/models");
		$this->require_class_path("{phprecious}/modelling/associations");
		$this->require_class_path("{phprecious}/validating");
		$this->require_class_path("{phprecious}/rendering");
		$this->require_class_path("{phprecious}/database");
		$this->require_class_path("{phprecious}/database/mongo");
		$this->require_class_path("{phprecious}/controlling");
		$this->require_class_path("{phprecious}/routing");
		$this->require_class_path("{root}/app/initializers");
		$this->require_class_path("{root}/models");
		$this->require_class_path("{root}/controllers");
	}
	
	protected function registerInitializers() {
		$this->addInitializer(new AppConfig());
	}
	
	private $database = NULL;
	
	public function database() {
		if (!@$this->database) {
			$dbclsname = $this->config("database.type") . "Database";
			$this->database = new $dbclsname($this->config("database.name"));
		}
		return $this->database;
	}
			
	private $logger = NULL;
	
	public function logger() {
		if (!@$this->logger) {
			$this->logger = new Logger();
			$this->logger->registerConsumer(
				new LoggerConsumerFilter(
					new LoggerFileConsumer($this->config("logger.directory") . "/" . $this->config("logger.name") . "." . date("W")),
					array("level" => 4)
				)
			);
		}
		if (func_num_args() == 0)
			return $this->logger;
		$this->logger->message(func_get_arg(0), func_get_arg(1), func_get_arg(2));
	}
		

	public function run() {
		try {
			$this->runApplication();
		} catch (Exception $e) {
		    $this->logger(array("exception"), Logger::ERROR, $e->__toString());
			$this->handleException($e);
		}
	}
	
	private $router = NULL;
	
	public function router() {
		if (!@$this->router) {
			$this->router = new Router(
				$this->config("server.protocol") . "://" . $this->config("server.domain"),
				dirname(__FILE__) . "/../controllers",
				array("logger" => $this->logger(), "relative_paths" => TRUE)
			);
			include_once(dirname(__FILE__) . "/../routes/routes.php");
		}
		return $this->router;
	}
	
	
	private $head = array();

	function head($key) {
		if (func_num_args() == 1)
			return @$this->head[$key];
		else
			$this->head[$key] = func_get_arg(1);
	}
	
	function session() {
		return Session::getSession();
	}
	
	private $renderer = NULL;

	function renderer() {
		if (!@$this->renderer)
			$this->renderer = new Renderer($this->resolvePath("{views}"), $this->resolvePath("{views-layout}"));
		return $this->renderer;
	}
	

	protected function runApplication() {
		$this->logger("webserver", Logger::INFO, "Request: " . Requests::getMethod() . " " . Requests::getPath());
		$this->router()->dispatchRoute();
	}
	
	protected function handleException($e) {
	    @ob_end_clean();
		// if $file got set then we were too late	
	    headers_sent($file);
		HttpHeader::setHeader('HTTP/1.0 500 Internal Server Error');
	    echo ("Error: " . $e->__toString());
	}
		
}