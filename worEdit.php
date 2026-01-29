<?php
// updateWorkout.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

// Solo POST/PUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$userId = requireAuth();

// Recoger datos
$workoutId = intval($_POST['id'] ?? 0);
$completed = isset($_POST['completed']) ? intval($_POST['completed']) : null;

// Validaciones
$errores = [];
if ($workoutId <= 0) {
    $errores[] = 'El ID del workout es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que el workout existe y es del usuario
    $stmt = $pdo->prepare("SELECT id FROM workout WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':id' => $workoutId,
        ':id_user' => $userId
    ]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar este workout o no existe']);
        exit;
    }

    // Actualizar completed
    if ($completed !== null) {
        $stmt = $pdo->prepare("UPDATE workout SET completed = :completed WHERE id = :id AND id_user = :id_user");
        $stmt->execute([
            ':completed' => $completed,
            ':id' => $workoutId,
            ':id_user' => $userId
        ]);
    }

    echo json_encode([
        'ok' => true,
        'msg' => 'Workout actualizado correctamente',
        'workout' => [
            'id' => $workoutId,
            'completed' => $completed
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al actualizar workout', 'error' => $e->getMessage()]);
}
?>