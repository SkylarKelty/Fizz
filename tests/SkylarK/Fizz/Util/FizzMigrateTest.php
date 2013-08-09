<?php
class FizzMigrateTest extends PHPUnit_Framework_TestCase
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

	public function setUp() {
		// Drop each test
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->drop();
	}

	// -----------------------------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------------------------

	public function test_CommitFailsWithoutFields() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$this->assertFalse($object->commit());
	}

	public function test_Commit() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$object->setPrimary("key", true);
		$this->assertTrue($object->commit());
	}
}