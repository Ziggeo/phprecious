<?php

abstract class JobModel extends DatabaseModel {
    
    const STATUS_OPEN = 0;
    const STATUS_EXECUTING = 1;
    const STATUS_DISCARDED = 2;
    const STATUS_CLOSED = 3;
    const STATUS_FAILED = 4;
    const STATUS_TIMEOUT = 5;
    const STATUS_NOT_READY = 6;
    
    private static $STATUS_STRINGS = array(
        self::STATUS_OPEN => "OPEN",
        self::STATUS_EXECUTING => "EXECUTING",
        self::STATUS_DISCARDED => "DISCARDED",
        self::STATUS_CLOSED => "CLOSED",
        self::STATUS_FAILED => "FAILED",
        self::STATUS_TIMEOUT => "TIMEOUT",
    	self::STATUS_NOT_READY => "NOTREADY"
    );
    
    protected static function jobOptions() {
        return array(
            /* Do you want to immediately execute the job after saving? */
            "direct_execute" => FALSE,
            /* When does a timeout occur? */
            "timeout" => NULL,
            /* Do you want to reschedule after timeout? */
            "timeout_max" => NULL,
            /* Do you want to reschedule jobs after failures? */
            "failure_max" => 1,
            /* Delete after closing */
            "delete_on_close" => FALSE,
            /* Delete after discard */
            "delete_on_discard" => FALSE,
        	"not_ready_max" => 1
        );
    }
    
    protected function validJob() {
        return TRUE;
    }
    
    protected abstract function performJob();
    
    protected static function initializeScheme() {
        $attrs = parent::initializeScheme();
        $attrs["status"] = array(
            "type" => "integer",
            "default" => self::STATUS_OPEN,
            "index" => TRUE
        );
        $attrs["direct_execute"] = array(
            "type" => "boolean",
            "default" => FALSE
        );
        $attrs["failure_count"] = array(
            "type" => "integer",
            "default" => 0
        );
        $attrs["not_ready_count"] = array(
            "type" => "integer",
            "default" => 0
        );
        $attrs["timeout_count"] = array(
            "type" => "integer",
            "default" => 0
        );
        return $attrs;
    }

    protected function afterCreate() {
        $opts = static::jobOptions();
        parent::afterCreate();
        $this->updateStatus(self::STATUS_OPEN, "Created Job");
        if ($opts["direct_execute"] || $this->direct_execute)
            $this->processJob();
    }

    public static function nextJob() {
        $jobs = static::allBy(array("status" => self::STATUS_OPEN), array("created" => 1), 1);
        return count($jobs) == 1 ? $jobs[0] : NULL;
    }
    
    protected function updateStatus($status, $message = "", $level = Logger::INFO) {
        static::log($level, "Job: " . $this->id() . " Status: " . self::$STATUS_STRINGS[$status] . ($message != "" ? " Message: " . $message : ""));
        $this->update(array("status" => $status));
    }
    
    protected function isReady() {
    	return TRUE;
    }

    public function processJob() {
        $opts = static::jobOptions();
        if ($this->status == self::STATUS_OPEN) {
            $this->updateStatus(self::STATUS_EXECUTING);
            if (!$this->validJob()) {
                $this->updateStatus(self::STATUS_DISCARDED);
                if ($opts["delete_on_discard"])
                    $this->delete();
                return TRUE;
            }
            if (!$this->isReady()) {
            	$this->inc("not_ready_count");
            	if ($opts["not_ready_max"] != NULL && $this->not_ready_count < $opts["not_ready_max"])
            		$this->updateStatus(self::STATUS_NOT_READY);
            	else
            		$this->updateStatus(self::STATUS_FAILED, "Not ready");
            	return TRUE;
            }
            try {
            	$this->performJob();
                $this->updateStatus(self::STATUS_CLOSED);
                if ($opts["delete_on_close"])
                    $this->delete();
                return TRUE;
            } catch (Exception $e) {
                $this->inc("failure_count");
                $this->updateStatus(self::STATUS_FAILED, $e->getMessage(), Logger::WARN);
            }
        }
        return FALSE;
    }

    public static function processJobs() {
        $jobs = self::allBy(array("status" => self::STATUS_OPEN), array("created" => 1), NULL, NULL, TRUE);
        foreach ($jobs as $job)
            $job->processJob();
    } 
    
    public static function updateJobs() {
        $opts = static::jobOptions();
        if ($opts["timeout"] != NULL) {
            $jobs = self::allBy(array("status" => self::STATUS_EXECUTING), NULL, NULL, NULL, TRUE);
            foreach ($jobs as $job) {
                if (TimePoint::get($job->created)->increment($opts["timeout"])->earlier()) {
                    $job->inc("timeout_count");
                    $job->updateStatus(self::STATUS_TIMEOUT, "", Logger::WARN);
                }
            }
        }
        if ($opts["timeout_max"] == NULL || $opts["timeout_max"] > 0) {
            $query = array("status" => self::STATUS_TIMEOUT);
            if ($opts["timeout_max"] != NULL)
                $query["timeout_count"] = array('$lte' => $opts["timeout_max"]);
            $jobs = self::allBy($query, NULL, NULL, NULL, TRUE);
            foreach ($jobs as $job)
                $job->updateStatus(self::STATUS_OPEN, "Reopen after timeout");
        }
        if ($opts["failure_max"] == NULL || $opts["failure_max"] > 1) {
            $query = array("status" => self::STATUS_FAILED);
            if ($opts["failure_max"] != NULL)
                $query["failure_count"] = array('$lte' => $opts["failure_max"]);
            $jobs = self::allBy($query, NULL, NULL, NULL, TRUE);
            foreach ($jobs as $job) 
                $job->updateStatus(self::STATUS_OPEN, "Reopen after failure");
        }
		if ($opts["not_ready_max"] == NULL || $opts["not_ready_max"] > 1) {
            $query = array("status" => self::STATUS_NOT_READY);
            if ($opts["not_ready_max"] != NULL)
                $query["not_ready_count"] = array('$lte' => $opts["not_ready_max"]);
            $jobs = self::allBy($query, NULL, NULL, NULL, TRUE);
            foreach ($jobs as $job)
                if ($job->isReady())
                    $job->updateStatus(self::STATUS_OPEN, "Reopen after not ready");
        }
    }
    
}


Class JobException extends Exception {
        
    function __construct($job, $message) {
        parent::__construct(get_class($job) . ": " . $message);
    }
}
