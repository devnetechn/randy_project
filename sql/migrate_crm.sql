-- =====================================================================
--  Migration: CRM leads pipeline + Google Calendar sync.
--  Run ONCE against an existing database (phpMyAdmin → SQL tab), or just
--  re-open setup.php which applies the same changes idempotently.
-- =====================================================================

ALTER TABLE appointments
  ADD COLUMN lead_stage    ENUM('new','contacted','quoted','won','lost') NOT NULL DEFAULT 'new' AFTER decline_reason,
  ADD COLUMN crm_notes     TEXT NULL AFTER lead_stage,
  ADD COLUMN gcal_event_id VARCHAR(255) NULL AFTER crm_notes;

-- CRM follow-up email tracking (added after the initial CRM rollout).
ALTER TABLE appointments
  ADD COLUMN crm_last_email_at DATETIME NULL AFTER crm_notes;
