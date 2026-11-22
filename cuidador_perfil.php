<?php
/**
 * ================================================================
 * ARCHIVO: cuidador_perfil.php
 * PROPÓSITO: Mostrar el perfil público detallado de un cuidador
 * y permitir enviar una solicitud de cuidado desde esta vista.
 * ================================================================
 */

$pageTitulo = "Perfil de Cuidador | SafePet";

require_once 'config/db.php';

// ── Función: leer datos del formulario de solicitud ─────────────
// Así el bloque principal queda más corto y fácil de presentar.
function leerSolicitudDesdePost(array $post): array
{
    return [
        'nombre_dueno'   => trim(htmlspecialchars($post['nombre_dueno'] ?? '')),
        'email_dueno'    => trim($post['email_dueno'] ?? ''),
        'telefono_dueno' => trim(htmlspecialchars($post['telefono_dueno'] ?? '')),
        'tipo_mascota'   => trim(htmlspecialchars($post['tipo_mascota'] ?? '')),
        'nombre_mascota' => trim(htmlspecialchars($post['nombre_mascota'] ?? '')),
        'fecha_inicio'   => trim($post['fecha_inicio'] ?? ''),
        'fecha_fin'      => trim($post['fecha_fin'] ?? ''),
        'mensaje'        => trim(htmlspecialchars($post['mensaje'] ?? '')),
    ];
}

