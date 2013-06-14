<?php

class BaseModel extends DatabaseModel {

	protected static function getDatabase() {
		return APP()->database();
	}

}