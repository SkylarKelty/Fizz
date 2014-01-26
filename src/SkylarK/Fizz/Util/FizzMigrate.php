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
	/** Error mode 1: store */
	public static $ERROR_MODE_STORE = 0;
	/** Error mode 2: print */
	public static $ERROR_MODE_PRINT = 1;
	/** Error mode 3: die */
	public static $ERROR_MODE_DIE = 2;

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
	/** Our error mode */
	protected $_error_mode;
	/** Our operation stack */
	private $_operations;
	/** Index stack */
	private $_indexes;

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
	 * @param string $errorMode The error mode we should use (ERROR_MODE_STORE, ERROR_MODE_PRINT, ERROR_MODE_DIE)
	 */
	public function __construct($tableName, $errorMode = 0) {
		$this->_table = $tableName;
		$this->_version = 0;
		$this->_table_version = 0;
		$this->_fields = array();
		$this->_indexes = array();
		$this->_errors = array();
		$this->_error_mode = $errorMode;
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
			$this->_error("No fields set whilst trying to commit");
			return false;
		}

		// If we are at version 0, create the table or update the table version info
		if ($this->_version == 0) {
			// Also check if the table exists
			if ($this->_exists()) {
				// Obtain the table version
				$comment = $this->_getComment();
				if ($comment === false) {
					$this->_error("Commit failed: could not find comment");
					return false;
				}
				$this->_table_version = intval($comment);
				// We have nothing to do here
				return true;
			}

			// The table does not exist, create the table
			if ($this->create() === false) {
				$error = $this->_pdo->errorInfo();
				$this->_error("Failed to create database! Reason given: " . $error[2]);
				return false;
			}

			// Increment version to alert beginMigration(...) to the fact we have committed the initial schema.
			$this->_version = 1;
		}

		// Run through operations
		if (count($this->_operations) > 0) {
			// Begin a transaction
			if (!$this->_pdo->beginTransaction()) {
				$error = $this->_pdo->errorInfo();
				$this->_error("Could not begin a transaction! Reason given: " . $error[2]);
				return false;
			}

			// Attempt all operations
			foreach ($this->_operations as $operation) {
				if ($this->_pdo->exec($operation) === false) {
					$error = $this->_pdo->errorInfo();
					$this->_error("Operation Failed: '" . $operation . "' Reason given: " . $error[2]);
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
	 * @param array $extra Additional attributes (e.g. AUTO INCREMENT)
	 */
	public function addField($name, $type, $null = false, $extra = array()) {
		$attrs = $extra;
		$attrs[] = $null ? "NULL" : "NOT NULL";

		$field = new \SkylarK\Fizz\Structures\Field($name, $type, $attrs);

		if ($this->_version > 0) {
			$this->_operations[] = $field->getAddSQL($this->_table);
		}

		$this->_fields[$name] = $field;

		return $field;
	}

	/**
	 * Remove a field from this model
	 * 
	 * @param string $name The name of the field
	 */
	public function removeField($name) {
		$field = $this->_fields[$name];

		if ($this->_version > 0) {
			$this->_operations[] = $field->getDropSQL($this->_table);
		}

		unset($this->_fields[$name]);
	}

	/**
	 * Rename a field from this model
	 * 
	 * @param string $name The name of the field
	 * @param string $new_name The new name of the field
	 */
	public function renameField($name, $newName) {
		$field = $this->_fields[$name];
		$field->setName($newName);

		if ($this->_version > 0) {
			$this->_operations[] = $field->getRenameSQL($this->_table, $name);
		}

		unset($this->_fields[$name]);
		$this->_fields[$newName] = $field;
	}

	/**
	 * Resize a field
	 * 
	 * @param string  $name The name of the field
	 * @param integer $size The new size of the field
	 */
	public function resizeField($name, $size) {
		$field = $this->_fields[$name];
		$currentType = $field->getType();

		// Work out the new type
		if (strpos($currentType, "(")) {
			$newType = preg_replace("/\([0-9\s]*\)/", "(".$size.")", $currentType);
		} else {
			$newType = $currentType . "(" . $size . ")";
		}
		
		$field->setType($newType);

		if ($this->_version > 0) {
			$this->_operations[] = $field->getChangeSQL($this->_table);
		}
	}

	/**
	 * ReType a field
	 * 
	 * @param string $name The name of the field
	 * @param string $type The new type of the field
	 */
	public function retypeField($name, $type) {
		$field = $this->_fields[$name];
		$field->setType($type);

		if ($this->_version > 0) {
			$this->_operations[] = $field->getChangeSQL($this->_table);
		}
	}

	/**
	 * Add an attribute to a field
	 * 
	 * @param string $name The name of the field
	 * @param string $attribute The attribute
	 */
	public function addAttribute($name, $attribute) {
		$field = $this->_fields[$name];
		$field->addAttribute($attribute);

		if ($this->_version > 0) {
			$this->_operations[] = $field->getChangeSQL($this->_table);
		}
	}

	/**
	 * Remove an attribute from a field
	 * 
	 * @param string $name The name of the field. Can also an array of fields.
	 * @param string $attribute The attribute
	 */
	public function removeAttribute($name, $attribute) {
		$field = $this->_fields[$name];
		$field->removeAttribute($attribute);

		if ($this->_version > 0) {
			$this->_operations[] = $field->getChangeSQL($this->_table);
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
			$this->_indexes[$name] = "PRIMARY KEY";
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` ADD PRIMARY KEY (  `" . $name . "` )";
		} else {
			unset($this->_indexes[$name]);
			$this->_operations[] = "ALTER TABLE `" . $this->_table . "` DROP PRIMARY KEY";
		}
	}

	/**
	 * Turn a field into an index, or remove an index
	 * 
	 * @param string  $name  The name of the field. Can also an array of fields.
	 * @param boolean $value True if this field should be an index, false if not
	 */
	public function setIndex($name, $value = true) {
		$name = is_array($name) ? join('`,`', $name) : $name;
		if ($value) {
			$this->_indexes[$name] = "INDEX";
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` ADD INDEX (`" . $name . "`)";
		} else {
			unset($this->_indexes[$name]);
			$this->_operations[] = "ALTER TABLE `" . $this->_table . "` DROP INDEX `" . $name . "`";
		}
	}

	/**
	 * Remove a named index
	 * 
	 * @param string  $name  The name of the field. Can also an array of fields.
	 */
	public function removeIndex($name) {
		$this->setIndex($name, false);
	}

	/**
	 * Make a field Unique, or not
	 * 
	 * @param string  $name  The name of the field. Can also an array of fields.
	 * @param boolean $value True if this field should be unique, false if not
	 */
	public function setUnique($name, $value = true) {
		$name = is_array($name) ? join('`,`', $name) : $name;
		if ($value) {
			$this->_indexes[$name] = "UNIQUE KEY";
			$this->_operations[] = "ALTER TABLE  `" . $this->_table . "` ADD UNIQUE (`" . $name . "`)";
		} else {
			unset($this->_indexes[$name]);
			$this->_operations[] = "ALTER TABLE `" . $this->_table . "` DROP UNIQUE `" . $name . "`";
		}
	}

	/**
	 * Remove a named unique
	 * 
	 * @param string  $name  The name of the field. Can also an array of fields.
	 */
	public function removeUnique($name) {
		$this->setUnique($name, false);
	}

	/**
	 * Create the table, internal use only
	 */
	protected function create() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->_table . "` (";

		// Build fields
		$fields = array();
		foreach ($this->_fields as $name => $field) {
			$fields[] = $field->getCreateSQL();
		}

		// Build indexes into the fields
		foreach ($this->_indexes as $name => $type) {
			switch ($type) {
				case "PRIMARY KEY":
					$fields[] = "PRIMARY KEY (`{$name}`)";
					break;
				case "UNIQUE KEY":
					$fields[] = "UNIQUE KEY `{$name}` (`{$name}`)";
					break;
				case "INDEX":
					$fields[] = "KEY `{$name}` (`{$name}`)";
					break;
			}
		}

		$sql .= implode(",", $fields);
		$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		// Cleanup operations after we create a table for the first time
		$this->_operations = array();

		return $this->_pdo->exec($sql) !== false;
	}

	/**
	 * Comment a table, internal use only
	 */
	protected function _comment($comment) {
		return $this->_pdo->exec("ALTER TABLE `" . $this->_table . "` COMMENT = '" . $comment . "'");
	}

	/**
	 * Handles errors
	 */
	protected function _error($error) {
		switch ($this->_error_mode) {
			case self::$ERROR_MODE_PRINT:
				print $error;
				break;
			case self::$ERROR_MODE_DIE:
				die($error);
				break;
			case self::$ERROR_MODE_STORE:
			default:
				$this->_errors[] = $error;
		}
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
	 * Returns a list of fields currently in the database
	 */
	protected function _getActualFields() {
		$statement = $this->_pdo->query("SELECT * FROM `" . $this->_table . "` LIMIT 1");

		$columns = array();
		// Grab column meta
		$column_count = $statement->columnCount();
		for ($i = 0; $i < $column_count; $i++) {
			$meta = $statement->getColumnMeta($i);
			$length = $this->_getTrueLength($meta['name']);
			$meta['truelength'] = $length === false ? $meta['len'] : $length;
			$columns[] = $meta;
		}

		$statement->fetchAll(); // Clear out

		return $columns;
	}

	/**
	 * Returns the true length of a column
	 */
	protected function _getTrueLength($col) {
		$sql = "SELECT character_maximum_length FROM information_schema.columns WHERE table_name='" . $this->_table . "'  AND table_schema=DATABASE()";
		$sql .= " AND column_name='" . $col . "'";
		$q = $this->_pdo->query($sql);
		
		if ($q === false) {
			$error = $this->_pdo->errorInfo();
			$this->_error("_getComment Failed: '" . $sql . "' Reason given: " . $error[2]);
			return false;
		}

		$q = $q->fetchAll();
		$len = $q[0]["character_maximum_length"];
		return is_numeric($len) ? intval($len) : false;
	}

	/**
	 * Get a table's comment
	 */
	protected function _getComment() {
		// Find our version
		$sql = "SELECT table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_schema=DATABASE() AND table_name='" . $this->_table . "'";
		$q = $this->_pdo->query($sql);
		if ($q === false) {
			$error = $this->_pdo->errorInfo();
			$this->_error("_getComment Failed: '" . $sql . "' Reason given: " . $error[2]);
			return false;
		}

		$q = $q->fetchAll();
		return $q[0]["table_comment"];
	}
}
