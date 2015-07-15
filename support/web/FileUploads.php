<?php

require_once (dirname ( __FILE__ ) . "/../strings/StringUtils.php");
require_once (dirname ( __FILE__ ) . "/Requests.php");
require_once (dirname ( __FILE__ ) . "/HttpHeader.php");

class FileUploads {
	
	private static function parseResumableUpload() {
		return array(
			"identifier" => FileUtils::safeFileName ( Requests::getVar ( "resumableIdentifier" ) ),
			"filename" => FileUtils::safeFileName ( Requests::getVar ( "resumableFilename" ) ),
			"chunkNumber" => intval ( Requests::getVar ( "resumableChunkNumber" ) ),
			"chunkSize" => intval ( Requests::getVar ( "resumableChunkSize" ) ),
			"totalSize" => intval ( Requests::getVar ( "resumableTotalSize" ) ),
			"currentChunkSize" => Requests::getVar ( "resumableCurrentChunkSize" ),
			"type" => Requests::getVar ( "resumableType" ),
			"relativePath" => Requests::getVar ( "resumableRelativePath" ),
			"totalChunks" => Requests::getVar ( "resumableTotalChunks" )
		);
	}
	
	public static function processResumableUpload($options = array()) {
		$directory = "/tmp";
		if (@$options ["directory"])
			$directory = $options["directory"];
		$prefix = "";
		if (@$options ["prefix"])
			$prefix = $options ["prefix"];
		$method = Requests::getMethod ();
		$parsed = self::parseResumableUpload();
		
		$chunkDirectory = $directory . "/" . $prefix . $parsed["identifier"];
		$chunkFile = $chunkDirectory . "/" . $parsed["filename"] . ".part" . $parsed["chunkNumber"];
		$finalFile = $directory . "/" . $prefix . $parsed["identifier"] . $parsed["filename"];
		
		if ($method === "GET") {
			if (file_exists ( $chunkFile ))
				header ( HttpHeader::formatStatusCode ( HttpHeader::HTTP_STATUS_OK, TRUE ) );
			else
				header ( HttpHeader::formatStatusCode ( HttpHeader::HTTP_STATUS_NOT_FOUND, TRUE ) );
		} else if ($method === "POST" && ! empty ( $_FILES )) {
			foreach ( $_FILES as $file ) {
				if ($file ['error'] != 0)
					continue;
				if (! is_dir ( $chunkDirectory ))
					mkdir ( $chunkDirectory, 0777, true );
				if (! move_uploaded_file ( $file ['tmp_name'], $chunkFile ))
					header ( HttpHeader::formatStatusCode ( HttpHeader::HTTP_STATUS_INTERNAL_SERVER_ERROR, TRUE ) );
				else {
					$total_files = 0;
					foreach ( scandir ( $chunkDirectory ) as $file )
						if (stripos ( $file, $parsed["filename"] ) !== false)
							$total_files ++;
					if ($total_files * $parsed["chunkSize"] >= ($parsed["totalSize"] - $parsed["chunkSize"] + 1)) {
						if (($fp = fopen ( $finalFile, 'w' )) !== false) {
							for($i = 1; $i <= $total_files; $i ++)
								fwrite ( $fp, file_get_contents ( $chunkDirectory . '/' . $parsed["filename"] . '.part' . $i ) );
							fclose ( $fp );
							if (rename ( $chunkDirectory, $chunkDirectory . '_UNUSED' ))
								FileUtils::delete_tree ( $chunkDirectory . '_UNUSED' );
							else
								FileUtils::delete_tree ( $chunkDirectory );
							header ( HttpHeader::formatStatusCode ( HttpHeader::HTTP_STATUS_OK, TRUE ) );
							return $finalFile;
						} else {
							header ( HttpHeader::formatStatusCode ( HttpHeader::HTTP_STATUS_INTERNAL_SERVER_ERROR, TRUE ) );
							return NULL;
						}
					}
				}
			}
			header ( HttpHeader::formatStatusCode ( HttpHeader::HTTP_STATUS_OK, TRUE ) );
			return NULL;
		}
		header ( HttpHeader::formatStatusCode ( HttpHeader::HTTP_STATUS_OK, TRUE ) );
		return NULL;
	}
	
	
	/*
	 * - directory
	 * - prefix
	 * - file key
	 * 
	 * returns: TRUE / FALSE
	 * 
	 */
	public static function resumableUploadPolyfill($options) {
		if (!@Requests::getVar("resumableIdentifier"))
			return !!@$_FILES[$options["file"]];
		$processed = self::processResumableUpload($options);
		if ($processed == NULL)	
			return FALSE;
		$resumable = self::parseResumableUpload();
		$_FILES[$options["file"]] = array(
			"name" => $resumable["filename"],
			"type" => $resumable["type"],
			"tmp_name" => $processed,
			"error" => 0,
			"size" => $resumable["totalSize"]
		);
		return TRUE;
	}
	
}
