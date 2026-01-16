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
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validaciones mínimas
$errores = [];
if ($username === '') $errores[] = 'El nombre de usuario es obligatorio.';
if ($email === '')    $errores[] = 'El correo electrónico es obligatorio.';
if ($password === '') $errores[] = 'La contraseña es obligatoria.';

if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

// Hashear la contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insertar en la tabla users
try {
    $sql = "INSERT INTO users (username, email, password_hash) 
            VALUES (:username, :email, :password_hash)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':password_hash' => $password_hash
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Usuario registrado correctamente',
        'username' => $username,
        'email' => $email
    ]);
} catch (Throwable $e) {
    // Manejo de errores, por ejemplo si el usuario/email ya existe
    http_response_code(500);
    $msg = 'Error al registrar el usuario';
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $msg = 'El nombre de usuario o el correo ya están en uso';
    }
    echo json_encode(['ok' => false, 'msg' => $msg, 'error' => $e->getMessage()]);
}
?>
