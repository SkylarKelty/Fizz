<?php
/**
 * Fizz is a lightweight ORM
 *
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace SkylarK\Fizz\Structures;

/**
 * This class stores data for indexes
 */
class Index
{
	/** The name of the index. */
	private $_name;

	/** The type of the index. */
	private $_type;

	/** List of fields it is attached too */
	private $_fields;

	/**
	 * Constructor
	 */
	public function __construct($type, $fields) {

		if (!is_array($fields)) {
			$fields = array($fields);
		}

		$this->_name = "unique_" . join('_', $fields);
		$this->_type = $type;
		$this->_fields = $fields;
	}

	/**
	 * Get the name of this field
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * Sets the name of this field
	 */
	public function setName($name) {
		$this->_name = $name;
	}

	/**
	 * Get the type of this field
	 */
	public function getType() {
		return $this->_type;
	}

	/**
	 * Sets the type of this field
	 */
	public function setType($type) {
		$this->_type = $type;
	}

	/**
	 * Get the fields of this field
	 */
	public function getFields() {
		return $this->_fields;
	}

	/**
	 * Get the create SQL and associated data.
	 */
	public function getCreateSQL() {
		$fields = join('`,`', $this->_fields);
		return "{$this->_type} {$this->_name} (`{$fields}`)";
	}

	/**
	 * Get the SQL to create this field
	 */
	public function getAddSQL($table) {
		return "ALTER TABLE `{$table}` ADD " . $this->getCreateSQL();
	}

	/**
	 * Get the SQL to drop this field
	 */
	public function getDropSQL($table) {
		return "ALTER TABLE `{$table}` DROP {$this->_type} `{$this->_name}`";
	}
}