<?php

class Session extends BaseModel {

	protected function initializeAssocs() {
		parent::initializeAssocs();
		$this->assocs["files"] = new ModelHasManyAssociation(
			$this,
			"session_id",
			"UploadedFile",
			array("cached" => TRUE)
		);
	}
	
	private static $session = NULL;
	
	public static function getSession() {
		if (!@self::$session) {
			$session_id = Cookies::get(APP()->config("session.cookie.name"));
			if ($session_id)
			    self::$session = self::findById($session_id);
			if (!@self::$session) {
				$session = new Session();
				$session->setSession();
			} 
		}
		return self::$session;
	}
	
	public function setSession() {
		$this->save();
		Cookies::set(
			APP()->config("session.cookie.name"),
			$this->id(),
			APP()->config("session.cookie.domain"),
			APP()->config("session.cookie.duration_days")
		);
		self::$session = $this;
	}

}