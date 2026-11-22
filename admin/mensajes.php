<?php
/**
 * ============================================================
 * ARCHIVO: admin/mensajes.php
 * PROPÓSITO: Ver y gestionar mensajes enviados desde contacto.php
 * Funcionalidades:
 *  - Listar mensajes de contacto
 *  - Filtrar por estado (nuevo / leido)
 *  - Marcar como leido / nuevo
 *  - Eliminar mensajes
 * ============================================================
 */
require_once 'auth.php';
require_once '../config/db.php';

$adminNombre = htmlspecialchars($_SESSION['admin_nombre']);
$mensaje     = '';
$tipoMensaje = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$pdo = getPDO();

// ── Función: revisar token de seguridad del formulario ──────────
// Evita que te envíen acciones desde fuera del panel.
function tokenValido(string $tokenSesion, array $post): bool
{
    return !empty($post['csrf_token']) && hash_equals($tokenSesion, $post['csrf_token']);
}

// ── Función: ejecutar acción sobre un mensaje ───────────────────
// Devuelve el texto de respuesta para mostrar en pantalla.
function ejecutarAccionMensaje(PDO $pdo, string $accion, int $id): array
{
    if ($id <= 0) {
        return ['', ''];
    }

    if ($accion === 'marcar_leido') {
        $pdo->prepare("UPDATE mensajes_contacto SET estado = 'leido' WHERE id = :id")
            ->execute([':id' => $id]);
        return ['Mensaje marcado como leido.', 'exito'];
    }

    if ($accion === 'marcar_nuevo') {
        $pdo->prepare("UPDATE mensajes_contacto SET estado = 'nuevo' WHERE id = :id")
            ->execute([':id' => $id]);
        return ['Mensaje marcado como nuevo.', 'exito'];
    }

    if ($accion === 'eliminar') {
        $pdo->prepare("DELETE FROM mensajes_contacto WHERE id = :id")
            ->execute([':id' => $id]);
        return ['Mensaje eliminado.', 'exito'];
    }

    return ['', ''];
}

// ── Procesar formulario (acciones de botones) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!tokenValido($csrfToken, $_POST)) {
        $mensaje = 'Token de seguridad invalido.';
        $tipoMensaje = 'error';
    } else {
        $accion = $_POST['accion'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);
        [$mensaje, $tipoMensaje] = ejecutarAccionMensaje($pdo, $accion, $id);
    }

    // Redirigimos solo cuando hay acción exitosa para evitar reenvío del POST.
    if ($tipoMensaje === 'exito') {
        $filtro = urlencode($_GET['estado'] ?? 'todos');
        header("Location: mensajes.php?estado=$filtro&msg=" . urlencode($mensaje));
        exit;
    }
}

if (isset($_GET['msg']) && $mensaje === '') {
    $mensaje = urldecode($_GET['msg']);
    $tipoMensaje = 'exito';
}

// ── Filtro de listado ───────────────────────────────────────────
// Si llega "nuevo" o "leido" filtramos; si no, mostramos todos.
$estadoFiltro = $_GET['estado'] ?? 'todos';
$where  = '';
$params = [];

if ($estadoFiltro === 'nuevo' || $estadoFiltro === 'leido') {
    $where = 'WHERE estado = :estado';
    $params[':estado'] = $estadoFiltro;
}

