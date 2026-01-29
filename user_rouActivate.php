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
$routineId = intval($_POST['id'] ?? 0);

// Validación
if ($routineId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID de la rutina es obligatorio']);
    exit;
}

try {
    // Verificar que el usuario tiene acceso a esta rutina
    $stmt = $pdo->prepare("
        SELECT r.id, r.name 
        FROM user_routine ur
        JOIN routine r ON r.id = ur.routine_id
        WHERE ur.user_id = :user_id AND ur.routine_id = :routine_id
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':routine_id' => $routineId
    ]);
    $routine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$routine) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes acceso a esta rutina o no existe']);
        exit;
    }

    // Desactivar todas las rutinas del usuario
    $stmt = $pdo->prepare("UPDATE user_routine SET active = 0 WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);

    // Activar la rutina seleccionada
    $stmt = $pdo->prepare("UPDATE user_routine SET active = 1 WHERE user_id = :user_id AND routine_id = :routine_id");
    $stmt->execute([
        ':user_id' => $userId,
        ':routine_id' => $routineId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutina activada correctamente',
        'routine' => [
            'id' => $routineId,
            'name' => $routine['name'],
            'active' => 1
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al activar la rutina', 'error' => $e->getMessage()]);
}
?>