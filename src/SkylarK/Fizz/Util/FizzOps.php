<?php
/**
 * Fizz is a lightweight ORM
 *
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace SkylarK\Fizz\Util;

/**
 * FizzOps can be used to manage operations on database schemas
 */
class FizzOps
{
	/**
	 * Operation Helper
	 */
	protected static function _operation($sql) {
		$pdo = \SkylarK\Fizz\FizzConfig::getDB();
		if (!$pdo) {
			throw new Exceptions\FizzDatabaseConnectionException("Could not connect to Database");
		}

		if ($pdo->exec($sql) === false) {
			$error = $pdo->errorInfo();
			return "Failed to truncate database! Reason given: " . $error[2];
		}

		return true;
	}

	/**
	 * Truncate a table.
	 *
	 * @param string $table The name of the table to run the OP on
	 */
	public static function truncate($table) {
		return self::_operation("TRUNCATE TABLE `" . $table . "`");
	}

	/**
	 * Drop a table.
	 *
	 * @param string $table The name of the table to run the OP on
	 */
	public static function drop($table) {
		return self::_operation("DROP TABLE IF EXISTS `" . $table . "`");
	}

	/**
	 * Optimize a table.
	 *
	 * @param string $table The name of the table to run the OP on
	 */
	public static function optimize($table) {
		return self::_operation("OPTIMIZE TABLE `" . $table . "`");
	}

	/**
	 * Flush a table.
	 *
	 * @param string $table The name of the table to run the OP on
	 */
	public static function flush($table) {
		return self::_operation("FLUSH TABLE `" . $table . "`");
	}

	/**
	 * Rename a table.
	 *
	 * @param string $from The name of the table to run the OP on
	 * @param string $to   The new name of the table
	 */
	public static function rename($from, $to) {
		return self::_operation("RENAME TABLE `" . $from . "` TO `" . $to . "`");
	}
}