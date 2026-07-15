-- Create database table for reservations
-- Run this in your PHPMyAdmin or MySQL console

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservationCode` varchar(50) NOT NULL UNIQUE,
  `guestName` varchar(255) NOT NULL,
  `seatLabel` varchar(100) DEFAULT NULL,
  `allowedGuests` int(11) NOT NULL DEFAULT 1,
  `phone` varchar(50) DEFAULT NULL,
  `status` enum('confirmed','pending','checked_in') NOT NULL DEFAULT 'pending',
  `category` enum('mitra','jamaah') NOT NULL DEFAULT 'jamaah',
  `checkedInAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reservation_code` (`reservationCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO `reservations` (`reservationCode`, `guestName`, `seatLabel`, `allowedGuests`, `phone`, `status`) VALUES
('UMR-ARIF-001', 'Arif Rahman', 'Table A · Seat 03', 2, '+62 812-1111-2222', 'confirmed'),
('UMR-NISA-002', 'Nisa Azzahra', 'Table B · Seat 05', 4, '+62 813-3333-4444', 'confirmed'),
('UMR-FARIS-003', 'Faris Maulana', 'Table C · Seat 01', 1, '+62 815-5555-6666', 'pending');
