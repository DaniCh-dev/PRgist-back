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
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validaciones mínimas
$errores = [];
if ($name === '')
    $errores[] = 'El nombre es obligatorio.';
if ($email === '') {
    $errores[] = 'El correo electrónico es obligatorio.';
} elseif (!preg_match('/^[\w\.-]+@[\w\.-]+\.\w{2,}$/', $email)) {
    $errores[] = 'El correo electrónico no tiene un formato válido.';
}
if ($password === '')
    $errores[] = 'La contraseña es obligatoria.';

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

// Hashear la contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insertar en la tabla User
try {
    $sql = "INSERT INTO User (name, email, password) 
            VALUES (:name, :email, :password)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $password_hash
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Usuario registrado correctamente',
        'name' => $name,
        'email' => $email
    ]);
} catch (Throwable $e) {
    // Manejo de errores, por ejemplo si el usuario/email ya existe
    http_response_code(500);
    $msg = 'Error al registrar el usuario';
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $msg = 'El correo ya está en uso';
    }
    echo json_encode(['ok' => false, 'msg' => $msg, 'error' => $e->getMessage()]);
}
?>