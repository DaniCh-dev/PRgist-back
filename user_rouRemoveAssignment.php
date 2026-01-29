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
    // Verificar que la asignación existe y obtener nombre de la rutina
    $stmt = $pdo->prepare("
        SELECT r.id, r.name, ur.active 
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
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'No tienes esta rutina asignada']);
        exit;
    }

    // Eliminar la asignación
    $stmt = $pdo->prepare("DELETE FROM user_routine WHERE user_id = :user_id AND routine_id = :routine_id");
    $stmt->execute([
        ':user_id' => $userId,
        ':routine_id' => $routineId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutina desvinculada correctamente',
        'routine' => [
            'id' => $routineId,
            'name' => $routine['name'],
            'was_active' => (int) $routine['active']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al desvincular la rutina', 'error' => $e->getMessage()]);
}
?>