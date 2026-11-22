<?php
/**
 * ================================================================
 * ARCHIVO: producto_detalle.php
 * PROPÓSITO: Mostrar detalle completo de un producto de la tienda.
 * ================================================================
 */

$pageTitulo = "Detalle de Producto | SafePet";

require_once 'config/db.php';
include 'includes/header.php';

function parseGaleriaProducto(?string $galeriaRaw): array
{
    if (!$galeriaRaw) {
        return [];
    }

    $galeriaRaw = trim($galeriaRaw);
    if ($galeriaRaw === '') {
        return [];
    }

    $json = json_decode($galeriaRaw, true);
    if (is_array($json)) {
        return array_values(array_filter(array_map('trim', $json), static fn($u) => $u !== ''));
    }

    $partes = preg_split('/[\r\n,]+/', $galeriaRaw);
    if (!is_array($partes)) {
        return [];
    }

    return array_values(array_filter(array_map('trim', $partes), static fn($u) => $u !== ''));
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$producto = null;

if ($id > 0) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id, nombre, descripcion, precio, precio_original, categoria, badge, stock, portada, galeria FROM productos WHERE id = :id AND activo = 1 LIMIT 1");
        $stmt->execute([':id' => $id]);
        $producto = $stmt->fetch();
    } catch (Exception $e) {
        $producto = null;
    }
}

if ($producto) {
    $portada = !empty($producto['portada'])
        ? $producto['portada']
        : 'https://picsum.photos/seed/safepet-product-' . (int) $producto['id'] . '/1200/900';

    $galeria = parseGaleriaProducto($producto['galeria'] ?? null);
    if (empty($galeria)) {
        $galeria = [
            $portada,
            'https://picsum.photos/seed/safepet-gallery-' . (int) $producto['id'] . '-1/1200/900',
            'https://picsum.photos/seed/safepet-gallery-' . (int) $producto['id'] . '-2/1200/900',
        ];
    }
}
?>

<section class="page-hero">
    <div class="container">
        <h1><i class="fas fa-box-open"></i> Detalle del producto</h1>
        <div class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span>›</span>
            <a href="productos.php">Productos</a>
            <span>›</span>
            <span>Detalle</span>
        </div>
    </div>
</section>

<section class="section section-light">
    <div class="container">
        <?php if (!$producto): ?>
            <div style="max-width:700px; margin:0 auto; text-align:center; padding:3rem; background:#fff; border:1px solid #e6ebf3; border-radius:16px; box-shadow:var(--shadow-card);">
                <i class="fas fa-search" style="font-size:2.2rem; color:#c2ccda; margin-bottom:0.8rem;"></i>
                <h2 style="margin-bottom:0.5rem;">Producto no encontrado</h2>
                <p style="color:var(--color-gray); margin-bottom:1rem;">Este producto no existe o no está disponible por ahora.</p>
                <a href="productos.php" class="btn btn-blue"><i class="fas fa-arrow-left"></i> Volver a productos</a>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: 1.1fr 1fr; gap:1.5rem; align-items:start;">
                <div>
                    <div style="position:relative; border-radius:16px; overflow:hidden; background:#f5f8fd; border:1px solid #e6ebf3;">
                        <?php if (!empty($producto['badge'])): ?>
                            <div class="product-badge" style="z-index:2;"><?= htmlspecialchars($producto['badge']) ?></div>
                        <?php endif; ?>
                        <img id="mainProductImage"
                             src="<?= htmlspecialchars($portada) ?>"
                             alt="<?= htmlspecialchars($producto['nombre']) ?>"
                             style="display:block; width:100%; height:430px; object-fit:cover;">
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:0.6rem; margin-top:0.8rem;">
                        <?php foreach ($galeria as $img): ?>
                            <button type="button"
                                    onclick="document.getElementById('mainProductImage').src='<?= htmlspecialchars($img, ENT_QUOTES) ?>'"
                                    style="border:1px solid #d9e2f0; border-radius:10px; background:#fff; overflow:hidden; padding:0; cursor:pointer; height:90px;">
                                <img src="<?= htmlspecialchars($img) ?>"
                                     alt="Miniatura de <?= htmlspecialchars($producto['nombre']) ?>"
                                     style="width:100%; height:100%; object-fit:cover; display:block;">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="background:#fff; border:1px solid #e6ebf3; border-radius:16px; box-shadow:var(--shadow-card); padding:1.2rem;">
                    <span class="product-category"><?= htmlspecialchars($producto['categoria']) ?></span>
                    <h2 style="margin:0.2rem 0 0.8rem; font-size:1.8rem; color:var(--color-dark);"><?= htmlspecialchars($producto['nombre']) ?></h2>

                    <div class="product-price" style="margin-bottom:0.9rem;">
                        <?php if (!empty($producto['precio_original'])): ?>
                            <span class="original">$<?= number_format($producto['precio_original'], 0, ',', '.') ?></span>
                        <?php endif; ?>
                        $<?= number_format($producto['precio'], 0, ',', '.') ?>
                    </div>

                    <p style="color:var(--color-gray); line-height:1.6; margin-bottom:1rem;">
                        <?= nl2br(htmlspecialchars($producto['descripcion'] ?? 'Producto sin descripción.')) ?>
                    </p>

                    <?php if ((int)($producto['stock'] ?? 0) > 0): ?>
                        <p style="font-size:0.9rem; color:#0b7a39; margin-bottom:0.9rem;"><i class="fas fa-check-circle"></i> Stock disponible: <?= (int)$producto['stock'] ?></p>
                    <?php else: ?>
                        <p style="font-size:0.9rem; color:#b3261e; margin-bottom:0.9rem;"><i class="fas fa-times-circle"></i> Sin stock por ahora</p>
                    <?php endif; ?>

                    <div class="product-actions">
                        <button class="btn-cart"
                            onclick="agregarAlCarrito(
                                'p<?= (int)$producto['id'] ?>',
                                '<?= htmlspecialchars(addslashes($producto['nombre'])) ?>',
                                <?= (int)$producto['precio'] ?>,
                                '<?= htmlspecialchars(addslashes($portada)) ?>',
                                '<?= htmlspecialchars($producto['categoria']) ?>'
                            )"
                            title="Agregar al carrito">
                            <i class="fas fa-shopping-cart"></i> Agregar al carrito
                        </button>

                        <button class="btn-buy"
                            onclick="comprarAhora(
                                '<?= htmlspecialchars(addslashes($producto['nombre'])) ?>',
                                <?= (int)$producto['precio'] ?>,
                                '<?= htmlspecialchars(addslashes($portada)) ?>'
                            )"
                            title="Comprar por WhatsApp">
                            <i class="fab fa-whatsapp"></i> Comprar ahora
                        </button>
                    </div>

                    <a href="productos.php" class="btn btn-blue" style="margin-top:0.85rem; width:100%; justify-content:center;">
                        <i class="fas fa-arrow-left"></i> Seguir viendo productos
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
