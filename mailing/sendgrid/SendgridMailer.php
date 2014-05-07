<?php

require_once(dirname(__file__) . "/../Mailer.php");
// require_once($SENDGRID_DIRECTORY . "/SendGrid_loader.php");
Required::require_file("SendGrid_loader.php");


/*
 * Options
 *   - username: sendmail username (required)
 *   - password: sendmail password (required)
 *   - api: "web" | "smtp" (optional)
 * 
 */

class SendgridMailer extends Mailer
{
	
	private $sendgrid;
	
	public function __construct($options = array()) {
		parent::__construct($options);

		if (@!$this->option("username") || @!$this->option("password"))
			throw new MailerException("Username + password required for Sendgrid!");
		
		$this->sendgrid = new SendGrid($this->option('username'), $this->option('password'));
	}
	
	protected function sendMail($mail) {
		$sendgridmail = new SendGrid\Mail();
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
		
		return $this->sendNow($sendgridmail);
	}
	
	private function sendNow($sendgridmail, $tries = 5) {
		$last_exception = NULL;
		$api = @$this->option("api") ? $this->option("api") : "smtp";
		if ($api != "web" && $api != "smtp")
			throw new MailerException("Unknown sendgrid api '{$api}'");
		while ($tries > 0) {
			try {
				return $api == "web" ? $this->sendgrid->web->send($sendgridmail) : $this->sendgrid->smtp->send($sendgridmail);
			} catch (Exception $e) {
				$tries--;
				$last_exception = $e;
			}
		}
		throw new MailerException($last_exception);
	} 

}
