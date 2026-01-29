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
    // Obtener la rutina activa del usuario desde user_routine
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.name,
            r.id_owner,
            ur.active
        FROM user_routine ur
        JOIN routine r ON r.id = ur.routine_id
        WHERE ur.user_id = :user_id AND ur.active = 1
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $userId]);
    $routine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$routine) {
        echo json_encode([
            'ok' => true,
            'msg' => 'No tienes ninguna rutina activa',
            'routine' => null
        ]);
        exit;
    }

    // Convertir active a int
    $routine['active'] = (int) $routine['active'];

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutina activa obtenida correctamente',
        'routine' => $routine
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener la rutina activa', 'error' => $e->getMessage()]);
}
?>