<?php
include_once 'config/database.php';

class PageController
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function savePage()
    {
        // Only allow POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            return;
        }

        // Get inputs
        $userId = $_POST['user_id'] ?? null;
        $slug = $_POST['slug'] ?? null;
        $bio = $_POST['bio'] ?? '';
        $linksJson = $_POST['links'] ?? '[]';

        if (!$userId || !$slug) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }

        // Handle Image Upload
        $profileImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                // In production, use full URL or relative path handled by web server
                // For PHP native server, we might need a separate route to serve images or symlink
                // Storing relative path
                $profileImage = 'uploads/' . $filename;
            }
        }

        try {
            $this->conn->beginTransaction();

            // 1. Check if page exists for user
            $query = "SELECT id, profile_image FROM pages WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $existingPage = $stmt->fetch(PDO::FETCH_ASSOC);

            // Keep old image if no new one uploaded
            if (!$profileImage && $existingPage) {
                $profileImage = $existingPage['profile_image'];
            }

            // 2. Upsert Page
            if ($existingPage) {
                // Update
                $query = "UPDATE pages SET slug = :slug, bio = :bio, profile_image = :img, updated_at = NOW() WHERE user_id = :user_id";
                $pageId = $existingPage['id'];
            } else {
                // Insert
                $query = "INSERT INTO pages (user_id, slug, bio, profile_image) VALUES (:user_id, :slug, :bio, :img)";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':img', $profileImage);
            $stmt->execute();

            if (!$existingPage) {
                $pageId = $this->conn->lastInsertId();
            }

            // 3. Update Links (Delete all and re-insert for simplicity)
            $query = "DELETE FROM links WHERE page_id = :page_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':page_id', $pageId);
            $stmt->execute();

            $links = json_decode($linksJson, true);
            if (is_array($links)) {
                $query = "INSERT INTO links (page_id, title, url, icon, sort_order) VALUES (:page_id, :title, :url, :icon, :sort)";
                $stmt = $this->conn->prepare($query);

                $order = 0;
                foreach ($links as $link) {
                    $stmt->bindParam(':page_id', $pageId);
                    $stmt->bindParam(':title', $link['title']);
                    $stmt->bindParam(':url', $link['url']);
                    $stmt->bindParam(':icon', $link['icon']);
                    $stmt->bindParam(':sort', $order);
                    $stmt->execute();
                    $order++;
                }
            }

            $this->conn->commit();
            http_response_code(200);
            echo json_encode(["message" => "Page saved successfully", "slug" => $slug]);

        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Failed to save page: " . $e->getMessage()]);
        }
    }

    public function getPageData($slug)
    {
        try {
            // Get Page and User info
            $query = "
                SELECT p.*, u.username, u.email 
                FROM pages p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.slug = :slug 
                LIMIT 1
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();
            $page = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$page)
                return null;

            // Get Links
            $query = "SELECT title, url, icon, type FROM links WHERE page_id = :page_id ORDER BY sort_order ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':page_id', $page['id']);
            $stmt->execute();
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch default theme if not set
            if (!isset($page['theme_color']))
                $page['theme_color'] = '#F8F9FE';
            if (!isset($page['text_color']))
                $page['text_color'] = '#1A1A1A';

            return [
                'user' => [
                    'username' => $page['username'],
                    'email' => $page['email']
                ],
                'page' => $page,
                'links' => $links,
                'theme' => [
                    'bg_color' => $page['theme_color'],
                    'text_color' => $page['text_color']
                ]
            ];
        } catch (Exception $e) {
            return null;
        }
    }



    public function getUserPageDetails($userId)
    {
        try {
            $query = "SELECT id, slug, bio, profile_image, theme_color, text_color FROM pages WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $page = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$page) {
                return ['success' => false, 'message' => 'Page not found'];
            }

            // Get links
            $query = "SELECT title, url, icon, type FROM links WHERE page_id = :page_id ORDER BY sort_order ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':page_id', $page['id']);
            $stmt->execute();
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'slug' => $page['slug'],
                'bio' => $page['bio'],
                'profile_image' => $page['profile_image'],
                'theme_color' => $page['theme_color'] ?? '#FFFFFF',
                'text_color' => $page['text_color'] ?? '#000000',
                'links' => $links
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function updatePageTheme($userId, $data)
    {
        try {
            $themeColor = $data['theme_color'] ?? null;
            $textColor = $data['text_color'] ?? null;

            if (!$themeColor && !$textColor) {
                return ['success' => false, 'message' => 'No changes provided'];
            }

            $fields = [];
            $params = [':user_id' => $userId];

            if ($themeColor) {
                $fields[] = "theme_color = :theme_color";
                $params[':theme_color'] = $themeColor;
            }

            if ($textColor) {
                $fields[] = "text_color = :text_color";
                $params[':text_color'] = $textColor;
            }

            $sql = "UPDATE pages SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Theme updated successfully'];
            } else {
                // Check if page exists
                $page = $this->checkUserPage($userId);
                if (!$page['has_page']) {
                    return ['success' => false, 'message' => 'Page not found'];
                }
                return ['success' => true, 'message' => 'No changes made'];
            }

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function checkUserPage($userId)
    {
        try {
            $query = "SELECT slug, profile_image FROM pages WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $page = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($page) {
                echo json_encode(["has_page" => true, "slug" => $page['slug'], "profile_image" => $page['profile_image']]);
            } else {
                echo json_encode(["has_page" => false]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function renderPublicPage($slug)
    {
        $pageData = $this->getPageData($slug);

        if (!$pageData) {
            http_response_code(404);
            echo "Page not found";
            return;
        }

        // Pass variables to view
        $username = htmlspecialchars($pageData['user']['username']); // Using username from user table
        $pageTitle = htmlspecialchars($pageData['page']['slug']);
        $bio = htmlspecialchars($pageData['page']['bio']);
        $image = $pageData['page']['profile_image'] ? '/' . $pageData['page']['profile_image'] : 'https://ui-avatars.com/api/?name=' . $username . '&background=random';
        $links = $pageData['links'];
        $theme = $pageData['theme'] ?? [
            'bg_color' => '#F8F9FE',
            'text_color' => '#1A1A1A'
        ];

        include __DIR__ . '/../views/public_page.php';
    }
}
