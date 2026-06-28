-- eFiction 4.0.0 initial schema
-- MySQL 8.0+ / MariaDB 10.5+
-- Default table prefix: fanfiction_

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Authors / users
CREATE TABLE IF NOT EXISTS authors (
    uid INT AUTO_INCREMENT PRIMARY KEY,
    penname VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    realname VARCHAR(120) DEFAULT NULL,
    age INT DEFAULT NULL,
    birthday DATE DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    occupation VARCHAR(255) DEFAULT NULL,
    hobbies TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    interests TEXT DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    level TINYINT NOT NULL DEFAULT 0,
    validated TINYINT NOT NULL DEFAULT 0,
    emailvalidated TINYINT NOT NULL DEFAULT 0,
    newemail VARCHAR(255) DEFAULT NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lastvisit DATETIME DEFAULT NULL,
    userskin VARCHAR(60) DEFAULT 'default',
    language VARCHAR(10) DEFAULT 'en',
    dateformat VARCHAR(40) DEFAULT 'Y-m-d',
    timeformat VARCHAR(40) DEFAULT 'H:i',
    timezone VARCHAR(60) DEFAULT 'UTC',
    consent TINYINT NOT NULL DEFAULT 0,
    cookies TINYINT NOT NULL DEFAULT 0,
    icon VARCHAR(255) DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_level (level),
    INDEX idx_validated (validated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Author preferences
CREATE TABLE IF NOT EXISTS authorprefs (
    uid INT NOT NULL PRIMARY KEY,
    sortby VARCHAR(40) DEFAULT 'title',
    storyindex TINYINT NOT NULL DEFAULT 1,
    rich_editor TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    catid INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(120) NOT NULL,
    parentcatid INT NOT NULL DEFAULT 0,
    displayorder INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    locked TINYINT NOT NULL DEFAULT 0,
    INDEX idx_parent (parentcatid),
    INDEX idx_order (displayorder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classifications (ratings, genres, warnings, characters, challenges)
CREATE TABLE IF NOT EXISTS classifications (
    classid INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(40) NOT NULL,
    name VARCHAR(120) NOT NULL,
    displayorder INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    locked TINYINT NOT NULL DEFAULT 0,
    UNIQUE KEY idx_type_name (type, name),
    INDEX idx_type_order (type, displayorder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stories
CREATE TABLE IF NOT EXISTS stories (
    sid INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    summary TEXT DEFAULT NULL,
    storynotes TEXT DEFAULT NULL,
    uid INT NOT NULL,
    catid VARCHAR(255) DEFAULT NULL,
    rating INT DEFAULT NULL,
    genre VARCHAR(255) DEFAULT NULL,
    warnings VARCHAR(255) DEFAULT NULL,
    characters VARCHAR(255) DEFAULT NULL,
    challenges VARCHAR(255) DEFAULT NULL,
    ccount INT NOT NULL DEFAULT 0,
    completed TINYINT NOT NULL DEFAULT 0,
    validated TINYINT NOT NULL DEFAULT 0,
    featured TINYINT NOT NULL DEFAULT 0,
    hidden TINYINT NOT NULL DEFAULT 0,
    count INT NOT NULL DEFAULT 0,
    score DECIMAL(3,2) DEFAULT 0.00,
    reviews INT NOT NULL DEFAULT 0,
    views INT NOT NULL DEFAULT 0,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_uid (uid),
    INDEX idx_validated (validated),
    INDEX idx_completed (completed),
    INDEX idx_featured (featured),
    INDEX idx_rating (rating),
    INDEX idx_updated (updated),
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE CASCADE,
    FOREIGN KEY (rating) REFERENCES classifications(classid) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chapters
CREATE TABLE IF NOT EXISTS chapters (
    chapid INT AUTO_INCREMENT PRIMARY KEY,
    sid INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    inorder INT NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    endnotes TEXT DEFAULT NULL,
    text LONGTEXT DEFAULT NULL,
    validated TINYINT NOT NULL DEFAULT 0,
    rating INT DEFAULT NULL,
    views INT NOT NULL DEFAULT 0,
    wordcount INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sid (sid),
    INDEX idx_order (sid, inorder),
    FOREIGN KEY (sid) REFERENCES stories(sid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coauthors (many-to-many)
CREATE TABLE IF NOT EXISTS inauthors (
    sid INT NOT NULL,
    uid INT NOT NULL,
    PRIMARY KEY (sid, uid),
    FOREIGN KEY (sid) REFERENCES stories(sid) ON DELETE CASCADE,
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Series
CREATE TABLE IF NOT EXISTS series (
    seriesid INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    seriesdescription TEXT DEFAULT NULL,
    uid INT NOT NULL,
    validated TINYINT NOT NULL DEFAULT 0,
    featured TINYINT NOT NULL DEFAULT 0,
    completed TINYINT NOT NULL DEFAULT 0,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uid (uid),
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Series stories
CREATE TABLE IF NOT EXISTS series_stories (
    seriesid INT NOT NULL,
    sid INT NOT NULL,
    inorder INT NOT NULL DEFAULT 0,
    PRIMARY KEY (seriesid, sid),
    INDEX idx_series_order (seriesid, inorder),
    FOREIGN KEY (seriesid) REFERENCES series(seriesid) ON DELETE CASCADE,
    FOREIGN KEY (sid) REFERENCES stories(sid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    reviewid INT AUTO_INCREMENT PRIMARY KEY,
    item INT NOT NULL,
    type ENUM('ST', 'SE') NOT NULL DEFAULT 'ST',
    chapid INT DEFAULT NULL,
    rating INT DEFAULT NULL,
    reviewer VARCHAR(120) DEFAULT NULL,
    review TEXT NOT NULL,
    uid INT DEFAULT NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    respond TEXT DEFAULT NULL,
    approver INT DEFAULT NULL,
    validated TINYINT NOT NULL DEFAULT 0,
    INDEX idx_item_type (item, type),
    INDEX idx_chapid (chapid),
    INDEX idx_uid (uid),
    INDEX idx_validated (validated),
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE SET NULL,
    FOREIGN KEY (chapid) REFERENCES chapters(chapid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites
CREATE TABLE IF NOT EXISTS favorites (
    favid INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    item INT NOT NULL,
    type ENUM('ST', 'SE', 'AU') NOT NULL DEFAULT 'ST',
    comments TEXT DEFAULT NULL,
    visible TINYINT NOT NULL DEFAULT 1,
    notify TINYINT NOT NULL DEFAULT 0,
    added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_uid_item_type (uid, item, type),
    INDEX idx_uid (uid),
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alerts
CREATE TABLE IF NOT EXISTS alerts (
    alertid INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    item INT NOT NULL,
    type ENUM('ST', 'SE', 'AU') NOT NULL DEFAULT 'ST',
    notify TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_uid_item_type (uid, item, type),
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Private messages
CREATE TABLE IF NOT EXISTS messages (
    messageid INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    uid INT NOT NULL,
    fromuid INT NOT NULL,
    folder ENUM('inbox', 'outbox') NOT NULL DEFAULT 'inbox',
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read TINYINT NOT NULL DEFAULT 0,
    alert TINYINT NOT NULL DEFAULT 0,
    INDEX idx_uid_folder (uid, folder),
    INDEX idx_read (read),
    FOREIGN KEY (uid) REFERENCES authors(uid) ON DELETE CASCADE,
    FOREIGN KEY (fromuid) REFERENCES authors(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin / user navigation panels
CREATE TABLE IF NOT EXISTS panels (
    panel_id INT AUTO_INCREMENT PRIMARY KEY,
    panel_title VARCHAR(120) NOT NULL,
    panel_url VARCHAR(255) DEFAULT NULL,
    panel_parent INT NOT NULL DEFAULT 0,
    panel_order INT NOT NULL DEFAULT 0,
    panel_level TINYINT NOT NULL DEFAULT 0,
    panel_type ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    panel_icon VARCHAR(60) DEFAULT NULL,
    panel_hidden TINYINT NOT NULL DEFAULT 0,
    panel_access TINYINT NOT NULL DEFAULT 0,
    INDEX idx_parent (panel_parent),
    INDEX idx_order (panel_order),
    INDEX idx_type (panel_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Skin blocks
CREATE TABLE IF NOT EXISTS blocks (
    block_id INT AUTO_INCREMENT PRIMARY KEY,
    block_name VARCHAR(60) NOT NULL UNIQUE,
    block_title VARCHAR(120) NOT NULL,
    block_file VARCHAR(120) DEFAULT NULL,
    block_status TINYINT NOT NULL DEFAULT 1,
    block_variables TEXT DEFAULT NULL,
    block_cache TEXT DEFAULT NULL,
    type ENUM('left', 'right', 'center', 'bottom') NOT NULL DEFAULT 'left',
    displayorder INT NOT NULL DEFAULT 0,
    INDEX idx_status (block_status),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site settings (multi-site capable by sitekey)
CREATE TABLE IF NOT EXISTS settings (
    sitekey VARCHAR(40) NOT NULL DEFAULT 'default',
    variable VARCHAR(120) NOT NULL,
    value TEXT DEFAULT NULL,
    PRIMARY KEY (sitekey, variable)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Action log
CREATE TABLE IF NOT EXISTS log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    log_action VARCHAR(120) NOT NULL,
    log_data TEXT DEFAULT NULL,
    log_type VARCHAR(40) DEFAULT 'general',
    log_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    log_uid INT DEFAULT NULL,
    INDEX idx_type (log_type),
    INDEX idx_date (log_date),
    INDEX idx_uid (log_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Simple cache table
CREATE TABLE IF NOT EXISTS cache (
    cache_id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(120) NOT NULL UNIQUE,
    cache_value LONGTEXT DEFAULT NULL,
    cache_type VARCHAR(40) DEFAULT 'general',
    expires_at DATETIME DEFAULT NULL,
    INDEX idx_key (cache_key),
    INDEX idx_type (cache_type),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Seed default classifications
INSERT INTO classifications (type, name, displayorder, description) VALUES
('rating', 'K', 10, 'Content suitable for all ages'),
('rating', 'K+', 20, 'Some content may not be suitable for young children'),
('rating', 'T', 30, 'Content suitable for teens'),
('rating', 'M', 40, 'Content suitable for mature teens and older'),
('rating', 'MA', 50, 'Content suitable only for adults')
ON DUPLICATE KEY UPDATE name = name;

-- Seed default user panels
INSERT INTO panels (panel_title, panel_url, panel_parent, panel_order, panel_level, panel_type, panel_icon) VALUES
('Home', '/', 0, 10, 0, 'user', 'home'),
('Browse', '/browse', 0, 20, 0, 'user', 'list'),
('Search', '/search', 0, 30, 0, 'user', 'search'),
('Contact', '/contact', 0, 40, 0, 'user', 'envelope'),
('Login', '/user/login', 0, 50, 0, 'user', 'sign-in'),
('Register', '/user/register', 0, 60, 0, 'user', 'user-plus')
ON DUPLICATE KEY UPDATE panel_title = panel_title;

-- Seed default admin panels
INSERT INTO panels (panel_title, panel_url, panel_parent, panel_order, panel_level, panel_type, panel_icon) VALUES
('Dashboard', '/admin', 0, 10, 1, 'admin', 'tachometer'),
('Settings', '/admin/settings', 0, 20, 1, 'admin', 'cog'),
('Members', '/admin/members', 0, 30, 1, 'admin', 'users'),
('Stories', '/admin/stories', 0, 40, 1, 'admin', 'book'),
('Categories', '/admin/categories', 0, 50, 1, 'admin', 'folder'),
('Classifications', '/admin/classifications', 0, 60, 1, 'admin', 'tags'),
('Validations', '/admin/validations', 0, 70, 1, 'admin', 'check-circle'),
('Skins', '/admin/skins', 0, 80, 1, 'admin', 'paint-brush'),
('Blocks', '/admin/blocks', 0, 90, 1, 'admin', 'th-large'),
('Panels', '/admin/panels', 0, 100, 1, 'admin', 'sitemap'),
('Logs', '/admin/logs', 0, 110, 1, 'admin', 'history')
ON DUPLICATE KEY UPDATE panel_title = panel_title;

-- Seed default blocks
INSERT INTO blocks (block_name, block_title, block_file, block_status, type, displayorder) VALUES
('menu', 'Menu', 'menu', 1, 'left', 10),
('latest_stories', 'Latest Stories', 'latest_stories', 1, 'right', 10),
('featured_story', 'Featured Story', 'featured_story', 1, 'right', 20),
('statistics', 'Statistics', 'statistics', 1, 'right', 30),
('footer', 'Footer', 'footer', 1, 'bottom', 10)
ON DUPLICATE KEY UPDATE block_name = block_name;
