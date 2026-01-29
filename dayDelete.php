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

// Recoger ID del día
$dayId = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// Validación
if ($dayId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID del día es obligatorio']);
    exit;
}

try {
    // Verificar que el día existe y su rutina pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT d.id, d.name, d.day_of_week 
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
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para eliminar este día o no existe']);
        exit;
    }

    // Eliminar día (los ejercicios del día se eliminarán en cascada por la foreign key)
    $stmt = $pdo->prepare("DELETE FROM day WHERE id = :id");
    $stmt->execute([':id' => $dayId]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Día eliminado correctamente',
        'day' => [
            'id' => $dayId,
            'name' => $day['name'],
            'day_of_week' => $day['day_of_week']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar el día', 'error' => $e->getMessage()]);
}
?>