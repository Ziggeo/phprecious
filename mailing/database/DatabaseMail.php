<?php

require_once(dirname(__file__) . "/../../modelling/models/DatabaseModel.php");

class DatabaseMail extends DatabaseModel {
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["sender"] = array("type" => "string", "validate" => array(new PresentValidator()));
		$attrs["recipient"] = array("type" => "string", "validate" => array(new PresentValidator()));
		$attrs["subject"] = array("type" => "string", "validate" => array(new PresentValidator()));
		$attrs["message"] = array("type" => "string", "validate" => array(new PresentValidator()));
		$attrs["mailed"] = array("type" => "boolean", "default" => FALSE);
		return $attrs;
	}
	
	public static function deliverAll($mailer) {
		$success = TRUE;
		$db_mails = self::allBy(array("mailed" => FALSE));
		foreach ($db_mails as $db_mail) {
			$mail = new Mail();
			$mail->sender = $db_mail->sender;
			$mail->recipient = $db_mail->recipient;
			$mail->subject = $db_mail->subject;
			$mail->message = $db_mail->message;
			if ($mailer->send($mail))
				$db_mail->update(array("mailed" => TRUE));
			else
				$success = FALSE;
		}
		return $success;
	}
	
}
