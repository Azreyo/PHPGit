-- PHPGit seed data only
-- Extracted from sql_scheme.sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

INSERT IGNORE INTO inbox (id, username, email, subject, body, created_at, unread, status)
VALUES (1, 'asdas', 'test@test.com', 'dasd', 'dasdasdsa', '2026-04-18 10:49:12', 0, 'new');

INSERT IGNORE INTO users (id, username, email, password, display_name, role, status, bio, website, last_login_at,
                          created_at, updated_at)
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

INSERT IGNORE INTO repositories (id, owner_user_id, repo_name, slug, repo_description, visibility, default_branch,
                                 stars, forks, lang, created_at, updated_at)
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

INSERT IGNORE INTO repository_members (repository_id, user_id, permission, added_at)
VALUES (1, 1, 'owner', '2026-04-21 15:09:34'),
       (1, 2, 'write', '2026-04-21 15:09:34'),
       (1, 3, 'read', '2026-04-21 15:09:34'),
       (2, 1, 'maintainer', '2026-04-21 15:09:34'),
       (2, 2, 'owner', '2026-03-29 11:03:45'),
       (4, 1, 'owner', '2026-04-18 16:02:20'),
       (5, 1, 'owner', '2026-04-21 15:00:32'),
       (6, 3, 'owner', '2026-04-21 17:43:11');

INSERT IGNORE INTO issues (id, repository_id, author_user_id, assignee_user_id, title, body, status, created_at,
                           closed_at)
VALUES (1, 1, 3, 2, 'Login error on invalid CSRF token', 'Reproducible when session expires before submit.', 'open',
        '2026-04-21 15:09:34', NULL),
       (2, 2, 1, 2, 'Dark mode contrast improvement', 'Buttons in dark mode need higher contrast.', 'open',
        '2026-04-21 15:09:34', NULL);

INSERT IGNORE INTO pull_requests (id, repository_id, author_user_id, from_branch_name, to_branch_name, from_head_hash,
                                  to_head_hash, title, body, status, created_at, merged_at)
VALUES (1, 1, 2, 'feature/auth-hardening', 'main', '2222222222222222222222222222222222222222222222222222222222222222',
        '1111111111111111111111111111111111111111111111111111111111111111', 'Improve auth hardening',
        'Adds stricter checks and cleaner redirects.', 'open', '2026-04-21 15:09:34', NULL);

SET FOREIGN_KEY_CHECKS = 1;
