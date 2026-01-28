<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

// Solo PUT/POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$userId = requireAuth();

// Recoger datos
$exerciseId = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');

// Validaciones
$errores = [];
if ($exerciseId <= 0) {
    $errores[] = 'El ID del ejercicio es obligatorio';
}
if ($name === '') {
    $errores[] = 'El nombre del ejercicio es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

try {
    // Verificar que el ejercicio existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id, name FROM Exercise WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':id' => $exerciseId,
        ':id_user' => $userId
    ]);
    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exercise) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para modificar este ejercicio o no existe']);
        exit;
    }

    // Verificar que no exista otro ejercicio con el mismo nombre para este usuario
    $stmt = $pdo->prepare("SELECT id FROM Exercise WHERE name = :name AND id_user = :id_user AND id != :id");
    $stmt->execute([
        ':name' => $name,
        ':id_user' => $userId,
        ':id' => $exerciseId
    ]);
    $duplicate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($duplicate) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Ya tienes otro ejercicio con ese nombre']);
        exit;
    }

    // Actualizar el ejercicio
    $stmt = $pdo->prepare("UPDATE Exercise SET name = :name WHERE id = :id AND id_user = :id_user");
    $stmt->execute([
        ':name' => $name,
        ':id' => $exerciseId,
        ':id_user' => $userId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Ejercicio actualizado correctamente',
        'exercise' => [
            'id' => $exerciseId,
            'name' => $name,
            'id_user' => $userId
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al actualizar el ejercicio', 'error' => $e->getMessage()]);
}
?>