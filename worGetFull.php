<?php
// getFullWorkout.php
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

// Recoger ID del workout
$workoutId = intval($_GET['id'] ?? 0);

// Validación
if ($workoutId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID del workout es obligatorio']);
    exit;
}

try {
    // Obtener información completa del workout
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
            r.id as routine_id,
            r.name as routine_name
        FROM workout w
        LEFT JOIN day d ON d.id = w.id_day
        LEFT JOIN routine r ON r.id = d.id_routine
        WHERE w.id = :id AND w.id_user = :id_user
    ");
    $stmt->execute([
        ':id' => $workoutId,
        ':id_user' => $userId
    ]);
    $workout = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$workout) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para ver este workout o no existe']);
        exit;
    }

    // Obtener todas las series del workout con información del ejercicio
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.position,
            s.n_reps,
            s.time_break,
            s.weight,
            s.completed,
            s.id_exercise,
            e.name as exercise_name
        FROM setentry s
        JOIN exercise e ON e.id = s.id_exercise
        WHERE s.id_workout = :id_workout
        ORDER BY s.position ASC
    ");
    $stmt->execute([':id_workout' => $workoutId]);
    $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir valores del workout
    $workout['completed'] = (int) $workout['completed'];
    $workout['duration'] = $workout['duration'] ? (int) $workout['duration'] : null;
    $workout['id_day'] = $workout['id_day'] ? (int) $workout['id_day'] : null;
    $workout['routine_id'] = $workout['routine_id'] ? (int) $workout['routine_id'] : null;

    // Convertir valores de las series
    foreach ($sets as &$set) {
        $set['position'] = (int) $set['position'];
        $set['n_reps'] = (int) $set['n_reps'];
        $set['completed'] = (int) $set['completed'];
        $set['id_exercise'] = (int) $set['id_exercise'];
        $set['weight'] = $set['weight'] ? (float) $set['weight'] : null;
        $set['time_break'] = $set['time_break'] ? (int) $set['time_break'] : null;
    }

    // Agregar las series al workout
    $workout['sets'] = $sets;
    $workout['total_sets'] = count($sets);

    // Calcular sets completadas
    $completedSets = array_filter($sets, function ($set) {
        return $set['completed'] == 1;
    });
    $workout['completed_sets'] = count($completedSets);

    echo json_encode([
        'ok' => true,
        'msg' => 'Workout completo obtenido correctamente',
        'workout' => $workout
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener el workout completo', 'error' => $e->getMessage()]);
}
?>