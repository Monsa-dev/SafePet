<?php
/**
 * ============================================================
 * ARCHIVO: admin/index.php
 * PROPÓSITO: Panel de administración para gestión de productos.
 * Funcionalidades:
 *  - Listar todos los productos con filtros de búsqueda
 *  - Agregar producto (modal)
 *  - Editar producto (modal pre-rellenado)
 *  - Eliminar producto (confirmación)
 *  - Activar / desactivar producto
 *  - Marcar / desmarcar como destacado
 * ============================================================
 */
require_once 'auth.php';
require_once '../config/db.php';

$adminNombre = htmlspecialchars($_SESSION['admin_nombre']);
$mensaje     = '';
$tipoMensaje = '';

// ── Token de seguridad del formulario ───────────────────────────
// Se usa para asegurarnos de que las acciones vienen desde este panel.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ── Categorías válidas ───────────────────────────────────────────
$categoriasValidas = ['Alimentos', 'Accesorios', 'Salud', 'Higiene'];

// ── Función: leer y ordenar datos del formulario de producto ────
// La idea es no repetir el mismo bloque para agregar y editar.
function leerProductoFormulario(array $post): array
{
    return [
        'id'             => (int) ($post['id'] ?? 0),
        'nombre'         => trim($post['nombre'] ?? ''),
        'descripcion'    => trim($post['descripcion'] ?? ''),
        'precio'         => (int) ($post['precio'] ?? 0),
        'precio_original'=> trim($post['precio_original'] ?? '') !== '' ? (int) $post['precio_original'] : null,
        'categoria'      => trim($post['categoria'] ?? ''),
        'portada'        => trim($post['portada'] ?? ''),
        'galeria'        => trim($post['galeria'] ?? '') ?: null,
        'badge'          => trim($post['badge'] ?? '') ?: null,
        'stock'          => max(0, (int) ($post['stock'] ?? 0)),
        'destacado'      => isset($post['destacado']) ? 1 : 0,
        'activo'         => isset($post['activo']) ? 1 : 0,
    ];
}

// ── Función: validar datos mínimos del producto ─────────────────
function productoValido(array $producto, array $categoriasValidas, bool $requiereId = false): bool
{
    if ($requiereId && $producto['id'] <= 0) {
        return false;
    }

    return $producto['nombre'] !== ''
        && $producto['precio'] > 0
        && in_array($producto['categoria'], $categoriasValidas, true);
}

