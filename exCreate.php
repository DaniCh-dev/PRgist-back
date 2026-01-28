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

// Recoger datos del formulario
$name = trim($_POST['name'] ?? '');

// Validaciones
$errores = [];
if ($name === '') {
    $errores[] = 'El nombre del ejercicio es obligatorio';
}

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

// Insertar ejercicio en la tabla Exercise
try {
    // Verificar si el ejercicio ya existe
    $stmt = $pdo->prepare("SELECT id FROM Exercise WHERE name = :name");
    $stmt->execute([':name' => $name]);
    $existingExercise = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingExercise) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'El ejercicio ya existe']);
        exit;
    }

    // Insertar nuevo ejercicio
    $sql = "INSERT INTO Exercise (name) VALUES (:name)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name' => $name]);

    $exerciseId = $pdo->lastInsertId();

    echo json_encode([
        'ok' => true,
        'msg' => 'Ejercicio creado correctamente',
        'exercise' => [
            'id' => $exerciseId,
            'name' => $name
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    $msg = 'Error al crear el ejercicio';
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $msg = 'El ejercicio ya existe';
    }
    echo json_encode(['ok' => false, 'msg' => $msg, 'error' => $e->getMessage()]);
}
?>