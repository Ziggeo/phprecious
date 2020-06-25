<?php


require_once(dirname(__FILE__) . "/../strings/StringUtils.php");
require_once(dirname(__FILE__) . "/ContentType.php");


Class FileStreamerException extends Exception {}


Class FileStreamer {
	
	/*
	 * Parses the given http range and returns an associative array with
	 *  - start: start byte (inclusive)
	 *  - end: end byte (inclusive)
	 *  - bytes: number of bytes to be transfered
	 * 
	 * If size is given, return values are bounded by size. Returns NULL if no range given.
	 * 
	 */
    public static function parseHttpRangeValue($rangeValue, $size = NULL) {
        list($size_unit, $range_orig) = explode('=', $rangeValue, 2);
        if ($size_unit == 'bytes') {
            list($range, $extra_ranges) = explode(',', $range_orig, 2);
            list($seek_start, $seek_end) = explode('-', $range, 2);
            $seek_start = max(0, intval($seek_start));
            $seek_end = !!$seek_end ? max($seek_start, intval($seek_end)) : ($size-1);
            if (isset($size)) {
                $seek_start = min($seek_start, $size - 1);
                $seek_end = min($seek_end, $size - 1);
            }
            return array(
                "start" => $seek_start,
                "end" => $seek_end,
                "bytes" => $seek_end - $seek_start + 1
            );
        }
        return NULL;
    }

	public static function parseHttpRange($size = NULL) {
		return isset($_SERVER['HTTP_RANGE']) ? static::parseHttpRangeValue($_SERVER['HTTP_RANGE'], $size) : NULL;
	}
	
	
	
	/*
	 * Returns a browser friendly download name
	 * 
	 */
	 
	public static function getDownloadName($file) {
	    $fileinfo = pathinfo($file);
		if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			return preg_replace('/\./', '%2e', $fileinfo['basename'], substr_count($fileinfo['basename'], '.') - 1);
		return $fileinfo['basename'];
	}


	
	/*
	 * Options
	 *   - download (boolean, default false): Set to true if you want the browser to download the file instead of executing it
	 *   - download_name (string, default filename): Set to the filename that the browser suggests to use
	 *   - resume (boolean, default true): Allow resuming
	 *   - open_mode (string, default 'rb'): Open mode for fopen
	 *   - open_context (object, default null): Context for fopen
	 *   - content_type (string, default null): Content Type; can be either an extension or the actual content type
	 *   - block_size (int, default 8 KB): Block size to read and stream
	 *
	 */

	public static function streamFile($file, $options = array()) {
		if (!is_file($file))
			throw new FileStreamerException("String given is not a file.");
		
		$download = isset($options["download"]) ? $options["download"] : FALSE;
		
		if ($download)
			$download_name = isset($options["download_name"]) ? $options["download_name"] : self::getDownloadName($file);
		
		$resume = isset($options["resume"]) ? $options["resume"] : TRUE;
		$open_mode = isset($options["open_mode"]) ? $options["open_mode"] : "rb";
		$open_context = isset($options["open_context"]) ? $options["open_context"] : NULL;
		$block_size = isset($options["block_size"]) ? $options["block_size"] : 8 * 1024;
		
		$file_size = filesize($file);
		
		$range = $resume ? self::parseHttpRange($file_size) : NULL;

		if (@$range) {
            HttpHeader::setHeader('HTTP/1.1 206 Partial Content');
	        HttpHeader::setHeader('Content-Range: bytes ' . $range["start"] . '-' . $range["end"] . '/' . $file_size);
		} else {
            HttpHeader::setHeader('HTTP/1.1 200 Ok');
		}
        HttpHeader::setHeader('Accept-Ranges: bytes');

		if (isset($options["content_type"])) {
			if (StringUtils::has_sub($options["content_type"], "/"))
				$content_type = $options["content_type"];
			else
				$content_type = ContentType::byExtension($options["content_type"], $download ? TRUE : FALSE);	
		} else
			$content_type = ContentType::byFileName($download ? $download_name : $file, $download ? TRUE : FALSE);
	    HttpHeader::setHeader('Content-Type: ' . $content_type);
	  				
		if ($download)
	    	HttpHeader::setHeader('Content-Disposition: attachment; filename="' . $download_name . '"');
		
	    HttpHeader::setHeader('Content-Length: ' . (@$range ? $range["bytes"] : $file_size));
	  		
		$remaining = @$range ? $range["bytes"] : $file_size;
	    
		if (@$options["head_only"])
			return $remaining;
		
		set_time_limit(0);
		$handle = $open_context ? fopen($file, $open_mode, FALSE, $open_context) : fopen($file, $open_mode);
		if (!$handle)
			throw new FileStreamerException("Could not open file.");
		
		if (@$range)
			fseek($handle, $range["start"]);
		
		$transferred = 0;

		while (!feof($handle) && ($remaining > 0) && !connection_aborted()) {
			$read_size = min($remaining, $block_size);
			$data = fread($handle, $read_size);
			$returned_size = strlen($data);
			if ($returned_size > $read_size)
				throw new FileStreamerException("Read returned more data than requested.");
            if (function_exists("custom_controller_print_data_function"))
                custom_controller_print_data_function($data);
            else
                print($data);
			$transferred += $returned_size;
			$remaining -= $returned_size;
			flush();
			ob_flush();
		}
		
		fclose($handle);
		return $transferred;
	}
	
}