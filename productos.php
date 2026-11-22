<?php
/**
 * ================================================================
 * ARCHIVO: productos.php
 * PROPÓSITO: Tienda de productos para mascotas.
 * Funcionalidades:
 *  - Filtro por categoría (todos, alimentos, accesorios, salud, higiene)
 *  - Búsqueda por nombre de producto
 *  - Botón "Agregar al carrito" → panel lateral JS
 *  - Botón "Comprar ahora"      → redirige directo a WhatsApp
 * ================================================================
 */

$pageTitulo = "Productos | SafePet";

require_once 'config/db.php';
include 'includes/header.php';

// ── Parámetros de búsqueda ───────────────────────────────────────
$busqueda  = isset($_GET['q'])         ? trim(htmlspecialchars($_GET['q']))   : '';
$categoria = isset($_GET['cat']) ? trim(htmlspecialchars($_GET['cat'])) : 'todos';
$pagina    = isset($_GET['pagina'])    ? max(1, (int)$_GET['pagina'])         : 1;
$por_pagina = 12;
$offset     = ($pagina - 1) * $por_pagina;

// ── Obtener productos de la BD ───────────────────────────────────
try {
    $pdo = getPDO();

    // Condiciones dinámicas según filtros
    $condiciones = ["activo = 1"];
    $params      = [];

    if ($busqueda !== '') {
        $condiciones[] = "(nombre LIKE :busqueda OR descripcion LIKE :busqueda2)";
        $params[':busqueda']  = "%$busqueda%";
        $params[':busqueda2'] = "%$busqueda%";
    }

    // Filtro de categoría (viene del botón de filtro en la URL como ?cat=xxx)
    $catFiltro = isset($_GET['cat']) ? trim(htmlspecialchars($_GET['cat'])) : 'todos';
    if ($catFiltro !== 'todos' && $catFiltro !== '') {
        $condiciones[] = "categoria = :categoria";
        $params[':categoria'] = $catFiltro;
    }

    $whereSQL = "WHERE " . implode(" AND ", $condiciones);

    // Total de productos para paginación
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM productos $whereSQL");
    $stmtTotal->execute($params);
    $totalProductos = (int) $stmtTotal->fetchColumn();
    $totalPaginas   = (int) ceil($totalProductos / $por_pagina);

    // Obtener productos
    $sql = "SELECT id, nombre, descripcion, precio, precio_original, categoria, portada, galeria, badge, stock
            FROM productos
            $whereSQL
            ORDER BY destacado DESC, id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
    $stmt->execute();
    $productos = $stmt->fetchAll();

} catch (Exception $e) {
    // Catálogo de demostración completo si la BD no está disponible
    $todosProductos = [
        // ── ALIMENTOS ───────────────────────────────────────────
        ['id'=>'1', 'nombre'=>'Croquetas Premium para Perro Adulto',   'descripcion'=>'Alta proteína, libre de granos. Ideal para razas medianas.',         'precio'=>45000, 'precio_original'=>55000, 'categoria'=>'Alimentos',    'portada'=>'https://picsum.photos/seed/safepet-prod-1/800/600',  'galeria'=>null, 'badge'=>'18% OFF', 'stock'=>20],
        ['id'=>'2', 'nombre'=>'Alimento Húmedo para Gato Sabor Atún',  'descripcion'=>'Pack de 12 latas de alimento húmedo premium para gatos adultos.',    'precio'=>38000, 'precio_original'=>null,   'categoria'=>'Alimentos',    'portada'=>'https://picsum.photos/seed/safepet-prod-2/800/600',  'galeria'=>null, 'badge'=>null,      'stock'=>15],
        ['id'=>'3', 'nombre'=>'Snacks Dentales para Perro',             'descripcion'=>'Premios que cuidan los dientes y el aliento de tu perro.',           'precio'=>12000, 'precio_original'=>null,   'categoria'=>'Alimentos',    'portada'=>'https://picsum.photos/seed/safepet-prod-3/800/600',  'galeria'=>null, 'badge'=>'Nuevo',   'stock'=>50],
        ['id'=>'4', 'nombre'=>'Mezcla de Semillas para Aves',           'descripcion'=>'Mezcla nutritiva de semillas para pájaros y loros.',                 'precio'=>15000, 'precio_original'=>null,   'categoria'=>'Alimentos',    'portada'=>'https://picsum.photos/seed/safepet-prod-4/800/600',  'galeria'=>null, 'badge'=>null,      'stock'=>30],

        // ── ACCESORIOS ──────────────────────────────────────────
        ['id'=>'5', 'nombre'=>'Cama Ortopédica para Mascotas',          'descripcion'=>'Espuma de memoria para el descanso perfecto. Lavable a máquina.',    'precio'=>89000, 'precio_original'=>null,   'categoria'=>'Accesorios',   'portada'=>'https://picsum.photos/seed/safepet-prod-5/800/600',  'galeria'=>null, 'badge'=>'Nuevo',   'stock'=>10],
        ['id'=>'6', 'nombre'=>'Correa Retráctil 5m',                    'descripcion'=>'Correa retráctil con freno automático. Para perros hasta 30kg.',     'precio'=>35000, 'precio_original'=>45000,  'categoria'=>'Accesorios',   'portada'=>'https://picsum.photos/seed/safepet-prod-6/800/600',  'galeria'=>null, 'badge'=>'22% OFF', 'stock'=>25],
        ['id'=>'7', 'nombre'=>'Transportadora Portátil',                'descripcion'=>'Transportadora ligera y ventilada para viajes con tu mascota.',      'precio'=>75000, 'precio_original'=>null,   'categoria'=>'Accesorios',   'portada'=>'https://picsum.photos/seed/safepet-prod-7/800/600',  'galeria'=>null, 'badge'=>null,      'stock'=>8],
        ['id'=>'8', 'nombre'=>'Juguete Dispensador de Premios',         'descripcion'=>'Estimulación mental para perros. Dispensa premios al rodar.',        'precio'=>28000, 'precio_original'=>null,   'categoria'=>'Accesorios',   'portada'=>'https://picsum.photos/seed/safepet-prod-8/800/600',  'galeria'=>null, 'badge'=>null,      'stock'=>35],

        // ── SALUD ────────────────────────────────────────────────
        ['id'=>'9', 'nombre'=>'Collar Anti-Pulgas y Garrapatas',        'descripcion'=>'Protección durante 8 meses contra pulgas, garrapatas y mosquitos.',  'precio'=>18000, 'precio_original'=>25000,  'categoria'=>'Salud',        'portada'=>'https://picsum.photos/seed/safepet-prod-9/800/600',  'galeria'=>null, 'badge'=>'28% OFF', 'stock'=>40],
        ['id'=>'10','nombre'=>'Suplemento Vitamínico Canino',           'descripcion'=>'Vitaminas A, B, C, D y omega-3 para perros en etapa adulta.',        'precio'=>32000, 'precio_original'=>null,   'categoria'=>'Salud',        'portada'=>'https://picsum.photos/seed/safepet-prod-10/800/600', 'galeria'=>null, 'badge'=>null,      'stock'=>20],
        ['id'=>'11','nombre'=>'Pasta Dental para Mascotas',             'descripcion'=>'Higiene dental sabor pollo. Apta para perros y gatos.',              'precio'=>22000, 'precio_original'=>null,   'categoria'=>'Salud',        'portada'=>'https://picsum.photos/seed/safepet-prod-11/800/600', 'galeria'=>null, 'badge'=>null,      'stock'=>30],

        // ── HIGIENE ──────────────────────────────────────────────
        ['id'=>'12','nombre'=>'Arena para Gato Natural Biodegradable',  'descripcion'=>'Arena de sílice cristalizada, controla olores 30 días.',             'precio'=>22000, 'precio_original'=>null,   'categoria'=>'Higiene',      'portada'=>'https://picsum.photos/seed/safepet-prod-12/800/600', 'galeria'=>null, 'badge'=>null,      'stock'=>60],
        ['id'=>'13','nombre'=>'Shampoo para Mascotas Avena y Miel',     'descripcion'=>'Fórmula suave para piel sensible. pH balanceado para mascotas.',     'precio'=>25000, 'precio_original'=>30000,  'categoria'=>'Higiene',      'portada'=>'https://picsum.photos/seed/safepet-prod-13/800/600', 'galeria'=>null, 'badge'=>'17% OFF', 'stock'=>45],
        ['id'=>'14','nombre'=>'Toallitas Húmedas para Mascotas x50',    'descripcion'=>'Para limpiar patas, oídos o cuerpo entre baños.',                   'precio'=>14000, 'precio_original'=>null,   'categoria'=>'Higiene',      'portada'=>'https://picsum.photos/seed/safepet-prod-14/800/600', 'galeria'=>null, 'badge'=>null,      'stock'=>70],
    ];

    // Filtrar en PHP si la BD no está disponible
    $catFiltro = isset($_GET['cat']) ? trim(htmlspecialchars($_GET['cat'])) : 'todos';
    if ($catFiltro !== 'todos' && $catFiltro !== '') {
        $productos = array_filter($todosProductos, fn($p) => $p['categoria'] === $catFiltro);
        $productos = array_values($productos);
    } else {
        $productos = $todosProductos;
    }

    if ($busqueda) {
        $productos = array_filter($productos, fn($p) => stripos($p['nombre'], $busqueda) !== false);
        $productos = array_values($productos);
    }

    $totalProductos = count($productos);
    $totalPaginas   = 1;
}
?>

