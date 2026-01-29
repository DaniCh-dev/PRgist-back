<?php
// finishWorkout.php
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

// Recoger ID del workout
$workoutId = intval($_POST['id'] ?? 0);

// Validación
if ($workoutId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID del workout es obligatorio']);
    exit;
}

try {
    // Verificar que el workout existe y es del usuario
    $stmt = $pdo->prepare("SELECT id, fecha_inicio, fecha_fin FROM workout WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':id' => $workoutId,
        ':id_user' => $userId
    ]);
    $workout = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$workout) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar este workout o no existe']);
        exit;
    }

    if ($workout['fecha_inicio'] === null) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Este workout no ha sido iniciado']);
        exit;
    }

    if ($workout['fecha_fin'] !== null) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Este workout ya ha sido finalizado']);
        exit;
    }

    // Actualizar fecha_fin con la hora actual del sistema
    $fechaFin = date('Y-m-d H:i:s');

    // Calcular duración en segundos
    $inicio = new DateTime($workout['fecha_inicio']);
    $fin = new DateTime($fechaFin);
    $duration = $fin->getTimestamp() - $inicio->getTimestamp();

    $stmt = $pdo->prepare("
        UPDATE workout 
        SET fecha_fin = :fecha_fin, duration = :duration, completed = 1 
        WHERE id = :id AND id_user = :id_user
    ");
    $stmt->execute([
        ':fecha_fin' => $fechaFin,
        ':duration' => $duration,
        ':id' => $workoutId,
        ':id_user' => $userId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Workout finalizado correctamente',
        'workout' => [
            'id' => $workoutId,
            'fecha_inicio' => $workout['fecha_inicio'],
            'fecha_fin' => $fechaFin,
            'duration' => $duration,
            'completed' => 1
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al finalizar workout', 'error' => $e->getMessage()]);
}
?>