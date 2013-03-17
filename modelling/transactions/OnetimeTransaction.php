<?php

class OnetimeTransaction extends DatabaseModel {
	
	const REMOVE_DAYS = 0.5;
	
	protected static function initializeScheme() {
		$attrs = parent::initializeScheme();
		$attrs["token"] = array("type" => "string", "validate" => array(new PresentValidator()));
		return $attrs;
	}

    public static function findByToken($token) {
    	return self::findBy(array("token" => $token));
    }

	protected function beforeValidate() {
		parent::beforeValidate();
		if (@!$this->token)
			$this->token = Tokens::generate();
	}
	
	public static function mem($token) {
		return @self::findByToken($token) ? TRUE : FALSE;
	}
	
	public static function push() {
		$obj = new OnetimeTransaction();
		$obj->save();
		return $obj->token;
	}
	
	public static function pop($token) {
		$obj = self::findByToken($token);
		if (!@$obj)
			return FALSE;
		$obj->delete();
		return TRUE;
	}
	
	public static function cleanup($simulate = FALSE) {
		foreach (self::all() as $instance)
			if (TimeSupport::microtime_diff($instance->created)/60/60/24 >= self::REMOVE_DAYS) {
				if (!$simulate)
					$instance->delete();
			}
	}

}
