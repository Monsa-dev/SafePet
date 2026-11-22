<?php
/**
 * ============================================================
 * ARCHIVO: admin/login.php
 * PROPÓSITO: Inicio de sesión exclusivo para el administrador.
 * Solo el dueño del sitio tiene acceso con sus credenciales.
 * ============================================================
 */
session_start();

// Si ya está logueado, ir directo al panel
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';

$error = '';

// ── Función: validar campos mínimos del formulario ──────────────
function datosLoginValidos(string $usuario, string $password): bool
{
    return $usuario !== '' && $password !== '';
}

// ── Función: buscar administrador por usuario/email ─────────────
function buscarAdminPorUsuario(PDO $pdo, string $usuario): array|false
{
    $stmt = $pdo->prepare("SELECT id, nombre, password FROM admins WHERE usuario = :usuario LIMIT 1");
    $stmt->execute([':usuario' => $usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    // Paso 1: revisar que el formulario venga completo.
    if (!datosLoginValidos($usuario, $password)) {
        $error = 'Completa todos los campos.';
    } else {
        try {
            $pdo  = getPDO();
            $admin = buscarAdminPorUsuario($pdo, $usuario);

            // Paso 2: validar credenciales.
            if ($admin && password_verify($password, $admin['password'])) {
                // Regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);
                $_SESSION['admin_id']     = $admin['id'];
                $_SESSION['admin_nombre'] = $admin['nombre'];

                // Paso 3: guardar fecha/hora del último ingreso.
                $pdo->prepare("UPDATE admins SET ultimo_login = NOW() WHERE id = :id")
                    ->execute([':id' => $admin['id']]);

                header('Location: index.php');
                exit;
            } else {
                // Mensaje genérico: no revelar si el usuario existe
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión con la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin SafePet — Iniciar sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            margin-bottom: 12px;
        }

        .login-logo h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }

        .login-logo p {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }

        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: #0f172a;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }

        .input-wrap input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.15);
        }

        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
        }

        .btn-login:hover  { opacity: .9; }
        .btn-login:active { transform: scale(.98); }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #64748b;
        }

        .back-link a { color: #2563eb; text-decoration: none; font-weight: 500; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <div class="icon"><i class="fas fa-shield-halved"></i></div>
            <h1>Panel Administrador</h1>
            <p>SafePet — Acceso exclusivo</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fas fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="usuario">Correo electrónico</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="usuario" name="usuario"
                           value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                           placeholder="admin@safepet.com" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="Ingresa tu contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-right-to-bracket"></i> Ingresar al panel
            </button>
        </form>

        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Volver al sitio</a>
        </div>
    </div>
</body>
</html>
