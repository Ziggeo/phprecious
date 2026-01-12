<?php

require_once(dirname(__FILE__) . "/../database/memory/MemoryDatabase.php");
require_once(dirname(__FILE__) . "/../modelling/models/DatabaseModel.php");
require_once(dirname(__FILE__) . "/../modelling/jobs/JobModel.php");

$database = NULL;
$test_job_model_options = array(
    "direct_execute" => FALSE,
    "timeout" => -1,
    "timeout_max" => 1,
    "failure_max" => 1,
    "delete_on_close" => FALSE,
    "delete_on_discard" => FALSE,
    "not_ready_max" => 1
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
        $next_job = JobModelTestModel::nextJob();
        $this->assertNull($next_job);
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
        $next_job = JobModelTestModel::nextJob();
        $this->assertInstanceOf(JobModelTestModel::class, $next_job);
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->processJob();
        $next_job = JobModelTestModel::nextJob();
        $this->assertNull($next_job);
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
        $next_job = JobModelTestModel::nextJob();
        $this->assertInstanceOf(JobModelTestModel::class, $next_job);
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->processJob();
        $next_job = JobModelTestModel::nextJob();
        $this->assertNull($next_job);
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
        $next_job = JobModelTestModel::nextJob();
        $this->assertInstanceOf(JobModelTestModel::class, $next_job);
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->processJob();
        $next_job = JobModelTestModel::nextJob();
        $this->assertNull($next_job);
        $this->assertEquals($job->status, JobModel::STATUS_FAILED);

    }

    public function testTimeout() {
        global $database, $test_job_model_options;
        $test_job_model_options["timeout"] = -1;
        $test_job_model_options["timeout_max"] = 1;
        $database = new MemoryDatabase();
        JobModelTestModel::table(TRUE);

        $job = new JobModelTestModel();
        $job->save();
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->update(array("status" => JobModel::STATUS_EXECUTING));
        $job->reload();
        $this->assertEquals($job->status, JobModel::STATUS_EXECUTING);
        JobModelTestModel::updateJobs();
        $job->reload();
        $this->assertEquals($job->status, JobModel::STATUS_OPEN);
        $job->update(array("status" => JobModel::STATUS_EXECUTING));
        JobModelTestModel::updateJobs();
        $job->reload();
        $this->assertEquals($job->status, JobModel::STATUS_TIMEOUT);
    }

}
