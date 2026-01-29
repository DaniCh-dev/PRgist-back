<?php
// updateSetEntry.php
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
$setId = intval($_POST['id'] ?? 0);
$nReps = isset($_POST['n_reps']) ? intval($_POST['n_reps']) : null;
$timeBreak = isset($_POST['time_break']) ? intval($_POST['time_break']) : null;
$weight = isset($_POST['weight']) ? floatval($_POST['weight']) : null;

// Validaciones
$errores = [];
if ($setId <= 0) {
    $errores[] = 'El ID de la serie es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que la serie existe y su workout pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT s.id 
        FROM setentry s
        JOIN workout w ON w.id = s.id_workout
        WHERE s.id = :id AND w.id_user = :id_user
    ");
    $stmt->execute([
        ':id' => $setId,
        ':id_user' => $userId
    ]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar esta serie o no existe']);
        exit;
    }

    // Construir query dinámicamente
    $updates = [];
    $params = [':id' => $setId];
    
    if ($nReps !== null) {
        $updates[] = "n_reps = :n_reps";
        $params[':n_reps'] = $nReps;
    }
    
    if ($timeBreak !== null) {
        $updates[] = "time_break = :time_break";
        $params[':time_break'] = $timeBreak;
    }
    
    if ($weight !== null) {
        $updates[] = "weight = :weight";
        $params[':weight'] = $weight;
    }

    if (empty($updates)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'msg' => 'No hay campos para actualizar']);
        exit;
    }

    // Actualizar serie
    $sql = "UPDATE setentry SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'ok' => true,
        'msg' => 'Serie actualizada correctamente',
        'set' => [
            'id' => $setId,
            'n_reps' => $nReps,
            'time_break' => $timeBreak,
            'weight' => $weight
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al actualizar la serie', 'error' => $e->getMessage()]);
}
?>