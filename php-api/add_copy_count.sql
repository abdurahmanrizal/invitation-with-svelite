-- Add copyCount column to reservations table
ALTER TABLE `reservations` ADD COLUMN `copyCount` INT(11) NOT NULL DEFAULT 0 AFTER `status`;

-- Add index for copyCount (useful for sorting by most copied)
ALTER TABLE `reservations` ADD INDEX `idx_copy_count` (`copyCount`);
