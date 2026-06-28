SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist (for clean installer resets)
DROP TABLE IF EXISTS `{prefix}challenges_stories`;
DROP TABLE IF EXISTS `{prefix}challenges`;
DROP TABLE IF EXISTS `{prefix}shoutbox`;
DROP TABLE IF EXISTS `{prefix}poll_votes`;
DROP TABLE IF EXISTS `{prefix}poll_answers`;
DROP TABLE IF EXISTS `{prefix}poll`;
DROP TABLE IF EXISTS `{prefix}messages`;
DROP TABLE IF EXISTS `{prefix}favorites`;
DROP TABLE IF EXISTS `{prefix}reviews`;
DROP TABLE IF EXISTS `{prefix}series_stories`;
DROP TABLE IF EXISTS `{prefix}series`;
DROP TABLE IF EXISTS `{prefix}chapters`;
DROP TABLE IF EXISTS `{prefix}stories`;
DROP TABLE IF EXISTS `{prefix}characterizations`;
DROP TABLE IF EXISTS `{prefix}classifications`;
DROP TABLE IF EXISTS `{prefix}characters`;
DROP TABLE IF EXISTS `{prefix}genres`;
DROP TABLE IF EXISTS `{prefix}ratings`;
DROP TABLE IF EXISTS `{prefix}warnings`;
DROP TABLE IF EXISTS `{prefix}categories`;
DROP TABLE IF EXISTS `{prefix}news`;
DROP TABLE IF EXISTS `{prefix}pages`;
DROP TABLE IF EXISTS `{prefix}blocks`;
DROP TABLE IF EXISTS `{prefix}authorprefs`;
DROP TABLE IF EXISTS `{prefix}authors`;
DROP TABLE IF EXISTS `{prefix}settings`;
DROP TABLE IF EXISTS `{prefix}log`;

