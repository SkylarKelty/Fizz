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
	protected $_table;
	/** Track our version */
	protected $_version;
	/** Track our database version */
	protected $_table_version;
	/** Track our fields */
	private $_fields;
	/** Our PDO instance */
	protected $_pdo;
	/** Our error stack */
	protected $_errors;
	/** Our operation stack */
	private $_operations;

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
		$this->_version = 0;
		$this->_table_version = 0;
		$this->_fields = array();
		$this->_errors = array();
		$this->_pdo = \SkylarK\Fizz\FizzConfig::getDB();
		if (!$this->_pdo) {
			throw new Exceptions\FizzDatabaseConnectionException("Could not connect to Database");
		}
	}

	/**
	 * Returns any errors
	 */
	public function getErrors() {
		return $this->_errors;
	}

	/**
	 * Begin a new set of migrations
	 */
	public function beginMigration() {
		if ($this->_version == 0) {
			$this->commit();
		}
		$this->_version++;
		$this->_operations = array();
	}

	/**
	 * End a set of migrations.
	 * @return boolean True if we committed any actions, or had nothing to do. False if there was an error.
	 */
	public function endMigration() {
		$result = true;
		if ($this->_version > $this->_table_version) {
			$result = $this->commit();
		}
		$this->_operations = array();
		return $result;
	}

	/**
	 * Commit chanegs to DB
	 */
	public function commit() {
		// Bail out if we have no fields
		if (count($this->_fields) === 0) {
			$this->_errors[] = "No fields set whilst trying to commit";
			return false;
		}

		// If we are at version 0, create the table or update the table version info
		if ($this->_version == 0) {
			// Also check if the table exists
			if (!$this->_exists()) {
				// Create the table
				if ($this->create() === false) {
					$error = $this->_pdo->errorInfo();
					$this->_errors[] = "Failed to create database! Reason given: " . $error[2];
					return false;
				}

				// Increment version to alert beginMigration(...) to the fact we have committed the initial schema.
				$this->_version = 1;
			} else {
				// Obtain the table version
				$comment = $this->_getComment();
				if ($comment === false) {
					$this->_errors[] = "Commit failed: could not find comment";
					return false;
				}
				$this->_table_version = intval($comment);
				// We have nothing to do here
				return true;
			}
		}

		// Run through operations
		if (count($this->_operations) > 0) {
			// Begin a transaction
			if (!$this->_pdo->beginTransaction()) {
				$error = $this->_pdo->errorInfo();
				$this->_errors[] = "Could not begin a transaction! Reason given: " . $error[2];
				return false;
			}

			// Attempt all operations
			foreach ($this->_operations as $operation) {
				if ($this->_pdo->exec($operation) === false) {
					$error = $this->_pdo->errorInfo();
					$this->_errors[] = "Operation Failed: '" . $operation . "' Reason given: " . $error[2];
					$this->_pdo->rollBack();
					return false;
				}
			}

			// Commit all the ops above
			$this->_pdo->commit();

			// Reset operations
			$this->_operations = array();
		}

		// Set the table comment
		$this->_comment($this->_version);

		// Set the tracking version
		$this->_table_version = $this->_version;

		return true;
	}

	/**
	 * Add a new field to this model
	 * 
	 * @param string $name The name of the field
	 * @param string $type SQL Type
	 * @param boolean $null Can this column be null? (Default: no)
	 */
	public function addField($name, $type, $null = false) {
		$this->_fields[$name] = array("type" => $type, "null" => $null);
		if ($this->_version > 0) {
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` ADD " . $this->_getFieldSQL($name, $this->_fields[$name]);
		}
	}

	/**
	 * Remove a field from this model
	 * 
	 * @param string $name The name of the field
	 */
	public function removeField($name) {
		unset($this->_fields[$name]);
		if ($this->_version > 0) {
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` DROP `" . $name . "`";
		}
	}

	/**
	 * Rename a field from this model
	 * 
	 * @param string $name The name of the field
	 * @param string $new_name The new name of the field
	 */
	public function renameField($name, $newName) {
		$this->_fields[$newName] = $this->_fields[$name];
		unset($this->_fields[$name]);
		if ($this->_version > 0) {
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` CHANGE `" . $name . "` " . $this->_getFieldSQL($newName, $this->_fields[$newName]);
		}
	}

	/**
	 * Resize a field
	 * 
	 * @param string  $name The name of the field
	 * @param integer $size The new size of the field
	 */
	public function resizeField($name, $size) {
		$currentType = $this->_fields[$name]['type'];

		// Work out the new type
		if (strpos($currentType, "(")) {
			$newType = preg_replace("/\([0-9\s]*\)/", "(".$size.")", $currentType);
		} else {
			$newType = $currentType . "(" . $size . ")";
		}
		

		$this->_fields[$name]['type'] = $newType;

		if ($this->_version > 0) {
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` CHANGE `" . $name . "` " . $this->_getFieldSQL($name, $this->_fields[$name]);
		}
	}

	/**
	 * Turn a field into a primary key, or remove a primary key
	 * 
	 * @param string  $name  The name of the field
	 * @param boolean $value True if this field should be a primary key, false if not
	 */
	public function setPrimary($name, $value = true) {
		if ($value) {
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` ADD PRIMARY KEY (  `" . $name . "` )";
		} else {
			$this->_operations[] = "ALTER TABLE `" . $this->_table . "` DROP PRIMARY KEY";
		}
	}

	/**
	 * Turn a field into an index, or remove an index
	 * 
	 * @param string  $name  The name of the field
	 * @param boolean $value True if this field should be an index, false if not
	 */
	public function setIndex($name, $value = true) {
		if ($value) {
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` ADD INDEX (`" . $name . "`)";
		} else {
			$this->_operations[] = "ALTER TABLE `" . $this->_table . "` DROP INDEX `" . $name . "`";
		}
	}

	/**
	 * Make a field Unique, or not
	 * 
	 * @param string  $name  The name of the field
	 * @param boolean $value True if this field should be unique, false if not
	 */
	public function setUnique($name, $value = true) {
		if ($value) {
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` ADD UNIQUE (`" . $name . "`)";
		} else {
			$this->_operations[] = "ALTER TABLE `" . $this->_table . "` DROP UNIQUE `" . $name . "`";
		}
	}

	/**
	 * Create the table, internal use only
	 */
	protected function create() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->_table . "` (";

		// Build fields
		$fields = array();
		foreach ($this->_fields as $name => $data) {
			$fields[] = $this->_getFieldSQL($name, $data);
		}

		$sql .= implode(",", $fields);
		$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		return $this->_pdo->exec($sql) !== false;
	}

	/**
	 * Comment a table, internal use only
	 */
	protected function _comment($comment) {
		return $this->_pdo->exec("ALTER TABLE `" . $this->_table . "` COMMENT = '" . $comment . "'");
	}

	// -----------------------------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------------------------

	/**
	 * Does this table exist?
	 */
	protected function _exists() {
		$q = $this->_pdo->query("SELECT 1 FROM `" . $this->_table . "`");
		if ($q === false) {
			return false;
		}
		$q->closeCursor();
		return true;
	}

	/**
	 * Returns SQL for a given field
	 */
	protected function _getFieldSQL($name, $data) {
		return "`" . $name . "` " . $data['type'] . " " . ($data['null'] ? "DEFAULT NULL" : "NOT NULL");
	}
	
	/**
	 * Returns a list of fields currently in the database
	 */
	protected function _getActualFields() {
		$statement = $this->_pdo->query("SELECT * FROM `" . $this->_table . "` LIMIT 1");

		$columns = array();
		// Grab column meta
		$column_count = $statement->columnCount();
		for ($i = 0; $i < $column_count; $i++) {
			$columns[] = $statement->getColumnMeta($i);
		}

		$result = $statement->fetchAll(); // Clear out

		return $columns;
	}

	/**
	 * Get a table's comment
	 */
	protected function _getDatabase() {
		$q = $this->_pdo->query("SELECT DATABASE() AS name;");
		if ($q === false) {
			$error = $this->_pdo->errorInfo();
			$this->_errors[] = "_getDatabase Failed! Reason given: " . $error[2];
			return false;
		}
		$q = $q->fetchAll();
		return $q[0]["name"];
	}

	/**
	 * Get a table's comment
	 */
	protected function _getComment() {
		$db = $this->_getDatabase();
		if ($db === false) {
			return false;
		}

		// Find our version
		$sql = "SELECT table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_schema='" . $db . "' AND table_name='" . $this->_table . "'";
		$q = $this->_pdo->query($sql);
		if ($q === false) {
			$error = $this->_pdo->errorInfo();
			$this->_errors[] = "_getComment Failed: '" . $sql . "' Reason given: " . $error[2];
			return false;
		}

		$q = $q->fetchAll();
		return $q[0]["table_comment"];
	}

	// -----------------------------------------------------------------------------------------
	// Table Operations
	// -----------------------------------------------------------------------------------------
	
	/**
	 * Operation Helper
	 */
	protected function _operation($sql) {
		if ($this->_pdo->exec($sql) === false) {
			$error = $this->_pdo->errorInfo();
			$this->_errors[] = "Failed to truncate database! Reason given: " . $error[2];
			return false;
		}
		return true;
	}

	/**
	 * Truncate the table.
	 * This command is not queued and is committed straight away
	 */
	public function op_truncate() {
		return $this->_operation("TRUNCATE TABLE `" . $this->_table . "`");
	}

	/**
	 * Drop the table.
	 * This command is not queued and is committed straight away
	 */
	public function op_drop() {
		return $this->_operation("DROP TABLE IF EXISTS `" . $this->_table . "`");
	}

	/**
	 * Optimize the table.
	 * This command is not queued and is committed straight away
	 */
	public function op_optimize() {
		return $this->_operation("OPTIMIZE TABLE `" . $this->_table . "`");
	}

	/**
	 * Flush the table.
	 * This command is not queued and is committed straight away
	 */
	public function op_flush() {
		return $this->_operation("FLUSH TABLE `" . $this->_table . "`");
	}
}
