<?php
/**
 * ================================================================
 * ARCHIVO: cuidadores/perfil.php
 * PROPÓSITO: Permite al cuidador editar su perfil público.
 * Campos editables: nombre, teléfono, ciudad, especialidad,
 *                   descripción, precio por hora.
 * ================================================================
 */

session_start();

if (!isset($_SESSION['cuidador_id'])) {
    header('Location: login.php');
    exit;
}

$basePath   = '../';
$cuidadorId = (int)$_SESSION['cuidador_id'];
require_once $basePath . 'config/db.php';

$exito  = '';
$errores = [];

// Cargar foto actual para poder reemplazarla si el usuario sube una nueva
$fotoActual = null;
try {
    $pdoFoto = getPDO();
    $stmtFoto = $pdoFoto->prepare("SELECT foto FROM cuidadores WHERE id = :id");
    $stmtFoto->execute([':id' => $cuidadorId]);
    $fotoActual = $stmtFoto->fetchColumn();
} catch (Exception $e) {
    $fotoActual = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim(htmlspecialchars($_POST['nombre']       ?? ''));
    $telefono    = trim(htmlspecialchars($_POST['telefono']     ?? ''));
    $ciudad      = trim(htmlspecialchars($_POST['ciudad']       ?? ''));
    $especialidad= trim(htmlspecialchars($_POST['especialidad'] ?? ''));
    $descripcion = trim(htmlspecialchars($_POST['descripcion']  ?? ''));
    $precio_hora = (int)($_POST['precio_hora'] ?? 0);

    $nuevaRutaFoto = null;

    if (strlen($nombre) < 3)  $errores[] = "El nombre es demasiado corto.";
    if (empty($telefono))      $errores[] = "El teléfono es obligatorio.";
    if (empty($ciudad))        $errores[] = "La ciudad es obligatoria.";
    if ($precio_hora <= 0)     $errores[] = "El precio por hora debe ser mayor a 0.";

    // Validar y procesar foto de perfil (si fue enviada)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errores[] = "No se pudo subir la foto. Intenta de nuevo.";
        } else {
            $tmpName = $_FILES['foto']['tmp_name'];
            $tamano  = (int)$_FILES['foto']['size'];

            // Límite: 2MB
            if ($tamano > 2 * 1024 * 1024) {
                $errores[] = "La foto debe pesar máximo 2MB.";
            } else {
                $mime = mime_content_type($tmpName);
                $permitidos = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif',
                ];

                if (!isset($permitidos[$mime])) {
                    $errores[] = "Formato no permitido. Usa JPG, PNG, WEBP o GIF.";
                } else {
                    $directorioRelativo = 'img/cuidadores';
                    $directorioAbsoluto = __DIR__ . '/../' . $directorioRelativo;

                    if (!is_dir($directorioAbsoluto) && !mkdir($directorioAbsoluto, 0775, true)) {
                        $errores[] = "No se pudo crear la carpeta de fotos.";
                    } else {
                        $ext = $permitidos[$mime];
                        $nombreArchivo = 'cuidador_' . $cuidadorId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $rutaDestinoAbsoluta = $directorioAbsoluto . '/' . $nombreArchivo;
                        $nuevaRutaFoto = $directorioRelativo . '/' . $nombreArchivo;

                        if (!move_uploaded_file($tmpName, $rutaDestinoAbsoluta)) {
                            $errores[] = "No fue posible guardar la foto en el servidor.";
                            $nuevaRutaFoto = null;
                        }
                    }
                }
            }
        }
    }

    if (empty($errores)) {
        try {
            $pdo  = getPDO();
            if ($nuevaRutaFoto !== null) {
                $stmt = $pdo->prepare("
                    UPDATE cuidadores
                    SET nombre = :nombre, telefono = :telefono, ciudad = :ciudad,
                        especialidad = :especialidad, descripcion = :descripcion,
                        precio_hora = :precio_hora, foto = :foto
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre'      => $nombre,
                    ':telefono'    => $telefono,
                    ':ciudad'      => $ciudad,
                    ':especialidad'=> $especialidad,
                    ':descripcion' => $descripcion,
                    ':precio_hora' => $precio_hora,
                    ':foto'        => $nuevaRutaFoto,
                    ':id'          => $cuidadorId,
                ]);

                // Limpiar foto anterior para no acumular archivos huérfanos
                if (!empty($fotoActual)) {
                    $rutaAnteriorAbsoluta = __DIR__ . '/../' . $fotoActual;
                    if (is_file($rutaAnteriorAbsoluta)) {
                        @unlink($rutaAnteriorAbsoluta);
                    }
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE cuidadores
                    SET nombre = :nombre, telefono = :telefono, ciudad = :ciudad,
                        especialidad = :especialidad, descripcion = :descripcion,
                        precio_hora = :precio_hora
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre'      => $nombre,
                    ':telefono'    => $telefono,
                    ':ciudad'      => $ciudad,
                    ':especialidad'=> $especialidad,
                    ':descripcion' => $descripcion,
                    ':precio_hora' => $precio_hora,
                    ':id'          => $cuidadorId,
                ]);
            }

            // Actualizar el nombre en sesión si cambió
            $_SESSION['cuidador_nombre'] = $nombre;
            $exito = "¡Perfil actualizado correctamente!";

        } catch (Exception $e) {
            $errores[] = "Error al actualizar. Inténtalo de nuevo.";
        }
    }
}

