<?php
/**
 * ============================================================
 * ARCHIVO: admin/logout.php
 * PROPÓSITO: Cierra la sesión del administrador de forma segura.
 * ============================================================
 */
session_start();

// 1) Limpiamos datos de sesión en servidor.
$_SESSION = [];

// 2) También invalidamos cookie para cerrar sesión completo.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3) Destruimos sesión y volvemos al login.
session_destroy();

header('Location: login.php');
exit;
