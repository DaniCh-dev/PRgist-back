<?php
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

try {
    // Obtener todos los ejercicios del usuario
    $stmt = $pdo->prepare("SELECT id, name FROM Exercise WHERE id_user = :id_user ORDER BY name ASC");
    $stmt->execute([':id_user' => $userId]);
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'msg' => 'Ejercicios obtenidos correctamente',
        'count' => count($exercises),
        'exercises' => $exercises
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener ejercicios', 'error' => $e->getMessage()]);
}
?>