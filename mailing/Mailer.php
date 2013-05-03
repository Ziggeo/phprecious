<?php

abstract class Mailer {
	
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
	
	public $default_sender = "noreply@domain.com";
	
	public function option($key) {
		return @$this->options[$key];
	}
	
	public function __construct($options = array()) {
		$this->options = $options;
		if (@$this->options["default_sender"])
			$this->default_sender = $this->options["default_sender"];
	}
	
	public function send($mail) {
		if (!@$mail->sender)
			$mail->sender = $this->default_sender;
		static::perfmon(true);
		static::log(Logger::INFO_2, "Sending email to '" . $mail->recipient . "' with " . get_called_class());
		$result = $this->sendMail($mail);
		static::perfmon(false);
		return $result;
	}
	
	abstract protected function sendMail($mail);

}

class MailerException extends Exception {}