-- Core settings
CREATE TABLE `{prefix}settings` (
    `sitekey` varchar(40) NOT NULL,
    `tableprefix` varchar(25) NOT NULL DEFAULT '{prefix}',
    `siteemail` varchar(255) NOT NULL,
    `sitetitle` varchar(255) NOT NULL,
    `siteurl` varchar(255) NOT NULL,
    `language` varchar(10) NOT NULL DEFAULT 'en',
    `skin` varchar(40) NOT NULL DEFAULT 'default',
    `storiespath` varchar(255) NOT NULL,
    `imagespath` varchar(255) NOT NULL,
    `maintenance` tinyint(1) NOT NULL DEFAULT 0,
    `regvalidate` tinyint(1) NOT NULL DEFAULT 0,
    `store` tinyint(1) NOT NULL DEFAULT 1,
    `characters` tinyint(1) NOT NULL DEFAULT 1,
    `reviewsallowed` tinyint(1) NOT NULL DEFAULT 1,
    `anonreviews` tinyint(1) NOT NULL DEFAULT 1,
    `rateonly` tinyint(1) NOT NULL DEFAULT 0,
    `captcha` tinyint(1) NOT NULL DEFAULT 0,
    `allowed_tags` text DEFAULT NULL,
    `disallowed_tags` text DEFAULT NULL,
    `newsstories` int(11) NOT NULL DEFAULT 0,
    `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
    `version` varchar(20) NOT NULL DEFAULT '4.0.0',
    PRIMARY KEY (`sitekey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Authors
CREATE TABLE `{prefix}authors` (
    `uid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `penname` varchar(30) NOT NULL,
    `realname` varchar(100) DEFAULT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `age` int(3) DEFAULT NULL,
    `birthday` date DEFAULT NULL,
    `location` varchar(100) DEFAULT NULL,
    `bio` text DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `validated` tinyint(1) NOT NULL DEFAULT 1,
    `level` tinyint(1) NOT NULL DEFAULT 0,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `lastvisit` datetime DEFAULT NULL,
    `random` varchar(32) DEFAULT NULL,
    `new stories` enum('yes','no') NOT NULL DEFAULT 'yes',
    `new chapters` enum('yes','no') NOT NULL DEFAULT 'yes',
    `new reviews` enum('yes','no') NOT NULL DEFAULT 'yes',
    `new favorites` enum('yes','no') NOT NULL DEFAULT 'yes',
    `newsletter` enum('yes','no') NOT NULL DEFAULT 'yes',
    PRIMARY KEY (`uid`),
    UNIQUE KEY `penname` (`penname`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Author preferences
CREATE TABLE `{prefix}authorprefs` (
    `uid` int(11) unsigned NOT NULL,
    `userskin` varchar(40) DEFAULT 'default',
    `sortby` varchar(20) DEFAULT 'title',
    `tinychapters` tinyint(1) NOT NULL DEFAULT 0,
    `level` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uid`),
    CONSTRAINT `{prefix}fk_authorprefs_author` FOREIGN KEY (`uid`) REFERENCES `{prefix}authors` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories
CREATE TABLE `{prefix}categories` (
    `catid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `parentcatid` int(11) unsigned NOT NULL DEFAULT 0,
    `category` varchar(100) NOT NULL,
    `displayorder` int(11) NOT NULL DEFAULT 0,
    `locked` tinyint(1) NOT NULL DEFAULT 0,
    `numitems` int(11) NOT NULL DEFAULT 0,
    `description` text DEFAULT NULL,
    PRIMARY KEY (`catid`),
    KEY `parentcatid` (`parentcatid`),
    KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Characters
CREATE TABLE `{prefix}characters` (
    `charid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `catid` int(11) unsigned NOT NULL DEFAULT 0,
    `character` varchar(100) NOT NULL,
    `bio` text DEFAULT NULL,
    `image` varchar(255) DEFAULT NULL,
    `count` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`charid`),
    KEY `catid` (`catid`),
    KEY `character` (`character`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classifications (genres, warnings, story types, etc.)
CREATE TABLE `{prefix}classifications` (
    `classid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(20) NOT NULL,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `displayorder` int(11) NOT NULL DEFAULT 0,
    `count` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`classid`),
    KEY `type` (`type`),
    KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stories
CREATE TABLE `{prefix}stories` (
    `sid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `summary` text DEFAULT NULL,
    `storynotes` text DEFAULT NULL,
    `uid` int(11) unsigned NOT NULL,
    `catid` varchar(255) DEFAULT NULL,
    `charid` varchar(255) DEFAULT NULL,
    `classid` varchar(255) DEFAULT NULL,
    `rid` int(11) unsigned NOT NULL DEFAULT 0,
    `hidden` tinyint(1) NOT NULL DEFAULT 0,
    `validated` tinyint(1) NOT NULL DEFAULT 0,
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `completed` tinyint(1) NOT NULL DEFAULT 0,
    `comments` tinyint(1) NOT NULL DEFAULT 1,
    `rating` tinyint(1) NOT NULL DEFAULT 0,
    `wordcount` int(11) NOT NULL DEFAULT 0,
    `chapters` int(11) NOT NULL DEFAULT 1,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `rr` tinyint(1) NOT NULL DEFAULT 0,
    ` Challenges` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`sid`),
    KEY `uid` (`uid`),
    KEY `validated` (`validated`),
    KEY `completed` (`completed`),
    KEY `featured` (`featured`),
    KEY `updated` (`updated`),
    KEY `title` (`title`),
    CONSTRAINT `{prefix}fk_stories_author` FOREIGN KEY (`uid`) REFERENCES `{prefix}authors` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chapters
CREATE TABLE `{prefix}chapters` (
    `chapid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `sid` int(11) unsigned NOT NULL,
    `uid` int(11) unsigned NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `inorder` int(11) NOT NULL DEFAULT 0,
    `notes` text DEFAULT NULL,
    `endnotes` text DEFAULT NULL,
    `validated` tinyint(1) NOT NULL DEFAULT 1,
    `wordcount` int(11) NOT NULL DEFAULT 0,
    `rating` tinyint(1) NOT NULL DEFAULT 0,
    `reviews` int(11) NOT NULL DEFAULT 0,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`chapid`),
    KEY `sid` (`sid`),
    KEY `uid` (`uid`),
    KEY `inorder` (`inorder`),
    CONSTRAINT `{prefix}fk_chapters_story` FOREIGN KEY (`sid`) REFERENCES `{prefix}stories` (`sid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Series
CREATE TABLE `{prefix}series` (
    `seriesid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `summary` text DEFAULT NULL,
    `uid` int(11) unsigned NOT NULL,
    `isopen` tinyint(1) NOT NULL DEFAULT 1,
    `hidden` tinyint(1) NOT NULL DEFAULT 0,
    `validated` tinyint(1) NOT NULL DEFAULT 1,
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`seriesid`),
    KEY `uid` (`uid`),
    KEY `validated` (`validated`),
    CONSTRAINT `{prefix}fk_series_author` FOREIGN KEY (`uid`) REFERENCES `{prefix}authors` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Series stories mapping
CREATE TABLE `{prefix}series_stories` (
    `seriesid` int(11) unsigned NOT NULL,
    `sid` int(11) unsigned NOT NULL,
    `uid` int(11) unsigned NOT NULL,
    `inorder` int(11) NOT NULL DEFAULT 0,
    `confirmed` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`seriesid`,`sid`),
    KEY `sid` (`sid`),
    KEY `uid` (`uid`),
    CONSTRAINT `{prefix}fk_series_stories_series` FOREIGN KEY (`seriesid`) REFERENCES `{prefix}series` (`seriesid`) ON DELETE CASCADE,
    CONSTRAINT `{prefix}fk_series_stories_story` FOREIGN KEY (`sid`) REFERENCES `{prefix}stories` (`sid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews
CREATE TABLE `{prefix}reviews` (
    `reviewid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `sid` int(11) unsigned NOT NULL DEFAULT 0,
    `chapid` int(11) unsigned NOT NULL DEFAULT 0,
    `seriesid` int(11) unsigned NOT NULL DEFAULT 0,
    `uid` int(11) unsigned NOT NULL DEFAULT 0,
    `reviewer` varchar(100) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `review` text DEFAULT NULL,
    `rating` tinyint(1) NOT NULL DEFAULT 0,
    `validated` tinyint(1) NOT NULL DEFAULT 1,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reply` text DEFAULT NULL,
    PRIMARY KEY (`reviewid`),
    KEY `sid` (`sid`),
    KEY `chapid` (`chapid`),
    KEY `seriesid` (`seriesid`),
    KEY `uid` (`uid`),
    KEY `validated` (`validated`),
    KEY `date` (`date`),
    CONSTRAINT `{prefix}fk_reviews_story` FOREIGN KEY (`sid`) REFERENCES `{prefix}stories` (`sid`) ON DELETE CASCADE,
    CONSTRAINT `{prefix}fk_reviews_chapter` FOREIGN KEY (`chapid`) REFERENCES `{prefix}chapters` (`chapid`) ON DELETE CASCADE,
    CONSTRAINT `{prefix}fk_reviews_series` FOREIGN KEY (`seriesid`) REFERENCES `{prefix}series` (`seriesid`) ON DELETE CASCADE,
    CONSTRAINT `{prefix}fk_reviews_author` FOREIGN KEY (`uid`) REFERENCES `{prefix}authors` (`uid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites
CREATE TABLE `{prefix}favorites` (
    `favid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uid` int(11) unsigned NOT NULL,
    `sid` int(11) unsigned NOT NULL DEFAULT 0,
    `seriesid` int(11) unsigned NOT NULL DEFAULT 0,
    `authorid` int(11) unsigned NOT NULL DEFAULT 0,
    `type` enum('story','series','author') NOT NULL DEFAULT 'story',
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`favid`),
    KEY `uid` (`uid`),
    KEY `sid` (`sid`),
    KEY `seriesid` (`seriesid`),
    KEY `authorid` (`authorid`),
    UNIQUE KEY `unique_favorite` (`uid`,`sid`,`seriesid`,`authorid`),
    CONSTRAINT `{prefix}fk_favorites_author` FOREIGN KEY (`uid`) REFERENCES `{prefix}authors` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- News
CREATE TABLE `{prefix}news` (
    `nid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uid` int(11) unsigned NOT NULL,
    `title` varchar(255) NOT NULL,
    `content` text DEFAULT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`nid`),
    KEY `uid` (`uid`),
    KEY `date` (`date`),
    CONSTRAINT `{prefix}fk_news_author` FOREIGN KEY (`uid`) REFERENCES `{prefix}authors` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom pages
CREATE TABLE `{prefix}pages` (
    `pid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `content` text DEFAULT NULL,
    `page_url` varchar(100) NOT NULL,
    `displayorder` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`pid`),
    UNIQUE KEY `page_url` (`page_url`),
    KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blocks
CREATE TABLE `{prefix}blocks` (
    `blockid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `title` varchar(100) NOT NULL,
    `block` varchar(50) NOT NULL,
    `position` enum('header','left','right','footer') NOT NULL DEFAULT 'left',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `displayorder` int(11) NOT NULL DEFAULT 0,
    `content` text DEFAULT NULL,
    `options` text DEFAULT NULL,
    PRIMARY KEY (`blockid`),
    KEY `active` (`active`),
    KEY `position` (`position`),
    KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages
CREATE TABLE `{prefix}messages` (
    `messid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uid` int(11) unsigned NOT NULL,
    `fromuid` int(11) unsigned NOT NULL,
    `subject` varchar(255) NOT NULL,
    `message` text DEFAULT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read` tinyint(1) NOT NULL DEFAULT 0,
    `sent` tinyint(1) NOT NULL DEFAULT 1,
    `draft` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`messid`),
    KEY `uid` (`uid`),
    KEY `fromuid` (`fromuid`),
    KEY `read` (`read`),
    CONSTRAINT `{prefix}fk_messages_author` FOREIGN KEY (`uid`) REFERENCES `{prefix}authors` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Polls
CREATE TABLE `{prefix}poll` (
    `pollid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `question` varchar(255) NOT NULL,
    `started` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ended` datetime DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`pollid`),
    KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{prefix}poll_answers` (
    `answerid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `pollid` int(11) unsigned NOT NULL,
    `answer` varchar(255) NOT NULL,
    `votes` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`answerid`),
    KEY `pollid` (`pollid`),
    CONSTRAINT `{prefix}fk_poll_answers_poll` FOREIGN KEY (`pollid`) REFERENCES `{prefix}poll` (`pollid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{prefix}poll_votes` (
    `voteid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `pollid` int(11) unsigned NOT NULL,
    `answerid` int(11) unsigned NOT NULL,
    `uid` int(11) unsigned NOT NULL DEFAULT 0,
    `ip` varchar(45) DEFAULT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`voteid`),
    UNIQUE KEY `unique_vote` (`pollid`,`uid`,`ip`),
    KEY `answerid` (`answerid`),
    CONSTRAINT `{prefix}fk_poll_votes_poll` FOREIGN KEY (`pollid`) REFERENCES `{prefix}poll` (`pollid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shoutbox
CREATE TABLE `{prefix}shoutbox` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uid` int(11) unsigned NOT NULL DEFAULT 0,
    `name` varchar(100) NOT NULL,
    `message` varchar(500) NOT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Challenges module
CREATE TABLE `{prefix}challenges` (
    `chalid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uid` int(11) unsigned NOT NULL DEFAULT 0,
    `challenge` text NOT NULL,
    `title` varchar(255) NOT NULL,
    `anonymous` tinyint(1) NOT NULL DEFAULT 0,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closed` tinyint(1) NOT NULL DEFAULT 0,
    `responses` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`chalid`),
    KEY `uid` (`uid`),
    KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{prefix}challenges_stories` (
    `chalid` int(11) unsigned NOT NULL,
    `sid` int(11) unsigned NOT NULL,
    PRIMARY KEY (`chalid`,`sid`),
    KEY `sid` (`sid`),
    CONSTRAINT `{prefix}fk_challenges_stories_challenge` FOREIGN KEY (`chalid`) REFERENCES `{prefix}challenges` (`chalid`) ON DELETE CASCADE,
    CONSTRAINT `{prefix}fk_challenges_stories_story` FOREIGN KEY (`sid`) REFERENCES `{prefix}stories` (`sid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log
CREATE TABLE `{prefix}log` (
    `logid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uid` int(11) unsigned NOT NULL DEFAULT 0,
    `action` varchar(50) NOT NULL,
    `item` varchar(100) DEFAULT NULL,
    `details` text DEFAULT NULL,
    `ip` varchar(45) DEFAULT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`logid`),
    KEY `uid` (`uid`),
    KEY `action` (`action`),
    KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
