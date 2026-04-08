-- PHPGit SQL schema + fake data (v4)
-- Optimized for MySQL 8+ / MariaDB 10.11+
--
-- ARCHITECTURE NOTE:
-- To keep the database efficient, we only store metadata and features NOT provided by Git.
-- Versioning data (blobs, trees, commits, branches) is read directly from .git files
-- on the filesystem using PHP's filesystem functions or git binary calls.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users
(
    id            INT UNSIGNED                                      NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)                                       NOT NULL,
    email         VARCHAR(191)                                      NOT NULL,
    password      VARCHAR(255)                                      NOT NULL,
    display_name  VARCHAR(100)                                               DEFAULT NULL,
    role          ENUM ('USER', 'ADMIN', 'MAINTAINER', 'MODERATOR') NOT NULL DEFAULT 'USER',
    status        ENUM ('ACTIVE', 'INACTIVE', 'SUSPENDED')          NOT NULL DEFAULT 'ACTIVE',
    bio           TEXT                                                       DEFAULT NULL,
    website       VARCHAR(255)                                               DEFAULT NULL,
    last_login_at TIMESTAMP                                                  DEFAULT NULL,
    created_at    TIMESTAMP                                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP                                         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_users_email (email),
    UNIQUE KEY ux_users_username (username),
    INDEX ix_users_role (role),
    INDEX ix_users_status (status)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repositories
(
    id               INT UNSIGNED               NOT NULL AUTO_INCREMENT,
    owner_user_id    INT UNSIGNED               NOT NULL,
    repo_name        VARCHAR(100)               NOT NULL,
    slug             VARCHAR(150)               NOT NULL,
    repo_description TEXT                                DEFAULT NULL,
    visibility       ENUM ('public', 'private') NOT NULL DEFAULT 'public',
    default_branch   VARCHAR(100)               NOT NULL DEFAULT 'main',
    stars INT UNSIGNED DEFAULT 0,
    forks INT UNSIGNED DEFAULT 0,
    lang             VARCHAR(50)                         DEFAULT NULL,
    created_at       TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_repositories_owner_slug (owner_user_id, slug),
    INDEX ix_repositories_visibility (visibility),
    INDEX ix_repositories_lang (lang),
    INDEX ix_repositories_repo_name (repo_name),
    INDEX ix_repositories_popular (visibility, stars DESC),
    CONSTRAINT fk_repositories_owner FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repository_members
(
    repository_id INT UNSIGNED                                  NOT NULL,
    user_id       INT UNSIGNED                                  NOT NULL,
    permission    ENUM ('owner', 'maintainer', 'write', 'read') NOT NULL DEFAULT 'read',
    added_at      TIMESTAMP                                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (repository_id, user_id),
    INDEX ix_repository_members_user (user_id),
    INDEX ix_repository_members_permission (repository_id, permission),
    CONSTRAINT fk_repo_members_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_repo_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issues
(
    id               INT UNSIGNED            NOT NULL AUTO_INCREMENT,
    repository_id    INT UNSIGNED            NOT NULL,
    author_user_id   INT UNSIGNED NOT NULL,
    assignee_user_id INT UNSIGNED DEFAULT NULL,
    title            VARCHAR(160)            NOT NULL,
    body             TEXT                             DEFAULT NULL,
    status           ENUM ('open', 'closed') NOT NULL DEFAULT 'open',
    created_at       TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at        TIMESTAMP               NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX ix_issues_repo_status (repository_id, status),
    INDEX ix_issues_repo_created (repository_id, created_at),
    INDEX ix_issues_author (author_user_id),
    INDEX ix_issues_assignee (assignee_user_id),
    CONSTRAINT fk_issues_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_issues_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_issues_assignee FOREIGN KEY (assignee_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pull_requests
(
    id               INT UNSIGNED                      NOT NULL AUTO_INCREMENT,
    repository_id    INT UNSIGNED                      NOT NULL,
    author_user_id   INT UNSIGNED                      NOT NULL,
    from_branch_name VARCHAR(100)                      NOT NULL,
    to_branch_name   VARCHAR(100)                      NOT NULL,
    from_head_hash   CHAR(64)                                   DEFAULT NULL,
    to_head_hash     CHAR(64)                                   DEFAULT NULL,
    title            VARCHAR(160)                      NOT NULL,
    body             TEXT                                       DEFAULT NULL,
    status           ENUM ('open', 'merged', 'closed') NOT NULL DEFAULT 'open',
    created_at       TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    merged_at        TIMESTAMP                         NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX ix_pull_requests_repo_status (repository_id, status),
    INDEX ix_pull_requests_repo_created (repository_id, created_at),
    INDEX ix_pull_requests_author (author_user_id),
    CONSTRAINT fk_pr_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS level
(
    id         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    level      TINYINT UNSIGNED NOT NULL,
    `desc`     VARCHAR(50)               DEFAULT NULL,
    created_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_level_level (level)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS log
(
    id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    level_id   TINYINT UNSIGNED NOT NULL,
    message    TEXT             NOT NULL,
    security   TINYINT(1)       NOT NULL DEFAULT 0,
    ip         TEXT                      DEFAULT NULL,
    log_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX ix_log_level_created (level_id, created_at),
    INDEX ix_log_security_created (security, created_at),
    CONSTRAINT fk_log_level FOREIGN KEY (level_id) REFERENCES level (id) ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

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
                        visibility     = VALUES(visibility),
                        default_branch = VALUES(default_branch);

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

-- Login credentials for local testing:
-- admin@phpgit.dev / Admin1234!
-- alice@phpgit.dev / User12345!
-- demo@phpgit.dev  / Demo12345!

