<?php

class EmailValidator extends Validator {
	
	// Returns an error string in case of an error; otherwise returns NULL
	public function validate($email, $context = NULL) {
		if (!isset($email) || !is_string($email) || strlen($email) == 0)
			return _("Email address required.");
	  
		// split into username and domain
		if (strpos($email, "@") == false)
			return _("No @ sign.");
	  
		list ($username, $domain) = explode("@", $email, 2);
	
		// must have non-empty username
		if ($username == "")
			return _("Empty username.");
	
		// must have a non-empty domain
		if ($domain == "")
			return _("Empty domain.");
	
		if (!preg_match("/^[\!\#\$\%\*\?\|\^\{\}\`\~\&\+\-\=\_\.\/\'a-zA-Z\d]+$/", $username))
			return _("Username cannot have special characters (\, ' ', \").");
	
		if (!preg_match("/^[\.\-a-zA-Z\d]+$/", $domain))
			return _("Domain cannot have special characters {%, $, ' ', ^, '', \"\", _, + etc.}.");
	
		// domain can't have another @ in it
		if (strpos($domain, "@") !== false)
			return _("More than one @.");
	
		// domain must have at least one dot in it
		// get position of rightmost dot in process
		$pos = strrpos($domain, ".");
	
	    // correct because we want to catch both not found and pos = 0 
	    if (!$pos)
	    	return _("No . in domain.");
	
		// extract TLD
		$tld = substr($domain, $pos+1);
		if ($tld == "")
			return _("No TLD (e.g. .com).");

      	return NULL;
	}
	
}
