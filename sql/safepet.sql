-- ================================================================
-- ARCHIVO: sql/safepet.sql
-- PROPÓSITO: Script completo de la base de datos SafePet.
-- Contiene:
--  1. Creación de la base de datos
--  2. Tabla: cuidadores (usuarios del panel)
--  3. Tabla: solicitudes (peticiones de cuidado)
--  4. Tabla: productos (catálogo de la tienda)
--  5. Datos de ejemplo (seed data) para demostración
--
-- ================================================================


-- ── 1. BASE DE DATOS ─────────────────────────────────────────────
-- IF NOT EXISTS: No da error si ya existe la BD
CREATE DATABASE IF NOT EXISTS safepet_db
  CHARACTER SET utf8mb4   -- Soporte completo de Unicode (emojis, ñ, etc.)
  COLLATE utf8mb4_unicode_ci;  -- Ordenación insensible a mayúsculas

USE safepet_db;


-- ── 2. TABLA: cuidadores ─────────────────────────────────────────
-- Almacena los datos de los cuidadores registrados en la plataforma.
-- La contraseña se guarda como hash de bcrypt (nunca texto plano).
CREATE TABLE IF NOT EXISTS cuidadores (
    id           INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,                          -- Nombre completo
    email        VARCHAR(150) NOT NULL UNIQUE,                   -- Email único (login)
    password     VARCHAR(255) NOT NULL,                          -- Hash bcrypt
    telefono     VARCHAR(20)  DEFAULT NULL,                      -- WhatsApp del cuidador
    ciudad       VARCHAR(80)  NOT NULL,
    especialidad VARCHAR(100) DEFAULT 'Perros y Gatos',          -- Tipo de mascotas
    descripcion  TEXT         DEFAULT NULL,                      -- Bio pública del cuidador
    precio_hora  DECIMAL(10,0) DEFAULT 20000,                   -- Precio en COP por hora
    foto         VARCHAR(255) DEFAULT NULL,                      -- Ruta de la foto de perfil
    rating       DECIMAL(3,1) DEFAULT 0.0,                      -- Calificación promedio
    activo       TINYINT(1)   DEFAULT 1,                        -- 1=activo, 0=bloqueado
    disponible   TINYINT(1)   DEFAULT 1,                        -- Toggle de disponibilidad
    fecha_registro DATETIME   DEFAULT CURRENT_TIMESTAMP,
    fecha_update   DATETIME   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índice en email para búsquedas de login rápidas
    INDEX idx_email (email),
    -- Índice en ciudad para filtros de búsqueda
    INDEX idx_ciudad (ciudad),
    -- Índice en rating para ordenar por mejor calificados
    INDEX idx_rating (rating)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 3. TABLA: solicitudes ────────────────────────────────────────
-- Cada registro es una solicitud de un dueño de mascota a un cuidador.
-- estado: pendiente | aceptada | rechazada
CREATE TABLE IF NOT EXISTS solicitudes (
    id              INT      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cuidador_id     INT      UNSIGNED NOT NULL,                 -- FK → cuidadores.id

    -- Datos del dueño (no requiere cuenta, cualquiera puede solicitar)
    nombre_dueno    VARCHAR(100) NOT NULL,
    email_dueno     VARCHAR(150) NOT NULL,
    telefono_dueno  VARCHAR(20)  NOT NULL,

    -- Datos de la mascota
    tipo_mascota    VARCHAR(50)  NOT NULL,                      -- Perro, Gato, etc.
    nombre_mascota  VARCHAR(80)  NOT NULL,
    raza_mascota    VARCHAR(80)  DEFAULT NULL,
    edad_mascota    VARCHAR(30)  DEFAULT NULL,

    -- Fechas y detalles del servicio
    fecha_inicio    DATE         NOT NULL,
    fecha_fin       DATE         NOT NULL,
    mensaje         TEXT         DEFAULT NULL,                  -- Notas extra del dueño

    -- Estado de la solicitud
    estado          ENUM('pendiente', 'aceptada', 'rechazada') DEFAULT 'pendiente',
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME DEFAULT NULL,

    -- Llave foránea: al eliminar un cuidador, sus solicitudes también se eliminan
    FOREIGN KEY (cuidador_id) REFERENCES cuidadores(id) ON DELETE CASCADE,

    -- Índices para consultas frecuentes del dashboard
    INDEX idx_cuidador  (cuidador_id),
    INDEX idx_estado    (estado),
    INDEX idx_fecha_sol (fecha_solicitud)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 4. TABLA: productos ──────────────────────────────────────────
-- Catálogo de productos de la tienda virtual.
CREATE TABLE IF NOT EXISTS productos (
    id              INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT         DEFAULT NULL,
    precio          DECIMAL(10,0) NOT NULL,                     -- Precio actual en COP
    precio_original DECIMAL(10,0) DEFAULT NULL,                 -- Precio antes del descuento
    categoria       VARCHAR(50) NOT NULL,                       -- Alimentos, Accesorios, Salud, Higiene
    portada         VARCHAR(255) DEFAULT NULL,                   -- URL/ruta de imagen principal
    galeria         TEXT         DEFAULT NULL,                   -- URLs separadas por comas o JSON
    emoji           VARCHAR(10) DEFAULT '🐾',                   -- Campo legado (compatibilidad)
    badge           VARCHAR(30) DEFAULT NULL,                   -- Ej: "Nuevo", "20% OFF"
    stock           INT         DEFAULT 100,
    destacado       TINYINT(1)  DEFAULT 0,                      -- Mostrar en página de inicio
    activo          TINYINT(1)  DEFAULT 1,
    fecha_creacion  DATETIME    DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_categoria (categoria),
    INDEX idx_activo    (activo),
    INDEX idx_destacado (destacado)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Productos de ejemplo ─────────────────────────────────────────
INSERT INTO productos (nombre, descripcion, precio, precio_original, categoria, portada, galeria, emoji, badge, stock, destacado) VALUES
('Croquetas Premium para Perro Adulto', 'Alta proteína, libre de granos. Ideal para razas medianas.', 45000, 55000, 'Alimentos', 'https://picsum.photos/seed/safepet-prod-1/800/600', NULL, '🐾', '18% OFF', 20, 1),
('Alimento Húmedo para Gato Sabor Atún', 'Pack de 12 latas de alimento húmedo premium para gatos adultos.', 38000, NULL, 'Alimentos', 'https://picsum.photos/seed/safepet-prod-2/800/600', NULL, '🐾', NULL, 15, 0),
('Snacks Dentales para Perro', 'Premios que cuidan los dientes y el aliento de tu perro.', 12000, NULL, 'Alimentos', 'https://picsum.photos/seed/safepet-prod-3/800/600', NULL, '🐾', 'Nuevo', 50, 0),
('Mezcla de Semillas para Aves', 'Mezcla nutritiva de semillas para pájaros y loros.', 15000, NULL, 'Alimentos', 'https://picsum.photos/seed/safepet-prod-4/800/600', NULL, '🐾', NULL, 30, 0),
('Cama Ortopédica para Mascotas', 'Espuma de memoria para el descanso perfecto. Lavable a máquina.', 89000, NULL, 'Accesorios', 'https://picsum.photos/seed/safepet-prod-5/800/600', NULL, '🐾', 'Nuevo', 10, 1),
('Correa Retráctil 5m', 'Correa retráctil con freno automático. Para perros hasta 30kg.', 35000, 45000, 'Accesorios', 'https://picsum.photos/seed/safepet-prod-6/800/600', NULL, '🐾', '22% OFF', 25, 0),
('Transportadora Portátil', 'Transportadora ligera y ventilada para viajes con tu mascota.', 75000, NULL, 'Accesorios', 'https://picsum.photos/seed/safepet-prod-7/800/600', NULL, '🐾', NULL, 8, 0),
('Juguete Dispensador de Premios', 'Estimulación mental para perros. Dispensa premios al rodar.', 28000, NULL, 'Accesorios', 'https://picsum.photos/seed/safepet-prod-8/800/600', NULL, '🐾', NULL, 35, 0),
('Collar Anti-Pulgas y Garrapatas', 'Protección durante 8 meses contra pulgas, garrapatas y mosquitos.', 18000, 25000, 'Salud', 'https://picsum.photos/seed/safepet-prod-9/800/600', NULL, '🐾', '28% OFF', 40, 1),
('Suplemento Vitamínico Canino', 'Vitaminas A, B, C, D y omega-3 para perros en etapa adulta.', 32000, NULL, 'Salud', 'https://picsum.photos/seed/safepet-prod-10/800/600', NULL, '🐾', NULL, 20, 0),
('Pasta Dental para Mascotas', 'Higiene dental sabor pollo. Apta para perros y gatos.', 22000, NULL, 'Salud', 'https://picsum.photos/seed/safepet-prod-11/800/600', NULL, '🐾', NULL, 30, 0),
('Arena para Gato Natural Biodegradable', 'Arena de sílice cristalizada, controla olores 30 días.', 22000, NULL, 'Higiene', 'https://picsum.photos/seed/safepet-prod-12/800/600', NULL, '🐾', NULL, 60, 0),
('Shampoo para Mascotas Avena y Miel', 'Fórmula suave para piel sensible. pH balanceado para mascotas.', 25000, 30000, 'Higiene', 'https://picsum.photos/seed/safepet-prod-13/800/600', NULL, '🐾', '17% OFF', 45, 0),
('Toallitas Húmedas para Mascotas x50', 'Para limpiar patas, oídos o cuerpo entre baños.', 14000, NULL, 'Higiene', 'https://picsum.photos/seed/safepet-prod-14/800/600', NULL, '🐾', NULL, 70, 0),
('Collar Reflectante para Perro', 'Collar con banda reflectante y hebilla resistente. Ajustable.', 32000, NULL, 'Accesorios', 'https://picsum.photos/seed/safepet-prod-15/800/600', NULL, '🐾', 'Nuevo', 18, 0),
('Bebedero Automático para Mascotas', 'Bebedero de 2L con sistema antifugas para agua fresca todo el día.', 46000, NULL, 'Accesorios', 'https://picsum.photos/seed/safepet-prod-16/800/600', NULL, '🐾', '20% OFF', 12, 0),
('Termómetro Digital para Mascotas', 'Termómetro rápido y preciso para medir temperatura corporal.', 28000, NULL, 'Salud', 'https://picsum.photos/seed/safepet-prod-17/800/600', NULL, '🐾', NULL, 25, 0);
-- ================================================================
-- RESUMEN DE LO CREADO:
--  - Tabla cuidadores: sin registros iniciales
--  - Tabla solicitudes: sin registros iniciales
--  - Tabla productos: 17 productos en 4 categorías
--
-- Para crear cuidadores, usar /cuidadores/registro.php
-- ================================================================
