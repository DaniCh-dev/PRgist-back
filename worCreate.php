<?php
// createWorkout.php
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

// Recoger datos
$date = trim($_POST['date'] ?? date('Y-m-d H:i:s'));
$idDay = isset($_POST['id_day']) ? intval($_POST['id_day']) : null;

try {
    // Si se proporciona id_day, verificar que existe y el usuario tiene acceso
    if ($idDay !== null) {
        $stmt = $pdo->prepare("
            SELECT d.id 
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
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'No tienes acceso a este día o no existe']);
            exit;
        }
    }

    // Insertar workout
    $stmt = $pdo->prepare("
        INSERT INTO workout (date, id_day, id_user) 
        VALUES (:date, :id_day, :id_user)
    ");
    $stmt->execute([
        ':date' => $date,
        ':id_day' => $idDay,
        ':id_user' => $userId
    ]);

    $workoutId = $pdo->lastInsertId();

    echo json_encode([
        'ok' => true,
        'msg' => 'Workout creado correctamente',
        'workout' => [
            'id' => $workoutId,
            'date' => $date,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'duration' => null,
            'completed' => 0,
            'id_day' => $idDay,
            'id_user' => $userId
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al crear el workout', 'error' => $e->getMessage()]);
}
?>