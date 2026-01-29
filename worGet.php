<?php
// getWorkouts.php
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

try {
    // Obtener todos los workouts del usuario
    $stmt = $pdo->prepare("
        SELECT 
            w.id,
            w.date,
            w.fecha_inicio,
            w.fecha_fin,
            w.duration,
            w.completed,
            w.id_day,
            w.id_user,
            d.name as day_name,
            d.day_of_week,
            r.name as routine_name
        FROM workout w
        LEFT JOIN day d ON d.id = w.id_day
        LEFT JOIN routine r ON r.id = d.id_routine
        WHERE w.id_user = :id_user
        ORDER BY w.date DESC
    ");
    $stmt->execute([':id_user' => $userId]);
    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir completed a int
    foreach ($workouts as &$workout) {
        $workout['completed'] = (int) $workout['completed'];
        $workout['duration'] = $workout['duration'] ? (int) $workout['duration'] : null;
    }

    echo json_encode([
        'ok' => true,
        'msg' => 'Workouts obtenidos correctamente',
        'count' => count($workouts),
        'workouts' => $workouts
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener workouts', 'error' => $e->getMessage()]);
}
?>