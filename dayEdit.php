<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

// Solo POST/PUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$userId = requireAuth();

// Recoger datos
$dayId = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$dayOfWeek = trim($_POST['day_of_week'] ?? '');

// Validaciones
$errores = [];
if ($dayId <= 0) {
    $errores[] = 'El ID del día es obligatorio';
}
if ($name === '') {
    $errores[] = 'El nombre del día es obligatorio';
}
if ($dayOfWeek !== '' && !in_array($dayOfWeek, ['L', 'M', 'X', 'J', 'V', 'S', 'D'])) {
    $errores[] = 'El día de la semana debe ser: L, M, X, J, V, S o D';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que el día existe y su rutina pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT d.id, d.id_routine 
        FROM day d
        JOIN routine r ON r.id = d.id_routine
        WHERE d.id = :id AND r.id_owner = :id_owner
    ");
    $stmt->execute([
        ':id' => $dayId,
        ':id_owner' => $userId
    ]);
    $day = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$day) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar este día o no existe']);
        exit;
    }

    // Verificar que no exista otro día con ese día de la semana en la misma rutina
    $stmt = $pdo->prepare("
        SELECT id 
        FROM day 
        WHERE id_routine = :id_routine 
        AND day_of_week = :day_of_week 
        AND id != :id
    ");
    $stmt->execute([
        ':id_routine' => $day['id_routine'],
        ':day_of_week' => $dayOfWeek,
        ':id' => $dayId
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Ya existe otro día para ese día de la semana en esta rutina']);
        exit;
    }

    // Actualizar día
    $stmt = $pdo->prepare("UPDATE day SET name = :name, day_of_week = :day_of_week WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':day_of_week' => $dayOfWeek,
        ':id' => $dayId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Día actualizado correctamente',
        'day' => [
            'id' => $dayId,
            'name' => $name,
            'day_of_week' => $dayOfWeek,
            'id_routine' => $day['id_routine']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al actualizar el día', 'error' => $e->getMessage()]);
}
?>