<?php

require_once(dirname(__file__) . "/../Mailer.php");
require_once(dirname(__file__) . "/DatabaseMail.php");

class DatabaseMailer extends Mailer
{
	
	protected function sendMail($mail) {
		$db_mail = new DatabaseMail(array(
			"sender" => $mail->sender,
			"recipient" => $mail->recipient,
			"subject" => $mail->subject,
			"message" => $mail->message,
			"message_html" => $mail->message_html
		));
		
		return $db_mail->save();
	}

}
