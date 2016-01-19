<?php

require_once(dirname(__file__) . "/../Mailer.php");
// require_once($SENDGRID_DIRECTORY . "/SendGrid_loader.php");
//Required::require_file("SendGrid_loader.php");


/*
 * Options
 *   - username: sendmail username (required)
 *   - password: sendmail password (required)
 *   - api: "web" | "smtp" (optional)
 * 
 */

class SendgridApiMailer extends Mailer
{
	
	private $sendgrid;
	
	public function __construct($options = array()) {
		parent::__construct($options);

		if (@!$this->option("apikey"))
			throw new MailerException("Sendgrid Api Required.");
		
		$this->sendgrid = new SendGrid($this->option('apikey'));
	}
	
	protected function sendMail($mail) {
		$sendgridmail = new SendGrid\Email();
		$sendgridmail->addTo($mail->recipient)
					 ->setSubject($mail->subject);
		if(@$mail->message)
			$sendgridmail->setText($mail->message);					 
		if(@$mail->message_html)
			$sendgridmail->setHtml($mail->message_html);					 
					 
		$sender_arr = explode("<", $mail->sender);
		if (count($sender_arr) == 1)
			$sendgridmail->setFrom($mail->sender);
		else {
			$sendgridmail->setFrom(trim(trim($sender_arr[1], ">")))
			             ->setFromName(trim($sender_arr[0]));
		}
		if (@$mail->attachments)
			foreach ($mail->attachments as $attachment)
				if (file_exists($attachment))
					$sendgridmail->addAttachment($attachment);
		
		return $this->sendNow($sendgridmail);
	}
	
	private function sendNow($sendgridmail, $tries = 5) {
		$last_exception = NULL;
		while ($tries > 0) {
			try {
				return $this->sendgrid->send($sendgridmail);
			} catch (Exception $e) {
				$this->sendgrid = new SendGrid($this->option('apikey'));
				$tries--;
				$last_exception = $e;
			}
		}
		throw new MailerException($last_exception);
	} 

}
