<?php


require_once(dirname(__FILE__) . "/LoggerConsumer.php");
require_once(dirname(__FILE__) . "/../mailing/Mail.php");

class LoggerMailConsumer extends LoggerConsumer {
	
	private $mailer;
	private $sender;
	private $recipients;
	private $subject;
	
	public function __construct($mailer, $sender, $recipients, $subject) {
		$this->mailer = $mailer;
		$this->sender = $sender;
		$this->recipients = $recipients;
		$this->subject = $subject;

	}
		
	protected function processMessage($logger, $logMessage) {
        $mail = new Mail();
        $mail->subject = sprintf($this->subject, date('Y-m-d H:i:s'));
        $mail->message = $logMessage->formatMultiLine();
        $mail->sender = $this->sender;
		
		foreach ($this->recipients as $email) {
			$mail->recipient = $email;
			$this->mailer->send($mail);
		}
	}
	
	
}

