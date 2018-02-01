<?php

require_once(dirname(__FILE__) . "/../database/memory/MemoryDatabase.php");
require_once(dirname(__FILE__) . "/../modelling/models/DatabaseModel.php");
require_once(dirname(__FILE__) . "/../modelling/jobs/JobModel.php");

$database = NULL;
$test_job_model_options = array(
    "direct_execute" => FALSE,
    "timeout" => NULL,
    "timeout_max" => NULL,
    "failure_max" => 1,
    "delete_on_close" => FALSE,
    "delete_on_discard" => FALSE
);
$test_valid_job = TRUE;
$test_fail_job = FALSE;

Class JobModelTestModel extends JobModel {
    
    protected static function getDatabase() {
        global $database;
        return $database;
    }
    
    protected static function jobOptions() {
        global $test_job_model_options;
        return $test_job_model_options;
    }
    
    protected function validJob() {
        global $test_valid_job;
        return $test_valid_job;
    }
    
    protected function performJob() {
        global $test_fail_job;
        if ($test_fail_job)
            throw new Exception("Failed");
    }
    
}


class JobModelTest extends PHPUnit\Framework\TestCase {
    
    public function testExecuteDirectSuccess() {
        global $database, $test_valid_job, $test_fail_job, $test_job_model_options;
        $test_valid_job = TRUE;
        $test_fail_job = FALSE;
        $test_job_model_options["direct_execute"] = TRUE;
        $database = new MemoryDatabase();
        $job = new JobModelTestModel();
        $job->save();
        $jobs = JobModelTestModel::nextJob(NULL);
        $this->assertEquals(count($jobs), 0);
        $this->assertEquals($job->status, JobModel::STATUS_CLOSED);
    }

    public function testExecuteSuccess() {
        global $database, $test_valid_job, $test_fail_job, $test_job_model_options;
        $test_valid_job = TRUE;
        $test_fail_job = FALSE;
        $test_job_model_options["direct_execute"] = FALSE;
        $database = new MemoryDatabase();
        $job = new JobModelTestModel();
        $job->save();
        $jobs = JobModelTestModel::nextJob(NULL);
        $this->assertEquals(count($jobs), 1);
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->processJob();
        $jobs = JobModelTestModel::nextJob(NULL);
        $this->assertEquals(count($jobs), 0);
        $this->assertEquals($job->status, JobModel::STATUS_CLOSED);
    }
    
    public function testExecuteInvalid() {
        global $database, $test_valid_job, $test_fail_job, $test_job_model_options;
        $test_valid_job = FALSE;
        $test_fail_job = FALSE;
        $test_job_model_options["direct_execute"] = FALSE;
        $database = new MemoryDatabase();
        $job = new JobModelTestModel();
        $job->save();
        $jobs = JobModelTestModel::nextJob(NULL);
        $this->assertEquals(count($jobs), 1);
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->processJob();
        $jobs = JobModelTestModel::nextJob(NULL);
        $this->assertEquals(count($jobs), 0);
        $this->assertEquals($job->status, JobModel::STATUS_DISCARDED);
    }

    public function testExecuteFail() {
        global $database, $test_valid_job, $test_fail_job, $test_job_model_options;
        $test_valid_job = TRUE;
        $test_fail_job = TRUE;
        $test_job_model_options["direct_execute"] = FALSE;
        $database = new MemoryDatabase();
        $job = new JobModelTestModel();
        $job->save();
        $jobs = JobModelTestModel::nextJob(NULL);
        $this->assertEquals(count($jobs), 1);
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->processJob();
        $jobs = JobModelTestModel::nextJob(NULL);
        $this->assertEquals(count($jobs), 0);
        $this->assertEquals($job->status, JobModel::STATUS_FAILED);
    }

}
