-- ============================================================
-- Greaselogs v4 - Email activation
-- Run this ONCE on the live database.
-- ============================================================

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `activation_token_hash` CHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS `activation_expires_at` DATETIME NULL;
