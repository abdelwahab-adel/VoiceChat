-- =====================================================================
-- VoiceChat — Database Schema
-- MySQL 5.7+ / 8.0+
-- Charset: utf8mb4
-- Collation: utf8mb4_unicode_ci
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =====================================================================
-- 1. USERS
-- =====================================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36)        NOT NULL,
  `username`          VARCHAR(50)     NOT NULL,
  `email`             VARCHAR(150)    NOT NULL,
  `phone`             VARCHAR(30)             DEFAULT NULL,
  `password`          VARCHAR(255)    NOT NULL,
  `display_name`      VARCHAR(100)             DEFAULT NULL,
  `bio`               VARCHAR(500)             DEFAULT NULL,
  `avatar`            VARCHAR(500)             DEFAULT NULL,
  `cover`             VARCHAR(500)             DEFAULT NULL,
  `gender`            ENUM('male','female','other') DEFAULT NULL,
  `birthdate`         DATE                     DEFAULT NULL,
  `country`           VARCHAR(80)              DEFAULT NULL,
  `city`              VARCHAR(80)              DEFAULT NULL,
  `language`          VARCHAR(20)              DEFAULT 'en',
  `coins`             BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `xp`                INT UNSIGNED    NOT NULL DEFAULT 0,
  `level`             INT UNSIGNED    NOT NULL DEFAULT 1,
  `vip_level`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `status`            ENUM('active','suspended','banned','pending','deleted') NOT NULL DEFAULT 'active',
  `role`              ENUM('user','moderator','admin','superadmin') NOT NULL DEFAULT 'user',
  `email_verified_at` DATETIME                 DEFAULT NULL,
  `phone_verified_at` DATETIME                 DEFAULT NULL,
  `last_login_at`     DATETIME                 DEFAULT NULL,
  `last_login_ip`     VARCHAR(45)              DEFAULT NULL,
  `online_status`     ENUM('online','away','busy','offline') NOT NULL DEFAULT 'offline',
  `last_seen_at`      DATETIME                 DEFAULT NULL,
  `is_verified`       TINYINT(1)      NOT NULL DEFAULT 0,
  `is_featured`       TINYINT(1)      NOT NULL DEFAULT 0,
  `settings`          JSON                     DEFAULT NULL,
  `social_links`      JSON                     DEFAULT NULL,
  `remember_token`    VARCHAR(100)             DEFAULT NULL,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_uuid`   (`uuid`),
  UNIQUE KEY `uniq_users_username` (`username`),
  UNIQUE KEY `uniq_users_email`  (`email`),
  KEY `idx_users_status`     (`status`),
  KEY `idx_users_online`     (`online_status`,`last_seen_at`),
  KEY `idx_users_level`      (`level` DESC, `xp` DESC),
  KEY `idx_users_vip`        (`vip_level` DESC),
  KEY `idx_users_last_login` (`last_login_at` DESC),
  KEY `idx_users_created`    (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. AGENCIES
-- =====================================================================
DROP TABLE IF EXISTS `agencies`;
CREATE TABLE `agencies` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`         CHAR(36)        NOT NULL,
  `name`         VARCHAR(120)    NOT NULL,
  `slug`         VARCHAR(140)    NOT NULL,
  `description`  TEXT                     DEFAULT NULL,
  `logo`         VARCHAR(500)             DEFAULT NULL,
  `cover`        VARCHAR(500)             DEFAULT NULL,
  `owner_id`     BIGINT UNSIGNED NOT NULL,
  `country`      VARCHAR(80)              DEFAULT NULL,
  `level`        INT UNSIGNED    NOT NULL DEFAULT 1,
  `xp`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `members_count`INT UNSIGNED    NOT NULL DEFAULT 0,
  `rooms_count`  INT UNSIGNED    NOT NULL DEFAULT 0,
  `status`       ENUM('active','suspended','banned') NOT NULL DEFAULT 'active',
  `verified`     TINYINT(1)      NOT NULL DEFAULT 0,
  `settings`     JSON                     DEFAULT NULL,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_agencies_uuid`  (`uuid`),
  UNIQUE KEY `uniq_agencies_slug`  (`slug`),
  UNIQUE KEY `uniq_agencies_name`  (`name`),
  KEY `idx_agencies_owner`  (`owner_id`),
  KEY `idx_agencies_status` (`status`),
  KEY `idx_agencies_level`  (`level` DESC, `xp` DESC),
  CONSTRAINT `fk_agencies_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. AGENCY MEMBERS
