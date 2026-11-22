<?php
/**
 * ================================================================
 * ARCHIVO: cuidadores.php
 * PROPÓSITO: Página de listado de cuidadores disponibles.
 * Funcionalidades:
 *  - Búsqueda por nombre o ciudad
 *  - Filtrado por tipo de mascota y calificación
 *  - Cards con información del cuidador
 *  - Botón para contactar al cuidador vía WhatsApp
 * ================================================================
 */

$pageTitulo = "Cuidadores | SafePet";

require_once 'config/db.php';
include 'includes/header.php';

// ── Parámetros de búsqueda y filtros desde la URL (GET) ──────────
// Sanitizamos con htmlspecialchars para prevenir XSS
$busqueda   = isset($_GET['q'])          ? trim(htmlspecialchars($_GET['q']))          : '';
$ciudad     = isset($_GET['ciudad'])      ? trim(htmlspecialchars($_GET['ciudad']))      : '';
$tipo_masct = isset($_GET['tipo'])        ? trim(htmlspecialchars($_GET['tipo']))        : '';
$min_rating = isset($_GET['rating'])      ? (float)$_GET['rating']                     : 0;
$pagina     = isset($_GET['pagina'])      ? max(1, (int)$_GET['pagina'])               : 1;

$por_pagina = 9; // Cuántos cuidadores mostrar por página
$offset     = ($pagina - 1) * $por_pagina;

// ── Consulta de cuidadores con filtros dinámicos ─────────────────
// Usamos consultas preparadas para evitar SQL Injection
try {
    $pdo = getPDO();

    // Construir cláusula WHERE dinámica
    $condiciones = ["activo = 1"]; // Base: solo cuidadores activos
    $params      = [];

    if ($busqueda !== '') {
        $condiciones[] = "(nombre LIKE :busqueda OR descripcion LIKE :busqueda2)";
        $params[':busqueda']  = "%$busqueda%";
        $params[':busqueda2'] = "%$busqueda%";
    }

    if ($ciudad !== '') {
        $condiciones[] = "ciudad = :ciudad";
        $params[':ciudad'] = $ciudad;
    }

    if ($tipo_masct !== '') {
        // El campo especialidad puede contener varios tipos separados por coma
        $condiciones[] = "especialidad LIKE :tipo";
        $params[':tipo'] = "%$tipo_masct%";
    }

    if ($min_rating > 0) {
        $condiciones[] = "rating >= :rating";
        $params[':rating'] = $min_rating;
    }

    $whereSQL = "WHERE " . implode(" AND ", $condiciones);

    // Contar total para paginación
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM cuidadores $whereSQL");
    $stmtCount->execute($params);
    $totalCuidadores = (int) $stmtCount->fetchColumn();
    $totalPaginas    = (int) ceil($totalCuidadores / $por_pagina);

    // Obtener cuidadores de la página actual
    $sql = "SELECT id, nombre, especialidad, descripcion, rating, precio_hora, foto, ciudad, disponible
            FROM cuidadores
            $whereSQL
            ORDER BY disponible DESC, rating DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
    $stmt->execute();
    $cuidadores = $stmt->fetchAll();

    // Obtener ciudades únicas para el filtro
    $ciudades = $pdo->query("SELECT DISTINCT ciudad FROM cuidadores WHERE activo=1 ORDER BY ciudad")->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    $cuidadores = [];
    $totalCuidadores = count($cuidadores);
    $totalPaginas    = 1;
    $ciudades        = [];
}

// Función para generar estrellas según el rating
function generarEstrellas(float $rating): string {
    $llenas = floor($rating);
    $media  = ($rating - $llenas) >= 0.5 ? 1 : 0;
    $vacias = 5 - $llenas - $media;

    $html = '';
    for ($i = 0; $i < $llenas; $i++) $html .= '<i class="fas fa-star"></i>';
    if ($media)                         $html .= '<i class="fas fa-star-half-alt"></i>';
    for ($i = 0; $i < $vacias; $i++)   $html .= '<i class="far fa-star"></i>';
    return $html;
}
?>

