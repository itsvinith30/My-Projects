-- This file contains the SQL commands to create the database tables.
-- You can import this file directly into phpMyAdmin to set up your database.

--
-- Database: `counseling_app`
--
CREATE DATABASE IF NOT EXISTS `counseling_app` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `counseling_app`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `user_role` ENUM('patient', 'counselor') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `counselor_profiles`
--
CREATE TABLE IF NOT EXISTS `counselor_profiles` (
  `profile_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `specialization` VARCHAR(255) DEFAULT 'General Counseling',
  `bio` TEXT,
  `profile_image_url` VARCHAR(255) DEFAULT 'default_avatar.png',
  `application_questions` TEXT, -- Stores custom questions as a JSON string
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `counselor_id` INT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
  `application_form_data` TEXT NOT NULL, -- Stores patient's details and answers as a JSON string
  `scheduled_datetime` DATETIME NULL, -- This will now be set by the PATIENT
  `meeting_details` TEXT NULL, -- Counselor provides meeting link/instructions here
  `rejection_reason` TEXT NULL,
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`counselor_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- NEW: Table structure for table `counselor_availability`
-- This table allows counselors to set their general weekly availability.
--
CREATE TABLE IF NOT EXISTS `counselor_availability` (
  `availability_id` INT AUTO_INCREMENT PRIMARY KEY,
  `counselor_id` INT NOT NULL,
  `day_of_week` ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  FOREIGN KEY (`counselor_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Add some sample data for testing
--
INSERT INTO `counselor_availability` (`counselor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 'Monday', '09:00:00', '17:00:00'),
(1, 'Wednesday', '10:00:00', '18:00:00'),
(2, 'Tuesday', '09:00:00', '13:00:00'),
(2, 'Thursday', '12:00:00', '19:00:00');

