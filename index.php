<?php
/**
 * ================================================================
 * ARCHIVO: index.php
 * PROPÓSITO: Página de inicio (Home) de SafePet.
 * Secciones:
 *  - Hero / Banner principal con CTA
 *  - Cómo funciona SafePet (3 pasos)
 *  - Servicios ofrecidos
 *  - Cuidadores destacados (cargados desde BD)
 *  - Productos destacados (cargados desde BD)
 *  - Sección CTA final (¿Eres cuidador?)
 * ================================================================
 */

// Definimos el título de esta página antes de incluir el header
$pageTitulo = "SafePet - Inicio | Cuidado para tu mascota";

// Incluimos la conexión a la BD y el header
require_once 'config/db.php';
include 'includes/header.php';

// ── Obtener cuidadores destacados desde la BD ────────────────────
// Mostramos solo los 3 con mejor calificación (rating DESC)
try {
    $pdo = getPDO();

    // Consulta para cuidadores disponibles con mejor rating
    $sqlCuidadores = "SELECT id, nombre, especialidad, descripcion, rating, precio_hora, foto, ciudad
                      FROM cuidadores
                      WHERE activo = 1 AND disponible = 1
                      ORDER BY rating DESC
                      LIMIT 3";

    $cuidadores = $pdo->query($sqlCuidadores)->fetchAll();

} catch (Exception $e) {
    // Si la BD no está disponible, usamos datos de ejemplo (modo demo)
    $cuidadores = [
        ['id' => 1, 'nombre' => 'María García', 'especialidad' => 'Perros y Gatos',    'rating' => 4.9, 'precio_hora' => 25000, 'ciudad' => 'Bogotá',    'foto' => null],
        ['id' => 2, 'nombre' => 'Carlos López', 'especialidad' => 'Perros grandes',     'rating' => 4.8, 'precio_hora' => 20000, 'ciudad' => 'Medellín',  'foto' => null],
        ['id' => 3, 'nombre' => 'Ana Martínez', 'especialidad' => 'Mascotas exóticas', 'rating' => 4.7, 'precio_hora' => 30000, 'ciudad' => 'Cali',      'foto' => null],
    ];
}

// ── Obtener productos destacados ────────────────────────────────
try {
    $sqlProductos = "SELECT id, nombre, descripcion, precio, precio_original, categoria, portada, galeria, badge
                     FROM productos
                     WHERE activo = 1 AND destacado = 1
                     LIMIT 4";
    $productos = $pdo->query($sqlProductos)->fetchAll();
} catch (Exception $e) {
    // Datos de ejemplo si la BD no está disponible
}
?>

<!-- ════════════════════════════════════════════════════════
     SECCIÓN 1: HERO / BANNER PRINCIPAL
     Primera sección que ve el usuario. Define el mensaje
     central de la marca y los botones de acción principales.
     ════════════════════════════════════════════════════════ -->
<section class="hero">
    <div class="container hero-content">

        <!-- Texto y CTAs -->
        <div class="hero-text">
            <h1>
                Tu mascota merece<br>
                <span>el mejor cuidado</span>
            </h1>
            <p>
                Conectamos dueños de mascotas con cuidadores de confianza verificados.
                También encuentra los mejores productos para tu compañero de vida.
            </p>

            <!-- Botones de llamada a la acción -->
            <div class="hero-buttons">
                <a href="cuidadores.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar Cuidador
                </a>
                <a href="productos.php" class="btn btn-secondary">
                    <i class="fas fa-store"></i> Ver Productos
                </a>
            </div>

            <!-- Estadísticas de confianza -->
            <div style="display:flex; gap:2rem; margin-top:2rem; flex-wrap:wrap;">
                <div style="text-align:center;">
                    <strong style="display:block; font-size:1.6rem;">500+</strong>
                    <small style="opacity:0.8;">Cuidadores</small>
                </div>
                <div style="text-align:center;">
                    <strong style="display:block; font-size:1.6rem;">2K+</strong>
                    <small style="opacity:0.8;">Mascotas cuidadas</small>
                </div>
                <div style="text-align:center;">
                    <strong style="display:block; font-size:1.6rem;">4.9 </strong>
                    <small style="opacity:0.8;">Calificación</small>
                </div>
            </div>
        </div>

        <!-- Imagen / ícono animado -->
        <div class="hero-image">
            <div class="hero-image-circle">🐾</div>
        </div>

    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     SECCIÓN 2: CÓMO FUNCIONA
     Explica el proceso en 3 pasos simples para los dueños.
     ════════════════════════════════════════════════════════ -->
