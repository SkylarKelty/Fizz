<?php

class FizzOpsTest extends PHPUnit_Framework_TestCase
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

		// Create a new table for each test
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->commit();
	}

	public function test_Truncate() {
		$this->assertTrue(SkylarK\Fizz\Util\FizzOps::truncate("Example"));
	}

	public function test_Optimize() {
		$this->assertTrue(SkylarK\Fizz\Util\FizzOps::optimize("Example"));
	}

	public function test_Flush() {
		$this->assertTrue(SkylarK\Fizz\Util\FizzOps::truncate("Example"));
	}

	public function test_Drop() {
		$this->assertTrue(SkylarK\Fizz\Util\FizzOps::drop("Example"));
	}

	public function test_Rename() {
		$this->assertTrue(SkylarK\Fizz\Util\FizzOps::rename("Example", "ExampleRename"));
	}
}