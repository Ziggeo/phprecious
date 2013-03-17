<?php

class Bcrypt {
	
    public static function hash($password, $work_factor = 0) {
    	$bytes = Tokens::random_bytes(16);
        $salt = '$2a$' . str_pad(8, 2, '0', STR_PAD_LEFT) . '$' . substr(strtr(base64_encode($bytes), '+', '.'), 0, 22);
        return crypt($password, $salt);
    }

    public static function check($password, $stored_hash) {
        return crypt($password, $stored_hash) == $stored_hash;
    }
}
