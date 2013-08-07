<?php

class Demo extends SkylarK\Fizz\Fizz
{
	public $_ignore_me;
	public $key;
	public $value;

	public function toggleVar() {
		if (isset($this->test)) {
			unset($this->test);
		} else {
			$this->test = '';
		}
		$this->_fizz_updateFields();
	}
}

class FizzTest extends PHPUnit_Framework_TestCase
{
	private static $_fizz;

	public static function setUpBeforeClass() {
		try {
			self::$_fizz = new Demo("mysql:dbname=testdb;host=127.0.0.1", "travis", "");
		}
		catch (PDOException $e) {
			die($e->getMessage());
			exit(0);
		}
	}

	public static function tearDownAfterClass() {
		self::$_fizz = NULL;
	}

	// Tests

	public function test_Grab_Vars() {
		$this->assertEquals(array("key", "value"), self::$_fizz->fields());
		self::$_fizz->toggleVar();
		$this->assertEquals(array("key", "value", "test"), self::$_fizz->fields());
		self::$_fizz->toggleVar();
		$this->assertEquals(array("key", "value"), self::$_fizz->fields());
	}

}
