<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php'; // Tu conexión PDO
require __DIR__ . '/vendor/autoload.php'; // Librería JWT
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
if ($email === '') $errores[] = 'El correo electrónico es obligatorio.';
if ($password === '') $errores[] = 'La contraseña es obligatoria.';

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

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    // -----------------------
    // 🔹 Generar JWT
    // -----------------------
    $key = 'TU_CLAVE_SECRETA_SUPERSEGURA'; // Cambiar por una clave larga y secreta
    $payload = [
        'iat' => time(),                // Tiempo de emisión
        'exp' => time() + 3600,         // Expira en 1 hora
        'sub' => $user['id'],           // Usuario
        'email' => $user['email'],
        'name' => $user['name']
    ];
    $jwt = JWT::encode($payload, $key, 'HS256');

    // -----------------------
    // Respuesta exitosa
    // -----------------------
    echo json_encode([
        'ok' => true,
        'msg' => 'Inicio de sesión exitoso',
        'token' => $jwt,   // Token JWT
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
