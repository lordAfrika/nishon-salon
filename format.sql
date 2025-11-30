-- format.sql - schema for Nishon salon booking system
-- Run this in MySQL (e.g., via phpMyAdmin or mysql CLI)

CREATE DATABASE IF NOT EXISTS `nishon` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nishon`;

-- users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50),
  `password_hash` VARCHAR(255) NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50),
  `booking_date` DATE NOT NULL,
  `booking_time` TIME NOT NULL,
  `message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_booking_date` (`booking_date`),
  INDEX `idx_booking_time` (`booking_time`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Trigger to enforce working hours (09:00 - 23:00) and max 5 clients per hour
DELIMITER $$
CREATE TRIGGER trg_bookings_before_insert
BEFORE INSERT ON bookings FOR EACH ROW
BEGIN
  IF NEW.booking_time < '09:00:00' OR NEW.booking_time > '23:00:00' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Booking outside working hours (09:00 - 23:00)';
  END IF;
  DECLARE cnt INT DEFAULT 0;
  SELECT COUNT(*) INTO cnt FROM bookings WHERE booking_date = NEW.booking_date AND HOUR(booking_time) = HOUR(NEW.booking_time);
  IF cnt >= 5 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This hour is full - please pick another time';
  END IF;
END$$

CREATE TRIGGER trg_bookings_before_update
BEFORE UPDATE ON bookings FOR EACH ROW
BEGIN
  IF NEW.booking_time < '09:00:00' OR NEW.booking_time > '23:00:00' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Booking outside working hours (09:00 - 23:00)';
  END IF;
  DECLARE cnt2 INT DEFAULT 0;
  SELECT COUNT(*) INTO cnt2 FROM bookings WHERE booking_date = NEW.booking_date AND HOUR(booking_time) = HOUR(NEW.booking_time) AND id != OLD.id;
  IF cnt2 >= 5 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This hour is full - please pick another time';
  END IF;
END$$
DELIMITER ;

-- Optional: insert an admin user placeholder (password must be hashed via PHP)
-- INSERT INTO users (full_name, email, phone, password_hash, is_admin) VALUES ('Admin', 'admin@example.com', '000', '<php_password_hash_here>', 1);
