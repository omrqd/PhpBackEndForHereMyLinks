<?php

class User
{
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $email_notifications;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                (username, email, password_hash, email_notifications)
                VALUES (:username, :email, :password_hash, :email_notifications)";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->email_notifications = true; // Default

        // hash password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":email_notifications", $this->email_notifications, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function emailExists()
    {
        $query = "SELECT id, username, password_hash
                FROM " . $this->table_name . "
                WHERE email = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password_hash']; // Store hash for verification
            return true;
        }

        return false;
    }

    public function getUserById()
    {
        $query = "SELECT username, email, password_hash, email_notifications
                FROM " . $this->table_name . "
                WHERE id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    public function updateNotifications()
    {
        $query = "UPDATE " . $this->table_name . "
                SET email_notifications = :email_notifications
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // $this->email_notifications is likely a boolean from json_decode
        // No need to sanitize with htmlspecialchars for a boolean bound as PDO::PARAM_BOOL
        // $this->email_notifications = htmlspecialchars(strip_tags($this->email_notifications)); 
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':email_notifications', $this->email_notifications, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updatePassword()
    {
        $query = "UPDATE " . $this->table_name . "
                SET password_hash = :password_hash
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
