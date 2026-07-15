-- Add a category used by the admin Mitra/Jamaah tabs.
-- Existing reservations are categorized as Jamaah.
ALTER TABLE `reservations`
  ADD COLUMN `category` ENUM('mitra', 'jamaah') NOT NULL DEFAULT 'jamaah' AFTER `status`;

ALTER TABLE `reservations` ADD INDEX `idx_category` (`category`);
