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

		// Check actual DB
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null", "primary_key"), $fields[0]['flags']);
	}

	public function test_AddField() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check fields
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("value", $fields[1]['name']);

		$object->beginMigration();
		$object->addField("another_value", "varchar(225)");
		$this->assertTrue($object->endMigration());

		// Check fields
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertTrue(isset($fields[2]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("value", $fields[1]['name']);
		$this->assertEquals("another_value", $fields[2]['name']);
		$this->assertEquals(11, $fields[0]['len']);
	}

	public function test_RemoveField() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check fields
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("value", $fields[1]['name']);

		$object->beginMigration();
		$object->removeField("value");
		$this->assertTrue($object->endMigration());

		// Check fields
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(!isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
	}

	public function test_RenameField() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check fields
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("value", $fields[1]['name']);

		$object->beginMigration();
		$object->renameField("value", "new");
		$this->assertTrue($object->endMigration());

		// Check fields
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("new", $fields[1]['name']);
	}

	public function test_SetPrimary() {
		$object = new SkylarK\Fizz\Util\FizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null"), $fields[0]['flags']);

		// Do migration
		$object->beginMigration();
		$object->setPrimary("key", true);
		$this->assertTrue($object->endMigration());

		// Check actual DB
		$fields = $object->getActualFields();
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null", "primary_key"), $fields[0]['flags']);
	}

	// -----------------------------------------------------------------------------------------
	// Operation Tests
	// -----------------------------------------------------------------------------------------

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