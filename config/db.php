<?php
/**
 * Archivo: config/db.php
 * Idea principal:
 * Tener un solo punto de conexión a la base de datos para todo el proyecto.
 */

// ── Datos de conexión ───────────────────────────────────────────
// En Render se configuran como variables de entorno; localmente conserva
// los valores de desarrollo.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'safepet_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * getPDO()
 * Qué hace:
 * Devuelve una conexión lista para usar.
 *
 * Por qué está así:
 * Guarda la conexión en una variable estática para no abrirla varias veces
 * en la misma petición y así mantener el código más rápido y ordenado.
 */
function getPDO(): PDO
{
    // Esta variable conserva la conexión durante toda la petición actual.
    static $pdo = null;

    // Si aún no existe conexión, la creamos una sola vez.
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        // Ajustes recomendados para trabajar de forma clara y segura.
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // Guardamos el error real en log para revisar después.
            error_log("Error de conexión BD: " . $e->getMessage());

            // Al usuario final le mostramos un mensaje simple.
            die("Error al conectar con la base de datos. Por favor intente más tarde.");
        }
    }

    return $pdo;
}
