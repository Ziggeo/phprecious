<?php

	$this->router()->addMetaRoute("404", 'Meta#not_found');

	$this->router()->addRoute("GET",     "",              "Root#index");
	$this->router()->addRoute("GET",     "uploads\/(.+)", "Uploads#read",    array("path" => "read_upload_path"));
	$this->router()->addRoute("POST",    "uploads\/new",  "Uploads#create",  array("path" => "create_upload_path"));
	$this->router()->addRoute("DELETE",  "uploads\/(.+)", "Uploads#destroy", array("path" => "destroy_upload_path"));
	