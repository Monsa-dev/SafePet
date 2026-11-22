-- ================================================================
-- ARCHIVO: sql/admin.sql
-- PROPÓSITO: Crear tabla de administradores y usuario inicial.
-- CREDENCIALES INICIALES:
--  Usuario:    admin@safepet.com
--  Contraseña: SafePet2026
--  (Puedes cambiarla desde el panel una vez dentro)
-- ================================================================

USE safepet_db;

-- ── Tabla admins ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    usuario     VARCHAR(60)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    ultimo_login DATETIME    DEFAULT NULL,
    fecha_creacion DATETIME  DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla mensajes de contacto ──────────────────────────────────
CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL,
    asunto         VARCHAR(150) NOT NULL,
    mensaje        TEXT NOT NULL,
    estado         ENUM('nuevo', 'leido') DEFAULT 'nuevo',
    ip_origen      VARCHAR(45) DEFAULT NULL,
    user_agent     VARCHAR(255) DEFAULT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Usuario administrador inicial ────────────────────────────────
-- Contraseña: SafePet2026
-- Hash generado con password_hash('SafePet2026', PASSWORD_BCRYPT)
INSERT IGNORE INTO admins (nombre, usuario, password) VALUES
('Administrador SafePet', 'admin@safepet.com', '$2y$10$T7bSCokACkhTcnLpBPMFeezOA7pwCrfAO8j4OyM2j5vqVJrN/SnN.');
