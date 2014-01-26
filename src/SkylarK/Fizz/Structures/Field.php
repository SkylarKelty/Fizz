<?php
/**
 * Fizz is a lightweight ORM
 *
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace SkylarK\Fizz\Structures;

/**
 * This class stores data for fields
 */
class Field
{
	/** The name of the field. */
	private $_name;

	/** The type of the field. */
	private $_type;

	/** List of attributes */
	private $_attrs;

	/**
	 * Constructor
	 */
	public function __construct($name, $type, $attrs = array()) {
		$this->_name = $name;
		$this->_type = $type;

		if (!is_array($attrs)) {
			throw new \Exception("\$attrs must be an array!");
		}

		$this->_attrs = $attrs;
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
	 * Get the attrs of this field
	 */
	public function getAttrs() {
		return $this->_attrs;
	}

	/**
	 * Get the data of this field as an associative array
	 */
	public function getData() {
		return array(
			"name" => $this->_name,
			"type" => $this->_type,
			"attrs" => join(' ', $this->_attrs)
		);
	}

	/**
	 * Gets the data required for an update of this field
	 */
	public function getUpdateData($current_data) {
		// TODO
	}

	/**
	 * Get the create SQL and associated data.
	 */
	public function getCreateSQL() {
		$attrs = join(' ', $this->_attrs);
		return "`{$this->_name}` {$this->_type} {$attrs}";
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
		return "ALTER TABLE `{$table}` DROP `{$this->_name}`";
	}

	/**
	 * Get the SQL to change this field's type
	 */
	public function getChangeSQL($table) {
		return "ALTER TABLE `{$table}` CHANGE `{$this->_name}` " . $this->getCreateSQL();
	}

	/**
	 * Get the SQL to rename this field
	 */
	public function getRenameSQL($table, $old_name) {
		return "ALTER TABLE `{$table}` CHANGE `{$old_name}` " . $this->getCreateSQL();
	}
}