CREATE TABLE IF NOT EXISTS personal_access_tokens
(
    id           INT(10) UNSIGNED       NOT NULL AUTO_INCREMENT,
    user_id      INT(10) UNSIGNED       NOT NULL,
    name         VARCHAR(100)           NOT NULL,
    token_prefix VARCHAR(24)            NOT NULL,
    token_hash   CHAR(64)               NOT NULL,
    scope        ENUM ('read', 'write') NOT NULL DEFAULT 'read',
    expires_at   TIMESTAMP              NULL     DEFAULT NULL,
    last_used_at TIMESTAMP              NULL     DEFAULT NULL,
    revoked_at   TIMESTAMP              NULL     DEFAULT NULL,
    created_at   TIMESTAMP              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_personal_access_tokens_hash (token_hash),
    INDEX ix_personal_access_tokens_prefix (token_prefix),
    INDEX ix_personal_access_tokens_user (user_id),
    CONSTRAINT fk_personal_access_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
