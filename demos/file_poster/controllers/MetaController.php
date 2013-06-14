<?php

Class MetaController extends Controller {
	
	function not_found() {
		APP()->head("title", "Not Found");
		APP()->renderer()->render("meta/not_found");
	}
	
}