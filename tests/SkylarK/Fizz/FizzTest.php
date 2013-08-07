<?php

class Demo extends SkylarK\Fizz\Fizz
{
	public $key;
	public $value;
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
		$this->assertEqual(array("key", "value"), self::$_fizz->fields());
	}

	//public function test_Table_Install() {
		//$this->assertTrue(self::$_fizz->install());
	//}
}