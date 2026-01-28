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

// Recoger parámetro de búsqueda
$search = trim($_GET['search'] ?? '');

// Validación
if ($search === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El parámetro de búsqueda es obligatorio']);
    exit;
}

try {
    // Buscar ejercicios por nombre (LIKE) de TODOS los usuarios
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.name,
            e.id_user,
            u.name as owner_name,
            u.email as owner_email
        FROM exercise e
        JOIN user u ON u.id = e.id_user
        WHERE e.name LIKE :search 
        ORDER BY e.name ASC
    ");
    
    $stmt->execute([
        ':search' => '%' . $search . '%'
    ]);
    
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'msg' => 'Búsqueda completada',
        'search' => $search,
        'count' => count($exercises),
        'exercises' => $exercises
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al buscar ejercicios', 'error' => $e->getMessage()]);
}
?>