<?php


Class FileStreamer {
	
	/*
	 * Options
	 *   - download (boolean): false
	 *   - download_name (string): null
	 *   - noresume (boolean): false
	 */
	public static function streamFile($file, $options = array()) {
		if (!is_file($file))
			return FALSE;
		
	    $fileinfo = pathinfo($file);
		$filename = $fileinfo['basename'];
		if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			$filename = preg_replace('/\./', '%2e', $fileinfo['basename'], substr_count($fileinfo['basename'], '.') - 1);
    
		$range = "";
	    if (!@$options["noresume"] && isset($_SERVER['HTTP_RANGE'])) {
	        list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);	
	        if ($size_unit == 'bytes')
				list($range, $extra_ranges) = explode(',', $range_orig, 2);
		}
	    list($seek_start, $seek_end) = explode('-', $range, 2);
		$size = filesize($file);
	    $seek_end = empty($seek_end) ? $size - 1 : min(abs(intval($seek_end)), $size - 1);
	    $seek_start = empty($seek_start) || $seek_end < abs(intval($seek_start)) ? 0 : max(abs(intval($seek_start)), 0);

	    if (!@$options["noresume"]) {
	        if ($seek_start > 0 || $seek_end < $size - 1)
	            header('HTTP/1.1 206 Partial Content');
	        header('Accept-Ranges: bytes');
	        header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
	    }
		
		$content_type = ContentType::byFileName($filename, @$options["download"] ? TRUE : FALSE);
	    header('Content-Type: ' . $content_type);
		if (@$options["download"])
	    	header('Content-Disposition: attachment; filename="' . (@$options["download_name"] ? $options["download_name"] : $filename) . '"');
	    header('Content-Length: ' . ($seek_end - $seek_start + 1) );

	    $fp = fopen($file, 'rb');
	    fseek($fp, $seek_start);
	    while(!feof($fp)) {
	        //reset time limit for big files
	        set_time_limit(0);
	        print(fread($fp, 1024*8));
	        flush();
	        ob_flush();
	    }
	    fclose($fp);
		return true;
	}
	
}