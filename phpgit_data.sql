-- Login credentials for local testing:
-- admin@phpgit.dev / Admin1234!
-- alice@phpgit.dev / User12345!
-- demo@phpgit.dev  / Demo12345!
INSERT INTO users (id, username, email, password, role, display_name, bio, website)
VALUES (1, 'admin', 'admin@phpgit.dev', '$2y$12$u7Crv3C8JbbQ2IuRDBzyfOnsJ5by1Vo7YLt51pQT8jUQEdBhz4VNC', 'ADMIN',
        'System Administrator', 'Project maintainer', 'https://phpgit.dev'),
       (2, 'alice', 'alice@phpgit.dev', '$2y$12$bgj9S30gtrU/zjHPbJaHNeN0yh4nnh6CX.h9vGcQ7nYBy6hxipIOe', 'USER',
        'Alice Smith', 'Backend contributor', 'https://alice.dev'),
       (3, 'demo', 'demo@phpgit.dev', '$2y$12$DB/7b4k2I7aptbBln7hLXeytuoRc5fsCd5euYW2cqDYW8ZQXMTp4y', 'USER',
        'Demo Account', 'Read-only demo account', NULL)
ON DUPLICATE KEY UPDATE username     = VALUES(username),
                        role         = VALUES(role),
                        display_name = VALUES(display_name),
                        bio          = VALUES(bio),
                        website      = VALUES(website);

INSERT INTO repositories (id, owner_user_id, repo_name, slug, repo_description, stars, forks, lang, visibility,
                          default_branch)
VALUES (1, 1, 'phpgit-core', 'phpgit-core', 'Main PHPGit platform repository', 255, 190, 'php', 'public', 'main'),
       (2, 2, 'bootstrap-theme', 'bootstrap-theme', 'Theme experiments for frontend', 4980, 2094, 'javascript',
        'public', 'main')
ON DUPLICATE KEY UPDATE repo_name        = VALUES(repo_name),
                        repo_description = VALUES(repo_description),
                        visibility       = VALUES(visibility),
                        default_branch   = VALUES(default_branch);

INSERT INTO repository_members (repository_id, user_id, permission)
VALUES (1, 1, 'owner'),
       (1, 2, 'write'),
       (1, 3, 'read'),
       (2, 2, 'owner'),
       (2, 1, 'maintainer')
ON DUPLICATE KEY UPDATE permission = VALUES(permission);

INSERT INTO issues (id, repository_id, author_user_id, assignee_user_id, title, body, status)
VALUES (1, 1, 3, 2, 'Login error on invalid CSRF token', 'Reproducible when session expires before submit.', 'open'),
       (2, 2, 1, 2, 'Dark mode contrast improvement', 'Buttons in dark mode need higher contrast.', 'open')
ON DUPLICATE KEY UPDATE assignee_user_id = VALUES(assignee_user_id),
                        status           = VALUES(status);

INSERT INTO pull_requests
(id, repository_id, author_user_id, from_branch_name, to_branch_name,
 from_head_hash, to_head_hash, title, body, status)
VALUES (1, 1, 2, 'feature/auth-hardening', 'main',
        '2222222222222222222222222222222222222222222222222222222222222222',
        '1111111111111111111111111111111111111111111111111111111111111111',
        'Improve auth hardening', 'Adds stricter checks and cleaner redirects.', 'open')
ON DUPLICATE KEY UPDATE status = VALUES(status),
                        title  = VALUES(title);

INSERT INTO level (id, level, `desc`)
VALUES (1, 1, 'Debug'),
       (2, 2, 'Info'),
       (3, 3, 'Warning'),
       (4, 4, 'Error'),
       (5, 5, 'Critical')
ON DUPLICATE KEY UPDATE `desc` = VALUES(`desc`);