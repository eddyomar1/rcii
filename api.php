<?php
// api.php

// 1) CORS & preflight
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2) JSON output
header('Content-Type: application/json');

// 3) Conexión a MySQL
$mysqli = new mysqli(
    "localhost",
    "u138076177_chacharito",
    "3spWifiPruev@",
    "u138076177_pw"
);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $mysqli->connect_error]);
    exit;
}

// 4) Determinar acción
$action = $_GET['action'] ?? '';

// 5) Obtener actividades
if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? '';
    if ($date) {
        // Validar formato YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
            exit;
        }
        $stmt = $mysqli->prepare("
            SELECT 
                user_id,
                activity_type,
                trabajo_realizado,
                DATE_FORMAT(timestamp, '%d/%m/%Y %H:%i:%s') AS formatted_timestamp
            FROM activities
            WHERE DATE(timestamp) = ?
            ORDER BY timestamp DESC
        ");
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        // Sin filtro de fecha
        $res = $mysqli->query("
            SELECT 
                user_id,
                activity_type,
                trabajo_realizado,
                DATE_FORMAT(timestamp, '%d/%m/%Y %H:%i:%s') AS formatted_timestamp
            FROM activities
            ORDER BY timestamp DESC
        ");
    }

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}

// 6) Añadir nueva actividad
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer JSON
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $user_id           = trim($data['user_id']       ?? '');
    $activity_type     = trim($data['activity_type'] ?? '');
    $trabajo_realizado = trim($data['trabajo_realizado'] ?? '');

    // Validaciones básicas
    if ($user_id === '' || $activity_type === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Falta user_id o activity_type']);
        exit;
    }

    // 6.1) Última actividad de este usuario
    $stmt = $mysqli->prepare("
        SELECT activity_type
        FROM activities
        WHERE user_id = ?
        ORDER BY timestamp DESC
        LIMIT 1
    ");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $lastType = $last['activity_type'] ?? '';

    // 6.2) Prohibir dos Entradas o dos Salidas consecutivas
    if (
        ($activity_type === 'Entrada' && $lastType === 'Entrada') ||
        ($activity_type === 'Salida'  && $lastType === 'Salida')
    ) {
        http_response_code(400);
        echo json_encode([
            'error' => "No puedes registrar dos '$activity_type' consecutivas."
        ]);
        exit;
    }

    // 6.3) Insertar la nueva actividad
    $stmt = $mysqli->prepare("
        INSERT INTO activities
           (user_id, activity_type, trabajo_realizado, timestamp)
        VALUES (?,       ?,             ?,               NOW())
    ");
    $stmt->bind_param('sss', $user_id, $activity_type, $trabajo_realizado);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Insert failed: ' . $stmt->error]);
    }
    exit;
}

// 7) Acción inválida
http_response_code(400);
echo json_encode(['error' => 'Invalid action or method']);
