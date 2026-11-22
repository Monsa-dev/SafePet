<?php
/**
 * ============================================================
 * ARCHIVO: admin/auth.php
 * PROPÓSITO: Guard de autenticación para el panel de admin.
 * Se incluye al inicio de cada página protegida del admin.
 * Si no hay sesión activa, redirige al login.
 * ============================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay admin logueado, no dejamos entrar al panel.
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
