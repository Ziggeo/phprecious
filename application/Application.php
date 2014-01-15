<?php


require_once(dirname(__FILE__) . "/../support/required/Required.php");
require_once(dirname(__FILE__) . "/../support/strings/StringTable.php");
        


Class Application {
    
    private $folders = array();
    private $config;
    private $tags = array();
    private $initializers = array();
    public $error_messages = array();

    public function error_handler($severity, $message, $filename, $lineno) {
        $this->error_messages[] = array("severity" => $severity, "message" => $message, "filename" => $filename, "lineno" => $lineno);
    }
    
    public function fatal_handler() {
        $error = error_get_last();
    
        if ($error !== NULL)
            $this->fatal_error($error["type"], $error["file"], $error["line"], $error["message"]);
    }       
    
    protected function fatal_error($type, $file, $line, $message) {}
    
    function __construct() {
        set_error_handler(array($this, "error_handler"));
        register_shutdown_function(array($this, "fatal_handler"));
        $this->config = new StringTable();
    }
    
    public function initialize() {
        $this->processInitializers();
    }
    
    public function getFolder($key) {
        return isset($this->folders[$key]) ? $this->folders[$key] : NULL;
    }
    
    public function setFolder($key, $value) {
        $this->folders[$key] = $this->resolvePath($value);
    }
    
    public function resolvePath($path) {
        $self = $this;
        return preg_replace_callback(
            "/\{(.+)\}/", function ($matches) use ($self) {
                return $self->getFolder($matches[1]);
            }, $path
        );
    }
    
    public function require_file_path($path) {
        Required::add_file_path($this->resolvePath($path));
    }
    
    public function require_class_path($path) {
        Required::add_class_path($this->resolvePath($path));
    }
    
    public function require_class_paths($path) {
        Required::add_class_paths($this->resolvePath($path));
    }
    
    public function addTag($tag) {
        $this->tags[$tag] = TRUE;
    }
    
    public function removeTag($tag) {
        unset($this->tags[$tag]);
    }

    public function hasTag($tag) {
        return isset($this->tags[$tag]);
    }
    
    public function config($key = "") {
        return $this->config->get($key);
    }
    
    public function configTable() {
        return $this->config->table();
    }
    
    public function setConfig($key = "", $value = NULL, $overwrite = TRUE) {
        if ($overwrite || !@$this->config->exists($key))
            $this->config->set($key, $value);
    }
    
    public function hasConfig($key) {
        return $this->config->exists($key);
    }
    
    private function touchInitializer($ident) {
        if (!isset($this->initializers[$ident])) {
            $this->initializers[$ident] = array(
                "initializer" => NULL,
                "processed" => FALSE,
                "active" => FALSE,
                "before" => array(),
                "after" => array(),
            );
        }
    }
    
    public function addInitializer($initializer) {
        $ident = $initializer->ident();
        $this->touchInitializer($ident);
        $arr = &$this->initializers[$ident];
        if (@$arr["initializer"])
            return FALSE;
        $arr["initializer"] = $initializer;
        $deactivate = FALSE;
        foreach ($initializer->before() as $before) {
            $this->touchInitializer($before);
            if ($this->initializers[$before]["processed"])
                $deactivate = TRUE;
            else {
                $arr["before"][$before] = TRUE;
                $this->initializers[$before]["after"][$ident] = TRUE;
            }
        }
        foreach ($initializer->after() as $after) {
            $this->touchInitializer($after);
            if ($this->initializers[$after]["processed"]) {
                if (!$this->initializers[$after]["active"])
                    $deactivate = TRUE;
            } else {
                $arr["after"][$after] = TRUE;
                $this->initializers[$after]["before"][$ident] = TRUE;
            }
        }
        if ($deactivate)
            $this->processInitializer($initializer, FALSE);
    }
    
    private function processInitializer($initializer, $active = TRUE) {
        $ident = $initializer->ident();
        $arr = &$this->initializers[$ident];
        $arr["processed"] = TRUE;
        $arr["active"] = $active;
        if ($active) {
            $initializer->execute($this);
            foreach ($arr["before"] as $key=>$value)
                unset($this->initializers[$key]["after"][$ident]);
        }
    }
    
    private function processInitializers() {
        $changed = TRUE;
        while ($changed) {
            $changed = FALSE;
            foreach ($this->initializers as $ident=>$initializer) {
                if (!$initializer["processed"] && count($initializer["after"]) == 0) {
                    $this->processInitializer($initializer["initializer"], $initializer["initializer"]->applicable($this));
                    $changed = TRUE;
                }
            }           
        }
    }
    
}



Class ApplicationInitializer {
    
    private static $anonymous_ident_id = 0;
    protected $ident = NULL;
    protected $required_tags = array();
    protected $forbidden_tags = array();
    protected $before = array();
    protected $after = array();
    private $execute_func = NULL;
    
    function __construct($options = array()) {
        if (@$options["ident"])
            $this->ident = $options["ident"];
        if (@$options["required_tags"])
            $this->required_tags = $options["required_tags"];
        if (@$options["forbidden_tags"])
            $this->forbidden_tags = $options["forbidden_tags"];
        if (@$options["before"])
            $this->before = $options["before"];
        if (@$options["after"])
            $this->after = $options["after"];
        if (@$options["execute"])
            $this->execute_func = $options["execute"];
    }
    
    public function ident() {
        if (!@$this->ident)
            $this->ident = "anonymous_" . self::$anonymous_ident_id++;
        return $this->ident;
    }
    
    public function required_tags() {
        return $this->required_tags;
    }
    
    public function forbidden_tags() {
        return $this->forbidden_tags;
    }

    public function before() {
        return $this->before;
    }

    public function after() {
        return $this->after;
    }
    
    public function applicable($application) {
        foreach ($this->required_tags() as $tag)
            if (!$application->hasTag($tag))
                return FALSE;
        foreach ($this->forbidden_tags() as $tag)
            if ($application->hasTag($tag))
                return FALSE;
        return TRUE;
    }

    public function execute($application) {
        if (@$this->execute_func) {
            $func = $this->execute_func;
            $func($application);
        }
    }
    
    
}
