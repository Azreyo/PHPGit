-- PHPGit SQL schema only (v4)
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
    stars            INT UNSIGNED                        DEFAULT 0,
    forks            INT UNSIGNED                        DEFAULT 0,
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
    author_user_id   INT UNSIGNED            NOT NULL,
    assignee_user_id INT UNSIGNED                     DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS inbox
(
    id         INT UNSIGNED                        NOT NULL AUTO_INCREMENT,
    username   VARCHAR(50)                         NOT NULL,
    email      VARCHAR(191)                        NOT NULL,
    subject    VARCHAR(255)                        NOT NULL,
    body       TEXT                                NOT NULL,
    status     ENUM ('new', 'replied', 'archived') NOT NULL DEFAULT 'new',
    unread     TINYINT(1)                          NOT NULL DEFAULT 1,
    created_at TIMESTAMP                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX ix_inbox_status (status),
    INDEX ix_inbox_unread (unread),
    INDEX ix_inbox_created (created_at)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS log
(
    id       INT UNSIGNED                                                      NOT NULL AUTO_INCREMENT,
    level    ENUM ('Debug', 'Info', 'Warning', 'Error', 'Critical', 'Unknown') NOT NULL DEFAULT 'Unknown',
    message  TEXT                                                              NOT NULL,
    security TINYINT(1)                                                        NOT NULL DEFAULT 0,
    ip       TEXT                                                                       DEFAULT NULL,
    log_time TIMESTAMP                                                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX ix_log_level_created (level, log_time),
    INDEX ix_log_security_created (security, log_time)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS programming_languages
(
    lang  VARCHAR(50)  NOT NULL,
    view  VARCHAR(100) NOT NULL,
    color CHAR(7)      NOT NULL,
    PRIMARY KEY (lang)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

