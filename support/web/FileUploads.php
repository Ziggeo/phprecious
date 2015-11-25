<?php
require_once (dirname ( __FILE__ ) . "/../strings/StringUtils.php");
require_once (dirname ( __FILE__ ) . "/Requests.php");
require_once (dirname ( __FILE__ ) . "/HttpHeader.php");

class FileUploads {
	
	public static function resumableUploadsParse($options = array()) {
		$result = array (
				"method" => Requests::getMethod(),
				"identifier" => FileUtils::safeFileName ( Requests::getVar ( "resumableIdentifier" ) ),
				"filename" => FileUtils::safeFileName ( Requests::getVar ( "resumableFilename" ) ),
				"chunkNumber" => intval ( Requests::getVar ( "resumableChunkNumber" ) ),
				"chunkSize" => intval ( Requests::getVar ( "resumableChunkSize" ) ),
				"totalSize" => intval ( Requests::getVar ( "resumableTotalSize" ) ),
				"currentChunkSize" => Requests::getVar ( "resumableCurrentChunkSize" ),
				"type" => Requests::getVar ( "resumableType" ),
				"relativePath" => Requests::getVar ( "resumableRelativePath" ),
				"totalChunks" => Requests::getVar ( "resumableTotalChunks" ),
				"tmpDirectory" => @$options ["directory"] ? $options ["directory"] : "/tmp",
				"tmpPrefix" => @$options ["prefix"] ? $options ["prefix"] : "",
		);
		$result["chunkDirectory"] = $result["tmpDirectory"] . "/" . $result["tmpPrefix"] . $result["identifier"] . "parts"; 		
		$result["chunkFile"] = $result["chunkDirectory"] . "/part." . $result["chunkNumber"]; 		
		$result["finalFile"] = $result["tmpDirectory"] . "/" . $result["tmpPrefix"] . $result["identifier"]; 		
		return $result;
	}
	
	private static function resumableUploadsTestChunk($parsed) {
		return file_exists($parsed["chunkNumber"]);
	}
	
	private static function resumableUploadsAddChunk($parsed) {
		if (count ($_FILES) !== 1) 
			return FALSE;
		$file = NULL;
		foreach ($_FILES as $f)
			$file = $f;
		if ($file ['error'] != 0 || $file ['size'] <= 0)
			return FALSE;
		if (! is_dir ( $parsed["chunkDirectory"]))
			mkdir ( $parsed["chunkDirectory"], 0777, true );
		if (! move_uploaded_file ( $file ['tmp_name'], $parsed["chunkFile"] )) 
			return FALSE;
		return TRUE;
	}
	
	private static function resumableUploadsChunksComplete($parsed) {
		$size = 0;
		$i = 1;
		while (file_exists($parsed["chunkDirectory"] . "/part." . $i)) {
			$size += filesize($parsed["chunkDirectory"] . "/part." . $i);
			$i++;
		}
		return $parsed["totalSize"] === $size;
	}
	
