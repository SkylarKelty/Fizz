<?php
/**
 * Fizz is a lightweight ORM
 *
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace SkylarK\Fizz\Util;

/**
 * Introspect can be used to grab data from a present database
 */
class Introspect
{
	/** PDO */
	private $_pdo;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_pdo = \SkylarK\Fizz\FizzConfig::getDB();
	}

	/**
	 * Returns a list of tables in the database
	 *
	 * @return array Array of tables
	 */
	public function getTables() {
		$result = array();

		$stmt = $this->_pdo->query("SHOW TABLES");
		foreach ($stmt as $row) {
			$result[] = $row[0];
		}

		return $result;
	}

	/**
	 * Returns an array of column metadata for a given table
	 *
	 * @param string $table The table to inspect
	 * @return array Array of column metadata
	 */
	public function getMeta($table) {
		$result = array();

		$stmt = $this->_pdo->query("DESCRIBE $table");

		foreach ($stmt as $v) {
			$result[] = array(
				"name" => $v['Field'],
				"type" => $v['Type'],
				"null" => $v['Null'],
				"iskey" => $v['Key'],
				"default" => $v['Default'],
				"extra" => $v['Extra']
			);
		}

		return $result;
	}
}