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
	public function setUp() {
		// Although it seems horribly inefficient, it ensures each test is clean
		// and fresh. It's for tests, so accuracy > efficiency
		try {
			SkylarK\Fizz\FizzConfig::setDB("mysql:dbname=testdb;host=127.0.0.1", "travis", "");
		}
		catch (PDOException $e) {
			die($e->getMessage());
			exit(0);
		}

		// Drop each test's table
		SkylarK\Fizz\Util\FizzOps::drop("Demo");

		// Create table
		$object = new SkylarK\Fizz\Util\FizzMigrate("Demo");
		$object->addField("key", "varchar(125)");
		$object->addField("value", "text");
		$this->assertTrue($object->commit());
	}

	public static function tearDownAfterClass() {
		SkylarK\Fizz\Util\FizzOps::drop("Demo");
	}

	// -----------------------------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------------------------

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

	public function test_Find() {
		$demo = new Demo();
		$demo->key = "Test";
		$demo->value = "Testing here!";
		$this->assertTrue($demo->create());
		$demo->key = "Test2";
		$this->assertTrue($demo->create());

		$objs = Demo::find(array("key" => "Test2"));
		$this->assertTrue(isset($objs[0]));
		$this->assertFalse(isset($objs[1]));
		$this->assertEquals($objs[0]->key, "Test2");
		$this->assertEquals($objs[0]->value, "Testing here!");
	}

}
