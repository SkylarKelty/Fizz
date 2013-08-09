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
	/** Track our fields */
	private $_fields;
	/** Our PDO instance */
	private $_pdo;

	/**
	 * Construct a new migrations object.
	 *
	 * A migration takes the following form:
	 *
	 * // First, create the start object
	 * $object = new FizzMigrate("MetaData");
	 * $object->addField("key", "varchar(255)");
	 * $object->addField("value", "varchar(255)");
	 * $object->setPrimary("key", true);
	 * $object->setUnique("value", true);
	 * 
	 * // Commit changes to DB
	 * $object->commit();
	 *
	 * // Then list your changes
	 * $object->beginMigration();
	 * $object->setUnique("value", false);
	 * $object->endMigration();
	 *
	 * // More changes later
	 * $object->beginMigration();
	 * $object->retype("value", "text");
	 * $object->endMigration();
	 * 
	 * @param string $tableName The name of the table we relate too
	 * @param string $schemaVersion The version of the schema we are working on. Important for upgrades
	 */
	public function __construct($tableName) {
		$this->_table = $tableName;
		$this->_version = 1;
		$this->_fields = array();
		$this->_pdo = \SkylarK\Fizz\FizzConfig::getDB();
		if (!$this->_pdo) {
			throw new Exceptions\FizzDatabaseConnectionException("Could not connect to Database");
		}
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
	public function endMigration() {
		$this->commit();
	}

	/**
	 * Add a new field to this model
	 * 
	 * @param string $name The name of the field
	 * @param string $type SQL Type
	 */
	public function addField($name, $type) {
		$this->_fields[$name] = array("type" => $type);
	}

	/**
	 * Commit chanegs to DB
	 */
	public function commit() {
		$this->comment($this->_version);
		return true;
	}

	/**
	 * Turn a field into a primary key, or remove one
	 * 
	 * @param string  $name  The name of the field
	 * @param boolean $value True if this field should be a primary key, false if not
	 */
	public function setPrimary($name, $value = true) {
		// ALTER TABLE Demo DROP PRIMARY KEY
		// if ($value) {
		// 	ALTER TABLE  `Demo` ADD PRIMARY KEY (  `key` )
		// }
	}

	/**
	 * Truncate the table
	 */
	public function truncate() {
		return $this->_pdo->exec("TRUNCATE TABLE `" . $this->_table . "`");
	}

	/**
	 * Drop the table
	 */
	public function drop() {
		return $this->_pdo->exec("DROP TABLE `" . $this->_table . "`");
	}

	/**
	 * Comment a table, internal use only
	 */
	private function comment($comment) {
		return $this->_pdo->exec("ALTER TABLE `" . $this->_table . "` COMMENT = '" . $comment . "'");
	}
}

/**
 * Queries to refer too:
 * `ALTER TABLE Demo DROP PRIMARY KEY`
 * ALTER TABLE  `Demo` ADD PRIMARY KEY (  `key` )
 * ALTER TABLE  `Demo` ADD INDEX (  `value` ) ;
 * `ALTER TABLE Demo DROP INDEX key`
 * ALTER TABLE  `Demo` ADD UNIQUE (`key`)
 */