// ── Función: ejecutar actualización simple por ID ───────────────
// Nos ayuda para acciones tipo activar/desactivar o destacar.
function ejecutarAccionSimple(PDO $pdo, string $sql, int $id): bool
{
    if ($id <= 0) {
        return false;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return true;
}

// ── Procesar acciones POST ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validar CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $mensaje     = 'Token de seguridad inválido. Recarga la página.';
        $tipoMensaje = 'error';
    } else {
        $accion = $_POST['accion'] ?? '';
        $pdo    = getPDO();

        // ── AGREGAR PRODUCTO ─────────────────────────────────────
        if ($accion === 'agregar') {
            $producto = leerProductoFormulario($_POST);

            if (!productoValido($producto, $categoriasValidas)) {
                $mensaje     = 'Nombre, precio y categoría son obligatorios.';
                $tipoMensaje = 'error';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO productos (nombre, descripcion, precio, precio_original, categoria, portada, galeria, badge, stock, destacado, activo)
                     VALUES (:nombre, :descripcion, :precio, :precio_original, :categoria, :portada, :galeria, :badge, :stock, :destacado, :activo)"
                );
                $stmt->execute([
                    ':nombre'          => $producto['nombre'],
                    ':descripcion'     => $producto['descripcion'],
                    ':precio'          => $producto['precio'],
                    ':precio_original' => $producto['precio_original'],
                    ':categoria'       => $producto['categoria'],
                    ':portada'         => $producto['portada'] !== '' ? $producto['portada'] : null,
                    ':galeria'         => $producto['galeria'],
                    ':badge'           => $producto['badge'],
                    ':stock'           => $producto['stock'],
                    ':destacado'       => $producto['destacado'],
                    ':activo'          => $producto['activo'],
                ]);
                $mensaje     = "Producto \"{$producto['nombre']}\" agregado correctamente.";
                $tipoMensaje = 'exito';
            }
        }

        // ── EDITAR PRODUCTO ──────────────────────────────────────
        elseif ($accion === 'editar') {
            $producto = leerProductoFormulario($_POST);

            if (!productoValido($producto, $categoriasValidas, true)) {
                $mensaje     = 'Datos inválidos para editar el producto.';
                $tipoMensaje = 'error';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE productos
                     SET nombre = :nombre, descripcion = :descripcion, precio = :precio,
                         precio_original = :precio_original, categoria = :categoria, portada = :portada, galeria = :galeria,
                         badge = :badge, stock = :stock, destacado = :destacado, activo = :activo
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':nombre'          => $producto['nombre'],
                    ':descripcion'     => $producto['descripcion'],
                    ':precio'          => $producto['precio'],
                    ':precio_original' => $producto['precio_original'],
                    ':categoria'       => $producto['categoria'],
                    ':portada'         => $producto['portada'] !== '' ? $producto['portada'] : null,
                    ':galeria'         => $producto['galeria'],
                    ':badge'           => $producto['badge'],
                    ':stock'           => $producto['stock'],
                    ':destacado'       => $producto['destacado'],
                    ':activo'          => $producto['activo'],
                    ':id'              => $producto['id'],
                ]);
                $mensaje     = "Producto actualizado correctamente.";
                $tipoMensaje = 'exito';
            }
        }

        // ── ELIMINAR PRODUCTO ────────────────────────────────────
        elseif ($accion === 'eliminar') {
            if (ejecutarAccionSimple($pdo, "DELETE FROM productos WHERE id = :id", (int) ($_POST['id'] ?? 0))) {
                $mensaje     = 'Producto eliminado permanentemente.';
                $tipoMensaje = 'exito';
            }
        }

        // ── TOGGLE ACTIVO ────────────────────────────────────────
        elseif ($accion === 'toggle_activo') {
            if (ejecutarAccionSimple($pdo, "UPDATE productos SET activo = NOT activo WHERE id = :id", (int) ($_POST['id'] ?? 0))) {
                $mensaje     = 'Estado del producto actualizado.';
                $tipoMensaje = 'exito';
            }
        }

        // ── TOGGLE DESTACADO ─────────────────────────────────────
        elseif ($accion === 'toggle_destacado') {
            if (ejecutarAccionSimple($pdo, "UPDATE productos SET destacado = NOT destacado WHERE id = :id", (int) ($_POST['id'] ?? 0))) {
                $mensaje     = 'Producto destacado actualizado.';
                $tipoMensaje = 'exito';
            }
        }
    }

    // Redirigir con PRG para evitar re-envío del formulario
    if ($tipoMensaje === 'exito') {
        header("Location: index.php?msg=" . urlencode($mensaje));
        exit;
    }
}

// Capturar mensaje de redirección GET
if (isset($_GET['msg']) && $mensaje === '') {
    $mensaje     = urldecode($_GET['msg']);
    $tipoMensaje = 'exito';
}

// ── Obtener productos ────────────────────────────────────────────
$pdo = getPDO();

$busqueda  = trim($_GET['q']   ?? '');
$catFiltro = trim($_GET['cat'] ?? 'todos');

$condiciones = [];
$params      = [];

if ($busqueda !== '') {
    $condiciones[] = "(nombre LIKE :q OR descripcion LIKE :q2)";
    $params[':q']  = "%$busqueda%";
    $params[':q2'] = "%$busqueda%";
}
if ($catFiltro !== 'todos' && in_array($catFiltro, $categoriasValidas, true)) {
    $condiciones[] = "categoria = :cat";
    $params[':cat'] = $catFiltro;
}

