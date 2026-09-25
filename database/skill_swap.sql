-- ========================================================
-- Skill Swap Marketplace - Complete Database Schema & Seed Data
-- Database: skill_swap
-- Compatible with: MySQL 5.7+ / MariaDB 10.4+ / PHP 8.x
-- ========================================================

DROP DATABASE IF EXISTS `skill_swap`;
CREATE DATABASE `skill_swap` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `skill_swap`;

-- Disable foreign key checks during schema creation
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `bio` TEXT DEFAULT NULL,
  `location` VARCHAR(100) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT 'default-avatar.svg',
  `discord` VARCHAR(100) DEFAULT NULL,
  `facebook` VARCHAR(255) DEFAULT NULL,
  `contact_email` VARCHAR(255) DEFAULT NULL,
  `github` VARCHAR(255) DEFAULT NULL,
  `linkedin` VARCHAR(255) DEFAULT NULL,
  `twitter` VARCHAR(255) DEFAULT NULL,
  `instagram` VARCHAR(255) DEFAULT NULL,
  
  `whatsapp` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_username` (`username`),
  UNIQUE KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: skills
-- --------------------------------------------------------
DROP TABLE IF EXISTS `skills`;
CREATE TABLE `skills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `category` VARCHAR(60) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_skills_name` (`name`),
  KEY `idx_skills_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: user_skills
-- --------------------------------------------------------
DROP TABLE IF EXISTS `user_skills`;
CREATE TABLE `user_skills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `skill_id` INT UNSIGNED NOT NULL,
  `skill_type` ENUM('OFFER', 'WANT') NOT NULL,
  `proficiency` ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') NOT NULL DEFAULT 'Intermediate',
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_skill_unique` (`user_id`, `skill_id`, `skill_type`),
  KEY `idx_user_skills_user` (`user_id`),
  KEY `idx_user_skills_skill` (`skill_id`),
  KEY `idx_user_skills_type` (`skill_type`),
  CONSTRAINT `fk_user_skills_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_skills_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: swap_requests
-- --------------------------------------------------------
DROP TABLE IF EXISTS `swap_requests`;
CREATE TABLE `swap_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `offered_skill_id` INT UNSIGNED NOT NULL,
  `requested_skill_id` INT UNSIGNED NOT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_swap_sender` (`sender_id`),
  KEY `idx_swap_receiver` (`receiver_id`),
  KEY `idx_swap_status` (`status`),
  CONSTRAINT `fk_swap_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_swap_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_swap_offered_skill` FOREIGN KEY (`offered_skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_swap_requested_skill` FOREIGN KEY (`requested_skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: conversations
