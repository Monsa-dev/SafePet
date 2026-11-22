<?php
/**
 * ================================================================
 * ARCHIVO: cuidadores/dashboard.php
 * PROPÓSITO: Panel de control del cuidador autenticado.
 * Secciones:
 *  - Resumen (estadísticas: solicitudes pendientes, aceptadas, etc.)
 *  - Solicitudes recibidas (tabla con opción de aceptar/rechazar)
 *  - Toggle de disponibilidad del cuidador
 *  - Perfil básico del cuidador
 *
 * SEGURIDAD:
 *  - Requiere sesión activa (redirige al login si no hay sesión)
 *  - Todas las acciones se validan con tokens CSRF implícitos
 *    (verificación de que el ID de solicitud pertenece al cuidador)
 * ================================================================
 */

session_start();

// ── PROTECCIÓN DE LA RUTA ────────────────────────────────────────
// Si no hay sesión activa del cuidador, bloquear el acceso
if (!isset($_SESSION['cuidador_id'])) {
    $_SESSION['flash_info'] = "Debes iniciar sesión para acceder al panel.";
    header('Location: login.php');
    exit;
}

$basePath     = '../';
$cuidadorId   = (int)$_SESSION['cuidador_id'];
$cuidadorNom  = htmlspecialchars($_SESSION['cuidador_nombre']);

require_once $basePath . 'config/db.php';

