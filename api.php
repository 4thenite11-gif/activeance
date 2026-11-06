<?php
/**
 * Activeance Investment CC - API Endpoint
 * Handles all requests from the frontend.
 */

// config.php handles CORS headers, session security, and starts the session safely.
require 'config.php';

// API always returns JSON (admin.php must NOT — that's why this is here, not in config.php)
header("Content-Type: application/json; charset=UTF-8");

// Connect to the Database securely using PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Helper: is the current visitor a logged-in admin?
function api_is_admin(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Helper: require admin or return 403
function api_require_admin(): void {
    if (!api_is_admin()) {
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized access"]);
        exit;
    }
}

// Get the requested action from the URL (e.g., api.php?action=get_cars)
$action = $_GET['action'] ?? '';

// Route the request to the correct function
switch ($action) {

    // ==========================================
    // 0. SESSION CHECK
    // ==========================================
    case 'check_session':
        echo json_encode(["logged_in" => api_is_admin()]);
        break;

    // ==========================================
    // 1. ADMIN LOGIN
    // ==========================================
    case 'login':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->username, $data->password)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing username or password"]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([trim($data->username)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data->password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = (int) $user['id'];

            echo json_encode(["success" => true, "message" => "Logged in successfully"]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Invalid credentials"]);
        }
        break;

    // ==========================================
    // 1b. ADMIN LOGOUT
    // ==========================================
    case 'logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        echo json_encode(["success" => true, "message" => "Logged out"]);
        break;

    // ==========================================
    // 2. PUBLIC SETTINGS (business name, colors, phone, etc.)
    // ==========================================
    case 'get_settings':
        try {
            $row = $pdo->query("SELECT business_name, tagline, quote_text, logo_url,
                                       owner_name, owner_photo_url,
                                       color_primary, color_dark, color_accent, phone
                                FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $row = null; // settings table not migrated yet
        }

        // Safe defaults so the frontend never breaks
        $defaults = [
            'business_name' => 'Activeance Investment CC',
            'tagline'       => 'PREMIUM PRE-OWNED VEHICLES',
            'quote_text'    => '',
            'logo_url'      => 'public/logo.png',
            'owner_name'    => 'Mr. A Muxinga',
            'owner_photo_url' => 'public/owner.jpg',
            'color_primary' => '#D62828',
            'color_dark'    => '#0B132B',
            'color_accent'  => '#25D366',
            'phone'         => '+264814540060',
        ];
        echo json_encode(array_merge($defaults, $row ?: []));
        break;

    // ==========================================
    // 3. GET AVAILABLE CARS (public — sold cars excluded)
    // ==========================================
    case 'get_cars':
        // Sold cars are hidden from the storefront; they only appear in admin stats.
        $stmt = $pdo->query("
            SELECT id, make_model, year, price, image_url,
                   waiting_period, shipment_status, status
            FROM cars
            WHERE status = 'Available'
            ORDER BY id DESC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // ==========================================
    // 4. GET ALL CARS (admin only — includes sold)
    // ==========================================
    case 'get_all_cars':
        api_require_admin();
        $stmt = $pdo->query("SELECT * FROM cars ORDER BY id DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // ==========================================
    // 5. ADD A CAR (admin)
    // ==========================================
    case 'add_car':
        api_require_admin();

        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->make_model, $data->year, $data->price, $data->image_url)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        $make_model = trim((string) $data->make_model);
        $image_url  = trim((string) $data->image_url);
        $year       = (int) $data->year;
        $price      = (float) $data->price;
        $waiting    = isset($data->waiting_period) ? trim((string) $data->waiting_period) : null;
        $shipment   = $data->shipment_status ?? 'In Stock';

        if ($make_model === '' || $image_url === '' || $year <= 0 || $price <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid field values"]);
            exit;
        }
        if (!in_array($shipment, ['In Stock', 'In Transit', 'Arriving Soon'], true)) {
            $shipment = 'In Stock';
        }

        $stmt = $pdo->prepare("
            INSERT INTO cars (make_model, year, price, image_url, waiting_period, shipment_status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$make_model, $year, $price, $image_url, $waiting ?: null, $shipment]);

        echo json_encode([
            "success" => true,
            "message" => "Car added successfully",
            "id"      => (int) $pdo->lastInsertId()
        ]);
        break;

    // ==========================================
    // 6. UPDATE A CAR (admin — edit price/image/waiting)
    // ==========================================
    case 'update_car':
        api_require_admin();

        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->id, $data->make_model, $data->year, $data->price, $data->image_url)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        $id         = (int) $data->id;
        $make_model = trim((string) $data->make_model);
        $image_url  = trim((string) $data->image_url);
        $year       = (int) $data->year;
        $price      = (float) $data->price;
        $waiting    = isset($data->waiting_period) ? trim((string) $data->waiting_period) : null;
        $shipment   = $data->shipment_status ?? 'In Stock';

        if ($id <= 0 || $make_model === '' || $image_url === '' || $year <= 0 || $price <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid field values"]);
            exit;
        }
        if (!in_array($shipment, ['In Stock', 'In Transit', 'Arriving Soon'], true)) {
            $shipment = 'In Stock';
        }

        $stmt = $pdo->prepare("
            UPDATE cars
            SET make_model = ?, year = ?, price = ?, image_url = ?,
                waiting_period = ?, shipment_status = ?
            WHERE id = ?
        ");
        $stmt->execute([$make_model, $year, $price, $image_url, $waiting ?: null, $shipment, $id]);

        echo json_encode(["success" => true, "message" => "Car updated"]);
        break;

    // ==========================================
    // 7. DELETE A CAR (admin)
    // ==========================================
    case 'delete_car':
        api_require_admin();

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing car ID"]);
            exit;
        }

        $id = (int) $_GET['id'];
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid car ID"]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(["success" => true, "message" => "Car deleted successfully"]);
        break;

    // ==========================================
    // 8. MARK CAR AS SOLD (admin — feeds stats)
    // ==========================================
    case 'mark_sold':
        api_require_admin();

        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->id)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing car ID"]);
            exit;
        }

        $id = (int) $data->id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid car ID"]);
            exit;
        }

        // Get the listed price as a fallback if sold_price wasn't provided
        $stmt = $pdo->prepare("SELECT price FROM cars WHERE id = ?");
        $stmt->execute([$id]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$car) {
            http_response_code(404);
            echo json_encode(["error" => "Car not found"]);
            exit;
        }

        $soldPrice = isset($data->sold_price) && (float) $data->sold_price > 0
            ? (float) $data->sold_price
            : (float) $car['price'];

        $pdo->prepare("
            UPDATE cars
            SET status = 'Sold', sold_at = NOW(), sold_price = ?
            WHERE id = ?
        ")->execute([$soldPrice, $id]);

        echo json_encode([
            "success" => true,
            "message" => "Car marked as sold",
            "sold_price" => $soldPrice
        ]);
        break;

    // ==========================================
    // 9. UNDO SOLD (admin)
    // ==========================================
    case 'mark_available':
        api_require_admin();

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing car ID"]);
            exit;
        }

        $id = (int) $_GET['id'];
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid car ID"]);
            exit;
        }

        $pdo->prepare("
            UPDATE cars
            SET status = 'Available', sold_at = NULL, sold_price = NULL
            WHERE id = ?
        ")->execute([$id]);

        echo json_encode(["success" => true, "message" => "Car marked as available again"]);
        break;

    // ==========================================
    // 10. SALES STATS (admin dashboard)
    // ==========================================
    case 'get_stats':
        api_require_admin();

        $stats = [
            'total_cars'     => (int)   $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn(),
            'available_cars' => (int)   $pdo->query("SELECT COUNT(*) FROM cars WHERE status='Available'")->fetchColumn(),
            'sold_cars'      => (int)   $pdo->query("SELECT COUNT(*) FROM cars WHERE status='Sold'")->fetchColumn(),
            'in_transit'     => (int)   $pdo->query("SELECT COUNT(*) FROM cars WHERE status='Available' AND shipment_status <> 'In Stock'")->fetchColumn(),
            'revenue'        => (float) $pdo->query("SELECT COALESCE(SUM(sold_price),0) FROM cars WHERE status='Sold'")->fetchColumn(),
            'review_count'   => (int)   $pdo->query("SELECT COUNT(*) FROM reviews WHERE approved=1")->fetchColumn(),
            'avg_rating'     => (float) $pdo->query("SELECT COALESCE(AVG(rating),0) FROM reviews WHERE approved=1")->fetchColumn(),
        ];

        $stats['recent_sales'] = $pdo->query("
            SELECT id, make_model, year, sold_price, sold_at
            FROM cars WHERE status='Sold'
            ORDER BY sold_at DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($stats);
        break;

    // ==========================================
    // 11. GET APPROVED REVIEWS (public)
    // ==========================================
    case 'get_reviews':
        try {
            $rows = $pdo->query("
                SELECT id, name, rating, comment, created_at
                FROM reviews
                WHERE approved = 1
                ORDER BY created_at DESC
                LIMIT 30
            ")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
        } catch (PDOException $e) {
            echo json_encode([]); // reviews table not migrated yet
        }
        break;

    // ==========================================
    // 12. GET PENDING REVIEWS (admin)
    // ==========================================
    case 'get_pending_reviews':
        api_require_admin();
        $rows = $pdo->query("
            SELECT id, name, rating, comment, created_at
            FROM reviews
            WHERE approved = 0
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        break;

    // ==========================================
    // 13. SUBMIT REVIEW (public — goes to moderation queue)
    // ==========================================
    case 'submit_review':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->name, $data->rating, $data->comment)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing fields"]);
            exit;
        }

        $name    = trim((string) $data->name);
        $rating  = (int) $data->rating;
        $comment = trim((string) $data->comment);

        if ($name === '' || mb_strlen($name) > 100) {
            http_response_code(400);
            echo json_encode(["error" => "Name is required (max 100 chars)"]);
            exit;
        }
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(["error" => "Rating must be between 1 and 5"]);
            exit;
        }
        if (mb_strlen($comment) < 10 || mb_strlen($comment) > 1000) {
            http_response_code(400);
            echo json_encode(["error" => "Comment must be 10–1000 characters"]);
            exit;
        }

        $pdo->prepare("INSERT INTO reviews (name, rating, comment) VALUES (?, ?, ?)")
            ->execute([$name, $rating, $comment]);

        echo json_encode([
            "success" => true,
            "message" => "Thank you! Your review is pending approval."
        ]);
        break;

    // ==========================================
    // 14. APPROVE REVIEW (admin)
    // ==========================================
    case 'approve_review':
        api_require_admin();

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing review ID"]);
            exit;
        }

        $pdo->prepare("UPDATE reviews SET approved = 1 WHERE id = ?")
            ->execute([(int) $_GET['id']]);

        echo json_encode(["success" => true, "message" => "Review approved"]);
        break;

    // ==========================================
    // 15. DELETE REVIEW (admin)
    // ==========================================
    case 'delete_review':
        api_require_admin();

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing review ID"]);
            exit;
        }

        $pdo->prepare("DELETE FROM reviews WHERE id = ?")
            ->execute([(int) $_GET['id']]);

        echo json_encode(["success" => true, "message" => "Review deleted"]);
        break;

    // ==========================================
    // DEFAULT: Action not found
    // ==========================================
    default:
        http_response_code(400);
        echo json_encode(["error" => "Invalid action. Please specify a valid action."]);
        break;
}