-- --------------------------------------------------------
DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_one` INT UNSIGNED NOT NULL,
  `user_two` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_conversation_pair` (`user_one`, `user_two`),
  KEY `idx_conversation_user_two` (`user_two`),
  CONSTRAINT `fk_conv_user_one` FOREIGN KEY (`user_one`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_user_two` FOREIGN KEY (`user_two`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: messages
-- --------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_conversation` (`conversation_id`),
  KEY `idx_msg_sender` (`sender_id`),
  KEY `idx_msg_is_read` (`is_read`),
  CONSTRAINT `fk_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: notifications
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(40) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_is_read` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: reviews
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reviewer_id` INT UNSIGNED NOT NULL,
  `reviewed_user_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_review_pair` (`reviewer_id`, `reviewed_user_id`),
  KEY `idx_review_reviewed` (`reviewed_user_id`),
  CONSTRAINT `fk_rev_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_reviewed` FOREIGN KEY (`reviewed_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: reports
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` INT UNSIGNED NOT NULL,
  `reported_user_id` INT UNSIGNED NOT NULL,
  `report_type` VARCHAR(50) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rep_reporter` (`reporter_id`),
  KEY `idx_rep_reported` (`reported_user_id`),
  KEY `idx_rep_status` (`status`),
  CONSTRAINT `fk_rep_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rep_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;


-- ========================================================
-- SEED DATA
-- ========================================================

-- Master Skills Taxonomy
INSERT INTO `skills` (`id`, `name`, `category`, `description`) VALUES
(1, 'Python', 'Programming', 'Python programming for web, automation, scripting, and data analysis.'),
(2, 'JavaScript', 'Programming', 'Modern ES6+, DOM manipulation, web application frontend development.'),
(3, 'PHP', 'Programming', 'Backend server-side web scripting with PHP and MySQL database design.'),
(4, 'Java', 'Programming', 'Object-oriented software development and enterprise application logic.'),
(5, 'C++', 'Programming', 'Systems programming, high-performance algorithms, and memory management.'),
(6, 'SQL & Databases', 'Programming', 'Relational database schema modeling, queries, indexes, and optimization.'),
(7, 'HTML5 & CSS3', 'Programming', 'Semantic markup, modern CSS grid, flexbox, animations, and responsive UI.'),
(8, 'Adobe Photoshop', 'Design', 'Photo retouching, digital artwork, raster manipulation, and banner design.'),
(9, 'UI/UX Design', 'Design', 'User experience wireframing, UX research, usability testing, and UI design.'),
(10, 'Figma', 'Design', 'Collaborative design systems, prototyping, micro-interactions, and handoff.'),
(11, 'Video Editing', 'Design', 'Non-linear video editing, transitions, color grading, and audio mastering.'),
(12, 'Graphic Design', 'Design', 'Visual identity, logos, vector illustration, and typography aesthetics.'),
(13, 'Acoustic Guitar', 'Creative', 'Fingerpicking, chords, chord transitions, rhythm, and song accompaniment.'),
(14, 'Digital Photography', 'Creative', 'Manual exposure, composition, lighting setups, and RAW workflow.'),
(15, 'Music Production', 'Creative', 'Digital audio workstation sequencing, beat making, synthesizers, and mixing.'),
(16, 'Illustration & Drawing', 'Creative', 'Character design, line art, shading, perspective, and digital painting.'),
(17, 'English Fluency & Writing', 'Education', 'Conversational English, academic writing, grammar, and pronunciation.'),
(18, 'Spanish Conversation', 'Education', 'Everyday conversational Spanish, vocabulary, grammar, and expressions.'),
(19, 'College Mathematics', 'Education', 'Linear algebra, calculus, discrete math, and probability foundations.'),
(20, 'Physics Fundamentals', 'Education', 'Mechanics, electromagnetism, optics, and problem solving.'),
(21, 'Public Speaking', 'Business', 'Stage presence, speech crafting, body language, and confidence.'),
(22, 'Digital Marketing', 'Business', 'Performance marketing, social media campaigns, analytics, and funnel design.'),
(23, 'Content Writing & SEO', 'Business', 'Search engine optimization, article writing, storytelling, and keywords.'),
(24, 'Project Management', 'Business', 'Agile, sprint planning, task prioritization, and team coordination.');

-- Demo Users
-- Note: 'Password@123' hash is: $2y$10$UbyLpK3X/YbvGQdetQJX0.gUkzl.diTyrXrrHgtgaxunTunHFxOsi
--       'Admin@123' hash is:    $2y$10$qJKoPK.IWa5/R2qBdUEWZeH7MG.oGtt/vZZktUSTSaokKz4i3pcA6
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `bio`, `location`, `profile_image`, `role`, `status`) VALUES
(1, 'Administrator', 'admin', 'admin@skillswap.com', '$2y$10$qJKoPK.IWa5/R2qBdUEWZeH7MG.oGtt/vZZktUSTSaokKz4i3pcA6', 'Skill Swap Platform Administrator and Community Moderator.', 'San Francisco, CA', 'default-avatar.svg', 'admin', 'active'),
(2, 'Sarah Chen', 'sarah_c', 'sarah@skillswap.com', '$2y$10$UbyLpK3X/YbvGQdetQJX0.gUkzl.diTyrXrrHgtgaxunTunHFxOsi', 'Full-stack software developer with 4 years experience in Python and JavaScript. Looking to learn Photoshop & UI/UX for personal projects!', 'Seattle, WA', 'default-avatar.svg', 'user', 'active'),
(3, 'Alex Miller', 'alex_m', 'alex@skillswap.com', '$2y$10$UbyLpK3X/YbvGQdetQJX0.gUkzl.diTyrXrrHgtgaxunTunHFxOsi', 'Passionate digital designer and Figma enthusiast. I craft brand identities and UI designs. Eager to pick up Python backend coding!', 'Austin, TX', 'default-avatar.svg', 'user', 'active'),
(4, 'Priya Sharma', 'priya_s', 'priya@skillswap.com', '$2y$10$UbyLpK3X/YbvGQdetQJX0.gUkzl.diTyrXrrHgtgaxunTunHFxOsi', 'Data analyst by day, music lover by night. Happy to teach SQL, Python, or College Math in exchange for Acoustic Guitar lessons.', 'Boston, MA', 'default-avatar.svg', 'user', 'active'),
(5, 'David Kim', 'david_k', 'david@skillswap.com', '$2y$10$UbyLpK3X/YbvGQdetQJX0.gUkzl.diTyrXrrHgtgaxunTunHFxOsi', 'Commercial videographer and video editor. Love sharing Premiere/After Effects tricks. Interested in Spanish conversation and public speaking.', 'Chicago, IL', 'default-avatar.svg', 'user', 'active'),
(6, 'Elena Rostova', 'elena_r', 'elena@skillswap.com', '$2y$10$UbyLpK3X/YbvGQdetQJX0.gUkzl.diTyrXrrHgtgaxunTunHFxOsi', 'Native Spanish speaker and linguistic researcher. Can help you become fluent in Spanish! Want to learn Web Development & JavaScript.', 'New York, NY', 'default-avatar.svg', 'user', 'active');

-- User Skills (Set up strong 2-way and 1-way matches)
-- Sarah (User 2) OFFERS Python (1), JavaScript (2), SQL (6); WANTS Photoshop (8), UI/UX (9)
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `proficiency`, `description`) VALUES
(2, 1, 'OFFER', 'Advanced', 'Built production microservices and automation scripts with Python and Flask/FastAPI.'),
(2, 2, 'OFFER', 'Expert', 'Deep experience in modern JavaScript, asynchronous programming, and web APIs.'),
(2, 3, 'OFFER', 'Intermediate', 'Can teach modern PHP 8 and backend server setup.'),
(2, 8, 'WANT', 'Beginner', 'Want to master retouching, color palettes, and creating graphics for my apps.'),
(2, 9, 'WANT', 'Intermediate', 'Looking to improve user flow design and interface wireframing.');

-- Alex (User 3) OFFERS Photoshop (8), UI/UX (9), Figma (10); WANTS Python (1), JavaScript (2)
-- Note: Sarah & Alex form a PERFECT MUTUAL TWO-WAY MATCH!
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `proficiency`, `description`) VALUES
(3, 8, 'OFFER', 'Expert', '10+ years using Adobe Photoshop for photo manipulation, posters, and web mockups.'),
(3, 9, 'OFFER', 'Advanced', 'Specialized in design systems, accessibility, and high-fidelity interface prototypes.'),
(3, 10, 'OFFER', 'Expert', 'Can teach complete Figma workflow, component libraries, and auto-layout.'),
(3, 1, 'WANT', 'Beginner', 'Want to understand basic syntax, loops, and write simple data processing scripts.'),
(3, 2, 'WANT', 'Intermediate', 'Want to learn frontend JavaScript to bring my UI designs to life in code.');

-- Priya (User 4) OFFERS SQL (6), Python (1), Math (19); WANTS Guitar (13), Video Editing (11)
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `proficiency`, `description`) VALUES
(4, 6, 'OFFER', 'Expert', 'Master complex JOINs, query tuning, indexing, and relational schemas.'),
(4, 1, 'OFFER', 'Advanced', 'Data processing using Pandas, NumPy, and basic automation.'),
(4, 19, 'OFFER', 'Advanced', 'Can tutor calculus, statistics, and discrete math with clear intuitive explanations.'),
(4, 13, 'WANT', 'Beginner', 'Beginner guitarist eager to learn basic chords and strumming patterns.'),
(4, 11, 'WANT', 'Beginner', 'Want to edit short educational video lessons.');

-- David (User 5) OFFERS Video Editing (11), Photography (14), Guitar (13); WANTS Spanish (18), Public Speaking (21)
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `proficiency`, `description`) VALUES
(5, 11, 'OFFER', 'Expert', 'Professional video editing, pacing, sound design, and color grading.'),
(5, 14, 'OFFER', 'Advanced', 'Master aperture, shutter speed, studio lighting, and framing.'),
(5, 13, 'OFFER', 'Intermediate', 'Can teach acoustic guitar chords, fingerpicking, and pop song progressions.'),
(5, 18, 'WANT', 'Beginner', 'Planning to travel to South America; need conversational Spanish.'),
(5, 21, 'WANT', 'Intermediate', 'Looking to improve keynote speaking and pitch presentations.');

-- Elena (User 6) OFFERS Spanish (18), Content Writing (23); WANTS JavaScript (2), Python (1)
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `proficiency`, `description`) VALUES
(6, 18, 'OFFER', 'Expert', 'Native speaker. Can guide you from zero to conversational fluency with practical dialogues.'),
(6, 23, 'OFFER', 'Advanced', 'Editorial writing, storytelling, and compelling copy.'),
(6, 2, 'WANT', 'Beginner', 'Starting from scratch with web development.'),
(6, 1, 'WANT', 'Beginner', 'Want to learn Python basics for language text processing.');

-- Seed Swap Requests
-- Sarah Chen requested Photoshop from Alex Miller in exchange for Python (Accepted swap!)
INSERT INTO `swap_requests` (`id`, `sender_id`, `receiver_id`, `offered_skill_id`, `requested_skill_id`, `message`, `status`, `created_at`) VALUES
(1, 2, 3, 1, 8, 'Hi Alex! I noticed you want to learn Python. I have built production Python backends and would love to exchange 1-on-1 tutoring sessions for some Photoshop lessons!', 'accepted', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 4, 5, 1, 13, 'Hey David! Would you be open to teaching acoustic guitar chords in exchange for Python or SQL lessons? Looking forward to connecting!', 'pending', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Seed Conversation & Messages
INSERT INTO `conversations` (`id`, `user_one`, `user_two`, `created_at`) VALUES
(1, 2, 3, DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 2, 'Hi Alex! Thanks for accepting the swap request. Really excited to collaborate!', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 3, 'Hey Sarah! Absolutely, Python has been on my bucket list for months. When would be a good time for our first session?', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 1, 2, 'How about Thursday at 6 PM? We can do 45 minutes of Python fundamentals and then 45 minutes of Photoshop basics.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 1, 3, 'Sounds like a great plan! I will prepare some Photoshop exercises for beginners.', 0, DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Seed Notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `reference_id`, `is_read`, `created_at`) VALUES
(2, 'swap_accepted', 'Swap Request Accepted', 'Alex Miller accepted your skill swap request for Adobe Photoshop.', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'swap_received', 'New Swap Request', 'Sarah Chen sent you a swap request: Python for Adobe Photoshop.', 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'swap_received', 'New Swap Request', 'Priya Sharma sent you a swap request: Python for Acoustic Guitar.', 2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'new_message', 'New Message from Alex', 'Sounds like a great plan! I will prepare some Photoshop exercises for beginners.', 1, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Seed Reviews
INSERT INTO `reviews` (`reviewer_id`, `reviewed_user_id`, `rating`, `comment`, `created_at`) VALUES
(3, 2, 5, 'Sarah is an incredible mentor! She explained Python variables, lists, and functions with super clear analogies. Highly recommended for any skill exchange!', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 3, 5, 'Alex is a fantastic teacher. In just one session I learned how layers, masking, and adjustment tools work in Photoshop. Can not wait for our next session!', DATE_SUB(NOW(), INTERVAL 1 DAY));