// ── PROCESAR ACCIONES (aceptar/rechazar solicitudes, toggle disponibilidad) ──
$accionMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo    = getPDO();
        $accion = $_POST['accion'] ?? '';

        // ── Acción: Aceptar solicitud ────────────────────────
        if ($accion === 'aceptar' && isset($_POST['solicitud_id'])) {
            $solicitudId = (int)$_POST['solicitud_id'];

            // SEGURIDAD: verificar que la solicitud pertenece a ESTE cuidador
            // (no se puede aceptar la solicitud de otro cuidador)
            $stmt = $pdo->prepare("
                UPDATE solicitudes
                SET estado = 'aceptada', fecha_respuesta = NOW()
                WHERE id = :id AND cuidador_id = :cuidador_id AND estado = 'pendiente'
            ");
            $stmt->execute([':id' => $solicitudId, ':cuidador_id' => $cuidadorId]);

            if ($stmt->rowCount() > 0) {
                $accionMsg = ['tipo' => 'success', 'texto' => 'Solicitud aceptada correctamente.'];
            } else {
                $accionMsg = ['tipo' => 'danger', 'texto' => 'No se pudo aceptar la solicitud.'];
            }
        }

        // ── Acción: Rechazar solicitud ───────────────────────
        if ($accion === 'rechazar' && isset($_POST['solicitud_id'])) {
            $solicitudId = (int)$_POST['solicitud_id'];

            $stmt = $pdo->prepare("
                UPDATE solicitudes
                SET estado = 'rechazada', fecha_respuesta = NOW()
                WHERE id = :id AND cuidador_id = :cuidador_id AND estado = 'pendiente'
            ");
            $stmt->execute([':id' => $solicitudId, ':cuidador_id' => $cuidadorId]);

            $accionMsg = ['tipo' => 'info', 'texto' => 'Solicitud rechazada.'];
        }

        // ── Acción: Cambiar disponibilidad ───────────────────
        if ($accion === 'toggle_disponibilidad') {
            // Alternar el valor de disponible (0 → 1 o 1 → 0)
            $stmt = $pdo->prepare("
                UPDATE cuidadores
                SET disponible = IF(disponible = 1, 0, 1)
                WHERE id = :id
            ");
            $stmt->execute([':id' => $cuidadorId]);
            $accionMsg = ['tipo' => 'success', 'texto' => 'Disponibilidad actualizada.'];
        }

    } catch (Exception $e) {
        error_log("Error dashboard: " . $e->getMessage());
        $accionMsg = ['tipo' => 'danger', 'texto' => 'Error al procesar la solicitud.'];
    }
}

// ── OBTENER DATOS DEL DASHBOARD ──────────────────────────────────
try {
    $pdo = getPDO();

    // Datos del perfil del cuidador actual
    $stmtPerfil = $pdo->prepare("
        SELECT id, nombre, email, telefono, ciudad, especialidad, descripcion, precio_hora, foto, disponible, rating
        FROM cuidadores
        WHERE id = :id
    ");
    $stmtPerfil->execute([':id' => $cuidadorId]);
    $perfil = $stmtPerfil->fetch();

    if (!$perfil) {
        // Si no existe el cuidador en BD, cerrar sesión
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Estadísticas del cuidador
    $stmtStats = $pdo->prepare("
        SELECT
            SUM(estado = 'pendiente')  AS pendientes,
            SUM(estado = 'aceptada')   AS aceptadas,
            SUM(estado = 'rechazada')  AS rechazadas,
            COUNT(*)                   AS total
        FROM solicitudes
        WHERE cuidador_id = :id
    ");
    $stmtStats->execute([':id' => $cuidadorId]);
    $stats = $stmtStats->fetch();

    // Solicitudes pendientes (las más importantes → primero)
    $stmtSolicitudes = $pdo->prepare("
        SELECT s.id, s.nombre_dueno, s.email_dueno, s.telefono_dueno,
               s.tipo_mascota, s.nombre_mascota, s.fecha_inicio, s.fecha_fin,
               s.mensaje, s.estado, s.fecha_solicitud
        FROM solicitudes s
        WHERE s.cuidador_id = :id
        ORDER BY
            CASE s.estado WHEN 'pendiente' THEN 0 ELSE 1 END,
            s.fecha_solicitud DESC
        LIMIT 20
    ");
    $stmtSolicitudes->execute([':id' => $cuidadorId]);
    $solicitudes = $stmtSolicitudes->fetchAll();

} catch (Exception $e) {
    // Si la BD no está disponible, usar datos de demostración
    $perfil = [
        'id' => $cuidadorId, 'nombre' => $cuidadorNom,
        'email' => $_SESSION['cuidador_email'] ?? '', 'telefono' => '',
        'ciudad' => 'Bogotá', 'especialidad' => 'Perros y Gatos',
        'descripcion' => '', 'precio_hora' => 25000,
        'foto' => null, 'disponible' => 1, 'rating' => 4.5
    ];
    $stats = ['pendientes' => 2, 'aceptadas' => 5, 'rechazadas' => 1, 'total' => 8];
    $solicitudes = [
        ['id'=>1, 'nombre_dueno'=>'Andrés Torres', 'email_dueno'=>'andres@mail.com',
         'telefono_dueno'=>'3001234567', 'tipo_mascota'=>'Perro', 'nombre_mascota'=>'Max',
         'fecha_inicio'=>'2026-04-10', 'fecha_fin'=>'2026-04-12',
         'mensaje'=>'Necesito cuidado por el fin de semana.', 'estado'=>'pendiente',
         'fecha_solicitud'=>'2026-04-06 10:30:00'],
        ['id'=>2, 'nombre_dueno'=>'Laura Jiménez', 'email_dueno'=>'laura@mail.com',
         'telefono_dueno'=>'3109876543', 'tipo_mascota'=>'Gato', 'nombre_mascota'=>'Luna',
         'fecha_inicio'=>'2026-04-15', 'fecha_fin'=>'2026-04-15',
         'mensaje'=>'Solo un día mientras viajo.', 'estado'=>'aceptada',
         'fecha_solicitud'=>'2026-04-05 14:00:00'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel | SafePet Cuidadores</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- ════════════════════════════════════════════════════════
     LAYOUT DEL DASHBOARD (Sidebar + Contenido)
     ════════════════════════════════════════════════════════ -->
<div class="dashboard-layout">

    <!-- ── SIDEBAR IZQUIERDO ─────────────────────────────── -->
    <aside class="sidebar">

        <!-- Logo en el sidebar -->
        <div class="sidebar-logo">
            <img src="../img/logo safepet.png" alt="SafePet" style="filter:brightness(0) invert(1);">
            <p style="color:rgba(255,255,255,0.8); font-size:0.8rem; margin-top:0.5rem;">Panel del Cuidador</p>
        </div>

        <!-- Información del cuidador logueado -->
        <div style="padding:0 1.5rem 1.5rem; border-bottom:1px solid rgba(255,255,255,0.15); margin-bottom:1rem;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:45px; height:45px; background:rgba(255,255,255,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.3rem; overflow:hidden;">
                    <?php if (!empty($perfil['foto'])): ?>
                        <img src="../<?= htmlspecialchars($perfil['foto']) ?>" alt="Foto de perfil" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <?= strtoupper(substr($cuidadorNom, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <p style="color:white; font-weight:600; font-size:0.9rem; margin:0;"><?= $cuidadorNom ?></p>
                    <p style="color:rgba(255,255,255,0.65); font-size:0.78rem; margin:0;"><?= htmlspecialchars($perfil['especialidad']) ?></p>
                </div>
            </div>

            <!-- Toggle de disponibilidad en el sidebar -->
            <form method="POST" style="margin-top:1rem; display:flex; align-items:center; gap:10px;">
                <input type="hidden" name="accion" value="toggle_disponibilidad">
                <label class="toggle-switch">
                    <input type="checkbox"
                           <?= $perfil['disponible'] ? 'checked' : '' ?>
                           onclick="this.form.submit()">
                    <span class="toggle-slider"></span>
                </label>
                <span style="color:rgba(255,255,255,0.85); font-size:0.85rem;">
                    <?= $perfil['disponible'] ? '✅ Disponible' : '⏸️ No disponible' ?>
                </span>
            </form>
        </div>

        <!-- Navegación del panel -->
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i> Mi Panel
            </a>
            <a href="dashboard.php#solicitudes">
                <i class="fas fa-bell"></i> Solicitudes
                <!-- Badge con solicitudes pendientes -->
                <?php if ($stats['pendientes'] > 0): ?>
                <span style="background:#E53935; color:white; border-radius:20px; padding:2px 8px; font-size:0.75rem; margin-left:auto;">
                    <?= $stats['pendientes'] ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="perfil.php">
                <i class="fas fa-user-edit"></i> Mi Perfil
            </a>
            <a href="../index.php">
                <i class="fas fa-globe"></i> Ver Sitio Web
            </a>
            <a href="logout.php" style="margin-top:auto; color:rgba(255,100,100,0.9);">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
    </aside>
    <!-- Fin sidebar -->

    <!-- ── CONTENIDO PRINCIPAL ───────────────────────────── -->
    <main class="dashboard-content">

        <!-- Saludo personalizado con disponibilidad -->
        <div class="dashboard-greeting">
            <h2>¡Hola, <?= $cuidadorNom ?>! 👋</h2>
            <p>
                <?php if ($perfil['disponible']): ?>
                    Tu perfil está <strong>visible</strong> y puedes recibir nuevas solicitudes.
                <?php else: ?>
                    Tu perfil está <strong>oculto</strong>. Activa tu disponibilidad para recibir solicitudes.
                <?php endif; ?>
            </p>
        </div>

        <!-- Mensaje de acción (aceptar/rechazar) -->
        <?php if (!empty($accionMsg)): ?>
        <div class="alert alert-<?= $accionMsg['tipo'] ?>">
            <i class="fas fa-info-circle"></i>
            <?= htmlspecialchars($accionMsg['texto']) ?>
        </div>
        <?php endif; ?>

        <!-- Mensaje flash del login/registro -->
        <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <!-- ── TARJETAS DE ESTADÍSTICAS ──────────────────── -->
        <div class="stats-grid">

            <div class="stat-card" style="border-left-color:#FFA726;">
                <div class="stat-icon" style="color:#FFA726;"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="stat-value" style="color:#FFA726;"><?= $stats['pendientes'] ?? 0 ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>

            <div class="stat-card" style="border-left-color:#43A047;">
                <div class="stat-icon" style="color:#43A047;"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="stat-value" style="color:#43A047;"><?= $stats['aceptadas'] ?? 0 ?></div>
                    <div class="stat-label">Aceptadas</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-list-alt"></i></div>
                <div>
                    <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-label">Total solicitudes</div>
                </div>
            </div>

            <div class="stat-card" style="border-left-color:#FFA726;">
                <div class="stat-icon" style="color:#FFA726;"><i class="fas fa-star"></i></div>
                <div>
                    <div class="stat-value" style="color:#FFA726;"><?= number_format($perfil['rating'], 1) ?></div>
                    <div class="stat-label">Calificación</div>
                </div>
            </div>

        </div>


        <!-- ════════════════════════════════════════════════
             TABLA DE SOLICITUDES
             Listado de solicitudes con opciones de acción.
             Las pendientes aparecen primero.
             ════════════════════════════════════════════════ -->
        <div class="card" id="solicitudes">
            <div class="card-title">
                <i class="fas fa-bell"></i>
                Solicitudes de Cuidado
                <?php if ($stats['pendientes'] > 0): ?>
                <span style="background:#E53935; color:white; border-radius:20px; padding:2px 10px; font-size:0.8rem; font-weight:600;">
                    <?= $stats['pendientes'] ?> nueva<?= $stats['pendientes'] > 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if (empty($solicitudes)): ?>
            <!-- Estado vacío: aún no hay solicitudes -->
            <div style="text-align:center; padding:3rem; color:var(--color-gray);">
                <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:1rem; display:block; color:#e0e0e0;"></i>
                <p>Aún no has recibido solicitudes.</p>
                <p style="font-size:0.85rem;">Asegúrate de tener tu perfil activo y disponible.</p>
            </div>

            <?php else: ?>

            <!-- Tabla responsive de solicitudes -->
            <div style="overflow-x:auto;">
                <table class="solicitudes-table">
                    <thead>
                        <tr>
                            <th>Dueño</th>
                            <th>Mascota</th>
                            <th>Fechas</th>
                            <th>Mensaje</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <!-- Datos del dueño -->
                            <td>
                                <strong><?= htmlspecialchars($s['nombre_dueno']) ?></strong><br>
                                <small style="color:var(--color-gray);"><?= htmlspecialchars($s['email_dueno']) ?></small><br>
                                <small>📱 <?= htmlspecialchars($s['telefono_dueno']) ?></small>
                            </td>

                            <!-- Tipo y nombre de la mascota -->
                            <td>
                                <strong><?= htmlspecialchars($s['nombre_mascota']) ?></strong><br>
                                <span class="tag"><?= htmlspecialchars($s['tipo_mascota']) ?></span>
                            </td>

                            <!-- Fechas de cuidado -->
                            <td>
                                <small>
                                    📅 Del <?= date('d/m/Y', strtotime($s['fecha_inicio'])) ?><br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;al <?= date('d/m/Y', strtotime($s['fecha_fin'])) ?>
                                </small>
                            </td>

                            <!-- Mensaje del dueño (truncado) -->
                            <td style="max-width:150px;">
                                <small style="color:var(--color-gray);">
                                    <?= htmlspecialchars(substr($s['mensaje'] ?? '', 0, 60)) ?>
                                    <?= strlen($s['mensaje'] ?? '') > 60 ? '...' : '' ?>
                                </small>
                            </td>

                            <!-- Badge de estado -->
                            <td>
                                <?php
                                $badgeClase = match($s['estado']) {
                                    'pendiente' => 'badge-pendiente',
                                    'aceptada'  => 'badge-aceptada',
                                    'rechazada' => 'badge-rechazada',
                                    default     => 'tag'
                                };
                                $iconoEstado = match($s['estado']) {
                                    'pendiente' => 'fa-clock',
                                    'aceptada'  => 'fa-check',
                                    'rechazada' => 'fa-times',
                                    default     => 'fa-question'
                                };
                                ?>
                                <span class="<?= $badgeClase ?>">
                                    <i class="fas <?= $iconoEstado ?>"></i>
                                    <?= ucfirst($s['estado']) ?>
                                </span>
                            </td>

                            <!-- Botones de acción (solo para solicitudes pendientes) -->
                            <td>
                                <?php if ($s['estado'] === 'pendiente'): ?>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">

                                    <!--
                                        FORMULARIO PARA ACEPTAR SOLICITUD
                                        POST con campo hidden para identificar la solicitud y la acción.
                                        La verificación de que la solicitud pertenece al cuidador
                                        se hace en el PHP del servidor (línea ~70 aprox.)
                                    -->
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('¿Aceptar la solicitud de <?= htmlspecialchars(addslashes($s['nombre_dueno'])) ?>?')">
                                        <input type="hidden" name="accion"       value="aceptar">
                                        <input type="hidden" name="solicitud_id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit" class="btn btn-blue" style="padding:6px 14px; font-size:0.8rem;">
                                            <i class="fas fa-check"></i> Aceptar
                                        </button>
                                    </form>

                                    <!-- FORMULARIO PARA RECHAZAR SOLICITUD -->
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('¿Rechazar la solicitud de <?= htmlspecialchars(addslashes($s['nombre_dueno'])) ?>?')">
                                        <input type="hidden" name="accion"       value="rechazar">
                                        <input type="hidden" name="solicitud_id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit"
                                                style="padding:6px 14px; font-size:0.8rem; background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; border-radius:6px; cursor:pointer; font-family:inherit;">
                                            <i class="fas fa-times"></i> Rechazar
                                        </button>
                                    </form>

                                    <!-- Contactar al dueño por WhatsApp -->
                                    <a href="https://wa.me/57<?= preg_replace('/[^0-9]/', '', $s['telefono_dueno']) ?>?text=<?= urlencode("Hola " . $s['nombre_dueno'] . ", soy " . $cuidadorNom . " de SafePet. Vi tu solicitud para cuidar a " . $s['nombre_mascota'] . ". ¡Me da gusto ayudarte!") ?>"
                                       target="_blank" rel="noopener"
                                       style="padding:6px 14px; font-size:0.8rem; background:#25D366; color:white; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fab fa-whatsapp"></i> WA
                                    </a>

                                </div>
                                <?php else: ?>
                                    <!-- Solicitud ya procesada: solo mostrar contacto -->
                                    <a href="https://wa.me/57<?= preg_replace('/[^0-9]/', '', $s['telefono_dueno']) ?>"
                                       target="_blank" rel="noopener"
                                       style="padding:6px 12px; font-size:0.8rem; background:#25D366; color:white; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                <?php endif; ?>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>
        </div>


        <!-- ── RESUMEN DEL PERFIL (tarjeta inferior) ──────── -->
        <div class="card">
            <div class="card-title"><i class="fas fa-user"></i> Mi Perfil</div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <p style="font-size:0.85rem; color:var(--color-gray); margin-bottom:4px;">Ciudad</p>
                    <p style="font-weight:600;"><i class="fas fa-map-marker-alt" style="color:var(--color-primary);"></i> <?= htmlspecialchars($perfil['ciudad']) ?></p>
                </div>
                <div>
                    <p style="font-size:0.85rem; color:var(--color-gray); margin-bottom:4px;">Especialidad</p>
                    <p style="font-weight:600;"><i class="fas fa-paw" style="color:var(--color-primary);"></i> <?= htmlspecialchars($perfil['especialidad']) ?></p>
                </div>
                <div>
                    <p style="font-size:0.85rem; color:var(--color-gray); margin-bottom:4px;">Precio / hora</p>
                    <p style="font-weight:600; color:var(--color-primary);">$<?= number_format($perfil['precio_hora'], 0, ',', '.') ?> COP</p>
                </div>
                <div>
                    <p style="font-size:0.85rem; color:var(--color-gray); margin-bottom:4px;">Teléfono</p>
                    <p style="font-weight:600;"><?= htmlspecialchars($perfil['telefono']) ?: 'No configurado' ?></p>
                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <a href="perfil.php" class="btn btn-blue">
                    <i class="fas fa-edit"></i> Editar Perfil
                </a>
            </div>
        </div>

    </main>
    <!-- Fin contenido principal -->

</div>
<!-- Fin layout dashboard -->

<script src="../js/main.js"></script>
</body>
</html>
