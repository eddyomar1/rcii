<?php
// api.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1) Conexión
$mysqli = new mysqli(
    "localhost",
    "u138076177_chacharito",   // tu usuario MySQL
    "3spWifiPruev@",           // tu contraseña MySQL
    "u138076177_pw"            // tu base de datos
);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $mysqli->connect_error]);
    exit;
}

// 2) ¿qué acción?
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get') {
    // 2.a) Lectura, opcionalmente filtrada por fecha
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    if ($date) {
        // validamos formato YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
            exit;
        }
        $stmt = $mysqli->prepare(
            "SELECT 
               user_id,
               activity_type,
               DATE_FORMAT(timestamp, '%d/%m/%Y %H:%i:%s') AS formatted_timestamp
             FROM activities
             WHERE DATE(timestamp) = ?
             ORDER BY timestamp DESC"
        );
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        // sin filtro
        $res = $mysqli->query(
            "SELECT 
               user_id,
               activity_type,
               DATE_FORMAT(timestamp, '%d/%m/%Y %H:%i:%s') AS formatted_timestamp
             FROM activities
             ORDER BY timestamp DESC"
        );
    }

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2.b) Inserción de nueva actividad
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $user_id       = isset($data['user_id'])       ? trim($data['user_id'])       : '';
    $activity_type = isset($data['activity_type']) ? trim($data['activity_type']) : '';

    // validaciones
    if ($user_id === '' || $activity_type === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_id or activity_type']);
        exit;
    }
    if (strlen($user_id) > 50 || strlen($activity_type) > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'user_id or activity_type too long']);
        exit;
    }

    $stmt = $mysqli->prepare(
        "INSERT INTO activities (user_id, activity_type, timestamp)
         VALUES (?, ?, NOW())"
    );
    $stmt->bind_param('ss', $user_id, $activity_type);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Insert failed: ' . $stmt->error]);
    }
    exit;
}

// acción inválida
http_response_code(400);
echo json_encode(['error' => 'Invalid action or method']);
