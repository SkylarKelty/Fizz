<?php
/**
 * Fizz is a lightweight ORM
 *
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace SkylarK\Fizz;

/**
 * The core of Fizz, all models should extend this
 */
abstract class Fizz
{
	/** Our PDO instance */
	private $_fizz_pdo;
	/** The name of the table we use */
	private $_fizz_table;

	/**
	 * Initialize Fizz with valid database connection details
	 * 
	 * @param string $db_dsn A PDO connection string
	 * @param string $table The name of the table this model relates too
	 */
	public function __construct($table = NULL) {
		$this->_fizz_pdo = FizzConfig::getDB();
		if (!$this->_fizz_pdo) {
			throw new Exceptions\FizzDatabaseConnectionException("Could not connect to Database");
		}

		$this->_fizz_table = empty($table) ? get_called_class() : $table;
	}

	/**
	 * Return a list of fields we know about.
	 */
	public function fields() {
		$fields = array();
		foreach (get_object_vars($this) as $key => $val) {
			if (is_string($key) && strpos($key, "_") !== 0) {
				$fields[] = $key;
			}
		}
		return $fields;
	}

	/**
	 * Insert into the database
	 */
	public function create() {
		$fields = $this->fields();

		$values = array();
		foreach ($fields as $field) {
			$values[":" . $field] = $this->$field;
		}

		$sql = "INSERT INTO `".$this->_fizz_table."` (`".implode("`,`", $fields)."`) VALUES (:".implode(",:", $fields).")";
		$q = $this->_fizz_pdo->prepare($sql);
		if ($q->execute($values)) {
			return true;
		}
		return $q->errorInfo();
	}
}