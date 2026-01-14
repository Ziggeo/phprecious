<?php

require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerFileConsumer extends LoggerConsumer {

	private $filename;
	private $logfh;
	private $last_filename = NULL;
	private $options;

	public function __construct($filename, $options = array()) {
		$this->filename = $filename;
		$this->options = array_merge(array(
			"rotate_logs" => FALSE,
			"rotate_time" => 60 * 60 * 24, // day
			"rotate_modulo" => 7, // week
			"delete_old_logs" => FALSE
		), $options);
		$this->obtain();
	}

    public function __destruct() {
    	$this->release();
    }

	private function release() {
        if ($this->logfh) {
        	fclose($this->logfh);
	        unset($this->logfh);
		}
	}

	private function currentFilename($time_shift = 0, $modulo_shift = 0) {
		if (!@$this->options["rotate_logs"])
			return $this->filename;
		$base = $this->filename;
		$divider = $this->options["rotate_time"];
		$modulo = $this->options["rotate_modulo"];
		$time = floor(time() / $divider) + $time_shift + $modulo_shift * $modulo;
		$time_mod = $time % $modulo;
		return $this->filename . "." . $time_mod . "." . $time;
	}

	private function obtain() {
		$current = $this->currentFilename();
		if ($current != $this->last_filename) {
			$this->last_filename = $current;
	        if (!is_file($this->last_filename)) {
	            file_put_contents($this->last_filename, "", LOCK_EX);
	            chmod($this->last_filename, 0666);
	        }
	        $this->logfh = @fopen($this->last_filename, "a");
	        // Check if file handle was successfully created
	        if ($this->logfh === false) {
	            // Log handle creation failed, set to null to prevent fatal errors
	            $this->logfh = null;
	            // Could add error handling here if needed
	        }
			if (@$this->options["delete_old_logs"]) {
				$old = $this->currentFilename(0, -1);
				if ($old != $this->last_filename && file_exists($old))
					unlink($old);
			}
		}
	}

	protected function processMessage($logger, $logMessage) {
		$this->obtain();
		// Only write to log if we have a valid file handle
		if ($this->logfh) {
			fwrite($this->logfh, $logMessage->formatOneLine());
		}
	}

}
