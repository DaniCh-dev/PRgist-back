<?php
// completeSetEntry.php
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

// Recoger ID de la serie
$setId = intval($_POST['id'] ?? 0);

// Validación
if ($setId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID de la serie es obligatorio']);
    exit;
}

try {
    // Verificar que la serie existe y su workout pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT s.id, s.completed 
        FROM setentry s
        JOIN workout w ON w.id = s.id_workout
        WHERE s.id = :id AND w.id_user = :id_user
    ");
    $stmt->execute([
        ':id' => $setId,
        ':id_user' => $userId
    ]);
    $set = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$set) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar esta serie o no existe']);
        exit;
    }

    // Alternar estado de completado
    $newCompleted = $set['completed'] == 1 ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE setentry SET completed = :completed WHERE id = :id");
    $stmt->execute([
        ':completed' => $newCompleted,
        ':id' => $setId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => $newCompleted == 1 ? 'Serie completada correctamente' : 'Serie marcada como incompleta',
        'set' => [
            'id' => $setId,
            'completed' => $newCompleted
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al alternar el estado de la serie', 'error' => $e->getMessage()]);
}
?>