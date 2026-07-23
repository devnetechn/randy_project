-- =====================================================================
--  Randy's Painting & Drywall Services — MySQL schema (XAMPP / MariaDB)
--  Ported from the original PostgreSQL migrations. Auth uses PHP sessions,
--  so the original `refresh_tokens` table is intentionally dropped.
--
--  Run once from phpMyAdmin or the CLI:
--    mysql -u root < sql/schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS randy_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE randy_db;

-- ----------  Users  ----------
CREATE TABLE IF NOT EXISTS users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(255) NOT NULL,
  phone         VARCHAR(64) NULL,
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------  Chat: conversations + messages  ----------
CREATE TABLE IF NOT EXISTS conversations (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id       BIGINT UNSIGNED NOT NULL,
  status            ENUM('ai','waiting_human','human','closed') NOT NULL DEFAULT 'ai',
  assigned_admin_id BIGINT UNSIGNED NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_message_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_conv_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_admin    FOREIGN KEY (assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_conv_customer (customer_id),
  INDEX idx_conv_status (status),
  INDEX idx_conv_last_msg (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_type     ENUM('customer','ai','admin','system') NOT NULL,
  sender_id       BIGINT UNSIGNED NULL,
  body            TEXT NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_sender       FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_msg_conversation (conversation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------  Appointments / bookings  ----------
CREATE TABLE IF NOT EXISTS appointments (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id     BIGINT UNSIGNED NOT NULL,
  conversation_id BIGINT UNSIGNED NULL,
  service_type    VARCHAR(120) NOT NULL,
  preferred_at    DATETIME NOT NULL,
  scheduled_at    DATETIME NULL,
  address         VARCHAR(300) NOT NULL,
  phone           VARCHAR(64) NULL,
  notes           TEXT NULL,
  status          ENUM('pending','confirmed','declined','cancelled','completed') NOT NULL DEFAULT 'pending',
  decline_reason  VARCHAR(500) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_appt_customer     FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
  INDEX idx_appt_customer (customer_id),
  INDEX idx_appt_status (status),
  INDEX idx_appt_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------  Gallery  ----------
CREATE TABLE IF NOT EXISTS gallery_images (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename    VARCHAR(255) NOT NULL,
  caption     VARCHAR(200) NULL,
  description TEXT NULL,
  keywords    VARCHAR(300) NULL,
  category    ENUM('interior','exterior','drywall','commercial','other') NOT NULL DEFAULT 'other',
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gallery_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
