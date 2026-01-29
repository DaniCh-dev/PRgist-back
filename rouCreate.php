<?php
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
$name = trim($_POST['name'] ?? '');

// Validaciones
$errores = [];
if ($name === '') {
    $errores[] = 'El nombre de la rutina es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar duplicados
    $stmt = $pdo->prepare("SELECT id FROM routine WHERE name = :name AND id_owner = :id_owner");
    $stmt->execute([
        ':name' => $name,
        ':id_owner' => $userId
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Ya tienes una rutina con ese nombre']);
        exit;
    }

    // Insertar rutina
    $stmt = $pdo->prepare("INSERT INTO routine (name, id_owner) VALUES (:name, :id_owner)");
    $stmt->execute([
        ':name' => $name,
        ':id_owner' => $userId
    ]);

    $routineId = $pdo->lastInsertId();

    // Asignar automáticamente la rutina al usuario que la creó (desactivada por defecto)
    $stmt = $pdo->prepare("INSERT INTO user_routine (user_id, routine_id, active) VALUES (:user_id, :routine_id, 0)");
    $stmt->execute([
        ':user_id' => $userId,
        ':routine_id' => $routineId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutina creada y asignada correctamente',
        'routine' => [
            'id' => $routineId,
            'name' => $name,
            'id_owner' => $userId,
            'assigned' => true,
            'active' => 0
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al crear la rutina', 'error' => $e->getMessage()]);
}
?>