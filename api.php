<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration (replace with your Hostinger MySQL details)
$host = 'your_hostinger_mysql_host'; // e.g., 'mysql.hostinger.com' or 'localhost'
$dbname = 'your_database_name'; // e.g., 'u123456789_rcii_activities'
$username = 'your_database_username'; // e.g., 'u123456789_rcii_user'
$password = 'your_database_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get') {
    try {
        $date = isset($_GET['date']) ? $_GET['date'] : '';
        if ($date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
                exit;
            }
            $stmt = $pdo->prepare('SELECT user_id, activity_type, DATE_FORMAT(timestamp, "%d/%m/%Y %H:%i:%s") AS soaked_timestamp 
                                   FROM activities 
                                   WHERE DATE(timestamp) = ? 
                                   ORDER BY timestamp DESC');
            $stmt->execute([$date]);
        } else {
            $stmt = $pdo->query('SELECT user_id, activity_type, DATE_FORMAT(timestamp, "%d/%m/%Y %H:%i:%s") AS formatted_timestamp 
                                 FROM activities 
                                 ORDER BY timestamp DESC');
        }
        $activities = $stmt->fetchAll();
        echo json_encode($activities);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
        exit;
    }
} elseif ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($data['user_id']) ? trim($data['user_id']) : '';
        $activity_type = isset($data['activity_type']) ? trim($data['activity_type']) : '';

        if (empty($user_id) || empty($activity_type)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user_id or activity_type']);
            exit;
        }

        if (strlen($user_id) > 50 || strlen($activity_type) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id or activity_type too long']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO activities (user_id, activity_type, timestamp) VALUES (?, ?, NOW())');
        $stmt->execute([$user_id, $activity_type]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Insert failed: ' . $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action or method']);
}
?>