// ── Consulta principal de mensajes ──────────────────────────────
$stmt = $pdo->prepare("SELECT id, nombre, email, asunto, mensaje, estado, fecha_creacion FROM mensajes_contacto $where ORDER BY id DESC");
$stmt->execute($params);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Resumen rápido para las tarjetas de arriba ─────────────────
$totalMensajes = (int) $pdo->query("SELECT COUNT(*) FROM mensajes_contacto")->fetchColumn();
$totalNuevos   = (int) $pdo->query("SELECT COUNT(*) FROM mensajes_contacto WHERE estado = 'nuevo'")->fetchColumn();
$totalLeidos   = (int) $pdo->query("SELECT COUNT(*) FROM mensajes_contacto WHERE estado = 'leido'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Mensajes | SafePet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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

        .sidebar-admin .info p { font-size: 13px; font-weight: 600; color: #fff; }
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

        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-height: 100vh;
        }

        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 16px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
        }

        .topbar h1 { font-size: 20px; }
        .topbar p  { color: var(--muted); font-size: 13px; }

        .content { padding: 24px 28px; }

        .alert {
            padding: 12px 14px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-exito { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 18px;
        }

        .stat {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
        }

        .stat span {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        .stat p { color: var(--muted); font-size: 12px; margin-top: 5px; }

        .filters {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .filter {
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--muted);
            background: var(--white);
            font-size: 13px;
        }

        .filter.active, .filter:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #eff6ff;
        }

        .message-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 12px;
        }

        .message-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 8px;
        }

        .message-top h3 {
            font-size: 15px;
            margin-bottom: 4px;
        }

        .message-meta {
            color: var(--muted);
            font-size: 12px;
        }

        .estado {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .estado-nuevo { background: #ffedd5; color: #9a3412; }
        .estado-leido { background: #dcfce7; color: #166534; }

        .message-body {
            margin-top: 10px;
            color: #334155;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid var(--border);
            background: #fff;
            color: #334155;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn:hover { background: #f8fafc; }
        .btn-danger { border-color: #fecaca; color: #b91c1c; background: #fef2f2; }

        .empty {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px 20px;
            text-align: center;
            color: var(--muted);
        }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
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
        <a href="index.php" class="nav-link">
            <i class="fas fa-box-open"></i> Productos
        </a>
        <a href="mensajes.php" class="nav-link active">
            <i class="fas fa-inbox"></i> Mensajes
        </a>

        <div class="nav-label">Sitio</div>
        <a href="../contacto.php" class="nav-link" target="_blank">
            <i class="fas fa-envelope"></i> Ver contacto
        </a>
        <a href="../index.php" class="nav-link" target="_blank">
            <i class="fas fa-house"></i> Ver sitio web
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-right-from-bracket"></i> Cerrar sesion
        </a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <h1><i class="fas fa-inbox" style="color:var(--primary);margin-right:6px;"></i>Mensajes de Contacto</h1>
            <p>Mensajes recibidos desde contacto.php</p>
        </div>
    </div>

    <div class="content">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipoMensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat"><span><?= $totalMensajes ?></span><p>Total mensajes</p></div>
            <div class="stat"><span><?= $totalNuevos ?></span><p>Nuevos</p></div>
            <div class="stat"><span><?= $totalLeidos ?></span><p>Leidos</p></div>
        </div>

        <div class="filters">
            <a class="filter <?= $estadoFiltro === 'todos' ? 'active' : '' ?>" href="mensajes.php?estado=todos">Todos</a>
            <a class="filter <?= $estadoFiltro === 'nuevo' ? 'active' : '' ?>" href="mensajes.php?estado=nuevo">Nuevos</a>
            <a class="filter <?= $estadoFiltro === 'leido' ? 'active' : '' ?>" href="mensajes.php?estado=leido">Leidos</a>
        </div>

        <?php if (empty($mensajes)): ?>
            <div class="empty">
                <i class="fas fa-inbox" style="font-size:40px;opacity:.25;display:block;margin-bottom:8px;"></i>
                No hay mensajes para este filtro.
            </div>
        <?php else: ?>
            <?php foreach ($mensajes as $m): ?>
                <div class="message-card">
                    <div class="message-top">
                        <div>
                            <h3><?= htmlspecialchars($m['asunto']) ?></h3>
                            <div class="message-meta">
                                <strong><?= htmlspecialchars($m['nombre']) ?></strong>
                                (<?= htmlspecialchars($m['email']) ?>)
                                • <?= date('d/m/Y H:i', strtotime($m['fecha_creacion'])) ?>
                            </div>
                        </div>
                        <span class="estado estado-<?= $m['estado'] === 'nuevo' ? 'nuevo' : 'leido' ?>">
                            <?= $m['estado'] === 'nuevo' ? 'Nuevo' : 'Leido' ?>
                        </span>
                    </div>

                    <div class="message-body"><?= htmlspecialchars($m['mensaje']) ?></div>

                    <div class="actions">
                        <?php if ($m['estado'] === 'nuevo'): ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="accion" value="marcar_leido">
                                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                <button type="submit" class="btn"><i class="fas fa-check"></i> Marcar leido</button>
                            </form>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="accion" value="marcar_nuevo">
                                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                <button type="submit" class="btn"><i class="fas fa-rotate-left"></i> Marcar nuevo</button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" onsubmit="return confirm('¿Eliminar este mensaje?');">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