<section class="section section-gray">
    <div class="container">

        <div class="section-header animate-on-scroll">
            <h2>¿Cómo funciona SafePet?</h2>
            <p>Encontrar el cuidador perfecto para tu mascota es fácil y rápido.</p>
        </div>

        <div class="grid-3">
            <!-- Paso 1 -->
            <div class="service-card animate-on-scroll">
                <div class="icon"><i class="fas fa-search"></i></div>
                <h3>1. Busca un Cuidador</h3>
                <p>Explora perfiles de cuidadores verificados en tu ciudad. Lee reseñas y elige el que mejor se adapte a las necesidades de tu mascota.</p>
            </div>

            <!-- Paso 2 -->
            <div class="service-card animate-on-scroll">
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
                <h3>2. Solicita el Servicio</h3>
                <p>Envía una solicitud al cuidador elegido indicando fechas, tipo de mascota y servicios que necesitas. El cuidador la revisará y aceptará.</p>
            </div>

            <!-- Paso 3 -->
            <div class="service-card animate-on-scroll">
                <div class="icon"><i class="fas fa-heart"></i></div>
                <h3>3. Tranquilidad Total</h3>
                <p>Tu mascota estará en manos de alguien de confianza. Recibirás actualizaciones y fotos de cómo está tu compañero.</p>
            </div>
        </div>

    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     SECCIÓN 3: SERVICIOS OFRECIDOS
     ════════════════════════════════════════════════════════ -->
<section class="section section-light">
    <div class="container">

        <div class="section-header animate-on-scroll">
            <h2>Nuestros Servicios</h2>
            <p>Todo lo que tu mascota necesita, en un solo lugar.</p>
        </div>

        <div class="grid-4">
            <div class="service-card animate-on-scroll">
                <div class="icon"><i class="fas fa-home"></i></div>
                <h3>Cuidado en Casa</h3>
                <p>El cuidador va a tu hogar a cuidar a tu mascota.</p>
            </div>

            <div class="service-card animate-on-scroll">
                <div class="icon"><i class="fas fa-dog"></i></div>
                <h3>Paseo</h3>
                <p>Paseos diarios supervisados para el bienestar de tu perro.</p>
            </div>

            <div class="service-card animate-on-scroll">
                <div class="icon"><i class="fas fa-bath"></i></div>
                <h3>Guardería</h3>
                <p>Tu mascota se queda en casa del cuidador de forma segura.</p>
            </div>

            <div class="service-card animate-on-scroll">
                <div class="icon"><i class="fas fa-store"></i></div>
                <h3>Tienda Online</h3>
                <p>Productos de calidad para alimentación, salud y diversión.</p>
            </div>
        </div>

    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     SECCIÓN 4: CUIDADORES DESTACADOS
     Muestra los 3 cuidadores con mejor calificación.
     Si se cargaron desde la BD se muestran reales; si no,
     se usan los datos de demostración definidos arriba.
     ════════════════════════════════════════════════════════ -->
<section class="section section-blue">
    <div class="container">

        <div class="section-header animate-on-scroll">
            <h2>Cuidadores Destacados</h2>
            <p>Conoce algunos de nuestros mejores cuidadores verificados.</p>
        </div>

        <div class="grid-3">
            <?php foreach ($cuidadores as $c): ?>
            <div class="cuidador-card animate-on-scroll">

                <!-- Foto de perfil o ícono por defecto -->
                <div class="cuidador-card-img">
                    <?php if (!empty($c['foto'])): ?>
                        <img src="<?= htmlspecialchars($c['foto']) ?>"
                             alt="<?= htmlspecialchars($c['nombre']) ?>"
                             style="width:100%; height:100%; object-fit:contain; background:var(--color-white);">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size:5rem;color:var(--color-primary);"></i>
                    <?php endif; ?>
                </div>

                <div class="cuidador-card-body">
                    <!-- Nombre del cuidador -->
                    <h3><?= htmlspecialchars($c['nombre']) ?></h3>

                    <!-- Rating con estrellas -->
                    <div class="cuidador-rating">
                        <i class="fas fa-star"></i>
                        <?= number_format($c['rating'], 1) ?> / 5.0
                    </div>

                    <!-- Badge de disponibilidad -->
                    <span class="badge-disponible"><i class="fas fa-check-circle"></i> Disponible</span>

                    <!-- Especialidad en tag -->
                    <div class="cuidador-tags">
                        <span class="tag"><i class="fas fa-paw"></i> <?= htmlspecialchars($c['especialidad']) ?></span>
                        <span class="tag"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['ciudad']) ?></span>
                    </div>

                    <!-- Precio por hora -->
                    <div class="cuidador-precio">
                        <i class="fas fa-tag"></i>
                        Desde <?= number_format($c['precio_hora'], 0, ',', '.') ?> COP/hora
                    </div>

                    <a href="cuidador_perfil.php?id=<?= $c['id'] ?>" class="btn btn-blue" style="width:100%; justify-content:center;">
                        <i class="fas fa-eye"></i> Ver Perfil
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top:2rem;">
            <a href="cuidadores.php" class="btn btn-blue">
                <i class="fas fa-users"></i> Ver Todos los Cuidadores
            </a>
        </div>

    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     SECCIÓN 5: PRODUCTOS DESTACADOS
     Muestra 4 productos marcados como destacados en la BD.
     ════════════════════════════════════════════════════════ -->