// Cargar datos actuales del perfil
try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM cuidadores WHERE id = :id");
    $stmt->execute([':id' => $cuidadorId]);
    $perfil = $stmt->fetch();
} catch (Exception $e) {
    $perfil = [
        'nombre' => $_SESSION['cuidador_nombre'], 'telefono' => '',
        'ciudad' => '', 'especialidad' => '', 'descripcion' => '', 'precio_hora' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | SafePet</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="dashboard-layout">

    <!-- Sidebar (igual al dashboard) -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../img/logo safepet.png" alt="SafePet" style="filter:brightness(0) invert(1);">
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Mi Panel</a>
            <a href="dashboard.php#solicitudes"><i class="fas fa-bell"></i> Solicitudes</a>
            <a href="perfil.php" class="active"><i class="fas fa-user-edit"></i> Mi Perfil</a>
            <a href="../index.php"><i class="fas fa-globe"></i> Ver Sitio</a>
            <a href="logout.php" style="color:rgba(255,100,100,0.9);"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="dashboard-content">
        <div style="max-width:650px;">
            <h2 style="color:var(--color-primary); margin-bottom:1.5rem;">
                <i class="fas fa-user-edit"></i> Editar Perfil
            </h2>

            <?php if ($exito): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($exito) ?></div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php foreach ($errores as $e): ?><p><?= $e ?></p><?php endforeach; ?></div>
            </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST" action="perfil.php" enctype="multipart/form-data">

                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Foto de perfil</label>
                        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:0.6rem;">
                            <div style="width:72px; height:72px; border-radius:50%; overflow:hidden; background:var(--color-lighter-blue); display:flex; align-items:center; justify-content:center;">
                                <?php if (!empty($perfil['foto'])): ?>
                                    <img src="../<?= htmlspecialchars($perfil['foto']) ?>" alt="Foto de perfil" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle" style="font-size:2.8rem;color:var(--color-primary);"></i>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:0.85rem; color:var(--color-gray);">
                                Sube una imagen JPG, PNG, WEBP o GIF (máximo 2MB). Al seleccionarla podrás ajustarla para ver qué parte queda en el círculo.
                            </div>
                        </div>
                        <input type="file" id="fotoInput" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nombre completo *</label>
                        <input type="text" name="nombre" class="form-control"
                               value="<?= htmlspecialchars($perfil['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="grid-2" style="gap:1rem;">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Teléfono *</label>
                            <input type="tel" name="telefono" class="form-control"
                                   value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Ciudad *</label>
                            <input type="text" name="ciudad" class="form-control"
                                   value="<?= htmlspecialchars($perfil['ciudad'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="grid-2" style="gap:1rem;">
                        <div class="form-group">
                            <label><i class="fas fa-paw"></i> Especialidad *</label>
                            <select name="especialidad" class="form-control">
                                <?php
                                $especialidades = ['Perros y Gatos','Solo Perros','Solo Gatos','Perros grandes','Perros pequeños','Mascotas exóticas','Todo tipo'];
                                foreach ($especialidades as $esp):
                                    $sel = ($perfil['especialidad'] === $esp) ? 'selected' : '';
                                ?>
                                <option value="<?= $esp ?>" <?= $sel ?>><?= $esp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Precio/hora (COP) *</label>
                            <input type="number" name="precio_hora" class="form-control"
                                   value="<?= htmlspecialchars((string)($perfil['precio_hora'] ?? '')) ?>"
                                   min="5000" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Descripción del perfil</label>
                        <textarea name="descripcion" class="form-control" maxlength="500"><?= htmlspecialchars($perfil['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex; gap:1rem;">
                        <button type="submit" class="btn btn-blue">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="dashboard.php" class="btn" style="background:#f5f5f5; color:#555;">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>

<!-- Modal de recorte de foto -->
<div id="cropModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2500; align-items:center; justify-content:center; padding:1rem;">
    <div style="width:min(920px, 100%); background:#fff; border-radius:14px; padding:1rem; box-shadow:0 12px 28px rgba(0,0,0,0.25);">
        <h3 style="margin:0 0 0.5rem; color:var(--color-primary);"><i class="fas fa-crop-alt"></i> Ajusta tu foto de perfil</h3>
        <p style="margin:0 0 0.75rem; color:var(--color-gray); font-size:0.9rem;">Mueve y haz zoom hasta que el rostro quede bien centrado.</p>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:1rem; align-items:start;">
            <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; min-height:360px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                <img id="cropImage" alt="Imagen para recortar" style="max-width:100%; display:block;">
            </div>

            <div>
                <p style="margin:0 0 0.4rem; font-weight:600; color:var(--color-primary);">Vista previa circular</p>
                <div style="width:170px; height:170px; border-radius:50%; overflow:hidden; border:3px solid var(--color-primary); background:var(--color-lighter-blue); margin-bottom:0.8rem;">
                    <img id="cropPreview" alt="Vista previa" style="width:100%; height:100%; object-fit:cover;">
                </div>

                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <button type="button" id="cropRotateLeft" class="btn" style="background:#f5f5f5; color:#555; justify-content:center;">
                        <i class="fas fa-undo"></i> Girar -90°
                    </button>
                    <button type="button" id="cropRotateRight" class="btn" style="background:#f5f5f5; color:#555; justify-content:center;">
                        <i class="fas fa-redo"></i> Girar +90°
                    </button>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:0.6rem; justify-content:flex-end; margin-top:1rem;">
            <button type="button" id="cropCancel" class="btn" style="background:#f5f5f5; color:#555;">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" id="cropApply" class="btn btn-blue">
                <i class="fas fa-check"></i> Usar esta foto
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function () {
    const input = document.getElementById('fotoInput');
    const modal = document.getElementById('cropModal');
    const image = document.getElementById('cropImage');
    const preview = document.getElementById('cropPreview');
    const btnApply = document.getElementById('cropApply');
    const btnCancel = document.getElementById('cropCancel');
    const btnRotateLeft = document.getElementById('cropRotateLeft');
    const btnRotateRight = document.getElementById('cropRotateRight');

    let cropper = null;
    let objectUrl = null;

    if (!input) return;

    function closeModal() {
        modal.style.display = 'none';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Selecciona un archivo de imagen válido.');
            input.value = '';
            return;
        }

        objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        preview.src = objectUrl;
        modal.style.display = 'flex';

        if (cropper) cropper.destroy();
        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            responsive: true,
            background: false,
            guides: true,
            movable: true,
            zoomable: true,
            rotatable: true,
            cropBoxResizable: true,
            cropBoxMovable: true,
            minContainerWidth: 420,
            minContainerHeight: 320,
            preview: '#cropPreview',
        });
    });

    btnRotateLeft.addEventListener('click', () => {
        if (cropper) cropper.rotate(-90);
    });

    btnRotateRight.addEventListener('click', () => {
        if (cropper) cropper.rotate(90);
    });

    btnCancel.addEventListener('click', () => {
        input.value = '';
        closeModal();
    });

    btnApply.addEventListener('click', () => {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: 600,
            height: 600,
            imageSmoothingQuality: 'high'
        });

        canvas.toBlob((blob) => {
            if (!blob) return;

            // Reemplazar el archivo original por el recortado usando DataTransfer
            const croppedFile = new File([blob], 'perfil-recortado.jpg', { type: 'image/jpeg' });
            const dt = new DataTransfer();
            dt.items.add(croppedFile);
            input.files = dt.files;

            closeModal();
        }, 'image/jpeg', 0.9);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
})();
</script>
</body>
</html>
