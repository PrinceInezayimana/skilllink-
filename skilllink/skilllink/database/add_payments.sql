-- Add payments table to track job application fees
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 9.59,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `rwf_amount` int(11) NOT NULL DEFAULT 10000,
  `phone_number` varchar(20) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending', 'completed', 'failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `employer_id` (`employer_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