<section class="section section-light">
    <div class="container">

        <div class="section-header animate-on-scroll">
            <h2>Productos Destacados</h2>
            <p>Los productos favoritos de nuestros clientes para sus mascotas.</p>
        </div>

        <div class="grid-4">
            <?php foreach ($productos as $p): ?>
            <!-- data-category es usado por el JS de filtros -->
              <?php $portada = !empty($p['portada']) ? $p['portada'] : ('https://picsum.photos/seed/safepet-home-product-' . (int)$p['id'] . '/800/600'); ?>
              <div class="product-card product-card-clickable animate-on-scroll"
                  data-category="<?= htmlspecialchars($p['categoria']) ?>"
                  role="link"
                  tabindex="0"
                  onclick="window.location.href='producto_detalle.php?id=<?= (int)$p['id'] ?>'"
                  onkeypress="if(event.key==='Enter'){ window.location.href='producto_detalle.php?id=<?= (int)$p['id'] ?>'; }">

                <!-- Imagen / emoji del producto -->
                <div class="product-img">
                    <?php if (!empty($p['badge'])): ?>
                        <div class="product-badge"><?= htmlspecialchars($p['badge']) ?></div>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($portada) ?>"
                        alt="<?= htmlspecialchars($p['nombre']) ?>"
                        loading="lazy">
                </div>

                <div class="product-body">
                    <span class="product-category"><?= htmlspecialchars($p['categoria']) ?></span>
                    <h3><?= htmlspecialchars($p['nombre']) ?></h3>

                    <!-- Precio: si tiene precio original muestra tachado -->
                    <div class="product-price">
                        <?php if (!empty($p['precio_original'])): ?>
                            <span class="original">
                                $<?= number_format($p['precio_original'], 0, ',', '.') ?>
                            </span>
                        <?php endif; ?>
                        $<?= number_format($p['precio'], 0, ',', '.') ?>
                    </div>

                    <!-- Botones de acción -->
                    <div class="product-actions">
                        <!-- JS - Agrega al panel de carrito -->
                        <button class="btn-cart"
                            onclick="event.stopPropagation(); agregarAlCarrito(
                                '<?= $p['id'] ?>',
                                '<?= htmlspecialchars(addslashes($p['nombre'])) ?>',
                                <?= (int)$p['precio'] ?>,
                                '<?= htmlspecialchars(addslashes($portada)) ?>',
                                '<?= htmlspecialchars($p['categoria']) ?>'
                            )">
                            <i class="fas fa-shopping-cart"></i> Carrito
                        </button>

                        <!-- JS - Redirige directo a WhatsApp con el producto -->
                        <button class="btn-buy"
                            onclick="event.stopPropagation(); comprarAhora(
                                '<?= htmlspecialchars(addslashes($p['nombre'])) ?>',
                                <?= (int)$p['precio'] ?>,
                                '<?= htmlspecialchars(addslashes($portada)) ?>'
                            )">
                            <i class="fab fa-whatsapp"></i> Comprar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top:2rem;">
            <a href="productos.php" class="btn btn-blue">
                <i class="fas fa-store"></i> Ver Todos los Productos
            </a>
        </div>

    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     SECCIÓN 6: LLAMADA A LA ACCIÓN PARA CUIDADORES
     Invita a los profesionales a registrarse en la plataforma.
     ════════════════════════════════════════════════════════ -->
<section class="cta-section">
    <div class="container">
        <h2>¿Eres cuidador de mascotas?</h2>
        <p>Únete a nuestra comunidad, consigue clientes y gestiona tus solicitudes desde tu panel personal.</p>
        <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
            <a href="cuidadores/registro.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Regístrate Gratis
            </a>
            <a href="cuidadores/login.php" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
        </div>
    </div>
</section>


<!-- Incluimos el footer compartido -->
<?php include 'includes/footer.php'; ?>
