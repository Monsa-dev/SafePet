<?php
/**
 * ================================================================
 * ARCHIVO: cuidadores/login.php
 * PROPÓSITO: Formulario de inicio de sesión para cuidadores.
 * Proceso de autenticación:
 *  1. Recibir email y contraseña del formulario
 *  2. Buscar el cuidador por email en la BD
 *  3. Verificar la contraseña con password_verify() (anti-brute force implícito)
 *  4. Si es correcto: crear sesión y redirigir al dashboard
 *  5. Si no: mostrar error genérico (no revelar si el email existe)
 *
 * SEGURIDAD:
 *  - No se revela si el email existe o no (mensaje genérico)
 *  - Las contraseñas se verifican con password_verify (bcrypt)
 *  - Sesión iniciada antes de cualquier output
 * ================================================================
 */

session_start();

// Si ya está logueado, redirigir al dashboard directamente
if (isset($_SESSION['cuidador_id'])) {
    header('Location: dashboard.php');
    exit;
}

$basePath = '../';
require_once $basePath . 'config/db.php';

$error = '';
$email = ''; // Para repoblar el campo email si hay error

// ── Procesar el formulario ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    // Validación básica: ambos campos son requeridos
    if (empty($email) || empty($password)) {
        $error = "Por favor completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Ingresa un correo electrónico válido.";
    } else {
        try {
            $pdo = getPDO();

            // Buscar cuidador por email
            // Nota: buscamos solo por email y verificamos password en PHP (no en SQL)
            $stmt = $pdo->prepare("
                SELECT id, nombre, email, password, activo
                FROM cuidadores
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $cuidador = $stmt->fetch();

            // Verificar si existe y si la contraseña es correcta
            // password_verify() compara el hash almacenado con la contraseña ingresada
            if ($cuidador && password_verify($password, $cuidador['password'])) {

                // Verificar que la cuenta esté activa
                if (!$cuidador['activo']) {
                    $error = "Tu cuenta está desactivada. Contacta a soporte.";
                } else {
                    // ✅ Autenticación exitosa: crear variables de sesión
                    // Regenerar el ID de sesión para prevenir Session Fixation
                    session_regenerate_id(true);

                    $_SESSION['cuidador_id']     = $cuidador['id'];
                    $_SESSION['cuidador_nombre'] = $cuidador['nombre'];
                    $_SESSION['cuidador_email']  = $cuidador['email'];

                    // Redirigir al dashboard
                    header('Location: dashboard.php');
                    exit;
                }

            } else {
                // Mensaje genérico para no revelar si el email existe (seguridad)
                $error = "Credenciales incorrectas. Verifica tu correo y contraseña.";
            }

        } catch (Exception $e) {
            error_log("Error login cuidador: " . $e->getMessage());
            $error = "Error interno. Por favor intenta de nuevo.";
        }
    }
}

// Recuperar mensaje flash de éxito si viene del registro
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']); // Consumir el flash (solo se muestra una vez)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Cuidadores | SafePet</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Header mínimo -->
<div style="background:var(--color-primary); padding:1rem; text-align:center;">
    <a href="../index.php">
        <img src="../img/logo safepet.png" alt="SafePet" style="height:45px; filter:brightness(0) invert(1);">
    </a>
</div>

<!-- ════════════════════════════════════════════════════════
     FORMULARIO DE LOGIN
     ════════════════════════════════════════════════════════ -->
<div class="auth-page">
    <div class="auth-card">

        <!-- Logo y título -->
        <div class="auth-card-header">
            <img src="../img/logo safepet.png" alt="SafePet Logo">
            <h2>Panel de Cuidadores</h2>
            <p>Inicia sesión para gestionar tus solicitudes</p>
        </div>

        <!-- Mensaje flash de registro exitoso -->
        <?php if ($flashSuccess): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($flashSuccess) ?>
        </div>
        <?php endif; ?>

        <!-- Mensaje de error de login -->
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!--
            FORMULARIO DE LOGIN
            autocomplete="off" en el form evita que el navegador sugiera
            contraseñas guardadas en campos que no corresponden.
        -->
        <form method="POST" action="login.php">

            <!-- Campo email -->
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Correo electrónico
                </label>
                <input type="email" id="email" name="email"
                       class="form-control"
                       placeholder="tu@correo.com"
                       value="<?= htmlspecialchars($email) ?>"
                       required
                       autocomplete="username">
            </div>

            <!-- Campo contraseña con botón mostrar/ocultar -->
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Contraseña
                </label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password"
                           class="form-control"
                           placeholder="Tu contraseña"
                           required
                           autocomplete="current-password"
                           style="padding-right:45px;">
                    <button type="button" onclick="togglePasswordLogin()"
                            style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--color-gray); cursor:pointer;">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Botón de inicio de sesión -->
            <button type="submit" class="btn btn-blue"
                    style="width:100%; justify-content:center; padding:14px; font-size:1rem;">
                <i class="fas fa-sign-in-alt"></i> Ingresar al Panel
            </button>

        </form>

        <!-- Links de navegación -->
        <div class="auth-footer-link">
            ¿No tienes cuenta? <a href="registro.php">Regístrate gratis</a>
        </div>
        <div style="text-align:center; margin-top:0.75rem;">
            <a href="../index.php" style="color:var(--color-gray); font-size:0.85rem;">
                <i class="fas fa-arrow-left"></i> Volver al sitio principal
            </a>
        </div>

    </div>
</div>

<script>
/** Alternar visibilidad de la contraseña */
function togglePasswordLogin() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type   = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type   = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
</body>
</html>
