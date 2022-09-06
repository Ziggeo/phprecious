<?php

	require_once(dirname(__FILE__) . "/../files/FileUtils.php");

	class ContentType {

		public static function byExtension($ext, $download = FALSE) {
			if (!@$ext) {
				return "application/octet-stream";
			}
			switch ($ext) {
				case "3gp":
					return "video/3gpp";
				case "7z":
					return "application/x-7z-compressed";
				case "aac":
					return "audio/x-aac";
				case "ps":
				case "eps":
				case "ai":
					return "application/postscript";
				case "aif":
					return "audio/x-aiff";
				case "txt":
				case "ini":
				case "log":
				case "asc":
					return "text/plain";
				case "asf":
					return "video/x-ms-asf";
				case "atom":
					return "application/atom+xml";
				case "avi":
					return "video/x-msvideo";
				case "bmp":
					return "image/bmp";
				case "bz2":
					return "application/x-bzip2";
				case "cer":
					return "application/pkix-cert";
				case "crl":
					return "application/pkix-crl";
				case "crt":
					return "application/x-x509-ca-cert";
				case "css":
					return "text/css";
				case "csv":
					return "text/csv";
				case "cu":
					return "application/cu-seeme";
				case "deb":
					return "application/x-debian-package";
				case "doc":
					return "application/msword";
				case "docx":
					return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
				case "dvi":
					return "application/x-dvi";
				case "eot":
					return "application/vnd.ms-fontobject";
				case "epub":
					return "application/epub+zip";
				case "etx":
					return "text/x-setext";
				case "flac":
					return "audio/flac";
				case "flv":
					return "video/x-flv";
				case "gif":
					return "image/gif";
				case "gz":
					return "application/gzip";
				case "html":
				case "htm":
					return "text/html";
				case "ico":
					return "image/x-icon";
				case "ics":
					return "text/calendar";
				case "iso":
					return "application/x-iso9660-image";
				case "jar":
					return "application/java-archive";
				case "jpeg":
				case "jpg":
				case "jpe":
					return "image/jpeg";
				case "js":
					return "text/javascript";
				case "json":
					return "application/json";
				case "latex":
					return "application/x-latex";
				case "mp4a":
				case "m4a":
					return "audio/mp4";
				case "mpg4":
				case "mp4":
				case "mp4v":
				case "m4v":
					return "video/mp4";
				case "midi":
				case "mid":
					return "audio/midi";
				case "qt":
				case "mov":
					return "video/quicktime";
				case "mkv":
					return "video/x-matroska";
				case "mp3":
					return "audio/mpeg";
				case "mpeg":
				case "mpg":
				case "mpe":
					return "video/mpeg";
				case "ogg":
				case "oga":
					return "audio/ogg";
				case "ogv":
					return "video/ogg";
				case "ogx":
					return "application/ogg";
				case "pbm":
					return "image/x-portable-bitmap";
				case "pdf":
					return "application/pdf";
				case "pgm":
					return "image/x-portable-graymap";
				case "png":
					return "image/png";
				case "pnm":
					return "image/x-portable-anymap";
				case "ppm":
					return "image/x-portable-pixmap";
				case "ppt":
					return "application/vnd.ms-powerpoint";
				case "pptx":
					return "application/vnd.openxmlformats-officedocument.presentationml.presentation";
				case "rar":
					return "application/x-rar-compressed";
				case "ras":
					return "image/x-cmu-raster";
				case "rss":
					return "application/rss+xml";
				case "rtf":
					return "application/rtf";
				case "sgml":
				case "sgm":
					return "text/sgml";
				case "svg":
					return "image/svg+xml";
				case "swf":
					return "application/x-shockwave-flash";
				case "tar":
					return "application/x-tar";
				case "tiff":
				case "tif":
					return "image/tiff";
				case "torrent":
					return "application/x-bittorrent";
				case "ttf":
					return "application/x-font-ttf";
				case "wav":
					return "audio/x-wav";
				case "webm":
					return "video/webm";
				case "webp":
					return "image/webp";
				case "wma":
					return "audio/x-ms-wma";
				case "wmv":
					return "video/x-ms-wmv";
				case "woff":
					return "application/x-font-woff";
				case "wsdl":
					return "application/wsdl+xml";
				case "xbm":
					return "image/x-xbitmap";
				case "xls":
					return "application/vnd.ms-excel";
				case "xlsx":
					return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
				case "xml":
					return "application/xml";
				case "xpm":
					return "image/x-xpixmap";
				case "xwd":
					return "image/x-xwindowdump";
				case "yml":
				case "yaml":
					return "text/yaml";
				case "zip": return "application/zip";
				default:
					return $download ? "application/force-download" : "application/" . $ext;
			}
	}
	
	public static function byFileName($filename, $download = FALSE) {
		return self::byExtension(FileUtils::extensionOf($filename));
	}
			
}