// ── Función: validar datos de solicitud ─────────────────────────
function validarSolicitud(array $datos): array
{
    $errores = [];

    if (strlen($datos['nombre_dueno']) < 3) {
        $errores[] = 'El nombre del dueno debe tener al menos 3 caracteres.';
    }
    if (!filter_var($datos['email_dueno'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo valido.';
    }
    if ($datos['telefono_dueno'] === '') {
        $errores[] = 'El telefono es obligatorio.';
    }
    if ($datos['tipo_mascota'] === '' || $datos['nombre_mascota'] === '') {
        $errores[] = 'Tipo y nombre de mascota son obligatorios.';
    }
    if ($datos['fecha_inicio'] === '' || $datos['fecha_fin'] === '') {
        $errores[] = 'Debes seleccionar fecha de inicio y fin.';
    }

    return $errores;
}

// ── Función: guardar solicitud en base de datos ─────────────────
function guardarSolicitud(PDO $pdo, int $cuidadorId, array $datos): void
{
    $stmtInsert = $pdo->prepare("INSERT INTO solicitudes
        (cuidador_id, nombre_dueno, email_dueno, telefono_dueno, tipo_mascota, nombre_mascota, fecha_inicio, fecha_fin, mensaje, estado)
        VALUES
        (:cuidador_id, :nombre_dueno, :email_dueno, :telefono_dueno, :tipo_mascota, :nombre_mascota, :fecha_inicio, :fecha_fin, :mensaje, 'pendiente')");

    $stmtInsert->execute([
        ':cuidador_id'    => $cuidadorId,
        ':nombre_dueno'   => $datos['nombre_dueno'],
        ':email_dueno'    => $datos['email_dueno'],
        ':telefono_dueno' => $datos['telefono_dueno'],
        ':tipo_mascota'   => $datos['tipo_mascota'],
        ':nombre_mascota' => $datos['nombre_mascota'],
        ':fecha_inicio'   => $datos['fecha_inicio'],
        ':fecha_fin'      => $datos['fecha_fin'],
        ':mensaje'        => $datos['mensaje'],
    ]);
}

$cuidadorId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($cuidadorId <= 0) {
    header('Location: cuidadores.php');
    exit;
}

$cuidador = null;
$mensajeEstado = ['tipo' => '', 'texto' => ''];

try {
    $pdo = getPDO();

    // Cargar perfil del cuidador seleccionado
    $stmt = $pdo->prepare("SELECT id, nombre, email, telefono, ciudad, especialidad, descripcion, precio_hora, foto, rating, disponible
                           FROM cuidadores
                           WHERE id = :id AND activo = 1
                           LIMIT 1");
    $stmt->execute([':id' => $cuidadorId]);
    $cuidador = $stmt->fetch();

    // Procesar solicitud si llega POST
    if ($cuidador && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1) leer datos, 2) validar, 3) guardar.
        $datosSolicitud = leerSolicitudDesdePost($_POST);
        $errores = validarSolicitud($datosSolicitud);

        if (empty($errores)) {
            guardarSolicitud($pdo, $cuidadorId, $datosSolicitud);

            $mensajeEstado = ['tipo' => 'success', 'texto' => 'Solicitud enviada con éxito. El cuidador la verá en su panel.'];
        } else {
            $mensajeEstado = ['tipo' => 'danger', 'texto' => implode(' ', $errores)];
        }
    }
} catch (Exception $e) {
    error_log('Error perfil cuidador: ' . $e->getMessage());
}

include 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><i class="fas fa-user"></i> Perfil del Cuidador</h1>
        <p>Conoce la experiencia y disponibilidad antes de reservar</p>
        <div class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span>›</span>
            <a href="cuidadores.php">Cuidadores</a>
            <span>›</span>
            <span>Perfil</span>
        </div>
    </div>
</section>

<section class="section section-light">
    <div class="container">
        <?php if (!$cuidador): ?>
            <div class="card" style="text-align:center;">
                <p>No encontramos el cuidador solicitado.</p>
                <a href="cuidadores.php" class="btn btn-blue"><i class="fas fa-arrow-left"></i> Volver a Cuidadores</a>
            </div>
        <?php else: ?>

            <?php if (!empty($mensajeEstado['texto'])): ?>
            <div class="alert alert-<?= htmlspecialchars($mensajeEstado['tipo']) ?>">
                <i class="fas fa-info-circle"></i>
                <?= htmlspecialchars($mensajeEstado['texto']) ?>
            </div>
            <?php endif; ?>

            <div class="grid-2" style="align-items:start;">
                <div class="card">
                    <div style="display:flex; gap:1rem; align-items:center; margin-bottom:1rem;">
                        <div style="width:110px; height:110px; border-radius:50%; overflow:hidden; background:var(--color-lighter-blue); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <?php if (!empty($cuidador['foto'])): ?>
                                <img src="<?= htmlspecialchars($cuidador['foto']) ?>" alt="<?= htmlspecialchars($cuidador['nombre']) ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size:4rem;color:var(--color-primary);"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 style="margin:0;color:var(--color-primary);"><?= htmlspecialchars($cuidador['nombre']) ?></h2>
                            <p style="margin:0;color:var(--color-gray);"><?= htmlspecialchars($cuidador['especialidad']) ?></p>
                            <p class="cuidador-rating" style="margin:4px 0 0;"><i class="fas fa-star"></i> <?= number_format((float)$cuidador['rating'], 1) ?> / 5.0</p>
                        </div>
                    </div>

                    <p><strong>Ciudad:</strong> <?= htmlspecialchars($cuidador['ciudad']) ?></p>
                    <p><strong>Precio por hora:</strong> $<?= number_format((float)$cuidador['precio_hora'], 0, ',', '.') ?> COP</p>
                    <p><strong>Estado:</strong>
                        <?php if ((int)$cuidador['disponible'] === 1): ?>
                            <span class="badge-disponible"><i class="fas fa-check-circle"></i> Disponible</span>
                        <?php else: ?>
                            <span class="badge-ocupado"><i class="fas fa-clock"></i> Ocupado</span>
                        <?php endif; ?>
                    </p>

                    <h3 style="margin-top:1rem;color:var(--color-primary);">Descripción</h3>
                    <p style="color:var(--color-gray);"><?= !empty($cuidador['descripcion']) ? nl2br(htmlspecialchars($cuidador['descripcion'])) : 'Este cuidador aún no ha agregado descripción.' ?></p>

                    <a href="https://wa.me/573242956121?text=<?= urlencode('Hola, me interesa el servicio de ' . $cuidador['nombre'] . ' en SafePet.') ?>"
                       class="btn-buy"
                       target="_blank"
                       rel="noopener"
                       style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;text-decoration:none;">
                        <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                    </a>
                </div>

                <div class="card">
                    <h3 class="card-title"><i class="fas fa-calendar-check"></i> Enviar solicitud de cuidado</h3>
                    <form method="POST" action="cuidador_perfil.php?id=<?= (int)$cuidador['id'] ?>">
                        <div class="grid-2" style="gap:1rem;">
                            <div class="form-group">
                                <label>Nombre del dueño *</label>
                                <input type="text" name="nombre_dueno" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Correo *</label>
                                <input type="email" name="email_dueno" class="form-control" required>
                            </div>
                        </div>

                        <div class="grid-2" style="gap:1rem;">
                            <div class="form-group">
                                <label>Teléfono *</label>
                                <input type="text" name="telefono_dueno" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Tipo de mascota *</label>
                                <select name="tipo_mascota" class="form-control" required>
                                    <option value="">Selecciona</option>
                                    <option value="Perro">Perro</option>
                                    <option value="Gato">Gato</option>
                                    <option value="Ave">Ave</option>
                                    <option value="Otra">Otra</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid-2" style="gap:1rem;">
                            <div class="form-group">
                                <label>Nombre de la mascota *</label>
                                <input type="text" name="nombre_mascota" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Mensaje</label>
                                <input type="text" name="mensaje" class="form-control" placeholder="Necesito cuidado por viaje, etc.">
                            </div>
                        </div>

                        <div class="grid-2" style="gap:1rem;">
                            <div class="form-group">
                                <label>Fecha inicio *</label>
                                <input type="date" name="fecha_inicio" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha fin *</label>
                                <input type="date" name="fecha_fin" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center;">
                            <i class="fas fa-paper-plane"></i> Enviar solicitud
                        </button>
                    </form>
                </div>
            </div>

            <div style="margin-top:1.2rem;">
                <a href="cuidadores.php" class="btn" style="background:#f5f5f5;color:#555;">
                    <i class="fas fa-arrow-left"></i> Volver a Cuidadores
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
