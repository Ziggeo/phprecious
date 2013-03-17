<?php

class Mail {
	
	public static $default_sender = "noreply@domain.com";
	
    var $sender;
    var $recipient;
	var $subject;
	var $message;
	var $message_html;

    // default sender is no-reply
	public function __construct() {
        $this->sender = static::$default_sender;
	}
	  
}
