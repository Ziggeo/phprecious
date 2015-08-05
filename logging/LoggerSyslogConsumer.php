<?php


require_once(dirname(__FILE__) . "/LoggerConsumer.php");

class LoggerSyslogConsumer extends LoggerConsumer {
	
	public function __construct($name) {
		openlog($name, LOG_ODELAY | LOG_PID, LOG_USER);
	}
	
	public static function logLevelToSyslogLevel($level) {
		if ($level == Logger::ERROR)
			return LOG_ERR;
		else if ($level == Logger::WARNING)
			return LOG_WARNING;
		else if ($level == Logger::INFO)
			return LOG_NOTICE;
		else if ($level == Logger::INFO_2)
			return LOG_INFO;
		else
			return LOG_DEBUG;
	}
	
	protected function processMessage($logger, $logMessage) {
		syslog(static::logLevelToSyslogLevel($logMessage->level()), $logMessage->toJSON());
	}
	
}
