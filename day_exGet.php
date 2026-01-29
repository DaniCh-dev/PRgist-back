<?php
// getDayExercises.php
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

// Recoger ID del día
$idDay = intval($_GET['id_day'] ?? 0);

// Validación
if ($idDay <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID del día es obligatorio']);
    exit;
}

try {
    // Verificar que el usuario tiene acceso al día
    $stmt = $pdo->prepare("
        SELECT d.id, d.name, d.day_of_week, r.name as routine_name
        FROM day d
        JOIN routine r ON r.id = d.id_routine
        LEFT JOIN user_routine ur ON ur.routine_id = r.id AND ur.user_id = :user_id
        WHERE d.id = :id AND (r.id_owner = :user_id2 OR ur.user_id IS NOT NULL)
    ");
    $stmt->execute([
        ':id' => $idDay,
        ':user_id' => $userId,
        ':user_id2' => $userId
    ]);
    $day = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$day) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para ver este día o no existe']);
        exit;
    }

    // Obtener ejercicios del día
    $stmt = $pdo->prepare("
        SELECT 
            de.id_day,
            de.id_exercise,
            de.n_sets,
            de.n_reps,
            de.time_break,
            e.name as exercise_name
        FROM day_exercise de
        JOIN exercise e ON e.id = de.id_exercise
        WHERE de.id_day = :id_day
    ");
    $stmt->execute([':id_day' => $idDay]);
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'msg' => 'Ejercicios del día obtenidos correctamente',
        'day' => [
            'id' => $day['id'],
            'name' => $day['name'],
            'day_of_week' => $day['day_of_week'],
            'routine_name' => $day['routine_name']
        ],
        'count' => count($exercises),
        'exercises' => $exercises
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener ejercicios', 'error' => $e->getMessage()]);
}
?>