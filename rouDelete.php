<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

// Solo POST/DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$userId = requireAuth();

// Recoger ID
$routineId = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// Validación
if ($routineId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID de la rutina es obligatorio']);
    exit;
}

try {
    // Verificar que la rutina existe y es tuya
    $stmt = $pdo->prepare("SELECT id, name FROM routine WHERE id = :id AND id_owner = :id_owner");
    $stmt->execute([
        ':id' => $routineId,
        ':id_owner' => $userId
    ]);
    $routine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$routine) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para eliminar esta rutina o no existe']);
        exit;
    }

    // Eliminar rutina (los días se eliminarán en cascada)
    $stmt = $pdo->prepare("DELETE FROM routine WHERE id = :id AND id_owner = :id_owner");
    $stmt->execute([
        ':id' => $routineId,
        ':id_owner' => $userId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutina eliminada correctamente',
        'routine' => [
            'id' => $routineId,
            'name' => $routine['name']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar la rutina', 'error' => $e->getMessage()]);
}
?>