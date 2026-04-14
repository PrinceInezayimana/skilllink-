-- Migration: auto-approve all existing jobs + add realtime support
-- Run this on existing databases

-- 1. Set all pending jobs to approved
UPDATE jobs SET status = 'approved' WHERE status = 'pending';

-- 2. Change default status to approved for future jobs
ALTER TABLE jobs MODIFY COLUMN status ENUM('pending','approved','rejected') DEFAULT 'approved';

-- 3. Make sure notifications table exists with correct schema
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    int(11)      NOT NULL,
  `message`    varchar(500) NOT NULL,
  `link`       varchar(255) DEFAULT NULL,
  `is_read`    tinyint(1)   NOT NULL DEFAULT 0,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`, `is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Migration complete.' AS status;
