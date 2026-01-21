<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$jwtSecret = $_ENV['JWT_SECRET'];
$jwtExpiration = intval($_ENV['JWT_EXPIRATION']);
$refreshExpiration = intval($_ENV['REFRESH_EXPIRATION']);

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Recoger email y password
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

$errores = [];
if ($email === '')
    $errores[] = 'El correo es obligatorio';
if ($password === '')
    $errores[] = 'La contraseña es obligatoria';
if ($errores) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

// Buscar usuario
try {
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM User WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Correo o contraseña incorrectos']);
        exit;
    }

    // Crear JWT
    $payload = [
        'iat' => time(),
        'exp' => time() + $jwtExpiration,
        'sub' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name']
    ];
    $jwt = JWT::encode($payload, $jwtSecret, 'HS256');

    // Crear refresh token aleatorio
    $refreshToken = bin2hex(random_bytes(64));
    $expiresAt = date('Y-m-d H:i:s', time() + $refreshExpiration);

    // Guardar refresh token en DB
    $stmt = $pdo->prepare("INSERT INTO RefreshToken (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
    $stmt->execute([
        ':user_id' => $user['id'],
        ':token' => $refreshToken,
        ':expires_at' => $expiresAt
    ]);

    // Respuesta JSON
    echo json_encode([
        'ok' => true,
        'msg' => 'Inicio de sesión exitoso',
        'jwt' => $jwt,
        'refresh_token' => $refreshToken,
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
