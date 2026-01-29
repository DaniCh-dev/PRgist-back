<?php
// removeDayExercise.php
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

// Recoger datos
$idDay = intval($_POST['id_day'] ?? $_GET['id_day'] ?? 0);
$idExercise = intval($_POST['id_exercise'] ?? $_GET['id_exercise'] ?? 0);

// Validaciones
$errores = [];
if ($idDay <= 0) {
    $errores[] = 'El ID del día es obligatorio';
}
if ($idExercise <= 0) {
    $errores[] = 'El ID del ejercicio es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que el día existe y su rutina pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT d.id 
        FROM day d
        JOIN routine r ON r.id = d.id_routine
        WHERE d.id = :id AND r.id_owner = :id_owner
    ");
    $stmt->execute([
        ':id' => $idDay,
        ':id_owner' => $userId
    ]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar este día o no existe']);
        exit;
    }

    // Verificar que el ejercicio está en el día
    $stmt = $pdo->prepare("
        SELECT de.id_day, e.name 
        FROM day_exercise de
        JOIN exercise e ON e.id = de.id_exercise
        WHERE de.id_day = :id_day AND de.id_exercise = :id_exercise
    ");
    $stmt->execute([
        ':id_day' => $idDay,
        ':id_exercise' => $idExercise
    ]);
    $dayExercise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dayExercise) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Este ejercicio no está en este día']);
        exit;
    }

    // Eliminar ejercicio del día
    $stmt = $pdo->prepare("DELETE FROM day_exercise WHERE id_day = :id_day AND id_exercise = :id_exercise");
    $stmt->execute([
        ':id_day' => $idDay,
        ':id_exercise' => $idExercise
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Ejercicio eliminado del día correctamente',
        'day_exercise' => [
            'id_day' => $idDay,
            'id_exercise' => $idExercise,
            'exercise_name' => $dayExercise['name']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar ejercicio', 'error' => $e->getMessage()]);
}
?>