<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

// Solo DELETE/POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$userId = requireAuth();

// Recoger ID del ejercicio
$exerciseId = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// Validaciones
if ($exerciseId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID del ejercicio es obligatorio']);
    exit;
}

try {
    // Verificar que el ejercicio existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id, name FROM Exercise WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':id' => $exerciseId,
        ':id_user' => $userId
    ]);
    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exercise) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para eliminar este ejercicio o no existe']);
        exit;
    }

    // Verificar si el ejercicio está en uso (en day_exercise o setentry)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM day_exercise WHERE id_exercise = :id");
    $stmt->execute([':id' => $exerciseId]);
    $inUseDay = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM setentry WHERE id_exercise = :id");
    $stmt->execute([':id' => $exerciseId]);
    $inUseSet = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($inUseDay > 0 || $inUseSet > 0) {
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'msg' => 'No puedes eliminar este ejercicio porque está en uso en rutinas o workouts',
            'in_use' => [
                'routines' => $inUseDay,
                'workouts' => $inUseSet
            ]
        ]);
        exit;
    }

    // Eliminar el ejercicio
    $stmt = $pdo->prepare("DELETE FROM Exercise WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':id' => $exerciseId,
        ':id_user' => $userId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Ejercicio eliminado correctamente',
        'exercise' => [
            'id' => $exerciseId,
            'name' => $exercise['name']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar el ejercicio', 'error' => $e->getMessage()]);
}
?>