<!-- Banner superior de la página -->
<section class="page-hero">
    <div class="container">
        <h1><i class="fas fa-store"></i> Tienda SafePet</h1>
        <p>Todo lo que tu mascota necesita, con entrega a domicilio</p>
        <div class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span>›</span>
            <span>Productos</span>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     BARRA DE BÚSQUEDA
     ════════════════════════════════════════════════════════ -->
<section class="section section-gray" style="padding:1.5rem 0;">
    <div class="container">
        <form method="GET" action="productos.php" style="display:flex; gap:1rem; max-width:600px; margin:0 auto;">
            <div style="flex:1; position:relative;">
                <input type="text" name="q" class="form-control"
                       placeholder="🔍 Buscar productos para mascotas..."
                       value="<?= htmlspecialchars($busqueda) ?>"
                       style="padding-left:1rem;">
                <!-- Mantiene el filtro de categoría al buscar -->
                <?php if (!empty($catFiltro) && $catFiltro !== 'todos'): ?>
                <input type="hidden" name="cat" value="<?= htmlspecialchars($catFiltro) ?>">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-blue">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>
    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     FILTROS POR CATEGORÍA
     Los botones actualizan la URL con el parámetro ?cat=xxx
     El JS también puede filtrar en el frontend para mayor velocidad.
     ════════════════════════════════════════════════════════ -->
