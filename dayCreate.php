<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$userId = requireAuth();

// Recoger datos
$name = trim($_POST['name'] ?? '');
$dayOfWeek = trim($_POST['day_of_week'] ?? '');
$routineId = intval($_POST['id_routine'] ?? 0);

// Validaciones
$errores = [];
if ($name === '') {
    $errores[] = 'El nombre del día es obligatorio';
}
if ($dayOfWeek !== '' && !in_array($dayOfWeek, ['L', 'M', 'X', 'J', 'V', 'S', 'D'])) {
    $errores[] = 'El día de la semana debe ser: L, M, X, J, V, S o D';
}
if ($routineId <= 0) {
    $errores[] = 'El ID de la rutina es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que la rutina existe y pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT r.id 
        FROM routine r
        WHERE r.id = :id AND r.id_owner = :id_owner
    ");
    $stmt->execute([
        ':id' => $routineId,
        ':id_owner' => $userId
    ]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar esta rutina o no existe']);
        exit;
    }

    // Verificar que no exista ya ese día de la semana en la rutina
    $stmt = $pdo->prepare("SELECT id FROM day WHERE id_routine = :id_routine AND day_of_week = :day_of_week");
    $stmt->execute([
        ':id_routine' => $routineId,
        ':day_of_week' => $dayOfWeek
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Ya existe un día para ese día de la semana en esta rutina']);
        exit;
    }

    // Insertar día
    $stmt = $pdo->prepare("INSERT INTO day (name, day_of_week, id_routine) VALUES (:name, :day_of_week, :id_routine)");
    $stmt->execute([
        ':name' => $name,
        ':day_of_week' => $dayOfWeek,
        ':id_routine' => $routineId
    ]);

    $dayId = $pdo->lastInsertId();

    echo json_encode([
        'ok' => true,
        'msg' => 'Día creado correctamente',
        'day' => [
            'id' => $dayId,
            'name' => $name,
            'day_of_week' => $dayOfWeek,
            'id_routine' => $routineId
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al crear el día', 'error' => $e->getMessage()]);
}
?>