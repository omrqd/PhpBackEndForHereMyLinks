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

    public function register()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->username) &&
            !empty($data->email) &&
            !empty($data->password)
        ) {
            $this->user->username = $data->username;
            $this->user->email = $data->email;
            $this->user->password = $data->password;

            // Check if email already exists
            if ($this->user->emailExists()) {
                http_response_code(400);
                echo json_encode(array("message" => "Email already exists."));
                return;
            }

            if ($this->user->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "User was created."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create user."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
        }
    }

    public function login()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->email) && !empty($data->password)) {
            $this->user->email = $data->email;

            if ($this->user->emailExists()) {
                if (password_verify($data->password, $this->user->password)) {
                    // Start session
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['user_id'] = $this->user->id;
                    $_SESSION['username'] = $this->user->username;

                    http_response_code(200);
                    echo json_encode(array(
                        "message" => "Login successful.",
                        "user_id" => $this->user->id,
                        "username" => $this->user->username
                    ));
                } else {
                    http_response_code(401);
                    echo json_encode(array("message" => "Invalid password."));
                }
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "User not found."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
        }
    }

    public function getUserInfo($userId)
    {
        $this->user->id = $userId;
        $stmt = $this->user->getUserById();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(array(
                "success" => true,
                "username" => $row['username'],
                "email" => $row['email'],
                "email_notifications" => filter_var($row['email_notifications'], FILTER_VALIDATE_BOOLEAN)
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("success" => false, "message" => "User not found."));
        }
    }

    public function updateNotifications($userId)
    {
        $data = json_decode(file_get_contents("php://input"));
        if (isset($data->email_notifications)) {
            $this->user->id = $userId;
            $this->user->email_notifications = $data->email_notifications;

            if ($this->user->updateNotifications()) {
                echo json_encode(array("success" => true, "message" => "Notification settings updated."));
            } else {
                http_response_code(503);
                echo json_encode(array("success" => false, "message" => "Unable to update settings."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Incomplete data."));
        }
    }

    public function changePassword($userId)
    {
        $data = json_decode(file_get_contents("php://input"));
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
                        echo json_encode(array("success" => true, "message" => "Password updated."));
                    } else {
                        http_response_code(503);
                        echo json_encode(array("success" => false, "message" => "Unable to update password."));
                    }
                } else {
                    http_response_code(401);
                    echo json_encode(array("success" => false, "message" => "Incorrect current password."));
                }
            } else {
                http_response_code(404);
                echo json_encode(array("success" => false, "message" => "User not found."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Incomplete data."));
        }
    }
}
