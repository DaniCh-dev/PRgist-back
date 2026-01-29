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

// Recoger ID de la rutina
$routineId = intval($_GET['id_routine'] ?? 0);

// Validación
if ($routineId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'El ID de la rutina es obligatorio']);
    exit;
}

try {
    // Verificar que el usuario tiene acceso a la rutina (es propietario O la tiene asignada)
    $stmt = $pdo->prepare("
        SELECT r.id, r.name 
        FROM routine r
        LEFT JOIN user_routine ur ON ur.routine_id = r.id AND ur.user_id = :user_id
        WHERE r.id = :id AND (r.id_owner = :user_id2 OR ur.user_id IS NOT NULL)
    ");
    $stmt->execute([
        ':id' => $routineId,
        ':user_id' => $userId,
        ':user_id2' => $userId
    ]);
    $routine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$routine) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para ver esta rutina o no existe']);
        exit;
    }

    // Obtener días de la rutina ordenados por día de la semana
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            day_of_week,
            id_routine
        FROM day
        WHERE id_routine = :id_routine
        ORDER BY FIELD(day_of_week, 'L', 'M', 'X', 'J', 'V', 'S', 'D')
    ");
    $stmt->execute([':id_routine' => $routineId]);
    $days = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'msg' => 'Días obtenidos correctamente',
        'routine' => [
            'id' => $routine['id'],
            'name' => $routine['name']
        ],
        'count' => count($days),
        'days' => $days
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener los días', 'error' => $e->getMessage()]);
}
?>