<section class="section section-light">
    <div class="container">

        <div class="products-filters">
            <!-- data-category es usado por el filtro JS del frontend -->
            <button class="filter-btn active" data-category="todos"
                    onclick="filtrarCategoria(this, 'todos')">
                <i class="fas fa-th"></i> Todos
            </button>
            <button class="filter-btn" data-category="Alimentos"
                    onclick="filtrarCategoria(this, 'Alimentos')">
                <i class="fas fa-bone"></i> Alimentos
            </button>
            <button class="filter-btn" data-category="Accesorios"
                    onclick="filtrarCategoria(this, 'Accesorios')">
                <i class="fas fa-tag"></i> Accesorios
            </button>
            <button class="filter-btn" data-category="Salud"
                    onclick="filtrarCategoria(this, 'Salud')">
                <i class="fas fa-heartbeat"></i> Salud
            </button>
            <button class="filter-btn" data-category="Higiene"
                    onclick="filtrarCategoria(this, 'Higiene')">
                <i class="fas fa-bath"></i> Higiene
            </button>
        </div>

        <!-- Contador de resultados -->
        <p style="color:var(--color-gray); text-align:center; margin-bottom:2rem; font-size:0.9rem;">
            Mostrando <strong id="productCount"><?= count($productos) ?></strong> de <?= $totalProductos ?> productos
        </p>

        <!-- ── GRID DE PRODUCTOS ─────────────────────────────── -->
        <!-- Nota: usamos id="productGrid" para que el JS pueda filtrar en tiempo real -->
        <div class="grid-4" id="productGrid">
            <?php foreach ($productos as $p): ?>
            <!-- data-category permite al JS de frontend filtrar sin recargar la página -->
              <?php $portada = !empty($p['portada']) ? $p['portada'] : ('https://picsum.photos/seed/safepet-product-' . (int)$p['id'] . '/800/600'); ?>
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
                    <!-- Categoría del producto -->
                    <span class="product-category"><?= htmlspecialchars($p['categoria']) ?></span>

                    <!-- Nombre del producto -->
                    <h3><?= htmlspecialchars($p['nombre']) ?></h3>

                    <!-- Descripción corta -->
                    <p><?= htmlspecialchars(substr($p['descripcion'] ?? '', 0, 70)) ?>...</p>

                    <!-- Precio (con precio original tachado si tiene descuento) -->
                    <div class="product-price">
                        <?php if (!empty($p['precio_original'])): ?>
                        <span class="original">$<?= number_format($p['precio_original'], 0, ',', '.') ?></span>
                        <?php endif; ?>
                        $<?= number_format($p['precio'], 0, ',', '.') ?>
                    </div>

                    <!-- Indicador de stock -->
                    <?php if (isset($p['stock']) && $p['stock'] <= 5 && $p['stock'] > 0): ?>
                    <p style="color:#E65100; font-size:0.8rem; margin-bottom:0.5rem;">
                        <i class="fas fa-exclamation-triangle"></i> ¡Solo <?= $p['stock'] ?> disponibles!
                    </p>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <div class="product-actions">
                        <!--
                            BOTÓN "AGREGAR AL CARRITO":
                            Llama a la función JS agregarAlCarrito() con los datos del producto.
                            Los datos vienen del PHP renderizado. Usamos addslashes() para que
                            los nombres con comillas no rompan el JavaScript.
                        -->
                        <button class="btn-cart"
                            onclick="event.stopPropagation(); agregarAlCarrito(
                                'p<?= $p['id'] ?>',
                                '<?= htmlspecialchars(addslashes($p['nombre'])) ?>',
                                <?= (int)$p['precio'] ?>,
                                '<?= htmlspecialchars(addslashes($portada)) ?>',
                                '<?= htmlspecialchars($p['categoria']) ?>'
                            )"
                            title="Agregar al carrito">
                            <i class="fas fa-shopping-cart"></i> Carrito
                        </button>

                        <!--
                            BOTÓN "COMPRAR AHORA":
                            Llama a comprarAhora() que redirige directamente a WhatsApp
                            con el número +57 3242956121 y un mensaje pre-escrito con el producto.
                        -->
                        <button class="btn-buy"
                            onclick="event.stopPropagation(); comprarAhora(
                                '<?= htmlspecialchars(addslashes($p['nombre'])) ?>',
                                <?= (int)$p['precio'] ?>,
                                '<?= htmlspecialchars(addslashes($portada)) ?>'
                            )"
                            title="Comprar por WhatsApp">
                            <i class="fab fa-whatsapp"></i> Comprar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación si hay más de una página -->
        <?php if ($totalPaginas > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"
               class="page-btn <?= $i === $pagina ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     SECCIÓN DE GARANTÍAS / BENEFICIOS
     Información de confianza que ayuda a convertir.
     ════════════════════════════════════════════════════════ -->
<section class="section section-blue">
    <div class="container">
        <div class="grid-4" style="text-align:center;">
            <div class="animate-on-scroll">
                <i class="fas fa-truck" style="font-size:2.5rem; color:var(--color-primary); margin-bottom:0.5rem;"></i>
                <h4 style="color:var(--color-primary);">Envío a domicilio</h4>
                <p style="font-size:0.85rem; color:var(--color-gray);">Entregamos en toda Colombia</p>
            </div>
            <div class="animate-on-scroll">
                <i class="fas fa-shield-alt" style="font-size:2.5rem; color:var(--color-primary); margin-bottom:0.5rem;"></i>
                <h4 style="color:var(--color-primary);">Productos seguros</h4>
                <p style="font-size:0.85rem; color:var(--color-gray);">Solo productos certificados para mascotas</p>
            </div>
            <div class="animate-on-scroll">
                <i class="fab fa-whatsapp" style="font-size:2.5rem; color:var(--color-primary); margin-bottom:0.5rem;"></i>
                <h4 style="color:var(--color-primary);">Compra por WhatsApp</h4>
                <p style="font-size:0.85rem; color:var(--color-gray);">Proceso fácil y rápido vía WhatsApp</p>
            </div>
            <div class="animate-on-scroll">
                <i class="fas fa-undo" style="font-size:2.5rem; color:var(--color-primary); margin-bottom:0.5rem;"></i>
                <h4 style="color:var(--color-primary);">Devoluciones</h4>
                <p style="font-size:0.85rem; color:var(--color-gray);">Garantía de satisfacción o devolución</p>
            </div>
        </div>
    </div>
</section>


<?php include 'includes/footer.php'; ?>

<!-- Script específico para el filtro de categorías en el frontend -->
<script>
/**
 * filtrarCategoria()
 * Filtra las tarjetas de producto localmente en el navegador
 * sin necesidad de recargar la página.
 * También actualiza el contador de productos visibles.
 * @param {HTMLElement} boton     - El botón de filtro clickeado
 * @param {string}      categoria - La categoría a mostrar ('todos' o nombre)
 */
function filtrarCategoria(boton, categoria) {
    // Refrescar estado activo de botones
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    boton.classList.add('active');

    // Aplicar visibilidad a las tarjetas
    const tarjetas = document.querySelectorAll('#productGrid .product-card');
    let visibles = 0;

    tarjetas.forEach(card => {
        if (categoria === 'todos' || card.dataset.category === categoria) {
            card.style.display = 'flex';
            card.style.animation = 'fadeIn 0.3s ease';
            visibles++;
        } else {
            card.style.display = 'none';
        }
    });

    // Actualizar el contador de resultados visibles
    const contador = document.getElementById('productCount');
    if (contador) contador.textContent = visibles;
}
</script>
