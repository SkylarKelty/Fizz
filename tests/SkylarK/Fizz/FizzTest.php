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

	public function test_Tablename() {
		$demo = new Demo();
		$this->assertEquals("Demo", $demo->tablename());
	}

	public function test_Grab_Vars() {
		$demo = new Demo();
		$this->assertEquals(array("key", "value"), $demo->_fizz_fields());
		$demo->test = "";
		$this->assertEquals(array("key", "value", "test"), $demo->_fizz_fields());
		unset($demo->test);
		$this->assertEquals(array("key", "value"), $demo->_fizz_fields());
	}

	public function test_Create() {
		$demo = new Demo();
		$demo->key = "Test";
		$demo->value = "Testing here!";
		$this->assertTrue($demo->create());
		$demo->shouldnt_be_here = "Test";
		$this->assertTrue(is_array($demo->create()));
	}

	public function test_Update() {
		$demo = new Demo();
		$demo->key = "Test";
		$demo->value = "Testing here!";
		$this->assertTrue($demo->create());
		$this->assertTrue($demo->update(array("value" => "Testing here! Look away!")));
		$this->assertEquals($demo->value, "Testing here! Look away!");
	}

	public function test_All() {
		$demo = new Demo();
		$demo->key = "Test";
		$demo->value = "Testing here!";
		$this->assertTrue($demo->create());
		$demo->key = "Test2";
		$this->assertTrue($demo->create());

		$objs = Demo::all();
		$this->assertTrue(isset($objs[0]));
		$this->assertTrue(isset($objs[1]));
		$this->assertEquals($objs[0]->key, "Test");
		$this->assertEquals($objs[0]->value, "Testing here!");
		$this->assertEquals($objs[1]->key, "Test2");
		$this->assertEquals($objs[1]->value, "Testing here!");
	}

}
