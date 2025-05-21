<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}



// 1) Conexión
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
                trabajo_realizado,
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

// api.php

// … tu código de conexión, cabeceras, manejo de OPTIONS …

$action = $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Leemos el JSON
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $user_id           = trim($data['user_id']       ?? '');
    $activity_type     = trim($data['activity_type'] ?? '');
    $trabajo_realizado = trim($data['trabajo_realizado'] ?? '');

    // 2) Validaciones básicas
    if ($user_id === '' || $activity_type === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Falta user_id o activity_type']);
        exit;
    }

    // ← Aquí agrega la comprobación de últimas Entradas/Salidas →
    // 3) Recuperar última actividad de este usuario
    $stmt = $mysqli->prepare(
      "SELECT activity_type
       FROM activities
       WHERE user_id = ?
       ORDER BY timestamp DESC
       LIMIT 1"
    );
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $lastType = $last['activity_type'] ?? '';

    // 4) Prohibir dos Entradas o dos Salidas consecutivas
    if (($activity_type === 'Entrada' && $lastType === 'Entrada') ||
        ($activity_type === 'Salida'  && $lastType === 'Salida')) {
        http_response_code(400);
        echo json_encode([
          'error' => "No puedes registrar dos '$activity_type' consecutivas."
        ]);
        exit;
    }
    // ← fin comprobación →

    // 5) Insert normal
    $stmt = $mysqli->prepare(
      "INSERT INTO activities
         (user_id, activity_type, trabajo_realizado, timestamp)
       VALUES (?, ?, ?, NOW())"
    );
    $stmt->bind_param('sss', $user_id, $activity_type, $trabajo_realizado);
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
