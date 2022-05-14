
SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE TABLE `account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `stop_nagging` int(11) unsigned DEFAULT NULL,
  `admin` int(11) unsigned DEFAULT NULL,
  `banned` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `donator` int(11) DEFAULT NULL,
  `hide_thanks` int(11) unsigned DEFAULT NULL,
  `daily_limit` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


CREATE TABLE `comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `body` text COLLATE utf8mb4_unicode_520_ci,
  `delay` int(11) unsigned DEFAULT NULL,
  `post_id` int(11) unsigned DEFAULT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `error` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_comment_post` (`post_id`),
  CONSTRAINT `c_fk_comment_post_id` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


CREATE TABLE `post` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subreddit` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `title` text COLLATE utf8mb4_unicode_520_ci,
  `body` text COLLATE utf8mb4_unicode_520_ci,
  `account_id` int(11) unsigned DEFAULT NULL,
  `when` double DEFAULT NULL,
  `whenzone` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `error` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sendreplies` int(11) unsigned DEFAULT NULL,
  `nsfw` int(11) unsigned DEFAULT NULL,
  `ratelimit_count` int(11) unsigned DEFAULT NULL,
  `ratelimit_sum` int(11) unsigned DEFAULT NULL,
  `when_original` int(11) unsigned DEFAULT NULL,
  `when_posted` int(11) unsigned DEFAULT NULL,
  `bulk` tinyint(1) unsigned DEFAULT NULL,
  `deleted` tinyint(1) unsigned DEFAULT NULL,
  `flair_identifier` text COLLATE utf8mb4_unicode_520_ci,
  `flair_text` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_post_account` (`account_id`),
  KEY `when` (`when`),
  KEY `when_original` (`when_original`),
  KEY `when_posted` (`when_posted`),
  KEY `account_id_when` (`account_id`,`when`),
  CONSTRAINT `c_fk_post_account_id` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


CREATE TABLE `shard` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `proxy` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `when` int(11) unsigned DEFAULT NULL,
  `rate_limit` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
