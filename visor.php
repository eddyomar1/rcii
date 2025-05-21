<?php
// view_activities.php

// 1) Conexión
$mysqli = new mysqli(
    "localhost",
    "u138076177_chacharito",   // tu usuario MySQL
    "3spWifiPruev@",           // tu contraseña MySQL
    "u138076177_pw"            // tu base de datos
);
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// 2) Consulta todos los registros
$sql = "
  SELECT 
    id,
    user_id,
    activity_type,
    trabajo_realizado,
    DATE_FORMAT(timestamp, '%d/%m/%Y %H:%i:%s') AS formatted_timestamp
  FROM activities
  ORDER BY timestamp DESC
";
$result = $mysqli->query($sql);
$activities = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
  <title>Listado de Actividades</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 py-10">
  <div class="max-w-4xl mx-auto bg-white shadow rounded-lg overflow-x-auto">
    <h1 class="text-2xl font-bold text-center p-6">Histórico de Actividades</h1>
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trabajo Realizado</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha / Hora</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php if (empty($activities)): ?>
          <tr>
            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No hay actividades registradas.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($activities as $act): ?>
            <tr>
              <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($act['id']) ?></td>
              <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($act['user_id']) ?></td>
              <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($act['activity_type']) ?></td>
              <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($act['trabajo_realizado']) ?></td>
              <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($act['formatted_timestamp']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
