<?php

class TestableFizzMigrate extends SkylarK\Fizz\Util\FizzMigrate
{
	public function call($func) {
		return $this->$func();
	}
}

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
		$object = new TestableFizzMigrate("Example");
		$object->op_drop();
	}

	// -----------------------------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------------------------

	public function test_CommitFailsWithoutFields() {
		$object = new TestableFizzMigrate("Example");
		$this->assertFalse($object->commit());
	}

	public function test_GetDatabase() {
		$object = new TestableFizzMigrate("Example");
		$this->assertEquals("testdb", $object->call("_getDatabase"));
	}

	public function test_Commit() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());
	}

	public function test_GetComment() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertEquals("1", $object->call("_getComment"));
	}

	public function test_PrimaryKeyCommit() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$object->setPrimary("key", true);
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null", "primary_key"), $fields[0]['flags']);
	}

	public function test_AddField() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check fields
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("value", $fields[1]['name']);

		$object->beginMigration();
		$object->addField("another_value", "varchar(225)");
		$this->assertTrue($object->endMigration());

		// Check fields
		$fields = $object->call("_getActualFields");
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
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check fields
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("value", $fields[1]['name']);

		$object->beginMigration();
		$object->removeField("value");
		$this->assertTrue($object->endMigration());

		// Check fields
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(!isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
	}

	public function test_RenameField() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check fields
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("value", $fields[1]['name']);

		$object->beginMigration();
		$object->renameField("value", "new");
		$this->assertTrue($object->endMigration());

		// Check fields
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals("key", $fields[0]['name']);
		$this->assertEquals("new", $fields[1]['name']);
	}

	public function test_SetPrimary() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null"), $fields[0]['flags']);

		// Do migration
		$object->beginMigration();
		$object->setPrimary("key", true);
		$this->assertTrue($object->endMigration());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null", "primary_key"), $fields[0]['flags']);
	}

	public function test_SetIndex() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null"), $fields[0]['flags']);

		// Do migration
		$object->beginMigration();
		$object->setIndex("key", true);
		$this->assertTrue($object->endMigration());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertEquals(array("not_null", "multiple_key"), $fields[0]['flags']);
	}

	public function test_SetUnique() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$object->addField("key2", "int(11)");
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertTrue(isset($fields[2]));
		$this->assertEquals(array("not_null"), $fields[0]['flags']);
		$this->assertEquals(array("not_null"), $fields[2]['flags']);

		// Do migration
		$object->beginMigration();
		$object->setUnique("key", true);
		$object->setUnique("key2", true);
		$this->assertTrue($object->endMigration());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertTrue(isset($fields[1]));
		$this->assertTrue(isset($fields[2]));
		$this->assertEquals(array("not_null", "primary_key"), $fields[0]['flags']); // The first unique is a PKey
		$this->assertEquals(array("not_null", "unique_key"), $fields[2]['flags']);
	}

	public function test_ReturnMigrations() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());

		// Do migration
		$object->beginMigration();
		$object->addField("extra", "varchar(325)");
		$this->assertTrue($object->endMigration());

		// Reset the object to ensure it doesnt run unnecessary migrations
		//$object = new TestableFizzMigrate("Example");
		//$object->addField("key", "int(11)");
		//$object->addField("value", "varchar(125)");
		//$this->assertTrue($object->commit());

		// Do migration
		//$object->beginMigration();
		//$object->addField("extra", "varchar(325)");
		//$this->assertTrue($object->endMigration());
	}

	// -----------------------------------------------------------------------------------------
	// Operation Tests
	// -----------------------------------------------------------------------------------------

	public function test_Truncate() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertTrue($object->op_truncate());
	}

	public function test_Optimize() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertTrue($object->op_optimize());
	}

	public function test_Flush() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertTrue($object->op_truncate());
	}
}