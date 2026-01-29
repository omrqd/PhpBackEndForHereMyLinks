<?php

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        // Load from environment variables or use local defaults
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'heremylinks_db';
        $this->username = getenv('DB_USER') ?: 'heremylinks';
        $this->password = getenv('DB_PASS') ?: 'Omar500600';
        $this->port = getenv('DB_PORT') ?: '5432';

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            // Log specific error for server admin
            error_log("Database Connection Error (Host: " . $this->host . "): " . $exception->getMessage());

            // Return generic 500 error to client
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Internal Server Error: Database connection failed"]);
            exit;
        }

        return $this->conn;
    }
}
