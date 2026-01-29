<?php
include_once 'config/database.php';
include_once 'models/User.php';

class AuthController
{

    private $db;
    private $user;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // Helper to get and validate JSON input
    private function getJsonInput()
    {
        $input = file_get_contents("php://input");
        $data = json_decode($input);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->respond(["message" => "Invalid JSON format"], 400);
        }

        if (!is_object($data)) {
            $this->respond(["message" => "Invalid JSON data"], 400);
        }

        return $data;
    }

    // Helper to send JSON response
    private function respond($data, $code = 200)
    {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    public function register()
    {
        try {
            $data = $this->getJsonInput();

            if (empty($data->username) || empty($data->email) || empty($data->password)) {
                $this->respond(["message" => "Incomplete data"], 400);
            }

            $this->user->username = htmlspecialchars(strip_tags($data->username));
            $this->user->email = htmlspecialchars(strip_tags($data->email));
            $this->user->password = htmlspecialchars(strip_tags($data->password));

            // Check if email already exists
            if ($this->user->emailExists()) {
                $this->respond(["message" => "Email already exists"], 400);
            }

            if ($this->user->create()) {
                $this->respond(["message" => "User was created"], 201);
            } else {
                $this->respond(["message" => "Unable to create user"], 503);
            }
        } catch (Exception $e) {
            error_log("Register Error: " . $e->getMessage());
            $this->respond(["message" => "Internal Server Error"], 500);
        }
    }

    public function login()
    {
        try {
            $data = $this->getJsonInput();

            if (empty($data->email) || empty($data->password)) {
                $this->respond(["message" => "Incomplete data"], 400);
            }

            $this->user->email = htmlspecialchars(strip_tags($data->email));

            // emailExists() populates $this->user->password with the hash from DB
            if ($this->user->emailExists()) {
                if (password_verify($data->password, $this->user->password)) {

                    // Session logic (optional for API, kept for compatibility)
                    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                        @session_start();
                    }
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        $_SESSION['user_id'] = $this->user->id;
                        $_SESSION['username'] = $this->user->username;
                    }

                    $this->respond([
                        "message" => "Login successful",
                        "user_id" => $this->user->id,
                        "username" => $this->user->username
                    ], 200);
                } else {
                    $this->respond(["message" => "Invalid password"], 401);
                }
            } else {
                $this->respond(["message" => "User not found"], 401);
            }
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            $this->respond(["message" => "Internal Server Error"], 500);
        }
    }

    public function getUserInfo($userId)
    {
        try {
            if (!$userId) {
                $this->respond(["success" => false, "message" => "User ID required"], 400);
            }

            $this->user->id = $userId;
            $stmt = $this->user->getUserById();
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->respond([
                    "success" => true,
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "email_notifications" => filter_var($row['email_notifications'], FILTER_VALIDATE_BOOLEAN)
                ], 200);
            } else {
                $this->respond(["success" => false, "message" => "User not found"], 404);
            }
        } catch (Exception $e) {
            error_log("GetUserInfo Error: " . $e->getMessage());
            $this->respond(["success" => false, "message" => "Server Error"], 500);
        }
    }

    public function updateNotifications($userId)
    {
        try {
            if (!$userId) {
                $this->respond(["success" => false, "message" => "User ID required"], 400);
            }

            $data = $this->getJsonInput();

            if (isset($data->email_notifications)) {
                $this->user->id = $userId;
                $this->user->email_notifications = $data->email_notifications;

                if ($this->user->updateNotifications()) {
                    $this->respond(["success" => true, "message" => "Notification settings updated"], 200);
                } else {
                    $this->respond(["success" => false, "message" => "Unable to update settings"], 503);
                }
            } else {
                $this->respond(["success" => false, "message" => "Incomplete data"], 400);
            }
        } catch (Exception $e) {
            error_log("UpdateNotifications Error: " . $e->getMessage());
            $this->respond(["success" => false, "message" => "Server Error"], 500);
        }
    }

    public function changePassword($userId)
    {
        try {
            if (!$userId) {
                $this->respond(["success" => false, "message" => "User ID required"], 400);
            }

            $data = $this->getJsonInput();

            if (!empty($data->current_password) && !empty($data->new_password)) {
                $this->user->id = $userId;

                // Fetch current password hash
                $stmt = $this->user->getUserById();
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $currentHash = $row['password_hash'];

                    if (password_verify($data->current_password, $currentHash)) {
                        $this->user->password = $data->new_password;
                        if ($this->user->updatePassword()) {
                            $this->respond(["success" => true, "message" => "Password updated"], 200);
                        } else {
                            $this->respond(["success" => false, "message" => "Unable to update password"], 503);
                        }
                    } else {
                        $this->respond(["success" => false, "message" => "Incorrect current password"], 401);
                    }
                } else {
                    $this->respond(["success" => false, "message" => "User not found"], 404);
                }
            } else {
                $this->respond(["success" => false, "message" => "Incomplete data"], 400);
            }
        } catch (Exception $e) {
            error_log("ChangePassword Error: " . $e->getMessage());
            $this->respond(["success" => false, "message" => "Server Error"], 500);
        }
    }
}
