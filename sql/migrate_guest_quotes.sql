-- =====================================================================
--  Migration: allow guest quote requests (no account required).
--  Run ONCE against an existing database (phpMyAdmin → SQL tab), or just
--  re-open setup.php which applies the same changes idempotently.
--
--  What it does:
--    * adds guest contact columns to `appointments`
--    * makes customer_id NULL-able (guests have no user row)
--    * changes the customer FK to ON DELETE SET NULL (deleting a user no
--      longer deletes their past appointments)
-- =====================================================================

ALTER TABLE appointments
  ADD COLUMN guest_name  VARCHAR(255) NULL AFTER customer_id,
  ADD COLUMN guest_email VARCHAR(255) NULL AFTER guest_name,
  ADD COLUMN guest_phone VARCHAR(64)  NULL AFTER guest_email;

ALTER TABLE appointments DROP FOREIGN KEY fk_appt_customer;
ALTER TABLE appointments MODIFY customer_id BIGINT UNSIGNED NULL;
ALTER TABLE appointments
  ADD CONSTRAINT fk_appt_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL;
