<?php
// getSetEntries.php
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
$idWorkout = intval($_GET['id_workout'] ?? 0);

// Validación
if ($idWorkout <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID del workout es obligatorio']);
    exit;
}

try {
    // Verificar que el workout existe y pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT w.id, w.date, d.name as day_name
        FROM workout w
        LEFT JOIN day d ON d.id = w.id_day
        WHERE w.id = :id AND w.id_user = :id_user
    ");
    $stmt->execute([
        ':id' => $idWorkout,
        ':id_user' => $userId
    ]);
    $workout = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$workout) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para ver este workout o no existe']);
        exit;
    }

    // Obtener series del workout
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
    $stmt->execute([':id_workout' => $idWorkout]);
    $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir valores
    foreach ($sets as &$set) {
        $set['completed'] = (int) $set['completed'];
        $set['weight'] = $set['weight'] ? (float) $set['weight'] : null;
        $set['time_break'] = $set['time_break'] ? (int) $set['time_break'] : null;
    }

    echo json_encode([
        'ok' => true,
        'msg' => 'Series obtenidas correctamente',
        'workout' => [
            'id' => $workout['id'],
            'date' => $workout['date'],
            'day_name' => $workout['day_name']
        ],
        'count' => count($sets),
        'sets' => $sets
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener series', 'error' => $e->getMessage()]);
}
?>