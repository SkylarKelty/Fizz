<?php
/**
 * Fizz is a lightweight ORM
 *
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace SkylarK\Fizz\Util;

/**
 * FizzMigrate can be used to handle database schemas
 */
class FizzMigrate
{
	/** Store our table name */
	private $_table;
	/** Track our version */
	private $_version;

	/**
	 * Construct a new migrations object.
	 *
	 * A migration takes the following form:
	 *
	 * // First, create the start object
	 * $object = new FizzMigrate("MetaData");
	 * $object->addField("key", "varchar", 250, true, true);
	 * $object->addField("value", "varchar", 250, true, false);
	 *
	 * // Then list your changes
	 * $object->beginMigration();
	 * $object->retype("value", "text");
	 * $object->resize("value", NULL);
	 * $object->endMigration();
	 *
	 * // More changes later
	 * $object->beginMigration();
	 * $object->removePrimaryKey("value");
	 * $object->endMigration();
	 * 
	 * @param string $tableName The name of the table we relate too
	 * @param string $schemaVersion The version of the schema we are working on. Important for upgrades
	 */
	public function __construct($tableName) {
		$this->_table = $tableName;
		$this->_version = 0;
	}

	/**
	 * Begin a new set of migrations
	 */
	public function beginMigration() {
		$this->_version++;
	}

	/**
	 * End a set of migrations
	 */
	public function beginMigration() {
		// -
	}
}