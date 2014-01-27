<?php

class IntrospectTest extends PHPUnit_Framework_TestCase
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
		$object = new SkylarK\Fizz\Util\FizzMigrate("IExample");
		$object->addField("key", "int(11)");
		$object->addField("val", "varchar(26)");
		$object->commit();
	}

	public static function tearDownAfterClass() {
		SkylarK\Fizz\Util\FizzOps::drop("IExample");
	}

	public function test_getTables() {
		$obj = new SkylarK\Fizz\Util\Introspect();
		$result = $obj->getTables();
		$this->assertTrue(count($result) > 0);
		$this->assertTrue(count(array_search("IExample", $result)) > 0);
	}

	public function test_getMeta() {
		$obj = new SkylarK\Fizz\Util\Introspect();
		$result = $obj->getMeta("IExample");
		$this->assertEquals(2, count($result));
		$this->assertEquals("key", $result[0]["name"]);
		$this->assertEquals("val", $result[1]["name"]);
		$this->assertEquals("int(11)", $result[0]["type"]);
		$this->assertEquals("varchar(26)", $result[1]["type"]);
	}

	public function test_saveModels() {
		$obj = new SkylarK\Fizz\Util\Introspect();
		$foldername = "/tmp/FizzIntrospectorTest";

		if (!file_exists($foldername)) {
			mkdir($foldername);
		}

		$obj->saveModels($foldername);
		$this->assertEquals(1, count(glob("$foldername/*.php")));
	}
}