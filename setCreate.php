<?php
// createSetEntry.php
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
$idWorkout = intval($_POST['id_workout'] ?? 0);
$idExercise = intval($_POST['id_exercise'] ?? 0);
$nReps = intval($_POST['n_reps'] ?? 0);
$timeBreak = isset($_POST['time_break']) ? intval($_POST['time_break']) : null;
$weight = isset($_POST['weight']) ? floatval($_POST['weight']) : null;

// Validaciones
$errores = [];
if ($idWorkout <= 0) {
    $errores[] = 'El ID del workout es obligatorio';
}
if ($idExercise <= 0) {
    $errores[] = 'El ID del ejercicio es obligatorio';
}
if ($nReps <= 0) {
    $errores[] = 'El número de repeticiones es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que el workout existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM workout WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':id' => $idWorkout,
        ':id_user' => $userId
    ]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar este workout o no existe']);
        exit;
    }

    // Verificar que el ejercicio existe
    $stmt = $pdo->prepare("SELECT id, name FROM exercise WHERE id = :id");
    $stmt->execute([':id' => $idExercise]);
    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exercise) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'El ejercicio no existe']);
        exit;
    }

    // Obtener la siguiente posición disponible en este workout
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(position), 0) + 1 as next_position FROM setentry WHERE id_workout = :id_workout");
    $stmt->execute([':id_workout' => $idWorkout]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC)['next_position'];

    // Insertar set
    $stmt = $pdo->prepare("
        INSERT INTO setentry (position, n_reps, time_break, weight, id_exercise, id_workout) 
        VALUES (:position, :n_reps, :time_break, :weight, :id_exercise, :id_workout)
    ");
    $stmt->execute([
        ':position' => $position,
        ':n_reps' => $nReps,
        ':time_break' => $timeBreak,
        ':weight' => $weight,
        ':id_exercise' => $idExercise,
        ':id_workout' => $idWorkout
    ]);

    $setId = $pdo->lastInsertId();

    echo json_encode([
        'ok' => true,
        'msg' => 'Serie creada correctamente',
        'set' => [
            'id' => $setId,
            'position' => $position,
            'n_reps' => $nReps,
            'time_break' => $timeBreak,
            'weight' => $weight,
            'completed' => 0,
            'id_exercise' => $idExercise,
            'exercise_name' => $exercise['name'],
            'id_workout' => $idWorkout
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al crear la serie', 'error' => $e->getMessage()]);
}
?>