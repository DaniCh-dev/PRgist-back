<?php
// deleteWorkout.php
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

// Recoger ID del workout
$workoutId = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// Validación
if ($workoutId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID del workout es obligatorio']);
    exit;
}

try {
    // Verificar que el workout existe y es del usuario
    $stmt = $pdo->prepare("
        SELECT w.id, w.date, d.name as day_name 
        FROM workout w
        LEFT JOIN day d ON d.id = w.id_day
        WHERE w.id = :id AND w.id_user = :id_user
    ");
    $stmt->execute([
        ':id' => $workoutId,
        ':id_user' => $userId
    ]);
    $workout = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$workout) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para eliminar este workout o no existe']);
        exit;
    }

    // Eliminar workout (los sets se eliminarán en cascada)
    $stmt = $pdo->prepare("DELETE FROM workout WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':id' => $workoutId,
        ':id_user' => $userId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Workout eliminado correctamente',
        'workout' => [
            'id' => $workoutId,
            'date' => $workout['date'],
            'day_name' => $workout['day_name']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar workout', 'error' => $e->getMessage()]);
}
?>