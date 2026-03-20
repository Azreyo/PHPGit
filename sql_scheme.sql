-- PHPGit SQL schema + fake data (v2)
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

CREATE TABLE IF NOT EXISTS repositories
(
    id             INT UNSIGNED               NOT NULL AUTO_INCREMENT,
  owner_user_id  INT UNSIGNED               NOT NULL,
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

CREATE TABLE IF NOT EXISTS blobs
(
    hash       CHAR(64)     NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    content    MEDIUMTEXT   NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (hash)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commits
(
    hash           CHAR(40)     NOT NULL,
    repository_id  INT UNSIGNED NOT NULL,
    author_user_id INT UNSIGNED NOT NULL,
    title          VARCHAR(120) NOT NULL,
    message        TEXT                  DEFAULT NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (repository_id, hash),
  UNIQUE KEY ux_commits_hash (hash),
    KEY ix_commits_repo_created (repository_id, created_at),
    CONSTRAINT fk_commits_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_commits_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commit_parents
(
    repository_id INT UNSIGNED      NOT NULL,
    commit_hash   CHAR(40)          NOT NULL,
    parent_hash   CHAR(40)          NOT NULL,
    parent_order  TINYINT UNSIGNED  NOT NULL,
    PRIMARY KEY (repository_id, commit_hash, parent_order),
    UNIQUE KEY ux_commit_parents_pair (repository_id, commit_hash, parent_hash),
    KEY ix_commit_parents_parent (repository_id, parent_hash),
    CONSTRAINT fk_cp_commit FOREIGN KEY (repository_id, commit_hash)
        REFERENCES commits (repository_id, hash) ON DELETE CASCADE,
    CONSTRAINT fk_cp_parent FOREIGN KEY (repository_id, parent_hash)
        REFERENCES commits (repository_id, hash) ON DELETE RESTRICT
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
    KEY ix_branches_repo_head (repository_id, head_commit_hash),
    CONSTRAINT fk_branches_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_branches_head_commit FOREIGN KEY (repository_id, head_commit_hash)
        REFERENCES commits (repository_id, hash) ON DELETE RESTRICT
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
    KEY ix_repository_members_user (user_id),
    KEY ix_repository_members_permission (repository_id, permission),
    CONSTRAINT fk_repo_members_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_repo_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commit_files
(
    id           BIGINT UNSIGNED                            NOT NULL AUTO_INCREMENT,
    commit_hash  CHAR(40)                                   NOT NULL,
    file_path    VARCHAR(500)                               NOT NULL,
    previous_path VARCHAR(500)                                       DEFAULT NULL,
    change_type  ENUM ('ADD', 'MODIFY', 'DELETE', 'RENAME') NOT NULL,
    blob_hash    CHAR(64)                                           DEFAULT NULL,
    created_at   TIMESTAMP                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_commit_files_hash_path (commit_hash, file_path),
    KEY ix_commit_files_blob (blob_hash),
    CONSTRAINT fk_commit_files_commit FOREIGN KEY (commit_hash) REFERENCES commits (hash) ON DELETE CASCADE,
    CONSTRAINT fk_commit_files_blob FOREIGN KEY (blob_hash) REFERENCES blobs (hash) ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issues
(
    id               INT UNSIGNED            NOT NULL AUTO_INCREMENT,
    repository_id    INT UNSIGNED            NOT NULL,
  author_user_id   INT UNSIGNED            NOT NULL,
  assignee_user_id INT UNSIGNED                     DEFAULT NULL,
    title            VARCHAR(160)            NOT NULL,
    body             TEXT                             DEFAULT NULL,
    status           ENUM ('open', 'closed') NOT NULL DEFAULT 'open',
    created_at       TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at        TIMESTAMP               NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    KEY ix_issues_repo_status (repository_id, status),
  KEY ix_issues_repo_created (repository_id, created_at),
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
  author_user_id INT UNSIGNED                      NOT NULL,
  from_branch_id INT UNSIGNED                               DEFAULT NULL,
  to_branch_id   INT UNSIGNED                               DEFAULT NULL,
  from_branch_name VARCHAR(100)                    NOT NULL,
  to_branch_name   VARCHAR(100)                    NOT NULL,
  from_head_hash   CHAR(40)                                 DEFAULT NULL,
  to_head_hash     CHAR(40)                                 DEFAULT NULL,
    title          VARCHAR(160)                      NOT NULL,
    body           TEXT                                       DEFAULT NULL,
    status         ENUM ('open', 'merged', 'closed') NOT NULL DEFAULT 'open',
    created_at     TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    merged_at      TIMESTAMP                         NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    KEY ix_pull_requests_repo_status (repository_id, status),
  KEY ix_pull_requests_repo_created (repository_id, created_at),
    CONSTRAINT fk_pr_repo FOREIGN KEY (repository_id) REFERENCES repositories (id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_pr_from_branch FOREIGN KEY (from_branch_id) REFERENCES branches (id) ON DELETE SET NULL,
  CONSTRAINT fk_pr_to_branch FOREIGN KEY (to_branch_id) REFERENCES branches (id) ON DELETE SET NULL
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

INSERT INTO commits (hash, repository_id, author_user_id, title, message)
VALUES ('1111111111111111111111111111111111111111', 1, 1, 'Initial project scaffold',
        'Bootstrap front controller and auth pages.'),
       ('2222222222222222222222222222222222222222', 1, 2,
        'Harden login flow', 'Add stronger validation and session handling.'),
       ('3333333333333333333333333333333333333333', 2, 2, 'Initial theme setup',
        'Create CSS structure and theme switch script.')
ON DUPLICATE KEY UPDATE title   = VALUES(title),
                        message = VALUES(message);

INSERT INTO commit_parents (repository_id, commit_hash, parent_hash, parent_order)
VALUES (1, '2222222222222222222222222222222222222222', '1111111111111111111111111111111111111111', 1)
ON DUPLICATE KEY UPDATE parent_hash = VALUES(parent_hash);

INSERT INTO branches (id, repository_id, name, head_commit_hash, is_protected)
VALUES (1, 1, 'main', '1111111111111111111111111111111111111111', 1),
       (2, 1, 'feature/auth-hardening', '2222222222222222222222222222222222222222', 0),
       (3, 2, 'main', '3333333333333333333333333333333333333333', 1)
ON DUPLICATE KEY UPDATE head_commit_hash = VALUES(head_commit_hash),
      is_protected     = VALUES(is_protected);

INSERT INTO repository_members (repository_id, user_id, permission)
VALUES (1, 1, 'owner'),
       (1, 2, 'write'),
       (1, 3, 'read'),
       (2, 2, 'owner'),
       (2, 1, 'maintainer')
ON DUPLICATE KEY UPDATE permission = VALUES(permission);

INSERT INTO blobs (hash, size_bytes, content)
VALUES ('e7b6b8fbfefdb410d4b9f2f6db3dcb23893fb26ca6e9f11e64d286f9e5fae6e4',
  CHAR_LENGTH('<?php\n// initial entry point\n'),
  '<?php\n// initial entry point\n'),
       ('f9303104693b2a6022f17020003eebf6f2fffd58fb7724cf5f966f95e6ec1604',
  CHAR_LENGTH('<?php\n// improved login validation\n'),
  '<?php\n// improved login validation\n'),
       ('1f9e3719de6f0a4d02237240eaad56d0c0f8ed37249028f0f7e4a56f391d4b96',
  CHAR_LENGTH('/* base theme styles */'),
  '/* base theme styles */')
ON DUPLICATE KEY UPDATE size_bytes = VALUES(size_bytes),
      content    = VALUES(content);

INSERT INTO commit_files (commit_hash, file_path, previous_path, change_type, blob_hash)
VALUES ('1111111111111111111111111111111111111111', 'src/Index.php', NULL, 'ADD',
  'e7b6b8fbfefdb410d4b9f2f6db3dcb23893fb26ca6e9f11e64d286f9e5fae6e4'),
       ('2222222222222222222222222222222222222222', 'src/pages/login.php', NULL, 'MODIFY',
  'f9303104693b2a6022f17020003eebf6f2fffd58fb7724cf5f966f95e6ec1604'),
       ('3333333333333333333333333333333333333333', 'src/assets/style.css', NULL, 'ADD',
  '1f9e3719de6f0a4d02237240eaad56d0c0f8ed37249028f0f7e4a56f391d4b96')
ON DUPLICATE KEY UPDATE change_type = VALUES(change_type),
      blob_hash   = VALUES(blob_hash),
      previous_path = VALUES(previous_path);

INSERT INTO issues (id, repository_id, author_user_id, assignee_user_id, title, body, status)
VALUES (1, 1, 3, 2, 'Login error on invalid CSRF token', 'Reproducible when session expires before submit.', 'open'),
       (2, 2, 1, 2, 'Dark mode contrast improvement', 'Buttons in dark mode need higher contrast.', 'open')
ON DUPLICATE KEY UPDATE assignee_user_id = VALUES(assignee_user_id),
                        status           = VALUES(status);

INSERT INTO pull_requests
    (id, repository_id, author_user_id, from_branch_id, to_branch_id, from_branch_name, to_branch_name,
     from_head_hash, to_head_hash, title, body, status)
VALUES (1, 1, 2, 2, 1, 'feature/auth-hardening', 'main',
  '2222222222222222222222222222222222222222', '1111111111111111111111111111111111111111',
  'Improve auth hardening', 'Adds stricter checks and cleaner redirects.', 'open')
ON DUPLICATE KEY UPDATE status = VALUES(status),
                        title  = VALUES(title);

-- Login credentials for local testing:
-- admin@phpgit.dev / Admin1234!
-- alice@phpgit.dev / User12345!
-- demo@phpgit.dev  / Demo12345!

