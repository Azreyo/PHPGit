-- Login credentials for local testing:
-- admin@phpgit.dev / Admin1234!
-- alice@phpgit.dev / User12345!
-- demo@phpgit.dev  / Demo12345!

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