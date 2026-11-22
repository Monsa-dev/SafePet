<?php
/**
 * ============================================================
 * ARCHIVO: includes/header.php
 * PROPÓSITO: Cabecera HTML reutilizable que se incluye en
 * todas las páginas públicas del sitio. Contiene el DOCTYPE,
 * la etiqueta <head> con meta-tags, estilos y el inicio del
 * <body>. Recibe la variable $pageTitulo para personalizar
 * el título de cada página.
 * ============================================================
 */

// Iniciamos sesión aquí para que esté disponible en todas las páginas
// session_status() evita errores si la sesión ya fue iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no se definió el título de página, usamos uno genérico
if (!isset($pageTitulo)) {
    $pageTitulo = "SafePet - Cuidado para tu mascota";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Codificación de caracteres: UTF-8 para soportar español y caracteres especiales -->
    <meta charset="UTF-8">

    <!-- Viewport: hace que el sitio sea responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Descripción SEO del sitio web -->
    <meta name="description" content="SafePet - Conectamos dueños de mascotas con cuidadores de confianza. Productos para tu mascota.">

    <!-- Título dinámico de la pestaña del navegador -->
    <title><?= htmlspecialchars($pageTitulo) ?></title>

    <!-- Hoja de estilos principal del proyecto -->
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>css/style.css">

    <!-- Font Awesome: iconos vectoriales (carrito, menú, pata, etc.) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts: Poppins para tipografía moderna y legible -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     BARRA SUPERIOR DE ANUNCIOS
     Muestra un mensaje de bienvenida / promo en la parte alta
     ══════════════════════════════════════════════════════════ -->
<div class="top-bar">
    <p><i class="fas fa-paw"></i> ¡Bienvenido a SafePet! El cuidado que tu mascota merece &nbsp;|&nbsp; <i class="fab fa-whatsapp"></i> +57 324 295 6121</p>
</div>

<!-- ══════════════════════════════════════════════════════════
     HEADER PRINCIPAL
     Contiene el logo y la navegación principal del sitio
     ══════════════════════════════════════════════════════════ -->
<header class="header-main">
    <div class="container header-inner">

        <!-- Logo del sitio -->
        <a href="<?= $basePath ?? '' ?>index.php" class="logo">
            <img src="<?= $basePath ?? '' ?>img/logo safepet.png" alt="SafePet Logo" class="logo-img">
        </a>

        <!-- Botón hamburguesa visible solo en móviles -->
        <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <!-- Navegación principal -->
        <nav class="nav-main" id="navMain">
            <ul class="nav-list">
                <!-- Los enlaces usan $basePath para funcionar desde subcarpetas -->
                <li><a href="<?= $basePath ?? '' ?>index.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>">Inicio</a></li>
                <li><a href="<?= $basePath ?? '' ?>cuidadores.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'cuidadores.php') ? 'active' : '' ?>">Cuidadores</a></li>
                <li><a href="<?= $basePath ?? '' ?>productos.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'productos.php') ? 'active' : '' ?>">Productos</a></li>
                <li><a href="<?= $basePath ?? '' ?>contacto.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'contacto.php') ? 'active' : '' ?>">Contacto</a></li>
                <li><a href="<?= $basePath ?? '' ?>cuidadores/login.php" class="nav-link btn-cuidador">Soy Cuidador</a></li>
            </ul>
        </nav>

        <!-- Ícono del carrito de compras con contador de ítems -->
        <div class="cart-icon" id="cartIcon" onclick="toggleCart()">
            <i class="fas fa-shopping-cart"></i>
            <!-- El badge se actualiza dinámicamente con JavaScript -->
            <span class="cart-badge" id="cartBadge">0</span>
        </div>

    </div>
</header>
<!-- Fin Header Principal -->
