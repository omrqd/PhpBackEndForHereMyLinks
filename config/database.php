<?php

class Database
{
    private $host = "localhost";
    private $db_name = "heremylinks_db";
    private $username = "heremylinks";
    private $password = "Omar500600"; // Default local password, should be changed in env
    private $port = "5432";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            // For production, log this instead of showing it
            error_log("Connection error: " . $exception->getMessage());
            echo json_encode(["error" => "Database connection failed"]);
            exit;
        }

        return $this->conn;
    }
}