-- =====================================================================
DROP TABLE IF EXISTS `agency_members`;
CREATE TABLE `agency_members` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`  BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `role`       ENUM('owner','admin','moderator','member') NOT NULL DEFAULT 'member',
  `joined_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at`    DATETIME                 DEFAULT NULL,
  `status`     ENUM('active','left','removed','pending') NOT NULL DEFAULT 'active',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_agency_user` (`agency_id`,`user_id`),
  KEY `idx_agency_members_status` (`status`),
  CONSTRAINT `fk_agency_members_agency` FOREIGN KEY (`agency_id`) REFERENCES `agencies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agency_members_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. AGENCY JOIN REQUESTS
-- =====================================================================
DROP TABLE IF EXISTS `agency_join_requests`;
CREATE TABLE `agency_join_requests` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`  BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `message`    VARCHAR(500)             DEFAULT NULL,
  `status`     ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `reviewed_by` BIGINT UNSIGNED         DEFAULT NULL,
  `reviewed_at` DATETIME                DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ajr_agency` (`agency_id`),
  KEY `idx_ajr_user`   (`user_id`),
  KEY `idx_ajr_status` (`status`),
  CONSTRAINT `fk_ajr_agency` FOREIGN KEY (`agency_id`) REFERENCES `agencies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ajr_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. ROOMS (Voice Rooms)
-- =====================================================================
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`            CHAR(36)        NOT NULL,
  `name`            VARCHAR(120)    NOT NULL,
  `slug`            VARCHAR(150)    NOT NULL,
  `description`     TEXT                     DEFAULT NULL,
  `cover`           VARCHAR(500)             DEFAULT NULL,
  `owner_id`        BIGINT UNSIGNED NOT NULL,
  `agency_id`       BIGINT UNSIGNED          DEFAULT NULL,
  `type`            ENUM('public','private','password','agency') NOT NULL DEFAULT 'public',
  `password`        VARCHAR(255)             DEFAULT NULL,
  `language`        VARCHAR(20)              DEFAULT 'en',
  `country`         VARCHAR(80)              DEFAULT NULL,
  `category`        VARCHAR(60)              DEFAULT 'general',
  `tags`            JSON                     DEFAULT NULL,
  `max_seats`       TINYINT UNSIGNED NOT NULL DEFAULT 8,
  `max_listeners`   INT UNSIGNED    NOT NULL DEFAULT 0,
  `current_listeners` INT UNSIGNED  NOT NULL DEFAULT 0,
  `mic_count`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_locked`       TINYINT(1)      NOT NULL DEFAULT 0,
  `is_recorded`     TINYINT(1)      NOT NULL DEFAULT 0,
  `is_featured`     TINYINT(1)      NOT NULL DEFAULT 0,
  `auto_mic_accept` TINYINT(1)      NOT NULL DEFAULT 0,
  `background_music` VARCHAR(255)            DEFAULT NULL,
  `status`          ENUM('active','paused','closed','banned') NOT NULL DEFAULT 'active',
  `started_at`      DATETIME                 DEFAULT NULL,
  `ended_at`        DATETIME                 DEFAULT NULL,
  `total_time`      INT UNSIGNED    NOT NULL DEFAULT 0,
  `settings`        JSON                     DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_rooms_uuid` (`uuid`),
  UNIQUE KEY `uniq_rooms_slug` (`slug`),
  KEY `idx_rooms_owner`  (`owner_id`),
  KEY `idx_rooms_agency` (`agency_id`),
  KEY `idx_rooms_type`   (`type`,`status`),
  KEY `idx_rooms_status` (`status`),
  KEY `idx_rooms_featured` (`is_featured`,`status`),
  KEY `idx_rooms_category` (`category`),
  CONSTRAINT `fk_rooms_owner`  FOREIGN KEY (`owner_id`)  REFERENCES `users`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_rooms_agency` FOREIGN KEY (`agency_id`) REFERENCES `agencies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. ROOM MODERATORS
-- =====================================================================
DROP TABLE IF EXISTS `room_moderators`;
CREATE TABLE `room_moderators` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `granted_by` BIGINT UNSIGNED NOT NULL,
  `permissions` JSON                    DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_room_mod` (`room_id`,`user_id`),
  CONSTRAINT `fk_room_mod_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_mod_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. ROOM PARTICIPANTS (currently in the room)
-- =====================================================================
DROP TABLE IF EXISTS `room_participants`;
CREATE TABLE `room_participants` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `seat_index` TINYINT                DEFAULT NULL,
  `role`       ENUM('owner','admin','moderator','speaker','listener') NOT NULL DEFAULT 'listener',
  `is_muted`   TINYINT(1)      NOT NULL DEFAULT 0,
  `is_hand_raised` TINYINT(1)   NOT NULL DEFAULT 0,
  `is_locked`  TINYINT(1)      NOT NULL DEFAULT 0,
  `joined_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at`    DATETIME                 DEFAULT NULL,
  `last_active_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `connection_id` VARCHAR(64)           DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_room_user_active` (`room_id`,`user_id`,`left_at`),
  KEY `idx_rp_seat`     (`room_id`,`seat_index`),
  KEY `idx_rp_user`     (`user_id`),
  KEY `idx_rp_active`   (`room_id`,`left_at`),
  CONSTRAINT `fk_rp_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. ROOM INVITES
