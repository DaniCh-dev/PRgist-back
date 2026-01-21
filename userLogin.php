<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php'; // Tu conexión PDO

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Recoger datos del formulario
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validaciones mínimas
$errores = [];
if ($email === '')
    $errores[] = 'El correo electrónico es obligatorio.';
if ($password === '')
    $errores[] = 'La contraseña es obligatoria.';

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

// Buscar usuario por email
try {
    $sql = "SELECT id, name, email, password FROM User WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Usuario no encontrado
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    // Inicio de sesión exitoso
    echo json_encode([
        'ok' => true,
        'msg' => 'Inicio de sesión exitoso',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al iniciar sesión', 'error' => $e->getMessage()]);
}
?>