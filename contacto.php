<?php
/**
 * ================================================================
 * ARCHIVO: contacto.php
 * PROPÓSITO: Página de contacto del sitio.
 * Funcionalidades:
 *  - Formulario de contacto que envía el mensaje por WhatsApp
 *  - Información de contacto directo
 *  - Mapa embebido (iframe placeholder)
 *  - Validación del lado del servidor antes de enviar
 * ================================================================
 */

$pageTitulo = "Contacto | SafePet";
require_once 'config/db.php';

// ── Estado base de la página ───────────────────────────────────
// Estas variables controlan lo que se muestra al usuario.
$mensaje_enviado = false;
$errores         = [];
$datos           = [];
$urlWhatsApp     = '';

// ── Función 1: limpiar y organizar datos del formulario ────────
// Separa esta tarea para que el bloque principal quede más corto y claro.
function leerDatosContacto(array $post): array
{
    return [
        'nombre'  => trim(htmlspecialchars($post['nombre'] ?? '')),
        'email'   => trim($post['email'] ?? ''),
        'asunto'  => trim(htmlspecialchars($post['asunto'] ?? '')),
        'mensaje' => trim(htmlspecialchars($post['mensaje'] ?? '')),
    ];
}

// ── Función 2: validar datos del formulario ────────────────────
// Devuelve una lista de errores; si está vacía, todo está bien.
function validarDatosContacto(array $datos): array
{
    $errores = [];

    if ($datos['nombre'] === '' || strlen($datos['nombre']) < 3) {
        $errores[] = "El nombre debe tener al menos 3 caracteres.";
    }

    if ($datos['email'] === '' || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Por favor ingresa un correo electrónico válido.";
    }

    if ($datos['asunto'] === '') {
        $errores[] = "El asunto es obligatorio.";
    }

    if ($datos['mensaje'] === '' || strlen($datos['mensaje']) < 10) {
        $errores[] = "El mensaje debe tener al menos 10 caracteres.";
    }

    return $errores;
}

