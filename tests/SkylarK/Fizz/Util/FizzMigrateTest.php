<?php
class FizzMigrateTest extends PHPUnit_Framework_TestCase
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

		// Drop each test
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->op_drop();
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
		$this->assertTrue($object->commit());
	}

	public function test_PrimaryKeyCommit() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$object->setPrimary("key", true);
		$this->assertTrue($object->commit());
	}

	public function test_Transaction() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());
		$object->beginMigration();
		$object->setPrimary("key", true);
		$this->assertTrue($object->endMigration());
	}

	public function test_Truncate() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertTrue($object->op_truncate());
	}

	public function test_Optimize() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertTrue($object->op_optimize());
	}

	public function test_Flush() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertTrue($object->op_truncate());
	}
}