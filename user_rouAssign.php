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

// Recoger ID de la rutina
$routineId = intval($_POST['routine_id'] ?? 0);

// Validación
if ($routineId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID de la rutina es obligatorio']);
    exit;
}

try {
    // Verificar que la rutina existe
    $stmt = $pdo->prepare("SELECT id, name, id_owner FROM routine WHERE id = :id");
    $stmt->execute([':id' => $routineId]);
    $routine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$routine) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'La rutina no existe']);
        exit;
    }

    // Verificar si ya está asignada
    $stmt = $pdo->prepare("SELECT routine_id FROM user_routine WHERE user_id = :user_id AND routine_id = :routine_id");
    $stmt->execute([
        ':user_id' => $userId,
        ':routine_id' => $routineId
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Ya tienes esta rutina asignada']);
        exit;
    }

    // Insertar asignación (desactivada por defecto)
    $stmt = $pdo->prepare("INSERT INTO user_routine (user_id, routine_id, active) VALUES (:user_id, :routine_id, 0)");
    $stmt->execute([
        ':user_id' => $userId,
        ':routine_id' => $routineId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutina asignada correctamente',
        'assignment' => [
            'user_id' => $userId,
            'routine_id' => $routineId,
            'routine_name' => $routine['name'],
            'active' => 0
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al asignar la rutina', 'error' => $e->getMessage()]);
}
?>