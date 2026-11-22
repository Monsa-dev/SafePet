<!-- ============================================================
     ARCHIVO: includes/footer.php
     PROPÓSITO: Pie de página reutilizable con información
     de contacto, links rápidos y redes sociales. Se incluye
     al final del <body> en todas las páginas del sitio.
     También carga el JavaScript principal del proyecto.
     ============================================================ -->

<!-- Panel lateral del carrito de compras
     Se muestra/oculta desde cualquier página con la función toggleCart() en JS -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-panel" id="cartPanel">
    <div class="cart-header">
        <h3><i class="fas fa-shopping-cart"></i> Mi Carrito</h3>
        <button class="cart-close" onclick="toggleCart()"><i class="fas fa-times"></i></button>
    </div>
    <!-- Los productos del carrito se renderizan aquí dinámicamente con JS -->
    <div class="cart-items" id="cartItems">
        <p class="cart-empty"><i class="fas fa-box-open"></i> Tu carrito está vacío</p>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <strong>Total:</strong> <span id="cartTotal">$0</span>
        </div>
        <!-- Al finalizar compra se redirige a WhatsApp con el resumen del pedido -->
        <button class="btn-whatsapp" onclick="finalizarCompra()">
            <i class="fab fa-whatsapp"></i> Finalizar por WhatsApp
        </button>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     FOOTER PRINCIPAL
     ════════════════════════════════════════════════════════════ -->
<footer class="footer-main">
    <div class="container footer-grid">

        <!-- Columna 1: Información de la empresa -->
        <div class="footer-col">
            <img src="<?= $basePath ?? '' ?>img/logo safepet.png" alt="SafePet" class="footer-logo">
            <p>Conectamos dueños de mascotas con cuidadores de confianza. Tu mascota merece lo mejor.</p>
            <div class="footer-redes">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://wa.me/573242956121" aria-label="WhatsApp" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>

        <!-- Columna 2: Links rápidos para dueños -->
        <div class="footer-col">
            <h4>Links Rápidos</h4>
            <ul>
                <li><a href="<?= $basePath ?? '' ?>index.php">Inicio</a></li>
                <li><a href="<?= $basePath ?? '' ?>cuidadores.php">Cuidadores</a></li>
                <li><a href="<?= $basePath ?? '' ?>productos.php">Productos</a></li>
                <li><a href="<?= $basePath ?? '' ?>contacto.php">Contacto</a></li>
            </ul>
        </div>

        <!-- Columna 3: Sección de cuidadores -->
        <div class="footer-col">
            <h4>¿Eres Cuidador?</h4>
            <ul>
                <li><a href="<?= $basePath ?? '' ?>cuidadores/registro.php">Regístrate gratis</a></li>
                <li><a href="<?= $basePath ?? '' ?>cuidadores/login.php">Iniciar sesión</a></li>
            </ul>
        </div>

        <!-- Columna 4: Información de contacto -->
        <div class="footer-col">
            <h4>Contacto</h4>
            <p><i class="fab fa-whatsapp"></i> +57 324 295 6121</p>
            <p><i class="fas fa-envelope"></i> info@safepet.com</p>
            <p><i class="fas fa-map-marker-alt"></i> Colombia</p>
        </div>

    </div>

    <!-- Barra de copyright -->
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> SafePet. Todos los derechos reservados. Hecho con <i class="fas fa-heart"></i> para las mascotas.</p>
    </div>
</footer>
<!-- Fin Footer -->

<!-- Script JS principal: cargado al final del body para mejor performance -->
<script src="<?= $basePath ?? '' ?>js/main.js"></script>
</body>
</html>
