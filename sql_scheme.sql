-- PHPGit SQL schema + fake data
-- Compatible with current auth queries in src/pages/login.php and src/pages/register.php
-- Target: MySQL 8+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users
(
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username   VARCHAR(50)  NOT NULL,
    email      VARCHAR(191) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(20)  NOT NULL DEFAULT 'USER',
    bio        VARCHAR(255)          DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_users_email (email),
    UNIQUE KEY ux_users_username (username),
    KEY ix_users_role (role)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS bio VARCHAR(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS repositories
(
    id             INT UNSIGNED               NOT NULL AUTO_INCREMENT,
    owner_user_id  INT                        NOT NULL,
    name           VARCHAR(100)               NOT NULL,
    slug           VARCHAR(150)               NOT NULL,
    description    TEXT                                DEFAULT NULL,
    visibility     ENUM ('public', 'private') NOT NULL DEFAULT 'public',
    default_branch VARCHAR(100)               NOT NULL DEFAULT 'main',
    created_at     TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_repositories_owner_slug (owner_user_id, slug),
    KEY ix_repositories_visibility (visibility),
    CONSTRAINT fk_repositories_owner FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repository_members
(
    repository_id INT UNSIGNED                                  NOT NULL,
    user_id       INT                                           NOT NULL,
    permission    ENUM ('owner', 'maintainer', 'write', 'read') NOT NULL DEFAULT 'read',
    added_at      TIMESTAMP                                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (repository_id, user_id),
    KEY ix_repository_members_user (user_id),
    CONSTRAINT fk_repo_members_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_repo_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS branches
(
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id    INT UNSIGNED NOT NULL,
    name             VARCHAR(100) NOT NULL,
    head_commit_hash CHAR(40)              DEFAULT NULL,
    is_protected     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_branches_repo_name (repository_id, name),
    CONSTRAINT fk_branches_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commits
(
    hash              CHAR(40)     NOT NULL,
    repository_id     INT UNSIGNED NOT NULL,
    branch_id         INT UNSIGNED NOT NULL,
    parent_hash       CHAR(40)              DEFAULT NULL,
    merge_parent_hash CHAR(40)              DEFAULT NULL,
    author_user_id    INT          NOT NULL,
    title             VARCHAR(120) NOT NULL,
    message           TEXT                  DEFAULT NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (hash),
    KEY ix_commits_repo_created (repository_id, created_at),
    KEY ix_commits_branch_created (branch_id, created_at),
    CONSTRAINT fk_commits_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_commits_branch FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE,
    CONSTRAINT fk_commits_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_commits_parent FOREIGN KEY (parent_hash) REFERENCES commits (hash) ON DELETE SET NULL,
    CONSTRAINT fk_commits_merge_parent FOREIGN KEY (merge_parent_hash) REFERENCES commits (hash) ON DELETE SET NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commit_files
(
    id           BIGINT UNSIGNED                            NOT NULL AUTO_INCREMENT,
    commit_hash  CHAR(40)                                   NOT NULL,
    file_path    VARCHAR(500)                               NOT NULL,
    change_type  ENUM ('ADD', 'MODIFY', 'DELETE', 'RENAME') NOT NULL,
    file_content MEDIUMTEXT                                          DEFAULT NULL,
    created_at   TIMESTAMP                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_commit_files_hash_path (commit_hash, file_path),
    CONSTRAINT fk_commit_files_commit FOREIGN KEY (commit_hash) REFERENCES commits (hash) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issues
(
    id               INT UNSIGNED            NOT NULL AUTO_INCREMENT,
    repository_id    INT UNSIGNED            NOT NULL,
    author_user_id   INT                     NOT NULL,
    assignee_user_id INT                              DEFAULT NULL,
    title            VARCHAR(160)            NOT NULL,
    body             TEXT                             DEFAULT NULL,
    status           ENUM ('open', 'closed') NOT NULL DEFAULT 'open',
    created_at       TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at        TIMESTAMP               NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    KEY ix_issues_repo_status (repository_id, status),
    CONSTRAINT fk_issues_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_issues_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_issues_assignee FOREIGN KEY (assignee_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pull_requests
(
    id             INT UNSIGNED                      NOT NULL AUTO_INCREMENT,
    repository_id  INT UNSIGNED                      NOT NULL,
    author_user_id INT                               NOT NULL,
    from_branch_id INT UNSIGNED                      NOT NULL,
    to_branch_id   INT UNSIGNED                      NOT NULL,
    title          VARCHAR(160)                      NOT NULL,
    body           TEXT                                       DEFAULT NULL,
    status         ENUM ('open', 'merged', 'closed') NOT NULL DEFAULT 'open',
    created_at     TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    merged_at      TIMESTAMP                         NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    KEY ix_pull_requests_repo_status (repository_id, status),
    CONSTRAINT fk_pr_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pr_from_branch FOREIGN KEY (from_branch_id) REFERENCES branches (id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_to_branch FOREIGN KEY (to_branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO users (id, username, email, password, role, bio)
VALUES (1, 'admin', 'admin@phpgit.dev', '$2y$12$u7Crv3C8JbbQ2IuRDBzyfOnsJ5by1Vo7YLt51pQT8jUQEdBhz4VNC', 'ADMIN',
        'Project maintainer'),
       (2, 'alice', 'alice@phpgit.dev', '$2y$12$bgj9S30gtrU/zjHPbJaHNeN0yh4nnh6CX.h9vGcQ7nYBy6hxipIOe', 'USER',
        'Backend contributor'),
       (3, 'demo', 'demo@phpgit.dev', '$2y$12$DB/7b4k2I7aptbBln7hLXeytuoRc5fsCd5euYW2cqDYW8ZQXMTp4y', 'USER',
        'Read-only demo account')
ON DUPLICATE KEY UPDATE username = VALUES(username),
                        role     = VALUES(role),
                        bio      = VALUES(bio);

INSERT INTO repositories (id, owner_user_id, name, slug, description, visibility, default_branch)
VALUES (1, 1, 'phpgit-core', 'phpgit-core', 'Main PHPGit platform repository', 'public', 'main'),
       (2, 2, 'bootstrap-theme', 'bootstrap-theme', 'Theme experiments for frontend', 'public', 'main')
ON DUPLICATE KEY UPDATE name           = VALUES(name),
                        description    = VALUES(description),
                        visibility     = VALUES(visibility),
                        default_branch = VALUES(default_branch);

INSERT INTO repository_members (repository_id, user_id, permission)
VALUES (1, 1, 'owner'),
       (1, 2, 'write'),
       (1, 3, 'read'),
       (2, 2, 'owner'),
       (2, 1, 'maintainer')
ON DUPLICATE KEY UPDATE permission = VALUES(permission);

INSERT INTO branches (id, repository_id, name, head_commit_hash, is_protected)
VALUES (1, 1, 'main', '1111111111111111111111111111111111111111', 1),
       (2, 1, 'feature/auth-hardening', '2222222222222222222222222222222222222222', 0),
       (3, 2, 'main', '3333333333333333333333333333333333333333', 1)
ON DUPLICATE KEY UPDATE head_commit_hash = VALUES(head_commit_hash),
                        is_protected     = VALUES(is_protected);

INSERT INTO commits (hash, repository_id, branch_id, parent_hash, merge_parent_hash, author_user_id, title, message)
VALUES ('1111111111111111111111111111111111111111', 1, 1, NULL, NULL, 1, 'Initial project scaffold',
        'Bootstrap front controller and auth pages.'),
       ('2222222222222222222222222222222222222222', 1, 2, '1111111111111111111111111111111111111111', NULL, 2,
        'Harden login flow', 'Add stronger validation and session handling.'),
       ('3333333333333333333333333333333333333333', 2, 3, NULL, NULL, 2, 'Initial theme setup',
        'Create CSS structure and theme switch script.')
ON DUPLICATE KEY UPDATE title   = VALUES(title),
                        message = VALUES(message);

INSERT INTO commit_files (commit_hash, file_path, change_type, file_content)
VALUES ('1111111111111111111111111111111111111111', 'src/Index.php', 'ADD', '<?php\n// initial entry point\n'),
       ('2222222222222222222222222222222222222222', 'src/pages/login.php', 'MODIFY',
        '<?php\n// improved login validation\n'),
       ('3333333333333333333333333333333333333333', 'src/assets/style.css', 'ADD', '/* base theme styles */')
ON DUPLICATE KEY UPDATE change_type  = VALUES(change_type),
                        file_content = VALUES(file_content);

INSERT INTO issues (id, repository_id, author_user_id, assignee_user_id, title, body, status)
VALUES (1, 1, 3, 2, 'Login error on invalid CSRF token', 'Reproducible when session expires before submit.', 'open'),
       (2, 2, 1, 2, 'Dark mode contrast improvement', 'Buttons in dark mode need higher contrast.', 'open')
ON DUPLICATE KEY UPDATE assignee_user_id = VALUES(assignee_user_id),
                        status           = VALUES(status);

INSERT INTO pull_requests (id, repository_id, author_user_id, from_branch_id, to_branch_id, title, body, status)
VALUES (1, 1, 2, 2, 1, 'Improve auth hardening', 'Adds stricter checks and cleaner redirects.', 'open')
ON DUPLICATE KEY UPDATE status = VALUES(status),
                        title  = VALUES(title);

-- Login credentials for local testing:
-- admin@phpgit.dev / Admin1234!
-- alice@phpgit.dev / User12345!
-- demo@phpgit.dev  / Demo12345!

