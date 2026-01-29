<?php
// Set working directory to project root (parent of public)
// This ensures all relative includes (controllers, models, config) continue to work
chdir(__DIR__ . '/../');

// 1. Error Handling & Output Buffering
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean buffer to avoid mixed HTML/JSON
        if (ob_get_length())
            ob_clean();

        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'error',
            'error' => 'PHP Fatal Error',
            'message' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line'],
        ]);
        exit;
    }
});

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Router
if (isset($uri[1]) && $uri[1] === 'api') {
    // 2. Force JSON for all API responses
    header("Content-Type: application/json; charset=UTF-8");

    if (isset($uri[2])) {
        // Auth Routes
        if ($uri[2] === 'register' || $uri[2] === 'login' || $uri[2] === 'get_user_info' || $uri[2] === 'update_notifications' || $uri[2] === 'change_password') {
            include_once 'controllers/AuthController.php';
            $auth = new AuthController();

            if ($uri[2] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->register();
            } elseif ($uri[2] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->login();
            } elseif ($uri[2] === 'get_user_info' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $userId = $_GET['user_id'] ?? 0;
                $auth->getUserInfo($userId);
            } elseif ($uri[2] === 'update_notifications' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = $_GET['user_id'] ?? 0;
                $auth->updateNotifications($userId);
            } elseif ($uri[2] === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = $_GET['user_id'] ?? 0;
                $auth->changePassword($userId);
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed"]);
            }
        }
        // Page Routes
        elseif ($uri[2] === 'save_page' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            include_once 'controllers/PageController.php';
            $page = new PageController();
            $page->savePage();
        } elseif ($uri[2] === 'update_theme' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            include_once 'controllers/PageController.php';
            $page = new PageController();
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }
            echo json_encode($page->updatePageTheme($userId, $input));
        } elseif ($uri[2] === 'user' && isset($uri[3]) && $uri[3] === 'status') {
            // GET /api/user/status?user_id=1
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                include_once 'controllers/PageController.php';
                $page = new PageController();
                $userId = $_GET['user_id'] ?? 0;
                $page->checkUserPage($userId);
            }
        } elseif ($uri[2] === 'page_details') {
            // GET /api/page_details?user_id=1
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                include_once 'controllers/PageController.php';
                $page = new PageController();
                $userId = $_GET['user_id'] ?? 0;
                echo json_encode($page->getUserPageDetails($userId));
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["message" => "API endpoint not specified"]);
    }
} else {
    // Check if it's a public profile slug (e.g. /mora)
    // Make sure it's not a static file or empty
    if (isset($uri[1]) && !empty($uri[1])) {
        $slug = $uri[1];
        // Basic validation: alphanumeric, dashes, underscores
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            // Check if ignored paths (like favicon.ico)
            if ($slug == 'favicon.ico') {
                http_response_code(404);
                exit;
            }

            include_once 'controllers/PageController.php';
            $page = new PageController();
            // Since this renders HTML, we shouldn't set JSON header at top if successful
            // But headers are already sent. In a simple PHP server script this is tricky.
            // We can overwrite header if output buffering is on, or just rely on browser handling.
            // Better to move content-type header inside api block or override here.
            header("Content-Type: text/html; charset=UTF-8");
            $page->renderPublicPage($slug);
        } else {
            echo "Invalid user page.";
        }
    } else {
        // Root path
        echo json_encode(["message" => "Welcome to HereMyLinks"]);
    }
}
?>