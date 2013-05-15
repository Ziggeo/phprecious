<?php

// Not complete
Class Application {
	
	private $config;
	private $environment_tags = array();
	
	function __construct() {
		$this->config = new StringTable();
		/*
		$this->loadRequirements();
		$this->determineEnvironment();
		$this->loadConfiguration();
		$this->setupConfiguration();
		 */
	}
	
	public function config($key = "") {
		return $this->config->get($key);
	}
	
	public function setConfig($key = "", $value = NULL) {
		$this->config->set($key, $value);
	}
	
	protected function addEnvironmentTag($tag) {
		$this->environment_tags[$tag] = TRUE;
	}
	
	protected function hasEnvironmentTag($tag) {
		return @$this->environment_tags[$tag];
	}
	
	protected function getEnvironmentTags() {
		return array_keys($this->environment_tags);
	}
	
	protected function addConfiguration($configuration, $options) {
		// TODO.
	}
	
/*	
	protected function loadRequirements() {
		// TODO
	}
	
	protected function determineEnvironment() {
		// TODO
	}
	
	protected function loadConfiguration() {
		// TODO
	}
	
	protected function setupConfiguration() {
		// TODO
	}
	
	public function execute() {
		// TODO
	}
	*/
}

