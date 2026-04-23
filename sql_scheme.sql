-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.11.14-MariaDB-0ubuntu0.24.04.1-log - Ubuntu 24.04
-- Server OS:                    debian-linux-gnu
-- HeidiSQL Version:             12.15.0.7171
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE = @@TIME_ZONE */;
/*!40103 SET TIME_ZONE = '+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;


-- Dumping database structure for phpgit
CREATE DATABASE IF NOT EXISTS `phpgit` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `phpgit`;

-- Dumping structure for table phpgit.inbox
CREATE TABLE IF NOT EXISTS `inbox`
(
    `id`         int(11)                         NOT NULL AUTO_INCREMENT,
    `username`   varchar(50)                     NOT NULL,
    `email`      varchar(50)                     NOT NULL,
    `subject`    varchar(50)                     NOT NULL,
    `body`       varchar(500)                    NOT NULL,
    `created_at` timestamp                       NOT NULL DEFAULT current_timestamp(),
    `unread`     tinyint(1)                      NOT NULL DEFAULT 1,
    `status`     enum ('new','replied','closed') NOT NULL DEFAULT 'new',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.inbox: ~1 rows (approximately)
INSERT IGNORE INTO `inbox` (`id`, `username`, `email`, `subject`, `body`, `created_at`, `unread`, `status`)
VALUES (1, 'asdas', 'test@test.com', 'dasd', 'dasdasdsa', '2026-04-18 10:49:12', 0, 'new');

-- Dumping structure for table phpgit.issues
CREATE TABLE IF NOT EXISTS `issues`
(
    `id`               int(10) unsigned       NOT NULL AUTO_INCREMENT,
    `repository_id`    int(10) unsigned       NOT NULL,
    `author_user_id`   int(10) unsigned       NOT NULL,
    `assignee_user_id` int(10) unsigned                DEFAULT NULL,
    `title`            varchar(160)           NOT NULL,
    `body`             text                            DEFAULT NULL,
    `status`           enum ('open','closed') NOT NULL DEFAULT 'open',
    `created_at`       timestamp              NOT NULL DEFAULT current_timestamp(),
    `closed_at`        timestamp              NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ix_issues_repo_status` (`repository_id`, `status`),
    KEY `ix_issues_repo_created` (`repository_id`, `created_at`),
    KEY `ix_issues_author` (`author_user_id`),
    KEY `ix_issues_assignee` (`assignee_user_id`),
    CONSTRAINT `fk_issues_assignee` FOREIGN KEY (`assignee_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_issues_author` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_issues_repo` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.issues: ~2 rows (approximately)
INSERT IGNORE INTO `issues` (`id`, `repository_id`, `author_user_id`, `assignee_user_id`, `title`, `body`, `status`,
                             `created_at`, `closed_at`)
VALUES (1, 1, 3, 2, 'Login error on invalid CSRF token', 'Reproducible when session expires before submit.', 'open',
        '2026-04-21 15:09:34', NULL),
       (2, 2, 1, 2, 'Dark mode contrast improvement', 'Buttons in dark mode need higher contrast.', 'open',
        '2026-04-21 15:09:34', NULL);

-- Dumping structure for table phpgit.log
CREATE TABLE IF NOT EXISTS `log`
(
    `id`       int(11)                                                      NOT NULL AUTO_INCREMENT,
    `level`    enum ('Debug','Info','Warning','Error','Critical','Unknown') NOT NULL DEFAULT 'Unknown',
    `message`  tinytext                                                     NOT NULL,
    `log_time` timestamp                                                    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `security` tinyint(1)                                                   NOT NULL DEFAULT 0,
    `ip`       text                                                                  DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 12
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.log: ~0 rows (approximately)

-- Dumping structure for table phpgit.programming_languages
CREATE TABLE IF NOT EXISTS `programming_languages`
(
    `lang`  varchar(50)  NOT NULL,
    `view`  varchar(100) NOT NULL,
    `color` char(7)      NOT NULL,
    PRIMARY KEY (`lang`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.programming_languages: ~0 rows (approximately)

-- Dumping structure for table phpgit.pull_requests
CREATE TABLE IF NOT EXISTS `pull_requests`
(
    `id`               int(10) unsigned                  NOT NULL AUTO_INCREMENT,
    `repository_id`    int(10) unsigned                  NOT NULL,
    `author_user_id`   int(10) unsigned                  NOT NULL,
    `from_branch_name` varchar(100)                      NOT NULL,
    `to_branch_name`   varchar(100)                      NOT NULL,
    `from_head_hash`   char(64)                                   DEFAULT NULL,
    `to_head_hash`     char(64)                                   DEFAULT NULL,
    `title`            varchar(160)                      NOT NULL,
    `body`             text                                       DEFAULT NULL,
    `status`           enum ('open','merged','archived') NOT NULL DEFAULT 'open',
    `created_at`       timestamp                         NOT NULL DEFAULT current_timestamp(),
    `merged_at`        timestamp                         NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ix_pull_requests_repo_status` (`repository_id`, `status`),
    KEY `ix_pull_requests_repo_created` (`repository_id`, `created_at`),
    KEY `ix_pull_requests_author` (`author_user_id`),
    CONSTRAINT `fk_pr_author` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_pr_repo` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.pull_requests: ~0 rows (approximately)
INSERT IGNORE INTO `pull_requests` (`id`, `repository_id`, `author_user_id`, `from_branch_name`, `to_branch_name`,
                                    `from_head_hash`, `to_head_hash`, `title`, `body`, `status`, `created_at`,
                                    `merged_at`)
VALUES (1, 1, 2, 'feature/auth-hardening', 'main', '2222222222222222222222222222222222222222222222222222222222222222',
        '1111111111111111111111111111111111111111111111111111111111111111', 'Improve auth hardening',
        'Adds stricter checks and cleaner redirects.', 'open', '2026-04-21 15:09:34', NULL);

-- Dumping structure for table phpgit.repositories
CREATE TABLE IF NOT EXISTS `repositories`
(
    `id`               int(10) unsigned          NOT NULL AUTO_INCREMENT,
    `owner_user_id`    int(10) unsigned          NOT NULL,
    `repo_name`        varchar(100)              NOT NULL,
    `slug`             varchar(150)              NOT NULL,
    `repo_description` text                               DEFAULT NULL,
    `visibility`       enum ('public','private') NOT NULL DEFAULT 'public',
    `default_branch`   varchar(100)              NOT NULL DEFAULT 'main',
    `stars`            int(10) unsigned                   DEFAULT 0,
    `forks`            int(10) unsigned                   DEFAULT 0,
    `lang`             varchar(50)                        DEFAULT NULL,
    `created_at`       timestamp                 NOT NULL DEFAULT current_timestamp(),
    `updated_at`       timestamp                 NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_repositories_owner_slug` (`owner_user_id`, `slug`),
    KEY `ix_repositories_visibility` (`visibility`),
    KEY `ix_repositories_lang` (`lang`),
    KEY `ix_repositories_repo_name` (`repo_name`),
    KEY `ix_repositories_popular` (`visibility`, `stars` DESC),
    CONSTRAINT `fk_repositories_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 7
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.repositories: ~4 rows (approximately)
INSERT IGNORE INTO `repositories` (`id`, `owner_user_id`, `repo_name`, `slug`, `repo_description`, `visibility`,
                                   `default_branch`, `stars`, `forks`, `lang`, `created_at`, `updated_at`)
VALUES (1, 1, 'phpgit-core', 'phpgit-core', 'Main PHPGit platform repository', 'public', 'main', 255, 190, 'php',
        '2026-04-21 15:09:34', '2026-04-21 15:09:34'),
       (2, 2, 'bootstrap-theme', 'bootstrap-theme', 'Theme experiments for frontend', 'public', 'main', 4980, 2094,
        'javascript', '2026-03-29 11:03:45', '2026-03-29 11:03:45'),
       (4, 1, 'PHPGit', 'admin/PHPGit', 'git based on pure php', 'public', 'main', 0, 0, NULL, '2026-04-18 16:02:20',
        '2026-04-18 16:02:20'),
       (5, 1, 'test', 'admin/test', 'testing', 'public', 'main', 0, 0, 'Markdown', '2026-04-21 15:00:32',
        '2026-04-21 15:55:11'),
       (6, 3, 'test-demo', 'demo/test-demo', 'test', 'private', 'main', 0, 0, NULL, '2026-04-21 17:43:11',
        '2026-04-21 17:43:11');

-- Dumping structure for table phpgit.repository_members
CREATE TABLE IF NOT EXISTS `repository_members`
(
    `repository_id` int(10) unsigned                           NOT NULL,
    `user_id`       int(10) unsigned                           NOT NULL,
    `permission`    enum ('owner','maintainer','write','read') NOT NULL DEFAULT 'read',
    `added_at`      timestamp                                  NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`repository_id`, `user_id`),
    KEY `ix_repository_members_user` (`user_id`),
    KEY `ix_repository_members_permission` (`repository_id`, `permission`),
    CONSTRAINT `fk_repo_members_repo` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_repo_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.repository_members: ~7 rows (approximately)
INSERT IGNORE INTO `repository_members` (`repository_id`, `user_id`, `permission`, `added_at`)
VALUES (1, 1, 'owner', '2026-04-21 15:09:34'),
       (1, 2, 'write', '2026-04-21 15:09:34'),
       (1, 3, 'read', '2026-04-21 15:09:34'),
       (2, 1, 'maintainer', '2026-04-21 15:09:34'),
       (2, 2, 'owner', '2026-03-29 11:03:45'),
       (4, 1, 'owner', '2026-04-18 16:02:20'),
       (5, 1, 'owner', '2026-04-21 15:00:32'),
       (6, 3, 'owner', '2026-04-21 17:43:11');

-- Dumping structure for table phpgit.ssh_keys
CREATE TABLE IF NOT EXISTS `ssh_keys`
(
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id`     int(10) unsigned NOT NULL,
    `title`       varchar(100)     NOT NULL,
    `key_type`    varchar(50)      NOT NULL,
    `public_key`  text             NOT NULL,
    `fingerprint` varchar(100)     NOT NULL,
    `created_at`  timestamp        NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_ssh_keys_fingerprint` (`fingerprint`),
    KEY `ix_ssh_keys_user` (`user_id`),
    CONSTRAINT `fk_ssh_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- Dumping structure for table phpgit.users
CREATE TABLE IF NOT EXISTS `users`
(
    `id`            int(10) unsigned                               NOT NULL AUTO_INCREMENT,
    `username`      varchar(50)                                    NOT NULL,
    `email`         varchar(191)                                   NOT NULL,
    `password`      varchar(255)                                   NOT NULL,
    `display_name`  varchar(100)                                            DEFAULT NULL,
    `role`          enum ('USER','ADMIN','MAINTAINER','MODERATOR') NOT NULL DEFAULT 'USER',
    `status`        enum ('ACTIVE','INACTIVE','SUSPENDED')         NOT NULL DEFAULT 'ACTIVE',
    `bio`           text                                                    DEFAULT NULL,
    `website`       varchar(255)                                            DEFAULT NULL,
    `last_login_at` timestamp                                      NULL     DEFAULT NULL,
    `created_at`    timestamp                                      NOT NULL DEFAULT current_timestamp(),
    `updated_at`    timestamp                                      NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_users_email` (`email`),
    UNIQUE KEY `ux_users_username` (`username`),
    KEY `ix_users_role` (`role`),
    KEY `ix_users_status` (`status`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 9
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Dumping data for table phpgit.users: ~7 rows (approximately)
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password`, `display_name`, `role`, `status`, `bio`, `website`,
                            `last_login_at`, `created_at`, `updated_at`)
VALUES (1, 'admin', 'admin@phpgit.dev', '$2y$12$kyryYUsN06.oUdGxk7yCv.XaolMwOUeGopoccjnySCf3T/w17Mqf6',
        'System Administrator', 'ADMIN', 'ACTIVE', 'Project maintainer', 'https://phpgit.dev', NULL,
        '2026-04-12 17:08:52', '2026-04-21 15:54:25'),
       (2, 'alice', 'alice@phpgit.dev', '$2y$12$bgj9S30gtrU/zjHPbJaHNeN0yh4nnh6CX.h9vGcQ7nYBy6hxipIOe', 'Alice Smith',
        'USER', 'ACTIVE', 'Backend contributor', 'https://alice.dev', NULL, '2026-03-29 11:03:45',
        '2026-03-29 11:03:45'),
       (3, 'demo', 'demo@phpgit.dev', '$2y$12$DB/7b4k2I7aptbBln7hLXeytuoRc5fsCd5euYW2cqDYW8ZQXMTp4y', 'Demo Account',
        'USER', 'ACTIVE', 'Read-only demo account', NULL, NULL, '2026-03-29 11:03:45', '2026-03-29 11:03:45'),
       (4, 'test', 'test@test.com', '$2y$12$skRO6wGN3CceVPLHHINXqOVMaJvvgadImfkaxdsXCiSRyWf5GacMm', NULL, 'USER',
        'ACTIVE', NULL, NULL, NULL, '2026-04-09 18:30:44', '2026-04-09 18:30:44'),
       (5, 'test1', 'test1@test.com', '$2y$12$8dyyT4OcfMO2XuEQBMk65uF6buQamY1RHrjhPNxHtkAnccBrJbz6G', NULL, 'USER',
        'ACTIVE', NULL, NULL, NULL, '2026-04-09 18:33:30', '2026-04-09 18:33:30'),
       (6, 'test2', 'test2@test.com', '$2y$12$0DCnJAdPDuXsJLRbGwjm9uA0As7PORt8Ay3IQK7TQkPNX.uFgSWpe', NULL, 'USER',
        'ACTIVE', NULL, NULL, NULL, '2026-04-09 18:36:19', '2026-04-09 18:36:19'),
       (7, 'test3', 'test3@test.com', '$2y$12$7UHXv.q81qDXF0v.BoY5Wu6CroC8S8AAJo9k1b9NmXZQ3EZCeMgl6', NULL, 'USER',
        'ACTIVE', NULL, NULL, NULL, '2026-04-09 18:37:19', '2026-04-09 18:37:19'),
       (8, 'test5', 'test25@test.com', '$2y$12$g0Zvck/NZ57HuljUPuyP9uxiAqEKaDWFtRdIdoQDbmtcX5lKJud3a', NULL, 'USER',
        'ACTIVE', NULL, NULL, NULL, '2026-04-09 18:41:19', '2026-04-09 18:41:19');

/*!40103 SET TIME_ZONE = IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE = IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS = IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES = IFNULL(@OLD_SQL_NOTES, 1) */;