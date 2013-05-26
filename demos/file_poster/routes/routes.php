<?php

	$this->router()->addMetaRoute("404", 'Meta#not_found');

	$this->router()->addRoute("GET",     "",              "Root#index");
	$this->router()->addRoute("GET",     "uploads\/new",  "Uploads#new_upload");
	$this->router()->addRoute("POST",    "uploads\/new",  "Uploads#create_upload");
	$this->router()->addRoute("DELETE",  "uploads\/(.+)", "Uploads#delete_upload");