-- =====================================================================
DROP TABLE IF EXISTS `room_invites`;
CREATE TABLE `room_invites` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    BIGINT UNSIGNED NOT NULL,
  `inviter_id` BIGINT UNSIGNED NOT NULL,
  `invitee_id` BIGINT UNSIGNED NOT NULL,
  `status`     ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
  `expires_at` DATETIME                 DEFAULT NULL,
  `responded_at` DATETIME               DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_room_invite` (`room_id`,`invitee_id`,`status`),
  KEY `idx_ri_invitee` (`invitee_id`,`status`),
  CONSTRAINT `fk_ri_room`    FOREIGN KEY (`room_id`)    REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_inviter` FOREIGN KEY (`inviter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_invitee` FOREIGN KEY (`invitee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9. GIFTS
-- =====================================================================
DROP TABLE IF EXISTS `gifts`;
CREATE TABLE `gifts` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)     NOT NULL,
  `slug`        VARCHAR(100)    NOT NULL,
  `description` VARCHAR(255)             DEFAULT NULL,
  `image`       VARCHAR(500)             DEFAULT NULL,
  `animation`   VARCHAR(500)             DEFAULT NULL,
  `category`    VARCHAR(50)              DEFAULT 'general',
  `price_coins` INT UNSIGNED    NOT NULL DEFAULT 0,
  `rarity`      ENUM('common','rare','epic','legendary','mythic') NOT NULL DEFAULT 'common',
  `is_animated` TINYINT(1)      NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`  INT             NOT NULL DEFAULT 0,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_gifts_slug` (`slug`),
  KEY `idx_gifts_active` (`is_active`,`sort_order`),
  KEY `idx_gifts_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 10. GIFT TRANSACTIONS
-- =====================================================================
DROP TABLE IF EXISTS `gift_transactions`;
CREATE TABLE `gift_transactions` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gift_id`        BIGINT UNSIGNED NOT NULL,
  `sender_id`      BIGINT UNSIGNED NOT NULL,
  `receiver_id`    BIGINT UNSIGNED NOT NULL,
  `room_id`        BIGINT UNSIGNED          DEFAULT NULL,
  `agency_id`      BIGINT UNSIGNED          DEFAULT NULL,
  `quantity`       INT UNSIGNED    NOT NULL DEFAULT 1,
  `coins_total`    INT UNSIGNED    NOT NULL,
  `message`        VARCHAR(255)             DEFAULT NULL,
  `is_anonymous`   TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gift_tx_sender`   (`sender_id`,`created_at` DESC),
  KEY `idx_gift_tx_receiver` (`receiver_id`,`created_at` DESC),
  KEY `idx_gift_tx_room`     (`room_id`),
  KEY `idx_gift_tx_agency`   (`agency_id`),
  CONSTRAINT `fk_gift_tx_gift`     FOREIGN KEY (`gift_id`)     REFERENCES `gifts`(`id`)     ON DELETE RESTRICT,
  CONSTRAINT `fk_gift_tx_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_gift_tx_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_gift_tx_room`     FOREIGN KEY (`room_id`)     REFERENCES `rooms`(`id`)     ON DELETE SET NULL,
  CONSTRAINT `fk_gift_tx_agency`   FOREIGN KEY (`agency_id`)   REFERENCES `agencies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 11. COIN TRANSACTIONS (purchases / earnings / spends)
-- =====================================================================
DROP TABLE IF EXISTS `coin_transactions`;
CREATE TABLE `coin_transactions` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       ENUM('purchase','gift_sent','gift_received','reward','refund','admin_adjust','daily_bonus','withdraw') NOT NULL,
  `amount`     BIGINT          NOT NULL,
  `balance_after` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `reference`  VARCHAR(120)             DEFAULT NULL,
  `description` VARCHAR(255)            DEFAULT NULL,
  `metadata`   JSON                     DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_coin_tx_user` (`user_id`,`created_at` DESC),
  KEY `idx_coin_tx_type` (`type`),
  CONSTRAINT `fk_coin_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 12. FOLLOWS
-- =====================================================================
DROP TABLE IF EXISTS `follows`;
CREATE TABLE `follows` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `follower_id` BIGINT UNSIGNED NOT NULL,
  `following_id`BIGINT UNSIGNED NOT NULL,
  `is_close_friend` TINYINT(1) NOT NULL DEFAULT 0,
  `is_notifications` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_follow` (`follower_id`,`following_id`),
  KEY `idx_following` (`following_id`),
  CONSTRAINT `fk_follow_follower`  FOREIGN KEY (`follower_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_follow_following` FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 13. FRIENDS (mutual)
-- =====================================================================
DROP TABLE IF EXISTS `friends`;
CREATE TABLE `friends` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `friend_id`  BIGINT UNSIGNED NOT NULL,
  `status`     ENUM('pending','accepted','blocked') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` DATETIME                 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_friend` (`user_id`,`friend_id`),
  KEY `idx_friend_status` (`user_id`,`status`),
  CONSTRAINT `fk_friend_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_friend_friend` FOREIGN KEY (`friend_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 14. BLOCKS
-- =====================================================================
DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `blocked_id` BIGINT UNSIGNED NOT NULL,
  `reason`     VARCHAR(255)             DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_block` (`user_id`,`blocked_id`),
  CONSTRAINT `fk_block_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_block_blocked` FOREIGN KEY (`blocked_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 15. REPORTS
-- =====================================================================
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` BIGINT UNSIGNED NOT NULL,
  `target_type` ENUM('user','room','agency','message','gift') NOT NULL,
  `target_id`   BIGINT UNSIGNED NOT NULL,
  `reason`      VARCHAR(100)    NOT NULL,
  `description` TEXT                     DEFAULT NULL,
  `status`      ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `reviewed_by` BIGINT UNSIGNED          DEFAULT NULL,
  `reviewed_at` DATETIME                 DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_target` (`target_type`,`target_id`),
  KEY `idx_reports_status` (`status`),
  KEY `idx_reports_reporter` (`reporter_id`),
  CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 16. MESSAGES (private chat)
-- =====================================================================
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id`   BIGINT UNSIGNED NOT NULL,
  `receiver_id` BIGINT UNSIGNED NOT NULL,
  `type`        ENUM('text','image','voice','video','file','gift','system') NOT NULL DEFAULT 'text',
  `content`     TEXT                     DEFAULT NULL,
  `media_url`   VARCHAR(500)             DEFAULT NULL,
  `metadata`    JSON                     DEFAULT NULL,
  `reply_to_id` BIGINT UNSIGNED          DEFAULT NULL,
  `is_read`     TINYINT(1)      NOT NULL DEFAULT 0,
  `read_at`     DATETIME                 DEFAULT NULL,
  `is_deleted_by_sender`   TINYINT(1) NOT NULL DEFAULT 0,
  `is_deleted_by_receiver` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_pair`  (`sender_id`,`receiver_id`,`created_at` DESC),
  KEY `idx_msg_recv`  (`receiver_id`,`is_read`),
  CONSTRAINT `fk_msg_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 17. CONVERSATIONS (latest message per pair)
-- =====================================================================
DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_one_id`   BIGINT UNSIGNED NOT NULL,
  `user_two_id`   BIGINT UNSIGNED NOT NULL,
  `last_message_id` BIGINT UNSIGNED         DEFAULT NULL,
  `user_one_unread` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_two_unread` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_one_typing` TINYINT(1)   NOT NULL DEFAULT 0,
  `user_two_typing` TINYINT(1)   NOT NULL DEFAULT 0,
  `user_one_deleted_at` DATETIME  DEFAULT NULL,
  `user_two_deleted_at` DATETIME  DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_conversation` (`user_one_id`,`user_two_id`),
  KEY `idx_conv_user_two` (`user_two_id`),
  CONSTRAINT `fk_conv_user_one` FOREIGN KEY (`user_one_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_user_two` FOREIGN KEY (`user_two_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 18. NOTIFICATIONS
-- =====================================================================
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       VARCHAR(60)     NOT NULL,
  `title`      VARCHAR(200)    NOT NULL,
  `body`       TEXT                     DEFAULT NULL,
  `data`       JSON                     DEFAULT NULL,
  `icon`       VARCHAR(100)             DEFAULT NULL,
  `image`      VARCHAR(500)             DEFAULT NULL,
  `action_url` VARCHAR(500)             DEFAULT NULL,
  `is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
  `read_at`    DATETIME                 DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at` DESC),
  KEY `idx_notif_type` (`type`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 19. BADGES
-- =====================================================================
DROP TABLE IF EXISTS `badges`;
CREATE TABLE `badges` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80)     NOT NULL,
  `slug`       VARCHAR(100)    NOT NULL,
  `description` VARCHAR(255)             DEFAULT NULL,
  `icon`       VARCHAR(500)             DEFAULT NULL,
  `xp_required` INT UNSIGNED   NOT NULL DEFAULT 0,
  `type`       VARCHAR(50)              DEFAULT 'level',
  `rarity`     ENUM('common','rare','epic','legendary','mythic') NOT NULL DEFAULT 'common',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_badges_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 20. USER BADGES
-- =====================================================================
DROP TABLE IF EXISTS `user_badges`;
CREATE TABLE `user_badges` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `badge_id`   BIGINT UNSIGNED NOT NULL,
  `awarded_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_badge` (`user_id`,`badge_id`),
  CONSTRAINT `fk_ub_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_ub_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 21. ACHIEVEMENTS
-- =====================================================================
DROP TABLE IF EXISTS `achievements`;
CREATE TABLE `achievements` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120)    NOT NULL,
  `slug`        VARCHAR(140)    NOT NULL,
  `description` TEXT                     DEFAULT NULL,
  `icon`        VARCHAR(500)             DEFAULT NULL,
  `xp_reward`   INT UNSIGNED    NOT NULL DEFAULT 0,
  `coins_reward`INT UNSIGNED    NOT NULL DEFAULT 0,
  `criteria`    JSON                     DEFAULT NULL,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_achievements_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 22. USER ACHIEVEMENTS
-- =====================================================================
DROP TABLE IF EXISTS `user_achievements`;
CREATE TABLE `user_achievements` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `achievement_id`BIGINT UNSIGNED NOT NULL,
  `progress`      INT UNSIGNED    NOT NULL DEFAULT 0,
  `target`        INT UNSIGNED    NOT NULL DEFAULT 1,
  `is_completed`  TINYINT(1)      NOT NULL DEFAULT 0,
  `completed_at`  DATETIME                 DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_ach` (`user_id`,`achievement_id`),
  CONSTRAINT `fk_ua_user`  FOREIGN KEY (`user_id`)       REFERENCES `users`(`id`)        ON DELETE CASCADE,
  CONSTRAINT `fk_ua_ach`   FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 23. ANNOUNCEMENTS
-- =====================================================================
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(200)    NOT NULL,
  `body`       TEXT                     DEFAULT NULL,
  `image`      VARCHAR(500)             DEFAULT NULL,
  `type`       ENUM('info','warning','success','promo') NOT NULL DEFAULT 'info',
  `target`     ENUM('all','users','vip','agency') NOT NULL DEFAULT 'all',
  `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
  `starts_at`  DATETIME                 DEFAULT NULL,
  `ends_at`    DATETIME                 DEFAULT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ann_active` (`is_active`,`starts_at`,`ends_at`),
  CONSTRAINT `fk_ann_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 24. BANS
-- =====================================================================
DROP TABLE IF EXISTS `bans`;
CREATE TABLE `bans` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `banned_by`  BIGINT UNSIGNED NOT NULL,
  `reason`     VARCHAR(500)             DEFAULT NULL,
  `type`       ENUM('temporary','permanent') NOT NULL DEFAULT 'temporary',
  `expires_at` DATETIME                 DEFAULT NULL,
  `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bans_user`  (`user_id`,`is_active`),
  KEY `idx_bans_admin` (`banned_by`),
  CONSTRAINT `fk_bans_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bans_admin`  FOREIGN KEY (`banned_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 25. LOGIN HISTORY
-- =====================================================================
DROP TABLE IF EXISTS `login_history`;
CREATE TABLE `login_history` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED          DEFAULT NULL,
  `email`      VARCHAR(150)             DEFAULT NULL,
  `ip`         VARCHAR(45)     NOT NULL,
  `user_agent` VARCHAR(500)             DEFAULT NULL,
  `status`     ENUM('success','failed','blocked') NOT NULL DEFAULT 'success',
  `reason`     VARCHAR(255)             DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lh_user`  (`user_id`,`created_at` DESC),
  KEY `idx_lh_ip`    (`ip`,`created_at` DESC),
  KEY `idx_lh_status`(`status`,`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 26. ACTIVITY LOGS
-- =====================================================================
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED          DEFAULT NULL,
  `action`     VARCHAR(120)    NOT NULL,
  `subject_type` VARCHAR(60)             DEFAULT NULL,
  `subject_id` BIGINT UNSIGNED          DEFAULT NULL,
  `ip`         VARCHAR(45)              DEFAULT NULL,
  `user_agent` VARCHAR(500)             DEFAULT NULL,
  `metadata`   JSON                     DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_act_user`    (`user_id`,`created_at` DESC),
  KEY `idx_act_action`  (`action`),
  KEY `idx_act_subject` (`subject_type`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 27. SETTINGS (key-value app config)
-- =====================================================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_name`   VARCHAR(100)    NOT NULL,
  `value`      LONGTEXT                 DEFAULT NULL,
  `type`       VARCHAR(30)     NOT NULL DEFAULT 'string',
  `group_name` VARCHAR(60)              DEFAULT 'general',
  `description` VARCHAR(255)            DEFAULT NULL,
  `is_public`  TINYINT(1)      NOT NULL DEFAULT 0,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_settings_key` (`key_name`),
  KEY `idx_settings_group` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 28. PASSWORD RESETS
-- =====================================================================
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150)    NOT NULL,
  `token`      VARCHAR(100)    NOT NULL,
  `ip`         VARCHAR(45)              DEFAULT NULL,
  `expires_at` DATETIME        NOT NULL,
  `used_at`    DATETIME                 DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pw_reset_token` (`token`),
  KEY `idx_pw_reset_email` (`email`),
  KEY `idx_pw_reset_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 29. REFRESH TOKENS (for JWT)
-- =====================================================================
DROP TABLE IF EXISTS `refresh_tokens`;
CREATE TABLE `refresh_tokens` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64)        NOT NULL,
  `device`     VARCHAR(255)             DEFAULT NULL,
  `ip`         VARCHAR(45)              DEFAULT NULL,
  `user_agent` VARCHAR(500)             DEFAULT NULL,
  `expires_at` DATETIME        NOT NULL,
  `revoked_at` DATETIME                 DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_refresh_token` (`token_hash`),
  KEY `idx_refresh_user` (`user_id`),
  CONSTRAINT `fk_refresh_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 30. ROOM TAGS / FAVORITES
-- =====================================================================
DROP TABLE IF EXISTS `room_favorites`;
CREATE TABLE `room_favorites` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `room_id`    BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fav` (`user_id`,`room_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 31. USER XP HISTORY
-- =====================================================================
DROP TABLE IF EXISTS `xp_history`;
CREATE TABLE `xp_history` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `amount`     INT             NOT NULL,
  `action`     VARCHAR(60)     NOT NULL,
  `reference`  VARCHAR(120)             DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_xp_user` (`user_id`,`created_at` DESC),
  CONSTRAINT `fk_xp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 32. ROOM MESSAGES (in-room chat)
-- =====================================================================
DROP TABLE IF EXISTS `room_messages`;
CREATE TABLE `room_messages` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       ENUM('text','emoji','gift','system','join','leave') NOT NULL DEFAULT 'text',
  `content`    TEXT                     DEFAULT NULL,
  `data`       JSON                     DEFAULT NULL,
  `is_deleted` TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_room` (`room_id`,`created_at` DESC),
  KEY `idx_rm_user` (`user_id`),
  CONSTRAINT `fk_rm_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 33. WS EVENTS (real-time events for the WebSocket server)
-- =====================================================================
DROP TABLE IF EXISTS `ws_events`;
CREATE TABLE `ws_events` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    BIGINT UNSIGNED          DEFAULT NULL,
  `user_id`    BIGINT UNSIGNED          DEFAULT NULL,
  `event`      VARCHAR(80)     NOT NULL,
  `payload`    JSON                     DEFAULT NULL,
  `delivered`  TINYINT(1)      NOT NULL DEFAULT 0,
  `delivered_at` DATETIME               DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ws_events_room`     (`room_id`,`delivered`,`id`),
  KEY `idx_ws_events_delivered`(`delivered`,`id`),
  KEY `idx_ws_events_user`     (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 34. WS ROOM STATE (cached live state for a room)
-- =====================================================================
DROP TABLE IF EXISTS `ws_room_state`;
CREATE TABLE `ws_room_state` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    BIGINT UNSIGNED NOT NULL,
  `state`      JSON                     DEFAULT NULL,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ws_state_room` (`room_id`),
  CONSTRAINT `fk_ws_state_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 35. WS CONNECTIONS (active socket connections)
-- =====================================================================
DROP TABLE IF EXISTS `ws_connections`;
CREATE TABLE `ws_connections` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection_id` VARCHAR(64)  NOT NULL,
  `user_id`    BIGINT UNSIGNED          DEFAULT NULL,
  `room_id`    BIGINT UNSIGNED          DEFAULT NULL,
  `ip`         VARCHAR(45)              DEFAULT NULL,
  `user_agent` VARCHAR(500)             DEFAULT NULL,
  `connected_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disconnected_at` DATETIME            DEFAULT NULL,
  `last_ping_at` DATETIME               DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ws_conn` (`connection_id`),
  KEY `idx_ws_conn_user` (`user_id`),
  KEY `idx_ws_conn_room` (`room_id`),
  KEY `idx_ws_conn_active` (`disconnected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;








