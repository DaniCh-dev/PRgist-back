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
    // Obtener rutinas del usuario
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name
        FROM routine
        WHERE id_owner = :id_owner
        ORDER BY name ASC
    ");
    $stmt->execute([':id_owner' => $userId]);
    $routines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutinas obtenidas correctamente',
        'count' => count($routines),
        'routines' => $routines
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener rutinas', 'error' => $e->getMessage()]);
}
?>