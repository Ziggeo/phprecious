<?php

Class UploadsController extends Controller {
	
	function create() {
		$file = UploadedFile::createByUpload(@$_FILES["file"], APP()->session()->id());
		APP()->router()->redirect("/");
	}
	
	function read($id) {
		$file = UploadedFile::findById($id);
		if (!@$file)
			return;
		HttpHeader::setHeader('Content-type: ' . $file->getContentType());
		ob_clean();
		flush();
	    readfile($file->getIdentPath());
	}

	function destroy($id) {
		$file = UploadedFile::findById($id);
		if (!@$file || $file->session()->id() != APP()->session()->id())
			return;
		$file->delete();
		APP()->router()->redirect("/");
	}

}