	private static function resumableUploadsAssembleChunks($parsed) {
		$fp = fopen ( $parsed["finalFile"], 'w' );
		if ($fp === FALSE)
			return FALSE;
		$i = 1;
		while (file_exists($parsed["chunkDirectory"] . "/part." . $i)) {
			$chunkName = $parsed["chunkDirectory"] . "/part." . $i;
			$src = fopen($chunkName, 'r');
			stream_copy_to_stream($src, $fp);
			fclose($src);
			//$chunkContent = file_get_contents($chunkName);
			//fwrite ( $fp, $chunkContent );
			$i++;
		}
		fclose ( $fp );
		$i = 1;
		while (file_exists($parsed["chunkDirectory"] . "/part." . $i)) {
			@unlink($parsed["chunkDirectory"] . "/part." . $i);
			$i++;
		}
		rmdir($parsed["chunkDirectory"]);
		return TRUE;
	}
	
	
	const RESUMABLE_CHUNK_FOUND = 0; // 200
	const RESUMABLE_CHUNK_WRITE_SUCCESS = 1; // 200
	const RESUMABLE_ASSEMBLE_SUCCESS = 2; // 200
	const RESUMABLE_CHUNK_WRITE_ERROR = 3; // 205
	const RESUMABLE_CHUNK_NOT_FOUND = 4; // 404
	const RESUMABLE_ASSEMBLE_INCOMPLETE = 5; // 412
	const RESUMABLE_ASSEMBLE_ERROR = 6; // 500
	const RESUMABLE_INVALID_OPERATION = 7; // 500
	
		
	public static function resumableUploadsProcess($parsed, $options = array("autoAssemble" => FALSE)) {
		if ($parsed["method"] === "GET") {
			if (self::resumableUploadsTestChunk($parsed))
				return self::RESUMABLE_CHUNK_FOUND;
			else
				return self::RESUMABLE_CHUNK_NOT_FOUND;
		}
		if ($parsed["method"] === "POST") {
			if (count($_FILES) > 0) {
				if (!self::resumableUploadsAddChunk($parsed))
					return self::RESUMABLE_CHUNK_WRITE_ERROR;
				if (!@$options["autoAssemble"] || !self::resumableUploadsChunksComplete($parsed)) 
					return self::RESUMABLE_CHUNK_WRITE_SUCCESS;
				if (!self::resumableUploadsAssembleChunks($parsed))
					return self::RESUMABLE_ASSEMBLE_ERROR;
				return self::RESUMABLE_ASSEMBLE_SUCCESS;
			} else {
				if (!self::resumableUploadsChunksComplete($parsed))
					return self::RESUMABLE_ASSEMBLE_INCOMPLETE;
				if (!self::resumableUploadsAssembleChunks($parsed))
					return self::RESUMABLE_ASSEMBLE_ERROR;
				return self::RESUMABLE_ASSEMBLE_SUCCESS;
			}
		}
		return self::RESUMABLE_INVALID_OPERATION;
	}
	
	public static function resumableUploadsProcessStatus($result) {
		if ($result <= self::RESUMABLE_ASSEMBLE_SUCCESS)
			return HttpHeader::HTTP_STATUS_OK;
		if ($result <= self::RESUMABLE_CHUNK_WRITE_ERROR)
			return HttpHeader::HTTP_STATUS_RESET_CONTENT;
		if ($result <= self::RESUMABLE_CHUNK_NOT_FOUND)
			return HttpHeader::HTTP_STATUS_NOT_FOUND;
		if ($result <= self::RESUMABLE_ASSEMBLE_INCOMPLETE)
			return HttpHeader::HTTP_STATUS_PRECONDITION_FAILED;
		return HttpHeader::HTTP_STATUS_INTERNAL_SERVER_ERROR;
	}
	
	/*
	 * - directory
	 * - prefix
	 * - file key
	 *
	 * returns: TRUE / FALSE
	 *
	 */
	public static function resumableUploadsPolyfill($options = array(
		"autoAssemble" => FALSE,
		"file" => "file"
	)) {
		if (! @Requests::getVar ( "resumableIdentifier" ))
			return ! ! @$_FILES [$options ["file"]];
		$parsed = self::resumableUploadsParse($options);
		$result = self::resumableUploadsProcess ($parsed, $options );
		if ($result !== self::RESUMABLE_ASSEMBLE_SUCCESS) {
			$status = self::resumableUploadsProcessStatus($result);
			header("HTTP/1.1 " . HttpHeader::formatStatusCode($status, TRUE));
			return FALSE;
		}
		$_FILES [$options ["file"]] = array (
				"name" => $parsed ["filename"],
				"type" => $parsed ["type"],
				"tmp_name" => $parsed["finalFile"],
				"error" => 0,
				"size" => $parsed ["totalSize"] 
		);
		return TRUE;
	}
	
}
