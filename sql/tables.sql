-- =====================================================================
--  Randy's Painting & Drywall — TABLES ONLY (no CREATE DATABASE / USE).
--  Use this on shared hosting (e.g. Hostinger): create the database in the
--  control panel first, then import this file INTO that database via
--  phpMyAdmin. Also used by setup.php against the already-selected database.
-- =====================================================================

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

CREATE TABLE IF NOT EXISTS appointments (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  -- customer_id is NULL for guest quote requests (no account required).
  customer_id     BIGINT UNSIGNED NULL,
  guest_name      VARCHAR(255) NULL,
  guest_email     VARCHAR(255) NULL,
  guest_phone     VARCHAR(64) NULL,
  conversation_id BIGINT UNSIGNED NULL,
  service_type    VARCHAR(120) NOT NULL,
  preferred_at    DATETIME NOT NULL,
  scheduled_at    DATETIME NULL,
  address         VARCHAR(300) NOT NULL,
  phone           VARCHAR(64) NULL,
  notes           TEXT NULL,
  status          ENUM('pending','confirmed','declined','cancelled','completed') NOT NULL DEFAULT 'pending',
  decline_reason  VARCHAR(500) NULL,
  -- CRM sales pipeline (separate from the operational `status` above).
  lead_stage      ENUM('new','contacted','quoted','won','lost') NOT NULL DEFAULT 'new',
  crm_notes       TEXT NULL,
  -- Google Calendar event id, so we can update/delete the synced event.
  gcal_event_id   VARCHAR(255) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_appt_customer     FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_appt_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
  INDEX idx_appt_customer (customer_id),
  INDEX idx_appt_status (status),
  INDEX idx_appt_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_images (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename   VARCHAR(255) NOT NULL,
  caption    VARCHAR(200) NULL,
  category   ENUM('interior','exterior','drywall','commercial','other') NOT NULL DEFAULT 'other',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gallery_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_posts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  excerpt      VARCHAR(300) NULL,
  body         MEDIUMTEXT NOT NULL,
  image        VARCHAR(255) NULL,
  status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_blog_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author     VARCHAR(120) NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL DEFAULT 5,
  body       TEXT NOT NULL,
  meta       VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  skey       VARCHAR(64) NOT NULL PRIMARY KEY,
  svalue     TEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_positions (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(150) NOT NULL,
  description      TEXT NOT NULL,
  requirements     TEXT NULL,
  employment_type  ENUM('full_time','part_time','contract') NOT NULL DEFAULT 'full_time',
  pay_range        VARCHAR(100) NULL,
  status           ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_job_positions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_applications (
  id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position_id              BIGINT UNSIGNED NULL,
  position_title_snapshot  VARCHAR(150) NOT NULL,
  name                     VARCHAR(150) NOT NULL,
  email                    VARCHAR(190) NOT NULL,
  phone                    VARCHAR(30) NOT NULL,
  years_experience         VARCHAR(50) NULL,
  availability             VARCHAR(150) NULL,
  message                  TEXT NULL,
  resume_path              VARCHAR(255) NOT NULL,
  status                   ENUM('new','reviewed','hired','rejected') NOT NULL DEFAULT 'new',
  created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_job_applications_position FOREIGN KEY (position_id) REFERENCES job_positions(id) ON DELETE SET NULL,
  INDEX idx_job_applications_status (status),
  INDEX idx_job_applications_position (position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
