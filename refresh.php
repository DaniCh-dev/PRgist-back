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

// Recoger refresh token del body
$refreshToken = trim($_POST['refresh_token'] ?? '');
if ($refreshToken === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'Refresh token obligatorio']);
    exit;
}

try {
    // Verificar que el refresh token exista y no haya expirado
    $stmt = $pdo->prepare("SELECT r.id, r.user_id, r.expires_at, u.name, u.email 
                           FROM RefreshToken r
                           JOIN User u ON u.id = r.user_id
                           WHERE r.token = :token");
    $stmt->execute([':token' => $refreshToken]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Refresh token inválido']);
        exit;
    }

    if (strtotime($tokenData['expires_at']) < time()) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Refresh token expirado']);
        exit;
    }

    // Generar nuevo JWT
    $payload = [
        'iat' => time(),
        'exp' => time() + $jwtExpiration,
        'sub' => $tokenData['user_id'],
        'email' => $tokenData['email'],
        'name' => $tokenData['name']
    ];
    $jwt = JWT::encode($payload, $jwtSecret, 'HS256');

    // Opcional: refresh token rotativo
    // Generar nuevo refresh token y actualizar DB
    $newRefreshToken = bin2hex(random_bytes(64));
    $newExpiresAt = date('Y-m-d H:i:s', time() + $refreshExpiration);

    $stmt = $pdo->prepare("UPDATE RefreshToken SET token = :newToken, expires_at = :expiresAt WHERE id = :id");
    $stmt->execute([
        ':newToken' => $newRefreshToken,
        ':expiresAt' => $newExpiresAt,
        ':id' => $tokenData['id']
    ]);

    // Respuesta JSON
    echo json_encode([
        'ok' => true,
        'msg' => 'Token renovado correctamente',
        'jwt' => $jwt,
        'refresh_token' => $newRefreshToken,
        'user' => [
            'id' => $tokenData['user_id'],
            'name' => $tokenData['name'],
            'email' => $tokenData['email']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al renovar token', 'error' => $e->getMessage()]);
}
