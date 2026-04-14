-- SkillLink Rwanda — Production Schema v2
-- Changes from v1:
--   1. Added `is_active` to `users` (admin approval workflow)
--   2. Added `status` to `jobs` (admin approval)
--   3. `company_logo` in `employers` is VARCHAR(255) (stores filename)
--   4. Added `profile_pic` VARCHAR(255) to `users` (student / admin avatar)
--   5. Added `profile_pic` VARCHAR(255) to `employers` (employer avatar, separate from logo)
--   6. Added `notifications` table (transitive notification system)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table: applications
-- --------------------------------------------------------
CREATE TABLE `applications` (
  `id`         int(11)                               NOT NULL,
  `student_id` int(11)                               NOT NULL,
  `job_id`     int(11)                               NOT NULL,
  `status`     enum('applied','accepted','rejected')  DEFAULT 'applied',
  `applied_at` timestamp                             NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `applications` (`id`, `student_id`, `job_id`, `status`, `applied_at`) VALUES
(3, 1, 1, 'applied',  '2026-02-06 11:46:57'),
(4, 1, 2, 'applied',  '2026-02-06 11:49:52'),
(5, 1, 3, 'applied',  '2026-02-06 12:11:58');

-- --------------------------------------------------------
-- Table: employers
-- --------------------------------------------------------
CREATE TABLE `employers` (
  `id`           int(11)      NOT NULL,
  `user_id`      int(11)      NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `description`  text         DEFAULT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `profile_pic`  varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `employers` (`id`, `user_id`, `company_name`, `description`, `company_logo`, `profile_pic`) VALUES
(1, 4, 'INEZA CODE ltd', 'haso', NULL, NULL);

-- --------------------------------------------------------
-- Table: jobs
-- --------------------------------------------------------
CREATE TABLE `jobs` (
  `id`              int(11)                                      NOT NULL,
  `employer_id`     int(11)                                      NOT NULL,
  `title`           varchar(150)                                 NOT NULL,
  `description`     text                                         DEFAULT NULL,
  `required_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                    CHECK (json_valid(`required_skills`)),
  `location`        varchar(100)                                 DEFAULT NULL,
  `status`          enum('pending','approved','rejected')        DEFAULT 'approved',
  `created_at`      timestamp                                    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `jobs` (`id`, `employer_id`, `title`, `description`, `required_skills`, `location`, `status`, `created_at`) VALUES
(1, 1, 'code',    'nio', '[\"php\"]',    'kgl',     'approved', '2026-02-06 11:11:23'),
(2, 1, 'program', 'hey', '[\"jva\"]',    'musanze',  'approved', '2026-02-06 11:49:13'),
(3, 1, 'php',     'hey', '[\"python\"]', 'huye',     'approved', '2026-02-06 12:11:03');

-- --------------------------------------------------------
-- Table: students
-- --------------------------------------------------------
CREATE TABLE `students` (
  `id`          int(11)  NOT NULL,
  `user_id`     int(11)  NOT NULL,
  `skills`      longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                CHECK (json_valid(`skills`)),
  `projects`    longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                CHECK (json_valid(`projects`)),
  `resume_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` (`id`, `user_id`, `skills`, `projects`, `resume_link`) VALUES
(1, 1, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id`          int(11)                            NOT NULL,
  `name`        varchar(100)                       NOT NULL,
  `email`       varchar(100)                       NOT NULL,
  `password`    varchar(255)                       NOT NULL,
  `role`        enum('student','employer','admin') NOT NULL,
  `is_active`   tinyint(1)                         NOT NULL DEFAULT 1,
  `profile_pic` varchar(255)                       DEFAULT NULL,
  `created_at`  timestamp                          NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- NOTE: Admin password below is bcrypt of "password" — CHANGE IT after first login.
-- To generate a new hash: echo password_hash('YourPassword', PASSWORD_DEFAULT);
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `profile_pic`, `created_at`) VALUES
(1, 'Prince INEZAYIMANA', 'inezayimanaprince8@gmail.com', '$2y$10$92umbak20w.uGo/ngChR0.iUbmL.oFx41Aorjtycy/rsw4M7oNDi.', 'student',  1, NULL, '2026-02-06 09:32:05'),
(4, 'INEZAYIMANA Prince', 'inezayimanaprince@gmail.com',  '$2y$10$zv5gqYgH1F0H8sFe1zGgvuien4gijV72XbuFqnGOAngyC9YEJflVC', 'employer', 1, NULL, '2026-02-06 11:10:53'),
(5, 'Admin',              'admin@skilllink.rw',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',    1, NULL, '2026-02-06 09:00:00');

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id`         int(11)      NOT NULL,
  `user_id`    int(11)      NOT NULL,
  `message`    varchar(500) NOT NULL,
  `link`       varchar(255) DEFAULT NULL,
  `is_read`    tinyint(1)   NOT NULL DEFAULT 0,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample notifications
INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'Your application for "code" has been received.', 'job_details.php?id=1', 0, '2026-02-06 11:47:00'),
(2, 1, 'New job posted: "program" in Musanze.', 'job_details.php?id=2', 0, '2026-02-06 11:50:00'),
(3, 4, 'A student applied for your job "code".', 'employer/applicants.php?job_id=1', 0, '2026-02-06 11:47:05');

-- --------------------------------------------------------
-- Indexes
-- --------------------------------------------------------
ALTER TABLE `applications`  ADD PRIMARY KEY (`id`), ADD KEY `student_id` (`student_id`), ADD KEY `job_id` (`job_id`);
ALTER TABLE `employers`     ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`);
ALTER TABLE `jobs`          ADD PRIMARY KEY (`id`), ADD KEY `employer_id` (`employer_id`);
ALTER TABLE `students`      ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`);
ALTER TABLE `users`         ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `notifications` ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------
ALTER TABLE `applications`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `employers`     MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `jobs`          MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `students`      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `users`         MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `notifications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- --------------------------------------------------------
-- Foreign Keys
-- --------------------------------------------------------
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`job_id`)     REFERENCES `jobs`     (`id`);

ALTER TABLE `employers`
  ADD CONSTRAINT `employers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE CASCADE;

ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
