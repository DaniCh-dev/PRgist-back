<?php
// deleteSetEntry.php
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

// Recoger ID de la serie
$setId = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// Validación
if ($setId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID de la serie es obligatorio']);
    exit;
}

try {
    // Verificar que la serie existe y su workout pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT s.id, s.position, s.id_workout, e.name as exercise_name
        FROM setentry s
        JOIN workout w ON w.id = s.id_workout
        JOIN exercise e ON e.id = s.id_exercise
        WHERE s.id = :id AND w.id_user = :id_user
    ");
    $stmt->execute([
        ':id' => $setId,
        ':id_user' => $userId
    ]);
    $set = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$set) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para eliminar esta serie o no existe']);
        exit;
    }

    // Eliminar serie
    $stmt = $pdo->prepare("DELETE FROM setentry WHERE id = :id");
    $stmt->execute([':id' => $setId]);

    // Reordenar posiciones del workout (opcional pero recomendado)
    $stmt = $pdo->prepare("
        UPDATE setentry 
        SET position = position - 1 
        WHERE id_workout = :id_workout AND position > :position
    ");
    $stmt->execute([
        ':id_workout' => $set['id_workout'],
        ':position' => $set['position']
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Serie eliminada correctamente',
        'set' => [
            'id' => $setId,
            'position' => $set['position'],
            'exercise_name' => $set['exercise_name']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar la serie', 'error' => $e->getMessage()]);
}
?>