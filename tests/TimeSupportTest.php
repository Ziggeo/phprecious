<?php

require_once(dirname(__FILE__) . "/../support/time/TimeSupport.php");

class TimeSupportTest extends PHPUnit_Framework_TestCase {
	
	public function testEnsureSecondsNumber() {
		$this->assertEquals(TimeSupport::ensure_seconds(12345), 12345);
		$this->assertEquals(TimeSupport::ensure_seconds("12345"), 12345);
	}
 
	public function testEnsureSecondsMicrotime() {
		$this->assertEquals(TimeSupport::ensure_seconds("0.54321 12345"), 12345);
	}

	public function testEnsureSecondsString() {
		$this->assertEquals(TimeSupport::ensure_seconds("2 hours"), 2 * 60 * 60);
	}
	
	public function testEnsureSecondsTimePoint() {
		$this->assertEquals(TimeSupport::ensure_seconds(TimePoint::get(12345)), 12345);
	}

	public function testEnsureTimePoint() {
		$t = time();
		$tp = TimePoint::ensure($t);
		$this->assertEquals($tp->seconds(), $t);
		$this->assertEquals(TimePoint::ensure($tp)->seconds(), $t);
	}
	
	public function testEnsureTimePeriod() {
		$t = 1234;
		$tp = TimePeriod::ensure($t);
		$this->assertEquals($tp->seconds(), $t);
		$this->assertEquals(TimePeriod::ensure($tp)->seconds(), $t);
	}

	public function testTimePeriodMinutesDays() {
		$this->assertEquals(TimePeriod::minutes(1234)->seconds(), 1234 * 60);
		$this->assertEquals(TimePeriod::days(1234)->seconds(), 1234 * 24 * 60 * 60);
	}

	public function testEarlier() {
		$this->assertTrue(TimePoint::get(1235)->earlier(TimePoint::get(1234)->increment(2)));
	}
	
	public function testTimePeriodFrom() {
		$this->assertEquals(TimePeriod::hours(2)->from(TimePoint::get(1234))->seconds(), 1234 + 2 * 60 * 60);
	}
	
}

