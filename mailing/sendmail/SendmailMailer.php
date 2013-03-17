<?php

require_once(dirname(__file__) . "/../Mailer.php");

/*
 * Options
 *   - bounces: a bouncer email address (optional)
 *   - headers: additional headers (optional)
 * 
 */

class SendmailMailer extends Mailer
{
	
	protected function sendMail($mail) { 
		$headers = "From: {$mail->sender}" . PHP_EOL .
				"Reply-To: {$mail->sender}" . PHP_EOL .
				"X-Mailer: PHP/" . phpversion() . PHP_EOL ;
		
		$header = $mail->headers;
		
		if (@$this->option("headers"))
			$headers .= $this->option("headers");
		
		$params = "";
		if (@$this->option("bounces"))
	        $params .= "-r " . $this->option("bounces");
		
		$result = mail($mail->recipient, $mail->subject, $mail->message, $headers, $params);

		return $result;
	}


}
