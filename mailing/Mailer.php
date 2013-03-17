<?php

abstract class Mailer
{
	
	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("mailer");
			else
				$PERFMON->leave("mailer");
		}
	}

	protected static function log($level, $s) {
		global $LOGGER;
		if (@$LOGGER)
			$LOGGER->message("framework.mailer", $level, $s);
	}

	private $options;
	
	public function option($key) {
		return @$this->options[$key];
	}
	
	public function __construct($options = array()) {
		$this->options = $options;
	}
	
	public function send($mail) {
		static::perfmon(true);
		static::log(Logger::INFO_2, "Sending email to '" . $mail->recipient . "' with " . get_called_class());
		$result = $this->sendMail($mail);
		static::perfmon(false);
		return $result;
	}
	
	abstract protected function sendMail($mail);

}

class MailerException extends Exception {}
