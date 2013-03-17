<?php

require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerFileConsumer extends LoggerConsumer {
	
	private $filename;
	private $logfh;
	
	public function __construct($filename) {
		$this->filename = $filename;
		
        if (!is_file($filename)) {
            file_put_contents($filename, "", LOCK_EX);  // create an empty log file
            chmod($filename, 0666);   // make sure apache can write (might be creating this from a cron job!)
        }

        // get a filehandle and hang on to it
        $this->logfh = @fopen($filename, "a");  // open log file for appending
	}
		
    public function __destruct() {
        if ($this->logfh)
        	fclose($this->logfh);
        unset($this->logfh);
    }
      
	protected function processMessage($logger, $logMessage) {
		// make sure we have a log file handle
    	if ($this->logfh)
			fwrite($this->logfh, $logMessage->formatOneLine());
	}
	
	
}

