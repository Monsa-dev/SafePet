/**
 * ================================================================
 * ARCHIVO: js/main.js
 * PROPÓSITO: Lógica del lado del cliente para SafePet.
 * Bloques de código:
 *  1. Estado del carrito y persistencia en localStorage
 *  2. Funciones del carrito (agregar, quitar, actualizar UI)
 *  3. Finalizar compra por WhatsApp
 *  4. Menú hamburguesa (responsive)
 *  5. Filtros de productos
 *  6. Notificaciones Toast
 *  7. Animaciones al hacer scroll (Intersection Observer)
 *  8. Inicialización al cargar la página
 * ================================================================
 */


/* ================================================================
   1. ESTADO DEL CARRITO Y PERSISTENCIA
   El carrito se guarda en localStorage para que persista al
   navegar entre páginas y al recargar el navegador.
   ================================================================ */
let carrito = JSON.parse(localStorage.getItem('safepet_carrito')) || [];
// ^ Si ya hay datos guardados, los carga; si no, empieza con array vacío.

/**
 * Guarda el carrito en localStorage.
 * Se llama cada vez que el carrito cambia.
 */
function guardarCarrito() {
    localStorage.setItem('safepet_carrito', JSON.stringify(carrito));
}


/* ================================================================
   2. FUNCIONES DEL CARRITO
   ================================================================ */

/**
 * agregarAlCarrito()
 * Agrega un producto al carrito o incrementa su cantidad si ya existe.
 * @param {string} id       - Identificador único del producto
 * @param {string} nombre   - Nombre del producto
 * @param {number} precio   - Precio del producto (número entero en COP)
 * @param {string} imagen   - URL de imagen representativa del producto
 * @param {string} categoria - Categoría del producto
 */
function agregarAlCarrito(id, nombre, precio, imagen, categoria) {
    // Buscamos si el producto ya está en el carrito
    const itemExistente = carrito.find(item => item.id === id);

    if (itemExistente) {
        // Si ya existe, solo incrementamos la cantidad
        itemExistente.cantidad++;
        mostrarToast(`¡Unidad extra de "${nombre}" agregada! 🛒`);
    } else {
        // Si no existe, lo añadimos como nuevo ítem
        carrito.push({
            id,
            nombre,
            precio,
            imagen,
            categoria,
            cantidad: 1
        });
        mostrarToast(`"${nombre}" agregado al carrito ✅`);
    }

    guardarCarrito();       // Persiste en localStorage
    actualizarCarritoUI();  // Actualiza la interfaz visual
}

/**
 * cambiarCantidad()
 * Incrementa o decrementa la cantidad de un ítem.
 * Si la cantidad llega a 0, elimina el ítem.
 * @param {string} id    - ID del producto
 * @param {number} delta - +1 para sumar, -1 para restar
 */
function cambiarCantidad(id, delta) {
    const item = carrito.find(i => i.id === id);

    if (!item) return; // Seguridad: no hacer nada si no existe

    item.cantidad += delta;

    if (item.cantidad <= 0) {
        // Si llega a 0 o menos, eliminar del carrito
        eliminarDelCarrito(id);
        return;
    }

    guardarCarrito();
    actualizarCarritoUI();
}

/**
 * eliminarDelCarrito()
 * Elimina completamente un producto del carrito.
 * @param {string} id - ID del producto a eliminar
 */
function eliminarDelCarrito(id) {
    const nombre = carrito.find(i => i.id === id)?.nombre || 'Producto';
    carrito = carrito.filter(item => item.id !== id); // Filtra quitando el ítem
    guardarCarrito();
    actualizarCarritoUI();
    mostrarToast(`"${nombre}" eliminado del carrito 🗑️`, 'info');
}

/**
 * actualizarCarritoUI()
 * Re-renderiza completamente el panel del carrito y actualiza
 * el badge contador en el ícono del header.
 */
