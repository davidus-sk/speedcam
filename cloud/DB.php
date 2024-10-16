<?php
/**
 * PHP class for sqlite3 database queries
 *
 * @author davidus.sk
 */
class DB {
	// connection object
	private $conn = null;

	/**
	 * Create object
	 */
	public function __construct($databaseFile) {
		$this->conn = new SQLite3($databaseFile);

		if (!$this->conn) {
			die('Could not open database: ' . $this->conn->lastErrorMsg());
		}//if
	}//function
 
	/**
	 * Destroy the class
	 */
	public function __destruct() {
		$this->conn->close();
	}//function

	/**
	 * Prepare and execute query
	 *
	 * @param $sql
	 * @param $params
	 * @return object
	 */
	public function query($sql, $params = []) {
		$stmt = $this->conn->prepare($sql);

		if (!$stmt) {
			die('Prepare failed: ' . $this->conn->lastErrorMsg());
		}//if

		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}//foreach

		$result = $stmt->execute();
		
		if (!$result) {
			die('Execute failed: ' . $this->conn->lastErrorMsg());
		}//if

		return $result;
	}//function

	/**
	 * Fetch all rows from a query
	 *
	 * @param $sql
	 * @param $params
	 * @return array
	 */
	public function fetchAll($sql, $params = []) {
		$result = $this->query($sql, $params);
		return $result->fetchAll();
	}//function

	/**
	 * Fetch row from a query
	 *
	 * @param $sql
	 * @param $params
	 * @return array
	 */
	public function fetchRow($sql, $params = []) {
		$result = $this->query($sql, $params);
		return $result->fetchArray();
	}//function

	/**
	 * Get last insert ID
	 *
	 * @return int
	 */
	public function lastInsertId() {
		return $this->conn->lastInsertRowID();
	}//function

	/**
	 * Create needed schemas
	 *
	 * @return void
	 */
	public function createSchemas() {
		// create table detections
		$this->conn->exec('CREATE TABLE IF NOT EXISTS detections (time TEXT, month INTEGER, day INTEGER, hour INTEGER, year INTEGER, camera INTEGER, radar, speed REAL, plate TEXT, image1 TEXT, image2 TEXT)');
	}//function
}//class
