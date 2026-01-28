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
    // Obtener TODOS los ejercicios de TODOS los usuarios
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.name,
            e.id_user,
            u.name as owner_name,
            u.email as owner_email
        FROM exercise e
        JOIN user u ON u.id = e.id_user
        ORDER BY e.name ASC
    ");
    $stmt->execute();
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'msg' => 'Todos los ejercicios obtenidos correctamente',
        'count' => count($exercises),
        'exercises' => $exercises
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener ejercicios', 'error' => $e->getMessage()]);
}
?>