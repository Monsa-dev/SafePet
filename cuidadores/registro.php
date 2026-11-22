<?php
/**
 * ================================================================
 * ARCHIVO: cuidadores/registro.php
 * PROPÓSITO: Formulario de registro para nuevos cuidadores.
 * Proceso:
 *  1. Mostrar formulario
 *  2. Validar todos los campos (servidor)
 *  3. Verificar que el email no esté ya registrado
 *  4. Hashear la contraseña con password_hash() (seguro, bcrypt)
 *  5. Insertar en la BD y redirigir al dashboard
 * ================================================================
 */

// session_start() debe ser lo primero antes de cualquier output
session_start();

// Si ya está logueado, no tiene sentido ver el registro → redirigir
if (isset($_SESSION['cuidador_id'])) {
    header('Location: dashboard.php');
    exit;
}

// basePath: cómo llegar a la raíz desde esta subcarpeta
$basePath = '../';
require_once $basePath . 'config/db.php';

$errores = [];
$datos   = []; // Para repoblar el formulario si hay errores

// ── Procesar formulario al enviarlo ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitizar y recoger campos del formulario
    $nombre      = trim(htmlspecialchars($_POST['nombre']      ?? ''));
    $email       = trim($_POST['email']                         ?? '');
    $password    = $_POST['password']                           ?? '';
    $password2   = $_POST['password2']                          ?? '';
    $telefono    = trim(htmlspecialchars($_POST['telefono']    ?? ''));
    $ciudad      = trim(htmlspecialchars($_POST['ciudad']      ?? ''));
    $especialidad= trim(htmlspecialchars($_POST['especialidad']?? ''));
    $descripcion = trim(htmlspecialchars($_POST['descripcion'] ?? ''));
    $precio_hora = (int)($_POST['precio_hora'] ?? 0);

    // Guardar para repoblar fields si hay error
    $datos = compact('nombre', 'email', 'telefono', 'ciudad', 'especialidad', 'descripcion', 'precio_hora');

    // ── VALIDACIONES ────────────────────────────────────────────

    // Nombre: requerido, mínimo 3 chars
    if (strlen($nombre) < 3) {
        $errores[] = "El nombre debe tener al menos 3 caracteres.";
    }

    // Email: formato válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Ingresa un correo electrónico válido.";
    }

    // Contraseña: mínimo 8 caracteres con al menos una letra y un número
    if (strlen($password) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errores[] = "La contraseña debe incluir letras y números.";
    }

    // Confirmar contraseña
    if ($password !== $password2) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    // Teléfono: requerido
    if (empty($telefono)) {
        $errores[] = "El teléfono es obligatorio.";
    }

    // Ciudad: requerida
    if (empty($ciudad)) {
        $errores[] = "La ciudad es obligatoria.";
    }

    // Especialidad: requerida
    if (empty($especialidad)) {
        $errores[] = "Elige al menos una especialidad.";
    }

    // Precio/hora: debe ser positivo
    if ($precio_hora <= 0) {
        $errores[] = "El precio por hora debe ser mayor a 0.";
    }

    // ── Solo si no hay errores, hacer INSERT ─────────────────────
    if (empty($errores)) {
        try {
            $pdo = getPDO();

            // Verificar si el email ya está registrado (evitar duplicados)
            $stmtCheck = $pdo->prepare("SELECT id FROM cuidadores WHERE email = :email LIMIT 1");
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                $errores[] = "Este correo electrónico ya está registrado. ¿Quieres <a href='login.php'>iniciar sesión</a>?";
            } else {
                // password_hash() con bcrypt: NUNCA guardar contraseñas en texto plano
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                // Insertar el nuevo cuidador en la BD
                $stmtInsert = $pdo->prepare("
                    INSERT INTO cuidadores
                        (nombre, email, password, telefono, ciudad, especialidad, descripcion, precio_hora, activo, disponible, rating)
                    VALUES
                        (:nombre, :email, :password, :telefono, :ciudad, :especialidad, :descripcion, :precio_hora, 1, 1, 0.0)
                ");

                $stmtInsert->execute([
                    ':nombre'      => $nombre,
                    ':email'       => $email,
                    ':password'    => $passwordHash,
                    ':telefono'    => $telefono,
                    ':ciudad'      => $ciudad,
                    ':especialidad'=> $especialidad,
                    ':descripcion' => $descripcion,
                    ':precio_hora' => $precio_hora,
                ]);

                // Iniciar sesión automáticamente tras el registro
                $nuevoCuidadorId = $pdo->lastInsertId();
                $_SESSION['cuidador_id']     = $nuevoCuidadorId;
                $_SESSION['cuidador_nombre'] = $nombre;
                $_SESSION['cuidador_email']  = $email;

                // Redirigir al dashboard con mensaje de bienvenida
                $_SESSION['flash_success'] = "¡Registro exitoso! Bienvenido a SafePet, $nombre.";
                header('Location: dashboard.php');
                exit;
            }

        } catch (Exception $e) {
            error_log("Error registro cuidador: " . $e->getMessage());
            $errores[] = "Error al registrar. Por favor intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cuidador | SafePet</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Header mínimo para páginas de autenticación -->
<div style="background:var(--color-primary); padding:1rem; text-align:center;">
    <a href="../index.php">
        <img src="../img/logo safepet.png" alt="SafePet" style="height:45px; filter:brightness(0) invert(1);">
    </a>
</div>

<!-- ════════════════════════════════════════════════════════
     FORMULARIO DE REGISTRO
     ════════════════════════════════════════════════════════ -->
<div class="auth-page">
    <div style="background:white; border-radius:20px; box-shadow:0 10px 40px rgba(21,101,192,0.15); padding:2.5rem; width:100%; max-width:650px;">

        <!-- Cabecera del card de registro -->
        <div class="auth-card-header" style="text-align:center; margin-bottom:2rem;">
            <img src="../img/logo safepet.png" alt="SafePet" style="width:90px; margin:0 auto 1rem;">
            <h2 style="color:var(--color-primary); font-size:1.7rem; font-weight:700;">Registro de Cuidador</h2>
            <p style="color:var(--color-gray);">Únete gratis y empieza a recibir solicitudes</p>
        </div>

        <!-- Errores del formulario -->
        <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <?php foreach ($errores as $e): ?>
                <p style="margin:2px 0;"><?= $e ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!--
            FORMULARIO DE REGISTRO
            Todos los campos son requeridos excepto la descripción.
            Se usa autocomplete para ayudar al navegador a rellenar datos.
        -->
        <form method="POST" action="registro.php" novalidate>

            <!-- Nombre y teléfono en una fila -->
            <div class="grid-2" style="gap:1rem;">
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre completo *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control"
                           placeholder="Tu nombre y apellido"
                           value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>"
                           required autocomplete="name" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="telefono"><i class="fas fa-phone"></i> Teléfono / WhatsApp *</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control"
                           placeholder="Ej: 573001234567"
                           value="<?= htmlspecialchars($datos['telefono'] ?? '') ?>"
                           required autocomplete="tel" maxlength="20">
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Correo electrónico *</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="tu@correo.com"
                       value="<?= htmlspecialchars($datos['email'] ?? '') ?>"
                       required autocomplete="email" maxlength="150">
            </div>

            <!-- Contraseñas -->
            <div class="grid-2" style="gap:1rem;">
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña *</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Mínimo 8 caracteres"
                               required autocomplete="new-password"
                               style="padding-right:45px;">
                        <!-- Botón para mostrar/ocultar contraseña -->
                        <button type="button" onclick="togglePassword('password')"
                                style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--color-gray); cursor:pointer;">
                            <i class="fas fa-eye" id="eyePassword"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password2"><i class="fas fa-lock"></i> Confirmar contraseña *</label>
                    <div style="position:relative;">
                        <input type="password" id="password2" name="password2" class="form-control"
                               placeholder="Repite la contraseña"
                               required autocomplete="new-password"
                               style="padding-right:45px;">
                        <button type="button" onclick="togglePassword('password2')"
                                style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--color-gray); cursor:pointer;">
                            <i class="fas fa-eye" id="eyePassword2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Ciudad y especialidad -->
            <div class="grid-2" style="gap:1rem;">
                <div class="form-group">
                    <label for="ciudad"><i class="fas fa-map-marker-alt"></i> Ciudad *</label>
                    <select id="ciudad" name="ciudad" class="form-control" required>
                        <option value="">Selecciona tu ciudad</option>
                        <?php
                        // Lista de ciudades principales de Colombia
                        $ciudades = ['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Cartagena',
                                     'Bucaramanga', 'Manizales', 'Pereira', 'Ibagué', 'Otra'];
                        foreach ($ciudades as $ciu):
                            $selected = (($datos['ciudad'] ?? '') === $ciu) ? 'selected' : '';
                        ?>
                        <option value="<?= $ciu ?>" <?= $selected ?>><?= $ciu ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="especialidad"><i class="fas fa-paw"></i> Especialidad *</label>
                    <select id="especialidad" name="especialidad" class="form-control" required>
                        <option value="">¿Con qué mascotas trabajas?</option>
                        <option value="Perros y Gatos"    <?= (($datos['especialidad'] ?? '') === 'Perros y Gatos')    ? 'selected' : '' ?>>Perros y Gatos</option>
                        <option value="Solo Perros"       <?= (($datos['especialidad'] ?? '') === 'Solo Perros')       ? 'selected' : '' ?>>Solo Perros</option>
                        <option value="Solo Gatos"        <?= (($datos['especialidad'] ?? '') === 'Solo Gatos')        ? 'selected' : '' ?>>Solo Gatos</option>
                        <option value="Perros grandes"    <?= (($datos['especialidad'] ?? '') === 'Perros grandes')    ? 'selected' : '' ?>>Perros grandes</option>
                        <option value="Perros pequeños"   <?= (($datos['especialidad'] ?? '') === 'Perros pequeños')   ? 'selected' : '' ?>>Perros pequeños</option>
                        <option value="Mascotas exóticas" <?= (($datos['especialidad'] ?? '') === 'Mascotas exóticas') ? 'selected' : '' ?>>Mascotas exóticas</option>
                        <option value="Todo tipo"         <?= (($datos['especialidad'] ?? '') === 'Todo tipo')         ? 'selected' : '' ?>>Todo tipo de mascotas</option>
                    </select>
                </div>
            </div>

            <!-- Precio por hora -->
            <div class="form-group">
                <label for="precio_hora"><i class="fas fa-tag"></i> Precio por hora (COP) *</label>
                <input type="number" id="precio_hora" name="precio_hora" class="form-control"
                       placeholder="Ej: 25000"
                       value="<?= htmlspecialchars((string)($datos['precio_hora'] ?? '')) ?>"
                       min="5000" max="500000" required>
                <small style="color:var(--color-gray); font-size:0.8rem;">Precio mínimo recomendado: $15.000 COP</small>
            </div>

            <!-- Descripción del perfil -->
            <div class="form-group">
                <label for="descripcion"><i class="fas fa-align-left"></i> Descripción del perfil</label>
                <textarea id="descripcion" name="descripcion" class="form-control"
                          placeholder="Cuéntales a los dueños sobre tu experiencia con mascotas..."
                          maxlength="500"><?= htmlspecialchars($datos['descripcion'] ?? '') ?></textarea>
                <small style="color:var(--color-gray); font-size:0.8rem;">Opcional. Un buen perfil atrae más clientes.</small>
            </div>

            <!-- Botón de registro -->
            <button type="submit" class="btn btn-blue" style="width:100%; justify-content:center; padding:14px; font-size:1rem; margin-top:0.5rem;">
                <i class="fas fa-user-plus"></i> Crear mi Cuenta de Cuidador
            </button>

        </form>

        <!-- Link para ir al login si ya tiene cuenta -->
        <div class="auth-footer-link" style="text-align:center; margin-top:1.5rem;">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>
        <div style="text-align:center; margin-top:0.75rem;">
            <a href="../index.php" style="color:var(--color-gray); font-size:0.85rem;">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
        </div>

    </div>
</div>

<script>
/**
 * togglePassword()
 * Alterna la visibilidad del campo contraseña entre texto y password.
 * @param {string} fieldId - ID del input de la contraseña
 */
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye   = document.getElementById('eye' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1));

    if (field.type === 'password') {
        field.type = 'text';
        if (eye) eye.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        if (eye) eye.className = 'fas fa-eye';
    }
}

/**
 * Validación en tiempo real de que las contraseñas coincidan.
 */
document.getElementById('password2')?.addEventListener('input', function () {
    const p1 = document.getElementById('password').value;
    const p2 = this.value;
    if (p2 && p1 !== p2) {
        this.style.borderColor = '#E53935';
    } else {
        this.style.borderColor = p2 ? '#43A047' : '#e0e0e0';
    }
});
</script>
</body>
</html>