function actualizarCarritoUI() {
    const cartItemsEl  = document.getElementById('cartItems');
    const cartBadge    = document.getElementById('cartBadge');
    const cartTotalEl  = document.getElementById('cartTotal');

    // ── Actualizar badge (total de unidades) ────────────────
    const totalUnidades = carrito.reduce((sum, item) => sum + item.cantidad, 0);
    if (cartBadge) {
        cartBadge.textContent = totalUnidades;
        // Mostrar u ocultar el badge según si hay ítems
        cartBadge.style.display = totalUnidades > 0 ? 'flex' : 'none';
    }

    // ── Actualizar total en pesos colombianos ────────────────
    const totalPrecio = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    if (cartTotalEl) {
        cartTotalEl.textContent = formatearPrecio(totalPrecio);
    }

    // ── Renderizar lista de ítems ────────────────────────────
    if (!cartItemsEl) return;

    if (carrito.length === 0) {
        // Carrito vacío
        cartItemsEl.innerHTML = `
            <p class="cart-empty">
                <i class="fas fa-box-open"></i>
                Tu carrito está vacío
            </p>`;
        return;
    }

    // Construir HTML de cada ítem del carrito
    let html = '';
    carrito.forEach(item => {
        html += `
        <div class="cart-item" id="cart-item-${item.id}">
            <div class="cart-item-media">
                ${item.imagen
                    ? `<img src="${escaparHTML(item.imagen)}" alt="${escaparHTML(item.nombre)}">`
                    : '<i class="fas fa-box"></i>'}
            </div>
            <div class="cart-item-info">
                <h4>${escaparHTML(item.nombre)}</h4>
                <p>${formatearPrecio(item.precio * item.cantidad)}</p>
            </div>
            <div class="cart-item-qty">
                <!-- Botón restar cantidad -->
                <button class="qty-btn" onclick="cambiarCantidad('${item.id}', -1)"
                        aria-label="Disminuir cantidad">−</button>
                <span>${item.cantidad}</span>
                <!-- Botón sumar cantidad -->
                <button class="qty-btn" onclick="cambiarCantidad('${item.id}', 1)"
                        aria-label="Aumentar cantidad">+</button>
                <!-- Botón eliminar del carrito -->
                <button class="qty-remove" onclick="eliminarDelCarrito('${item.id}')"
                        aria-label="Eliminar producto">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
    });

    cartItemsEl.innerHTML = html;
}

/**
 * toggleCart()
 * Abre o cierra el panel lateral del carrito.
 * Agrega/quita la clase 'open' al panel y al overlay.
 */
function toggleCart() {
    const panel   = document.getElementById('cartPanel');
    const overlay = document.getElementById('cartOverlay');

    if (!panel || !overlay) return;

    const estaAbierto = panel.classList.contains('open');

    panel.classList.toggle('open');
    overlay.classList.toggle('open');

    // Bloquear/desbloquear el scroll del body cuando el panel está abierto
    document.body.style.overflow = estaAbierto ? '' : 'hidden';
}


/* ================================================================
   3. FINALIZAR COMPRA POR WHATSAPP
   Genera un mensaje con el resumen del pedido y abre WhatsApp
   Web con ese mensaje pre-cargado.
   ================================================================ */

/**
 * finalizarCompra()
 * Construye el mensaje del pedido y redirige a WhatsApp.
 * El número de WhatsApp del negocio está hardcodeado aquí.
 */
function finalizarCompra() {
    if (carrito.length === 0) {
        mostrarToast('Tu carrito está vacío. Agrega productos primero.', 'error');
        return;
    }

    // Número de WhatsApp del negocio (formato internacional sin +)
    const WHATSAPP_NUMBER = '573242956121';

    // ── Construir el mensaje del pedido ─────────────────────
    let mensaje = '🐾 *Hola! Quiero hacer un pedido en SafePet:*\n\n';

    let total = 0;
    carrito.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        mensaje += `• *${item.nombre}*\n`;
        mensaje += `   Cantidad: ${item.cantidad} x ${formatearPrecio(item.precio)}\n`;
        mensaje += `   Subtotal: ${formatearPrecio(subtotal)}\n\n`;
    });

    mensaje += `─────────────────────\n`;
    mensaje += `💰 *TOTAL: ${formatearPrecio(total)}*\n\n`;
    mensaje += `¡Quedo atento a su respuesta! 😊`;

    // Codificar el mensaje para URL (reemplaza espacios y caracteres especiales)
    const mensajeCodificado = encodeURIComponent(mensaje);

    // URL de WhatsApp Web / App
    const urlWhatsApp = `https://wa.me/${WHATSAPP_NUMBER}?text=${mensajeCodificado}`;

    // Abrir en nueva pestaña para no perder la página actual
    window.open(urlWhatsApp, '_blank', 'noopener,noreferrer');
}

/**
 * comprarAhora()
 * Compra directa de un solo producto sin pasar por el carrito.
 * @param {string} nombre   - Nombre del producto
 * @param {number} precio   - Precio del producto
 * @param {string} imagen   - URL de imagen del producto
 */
function comprarAhora(nombre, precio, imagen) {
    const WHATSAPP_NUMBER = '573242956121';

    let mensaje = `🐾 *Hola! Quiero comprar este producto en SafePet:*\n\n`;
    mensaje += `• *${nombre}*\n`;
    mensaje += `💰 Precio: ${formatearPrecio(precio)}\n\n`;
    if (imagen) {
        mensaje += `🖼️ Imagen: ${imagen}\n\n`;
    }
    mensaje += `¡Por favor indíquenme cómo proceder con el pago y envío! 😊`;

    const urlWhatsApp = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(mensaje)}`;
    window.open(urlWhatsApp, '_blank', 'noopener,noreferrer');
}


/* ================================================================
   4. MENÚ HAMBURGUESA (RESPONSIVE)
   Controla la apertura/cierre del menú en móviles.
   ================================================================ */
function initMenuHamburguesa() {
    const boton = document.getElementById('menuToggle');
    const nav   = document.getElementById('navMain');

    if (!boton || !nav) return;

    boton.addEventListener('click', () => {
        nav.classList.toggle('open');

        // Animar las 3 líneas del hamburguesa a una "X"
        boton.classList.toggle('activo');

        // Actualizar atributo aria para accesibilidad
        const estaAbierto = nav.classList.contains('open');
        boton.setAttribute('aria-expanded', estaAbierto);
    });

    // Cerrar el menú si se hace clic fuera de él
    document.addEventListener('click', (e) => {
        if (!boton.contains(e.target) && !nav.contains(e.target)) {
            nav.classList.remove('open');
            boton.classList.remove('activo');
        }
    });
}


/* ================================================================
   5. FILTROS DE PRODUCTOS
   Permite filtrar los productos por categoría sin recargar la página.
   ================================================================ */

/**
 * initFiltrosProductos()
 * Busca todos los botones de filtro y las tarjetas de producto,
 * y configura los event listeners para filtrar.
 */
function initFiltrosProductos() {
    const filtros   = document.querySelectorAll('.filter-btn');
    const productos = document.querySelectorAll('.product-card');

    if (filtros.length === 0) return; // Solo si existe la sección de filtros

    filtros.forEach(btn => {
        btn.addEventListener('click', () => {
            // Quitar estado activo de todos los botones
            filtros.forEach(b => b.classList.remove('active'));
            // Activar el botón clickeado
            btn.classList.add('active');

            const categoria = btn.dataset.category; // Leer el atributo data-category

            productos.forEach(card => {
                if (categoria === 'todos' || card.dataset.category === categoria) {
                    // Mostrar con animación de fade
                    card.style.display = 'flex';
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.style.display = 'none'; // Ocultar
                }
            });
        });
    });
}


/* ================================================================
   6. NOTIFICACIONES TOAST
   Muestra mensajes temporales en la esquina inferior de la pantalla.
   ================================================================ */

/**
 * mostrarToast()
 * @param {string} mensaje  - Texto a mostrar
 * @param {string} tipo     - 'success' | 'error' | 'info' (default: 'success')
 * @param {number} duracion - Milisegundos que dura visible (default: 3000)
 */
function mostrarToast(mensaje, tipo = 'success', duracion = 3000) {
    // Asegurarse de que exista el contenedor de toasts
    let contenedor = document.getElementById('toastContainer');

    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'toastContainer';
        // Estilos del contenedor (posición fija en esquina inferior)
        Object.assign(contenedor.style, {
            position:  'fixed',
            bottom:    '20px',
            right:     '20px',
            zIndex:    '9999',
            display:   'flex',
            flexDirection: 'column',
            gap:       '10px'
        });
        document.body.appendChild(contenedor);
    }

    // ── Crear el toast ───────────────────────────────────────
    const toast = document.createElement('div');

    // Colores según el tipo de mensaje
    const colores = {
        success: { bg: '#1565C0', border: '#4FC3F7' },
        error:   { bg: '#C62828', border: '#EF9A9A' },
        info:    { bg: '#0D47A1', border: '#90CAF9'  }
    };
    const { bg, border } = colores[tipo] || colores.success;

    Object.assign(toast.style, {
        background:    bg,
        borderLeft:    `4px solid ${border}`,
        color:         'white',
        padding:       '12px 20px',
        borderRadius:  '8px',
        fontSize:      '0.9rem',
        fontWeight:    '500',
        boxShadow:     '0 4px 15px rgba(0,0,0,0.2)',
        fontFamily:    "'Poppins', sans-serif",
        maxWidth:      '300px',
        opacity:       '0',
        transform:     'translateX(100%)',
        transition:    'all 0.3s ease',
        cursor:        'pointer'
    });

    toast.textContent = mensaje;

    // Cerrar al hacer clic
    toast.addEventListener('click', () => removerToast(toast));

    contenedor.appendChild(toast);

    // Animación de entrada (pequeño delay para que el browser pinte primero)
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.style.opacity   = '1';
            toast.style.transform = 'translateX(0)';
        });
    });

    // Auto-remover después de 'duracion' ms
    setTimeout(() => removerToast(toast), duracion);
}

/**
 * removerToast()
 * Anima la salida del toast y lo elimina del DOM.
 */
function removerToast(toast) {
    toast.style.opacity   = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 300);
}


/* ================================================================
   7. ANIMACIONES AL HACER SCROLL (Intersection Observer)
   Los elementos con la clase 'animate-on-scroll' aparecen
   con una animación de fade-in cuando entran en el viewport.
   ================================================================ */
function initScrollAnimations() {
    // Agregar estilos CSS de la animación directamente desde JS
    const style = document.createElement('style');
    style.textContent = `
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }
    `;
    document.head.appendChild(style);

    // Crear el observador
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Delay escalonado para efecto cascada en grupos de tarjetas
                entry.target.style.transitionDelay = `${index * 80}ms`;
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Dejar de observar una vez animado
            }
        });
    }, {
        threshold: 0.1, // Activar cuando al menos el 10% del elemento es visible
        rootMargin: '0px 0px -50px 0px' // Un poco antes del viewport
    });

    // Observar todos los elementos con la clase
    document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
}


/* ================================================================
   8. UTILIDADES
   Funciones de ayuda usadas en múltiples partes del código.
   ================================================================ */

/**
 * formatearPrecio()
 * Formatea un número como precio en pesos colombianos.
 * Ejemplo: 15000 → "$15.000"
 */
function formatearPrecio(precio) {
    return new Intl.NumberFormat('es-CO', {
        style:    'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(precio);
}

/**
 * escaparHTML()
 * Previene ataques XSS escapando caracteres especiales HTML.
 * IMPORTANTE: siempre escapar datos del usuario antes de insertarlos en el DOM.
 */
function escaparHTML(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}


/* ================================================================
   9. INICIALIZACIÓN
   Se ejecuta cuando el DOM está completamente cargado.
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar el carrito con los datos guardados
    actualizarCarritoUI();

    // Menú hamburguesa en móviles
    initMenuHamburguesa();

    // Filtros de productos (si la sección existe)
    initFiltrosProductos();

    // Animaciones de scroll
    initScrollAnimations();

    // Cerrar carrito con tecla ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const panel = document.getElementById('cartPanel');
            if (panel && panel.classList.contains('open')) {
                toggleCart();
            }
        }
    });
});
