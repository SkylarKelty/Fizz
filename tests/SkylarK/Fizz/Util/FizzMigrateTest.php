<?php

class TestableFizzMigrate extends SkylarK\Fizz\Util\FizzMigrate
{
	public function __construct($tableName, $errorMode = 0) {
		parent::__construct($tableName, self::$ERROR_MODE_PRINT);
	}

	public function call($func, $args = array()) {
		return call_user_func_array(array($this, $func), $args);
	}

	public function resetVersion() {
		$this->_version = 0;
		$this->_table_version = 0;
	}
}

class FizzMigrateTest extends PHPUnit_Framework_TestCase
{
	private $_introspector;

	public function setUp() {
		$this->_introspector = new SkylarK\Fizz\Util\Introspect();

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
		SkylarK\Fizz\Util\FizzOps::drop("Example");

	}

	public static function tearDownAfterClass() {
		SkylarK\Fizz\Util\FizzOps::drop("Example");
	}

	// -----------------------------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------------------------

	public function test_CommitFailsWithoutFields() {
		$object = new TestableFizzMigrate("Example");
		ob_start();
		$this->assertFalse($object->commit());
		ob_get_clean();
	}

	public function test_Commit() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());
	}

	public function test_Exists() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertFalse($object->call("_exists"));
		$this->assertTrue($object->commit());
		$this->assertTrue($object->call("_exists"));
	}

	public function test_GetComment() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());
		$this->assertEquals("1", $object->call("_getComment"));
	}

	public function test_GetTrueLength() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$object->addField("value", "varchar(125)");
		$this->assertTrue($object->commit());
		$this->assertEquals(125, $object->call("_getTrueLength", array("value")));
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
		$this->assertEquals(11, $fields[0]['truelength']);
		$this->assertEquals(125, $fields[1]['truelength']);
		$this->assertEquals(225, $fields[2]['truelength']);
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

	public function test_Resize() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)");
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertEquals(11, $fields[0]['truelength']);

		// Do migration
		$object->beginMigration();
		$object->resizeField("key", 22);
		$this->assertTrue($object->endMigration());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertEquals(22, $fields[0]['truelength']);
	}

	public function test_ResizeWithNoCurrentSize() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int");
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertEquals(11, $fields[0]['truelength']);

		// Do migration
		$object->beginMigration();
		$object->resizeField("key", 22);
		$this->assertTrue($object->endMigration());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertEquals(22, $fields[0]['truelength']);
	}

	public function test_Retype() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int");
		$this->assertTrue($object->commit());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertEquals("LONG", $fields[0]['native_type']);

		// Do migration
		$object->beginMigration();
		$object->retypeField("key", "bigint(11)");
		$this->assertTrue($object->endMigration());

		// Check actual DB
		$fields = $object->call("_getActualFields");
		$this->assertTrue(is_array($fields));
		$this->assertTrue(isset($fields[0]));
		$this->assertEquals("LONGLONG", $fields[0]['native_type']);
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
		$object->resetVersion();
		$this->assertTrue($object->commit());
		$object->beginMigration();
		$object->addField("extra", "varchar(325)");
		$this->assertTrue($object->endMigration());
	}

	public function test_InitialPK() {
		// This is somewhat redundant but meh.
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)", false, array("AUTO_INCREMENT"));
		$object->addField("value", "varchar(125)");
		$object->setPrimary("key");
		$this->assertTrue($object->commit());
	}

	public function test_Attrs() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)", false, array("AUTO_INCREMENT"));
		$object->addField("value", "varchar(125)");
		$object->setPrimary("key");
		$this->assertTrue($object->commit());
		$object->removeAttribute("value", "NULL");
		$object->addAttribute("value", "NOT NULL");
		$this->assertTrue($object->commit());

		// Check actual DB
		$meta = $this->_introspector->getMeta("Example");
		$this->assertEquals("auto_increment", $meta[0]['extra']);
		$this->assertEquals("NO", $meta[1]['null']);
	}

	public function test_AggregateKeys() {
		$object = new TestableFizzMigrate("Example");
		$object->addField("key", "int(11)", false, array("AUTO_INCREMENT"));
		$object->addField("value", "varchar(125)");
		$object->addField("value2", "varchar(125)");
		$object->addField("value3", "varchar(225)");
		$object->setPrimary("key");
		$object->setIndex(array("value", "value2"));
		$this->assertTrue($object->commit());
		$object->setIndex(array("value2", "value3"));
		$this->assertTrue($object->commit());
	}
}