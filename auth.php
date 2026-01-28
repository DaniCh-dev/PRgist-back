<?php
require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$jwtSecret = $_ENV['JWT_SECRET'];

/**
 * Verifica el token JWT del header Authorization
 * @return object Datos decodificados del token (incluye user_id en ->sub)
 * @throws Exception Si el token no es válido
 */
function verifyToken()
{
    global $jwtSecret;

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Token no proporcionado']);
        exit;
    }

    $jwt = $matches[1];

    try {
        $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
        return $decoded;
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Token inválido o expirado']);
        exit;
    }
}

/**
 * Verifica el token y devuelve el ID del usuario autenticado
 * @return int ID del usuario
 */
function requireAuth()
{
    $decoded = verifyToken();
    return $decoded->sub; // user_id
}
?>