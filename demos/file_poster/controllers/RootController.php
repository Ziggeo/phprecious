<?php

Class RootController extends Controller {
	
	function index() {
		APP()->head("title", "");
		APP()->renderer()->render("root/index", array(
			"files" => UploadedFile::all()
		));
	}
	
}