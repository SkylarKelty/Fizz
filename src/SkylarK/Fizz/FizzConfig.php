<?php
/**
 * Fizz is a lightweight ORM
 *
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace SkylarK\Fizz;

/**
 * The config store all Fizz models rely on
 */
class FizzConfig
{
	/** Our PDO instance */
	private static $_pdo;

	/**
	 * Initialize Fizz with valid database connection details
	 * 
	 * @param string $db_dsn A PDO connection string
	 * @param string $db_username A PDO connection username
	 * @param string $db_password A PDO connection password
	 */
	public static function setDB($db_dsn, $db_username = NULL, $db_password = NULL) {
		self::$_pdo = new \PDO($db_dsn, $db_username, $db_password);
	}

	/**
	 * Returns our DB
	 */
	public static function getDB() {
		return self::$_pdo;
	}
}