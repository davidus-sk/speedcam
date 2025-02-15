<?php

class DB {
    private $mysqli;
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $socket;

    public function __construct($host, $username, $password, $database, $port = null, $socket = null) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->socket = $socket;

        $this->connect();
    }

    private function connect() {
        try {
            if ($this->port !== null && $this->socket !== null) {
                $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port, $this->socket);
            } elseif ($this->port !== null) {
                $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
            } elseif ($this->socket !== null) {
                $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, null, $this->socket);
            } else {
                $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database);
            }

            if ($this->mysqli->connect_error) {
                throw new Exception("MySQLi Connection Error: " . $this->mysqli->connect_error);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }

    public function query($sql) {
        try {
            $result = $this->mysqli->query($sql);

            if ($this->mysqli->error) {
                throw new Exception("MySQLi Query Error: " . $this->mysqli->error . " SQL: " . $sql);
            }

            return $result;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function fetchAssoc($result) {
        if ($result) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function fetchAllAssoc($result) {
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function escapeString($string) {
        return $this->mysqli->real_escape_string($string);
    }

    public function insertId() {
        return $this->mysqli->insert_id;
    }

    public function affectedRows() {
        return $this->mysqli->affected_rows;
    }

    public function close() {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
    }

    public function __destruct() {
        $this->close();
    }
}
