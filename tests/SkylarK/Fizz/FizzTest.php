<?php

class Demo extends SkylarK\Fizz\Fizz
{
	public $_ignore_me;
	public $key;
	public $value;

	public function __construct($table = NULL) {
		parent::__construct($table);
		$this->truncate();
	}
}

class FizzTest extends PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass() {
		try {
			SkylarK\Fizz\FizzConfig::setDB("mysql:dbname=testdb;host=127.0.0.1", "travis", "");
		}
		catch (PDOException $e) {
			die($e->getMessage());
			exit(0);
		}
	}

	// Tests

	public function test_Grab_Vars() {
		$demo = new Demo();
		$this->assertEquals(array("key", "value"), $demo->fields());
		$demo->test = "";
		$this->assertEquals(array("key", "value", "test"), $demo->fields());
		unset($demo->test);
		$this->assertEquals(array("key", "value"), $demo->fields());
	}

	public function test_Create() {
		$demo = new Demo();
		$demo->key = "Test";
		$demo->value = "Testing here!";
		$this->assertTrue($demo->create());
		$demo->shouldnt_be_here = "Test";
		$this->assertTrue(is_array($demo->create()));
	}

}