<!-- Banner de la página -->
<section class="page-hero">
    <div class="container">
        <h1><i class="fas fa-users"></i> Cuidadores de Mascotas</h1>
        <p>Encuentra el cuidador ideal para tu compañero de 4 patas</p>
        <div class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span>›</span>
            <span>Cuidadores</span>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════
     SECCIÓN DE FILTROS Y BÚSQUEDA
     El formulario usa método GET para que los filtros queden
     en la URL y el usuario pueda compartir/guardar la búsqueda.
     ════════════════════════════════════════════════════════ -->
<section class="section section-gray" style="padding: 1.5rem 0;">
    <div class="container">
        <!-- Formulario GET - los filtros se reflejan en la URL -->
        <form method="GET" action="cuidadores.php" class="filtros-form">
            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap:1rem; align-items:end; flex-wrap:wrap;">

                <!-- Buscador por nombre -->
                <div class="form-group" style="margin:0;">
                    <label><i class="fas fa-search"></i> Buscar cuidador</label>
                    <input type="text" name="q" class="form-control"
                           placeholder="Nombre o descripción..."
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>

                <!-- Filtro por ciudad -->
                <div class="form-group" style="margin:0;">
                    <label><i class="fas fa-map-marker-alt"></i> Ciudad</label>
                    <select name="ciudad" class="form-control">
                        <option value="">Todas las ciudades</option>
                        <?php foreach ($ciudades as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $ciudad === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por tipo de mascota -->
                <div class="form-group" style="margin:0;">
                    <label><i class="fas fa-paw"></i> Tipo de mascota</label>
                    <select name="tipo" class="form-control">
                        <option value="">Todos</option>
                        <option value="Perros"   <?= $tipo_masct === 'Perros'   ? 'selected' : '' ?>>Perros</option>
                        <option value="Gatos"    <?= $tipo_masct === 'Gatos'    ? 'selected' : '' ?>>Gatos</option>
                        <option value="Exóticas" <?= $tipo_masct === 'Exóticas' ? 'selected' : '' ?>>Exóticas</option>
                    </select>
                </div>

                <!-- Filtro por calificación mínima -->
                <div class="form-group" style="margin:0;">
                    <label><i class="fas fa-star"></i> Calificación mín.</label>
                    <select name="rating" class="form-control">
                        <option value="0">Cualquiera</option>
                        <option value="4"   <?= $min_rating >= 4   ? 'selected' : '' ?>>4+ ⭐</option>
                        <option value="4.5" <?= $min_rating >= 4.5 ? 'selected' : '' ?>>4.5+ ⭐</option>
                    </select>
                </div>

                <!-- Botón de búsqueda -->
                <div>
                    <button type="submit" class="btn btn-blue" style="width:100%;">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>

            </div>
        </form>
    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     LISTADO DE CUIDADORES
     ════════════════════════════════════════════════════════ -->
<section class="section section-light">
    <div class="container">

        <!-- Resultados encontrados -->
        <p style="color:var(--color-gray); margin-bottom:1.5rem; font-size:0.9rem;">
            <i class="fas fa-users"></i>
            <?= $totalCuidadores ?> cuidador<?= $totalCuidadores !== 1 ? 'es' : '' ?> encontrado<?= $totalCuidadores !== 1 ? 's' : '' ?>
            <?php if ($busqueda): ?> para "<strong><?= htmlspecialchars($busqueda) ?></strong>"<?php endif; ?>
        </p>

        <?php if (empty($cuidadores)): ?>
        <!-- Estado vacío si no hay resultados -->
        <div style="text-align:center; padding:4rem; color:var(--color-gray);">
            <i class="fas fa-search" style="font-size:3rem; margin-bottom:1rem; display:block; color:#e0e0e0;"></i>
            <p>No se encontraron cuidadores con los filtros seleccionados.</p>
            <a href="cuidadores.php" class="btn btn-blue" style="margin-top:1rem;">Ver todos los cuidadores</a>
        </div>

        <?php else: ?>

        <div class="grid-3">
            <?php foreach ($cuidadores as $c): ?>
            <div class="cuidador-card animate-on-scroll">

                <!-- Foto de perfil -->
                <div class="cuidador-card-img">
                    <?php if (!empty($c['foto'])): ?>
                        <img src="<?= htmlspecialchars($c['foto']) ?>"
                             alt="Foto de <?= htmlspecialchars($c['nombre']) ?>"
                             style="width:100%; height:100%; object-fit:contain; background:var(--color-white);">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size:5rem;color:var(--color-primary);"></i>
                    <?php endif; ?>
                </div>

                <div class="cuidador-card-body">
                    <h3><?= htmlspecialchars($c['nombre']) ?></h3>

                    <!-- Estrellas de calificación generadas dinámicamente -->
                    <div class="cuidador-rating">
                        <?= generarEstrellas((float)$c['rating']) ?>
                        <span style="margin-left:4px;"><?= number_format($c['rating'], 1) ?></span>
                    </div>

                    <!-- Badge de disponibilidad -->
                    <?php if ($c['disponible']): ?>
                        <span class="badge-disponible"><i class="fas fa-check-circle"></i> Disponible ahora</span>
                    <?php else: ?>
                        <span class="badge-ocupado"><i class="fas fa-clock"></i> Ocupado</span>
                    <?php endif; ?>

                    <!-- Tags de info del cuidador -->
                    <div class="cuidador-tags">
                        <span class="tag"><i class="fas fa-paw"></i> <?= htmlspecialchars($c['especialidad']) ?></span>
                        <span class="tag"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['ciudad']) ?></span>
                    </div>

                    <!-- Descripción corta (máx 80 caracteres) -->
                    <?php if (!empty($c['descripcion'])): ?>
                    <p style="font-size:0.85rem; color:var(--color-gray); margin-bottom:0.75rem;">
                        <?= htmlspecialchars(substr($c['descripcion'], 0, 80)) ?>...
                    </p>
                    <?php endif; ?>

                    <!-- Precio por hora -->
                    <div class="cuidador-precio">
                        <i class="fas fa-tag"></i>
                        Desde $<?= number_format($c['precio_hora'], 0, ',', '.') ?> COP/hora
                    </div>

                    <!-- Botones de acción -->
                    <div style="display:flex; gap:8px;">
                        <!-- Ver perfil completo -->
                        <a href="cuidador_perfil.php?id=<?= $c['id'] ?>" class="btn btn-blue" style="flex:1; justify-content:center; padding:10px;">
                            <i class="fas fa-eye"></i> Perfil
                        </a>

                        <!-- Contactar por WhatsApp -->
                        <!-- El número lo provee el cuidador en su perfil (aquí usamos el del negocio) -->
                        <a href="https://wa.me/573242956121?text=<?= urlencode("Hola, me interesa el servicio de " . $c['nombre'] . " para cuidar mi mascota. ¿Está disponible?") ?>"
                           class="btn-buy"
                           target="_blank"
                           rel="noopener noreferrer"
                           style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; border-radius:6px; text-decoration:none;">
                            <i class="fab fa-whatsapp"></i> Contactar
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>


        <!-- ══ PAGINACIÓN ══ -->
        <?php if ($totalPaginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>"
               class="page-btn" title="Página anterior">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"
               class="page-btn <?= $i === $pagina ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($pagina < $totalPaginas): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>"
               class="page-btn" title="Página siguiente">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>


<!-- CTA inferior para cuidadores -->
<section class="cta-section">
    <div class="container">
        <h2>¿Quieres ser cuidador en SafePet?</h2>
        <p>Regístrate gratis y empieza a recibir solicitudes de dueños de mascotas.</p>
        <a href="cuidadores/registro.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Registrarme como Cuidador
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
