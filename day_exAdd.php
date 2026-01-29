<?php
// addExerciseToDay.php
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
$idDay = intval($_POST['id_day'] ?? 0);
$idExercise = intval($_POST['id_exercise'] ?? 0);
$nSets = intval($_POST['n_sets'] ?? 0);
$nReps = intval($_POST['n_reps'] ?? 0);
$timeBreak = intval($_POST['time_break'] ?? 0);

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

    // Verificar que el ejercicio existe
    $stmt = $pdo->prepare("SELECT id, name FROM exercise WHERE id = :id");
    $stmt->execute([':id' => $idExercise]);
    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exercise) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'El ejercicio no existe']);
        exit;
    }

    // Verificar que no esté ya agregado
    $stmt = $pdo->prepare("SELECT id_day FROM day_exercise WHERE id_day = :id_day AND id_exercise = :id_exercise");
    $stmt->execute([
        ':id_day' => $idDay,
        ':id_exercise' => $idExercise
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Este ejercicio ya está agregado a este día']);
        exit;
    }

    // Insertar ejercicio en el día
    $stmt = $pdo->prepare("
        INSERT INTO day_exercise (id_day, id_exercise, n_sets, n_reps, time_break) 
        VALUES (:id_day, :id_exercise, :n_sets, :n_reps, :time_break)
    ");
    $stmt->execute([
        ':id_day' => $idDay,
        ':id_exercise' => $idExercise,
        ':n_sets' => $nSets > 0 ? $nSets : null,
        ':n_reps' => $nReps > 0 ? $nReps : null,
        ':time_break' => $timeBreak > 0 ? $timeBreak : null
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Ejercicio agregado al día correctamente',
        'day_exercise' => [
            'id_day' => $idDay,
            'id_exercise' => $idExercise,
            'exercise_name' => $exercise['name'],
            'n_sets' => $nSets > 0 ? $nSets : null,
            'n_reps' => $nReps > 0 ? $nReps : null,
            'time_break' => $timeBreak > 0 ? $timeBreak : null
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al agregar ejercicio', 'error' => $e->getMessage()]);
}
?>