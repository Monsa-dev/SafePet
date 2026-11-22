<?php
/**
 * ================================================================
 * ARCHIVO: cuidadores/logout.php
 * PROPÓSITO: Cierra la sesión del cuidador de forma segura.
 * Proceso:
 *  1. Iniciar y destruir la sesión
 *  2. Eliminar la cookie de sesión del navegador
 *  3. Redirigir al login con un mensaje de despedida
 * ================================================================
 */

session_start();

// Eliminar todas las variables de sesión del servidor
session_unset();

// Destruir la sesión en el servidor
session_destroy();

// También invalidar la cookie de sesión en el navegador
// Esto evita que el token antiguo pueda reutilizarse
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',                   // Valor vacío
        time() - 42000,       // Fecha en el pasado → el navegador la elimina
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirigir al login con mensaje de éxito
header('Location: login.php?logout=1');
exit;
?>
