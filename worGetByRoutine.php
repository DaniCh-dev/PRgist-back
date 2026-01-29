<?php
// getWorkoutsByRoutine.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$userId = requireAuth();

// Recoger ID de la rutina
$routineId = intval($_GET['id_routine'] ?? 0);

// Validación
if ($routineId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID de la rutina es obligatorio']);
    exit;
}

try {
    // Verificar que el usuario tiene acceso a la rutina
    $stmt = $pdo->prepare("
        SELECT r.id, r.name 
        FROM routine r
        LEFT JOIN user_routine ur ON ur.routine_id = r.id AND ur.user_id = :user_id
        WHERE r.id = :id AND (r.id_owner = :user_id2 OR ur.user_id IS NOT NULL)
    ");
    $stmt->execute([
        ':id' => $routineId,
        ':user_id' => $userId,
        ':user_id2' => $userId
    ]);
    $routine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$routine) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes acceso a esta rutina o no existe']);
        exit;
    }

    // Obtener todos los workouts del usuario de esta rutina
    $stmt = $pdo->prepare("
        SELECT 
            w.id,
            w.date,
            w.fecha_inicio,
            w.fecha_fin,
            w.duration,
            w.completed,
            w.id_day,
            d.name as day_name,
            d.day_of_week
        FROM workout w
        JOIN day d ON d.id = w.id_day
        WHERE w.id_user = :id_user 
        AND d.id_routine = :id_routine
        ORDER BY w.date DESC
    ");
    $stmt->execute([
        ':id_user' => $userId,
        ':id_routine' => $routineId
    ]);
    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir valores
    foreach ($workouts as &$workout) {
        $workout['completed'] = (int) $workout['completed'];
        $workout['duration'] = $workout['duration'] ? (int) $workout['duration'] : null;
    }

    echo json_encode([
        'ok' => true,
        'msg' => 'Workouts obtenidos correctamente',
        'routine' => [
            'id' => $routine['id'],
            'name' => $routine['name']
        ],
        'count' => count($workouts),
        'workouts' => $workouts
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener workouts', 'error' => $e->getMessage()]);
}
?>