$where    = $condiciones ? "WHERE " . implode(" AND ", $condiciones) : "";
$stmt     = $pdo->prepare("SELECT * FROM productos $where ORDER BY id DESC");
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas rápidas
$total     = count($productos);
$activos   = count(array_filter($productos, fn($p) => $p['activo']));
$destacados= count(array_filter($productos, fn($p) => $p['destacado']));
$inactivos = $total - $activos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — SafePet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Reset y base ─────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:    #2563eb;
            --primary-dk: #1e40af;
            --sidebar-bg: #0f172a;
            --sidebar-w:  260px;
            --bg:         #f1f5f9;
            --white:      #ffffff;
            --border:     #e2e8f0;
            --text:       #0f172a;
            --muted:      #64748b;
            --success:    #16a34a;
            --danger:     #dc2626;
            --warning:    #d97706;
            --radius:     12px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ──────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .sidebar-header .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-header .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dk));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }

        .sidebar-header .logo-text h2 {
            font-size: 16px; font-weight: 700; color: #fff;
        }

        .sidebar-header .logo-text span {
            font-size: 11px; color: #94a3b8; font-weight: 400;
        }

        .sidebar-admin {
            padding: 16px 24px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-admin .avatar {
            width: 36px; height: 36px;
            background: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }

        .sidebar-admin .info { min-width: 0; }
        .sidebar-admin .info p { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-admin .info span { font-size: 11px; color: #64748b; }

        .sidebar-nav { padding: 16px 12px; flex: 1; }

        .nav-label {
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .8px; color: #475569;
            padding: 8px 12px 6px; margin-top: 8px;
        }

        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 12px; border-radius: 8px;
            color: #94a3b8; text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: background .15s, color .15s;
            margin-bottom: 2px;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(37,99,235,.15);
            color: #93c5fd;
        }

        .nav-link.active { color: #60a5fa; }

        .nav-link i { width: 18px; text-align: center; }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .btn-logout {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 10px 12px; border-radius: 8px;
            background: rgba(220,38,38,.12); color: #f87171;
            border: none; cursor: pointer; font-family: 'Poppins', sans-serif;
            font-size: 14px; font-weight: 500;
            transition: background .15s;
            text-decoration: none;
        }

        .btn-logout:hover { background: rgba(220,38,38,.22); }

        /* ── Main ─────────────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }

        .topbar h1 { font-size: 20px; font-weight: 700; }
        .topbar p  { font-size: 13px; color: var(--muted); margin-top: 2px; }

        .btn-add {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dk));
            color: #fff; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 600; font-family: 'Poppins', sans-serif;
            cursor: pointer; transition: opacity .2s;
        }

        .btn-add:hover { opacity: .9; }

        /* ── Contenido ────────────────────────────────────── */
        .content { padding: 28px 32px; }

        /* ── Alerta ───────────────────────────────────────── */
        .alert {
            padding: 14px 18px; border-radius: var(--radius);
            margin-bottom: 24px; font-size: 14px;
            display: flex; align-items: center; gap: 10px;
        }

        .alert-exito  { background: #f0fdf4; border: 1px solid #bbf7d0; color: var(--success); }
        .alert-error  { background: #fef2f2; border: 1px solid #fecaca; color: var(--danger); }

        /* ── Stats cards ──────────────────────────────────── */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }

        .stat-icon.blue   { background: #eff6ff; color: var(--primary); }
        .stat-icon.green  { background: #f0fdf4; color: var(--success); }
        .stat-icon.yellow { background: #fffbeb; color: var(--warning); }
        .stat-icon.red    { background: #fef2f2; color: var(--danger); }

        .stat-info span { font-size: 26px; font-weight: 700; line-height: 1; }
        .stat-info p    { font-size: 12px; color: var(--muted); margin-top: 4px; }

        /* ── Toolbar de filtros ───────────────────────────── */
        .toolbar {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
        .search-wrap input {
            width: 100%; padding: 9px 12px 9px 36px;
            border: 1.5px solid var(--border); border-radius: 8px;
            font-family: 'Poppins', sans-serif; font-size: 13px;
            color: var(--text); outline: none;
            transition: border-color .2s;
        }
        .search-wrap input:focus { border-color: var(--primary); }

        .filter-btns { display: flex; gap: 6px; flex-wrap: wrap; }

        .filter-btn {
            padding: 8px 14px; border-radius: 8px;
            border: 1.5px solid var(--border);
            background: var(--white); color: var(--muted);
            font-size: 13px; font-family: 'Poppins', sans-serif;
            cursor: pointer; text-decoration: none;
            transition: all .15s; white-space: nowrap;
        }

        .filter-btn:hover, .filter-btn.active {
            border-color: var(--primary);
            background: #eff6ff; color: var(--primary);
            font-weight: 600;
        }

        /* ── Tabla ────────────────────────────────────────── */
        .tabla-wrap {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 13px 16px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            text-align: left;
            white-space: nowrap;
        }

        tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        td { padding: 14px 16px; font-size: 13px; vertical-align: middle; }

        .prod-nombre { font-weight: 600; color: var(--text); max-width: 200px; }
        .prod-desc {
            font-size: 12px; color: var(--muted);
            max-width: 220px; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }

        .badge-cat {
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600; white-space: nowrap;
        }

        .cat-alimentos  { background: #fef9c3; color: #854d0e; }
        .cat-accesorios { background: #ede9fe; color: #6d28d9; }
        .cat-salud      { background: #dcfce7; color: #166534; }
        .cat-higiene    { background: #e0f2fe; color: #075985; }

        .badge-promo {
            padding: 2px 8px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            background: #fee2e2; color: #991b1b;
        }

        .toggle-btn {
            width: 34px; height: 20px;
            border-radius: 20px;
            border: none; cursor: pointer;
            position: relative; transition: background .2s;
            flex-shrink: 0;
        }

        .toggle-btn::after {
            content: '';
            width: 14px; height: 14px;
            background: #fff; border-radius: 50%;
            position: absolute; top: 3px;
            transition: left .2s;
        }

        .toggle-btn.on  { background: var(--success); }
        .toggle-btn.off { background: #cbd5e1; }
        .toggle-btn.on::after  { left: 16px; }
        .toggle-btn.off::after { left: 3px; }

        .precio { font-weight: 700; color: var(--primary); }
        .precio-orig { font-size: 11px; color: var(--muted); text-decoration: line-through; display: block; }

        .acciones { display: flex; gap: 6px; align-items: center; }

        .btn-icon {
            width: 32px; height: 32px;
            border-radius: 8px; border: 1.5px solid var(--border);
            background: var(--white); color: var(--muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all .15s; font-size: 13px;
        }

        .btn-icon:hover.edit   { background: #eff6ff; color: var(--primary); border-color: var(--primary); }
        .btn-icon:hover.del    { background: #fef2f2; color: var(--danger);  border-color: var(--danger); }

        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--muted);
        }

        .empty-state i { font-size: 48px; opacity: .3; display: block; margin-bottom: 12px; }

        /* ── Modal ────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 200;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.visible { display: flex; }

        .modal {
            background: var(--white);
            border-radius: 16px;
            width: 100%; max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            animation: slideUp .2s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; background: var(--white); z-index: 1;
        }

        .modal-header h3 { font-size: 17px; font-weight: 700; }

        .btn-close {
            width: 32px; height: 32px; border-radius: 8px;
            border: 1.5px solid var(--border); background: var(--white);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 15px; color: var(--muted); transition: all .15s;
        }
        .btn-close:hover { background: #fef2f2; color: var(--danger); border-color: var(--danger); }

        .modal-body { padding: 24px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-grid .full { grid-column: 1 / -1; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }

        .form-group label {
            font-size: 13px; font-weight: 600; color: #374151;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 10px 12px;
            border: 1.5px solid var(--border); border-radius: 8px;
            font-family: 'Poppins', sans-serif; font-size: 13px;
            color: var(--text); outline: none;
            transition: border-color .2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus { border-color: var(--primary); }

        .form-group textarea { resize: vertical; min-height: 80px; }

        .form-check {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 500; cursor: pointer;
        }
        .form-check input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex; gap: 10px; justify-content: flex-end;
        }

        .btn-secondary {
            padding: 10px 20px; border-radius: 10px;
            border: 1.5px solid var(--border); background: var(--white);
            color: var(--muted); font-family: 'Poppins', sans-serif;
            font-size: 14px; font-weight: 500; cursor: pointer;
            transition: background .15s;
        }
        .btn-secondary:hover { background: var(--bg); }

        .btn-primary {
            padding: 10px 24px; border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dk));
            color: #fff; border: none;
            font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: opacity .2s;
        }
        .btn-primary:hover { opacity: .9; }

        /* ── Modal eliminar ───────────────────────────────── */
        .modal-del-body {
            padding: 32px 24px;
            text-align: center;
        }

        .modal-del-body .del-icon {
            width: 64px; height: 64px;
            background: #fef2f2; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: var(--danger);
            margin: 0 auto 16px;
        }

        .modal-del-body h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .modal-del-body p  { color: var(--muted); font-size: 14px; }

        .btn-danger {
            padding: 10px 24px; border-radius: 10px;
            background: var(--danger); color: #fff; border: none;
            font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: opacity .2s;
        }
        .btn-danger:hover { opacity: .85; }

        /* ── Responsive básico ────────────────────────────── */
        @media (max-width: 900px) {
            :root { --sidebar-w: 0px; }
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .content { padding: 20px; }
            .topbar { padding: 14px 20px; }
        }

        @media (max-width: 640px) {
            .stats { grid-template-columns: 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full { grid-column: 1; }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════ SIDEBAR ══════════════════════════ -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">🐾</div>
            <div class="logo-text">
                <h2>SafePet</h2>
                <span>Panel Administrador</span>
            </div>
        </div>
    </div>

    <div class="sidebar-admin">
        <div class="avatar"><?= strtoupper(substr($adminNombre, 0, 1)) ?></div>
        <div class="info">
            <p><?= $adminNombre ?></p>
            <span>Administrador</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">General</div>
        <a href="index.php" class="nav-link active">
            <i class="fas fa-box-open"></i> Productos
        </a>
        <a href="mensajes.php" class="nav-link">
            <i class="fas fa-inbox"></i> Mensajes
        </a>

        <div class="nav-label">Sitio</div>
        <a href="../index.php" class="nav-link" target="_blank">
            <i class="fas fa-house"></i> Ver sitio web
        </a>
        <a href="../productos.php" class="nav-link" target="_blank">
            <i class="fas fa-store"></i> Ver tienda
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-right-from-bracket"></i> Cerrar sesión
        </a>
    </div>
</aside>

<!-- ══════════════════════════ MAIN ══════════════════════════════ -->
<main class="main">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <h1><i class="fas fa-box-open" style="color:var(--primary);margin-right:8px;"></i>Gestión de Productos</h1>
            <p>Agrega, edita o elimina productos de la tienda</p>
        </div>
        <button class="btn-add" onclick="abrirModalAgregar()">
            <i class="fas fa-plus"></i> Nuevo producto
        </button>
    </div>

    <!-- Contenido -->
    <div class="content">

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipoMensaje ?>">
                <i class="fas fa-<?= $tipoMensaje === 'exito' ? 'circle-check' : 'circle-exclamation' ?>"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-boxes-stacked"></i></div>
                <div class="stat-info">
                    <span><?= $total ?></span>
                    <p>Total productos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
                <div class="stat-info">
                    <span><?= $activos ?></span>
                    <p>Publicados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <span><?= $destacados ?></span>
                    <p>Destacados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-eye-slash"></i></div>
                <div class="stat-info">
                    <span><?= $inactivos ?></span>
                    <p>Inactivos</p>
                </div>
            </div>
        </div>

        <!-- Toolbar de filtros -->
        <div class="toolbar">
            <form method="GET" action="index.php" style="display:contents;">
                <div class="search-wrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="q" placeholder="Buscar producto..."
                           value="<?= htmlspecialchars($busqueda) ?>"
                           onchange="this.form.submit()">
                </div>
                <?php if ($catFiltro !== 'todos'): ?>
                    <input type="hidden" name="cat" value="<?= htmlspecialchars($catFiltro) ?>">
                <?php endif; ?>
            </form>

            <div class="filter-btns">
                <?php
                $filtros = ['todos' => 'Todos', 'Alimentos' => '🍖 Alimentos',
                            'Accesorios' => '🎾 Accesorios', 'Salud' => '💊 Salud', 'Higiene' => '🛁 Higiene'];
                foreach ($filtros as $val => $label):
                    $q    = $busqueda ? "&q=" . urlencode($busqueda) : '';
                    $href = "index.php?cat=$val$q";
                    $active = ($catFiltro === $val) ? 'active' : '';
                ?>
                    <a href="<?= $href ?>" class="filter-btn <?= $active ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tabla de productos -->
        <div class="tabla-wrap">
            <?php if (empty($productos)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>No se encontraron productos<?= $busqueda ? " para \"$busqueda\"" : '' ?>.</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Portada</th>
                        <th>Nombre / Descripción</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Activo</th>
                        <th>Destacado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($productos as $p): ?>
                    <?php
                        $catClass = 'cat-' . strtolower($p['categoria']);
                    ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td style="text-align:center;">
                            <?php if (!empty($p['portada'])): ?>
                                <img src="<?= htmlspecialchars($p['portada']) ?>"
                                     alt="Portada de <?= htmlspecialchars($p['nombre']) ?>"
                                     style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:1px solid #e2e8f0;">
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size:0.8rem;">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="prod-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                            <div class="prod-desc"><?= htmlspecialchars($p['descripcion'] ?? '') ?></div>
                            <?php if ($p['badge']): ?>
                                <span class="badge-promo"><?= htmlspecialchars($p['badge']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-cat <?= $catClass ?>"><?= htmlspecialchars($p['categoria']) ?></span></td>
                        <td>
                            <span class="precio">$<?= number_format($p['precio'], 0, ',', '.') ?></span>
                            <?php if ($p['precio_original']): ?>
                                <span class="precio-orig">$<?= number_format($p['precio_original'], 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$p['stock'] ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="accion" value="toggle_activo">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="toggle-btn <?= $p['activo'] ? 'on' : 'off' ?>"
                                        title="<?= $p['activo'] ? 'Desactivar' : 'Activar' ?>"></button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="accion" value="toggle_destacado">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="toggle-btn <?= $p['destacado'] ? 'on' : 'off' ?>"
                                        title="<?= $p['destacado'] ? 'Quitar de destacados' : 'Marcar como destacado' ?>"></button>
                            </form>
                        </td>
                        <td>
                            <div class="acciones">
                                <button class="btn-icon edit" title="Editar"
                                    onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-icon del" title="Eliminar"
                                    onclick="abrirModalEliminar(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div><!-- /content -->
</main>

<!-- ══════════════════ MODAL: AGREGAR / EDITAR ══════════════════ -->
<div class="modal-overlay" id="modalProducto">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitulo">Nuevo producto</h3>
            <button class="btn-close" onclick="cerrarModal('modalProducto')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form method="POST" id="formProducto">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="accion"     id="formAccion"  value="agregar">
            <input type="hidden" name="id"         id="formId"      value="">

            <div class="modal-body">
                <div class="form-grid">

                    <div class="form-group full">
                        <label for="fnombre">Nombre del producto *</label>
                        <input type="text" id="fnombre" name="nombre" required maxlength="150"
                               placeholder="Ej: Croquetas Premium para Perro">
                    </div>

                    <div class="form-group full">
                        <label for="fdescripcion">Descripción</label>
                        <textarea id="fdescripcion" name="descripcion" maxlength="500"
                                  placeholder="Describe brevemente el producto..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fprecio">Precio (COP) *</label>
                        <input type="number" id="fprecio" name="precio" required min="1"
                               placeholder="Ej: 45000">
                    </div>

                    <div class="form-group">
                        <label for="fprecio_original">Precio original (opcional)</label>
                        <input type="number" id="fprecio_original" name="precio_original" min="1"
                               placeholder="Ej: 55000 (para mostrar descuento)">
                    </div>

                    <div class="form-group">
                        <label for="fcategoria">Categoría *</label>
                        <select id="fcategoria" name="categoria" required>
                            <option value="">— Selecciona —</option>
                            <?php foreach ($categoriasValidas as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fstock">Stock</label>
                        <input type="number" id="fstock" name="stock" min="0" value="100"
                               placeholder="Ej: 50">
                    </div>

                    <div class="form-group full">
                        <label for="fportada">Imagen de portada (URL)</label>
                        <input type="url" id="fportada" name="portada" maxlength="255"
                               placeholder="Ej: https://misitio.com/img/productos/croquetas.jpg">
                    </div>

                    <div class="form-group full">
                        <label for="fgaleria">Galería de imágenes (URLs)</label>
                        <textarea id="fgaleria" name="galeria" maxlength="2000"
                                  placeholder="Una URL por línea o separadas por comas"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fbadge">Badge / Etiqueta</label>
                        <input type="text" id="fbadge" name="badge" maxlength="30"
                               placeholder="Ej: Nuevo, 20% OFF">
                    </div>

                    <div class="form-group" style="flex-direction:row;align-items:center;gap:10px;">
                        <label class="form-check">
                            <input type="checkbox" name="activo" id="factivo" value="1" checked>
                            Publicar (visible en tienda)
                        </label>
                    </div>

                    <div class="form-group" style="flex-direction:row;align-items:center;gap:10px;">
                        <label class="form-check">
                            <input type="checkbox" name="destacado" id="fdestacado" value="1">
                            Destacado (aparece en inicio)
                        </label>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModal('modalProducto')">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnGuardar">
                    <i class="fas fa-floppy-disk"></i> Guardar producto
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════ MODAL: ELIMINAR ══════════════════════════ -->
<div class="modal-overlay" id="modalEliminar">
    <div class="modal" style="max-width:440px;">
        <form method="POST" id="formEliminar">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" id="eliminarId" value="">

            <div class="modal-del-body">
                <div class="del-icon"><i class="fas fa-trash"></i></div>
                <h3>¿Eliminar producto?</h3>
                <p id="eliminarNombre" style="margin-top:6px;font-weight:600;color:var(--text);"></p>
                <p style="margin-top:8px;">Esta acción es permanente y no se puede deshacer.</p>
            </div>

            <div class="modal-footer" style="justify-content:center;gap:12px;">
                <button type="button" class="btn-secondary" onclick="cerrarModal('modalEliminar')">Cancelar</button>
                <button type="submit" class="btn-danger">
                    <i class="fas fa-trash"></i> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ── Helpers ─────────────────────────────────────────────── */
function cerrarModal(id) {
    document.getElementById(id).classList.remove('visible');
}

// Cerrar modal al hacer clic fuera
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) cerrarModal(overlay.id);
    });
});

// Cerrar con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.visible').forEach(m => {
            m.classList.remove('visible');
        });
    }
});

/* ── Modal agregar ───────────────────────────────────────── */
function abrirModalAgregar() {
    document.getElementById('modalTitulo').textContent = 'Nuevo producto';
    document.getElementById('formAccion').value  = 'agregar';
    document.getElementById('formId').value      = '';
    document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-plus"></i> Agregar producto';
    document.getElementById('formProducto').reset();
    // Valores por defecto
    document.getElementById('fstock').value  = '100';
    document.getElementById('factivo').checked = true;
    document.getElementById('fdestacado').checked = false;
    document.getElementById('modalProducto').classList.add('visible');
}

/* ── Modal editar ────────────────────────────────────────── */
function abrirModalEditar(p) {
    document.getElementById('modalTitulo').textContent = 'Editar producto';
    document.getElementById('formAccion').value  = 'editar';
    document.getElementById('formId').value      = p.id;
    document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar cambios';

    document.getElementById('fnombre').value          = p.nombre        || '';
    document.getElementById('fdescripcion').value     = p.descripcion   || '';
    document.getElementById('fprecio').value          = p.precio        || '';
    document.getElementById('fprecio_original').value = p.precio_original || '';
    document.getElementById('fcategoria').value       = p.categoria     || '';
    document.getElementById('fstock').value           = p.stock         || '0';
    document.getElementById('fportada').value         = p.portada       || '';
    document.getElementById('fgaleria').value         = p.galeria       || '';
    document.getElementById('fbadge').value           = p.badge         || '';
    document.getElementById('factivo').checked        = p.activo == 1;
    document.getElementById('fdestacado').checked     = p.destacado == 1;

    document.getElementById('modalProducto').classList.add('visible');
}

/* ── Modal eliminar ──────────────────────────────────────── */
function abrirModalEliminar(id, nombre) {
    document.getElementById('eliminarId').value        = id;
    document.getElementById('eliminarNombre').textContent = nombre;
    document.getElementById('modalEliminar').classList.add('visible');
}

// Auto-ocultar la alerta de éxito después de 4 s
const alert = document.querySelector('.alert-exito');
if (alert) setTimeout(() => alert.style.opacity = '0', 4000);
</script>
</body>
</html>
