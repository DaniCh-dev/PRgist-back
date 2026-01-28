<?php
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
$routineId = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');

// Validaciones
$errores = [];
if ($routineId <= 0) {
    $errores[] = 'El ID de la rutina es obligatorio';
}
if ($name === '') {
    $errores[] = 'El nombre de la rutina es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que la rutina existe y es tuya
    $stmt = $pdo->prepare("SELECT id FROM routine WHERE id = :id AND id_owner = :id_owner");
    $stmt->execute([
        ':id' => $routineId,
        ':id_owner' => $userId
    ]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar esta rutina o no existe']);
        exit;
    }

    // Verificar duplicados
    $stmt = $pdo->prepare("SELECT id FROM routine WHERE name = :name AND id_owner = :id_owner AND id != :id");
    $stmt->execute([
        ':name' => $name,
        ':id_owner' => $userId,
        ':id' => $routineId
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Ya tienes otra rutina con ese nombre']);
        exit;
    }

    // Actualizar rutina
    $stmt = $pdo->prepare("UPDATE routine SET name = :name WHERE id = :id AND id_owner = :id_owner");
    $stmt->execute([
        ':name' => $name,
        ':id' => $routineId,
        ':id_owner' => $userId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Rutina actualizada correctamente',
        'routine' => [
            'id' => $routineId,
            'name' => $name
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al actualizar la rutina', 'error' => $e->getMessage()]);
}
?>