// ── Función 3: guardar mensaje para panel de administrador ─────
// Este guardado permite que tú veas el mensaje dentro del panel admin.
function guardarMensajeContacto(PDO $pdo, array $datos): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO mensajes_contacto (nombre, email, asunto, mensaje, ip_origen, user_agent)
         VALUES (:nombre, :email, :asunto, :mensaje, :ip_origen, :user_agent)"
    );

    $stmt->execute([
        ':nombre'     => $datos['nombre'],
        ':email'      => $datos['email'],
        ':asunto'     => $datos['asunto'],
        ':mensaje'    => $datos['mensaje'],
        ':ip_origen'  => $_SERVER['REMOTE_ADDR']     ?? null,
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

// ── Función 4: armar mensaje para WhatsApp ─────────────────────
// Mantiene el formato del texto en un solo lugar.
function construirUrlWhatsApp(array $datos): string
{
    $texto  = "🐾 *Nuevo mensaje de SafePet.com*\n\n";
    $texto .= "*Nombre:* {$datos['nombre']}\n";
    $texto .= "*Email:* {$datos['email']}\n";
    $texto .= "*Asunto:* {$datos['asunto']}\n\n";
    $texto .= "*Mensaje:*\n{$datos['mensaje']}";

    return "https://wa.me/573242956121?text=" . urlencode($texto);
}

// ── Flujo principal al enviar formulario ───────────────────────
// 1) leer datos, 2) validar, 3) guardar en BD, 4) preparar WhatsApp.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos   = leerDatosContacto($_POST);
    $errores = validarDatosContacto($datos);

    if (empty($errores)) {
        try {
            guardarMensajeContacto(getPDO(), $datos);
            $urlWhatsApp     = construirUrlWhatsApp($datos);
            $mensaje_enviado = true;
        } catch (PDOException $e) {
            $errores[] = "No pudimos guardar tu mensaje en este momento. Intenta nuevamente.";
        }
    }
}

include 'includes/header.php';
?>

<!-- Banner superior -->
<section class="page-hero">
    <div class="container">
        <h1><i class="fas fa-envelope"></i> Contáctanos</h1>
        <p>Estamos aquí para ayudarte. Escríbenos cuando quieras.</p>
        <div class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span>›</span>
            <span>Contacto</span>
        </div>
    </div>
</section>


<section class="section section-light">
    <div class="container">
        <div class="contact-grid">

            <!-- ══ COLUMNA IZQUIERDA: Información de contacto ══ -->
            <div>
                <h3 style="font-size:1.4rem; color:var(--color-primary); margin-bottom:1.5rem; font-weight:700;">
                    ¿Cómo podemos ayudarte?
                </h3>

                <p style="color:var(--color-gray); margin-bottom:1.5rem; font-size:0.95rem;">
                    Escríbenos con cualquier duda sobre nuestros servicios, cuidadores o productos.
                    También puedes contactarnos directamente por WhatsApp para una respuesta inmediata.
                </p>

                <!-- Ítems de contacto -->
                <div class="contact-item animate-on-scroll">
                    <div class="icon"><i class="fab fa-whatsapp"></i></div>
                    <div>
                        <h4>WhatsApp</h4>
                        <p>+57 324 295 6121</p>
                        <a href="https://wa.me/573242956121" target="_blank" rel="noopener"
                           style="color:var(--color-primary); font-size:0.85rem; font-weight:600;">
                            Abrir chat →
                        </a>
                    </div>
                </div>

                <div class="contact-item animate-on-scroll">
                    <div class="icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <h4>Correo electrónico</h4>
                        <p>info@safepet.com</p>
                    </div>
                </div>

                <div class="contact-item animate-on-scroll">
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <h4>Horario de atención</h4>
                        <p>Lunes - Viernes: 8am - 6pm</p>
                        <p>Sábados: 9am - 1pm</p>
                    </div>
                </div>

                <div class="contact-item animate-on-scroll">
                    <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h4>Ubicación</h4>
                        <p>Colombia</p>
                        <p>Atención virtual en todo el país</p>
                    </div>
                </div>

                <!-- Redes sociales -->
                <div style="margin-top:1.5rem;">
                    <h4 style="font-weight:600; margin-bottom:0.75rem; color:var(--color-primary);">Síguenos en redes</h4>
                    <div style="display:flex; gap:12px;">
                        <a href="#" style="width:42px; height:42px; background:var(--color-primary); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; transition:all 0.3s;"
                           onmouseover="this.style.transform='translateY(-3px)'"
                           onmouseout="this.style.transform='translateY(0)'"
                           aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" style="width:42px; height:42px; background:var(--color-primary); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; transition:all 0.3s;"
                           onmouseover="this.style.transform='translateY(-3px)'"
                           onmouseout="this.style.transform='translateY(0)'"
                           aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/573242956121" target="_blank" rel="noopener"
                           style="width:42px; height:42px; background:#25D366; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; transition:all 0.3s;"
                           onmouseover="this.style.transform='translateY(-3px)'"
                           onmouseout="this.style.transform='translateY(0)'"
                           aria-label="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ══ COLUMNA DERECHA: Formulario de contacto ══ -->
            <div>
                <div class="card">
                    <h3 class="card-title">
                        <i class="fas fa-paper-plane"></i> Envíanos un mensaje
                    </h3>

                    <!-- Mostrar errores de validación si los hay -->
                    <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <?php foreach ($errores as $err): ?>
                            <p style="margin:2px 0;"><?= $err ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Mostrar confirmación de éxito con enlace a WhatsApp -->
                    <?php if ($mensaje_enviado): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>¡Mensaje recibido!</strong>
                            <p style="font-size:0.9rem;">Ya quedó guardado en el panel de administrador. Si quieres, también puedes enviarlo por WhatsApp:</p>
                            <a href="<?= $urlWhatsApp ?>" target="_blank" rel="noopener"
                               class="btn btn-blue"
                               style="margin-top:10px; display:inline-flex; padding:10px 20px; font-size:0.9rem;">
                                <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
                            </a>
                        </div>
                    </div>

                    <?php else: ?>

                    <!--
                        FORMULARIO DE CONTACTO
                        Usamos method POST. PHP valida los campos del servidor.
                        Si todo es correcto, genera la URL de WhatsApp y la muestra.
                    -->
                    <form method="POST" action="contacto.php" novalidate>

                        <div class="grid-2" style="gap:1rem;">
                            <!-- Nombre -->
                            <div class="form-group">
                                <label for="nombre">
                                    <i class="fas fa-user"></i> Nombre *
                                </label>
                                <input type="text" id="nombre" name="nombre"
                                       class="form-control"
                                       placeholder="Tu nombre completo"
                                       value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>"
                                       required
                                       maxlength="100">
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Correo electrónico *
                                </label>
                                <input type="email" id="email" name="email"
                                       class="form-control"
                                       placeholder="tu@correo.com"
                                       value="<?= htmlspecialchars($datos['email'] ?? '') ?>"
                                       required
                                       maxlength="150">
                            </div>
                        </div>

                        <!-- Asunto -->
                        <div class="form-group">
                            <label for="asunto">
                                <i class="fas fa-tag"></i> Asunto *
                            </label>
                            <select id="asunto" name="asunto" class="form-control" required>
                                <option value="">Selecciona un asunto</option>
                                <option value="Información sobre cuidadores"
                                    <?= (($datos['asunto'] ?? '') === 'Información sobre cuidadores') ? 'selected' : '' ?>>
                                    Información sobre cuidadores
                                </option>
                                <option value="Consulta sobre productos"
                                    <?= (($datos['asunto'] ?? '') === 'Consulta sobre productos') ? 'selected' : '' ?>>
                                    Consulta sobre productos
                                </option>
                                <option value="Quiero ser cuidador"
                                    <?= (($datos['asunto'] ?? '') === 'Quiero ser cuidador') ? 'selected' : '' ?>>
                                    Quiero ser cuidador
                                </option>
                                <option value="Problema con un pedido"
                                    <?= (($datos['asunto'] ?? '') === 'Problema con un pedido') ? 'selected' : '' ?>>
                                    Problema con un pedido
                                </option>
                                <option value="Otro"
                                    <?= (($datos['asunto'] ?? '') === 'Otro') ? 'selected' : '' ?>>
                                    Otro
                                </option>
                            </select>
                        </div>

                        <!-- Mensaje -->
                        <div class="form-group">
                            <label for="mensaje">
                                <i class="fas fa-comment"></i> Mensaje *
                            </label>
                            <textarea id="mensaje" name="mensaje"
                                      class="form-control"
                                      placeholder="Escribe tu mensaje aquí..."
                                      required
                                      minlength="10"
                                      maxlength="1000"><?= htmlspecialchars($datos['mensaje'] ?? '') ?></textarea>
                            <!-- Contador de caracteres -->
                            <small style="color:var(--color-gray); font-size:0.8rem;" id="charCount">0 / 1000 caracteres</small>
                        </div>

                        <!-- Botón enviar -->
                        <button type="submit" class="btn btn-blue" style="width:100%; justify-content:center; padding:14px;">
                            <i class="fas fa-paper-plane"></i> Enviar mensaje
                        </button>

                        <p style="text-align:center; font-size:0.82rem; color:var(--color-gray); margin-top:0.75rem;">
                            * Campos obligatorios. El mensaje llegará al panel de administración.
                        </p>

                    </form>

                    <?php endif; ?>
                </div>
            </div>
            <!-- Fin columna formulario -->

        </div>
    </div>
</section>


<!-- ════════════════════════════════════════════════════════
     PREGUNTAS FRECUENTES (FAQ)
     ════════════════════════════════════════════════════════ -->
<section class="section section-blue">
    <div class="container">

        <div class="section-header">
            <h2>Preguntas Frecuentes</h2>
            <p>Resolvemos las dudas más comunes de nuestros usuarios.</p>
        </div>

        <!-- Acordeón de FAQ usando solo CSS/JS liviano -->
        <div style="max-width:700px; margin:0 auto;">

            <?php
            // Array con las preguntas frecuentes
            // Centralizar el contenido en PHP facilita editar/agregar FAQs
            $faqs = [
                [
                    'q' => '¿Cómo puedo contactar a un cuidador?',
                    'a' => 'Ve a la página de Cuidadores, elige el que más te guste y haz clic en el botón "Contactar". Serás redirigido a WhatsApp para coordinar los detalles directamente.'
                ],
                [
                    'q' => '¿Cómo realizo un pedido de productos?',
                    'a' => 'En la tienda de Productos puedes agregar artículos al carrito y luego hacer clic en "Finalizar por WhatsApp", o directamente clic en "Comprar ahora" en cualquier producto. Te redirigiremos a WhatsApp con tu pedido listo.'
                ],
                [
                    'q' => '¿Los cuidadores están verificados?',
                    'a' => 'Sí. Todos los cuidadores pasan por un proceso de verificación de identidad y antecedentes antes de aparecer en nuestra plataforma.'
                ],
                [
                    'q' => '¿Hacen envíos a toda Colombia?',
                    'a' => 'Sí, despachamos a todo el territorio nacional. El costo y tiempo de envío varía según tu ubicación. Consúltanos por WhatsApp para más detalles.'
                ],
                [
                    'q' => '¿Cómo registro mi cuenta como cuidador?',
                    'a' => 'Ve a la sección "Soy Cuidador" en el menú de navegación y haz clic en "Regístrate gratis". Completa el formulario y en 24 horas validaremos tu perfil.'
                ],
            ];
            ?>

            <?php foreach ($faqs as $i => $faq): ?>
            <div class="faq-item animate-on-scroll"
                 style="background:white; border-radius:10px; margin-bottom:1rem; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                <!-- Pregunta (botón toggle) -->
                <button class="faq-btn"
                        onclick="toggleFaq(this)"
                        style="width:100%; text-align:left; padding:1.1rem 1.5rem; background:none; border:none; font-family:var(--font-main, Poppins, sans-serif); font-size:0.95rem; font-weight:600; color:var(--color-primary); display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                    <?= htmlspecialchars($faq['q']) ?>
                    <i class="fas fa-chevron-down" style="transition:transform 0.3s; color:var(--color-light-blue);"></i>
                </button>

                <!-- Respuesta (oculta por defecto) -->
                <div class="faq-answer"
                     style="max-height:0; overflow:hidden; transition:max-height 0.4s ease, padding 0.3s ease; padding:0 1.5rem;">
                    <p style="padding-bottom:1.1rem; color:var(--color-gray); font-size:0.9rem; line-height:1.7;">
                        <?= htmlspecialchars($faq['a']) ?>
                    </p>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>


<?php include 'includes/footer.php'; ?>

<!-- Scripts específicos de la página de contacto -->
<script>
/**
 * Contador de caracteres para el textarea del formulario
 */
const textarea   = document.getElementById('mensaje');
const charCount  = document.getElementById('charCount');

if (textarea && charCount) {
    textarea.addEventListener('input', () => {
        const len = textarea.value.length;
        charCount.textContent = `${len} / 1000 caracteres`;
        // Cambiar color si se acerca al límite
        charCount.style.color = len > 900 ? '#E65100' : 'var(--color-gray)';
    });
}

/**
 * toggleFaq()
 * Abre/cierra el acordeón de FAQ.
 * Cierra los demás ítems cuando se abre uno nuevo.
 * @param {HTMLElement} btn - El botón clickeado
 */
function toggleFaq(btn) {
    const answer = btn.nextElementSibling; // El div de la respuesta
    const icon   = btn.querySelector('i');
    const estaAbierto = answer.style.maxHeight !== '0px' && answer.style.maxHeight !== '';

    // Cerrar todos los otros items primero
    document.querySelectorAll('.faq-answer').forEach(a => {
        a.style.maxHeight = '0';
        a.style.padding   = '0 1.5rem';
    });
    document.querySelectorAll('.faq-btn i').forEach(i => {
        i.style.transform = 'rotate(0deg)';
    });

    // Si este no estaba abierto, abrirlo
    if (!estaAbierto) {
        answer.style.maxHeight = answer.scrollHeight + 'px';
        icon.style.transform   = 'rotate(180deg)';
    }
}
</script>
